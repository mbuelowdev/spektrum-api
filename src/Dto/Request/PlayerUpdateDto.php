<?php

namespace App\Dto\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class PlayerUpdateDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $action,
    ) {}
}
