<?php

namespace App\Dto\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class SwitchTeamDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $uuidRoom,
        #[Assert\NotBlank]
        public string $uuidPlayer,
        #[Assert\NotBlank]
        public string $team, // Either "A" or "B"
    ) {}
}
