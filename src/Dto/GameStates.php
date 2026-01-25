<?php

namespace App\Dto;

class GameStates
{
    /**
     * In this state the cluegiver can click on:
     * - new cards
     * - start spinning
     * In this state the teamusers can click on:
     * - nothing
     * In this state the enemyusers can click on:
     * - nothing
     * @var string
     */
    public static string $STATE_00_START = 'STATE_00_START';

    /**
     * In this state the cluegiver can click on:
     * - submit guess (with a textbox and a clue)
     * In this state the teamusers can click on:
     * - nothing
     * In this state the enemyusers can click on:
     * - nothing
     * @var string
     */
    public static string $STATE_01_SHOW_HIDDEN_MARKER = 'STATE_01_SHOW_HIDDEN_MARKER';

    /**
     * In this state the cluegiver can click on:
     * - nothing
     * In this state the teamusers can click on:
     * - the semicircle (to submit a guess preview)
     * - submit guess (to lock in their vote)
     * In this state the enemyusers can click on:
     * - nothing
     * @var string
     */
    public static string $STATE_02_START = 'STATE_02_GUESS_ROUND';

    /**
     * In this state the cluegiver can click on:
     * - nothing
     * In this state the teamusers can click on:
     * - nothing
     * In this state the enemyusers can click on:
     * - the semicircle (to submit a guess preview)
     * - submit guess (to lock in their vote)
     * @var string
     */
    public static string $STATE_03_COUNTER_GUESS_ROUND = 'STATE_03_COUNTER_GUESS_ROUND';

    /**
     * In this state the cluegiver can click on:
     * - next round
     * In this state the teamusers can click on:
     * - nothing
     * In this state the enemyusers can click on:
     * - nothing
     * @var string
     */
    public static string $STATE_04_REVEAL = 'STATE_04_REVEAL';
}
