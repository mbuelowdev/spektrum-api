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
        // Inform subscribers about changes
        $update = new Update(
            'room-' . $room->getUuid(),
            $this->serializer->serialize($room, 'json'),
        );

        $this->hub->publish($update);
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

        // Set active player based on round index and current game state
        $activePlayers = $room->getGameRoundIndex() % 2 === 0 ? $room->getPlayersTeamA() : $room->getPlayersTeamB();
        $activePlayerIndex = ($room->getGameRoundIndex() /~ 2) % count($activePlayers);
        $room->setGameActivePlayer($activePlayers[$activePlayerIndex]);

        $room->setGameState(GameStates::$STATE_00_START);
        $room->setGameTargetDegree(null);
        $room->setGameCluegiverGuessText(null);
        $room->setGameRoundIndex($room->getGameRoundIndex() + 1);

        foreach ($room->getGameGuesses() as $gameGuesses) {
            $this->entityManager->remove($gameGuesses);
        }
        $room->removeAllGameGuesses();

        // Determine next player
        // We zip them together in an index based array
        $arrPlayersTeamA = $room->getPlayersTeamA();
        $arrPlayersTeamB = $room->getPlayersTeamB();


        $room->setGameActivePlayer($room->getPlayersTeamA()->first());
        $room->setGameActiveCard($this->getUnplayedCard($room));

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
        $isGuessRound = $room->getGameState() !== GameStates::$STATE_02_GUESS_ROUND;
        $isCounterGuessRound = $room->getGameState() !== GameStates::$STATE_03_COUNTER_GUESS_ROUND;

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

        $isRoundOver = count($arrFinalGuessesPlayingTeam) === (count($arrPlayingPlayers) - 1);
        if ($isRoundOver && ($room->getGameState() === GameStates::$STATE_02_GUESS_ROUND || $room->getGameState() === GameStates::$STATE_03_COUNTER_GUESS_ROUND)) {
            $room->setGameState(GameStates::getNextState($room->getGameState()));
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

        //TODO Set points based on answers

        $room->setGameState(GameStates::getNextState($room->getGameState()));

        $this->entityManager->persist($room);
    }
}
