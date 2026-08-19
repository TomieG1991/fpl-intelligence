<?php


class SquadTransferIntelligence
{

    /*
     * ========================================================
     * SQUAD RULES
     * ========================================================
     */

    private const REQUIRED_COUNTS = [

        'GK' => 2,
        'DEF' => 5,
        'MID' => 5,
        'FWD' => 3
    ];


    private const MAX_PLAYERS_PER_TEAM =
        3;


    /*
     * ========================================================
     * ANALYZE SQUAD
     * ========================================================
     */

    public function analyzeSquad(
        array $squad,
        float $bank = 0.0
    ): array {

        /*
         * ----------------------------------------------------
         * BASIC INPUT
         * ----------------------------------------------------
         */

        if ($bank < 0) {

            return $this->emptyAnalysis(
                $bank,
                [
                    'Bank cannot be negative.'
                ]
            );
        }


        if (empty($squad)) {

            return $this->emptyAnalysis(
                $bank,
                [
                    'Squad is empty.'
                ]
            );
        }


        /*
         * ----------------------------------------------------
         * VALIDATE STRUCTURE
         * ----------------------------------------------------
         */

        $validation =
            $this->validateSquad(
                $squad
            );


        /*
         * ----------------------------------------------------
         * SCORE PLAYERS
         * ----------------------------------------------------
         */

        $ranking =
            [];


        foreach (
            $squad
            as $player
        ) {

            $ranking[] =
                $this->buildPlayerPriority(
                    $player
                );
        }


        /*
         * Highest transfer priority first.
         */
        usort(
            $ranking,
            static function (
                array $a,
                array $b
            ): int {

                return (
                    $b[
                        'transfer_priority'
                    ]
                    ?? 0
                )
                <=>
                (
                    $a[
                        'transfer_priority'
                    ]
                    ?? 0
                );
            }
        );


        foreach (
            $ranking
            as $index => &$player
        ) {

            $player[
                'squad_rank'
            ] =
                $index + 1;
        }


        unset(
            $player
        );


        /*
         * ----------------------------------------------------
         * SUMMARY
         * ----------------------------------------------------
         */

        $summary =
            $this->buildSquadSummary(
                $squad,
                $ranking
            );


        return [

            'squad' =>
                $squad,

            'bank' =>
                round(
                    $bank,
                    1
                ),

            'validation' =>
                $validation,

            'ranking' =>
                $ranking,

            'weakest_players' =>
                array_slice(
                    $ranking,
                    0,
                    5
                ),

            'summary' =>
                $summary
        ];
    }


    /*
     * ========================================================
     * VALIDATE SQUAD
     * ========================================================
     */

    private function validateSquad(
        array $squad
    ): array {

        $issues =
            [];


        $positionCounts = [

            'GK' => 0,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 0
        ];


        $teamCounts =
            [];


        $seenPlayerIds =
            [];


        foreach (
            $squad
            as $player
        ) {

            $playerId =
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                );


            if ($playerId <= 0) {

                $issues[] =
                    'Squad contains a player with an invalid player ID.';

            } elseif (
                in_array(
                    $playerId,
                    $seenPlayerIds,
                    true
                )
            ) {

                $issues[] =
                    'Squad contains duplicate player ID '
                    . $playerId
                    . '.';

            } else {

                $seenPlayerIds[] =
                    $playerId;
            }


            $position =
                strtoupper(
                    trim(
                        (string) (
                            $player[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            if (
                array_key_exists(
                    $position,
                    $positionCounts
                )
            ) {

                $positionCounts[
                    $position
                ]++;

            } else {

                $issues[] =
                    'Squad contains an invalid player position.';
            }


            $teamId =
                (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                );


            if ($teamId > 0) {

                if (
                    !isset(
                        $teamCounts[
                            $teamId
                        ]
                    )
                ) {

                    $teamCounts[
                        $teamId
                    ] =
                        0;
                }


                $teamCounts[
                    $teamId
                ]++;
            }
        }


        /*
         * ----------------------------------------------------
         * TOTAL PLAYER COUNT
         * ----------------------------------------------------
         */

        if (
            count(
                $squad
            )
            !== 15
        ) {

            $issues[] =
                'Squad must contain exactly 15 players.';
        }


        /*
         * ----------------------------------------------------
         * POSITION COUNTS
         * ----------------------------------------------------
         */

        foreach (
            self::REQUIRED_COUNTS
            as $position => $requiredCount
        ) {

            if (
                (
                    $positionCounts[
                        $position
                    ]
                    ?? 0
                )
                !==
                $requiredCount
            ) {

                $issues[] =
                    $position
                    . ' count must be '
                    . $requiredCount
                    . '.';
            }
        }


        /*
         * ----------------------------------------------------
         * CLUB LIMIT
         * ----------------------------------------------------
         */

        foreach (
            $teamCounts
            as $teamId => $count
        ) {

            if (
                $count
                >
                self::MAX_PLAYERS_PER_TEAM
            ) {

                $issues[] =
                    'Team ID '
                    . $teamId
                    . ' exceeds the maximum of '
                    . self::MAX_PLAYERS_PER_TEAM
                    . ' players.';
            }
        }


        return [

            'is_valid' =>
                empty(
                    $issues
                ),

            'player_count' =>
                count(
                    $squad
                ),

            'position_counts' =>
                $positionCounts,

            'team_counts' =>
                $teamCounts,

            'issues' =>
                $issues
        ];
    }


    /*
     * ========================================================
     * PLAYER PRIORITY
     * ========================================================
     */

    private function buildPlayerPriority(
        array $player
    ): array {

        $intelligence =
            $this->numericScore(
                $player[
                    'intelligence_score'
                ]
                ?? null
            );


        $value =
            $this->numericScore(
                $player[
                    'value_rating'
                ]
                ?? null
            );


        $fixtures =
            $this->numericScore(
                $player[
                    'fixture_rating'
                ]
                ?? null
            );


        $availability =
            $this->numericScore(
                $player[
                    'availability_rating'
                ]
                ?? null
            );


        $confidence =
            $this->normalizeConfidence(
                $player[
                    'sample_confidence'
                ]
                ?? null
            );


        /*
         * Low score = higher transfer concern.
         *
         * Convert each positive metric into a weakness score.
         */
        $intelligenceWeakness =
            100
            -
            $intelligence;


        $valueWeakness =
            100
            -
            $value;


        $fixtureWeakness =
            100
            -
            $fixtures;


        $availabilityWeakness =
            100
            -
            $availability;


        $confidenceWeakness =
            100
            -
            $confidence;


        /*
         * Transfer Priority v1
         *
         * Intelligence        45%
         * Value               20%
         * Fixtures            15%
         * Availability        15%
         */
        $priority =
        (
            $intelligenceWeakness
            *
            0.45
        )
        +
        (
            $valueWeakness
            *
            0.20
        )
        +
        (
            $fixtureWeakness
            *
            0.15
        )
        +
        (
            $availabilityWeakness
            *
            0.20
        );


        $priority =
            round(
                max(
                    0,
                    min(
                        100,
                        $priority
                    )
                ),
                1
            );


        return [

            'player_id' =>
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                ),

            'fpl_player_id' =>
                isset(
                    $player[
                        'fpl_player_id'
                    ]
                )
                    ? (int) $player[
                        'fpl_player_id'
                    ]
                    : null,

            'name' =>
                $player[
                    'name'
                ]
                ?? null,

            'team_id' =>
                isset(
                    $player[
                        'team_id'
                    ]
                )
                    ? (int) $player[
                        'team_id'
                    ]
                    : null,

            'team_name' =>
                $player[
                    'team_name'
                ]
                ?? null,

            'position' =>
                $player[
                    'position'
                ]
                ?? null,

            'price' =>
                $player[
                    'price'
                ]
                ?? null,

            'intelligence_score' =>
                $intelligence,

            'value_rating' =>
                $value,

            'fixture_rating' =>
                $fixtures,

            'availability_rating' =>
                $availability,

            'sample_confidence' =>
                $confidence,

            'transfer_priority' =>
                $priority,

            'priority_label' =>
                $this->priorityLabel(
                    $priority
                ),

            'priority_reasons' =>
                $this->buildPriorityReasons(
                    $intelligence,
                    $value,
                    $fixtures,
                    $availability,
                    $confidence
                )
        ];
    }


    /*
     * ========================================================
     * PRIORITY REASONS
     * ========================================================
     */

    private function buildPriorityReasons(
        float $intelligence,
        float $value,
        float $fixtures,
        float $availability,
        float $confidence
    ): array {

        $reasons =
            [];


        if ($intelligence < 55) {

            $reasons[] =
                'Low Intelligence score';

        } elseif ($intelligence < 62) {

            $reasons[] =
                'Below-average Intelligence';
        }


        if ($value < 45) {

            $reasons[] =
                'Poor value';
        }


        if ($fixtures < 50) {

            $reasons[] =
                'Difficult upcoming fixtures';
        }


        if ($availability < 75) {

            $reasons[] =
                'Availability concern';
        }


        if (empty($reasons)) {

            $reasons[] =
                'No major squad concerns';
        }


        return $reasons;
    }


    /*
     * ========================================================
     * PRIORITY LABEL
     * ========================================================
     */

    private function priorityLabel(
        float $priority
    ): string {

        if ($priority >= 60) {
            return 'High';
        }


        if ($priority >= 40) {
            return 'Moderate';
        }


        if ($priority >= 25) {
            return 'Low';
        }


        return 'Very Low';
    }


    /*
     * ========================================================
     * SQUAD SUMMARY
     * ========================================================
     */

    private function buildSquadSummary(
        array $squad,
        array $ranking
    ): array {

        $intelligenceScores =
            [];


        $positionTotals = [

            'GK' => [],
            'DEF' => [],
            'MID' => [],
            'FWD' => []
        ];


        foreach (
            $squad
            as $player
        ) {

            $score =
                $player[
                    'intelligence_score'
                ]
                ?? null;


            if (
                $score === null
                ||
                !is_numeric(
                    $score
                )
            ) {

                continue;
            }


            $score =
                (float) $score;


            $intelligenceScores[] =
                $score;


            $position =
                strtoupper(
                    trim(
                        (string) (
                            $player[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            if (
                isset(
                    $positionTotals[
                        $position
                    ]
                )
            ) {

                $positionTotals[
                    $position
                ][] =
                    $score;
            }
        }


        $averageIntelligence =
            empty(
                $intelligenceScores
            )
                ? null
                : round(
                    array_sum(
                        $intelligenceScores
                    )
                    /
                    count(
                        $intelligenceScores
                    ),
                    1
                );


        $positionAverages =
            [];


        foreach (
            $positionTotals
            as $position => $scores
        ) {

            $positionAverages[
                $position
            ] =
                empty(
                    $scores
                )
                    ? null
                    : round(
                        array_sum(
                            $scores
                        )
                        /
                        count(
                            $scores
                        ),
                        1
                    );
        }


        $weakestPosition =
            null;


        $weakestPositionScore =
            null;


        foreach (
            $positionAverages
            as $position => $score
        ) {

            if ($score === null) {
                continue;
            }


            if (
                $weakestPositionScore === null
                ||
                $score
                <
                $weakestPositionScore
            ) {

                $weakestPosition =
                    $position;


                $weakestPositionScore =
                    $score;
            }
        }


        return [

            'average_intelligence' =>
                $averageIntelligence,

            'position_averages' =>
                $positionAverages,

            'weakest_position' =>
                $weakestPosition,

            'highest_priority_player_id' =>
                isset(
                    $ranking[0][
                        'player_id'
                    ]
                )
                    ? (int) $ranking[0][
                        'player_id'
                    ]
                    : null,

            'highest_transfer_priority' =>
                $ranking[0][
                    'transfer_priority'
                ]
                ?? null
        ];
    }


    /*
     * ========================================================
     * HELPERS
     * ========================================================
     */

    private function numericScore(
        mixed $value
    ): float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return 50.0;
        }


        return max(
            0,
            min(
                100,
                (float) $value
            )
        );
    }


    private function normalizeConfidence(
        mixed $value
    ): float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return 50.0;
        }


        $value =
            (float) $value;


        /*
         * Existing intelligence data sometimes represents
         * confidence as 0-1 rather than 0-100.
         */
        if (
            $value >= 0
            &&
            $value <= 1
        ) {

            $value *=
                100;
        }


        return max(
            0,
            min(
                100,
                $value
            )
        );
    }


    private function emptyAnalysis(
        float $bank,
        array $issues
    ): array {

        return [

            'squad' =>
                [],

            'bank' =>
                round(
                    $bank,
                    1
                ),

            'validation' => [

                'is_valid' =>
                    false,

                'player_count' =>
                    0,

                'position_counts' => [

                    'GK' => 0,
                    'DEF' => 0,
                    'MID' => 0,
                    'FWD' => 0
                ],

                'team_counts' =>
                    [],

                'issues' =>
                    $issues
            ],

            'ranking' =>
                [],

            'weakest_players' =>
                [],

            'summary' => [

                'average_intelligence' =>
                    null,

                'position_averages' => [

                    'GK' => null,
                    'DEF' => null,
                    'MID' => null,
                    'FWD' => null
                ],

                'weakest_position' =>
                    null,

                'highest_priority_player_id' =>
                    null,

                'highest_transfer_priority' =>
                    null
            ]
        ];
    }
}