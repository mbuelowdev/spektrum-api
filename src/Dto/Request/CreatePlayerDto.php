<?php

namespace App\Dto\Request;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CreatePlayerDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Groups(['default'])]
        public string $name,
    ) {}
}
