<?php

class TeamStrengthModel
{
    /**
     * Calculate the weighting given to the FPL baseline.
     */
    public function calculateBaselineWeight(
        int $played
    ): float {

        if ($played <= 0) {
            return 1.00;
        }

        if ($played === 1) {
            return 0.90;
        }

        if ($played === 2) {
            return 0.85;
        }

        if ($played === 3) {
            return 0.80;
        }

        if ($played === 4) {
            return 0.75;
        }

        if ($played === 5) {
            return 0.70;
        }

        if ($played === 6) {
            return 0.65;
        }

        if ($played === 7) {
            return 0.60;
        }

        if ($played === 8) {
            return 0.55;
        }

        if ($played === 9) {
            return 0.50;
        }

        return 0.45;
    }


    /**
     * Calculate the weighting given to actual performance.
     */
    public function calculatePerformanceWeight(
        int $played
    ): float {

        return round(
            1 -
            $this->calculateBaselineWeight(
                $played
            ), 
            2
        );
    }


    /**
     * Combine a baseline rating with actual performance.
     */
    public function calculateCombinedRating(
        float $baseline,
        ?float $performance,
        int $played
    ): float {

        /*
         * No completed matches means there is no
         * performance data to use.
         *
         * Therefore the FPL baseline remains
         * the complete rating.
         */
        if (
            $played <= 0
            ||
            $performance === null
        ) {
            return round(
                $baseline,
                2
            );
        }


        $baselineWeight =
            $this->calculateBaselineWeight(
                $played
            );

        $performanceWeight =
            $this->calculatePerformanceWeight(
                $played
            );


        return round(
            (
                $baseline * $baselineWeight
            )
            +
            (
                $performance * $performanceWeight
            ),
            2
        );
    }


    /**
     * Build the complete strength model for one team.
     *
     * Baseline strength comes from TeamStrength.
     *
     * Performance rating comes from TeamPerformance.
     *
     * The two are blended according to the number
     * of completed matches.
     */
    public function buildTeamModel(
        array $baseline,
        array $performance,
        TeamPerformance $performanceModel
    ): array {

        $played =
            (int) $performance['played'];


        /*
         * TeamPerformance is the single source
         * of truth for the performance rating.
         */
        $performanceRating =
            $performanceModel->calculatePerformanceRating(
                $performance
            );


        /*
         * Calculate combined home rating.
         */
        $homeRating =
            $this->calculateCombinedRating(
                (float) $baseline['home'],
                $performanceRating,
                $played
            );


        /*
         * Calculate combined away rating.
         */
        $awayRating =
            $this->calculateCombinedRating(
                (float) $baseline['away'],
                $performanceRating,
                $played
            );


        /*
         * Calculate combined overall rating.
         */
        $overallRating =
            $this->calculateCombinedRating(
                (float) $baseline['overall'],
                $performanceRating,
                $played
            );


        return [

            'id' =>
                (int) $baseline['id'],

            'name' =>
                $baseline['name'],

            'played' =>
                $played,

            'baseline_home' =>
                $baseline['home'],

            'baseline_away' =>
                $baseline['away'],

            'baseline_overall' =>
                $baseline['overall'],

            'performance_rating' =>
                $performanceRating,

            'home' =>
                $homeRating,

            'away' =>
                $awayRating,

            'overall' =>
                $overallRating,

            'baseline_weight' =>
                $this->calculateBaselineWeight(
                    $played
                ),

            'performance_weight' =>
                $this->calculatePerformanceWeight(
                    $played
                )
        ];
    }
}