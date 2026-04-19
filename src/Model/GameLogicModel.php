<?php

namespace App\Model;

use App\Dto\GameActions;
use App\Dto\GameStates;
use App\Dto\Request\GameActionDto;
use App\Dto\RoomEvents;
use App\Entity\Card;
use App\Entity\Guess;
use App\Entity\Room;
use App\Repository\CardRepository;
use App\Repository\PlayerRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use JsonException;

class GameLogicModel
{
    public function __construct(
        public RoomRepository $roomRepository,
        public PlayerRepository $playerRepository,
        public CardRepository $cardRepository,
        public EntityManagerInterface $entityManager,
        public HubInterface $hub,
        public SerializerInterface $serializer,
    ) {}

    public function pushRoomUpdate(Room $room): void
    {
        $payload = $this->serializer->normalize($room, 'array');
        $payload = $this->forceListSerializationOnCollections($payload);

        try {
            $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $payloadJson = $this->serializer->serialize($room, 'json');
        }

        // Inform subscribers about changes
        $update = new Update(
            'room-' . $room->getUuid(),
            $payloadJson,
        );

        $this->hub->publish($update);
    }

    private function forceListSerializationOnCollections(array $payload): array
    {
        if ($this->isIntKeyedArray($payload)) {
            $payload = array_values($payload);
        }

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->forceListSerializationOnCollections($value);
            }
        }

        return $payload;
    }

    private function isIntKeyedArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        foreach (array_keys($value) as $key) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    public function getUnplayedCard(Room $room, bool $includeAlreadyPlayedCards = false): Card
    {
        $maxCards = $this->cardRepository->count();

        $randomCardId = random_int(1, $maxCards);

        if ($includeAlreadyPlayedCards) {
            return $this->cardRepository->findOneBy(['id' => $randomCardId]);
        }

        $arrUnplayedCards = $this->cardRepository->findAllNotIn($room->getPlayedCards() ?? []);
        $randomCardId = random_int(0, count($arrUnplayedCards) - 1);

        return $arrUnplayedCards[$randomCardId];
    }

    public function executeGameAction(Room $room, GameActionDto $dto): void
    {
        switch ($dto->action) {
            case GameActions::$CREATE_NEW_GAME: $this->gameActionNewGame($room); break;
            case GameActions::$NEW_CARDS: $this->gameActionNewCards($room); break;
            case GameActions::$NEW_OR_OLD_CARDS: $this->gameActionNewCards($room, true); break;
            case GameActions::$START_SPINNING: $this->gameActionStartSpinning($room); break;
            case GameActions::$SUBMIT_CLUEGIVER_CLUE: $this->gameActionSubmitCluegiverClue($room, $dto); break;
            case GameActions::$SUBMIT_GUESS: $this->gameActionSubmitGuess($room, $dto, false); break;
            case GameActions::$SUBMIT_PREVIEW_GUESS: $this->gameActionSubmitGuess($room, $dto, true); break;
            case GameActions::$REMOVE_PREVIEW_GUESS: $this->gameActionRemovePreviewGuess($room, $dto); break;
            case GameActions::$REVEAL: $this->gameActionReveal($room); break;
            case GameActions::$NEXT_ROUND: $this->gameActionNextRound($room); break;
        }

        $this->entityManager->flush();

        $this->pushRoomUpdate($room);
    }

    public function gameActionNewGame(Room $room): void
    {
        if ($room->getPlayersTeamA()->isEmpty() || $room->getPlayersTeamB()->isEmpty()) {
            return;
        }

        $room->setGamePointsTeamA(0);
        $room->setGamePointsTeamB(1);
        $room->setGameState(GameStates::$STATE_00_START);
        $room->setGameTargetDegree(null);
        $room->setGameCluegiverGuessText(null);
        $room->setGameRoundIndex(0);
        $room->removeAllPlayedCards();
        foreach ($room->getGameGuesses() as $gameGuesses) {
            $this->entityManager->remove($gameGuesses);
        }
        $room->removeAllGameGuesses();

        $room->setGameActivePlayer($room->getPlayersTeamA()->first());
        $room->setGameActiveCard($this->getUnplayedCard($room));

        $this->entityManager->persist($room);
    }

    public function gameActionNextRound(Room $room): void
    {
        if ($room->getPlayersTeamA()->isEmpty() || $room->getPlayersTeamB()->isEmpty()) {
            return;
        }

        if ($room->getGameState() !== GameStates::$STATE_04_REVEAL) {
            return;
        }

        $futureGameIndex = $room->getGameRoundIndex() + 1;

        // Set active player based on round index and current game state
        $activePlayers = $futureGameIndex % 2 === 0 ? $room->getPlayersTeamA() : $room->getPlayersTeamB();
        $activePlayerIndex = (intdiv($futureGameIndex, 2)) % count($activePlayers);
        $room->setGameActivePlayer($activePlayers[$activePlayerIndex]);

        $room->setGameState(GameStates::$STATE_00_START);
        $room->setGameTargetDegree(null);
        $room->setGameCluegiverGuessText(null);
        $room->setGameRoundIndex($futureGameIndex);
        $room->setGameActiveCard($this->getUnplayedCard($room));

        foreach ($room->getGameGuesses() as $gameGuesses) {
            $this->entityManager->remove($gameGuesses);
        }
        $room->removeAllGameGuesses();

        $this->entityManager->persist($room);
    }

    public function gameActionNewCards(Room $room, bool $alsoUseOldCards = false): void
    {
        if ($room->getGameState() !== GameStates::$STATE_00_START) {
            return;
        }

        $card = $this->getUnplayedCard($room, $alsoUseOldCards);

        $room->setGameActiveCard($card);

        $this->entityManager->persist($room);
    }

    public function gameActionStartSpinning(Room $room): void
    {
        if ($room->getGameState() !== GameStates::$STATE_00_START) {
            return;
        }

        // A semi circle is 180 degree
        // We cut of 10 degree on each side that leaves us with 160 degree
        // To get a random float between 0 and 160 degree we do this:
        $room->setGameTargetDegree(random_int(0, 16000) / 100);

        $room->setGameState(GameStates::getNextState($room->getGameState()));

        $this->entityManager->persist($room);
    }
    public function gameActionSubmitCluegiverClue(Room $room, GameActionDto $dto): void {
        if ($room->getGameState() !== GameStates::$STATE_01_SHOW_HIDDEN_VALUE) {
            return;
        }

        $room->setGameCluegiverGuessText($dto->value);

        $room->setGameState(GameStates::getNextState($room->getGameState()));

        $this->entityManager->persist($room);
    }
    public function gameActionSubmitGuess(Room $room, GameActionDto $dto, bool $isPreview): void
    {
        if ($room->getGameState() !== GameStates::$STATE_02_GUESS_ROUND && $room->getGameState() !== GameStates::$STATE_03_COUNTER_GUESS_ROUND) {
            return;
        }

        $player = $this->playerRepository->findOneBy(['uuid' => $dto->uuidPlayer]);
        if ($player === null) {
            throw new NotFoundHttpException('Failed to find given player.');
        }

        if ($room->getGameActivePlayer()->getId() === $player->getId()) {
            return;
        }

        // Disallow spectators to have non-preview votes
        if (!$isPreview) {
            $playerIsInTeamA = $room->getPlayersTeamA()->contains($player);
            $playerIsInTeamB = $room->getPlayersTeamB()->contains($player);

            if (!($playerIsInTeamA || $playerIsInTeamB)) {
                return;
            }
        }

        // Disallow new guesses if a final guess has been issued
        foreach ($player->getGuesses() as $guess) {
            if (!$guess->isPreview() && $guess->getRoom() === $room) {
                return;
            }
        }

        // Remove previous preview guesses of that player
        foreach ($player->getGuesses() as $guess) {
            if ($guess->isPreview() && $guess->getRoom() === $room) {
                $room->removeGameGuess($guess);
                $this->entityManager->remove($guess);
            }
        }

        $guess = new Guess();
        $guess->setRoom($room);
        $guess->setPlayer($player);
        $guess->setDegree(floatval($dto->value));
        $guess->setIsPreview($isPreview);

        $room->addGameGuess($guess);

        $this->entityManager->persist($guess);
        $this->entityManager->persist($player);

        $isEvenRound = $room->getGameRoundIndex() % 2 === 0;
        $isGuessRound = $room->getGameState() === GameStates::$STATE_02_GUESS_ROUND;
        $isCounterGuessRound = $room->getGameState() === GameStates::$STATE_03_COUNTER_GUESS_ROUND;

        $isTeamATurn = ($isEvenRound && $isGuessRound) || (!$isEvenRound && $isCounterGuessRound);
        $arrPlayingPlayers = $isTeamATurn ? $room->getPlayersTeamA() : $room->getPlayersTeamB();
        $arrFinalGuessesPlayingTeam = $isPreview ? [] : [$guess];
        foreach ($arrPlayingPlayers as $player) {
            foreach ($player->getGuesses() as $playerGuess) {
                if ($playerGuess->getRoom() === $room && !$playerGuess->isPreview()) {
                    $arrFinalGuessesPlayingTeam[] = $playerGuess;
                }
            }
        }

        // One less player needed because in a guess round one player is the cluegiver instead of a player
        $quantityOfPlayingPlayers = $isGuessRound ? (count($arrPlayingPlayers) - 1) : count($arrPlayingPlayers);
        $isRoundOver = count($arrFinalGuessesPlayingTeam) === $quantityOfPlayingPlayers;
        if ($isRoundOver && ($room->getGameState() === GameStates::$STATE_02_GUESS_ROUND || $room->getGameState() === GameStates::$STATE_03_COUNTER_GUESS_ROUND)) {
            $room->setGameState(GameStates::getNextState($room->getGameState()));
            if ($room->getGameState() === GameStates::$STATE_04_REVEAL) {
                $this->applyRevealScoring($room);
            }
        }

        $this->entityManager->persist($guess);
        $this->entityManager->persist($room);
    }

    public function gameActionRemovePreviewGuess(Room $room, GameActionDto $dto): void
    {
        if ($room->getGameState() !== GameStates::$STATE_02_GUESS_ROUND && $room->getGameState() !== GameStates::$STATE_03_COUNTER_GUESS_ROUND) {
            return;
        }

        $player = $this->playerRepository->findOneBy(['uuid' => $dto->uuidPlayer]);
        if ($player === null) {
            throw new NotFoundHttpException('Failed to find given player.');
        }

        foreach ($player->getGuesses() as $guess) {
            if ($guess->isPreview() && $guess->getRoom() === $room) {
                $room->removeGameGuess($guess);
                $this->entityManager->remove($guess);
            }
        }

        $this->entityManager->persist($room);
    }

    public function gameActionReveal(Room $room): void
    {
        if ($room->getGameState() !== GameStates::$STATE_03_COUNTER_GUESS_ROUND) {
            return;
        }

        $this->lockInCounterTeamPreviewGuesses($room);
        $this->applyRevealScoring($room);

        $room->setGameState(GameStates::getNextState($room->getGameState()));

        $this->entityManager->persist($room);
    }

    /**
     * Even round index: team A guesses, team B counters. Odd: team B guesses, team A counters.
     */
    private function guessingTeamIsTeamA(Room $room): bool
    {
        return $room->getGameRoundIndex() % 2 === 0;
    }

    /**
     * @return list<float>
     */
    private function nonPreviewGuessDegreesForTeam(Room $room, $teamPlayers): array
    {
        $degrees = [];
        foreach ($teamPlayers as $player) {
            foreach ($player->getGuesses() as $guess) {
                if ($guess->getRoom() === $room && !$guess->isPreview()) {
                    $degrees[] = $guess->getDegree();
                }
            }
        }

        return $degrees;
    }

    private function lockInCounterTeamPreviewGuesses(Room $room): void
    {
        $counterPlayers = $this->guessingTeamIsTeamA($room)
            ? $room->getPlayersTeamB()
            : $room->getPlayersTeamA();

        foreach ($counterPlayers as $player) {
            foreach ($player->getGuesses() as $guess) {
                if ($guess->getRoom() === $room && $guess->isPreview()) {
                    $guess->setIsPreview(false);
                }
            }
        }
    }

    private function applyRevealScoring(Room $room): void
    {
        $target = $room->getGameTargetDegree();
        if ($target === null) {
            return;
        }

        $guessingTeamA = $this->guessingTeamIsTeamA($room);
        $guessingPlayers = $guessingTeamA ? $room->getPlayersTeamA() : $room->getPlayersTeamB();
        $counterPlayers = $guessingTeamA ? $room->getPlayersTeamB() : $room->getPlayersTeamA();

        $guessingDegrees = $this->nonPreviewGuessDegreesForTeam($room, $guessingPlayers);
        $counterDegrees = $this->nonPreviewGuessDegreesForTeam($room, $counterPlayers);

        $guessingAvg = count($guessingDegrees) > 0
            ? array_sum($guessingDegrees) / count($guessingDegrees)
            : null;
        $counterAvg = count($counterDegrees) > 0
            ? array_sum($counterDegrees) / count($counterDegrees)
            : 80.0;

        $guessingPoints = $guessingAvg !== null
            ? $this->pointsForGuessingTeamDistance(abs($guessingAvg - $target))
            : 0;

        $counterSidePoint = 0;
        if ($guessingAvg !== null) {
            if ($guessingPoints === 4) {
                // Guessing team hit the center segment — no counter bonus for side.
            } elseif ($guessingAvg < $target) {
                if ($counterAvg > $guessingAvg) {
                    $counterSidePoint = 1;
                }
            } elseif ($guessingAvg > $target) {
                if ($counterAvg < $guessingAvg) {
                    $counterSidePoint = 1;
                }
            }
        }

        $pointsA = $room->getGamePointsTeamA() ?? 0;
        $pointsB = $room->getGamePointsTeamB() ?? 0;

        if ($guessingTeamA) {
            $pointsA += $guessingPoints;
            $pointsB += $counterSidePoint;
        } else {
            $pointsB += $guessingPoints;
            $pointsA += $counterSidePoint;
        }

        $room->setGamePointsTeamA($pointsA);
        $room->setGamePointsTeamB($pointsB);
    }

    /**
     * Single best tier — bands do not stack. Degrees are absolute distance to target.
     */
    private function pointsForGuessingTeamDistance(float $distance): int
    {
        $segmentSize = 6;

        if ($distance <= ($segmentSize / 2)) {
            return 4;
        }
        if ($distance <= (($segmentSize / 2) + $segmentSize)) {
            return 3;
        }
        if ($distance <= (($segmentSize / 2) + $segmentSize + $segmentSize)) {
            return 2;
        }

        return 0;
    }
}
