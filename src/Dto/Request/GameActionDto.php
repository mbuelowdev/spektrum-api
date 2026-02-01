<?php

namespace App\Dto\Request;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class GameActionDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Groups(['default'])]
        public string $action,
        #[Assert\NotBlank]
        #[Groups(['default'])]
        public string $uuidPlayer,
        #[Groups(['default'])]
        public string $value,
    ) {}
}
