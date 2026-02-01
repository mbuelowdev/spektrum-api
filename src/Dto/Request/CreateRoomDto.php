<?php

namespace App\Dto\Request;

use Symfony\Component\Serializer\Annotation\Groups;

final class CreateRoomDto
{
    public function __construct(
        #[Groups(['default'])]
        public ?string $password = null,
    ) {}
}
