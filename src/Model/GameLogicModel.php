<?php

namespace App\Model;

use App\Entity\Card;
use App\Entity\Room;
use App\Repository\CardRepository;
use App\Repository\PlayerRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

class GameLogicModel
{
    public function __construct(
        public RoomRepository $roomRepository,
        public PlayerRepository $playerRepository,
        public CardRepository $cardRepository,
        public EntityManagerInterface $entityManager,
    ) {}

    public function getUnplayedCard(Room $room, bool $includeAlreadyPlayedCards = false): Card
    {
        $maxCards = $this->cardRepository->count();

        $randomCardId = random_int(0, $maxCards - 1);

        if ($includeAlreadyPlayedCards) {
            return $this->cardRepository->findOneBy(['id' => $randomCardId]);
        }
        $arrUnplayedCards = $this->cardRepository->findAllNotIn($room->getPlayedCards() ?? []);
        $randomCardId = random_int(0, count($arrUnplayedCards) - 1);

        return $arrUnplayedCards[$randomCardId];
    }

    public function getGameStateArray(Room $room)
    {
        return [
            'gameState' => $room->getGameState(),
            'gamePointsTeamA' => $room->getPointsTeamA(),
            'gamePointsTeamB' => $room->getPointsTeamB(),
            'gameActivePlayerUUID' => $room->getActivePlayer(),
            'gameRoundIndex' => $room->getRoundIndex(),
            'gameActiveCardId' => $room->getActiveCard(),
        ];
    }
}
