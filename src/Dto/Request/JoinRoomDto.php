<?php

namespace App\Dto\Request;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class JoinRoomDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Groups(['default'])]
        public string $uuidRoom,
        #[Assert\NotBlank]
        #[Groups(['default'])]
        public string $uuidPlayer,
        #[Groups(['default'])]
        public ?string $password = null,
    ) {}
}
