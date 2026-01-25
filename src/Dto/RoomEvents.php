<?php

namespace App\Dto;

class RoomEvents
{
    public static string $PLAYER_JOINED = 'PLAYER_JOINED';
    public static string $PLAYER_LEFT = 'PLAYER_LEFT';
    public static string $PLAYER_SWITCHED_TEAMS = 'PLAYER_SWITCHED_TEAMS';
    public static string $GAME_STATUS_UPDATE = 'GAME_STATUS_UPDATE';
}
