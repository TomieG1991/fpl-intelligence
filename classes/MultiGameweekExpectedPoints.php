<?php

class MultiGameweekExpectedPoints
{
    private PlayerExpectedPoints
        $playerExpectedPoints;


    public function __construct(
        PlayerExpectedPoints
            $playerExpectedPoints
    ) {

        $this->playerExpectedPoints =
            $playerExpectedPoints;
    }


    /**
     * Project one player across a sequence of upcoming fixtures.
     *
     * Each fixture is projected independently through the existing
     * PlayerExpectedPoints model so v0.29 remains the single source
     * of truth for scoring logic.
     *
     * The same current player/form evidence is reused for each
     * fixture. Only fixture context changes between projections.
     */
    public function projectFixtures(
        array $player,
        array $form,
        array $fixtures,
        array $fixtureContexts = []
    ): array {

        $playerId =
            (int) (
                $player[
                    'id'
                ]
                ?? 0
            );


        $fplPlayerId =
            (int) (
                $player[
                    'fpl_player_id'
                ]
                ?? 0
            );


        /*
         * --------------------------------------------------------
         * NORMALISE FIXTURE ORDER
         * --------------------------------------------------------
         */

        usort(
            $fixtures,
            function (
                array $a,
                array $b
            ): int {

                $gameweekA =
                    isset(
                        $a[
                            'gameweek'
                        ]
                    )
                    &&
                    is_numeric(
                        $a[
                            'gameweek'
                        ]
                    )
                        ? (int) $a[
                            'gameweek'
                        ]
                        : PHP_INT_MAX;


                $gameweekB =
                    isset(
                        $b[
                            'gameweek'
                        ]
                    )
                    &&
                    is_numeric(
                        $b[
                            'gameweek'
                        ]
                    )
                        ? (int) $b[
                            'gameweek'
                        ]
                        : PHP_INT_MAX;


                if (
                    $gameweekA !==
                    $gameweekB
                ) {

                    return
                        $gameweekA
                        <=>
                        $gameweekB;
                }


                $kickoffA =
                    (string) (
                        $a[
                            'kickoff_time'
                        ]
                        ?? ''
                    );


                $kickoffB =
                    (string) (
                        $b[
                            'kickoff_time'
                        ]
                        ?? ''
                    );


                if (
                    $kickoffA !==
                    $kickoffB
                ) {

                    return
                        strcmp(
                            $kickoffA,
                            $kickoffB
                        );
                }


                return
                    (
                        (int) (
                            $a[
                                'id'
                            ]
                            ?? 0
                        )
                    )
                    <=>
                    (
                        (int) (
                            $b[
                                'id'
                            ]
                            ?? 0
                        )
                    );
            }
        );


        /*
         * --------------------------------------------------------
         * PROJECT EACH FIXTURE
         * --------------------------------------------------------
         */

        $fixtureProjections =
            [];


        $gameweekProjections =
            [];


        foreach (
            $fixtures
            as $fixture
        ) {

            $fixtureId =
                (int) (
                    $fixture[
                        'id'
                    ]
                    ?? 0
                );


            $fplFixtureId =
                (int) (
                    $fixture[
                        'fpl_fixture_id'
                    ]
                    ?? 0
                );


            $gameweek =
                isset(
                    $fixture[
                        'gameweek'
                    ]
                )
                &&
                is_numeric(
                    $fixture[
                        'gameweek'
                    ]
                )
                    ? (int) $fixture[
                        'gameweek'
                    ]
                    : null;


            /*
             * Fixture context must be explicit.
             *
             * We deliberately do not manufacture a neutral context
             * because that would make missing planning evidence look
             * like a real 50/100-type fixture.
             */

            $context =
                null;


            $localContextKey =
                $fixtureId > 0
                    ? 'fixture:'
                        . $fixtureId
                    : null;


            $fplContextKey =
                $fplFixtureId > 0
                    ? 'fpl_fixture:'
                        . $fplFixtureId
                    : null;


            if (
                $localContextKey !== null
                &&
                isset(
                    $fixtureContexts[
                        $localContextKey
                    ]
                )
                &&
                is_array(
                    $fixtureContexts[
                        $localContextKey
                    ]
                )
            ) {

                $context =
                    $fixtureContexts[
                        $localContextKey
                    ];

            } elseif (
                $fplContextKey !== null
                &&
                isset(
                    $fixtureContexts[
                        $fplContextKey
                    ]
                )
                &&
                is_array(
                    $fixtureContexts[
                        $fplContextKey
                    ]
                )
            ) {

                $context =
                    $fixtureContexts[
                        $fplContextKey
                    ];
            } elseif (
                $fplFixtureId > 0
                &&
                isset(
                    $fixtureContexts[
                        $fplFixtureId
                    ]
                )
                &&
                is_array(
                    $fixtureContexts[
                        $fplFixtureId
                    ]
                )
            ) {

                $context =
                    $fixtureContexts[
                        $fplFixtureId
                    ];
            }


            if (
                $context === null
            ) {

                $fixtureProjections[] = [

                    'fixture_id' =>
                        $fixtureId > 0
                            ? $fixtureId
                            : null,

                    'fpl_fixture_id' =>
                        $fplFixtureId > 0
                            ? $fplFixtureId
                            : null,

                    'gameweek' =>
                        $gameweek,

                    'kickoff_time' =>
                        $fixture[
                            'kickoff_time'
                        ]
                        ?? null,

                    'status' =>
                        'Missing Fixture Context',

                    'projected_points' =>
                        null,

                    'projected_minutes' =>
                        null,

                    'projection_confidence_percent' =>
                        null,

                    'projection_confidence_label' =>
                        null,

                    'components' =>
                        [],

                    'inputs' =>
                        []
                ];


                continue;
            }


            $projection =
                $this->playerExpectedPoints
                    ->project(
                        $player,
                        $form,
                        $context
                    );


            $projectedPoints =
                isset(
                    $projection[
                        'projected_points'
                    ]
                )
                &&
                is_numeric(
                    $projection[
                        'projected_points'
                    ]
                )
                    ? (float) $projection[
                        'projected_points'
                    ]
                    : null;


            $fixtureProjection = [

                'fixture_id' =>
                    $fixtureId > 0
                        ? $fixtureId
                        : null,

                'fpl_fixture_id' =>
                    $fplFixtureId > 0
                        ? $fplFixtureId
                        : null,

                'gameweek' =>
                    $gameweek,

                'kickoff_time' =>
                    $fixture[
                        'kickoff_time'
                    ]
                    ?? null,

                'status' =>
                    $projectedPoints !== null
                        ? 'Projected'
                        : 'Unavailable',

                'projected_points' =>
                    $projectedPoints,

                'projected_minutes' =>
                    isset(
                        $projection[
                            'projected_minutes'
                        ]
                    )
                    &&
                    is_numeric(
                        $projection[
                            'projected_minutes'
                        ]
                    )
                        ? (float) $projection[
                            'projected_minutes'
                        ]
                        : null,

                'projection_confidence_percent' =>
                    isset(
                        $projection[
                            'projection_confidence_percent'
                        ]
                    )
                    &&
                    is_numeric(
                        $projection[
                            'projection_confidence_percent'
                        ]
                    )
                        ? (float) $projection[
                            'projection_confidence_percent'
                        ]
                        : null,

                'projection_confidence_label' =>
                    $projection[
                        'projection_confidence_label'
                    ]
                    ?? null,

                'components' =>
                    is_array(
                        $projection[
                            'components'
                        ]
                        ?? null
                    )
                        ? $projection[
                            'components'
                        ]
                        : [],

                'inputs' =>
                    is_array(
                        $projection[
                            'inputs'
                        ]
                        ?? null
                    )
                        ? $projection[
                            'inputs'
                        ]
                        : []
            ];


            $fixtureProjections[] =
                $fixtureProjection;


            /*
             * ----------------------------------------------------
             * GROUP BY GAMEWEEK
             * ----------------------------------------------------
             *
             * Multiple fixtures in one gameweek are intentionally
             * preserved and summed for Double Gameweeks.
             */

            if (
                $gameweek !== null
                &&
                $projectedPoints !== null
            ) {

                if (
                    !isset(
                        $gameweekProjections[
                            $gameweek
                        ]
                    )
                ) {

                    $gameweekProjections[
                        $gameweek
                    ] = [

                        'gameweek' =>
                            $gameweek,

                        'fixture_count' =>
                            0,

                        'projected_points' =>
                            0.0,

                        'fixtures' =>
                            []
                    ];
                }


                $gameweekProjections[
                    $gameweek
                ][
                    'fixture_count'
                ]++;


                $gameweekProjections[
                    $gameweek
                ][
                    'projected_points'
                ] +=
                    $projectedPoints;


                $gameweekProjections[
                    $gameweek
                ][
                    'fixtures'
                ][] =
                    $fixtureProjection;
            }
        }


        /*
         * --------------------------------------------------------
         * NORMALISE GAMEWEEK TOTALS
         * --------------------------------------------------------
         */

        ksort(
            $gameweekProjections,
            SORT_NUMERIC
        );


        foreach (
            $gameweekProjections
            as $gameweek => $summary
        ) {

            $gameweekProjections[
                $gameweek
            ][
                'projected_points'
            ] =
                round(
                    (float) $summary[
                        'projected_points'
                    ],
                    2
                );
        }


        /*
         * --------------------------------------------------------
         * HORIZON TOTALS
         * --------------------------------------------------------
         *
         * These are based on future gameweeks, not raw fixture
         * count. That means a Double Gameweek contributes both
         * fixtures to the same horizon, while a Blank Gameweek
         * contributes zero.
         */

        $totals = [

            'next_3' =>
                $this->calculateGameweekHorizonTotal(
                    $gameweekProjections,
                    3
                ),

            'next_5' =>
                $this->calculateGameweekHorizonTotal(
                    $gameweekProjections,
                    5
                ),

            'next_6' =>
                $this->calculateGameweekHorizonTotal(
                    $gameweekProjections,
                    6
                )
        ];


        return [

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $fplPlayerId,

            'fixtures' =>
                $fixtureProjections,

            'gameweeks' =>
                $gameweekProjections,

            'totals' =>
                $totals,

            'fixture_projection_count' =>
                count(
                    array_filter(
                        $fixtureProjections,
                        function (
                            array $fixture
                        ): bool {

                            return (
                                $fixture[
                                    'status'
                                ]
                                ?? null
                            )
                            ===
                            'Projected';
                        }
                    )
                )
        ];
    }


    /**
     * Sum projected points across the next N gameweeks represented
     * by the planning data.
     *
     * Missing gameweeks inside the range naturally behave as blanks.
     */
    private function calculateGameweekHorizonTotal(
        array $gameweeks,
        int $horizon
    ): ?float {

        if (
            $horizon <= 0
            ||
            empty(
                $gameweeks
            )
        ) {

            return null;
        }


        $gameweekNumbers =
            array_map(
                'intval',
                array_keys(
                    $gameweeks
                )
            );


        sort(
            $gameweekNumbers,
            SORT_NUMERIC
        );


        $firstGameweek =
            $gameweekNumbers[
                0
            ]
            ?? null;


        if (
            $firstGameweek === null
        ) {

            return null;
        }


        $lastGameweek =
            $firstGameweek
            +
            $horizon
            -
            1;


        $total =
            0.0;


        $hasProjection =
            false;


        for (
            $gameweek =
                $firstGameweek;
            $gameweek <= $lastGameweek;
            $gameweek++
        ) {

            if (
                !isset(
                    $gameweeks[
                        $gameweek
                    ]
                )
            ) {

                continue;
            }


            $projectedPoints =
                $gameweeks[
                    $gameweek
                ][
                    'projected_points'
                ]
                ?? null;


            if (
                !is_numeric(
                    $projectedPoints
                )
            ) {

                continue;
            }


            $total +=
                (float) $projectedPoints;


            $hasProjection =
                true;
        }


        return $hasProjection
            ? round(
                $total,
                2
            )
            : null;
    }
}