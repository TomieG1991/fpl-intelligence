<?php

class PlayerComparison
{
    /**
     * Compare two complete player intelligence profiles.
     */
    public function compare(
        array $playerA,
        array $playerB
    ): array {

        $summaryA =
            $playerA['summary']
            ?? [];

        $summaryB =
            $playerB['summary']
            ?? [];


        $performanceA =
            $playerA['performance']
            ?? [];

        $performanceB =
            $playerB['performance']
            ?? [];

        $assessmentA =
            $playerA['assessment']
            ?? [];

        $assessmentB =
            $playerB['assessment']
            ?? [];


        $metrics = [

            'intelligence' => [
                'label' => 'Intelligence',
                'a' =>
                    $summaryA[
                        'intelligence_score'
                    ]
                    ?? null,
                'b' =>
                    $summaryB[
                        'intelligence_score'
                    ]
                    ?? null
            ],

            'strength' => [
                'label' => 'Strength',
                'a' =>
                    $summaryA[
                        'strength_rating'
                    ]
                    ?? null,
                'b' =>
                    $summaryB[
                        'strength_rating'
                    ]
                    ?? null
            ],

            'value' => [
                'label' => 'Value',
                'a' =>
                    $summaryA[
                        'value_rating'
                    ]
                    ?? null,
                'b' =>
                    $summaryB[
                        'value_rating'
                    ]
                    ?? null
            ],

            'fixtures' => [
                'label' => 'Fixtures',
                'a' =>
                    $summaryA[
                        'fixture_rating'
                    ]
                    ?? null,
                'b' =>
                    $summaryB[
                        'fixture_rating'
                    ]
                    ?? null
            ],

            'availability' => [
                'label' => 'Availability',
                'a' =>
                    $summaryA[
                        'availability_rating'
                    ]
                    ?? null,
                'b' =>
                    $summaryB[
                        'availability_rating'
                    ]
                    ?? null
            ],

            'sample_confidence' => [
                'label' => 'Sample Confidence',
                'a' =>
                    isset(
                        $performanceA[
                            'sample_confidence'
                        ]
                    )
                    &&
                    is_numeric(
                        $performanceA[
                            'sample_confidence'
                        ]
                    )
                        ? (
                            (float)
                            $performanceA[
                                'sample_confidence'
                            ]
                            * 100
                        )
                        : null,
                'b' =>
                    isset(
                        $performanceB[
                            'sample_confidence'
                        ]
                    )
                    &&
                    is_numeric(
                        $performanceB[
                            'sample_confidence'
                        ]
                    )
                        ? (
                            (float)
                            $performanceB[
                                'sample_confidence'
                            ]
                            * 100
                        )
                        : null
            ]
        ];


        $comparisons =
            [];


        $winsA =
            0;

        $winsB =
            0;

        $ties =
            0;


        foreach (
            $metrics
            as $key => $metric
        ) {

            $comparison =
                $this->compareMetric(
                    $metric['a'],
                    $metric['b']
                );


            if (
                $comparison['winner']
                === 'a'
            ) {
                $winsA++;
            }


            if (
                $comparison['winner']
                === 'b'
            ) {
                $winsB++;
            }


            if (
                $comparison['winner']
                === 'tie'
            ) {
                $ties++;
            }


            $comparisons[$key] = [

                'label' =>
                    $metric['label'],

                'player_a' =>
                    $comparison[
                        'player_a'
                    ],

                'player_b' =>
                    $comparison[
                        'player_b'
                    ],

                'difference' =>
                    $comparison[
                        'difference'
                    ],

                'winner' =>
                    $comparison[
                        'winner'
                    ]
            ];
        }


        /*
         * Overall comparison deliberately follows
         * Intelligence Score rather than simple metric wins.
         *
         * Intelligence remains the application's official
         * combined player ranking.
         */

        $overallComparison =
            $this->compareMetric(
                $summaryA[
                    'intelligence_score'
                ]
                ?? null,
                $summaryB[
                    'intelligence_score'
                ]
                ?? null
            );


        return [

            'player_a' =>
                $this->buildPlayerSummary(
                    $playerA
                ),

            'player_b' =>
                $this->buildPlayerSummary(
                    $playerB
                ),

            'metrics' =>
                $comparisons,

            'metric_wins' => [

                'player_a' =>
                    $winsA,

                'player_b' =>
                    $winsB,

                'ties' =>
                    $ties
            ],

            'overall_winner' =>
                $overallComparison[
                    'winner'
                ],

            'overall_difference' =>
                $overallComparison[
                    'difference'
                ],

            'player_a_verdict' =>
                $assessmentA[
                    'verdict'
                ]
                ?? null,

            'player_b_verdict' =>
                $assessmentB[
                    'verdict'
                ]
                ?? null
        ];
    }


    /**
     * Compare one numeric metric.
     */
    public function compareMetric(
        mixed $valueA,
        mixed $valueB
    ): array {

        $valueA =
            $this->normaliseMetric(
                $valueA
            );


        $valueB =
            $this->normaliseMetric(
                $valueB
            );


        if (
            $valueA === null
            &&
            $valueB === null
        ) {

            return [

                'player_a' =>
                    null,

                'player_b' =>
                    null,

                'difference' =>
                    null,

                'winner' =>
                    'tie'
            ];
        }


        if ($valueA === null) {

            return [

                'player_a' =>
                    null,

                'player_b' =>
                    $valueB,

                'difference' =>
                    null,

                'winner' =>
                    'b'
            ];
        }


        if ($valueB === null) {

            return [

                'player_a' =>
                    $valueA,

                'player_b' =>
                    null,

                'difference' =>
                    null,

                'winner' =>
                    'a'
            ];
        }


        $difference =
            round(
                abs(
                    $valueA
                    -
                    $valueB
                ),
                2
            );


        if ($valueA > $valueB) {

            $winner =
                'a';

        } elseif ($valueB > $valueA) {

            $winner =
                'b';

        } else {

            $winner =
                'tie';
        }


        return [

            'player_a' =>
                $valueA,

            'player_b' =>
                $valueB,

            'difference' =>
                $difference,

            'winner' =>
                $winner
        ];
    }


    /**
     * Build front-end friendly player identity data.
     */
    private function buildPlayerSummary(
        array $profile
    ): array {

        $player =
            $profile['player']
            ?? [];


        $team =
            $profile['team']
            ?? [];


        $summary =
            $profile['summary']
            ?? [];


        return [

            'player_id' =>
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                ),

            'name' =>
                $player['name']
                ?? null,

            'position' =>
                $player['position']
                ?? null,

            'team_name' =>
                $team['name']
                ?? null,

            'price' =>
                isset(
                    $summary['price']
                )
                &&
                is_numeric(
                    $summary['price']
                )
                    ? (float)
                        $summary[
                            'price'
                        ]
                    : null,

            'intelligence_score' =>
                isset(
                    $summary[
                        'intelligence_score'
                    ]
                )
                &&
                is_numeric(
                    $summary[
                        'intelligence_score'
                    ]
                )
                    ? (float)
                        $summary[
                            'intelligence_score'
                        ]
                    : null
        ];
    }


    /**
     * Safely read numeric comparison values.
     */
    private function normaliseMetric(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return round(
            (float) $value,
            2
        );
    }
}