<?php

class PlayerFormTrend
{
    private PlayerForm $playerForm;


    /**
     * Initialise Player Form Trend Intelligence.
     */
    public function __construct(
        PlayerForm $playerForm
    ) {

        $this->playerForm =
            $playerForm;
    }


    /**
     * Build recent form and participation trend intelligence.
     *
     * Short window:
     * - last 3 fixtures / appearances
     *
     * Long window:
     * - last 5 fixtures / appearances
     *
     * The short window is compared against the longer baseline.
     *
     * A threshold is used so small differences are treated as
     * normal statistical noise rather than meaningful trends.
     */
    public function buildModel(
        int $playerId,
        ?string $position = null
    ): array {

        $shortModel =
            $this->playerForm
                ->buildModel(
                    $playerId,
                    $position,
                    3,
                    3
                );


        $longModel =
            $this->playerForm
                ->buildModel(
                    $playerId,
                    $position,
                    5,
                    5
                );


        $shortFormRating =
            $this->nullableFloat(
                $shortModel[
                    'form_rating'
                ]
                ?? null
            );


        $longFormRating =
            $this->nullableFloat(
                $longModel[
                    'form_rating'
                ]
                ?? null
            );


        $shortPerformanceRating =
            $this->nullableFloat(
                $shortModel[
                    'performance_rating'
                ]
                ?? null
            );


        $longPerformanceRating =
            $this->nullableFloat(
                $longModel[
                    'performance_rating'
                ]
                ?? null
            );


        $shortParticipation =
            $this->nullableFloat(
                $shortModel[
                    'participation_rate'
                ]
                ?? null
            );


        $longParticipation =
            $this->nullableFloat(
                $longModel[
                    'participation_rate'
                ]
                ?? null
            );


        /*
         * --------------------------------------------------------
         * FORM TREND
         * --------------------------------------------------------
         */

        $formDifference =
            (
                $shortPerformanceRating !== null
                &&
                $longPerformanceRating !== null
            )
                ? $shortPerformanceRating
                    -
                    $longPerformanceRating
                : null;


        $formTrend =
            $this->classifyDifference(
                $formDifference,
                5.0
            );


        /*
         * --------------------------------------------------------
         * PARTICIPATION TREND
         * --------------------------------------------------------
         *
         * Participation is deliberately separate from Form Rating.
         *
         * This prevents recent playing-time instability from being
         * confused with on-pitch performance quality.
         */

        $participationDifference =
            (
                $shortParticipation !== null
                &&
                $longParticipation !== null
            )
                ? $shortParticipation
                    -
                    $longParticipation
                : null;


        $participationTrend =
            $this->classifyDifference(
                $participationDifference,
                10.0
            );


        /*
         * --------------------------------------------------------
         * MINUTES TREND
         * --------------------------------------------------------
         *
         * Participation Rate answers:
         *
         * Did the player appear?
         *
         * Minutes per fixture answers:
         *
         * How much did the player actually play?
         */

        $shortMinutesPerFixture =
            $this->metric(
                $shortModel,
                'minutes_per_fixture'
            );


        $longMinutesPerFixture =
            $this->metric(
                $longModel,
                'minutes_per_fixture'
            );


        $minutesDifference =
            (
                $shortMinutesPerFixture !== null
                &&
                $longMinutesPerFixture !== null
            )
                ? $shortMinutesPerFixture
                    -
                    $longMinutesPerFixture
                : null;


        $minutesTrend =
            $this->classifyDifference(
                $minutesDifference,
                10.0
            );


        /*
         * --------------------------------------------------------
         * SAMPLE QUALITY
         * --------------------------------------------------------
         *
         * Trend classifications are only considered meaningful
         * when there is enough historical evidence.
         */

        $shortFixtureSample =
            (int) (
                $shortModel[
                    'fixture_sample_size'
                ]
                ?? 0
            );


        $longFixtureSample =
            (int) (
                $longModel[
                    'fixture_sample_size'
                ]
                ?? 0
            );


        $shortAppearanceSample =
            (int) (
                $shortModel[
                    'appearance_sample_size'
                ]
                ?? 0
            );


        $longAppearanceSample =
            (int) (
                $longModel[
                    'appearance_sample_size'
                ]
                ?? 0
            );


        $hasFullTrendSample =
            $shortFixtureSample >= 3
            &&
            $longFixtureSample >= 5;


        $hasPerformanceTrendSample =
            $shortAppearanceSample >= 2
            &&
            $longAppearanceSample >= 3;


        /*
         * Form trend requires sufficient appearance evidence.
         */
        if (
            !$hasPerformanceTrendSample
        ) {

            $formTrend =
                'Insufficient Data';
        }


        /*
         * Participation and minutes trends use team-fixture
         * evidence, so they require the full fixture windows.
         */
        if (
            !$hasFullTrendSample
        ) {

            $participationTrend =
                'Insufficient Data';


            $minutesTrend =
                'Insufficient Data';
        }


        return [

            'player_id' =>
                $playerId,

            'position' =>
                $longModel[
                    'position'
                ]
                ?? 'MID',

            'form_trend' =>
                $formTrend,

            'participation_trend' =>
                $participationTrend,

            'minutes_trend' =>
                $minutesTrend,

            'form_difference' =>
                $this->roundNullable(
                    $formDifference
                ),

            'participation_difference' =>
                $this->roundNullable(
                    $participationDifference
                ),

            'minutes_difference' =>
                $this->roundNullable(
                    $minutesDifference
                ),

            'short_form_rating' =>
                $shortFormRating,

            'long_form_rating' =>
                $longFormRating,
                
            'short_performance_rating' =>
                $shortPerformanceRating,

            'long_performance_rating' =>
                $longPerformanceRating,

            'short_participation_rate' =>
                $shortParticipation,

            'long_participation_rate' =>
                $longParticipation,

            'short_minutes_per_fixture' =>
                $shortMinutesPerFixture,

            'long_minutes_per_fixture' =>
                $longMinutesPerFixture,

            'short_fixture_sample_size' =>
                $shortFixtureSample,

            'long_fixture_sample_size' =>
                $longFixtureSample,

            'short_appearance_sample_size' =>
                $shortAppearanceSample,

            'long_appearance_sample_size' =>
                $longAppearanceSample,

            'has_full_trend_sample' =>
                $hasFullTrendSample,

            'has_performance_trend_sample' =>
                $hasPerformanceTrendSample,

            'short_model' =>
                $shortModel,

            'long_model' =>
                $longModel
        ];
    }


    /**
     * Classify a numeric difference using a symmetrical
     * positive / negative threshold.
     */
    public function classifyDifference(
        ?float $difference,
        float $threshold
    ): string {

        if (
            $difference === null
            ||
            !is_numeric(
                $difference
            )
        ) {

            return 'Insufficient Data';
        }


        $threshold =
            abs(
                $threshold
            );


        if (
            $difference >= $threshold
        ) {

            return 'Improving';
        }


        if (
            $difference <= -$threshold
        ) {

            return 'Declining';
        }


        return 'Stable';
    }


    /**
     * Read one weighted Player Form metric.
     */
    private function metric(
        array $model,
        string $metric
    ): ?float {

        $value =
            $model[
                'weighted_metrics'
            ][
                $metric
            ]
            ?? null;


        return $this->nullableFloat(
            $value
        );
    }


    /**
     * Convert numeric input while preserving NULL.
     */
    private function nullableFloat(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            $value === ''
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return (float) $value;
    }


    /**
     * Round diagnostics while preserving NULL.
     */
    private function roundNullable(
        ?float $value
    ): ?float {

        return $value !== null
            ? round(
                $value,
                2
            )
            : null;
    }
}