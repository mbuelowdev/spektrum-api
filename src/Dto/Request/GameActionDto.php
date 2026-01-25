<?php

namespace App\Dto\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class GameActionDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $action,
        public string $value,
    ) {}
}
