<?php

/**
 * Evaluates a preserved Starting XI recommendation against
 * authoritative completed-gameweek player outcomes.
 *
 * This service is deliberately factual.
 *
 * It does not:
 *
 * - recalculate recommendation intelligence
 * - optimise a replacement Starting XI
 * - simulate automatic substitutions
 * - enforce alternative formation choices
 * - calculate an accuracy score
 * - evaluate captain recommendations
 * - evaluate transfer recommendations
 *
 * It records:
 *
 * - realised results for recommended starters
 * - realised results for recommended bench players
 * - realised Starting XI points
 * - realised bench points
 * - factual cases where a bench player scored more points
 *   than a recommended starter
 */
class StartingXIBacktestingService
{
    /**
     * @param array $startingXI
     * @param array $bench
     * @param array $playerOutcomes
     *
     * @return array
     */
    public function evaluate(
        array $startingXI,
        array $bench,
        array $playerOutcomes
    ): array {

        /*
         * Without a preserved Starting XI or authoritative
         * outcome evidence, there is nothing to evaluate.
         */
        if (
            empty($startingXI)
            ||
            empty($playerOutcomes)
        ) {

            return [];
        }


        /*
         * Build an outcome lookup using the local player ID.
         *
         * PlayerGameweekOutcomeService has already aggregated
         * fixture-level history into one authoritative outcome
         * per player/gameweek, so this service must not
         * recalculate those outcomes.
         */
        $outcomesByPlayerId =
            [];


        foreach (
            $playerOutcomes
            as $outcome
        ) {

            if (
                !is_array($outcome)
            ) {

                continue;
            }


            $playerId =
                $outcome[
                    'player_id'
                ]
                ?? null;


            if (
                !is_numeric($playerId)
                ||
                (int) $playerId <= 0
            ) {

                continue;
            }


            $outcomesByPlayerId[
                (int) $playerId
            ] =
                $outcome;
        }


        /*
         * Evaluate recommended starters.
         */
        $evaluatedStartingXI =
            $this->evaluatePlayers(
                $startingXI,
                $outcomesByPlayerId
            );


        /*
         * Evaluate recommended bench.
         */
        $evaluatedBench =
            $this->evaluatePlayers(
                $bench,
                $outcomesByPlayerId
            );


        /*
         * If none of the preserved starters can be matched to
         * authoritative outcome evidence, no meaningful
         * Starting XI evaluation is available.
         */
        if (
            empty($evaluatedStartingXI)
        ) {

            return [];
        }


        /*
         * Sum realised FPL points.
         *
         * Negative and zero scores remain valid evidence.
         */
        $startingXIPoints =
            0;


        foreach (
            $evaluatedStartingXI
            as $starter
        ) {

            $startingXIPoints +=
                $starter[
                    'actual_points'
                ];
        }


        $benchPoints =
            0;


        foreach (
            $evaluatedBench
            as $benchPlayer
        ) {

            $benchPoints +=
                $benchPlayer[
                    'actual_points'
                ];
        }


        /*
         * Record factual pairwise bench outperformance.
         *
         * This deliberately does NOT claim that every bench
         * player could legally replace every starter under
         * FPL formation rules.
         *
         * It simply records:
         *
         *     bench actual points > starter actual points
         */
        $benchOutperformance =
            [];


        foreach (
            $evaluatedBench
            as $benchPlayer
        ) {

            foreach (
                $evaluatedStartingXI
                as $starter
            ) {

                if (
                    $benchPlayer[
                        'actual_points'
                    ]
                    <=
                    $starter[
                        'actual_points'
                    ]
                ) {

                    continue;
                }


                $benchOutperformance[] = [

                    'bench_player_id' =>
                        $benchPlayer[
                            'player_id'
                        ],

                    'starter_player_id' =>
                        $starter[
                            'player_id'
                        ],

                    'bench_actual_points' =>
                        $benchPlayer[
                            'actual_points'
                        ],

                    'starter_actual_points' =>
                        $starter[
                            'actual_points'
                        ],

                    'points_difference' =>
                        $benchPlayer[
                            'actual_points'
                        ]
                        -
                        $starter[
                            'actual_points'
                        ]
                ];
            }
        }


        return [

            'starting_xi' =>
                $evaluatedStartingXI,

            'bench' =>
                $evaluatedBench,

            'starting_xi_points' =>
                $startingXIPoints,

            'bench_points' =>
                $benchPoints,

            'bench_outperformance' =>
                $benchOutperformance
        ];
    }


    /**
     * Match preserved recommendation rows to authoritative
     * player outcomes.
     *
     * Invalid recommendation rows and players without an
     * authoritative outcome are deliberately skipped.
     *
     * @param array $players
     * @param array $outcomesByPlayerId
     *
     * @return array
     */
    private function evaluatePlayers(
        array $players,
        array $outcomesByPlayerId
    ): array {

        $evaluatedPlayers =
            [];


        foreach (
            $players
            as $player
        ) {

            if (
                !is_array($player)
            ) {

                continue;
            }


            $playerId =
                $player[
                    'player_id'
                ]
                ?? null;


            if (
                !is_numeric($playerId)
                ||
                (int) $playerId <= 0
            ) {

                continue;
            }


            $playerId =
                (int) $playerId;


            if (
                !array_key_exists(
                    $playerId,
                    $outcomesByPlayerId
                )
            ) {

                /*
                 * Missing authoritative outcome evidence must
                 * not be converted into a manufactured zero.
                 */
                continue;
            }


            $outcome =
                $outcomesByPlayerId[
                    $playerId
                ];


            $actualPoints =
                $outcome[
                    'total_points'
                ]
                ?? null;


            $actualMinutes =
                $outcome[
                    'minutes'
                ]
                ?? null;


            /*
             * An outcome without numeric realised points is
             * not usable as factual FPL result evidence.
             */
            if (
                !is_numeric($actualPoints)
            ) {

                continue;
            }


            /*
             * Preserve the useful recommendation identity
             * evidence while adding realised outcome evidence.
             *
             * We create a new array rather than modifying the
             * historical source row.
             */
            $evaluatedPlayer = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $player[
                        'name'
                    ]
                    ?? null,

                'position' =>
                    $player[
                        'position'
                    ]
                    ?? null,

                'actual_points' =>
                    $actualPoints,

                'actual_minutes' =>
                    is_numeric(
                        $actualMinutes
                    )
                        ? $actualMinutes
                        : null
            ];


            $evaluatedPlayers[] =
                $evaluatedPlayer;
        }


        return
            $evaluatedPlayers;
    }
}