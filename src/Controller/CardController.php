<?php

namespace App\Controller;

use App\Model\GameLogicModel;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CardController extends AbstractController
{
    public function __construct(
        public GameLogicModel $gameLogicModel,
    ) {}

    #[Route('/cards', name: 'app_cards', methods: ['GET'])]
    public function app_cards(
        CardRepository $cardRepository,
    ): JsonResponse
    {
        $arrCards = $cardRepository->findAll();

        $arrMappedCards = [];
        foreach ($arrCards as $card) {
            $arrMappedCards[] = [
                'id' => $card->getId(),
                'valueLeft' => $card->getValueLeft(),
                'valueRight' => $card->getValueRight(),
            ];
        }

        return $this->json([
            'cards' => $arrMappedCards,
        ]);
    }
}
