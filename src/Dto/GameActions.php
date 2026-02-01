<?php

namespace App\Dto;

class GameActions
{
    public static string $JOIN_ROOM = 'JOIN_ROOM';
    public static string $LEAVE_ROOM = 'LEAVE_ROOM';
    public static string $CREATE_NEW_GAME = 'CREATE_NEW_GAME';
    public static string $NEW_CARDS = 'NEW_CARDS';
    public static string $NEW_OR_OLD_CARDS = 'NEW_OR_OLD_CARDS';
    public static string $START_SPINNING = 'START_SPINNING';
    public static string $SUBMIT_CLUEGIVER_CLUE = 'SUBMIT_CLUEGIVER_CLUE';
    public static string $SUBMIT_GUESS = 'SUBMIT_GUESS';
    public static string $SUBMIT_PREVIEW_GUESS = 'SUBMIT_PREVIEW_GUESS';
    public static string $REMOVE_PREVIEW_GUESS = 'REMOVE_PREVIEW_GUESS';
    public static string $REVEAL = 'REVEAL';
    public static string $NEXT_ROUND = 'NEXT_ROUND';
}
