<?php

namespace App\Dto\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class CreatePlayerDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
    ) {}
}
