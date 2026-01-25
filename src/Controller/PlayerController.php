<?php

namespace App\Controller;

use App\Dto\Request\CreatePlayerDto;
use App\Entity\Player;
use App\Model\GameLogicModel;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class PlayerController extends AbstractController
{
    public function __construct(
        public GameLogicModel $gameLogicModel,
    ) {}

    #[Route('/player/create', name: 'app_player_create', methods: ['POST'])]
    public function app_player_create(
        #[MapRequestPayload] CreatePlayerDto $dto,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        $player = new Player();
        $player->setName($dto->name);
        $player->setUuid(Uuid::v4());
        $player->setLastHeartbeat(new \DateTime());
        $player->setWins(0);

        $entityManager->persist($player);
        $entityManager->flush();

        return $this->json([
            'message' => 'Successfully created player!',
            'uuid' => $player->getUuid(),
        ]);
    }

    #[Route('/player/{uuid}/heartbeat', name: 'app_player_heartbeat', methods: ['POST'])]
    public function app_player_heartbeat(
        string $uuid,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        $player = $playerRepository->findOneBy(['uuid' => $uuid]);

        if ($player === null) {
            return $this->json([]);
        }

        $player->setLastHeartbeat(new \DateTime());

        $entityManager->persist($player);
        $entityManager->flush();

        return $this->json([
            'message' => 'Heartbeat received!'
        ]);
    }
}
