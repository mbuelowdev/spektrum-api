<?php

namespace App\Controller;

use App\Dto\Request\CreateRoomDto;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RoomController extends AbstractController
{
    #[Route('/room', name: 'app_room')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/RoomController.php',
        ]);
    }

    #[Route('/room/create', name: 'app_room_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateRoomDto $dto,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Generate UUID v4
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // Version 4
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // Variant bits
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));

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
}
