<?php

namespace App\Controller;

use App\Dto\Request\PlayerUpdateDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class GameController extends AbstractController
{
    #[Route('/game/{gameId}/state', name: 'app_game_get_state', methods: ['GET'])]
    public function app_game_get_state(): JsonResponse
    {
        // Return state for specific game from database
        return $this->json([
            'game' => 'state',
        ]);
    }

    #[Route('/game/{gameId}/update', name: 'app_game_update', methods: ['POST'])]
    public function app_game_update(
        int $gameId,
        #[MapRequestPayload] PlayerUpdateDto $dto,
        HubInterface $hub
    ): JsonResponse {
        // Update game state in database
        // TODO

        // Inform subscribers about changes
        $update = new Update(
            'game-' . $gameId,
            json_encode([
                'action' => $dto->action,
                'value' => $dto->value,
            ]),
        );
        $hub->publish($update);

        // Return state for specific game from database
        return $this->json('Game state updated!');
    }
}
