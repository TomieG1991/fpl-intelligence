<?php

class TeamStrengthModel
{
    /**
     * Calculate the weighting given to the FPL baseline.
     *
     * The baseline gradually becomes less influential
     * as actual competitive performance becomes available.
     */
    public function calculateBaselineWeight(
        int $played
    ): float {

        if ($played <= 0) {
            return 1.00;
        }


        $weights = [

            1 => 0.90,
            2 => 0.85,
            3 => 0.80,
            4 => 0.75,
            5 => 0.70,
            6 => 0.65,
            7 => 0.60,
            8 => 0.55,
            9 => 0.50
        ];


        /*
         * From ten matches onwards the baseline
         * retains 45% influence.
         */
        return $weights[$played]
            ?? 0.45;
    }


    /**
     * Calculate the weighting given to actual performance.
     */
    public function calculatePerformanceWeight(
        int $played
    ): float {

        return round(
            1
            -
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
         * Keep component ratings inside the standard
         * 0-100 intelligence scale.
         */
        $baseline =
            max(
                0,
                min(
                    100,
                    $baseline
                )
            );


        if ($performance !== null) {

            $performance =
                max(
                    0,
                    min(
                        100,
                        $performance
                    )
                );
        }


        /*
         * No completed matches means there is no
         * performance data to use.
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


        $combined =
            (
                $baseline
                * $baselineWeight
            )
            +
            (
                $performance
                * $performanceWeight
            );


        return round(
            max(
                0,
                min(
                    100,
                    $combined
                )
            ),
            2
        );
    }


    /**
     * Build the complete strength model for one team.
     *
     * Baseline strength comes from TeamStrength.
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

        /*
         * A complete model requires valid baseline identity
         * and home/away/overall strength values.
         */
        $requiredBaselineFields = [

            'id',
            'name',
            'home',
            'away',
            'overall'
        ];


        foreach (
            $requiredBaselineFields
            as $field
        ) {

            if (
                !array_key_exists(
                    $field,
                    $baseline
                )
            ) {

                throw new InvalidArgumentException(
                    "Missing team baseline field: {$field}"
                );
            }
        }


        $played =
            max(
                0,
                (int) (
                    $performance['played']
                    ?? 0
                )
            );


        /*
         * TeamPerformance remains the single source
         * of truth for the performance rating.
         */
        $performanceRating =
            $performanceModel
                ->calculatePerformanceRating(
                    $performance
                );


        /*
         * Calculate combined ratings.
         *
         * For the current model the same overall
         * performance rating is blended into the
         * home, away and overall baselines.
         */
        $homeRating =
            $this->calculateCombinedRating(
                (float) $baseline['home'],
                $performanceRating,
                $played
            );


        $awayRating =
            $this->calculateCombinedRating(
                (float) $baseline['away'],
                $performanceRating,
                $played
            );


        $overallRating =
            $this->calculateCombinedRating(
                (float) $baseline['overall'],
                $performanceRating,
                $played
            );


        $baselineWeight =
            $this->calculateBaselineWeight(
                $played
            );


        $performanceWeight =
            $this->calculatePerformanceWeight(
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
                $baselineWeight,

            'performance_weight' =>
                $performanceWeight
        ];
    }
}