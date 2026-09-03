<?php

class FreeHitDecisionIntelligence
{
    private const HOLD_MAX_GAIN =
        4.0;


    private const USE_MIN_GAIN =
        10.0;


    /*
     * ============================================================
     * ANALYSE FREE HIT VALUE
     * ============================================================
     *
     * Compare the projected points of the current squad's best
     * legal Starting XI with the projected points of the optimized
     * Free Hit Starting XI.
     *
     * Expected Points are calculated elsewhere. This class only
     * compares already-produced projection values.
     */
    public function analyseValue(
        float $currentSquadProjectedPoints,
        float $freeHitProjectedPoints
    ): array {

        /*
         * --------------------------------------------------------
         * VALIDATE PROJECTIONS
         * --------------------------------------------------------
         */

        if (
            $currentSquadProjectedPoints < 0.0
        ) {

            throw new InvalidArgumentException(
                'Current squad projected points cannot be negative.'
            );
        }


        if (
            $freeHitProjectedPoints < 0.0
        ) {

            throw new InvalidArgumentException(
                'Free Hit projected points cannot be negative.'
            );
        }


        /*
         * --------------------------------------------------------
         * CALCULATE FREE HIT GAIN
         * --------------------------------------------------------
         */

        $projectedPointsGain =
            $freeHitProjectedPoints
            -
            $currentSquadProjectedPoints;


        return [

            'current_squad_projected_points' =>
                $currentSquadProjectedPoints,

            'free_hit_projected_points' =>
                $freeHitProjectedPoints,

            'projected_points_gain' =>
                $projectedPointsGain,

            'improves_squad' =>
                $projectedPointsGain > 0.0
        ];
    }


    /*
     * ============================================================
     * CREATE FREE HIT CHIP DECISION
     * ============================================================
     *
     * Recommendation bands:
     *
     * <= 4 projected points gain:
     *     Hold
     *
     * > 4 and < 10 projected points gain:
     *     Consider
     *
     * >= 10 projected points gain:
     *     Use
     */
    public function createDecision(
        float $projectedPointsGain,
        float $confidence
    ): ChipDecision {

        /*
         * --------------------------------------------------------
         * VALIDATE CONFIDENCE
         * --------------------------------------------------------
         */

        if (
            $confidence < 0.0
            ||
            $confidence > 1.0
        ) {

            throw new InvalidArgumentException(
                'Free Hit decision confidence must be between 0 and 1.'
            );
        }


        /*
         * --------------------------------------------------------
         * HOLD
         * --------------------------------------------------------
         */

        if (
            $projectedPointsGain
            <=
            self::HOLD_MAX_GAIN
        ) {

            return
                new ChipDecision(
                    'Free Hit',
                    'Hold',
                    $confidence,
                    'The projected Free Hit improvement is too small to justify using the chip.'
                );
        }


        /*
         * --------------------------------------------------------
         * USE
         * --------------------------------------------------------
         */

        if (
            $projectedPointsGain
            >=
            self::USE_MIN_GAIN
        ) {

            return
                new ChipDecision(
                    'Free Hit',
                    'Use',
                    $confidence,
                    'The projected Free Hit improvement is large enough to justify using the chip.'
                );
        }


        /*
         * --------------------------------------------------------
         * CONSIDER
         * --------------------------------------------------------
         */

        return
            new ChipDecision(
                'Free Hit',
                'Consider',
                $confidence,
                'The projected Free Hit improvement is meaningful but not strong enough for an automatic Use recommendation.'
            );
    }
}