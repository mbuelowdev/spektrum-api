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
        public RoomRepository $roomRepository,
        public PlayerRepository $playerRepository,
        public EntityManagerInterface $entityManager,
        public HubInterface $hub,
    ) {}

    #[Route('/room/{uuid}', name: 'app_room_status', methods: ['GET'])]
    public function app_room_status(string $uuid): JsonResponse
    {
        $room = $this->roomRepository->findOneBy(['uuid' => $uuid]);

        if ($room === null) {
            throw new NotFoundHttpException('No room found for uuid: ' . $uuid);
        }

        return $this->json($room);
    }

    #[Route('/room/create', name: 'app_room_create', methods: ['POST'])]
    public function app_room_create(#[MapRequestPayload] CreateRoomDto $dto): JsonResponse {

        $uuid = Uuid::v4();

        // Create new Room entity
        $room = new Room();
        $room->setUuid($uuid);
        $room->setPassword($dto->password);
        $room->setCreatedAt(new \DateTimeImmutable());

        // Persist to database
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        // Return the UUID
        return $this->json($room);
    }

    #[Route('/room/join', name: 'app_room_join', methods: ['POST'])]
    public function app_room_join(#[MapRequestPayload] JoinRoomDto $dto,): JsonResponse {

        $room = $this->roomRepository->findOneBy(['uuid' => $dto->uuidRoom]);
        if ($room === null) {
            throw new NotFoundHttpException('Failed to find given room.');
        }

        $player = $this->playerRepository->findOneBy(['uuid' => $dto->uuidPlayer]);
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

        // Add player to room, update activity state
        $room->addPlayersTeamA($player);
        $player->setLastHeartbeat(new \DateTime());

        $this->entityManager->persist($room);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        // Inform subscribers about changes
        $this->gameLogicModel->pushRoomUpdate($room);

        // Response
        return $this->json([
            'message' => 'Successfully joined the room ' . $dto->uuidRoom . '.',
        ]);
    }

    #[Route('/room/switch-team', name: 'app_room_switch_team', methods: ['POST'])]
    public function app_room_switch_team(#[MapRequestPayload] SwitchTeamDto $dto): JsonResponse {
        $room = $this->roomRepository->findOneBy(['uuid' => $dto->uuidRoom]);
        if ($room === null) {
            throw new NotFoundHttpException('Failed to find given room.');
        }

        $player = $this->playerRepository->findOneBy(['uuid' => $dto->uuidPlayer]);
        if ($player === null) {
            throw new NotFoundHttpException('Failed to find given player.');
        }

        if (!$room->getPlayers()->contains($player)) {
            return $this->json([
                'message' => 'Player not in the room.',
            ]);
        }

        if ($room->getGameState() != null) {
            return $this->json([
                'message' => 'Game has already started.',
            ]);
        }

        switch ($dto->team) {
            case 'A':
                $room->addPlayersTeamA($player);
                $room->removePlayersTeamB($player);
                break;
            case 'B':
                $room->addPlayersTeamB($player);
                $room->removePlayersTeamA($player);
                break;
            default:
                throw new BadRequestException('Unknown team.');
        }


        $this->entityManager->persist($room);
        $this->entityManager->flush();

        // Inform subscribers about changes
        $this->gameLogicModel->pushRoomUpdate($room);

        // Return the UUID
        return $this->json([
            'message' => 'Successfully switched to team ' . $dto->team . '.',
        ]);
    }

    #[Route('/room/{uuid}/refresh', name: 'app_room_refresh', methods: ['POST'])]
    public function app_room_refresh(string $uuid): JsonResponse {

        $room = $this->roomRepository->findOneBy(['uuid' => $uuid]);

        if ($room === null) {
            throw new NotFoundHttpException('Failed to find given room.');
        }

//        $timeoutThreshold = (new \DateTimeImmutable())->modify('-' . 60 . ' seconds');
//        foreach ($room->getPlayers() as $player) {
//            // if player heartbeat older than 60 seconds
//            if ($player->getLastHeartbeat() !== null && $player->getLastHeartbeat() < $timeoutThreshold) {
//                $room->removePlayersTeamA($player);
//                $room->removePlayersTeamB($player);
//            }
//        }

        // Inform subscribers about changes
        $this->gameLogicModel->pushRoomUpdate($room);

        // Persist to database
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        // Return the UUID
        return $this->json([
            'message' => 'Successfully refreshed room.',
        ]);
    }

    #[Route('/room/{uuid}/game-action', name: 'app_room_game_action', methods: ['POST'])]
    public function app_room_game_action(string $uuid, #[MapRequestPayload] GameActionDto $dto): JsonResponse {

        $room = $this->roomRepository->findOneBy(['uuid' => $uuid]);

        if ($room === null) {
            throw new NotFoundHttpException('Failed to find given room.');
        }

        $this->gameLogicModel->executeGameAction($room, $dto);

        return $this->json([
            'message' => 'Successfully executed game action.',
        ]);
    }
}
