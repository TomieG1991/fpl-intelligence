<?php

/**
 * GameweekStartingXIBacktestingService
 *
 * Orchestrates Starting XI backtesting from an already assembled
 * GameweekBacktestingEvidenceService result.
 *
 * This class does not:
 *
 * - fetch recommendation history
 * - fetch actual player outcomes
 * - determine gameweek availability
 * - recalculate Expected Points
 * - recalculate Player Intelligence
 * - recalculate Gameweek Intelligence
 * - select an alternative Starting XI
 * - simulate automatic substitutions
 * - evaluate captain recommendations
 * - evaluate transfer recommendations
 * - create an accuracy score
 * - create an overall backtesting score
 *
 * Historical evidence retrieval remains the responsibility of
 * GameweekBacktestingEvidenceService.
 *
 * Starting XI factual evaluation remains the responsibility of
 * the supplied specialist backtesting service.
 */
class GameweekStartingXIBacktestingService
{
    private object $startingXIBacktestingService;


    /**
     * Constructor.
     */
    public function __construct(
        object $startingXIBacktestingService
    ) {

        $this->startingXIBacktestingService =
            $startingXIBacktestingService;
    }


    /**
     * Evaluate the preserved Starting XI recommendation from an
     * already-assembled completed-gameweek evidence envelope.
     */
    public function evaluate(
        array $evidence
    ): array {

        /*
         * ========================================================
         * EVIDENCE STATUS
         * ========================================================
         *
         * GameweekBacktestingEvidenceService owns historical
         * availability and completeness.
         *
         * Do not manufacture Starting XI evaluation when the
         * evidence envelope is not Ready.
         */

        $status =
            $evidence[
                'status'
            ]
            ?? null;


        if (
            $status !==
            'Ready'
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

                'starting_xi_evaluation' =>
                    []
            ];
        }


        /*
         * ========================================================
         * READY EVIDENCE STRUCTURE
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
            ||
            empty(
                $recommendationSnapshot
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires a recommendation snapshot.'
            );
        }


        $startingXI =
            $recommendationSnapshot[
                'starting_xi'
            ]
            ?? null;


        if (
            !is_array(
                $startingXI
            )
            ||
            empty(
                $startingXI
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires preserved Starting XI evidence.'
            );
        }


        /*
         * Bench evidence is passed through exactly as preserved.
         *
         * A specialist evaluator may decide that an incomplete
         * bench prevents a particular comparison. This adapter
         * must not reconstruct missing recommendation evidence.
         */

        $bench =
            $recommendationSnapshot[
                'bench'
            ]
            ?? [];


        if (
            !is_array(
                $bench
            )
        ) {

            $bench =
                [];
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
                'Ready backtesting evidence requires authoritative player outcomes.'
            );
        }


        /*
         * ========================================================
         * SPECIALIST STARTING XI EVALUATION
         * ========================================================
         *
         * Delegate immutable recommendation evidence and
         * authoritative outcomes unchanged.
         */

        $startingXIEvaluation =
            $this
                ->startingXIBacktestingService
                ->evaluate(
                    $startingXI,
                    $bench,
                    $playerOutcomes
                );


        /*
         * ========================================================
         * STRUCTURALLY UNEVALUABLE EVIDENCE
         * ========================================================
         *
         * The gameweek itself may be historically Ready while
         * the preserved Starting XI evidence cannot support the
         * specialist comparison.
         *
         * Do not manufacture a zero or successful result.
         */

        if (
            !is_array(
                $startingXIEvaluation
            )
            ||
            empty(
                $startingXIEvaluation
            )
        ) {

            return [

                'status' =>
                    'Incomplete',

                'reason' =>
                    'Preserved Starting XI evidence could not be evaluated.',

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

                'starting_xi_evaluation' =>
                    []
            ];
        }


        /*
         * ========================================================
         * READY RESULT
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

            'starting_xi_evaluation' =>
                $startingXIEvaluation
        ];
    }
}