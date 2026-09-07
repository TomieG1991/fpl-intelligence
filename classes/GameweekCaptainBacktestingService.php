<?php

/**
 * GameweekCaptainBacktestingService
 *
 * Orchestrates captain backtesting from an already assembled
 * GameweekBacktestingEvidenceService result.
 *
 * This class does not:
 *
 * - fetch recommendation history
 * - fetch actual player outcomes
 * - determine gameweek availability
 * - recalculate Captain Intelligence
 * - recalculate Expected Points
 * - recalculate Player Intelligence
 * - simulate vice-captain fallback
 * - evaluate transfer recommendations
 * - create an accuracy score
 * - create an overall backtesting score
 *
 * Historical evidence retrieval remains the responsibility of
 * GameweekBacktestingEvidenceService.
 *
 * Captain-level factual comparison remains the responsibility of
 * CaptainBacktestingService.
 */
class GameweekCaptainBacktestingService
{
    private object $captainBacktestingService;


    /**
     * Constructor.
     */
    public function __construct(
        object $captainBacktestingService
    ) {
        $this->captainBacktestingService =
            $captainBacktestingService;
    }


    /**
     * Evaluate captain recommendation quality from assembled
     * historical evidence.
     */
    public function evaluate(
        array $evidence
    ): array {

        $status =
            $evidence[
                'status'
            ]
            ?? null;


        /*
         * ========================================================
         * NON-READY EVIDENCE
         * ========================================================
         *
         * Availability and completeness have already been decided
         * by GameweekBacktestingEvidenceService.
         *
         * Do not attempt to manufacture captain evaluation from
         * evidence that is not Ready.
         */

        if (
            $status !== 'Ready'
        ) {

            return [

                'status' =>
                    $status,

                'reason' =>
                    $evidence[
                        'reason'
                    ]
                    ?? null,

                'entry_id' =>
                    $evidence[
                        'entry_id'
                    ]
                    ?? null,

                'gameweek_id' =>
                    $evidence[
                        'gameweek_id'
                    ]
                    ?? null,

                'captain_evaluation' =>
                    []
            ];
        }


        /*
         * ========================================================
         * READY EVIDENCE VALIDATION
         * ========================================================
         */

        $recommendationSnapshot =
            $evidence[
                'recommendation_snapshot'
            ]
            ?? null;


        if (
            !is_array(
                $recommendationSnapshot
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires a recommendation snapshot.'
            );
        }


        $captainRecommendation =
            $recommendationSnapshot[
                'captain_recommendation'
            ]
            ?? null;


        if (
            !is_array(
                $captainRecommendation
            )
            ||
            empty(
                $captainRecommendation
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires preserved captain recommendation evidence.'
            );
        }


        $captain =
            $captainRecommendation[
                'captain'
            ]
            ?? null;


        if (
            !is_array(
                $captain
            )
            ||
            empty(
                $captain
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires a preserved recommended captain.'
            );
        }


        $captainRankings =
            $captainRecommendation[
                'rankings'
            ]
            ?? null;


        if (
            !is_array(
                $captainRankings
            )
            ||
            empty(
                $captainRankings
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires preserved Captain Intelligence rankings.'
            );
        }


        $playerOutcomes =
            $evidence[
                'player_outcomes'
            ]
            ?? null;


        if (
            !is_array(
                $playerOutcomes
            )
            ||
            empty(
                $playerOutcomes
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires realised player outcomes.'
            );
        }


        /*
         * ========================================================
         * CAPTAIN-LEVEL EVALUATION
         * ========================================================
         */

        $captainEvaluation =
            $this
                ->captainBacktestingService
                ->evaluate(
                    $captain,
                    $captainRankings,
                    $playerOutcomes
                );


        /*
         * ========================================================
         * STRUCTURALLY UNEVALUABLE CAPTAIN EVIDENCE
         * ========================================================
         *
         * The gameweek evidence itself may be Ready while the
         * preserved captain evidence is not suitable for a fair
         * captain comparison.
         *
         * CaptainBacktestingService owns that structural decision.
         */

        if (
            empty(
                $captainEvaluation
            )
        ) {

            return [

                'status' =>
                    'Incomplete',

                'reason' =>
                    'Preserved captain evidence could not be evaluated.',

                'entry_id' =>
                    $evidence[
                        'entry_id'
                    ]
                    ?? null,

                'gameweek_id' =>
                    $evidence[
                        'gameweek_id'
                    ]
                    ?? null,

                'captain_evaluation' =>
                    []
            ];
        }


        /*
         * ========================================================
         * CAPTAIN BACKTESTING CONTRACT
         * ========================================================
         */

        return [

            'status' =>
                'Ready',

            'reason' =>
                null,

            'entry_id' =>
                $evidence[
                    'entry_id'
                ]
                ?? null,

            'gameweek_id' =>
                $evidence[
                    'gameweek_id'
                ]
                ?? null,

            'captain_evaluation' =>
                $captainEvaluation
        ];
    }
}