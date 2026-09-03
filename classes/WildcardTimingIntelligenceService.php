<?php

class WildcardTimingIntelligenceService
{
    private WildcardTimingIntelligence
        $wildcardTimingIntelligence;


    public function __construct(
        WildcardTimingIntelligence $wildcardTimingIntelligence
    ) {

        $this->wildcardTimingIntelligence =
            $wildcardTimingIntelligence;
    }


    public function analyseHorizons(
        array $currentHorizon,
        array $wildcardHorizon
    ): array {

        /*
         * --------------------------------------------------------
         * VALIDATE CURRENT HORIZON
         * --------------------------------------------------------
         */

        if (
            !$this->isValidHorizon(
                $currentHorizon
            )
        ) {

            return
                $this->unavailableResult(
                    'Current squad horizon is unavailable or invalid.'
                );
        }


        /*
         * --------------------------------------------------------
         * VALIDATE WILDCARD HORIZON
         * --------------------------------------------------------
         */

        if (
            !$this->isValidHorizon(
                $wildcardHorizon
            )
        ) {

            return
                $this->unavailableResult(
                    'Wildcard squad horizon is unavailable or invalid.'
                );
        }


        /*
         * --------------------------------------------------------
         * REQUIRE SAME HORIZON LENGTH
         * --------------------------------------------------------
         *
         * Wildcard value must compare like with like.
         *
         * A three-gameweek current squad must not be compared
         * against a two-gameweek Wildcard squad.
         */

        $currentHorizonLength =
            (int) $currentHorizon[
                'horizon'
            ];


        $wildcardHorizonLength =
            (int) $wildcardHorizon[
                'horizon'
            ];


        if (
            $currentHorizonLength
            !==
            $wildcardHorizonLength
        ) {

            return
                $this->unavailableResult(
                    'Current and Wildcard horizons must have the same length.'
                );
        }


        /*
         * --------------------------------------------------------
         * REQUIRE SAME GAMEWEEK RANGE
         * --------------------------------------------------------
         *
         * Both squads must be evaluated across exactly the same
         * FPL gameweeks.
         */

        $currentGameweeks =
            $this->extractGameweekNumbers(
                $currentHorizon
            );


        $wildcardGameweeks =
            $this->extractGameweekNumbers(
                $wildcardHorizon
            );


        if (
            $currentGameweeks
            !==
            $wildcardGameweeks
        ) {

            return
                $this->unavailableResult(
                    'Current and Wildcard horizons must cover the same gameweeks.'
                );
        }


        /*
         * --------------------------------------------------------
         * SUM PROJECTED STARTING XI POINTS
         * --------------------------------------------------------
         */

        $currentProjectedPoints =
            $this->sumStartingXiProjectedPoints(
                $currentHorizon
            );


                $wildcardProjectedPoints =
                    $this->sumStartingXiProjectedPoints(
                        $wildcardHorizon
                    );


                /*
                 * --------------------------------------------------------
                 * HORIZON PROJECTION CONFIDENCE
                 * --------------------------------------------------------
                 *
                 * Wildcard value compares two independently projected
                 * squad horizons.
                 *
                 * Confidence in that comparison must therefore not exceed
                 * the weaker of the two horizon confidence values.
                 */

                $currentHorizonConfidence =
                    $currentHorizon[
                        'projection_confidence'
                    ]
                    ??
                    null;


                $wildcardHorizonConfidence =
                    $wildcardHorizon[
                        'projection_confidence'
                    ]
                    ??
                    null;


                        $horizonProjectionConfidence =
                            is_numeric(
                                $currentHorizonConfidence
                            )
                            &&
                            is_numeric(
                                $wildcardHorizonConfidence
                            )
                                ? min(
                                    (float) $currentHorizonConfidence,
                                    (float) $wildcardHorizonConfidence
                                )
                                : null;


                        /*
                         * --------------------------------------------------------
                         * REQUIRE PROJECTION CONFIDENCE
                         * --------------------------------------------------------
                         *
                         * A Wildcard decision compares two projected squad
                         * horizons.
                         *
                         * If either horizon lacks projection-confidence evidence,
                         * the comparison must not fall back to timing confidence
                         * alone.
                         */

                        if (
                            !is_numeric(
                                $horizonProjectionConfidence
                            )
                        ) {

                            return
                                $this->unavailableResult(
                                    'Current and Wildcard horizons must both provide projection confidence.'
                                );
                        }


                        /*
                         * --------------------------------------------------------
                         * IMMEDIATE WILDCARD VALUE
                         * --------------------------------------------------------
                         */

                    $immediateValue =
                        $this->wildcardTimingIntelligence
                            ->analyseImmediateValue(
                                $currentProjectedPoints,
                                $wildcardProjectedPoints
                            );


        /*
         * --------------------------------------------------------
         * DECISION
         * --------------------------------------------------------
         *
         * Genuine future Wildcard timing has not yet been wired
         * into this orchestration service.
         *
         * For this integration stage the future projected gain
         * therefore remains zero.
         */

                $decision =
                    $this->wildcardTimingIntelligence
                        ->createDecision(
                            $immediateValue[
                                'projected_points_gain'
                            ],
                            0.0,
                            $horizonProjectionConfidence
                        );


        return [

            'status' =>
                'Available',

            'current_squad_projected_points' =>
                $immediateValue[
                    'current_squad_projected_points'
                ],

            'wildcard_squad_projected_points' =>
                $immediateValue[
                    'wildcard_squad_projected_points'
                ],

            'projected_points_gain' =>
                $immediateValue[
                    'projected_points_gain'
                ],

            'improves_squad' =>
                $immediateValue[
                    'improves_squad'
                ],

            'decision' =>
                $decision
        ];
    }


    /*
     * ============================================================
     * HORIZON VALIDATION
     * ============================================================
     */

    private function isValidHorizon(
        array $horizon
    ): bool {

        if (
            (
                $horizon[
                    'status'
                ]
                ??
                null
            )
            !==
            'Available'
        ) {

            return
                false;
        }


        $horizonLength =
            $horizon[
                'horizon'
            ]
            ??
            null;


        if (
            !is_numeric(
                $horizonLength
            )
            ||
            (int) $horizonLength
            <=
            0
        ) {

            return
                false;
        }


        $gameweeks =
            $horizon[
                'gameweeks'
            ]
            ??
            null;


        if (
            !is_array(
                $gameweeks
            )
            ||
            empty(
                $gameweeks
            )
        ) {

            return
                false;
        }


        if (
            count(
                $gameweeks
            )
            !==
            (int) $horizonLength
        ) {

            return
                false;
        }


        foreach (
            $gameweeks
            as $gameweekKey => $gameweek
        ) {

            if (
                !is_array(
                    $gameweek
                )
            ) {

                return
                    false;
            }


            $gameweekNumber =
                $gameweek[
                    'gameweek'
                ]
                ??
                $gameweekKey;


            if (
                !is_numeric(
                    $gameweekNumber
                )
                ||
                (int) $gameweekNumber
                <=
                0
            ) {

                return
                    false;
            }


            $projectedPoints =
                $gameweek[
                    'starting_xi_projected_points'
                ]
                ??
                null;


            /*
             * Zero is valid.
             *
             * Missing or non-numeric projection data is not.
             */
            if (
                !is_numeric(
                    $projectedPoints
                )
            ) {

                return
                    false;
            }
        }


        return
            true;
    }


    /*
     * ============================================================
     * EXTRACT GAMEWEEK NUMBERS
     * ============================================================
     */

    private function extractGameweekNumbers(
        array $horizon
    ): array {

        $gameweekNumbers =
            [];


        foreach (
            $horizon[
                'gameweeks'
            ]
            as $gameweekKey => $gameweek
        ) {

            $gameweekNumber =
                $gameweek[
                    'gameweek'
                ]
                ??
                $gameweekKey;


            $gameweekNumbers[] =
                (int) $gameweekNumber;
        }


        sort(
            $gameweekNumbers,
            SORT_NUMERIC
        );


        return
            $gameweekNumbers;
    }


    /*
     * ============================================================
     * SUM STARTING XI PROJECTED POINTS
     * ============================================================
     */

    private function sumStartingXiProjectedPoints(
        array $horizon
    ): float {

        $total =
            0.0;


        foreach (
            $horizon[
                'gameweeks'
            ]
            as $gameweek
        ) {

            $total +=
                (float) $gameweek[
                    'starting_xi_projected_points'
                ];
        }


        return
            $total;
    }


    /*
     * ============================================================
     * UNAVAILABLE RESULT
     * ============================================================
     */

    private function unavailableResult(
        string $reason
    ): array {

        return [

            'status' =>
                'Unavailable',

            'reason' =>
                $reason,

            'current_squad_projected_points' =>
                null,

            'wildcard_squad_projected_points' =>
                null,

            'projected_points_gain' =>
                null,

            'improves_squad' =>
                null,

            'decision' =>
                null
        ];
    }
}