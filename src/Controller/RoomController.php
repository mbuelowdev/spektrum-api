<?php

namespace App\Controller;

use App\Dto\GameActions;
use App\Dto\GameStates;
use App\Dto\Request\CreateRoomDto;
use App\Dto\Request\GameActionDto;
use App\Dto\Request\JoinRoomDto;
use App\Dto\Request\SwitchTeamDto;
use App\Dto\RoomEvents;
use App\Entity\Player;
use App\Entity\Room;
use App\Model\GameLogicModel;
use App\Repository\PlayerRepository;
use App\Repository\RoomRepository;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RoomController extends AbstractController
{
    public function __construct(
        public GameLogicModel $gameLogicModel,
    ) {}

    #[Route('/room/{uuid}', name: 'app_room_status', methods: ['GET'])]
    public function app_room_status(
        string $uuid,
        RoomRepository $roomRepository,
        PlayerRepository $playerRepository,
    ): JsonResponse
    {
        $room = $roomRepository->findOneBy(['uuid' => $uuid]);

        if ($room === null) {
            throw new NotFoundHttpException('No room found for uuid: ' . $uuid);
        }

        $arrPlayers = [];
        foreach ($room->getPlayers() as $player) {
            $tmp = [];
            $tmp['uuid'] = $player->getUuid();
            $tmp['name'] = $player->getName();
            $arrPlayers[] = $tmp;
        }

        $arrPlayersTeamA = $playerRepository->findBy(['uuid' => $room->getPlayersTeamA()]);
        $arrPlayersTeamA = array_map(function (Player $player) {
            $tmp = [];
            $tmp['uuid'] = $player->getUuid();
            $tmp['name'] = $player->getName();

            return $tmp;
        }, $arrPlayersTeamA);

        $arrPlayersTeamB = $playerRepository->findBy(['uuid' => $room->getPlayersTeamB()]);
        $arrPlayersTeamB = array_map(function (Player $player) {
            $tmp = [];
            $tmp['uuid'] = $player->getUuid();
            $tmp['name'] = $player->getName();

            return $tmp;
        }, $arrPlayersTeamB);

//        $arrPlayersTeamB = [];
//        foreach ($room->getPlayersTeamB() as $player) {
//            $tmp = [];
//            $tmp['uuid'] = $player->getUuid();
//            $tmp['name'] = $player->getName();
//            $arrPlayersTeamB[] = $tmp;
//        }

        return $this->json([
            'uuid' => $uuid,
            'players' => $arrPlayers,
            'playersTeamA' => $arrPlayersTeamA,
            'playersTeamB' => $arrPlayersTeamB,
            ...$this->gameLogicModel->getGameStateArray($room)
        ]);
    }

    #[Route('/room/create', name: 'app_room_create', methods: ['POST'])]
    public function app_room_create(
        #[MapRequestPayload] CreateRoomDto $dto,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $uuid = Uuid::v4();

        // Create new Room entity
        $room = new Room();
        $room->setUuid($uuid);
        $room->setPassword($dto->password);
        $room->setCreatedAt(new \DateTimeImmutable());

        // Persist to database
        $entityManager->persist($room);
        $entityManager->flush();

        // Return the UUID
        return $this->json([
            'uuid' => $uuid,
        ]);
    }

    #[Route('/room/join', name: 'app_room_join', methods: ['POST'])]
    public function app_room_join(
        #[MapRequestPayload] JoinRoomDto $dto,
        RoomRepository $roomRepository,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager,
        HubInterface $hub,
    ): JsonResponse {

        $room = $roomRepository->findOneBy(['uuid' => $dto->uuidRoom]);
        if ($room === null) {
            throw new NotFoundHttpException('Failed to find given room.');
        }

        $player = $playerRepository->findOneBy(['uuid' => $dto->uuidPlayer]);
        if ($player === null) {
            throw new NotFoundHttpException('Failed to find given player.');
        }

        if ($room->getPlayers()->contains($player)) {
            return $this->json([
                'message' => 'Already in the room.',
            ]);
        }

        if ($room->getPassword() !== null && $room->getPassword() !== $dto->password) {
            return $this->json([
                'message' => 'Failed to join the room. Wrong password.',
            ]);
        }

        // Add player to room
        $room->addPlayer($player);
        $player->setLastHeartbeat(new \DateTime());

        $entityManager->persist($room);
        $entityManager->persist($player);
        $entityManager->flush();

        // Inform subscribers about changes
        $update = new Update(
            'room-' . $room->getUuid(),
            json_encode([
                'action' => RoomEvents::$PLAYER_JOINED,
                'playerUuid' => $player->getUuid(),
                'playerName' => $player->getName(),
            ]),
        );
        $hub->publish($update);

        // Return the UUID
        return $this->json([
            'message' => 'Successfully joined the room ' . $dto->uuidRoom . '.',
        ]);
    }

    #[Route('/room/switch-team', name: 'app_room_switch_team', methods: ['POST'])]
    public function app_room_switch_team(
        #[MapRequestPayload] SwitchTeamDto $dto,
        RoomRepository $roomRepository,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager,
        HubInterface $hub,
    ): JsonResponse {

        $room = $roomRepository->findOneBy(['uuid' => $dto->uuidRoom]);
        if ($room === null) {
            throw new NotFoundHttpException('Failed to find given room.');
        }

        $player = $playerRepository->findOneBy(['uuid' => $dto->uuidPlayer]);
        if ($player === null) {
            throw new NotFoundHttpException('Failed to find given player.');
        }

        if (!$room->getPlayers()->contains($player)) {
            return $this->json([
                'message' => 'Player not in the room.',
            ]);
        }

        switch ($dto->team) {
            case 'A':
                $room->setPlayersTeamA(array_unique([...$room->getPlayersTeamA(), $dto->uuidPlayer]));
                $room->setPlayersTeamB(array_diff($room->getPlayersTeamB(), [$dto->uuidPlayer]));
                break;
            case 'B':
                $room->setPlayersTeamA(array_diff($room->getPlayersTeamA(), [$dto->uuidPlayer]));
                $room->setPlayersTeamB(array_unique([...$room->getPlayersTeamB(), $dto->uuidPlayer]));
                break;
            default:
                throw new BadRequestException('Unknown team.');
        }


        $entityManager->persist($room);
        $entityManager->flush();

        // Inform subscribers about changes
        $update = new Update(
            'room-' . $room->getUuid(),
            json_encode([
                'action' => RoomEvents::$PLAYER_SWITCHED_TEAMS,
                'newTeam' => $dto->team,
                'playerUuid' => $player->getUuid(),
                'playerName' => $player->getName(),
            ]),
        );
        $hub->publish($update);

        // Return the UUID
        return $this->json([
            'message' => 'Successfully switched to team ' . $dto->team . '.',
        ]);
    }

    #[Route('/room/{uuid}/refresh', name: 'app_room_refresh', methods: ['POST'])]
    public function app_room_refresh(
        string $uuid,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager,
        HubInterface $hub,
    ): JsonResponse {

        $room = $roomRepository->findOneBy(['uuid' => $uuid]);

        if ($room === null) {
            throw new NotFoundHttpException('Failed to find given room.');
        }

        $timeoutThreshold = (new \DateTimeImmutable())->modify('-' . 60 . ' seconds');

        foreach ($room->getPlayers() as $player) {
            // if player heartbeat older than 60 seconds
            if ($player->getLastHeartbeat() !== null && $player->getLastHeartbeat() < $timeoutThreshold) {
                $room->removePlayer($player);
                $room->setPlayersTeamA(array_diff($room->getPlayersTeamA(), [$player->getUuid()]));
                $room->setPlayersTeamB(array_diff($room->getPlayersTeamB(), [$player->getUuid()]));

                $update = new Update(
                    'room-' . $room->getUuid(),
                    json_encode([
                        'action' => RoomEvents::$PLAYER_LEFT,
                        'playerUuid' => $player->getUuid(),
                        'playerName' => $player->getName(),
                    ]),
                );
                $hub->publish($update);
            }
        }

        // Persist to database
        $entityManager->persist($room);
        $entityManager->flush();

        // Return the UUID
        return $this->json([
            'message' => 'Successfully refreshed room.',
        ]);
    }

    #[Route('/room/{uuid}/game-action', name: 'app_room_game_action', methods: ['POST'])]
    public function app_room_game_action(
        string $uuid,
        #[MapRequestPayload] GameActionDto $dto,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager,
        HubInterface $hub,
    ): JsonResponse {

        $room = $roomRepository->findOneBy(['uuid' => $uuid]);

        if ($room === null) {
            throw new NotFoundHttpException('Failed to find given room.');
        }

        // Create a new game
        if ($dto->action === GameActions::$CREATE_NEW_GAME) {
            $room->setPointsTeamA(0);
            $room->setPointsTeamB(1);
            $room->setGameState(GameStates::$STATE_00_START);
            $room->setRoundIndex(0);
            $room->setPlayedCards([]);
            $room->setActivePlayer($room->getPlayersTeamA()[0]);

            $entityManager->persist($room);
            $entityManager->flush();

            $update = new Update(
                'room-' . $room->getUuid(),
                json_encode([
                    'action' => RoomEvents::$GAME_STATUS_UPDATE,
                    ...$this->gameLogicModel->getGameStateArray($room)
                ]),
            );
            $hub->publish($update);

            return $this->json([
                'message' => 'Started game.',
                ...$this->gameLogicModel->getGameStateArray($room)
            ]);
        }

        if ($dto->action === GameActions::$NEW_CARDS || $dto->action === GameActions::$NEW_OR_OLD_CARDS) {
            $alsoUseOldCards = $dto->action === GameActions::$NEW_OR_OLD_CARDS;
            $card = $this->gameLogicModel->getUnplayedCard($room, $alsoUseOldCards);

            return $this->json([
                'cardId' => $card->getId(),
                'cardValueLeft' => $card->getValueLeft(),
                'cardValueRight' => $card->getValueRight(),
            ]);
        }

        throw new \Exception('Unsupported action.');
    }
}
