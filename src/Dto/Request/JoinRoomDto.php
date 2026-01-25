<?php

namespace App\Dto\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class JoinRoomDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $uuidRoom,
        #[Assert\NotBlank]
        public string $uuidPlayer,
        public ?string $password = null,
    ) {}
}
