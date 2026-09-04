<?php

class TripleCaptainDecisionIntelligenceService
{
    private SquadHorizonIntelligenceService
        $squadHorizonIntelligenceService;

    private PlayerIntelligenceService
        $playerIntelligenceService;

    private CaptainIntelligence
        $captainIntelligence;

    private TripleCaptainIntelligence
        $tripleCaptainIntelligence;


    /*
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */

    public function __construct(
        SquadHorizonIntelligenceService $squadHorizonIntelligenceService,
        PlayerIntelligenceService $playerIntelligenceService,
        CaptainIntelligence $captainIntelligence,
        TripleCaptainIntelligence $tripleCaptainIntelligence
    ) {

        $this->squadHorizonIntelligenceService =
            $squadHorizonIntelligenceService;

        $this->playerIntelligenceService =
            $playerIntelligenceService;

        $this->captainIntelligence =
            $captainIntelligence;

        $this->tripleCaptainIntelligence =
            $tripleCaptainIntelligence;
    }


    /*
     * ============================================================
     * BUILD
     * ============================================================
     */

    public function build(
        array $importedSquad
    ): array {

        /*
         * --------------------------------------------------------
         * CURRENT ONE-GAMEWEEK HORIZON
         * --------------------------------------------------------
         */

        $currentHorizonResult =
            $this
                ->squadHorizonIntelligenceService
                ->buildForImportedSquad(
                    $importedSquad,
                    1
                );


        if (
            (
                $currentHorizonResult[
                    'status'
                ]
                ?? null
            )
            !==
            'Available'
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        $horizonResult =
            $currentHorizonResult[
                'horizon_result'
            ]
            ?? null;


        if (
            !is_array(
                $horizonResult
            )
            ||
            (
                $horizonResult[
                    'status'
                ]
                ?? null
            )
            !==
            'Available'
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        $gameweeks =
            $horizonResult[
                'gameweeks'
            ]
            ?? [];


        if (
            !is_array(
                $gameweeks
            )
            ||
            count(
                $gameweeks
            )
            !==
            1
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        $gameweek =
            reset(
                $gameweeks
            );


        if (
            !is_array(
                $gameweek
            )
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        /*
         * --------------------------------------------------------
         * AUTHORITATIVE SQUAD HORIZON CAPTAIN
         * --------------------------------------------------------
         */

        $horizonCaptain =
            $gameweek[
                'captain'
            ]
            ?? null;


        if (
            !is_array(
                $horizonCaptain
            )
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        $captainPlayerId =
            isset(
                $horizonCaptain[
                    'player_id'
                ]
            )
            &&
            is_numeric(
                $horizonCaptain[
                    'player_id'
                ]
            )
                ? (int) $horizonCaptain[
                    'player_id'
                ]
                : 0;


        if (
            $captainPlayerId
            <=
            0
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        /*
         * --------------------------------------------------------
         * LOAD PLAYER INTELLIGENCE SUMMARIES
         * --------------------------------------------------------
         *
         * CaptainIntelligence expects the lightweight player
         * summary contract produced by getAllPlayerSummaries().
         *
         * buildSquadFromFPLImport() exposes a different squad-facing
         * shape and must not be used as CaptainIntelligence input.
         */

        $playerSummaries =
            $this
                ->playerIntelligenceService
                ->getAllPlayerSummaries();


        if (
            !is_array(
                $playerSummaries
            )
            ||
            empty(
                $playerSummaries
            )
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        /*
         * --------------------------------------------------------
         * LOCATE THE SAME CAPTAIN SELECTED BY SQUAD HORIZON
         * --------------------------------------------------------
         */

        $captainSummary =
            null;


        foreach (
            $playerSummaries
            as $summary
        ) {

            if (
                !is_array(
                    $summary
                )
            ) {

                continue;
            }


            $summaryPlayerId =
                isset(
                    $summary[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $summary[
                        'player_id'
                    ]
                )
                    ? (int) $summary[
                        'player_id'
                    ]
                    : 0;


            if (
                $summaryPlayerId
                ===
                $captainPlayerId
            ) {

                $captainSummary =
                    $summary;

                break;
            }
        }


        if (
            $captainSummary
            ===
            null
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        /*
         * --------------------------------------------------------
         * EXISTING CAPTAIN INTELLIGENCE
         * --------------------------------------------------------
         */

        /*
         * --------------------------------------------------------
         * ADAPT PLAYER SUMMARY FOR CAPTAIN INTELLIGENCE
         * --------------------------------------------------------
         *
         * PlayerIntelligenceService exposes application-level summary
         * field names such as:
         *
         * - strength_rating
         * - next_fixture_rating
         * - availability_rating
         *
         * CaptainIntelligence uses its established decision-model
         * contract:
         *
         * - strength_score
         * - fixture_score
         * - availability
         *
         * This mirrors the existing Captain Intelligence integration
         * rather than introducing a separate scoring model.
         */

        $captainInput =
            $captainSummary;


        /*
         * Underlying player strength.
         */
        $captainInput[
            'strength_score'
        ] =
            $captainSummary[
                'strength_rating'
            ]
            ?? null;


        /*
         * Captain Intelligence deliberately uses the immediate fixture,
         * not the general multi-fixture rating.
         */
        $captainInput[
            'fixture_score'
        ] =
            $captainSummary[
                'next_fixture_rating'
            ]
            ?? null;


        /*
         * Existing availability evidence.
         */
        $captainInput[
            'availability'
        ] =
            $captainSummary[
                'availability_rating'
            ]
            ?? null;


        /*
         * --------------------------------------------------------
         * EXISTING CAPTAIN INTELLIGENCE
         * --------------------------------------------------------
         */

        $captainResult =
            $this
                ->captainIntelligence
                ->evaluate(
                    $captainInput
                );


        if (
            !is_array(
                $captainResult
            )
            ||
            (
                $captainResult[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
            ||
            !is_numeric(
                $captainResult[
                    'captain_score'
                ]
                ?? null
            )
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        /*
         * --------------------------------------------------------
         * PROJECTION CONFIDENCE
         * --------------------------------------------------------
         */

        $projectionConfidence =
            null;


        if (
            isset(
                $horizonCaptain[
                    'projection_confidence'
                ]
            )
            &&
            is_numeric(
                $horizonCaptain[
                    'projection_confidence'
                ]
            )
        ) {

            $projectionConfidence =
                (float) $horizonCaptain[
                    'projection_confidence'
                ];
        }


        /*
         * --------------------------------------------------------
         * CAPTAIN INTELLIGENCE CONFIDENCE
         * --------------------------------------------------------
         *
         * CaptainIntelligence exposes confidence on a 0–100 scale.
         *
         * TripleCaptainIntelligence and ChipDecision use 0–1.
         */

        $captainConfidence =
            null;


        $captainConfidencePercent =
            $captainResult[
                'components'
            ][
                'confidence'
            ]
            ?? null;


        if (
            is_numeric(
                $captainConfidencePercent
            )
        ) {

            $captainConfidence =
                max(
                    0.0,
                    min(
                        1.0,
                        (
                            (float) $captainConfidencePercent
                        )
                        /
                        100.0
                    )
                );
        }


        /*
         * --------------------------------------------------------
         * BUILD TRIPLE CAPTAIN OPPORTUNITY
         * --------------------------------------------------------
         *
         * No Expected Points are recalculated here.
         *
         * No Double Gameweek multiplier is applied here.
         */

        $opportunity = [

            'player_id' =>
                $captainPlayerId,

            'name' =>
                (string) (
                    $horizonCaptain[
                        'name'
                    ]
                    ??
                    $captainResult[
                        'name'
                    ]
                    ??
                    ''
                ),

            'position' =>
                (string) (
                    $horizonCaptain[
                        'position'
                    ]
                    ??
                    $captainResult[
                        'position'
                    ]
                    ??
                    ''
                ),

            'projected_points' =>
                $horizonCaptain[
                    'projected_points'
                ]
                ?? null,

            'projection_confidence' =>
                $projectionConfidence,

            'captain_score' =>
                (float) $captainResult[
                    'captain_score'
                ],

            'captain_confidence' =>
                $captainConfidence,

            'fixture_count' =>
                $horizonCaptain[
                    'fixture_count'
                ]
                ?? 0,

            'schedule_type' =>
                $horizonCaptain[
                    'schedule_type'
                ]
                ?? ''
        ];


        /*
         * --------------------------------------------------------
         * LOW-LEVEL TRIPLE CAPTAIN ANALYSIS
         * --------------------------------------------------------
         */

        try {

            $analysis =
                $this
                    ->tripleCaptainIntelligence
                    ->analyse(
                        $opportunity
                    );


            $decision =
                $this
                    ->tripleCaptainIntelligence
                    ->createDecision(
                        $analysis
                    );

        } catch (
            InvalidArgumentException $exception
        ) {

            return
                $this->unavailableResult(
                    $currentHorizonResult
                );
        }


        /*
         * --------------------------------------------------------
         * AVAILABLE RESULT
         * --------------------------------------------------------
         */

        return [

            'status' =>
                'Available',

            'current_horizon_result' =>
                $currentHorizonResult,

            'analysis' =>
                $analysis,

            'decision' =>
                $decision
        ];
    }


    /*
     * ============================================================
     * UNAVAILABLE RESULT
     * ============================================================
     */

    private function unavailableResult(
        array $currentHorizonResult
    ): array {

        return [

            'status' =>
                'Unavailable',

            'current_horizon_result' =>
                $currentHorizonResult,

            'analysis' =>
                null,

            'decision' =>
                null
        ];
    }
}