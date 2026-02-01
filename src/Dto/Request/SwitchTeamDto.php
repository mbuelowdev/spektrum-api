<?php

namespace App\Dto\Request;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class SwitchTeamDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Groups(['default'])]
        public string $uuidRoom,
        #[Assert\NotBlank]
        #[Groups(['default'])]
        public string $uuidPlayer,
        #[Assert\NotBlank]
        #[Groups(['default'])]
        public string $team, // Either "A" or "B"
    ) {}
}
