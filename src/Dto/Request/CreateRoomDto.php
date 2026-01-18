<?php

namespace App\Dto\Request;

final class CreateRoomDto
{
    public function __construct(
        public ?string $password = null,
    ) {}
}
