<?php

class ExpectedMinutes
{
    /**
     * Calculate expected playing time for the next fixture.
     *
     * Expected Minutes is intentionally separate from:
     *
     * - Sample Confidence
     * - Effective Confidence
     * - Player Form
     *
     * It answers one question only:
     *
     * "How many minutes do we currently expect this player
     *  to play in the next Premier League fixture?"
     */
    public function calculate(
        array $player,
        array $form = []
    ): array {

        $status =
            strtolower(
                trim(
                    (string) (
                        $player[
                            'status'
                        ]
                        ?? ''
                    )
                )
            );


        $chanceOfPlaying =
            $this->normaliseChanceOfPlaying(
                $player[
                    'chance_of_playing'
                ]
                ?? null,
                $status
            );


        $appearanceSampleSize =
            max(
                0,
                (int) (
                    $form[
                        'appearance_sample_size'
                    ]
                    ?? 0
                )
            );


        $fixtureSampleSize =
            max(
                0,
                (int) (
                    $form[
                        'fixture_sample_size'
                    ]
                    ?? 0
                )
            );


        $averageAppearanceMinutes =
            $this->normaliseMinutes(
                $form[
                    'raw_metrics'
                ][
                    'average_appearance_minutes'
                ]
                ?? null
            );


        $participationRate =
            $this->normalisePercentage(
                $form[
                    'participation_rate'
                ]
                ?? null
            );


        /*
         * --------------------------------------------------------
         * BASE EXPECTED MINUTES
         * --------------------------------------------------------
         *
         * Where real appearance evidence exists, average minutes
         * per appearance estimates how long the player tends to
         * remain on the pitch when selected.
         *
         * Participation rate then represents how consistently the
         * player has actually appeared in the recent fixture window.
         */
        if (
            $appearanceSampleSize > 0
            &&
            $averageAppearanceMinutes !== null
        ) {

            $baseMinutes =
                $averageAppearanceMinutes;


            if (
                $fixtureSampleSize > 0
                &&
                $participationRate !== null
            ) {

                $baseMinutes *=
                    $participationRate
                    /
                    100;
            }


            $evidenceSource =
                'Recent History';

        } else {

            /*
             * No historical appearance evidence exists yet.
             *
             * Do not invent a strong starting expectation.
             * A neutral fallback allows the projection layer to
             * function during preseason / very early season while
             * making the weaker evidence explicit.
             */
            $baseMinutes =
                60.0;


            $evidenceSource =
                'Fallback';
        }


        /*
         * Current availability modifies the historical expectation.
         */
        $projectedMinutes =
            $baseMinutes
            *
            (
                $chanceOfPlaying
                /
                100
            );


        $projectedMinutes =
            max(
                0.0,
                min(
                    90.0,
                    $projectedMinutes
                )
            );


        return [

            'projected_minutes' =>
                round(
                    $projectedMinutes,
                    2
                ),

            'base_minutes' =>
                round(
                    max(
                        0.0,
                        min(
                            90.0,
                            $baseMinutes
                        )
                    ),
                    2
                ),

            'chance_of_playing' =>
                $chanceOfPlaying,

            'participation_rate' =>
                $participationRate,

            'average_appearance_minutes' =>
                $averageAppearanceMinutes,

            'fixture_sample_size' =>
                $fixtureSampleSize,

            'appearance_sample_size' =>
                $appearanceSampleSize,

            'evidence_source' =>
                $evidenceSource
        ];
    }


    /**
     * Resolve the player's current availability percentage.
     *
     * FPL commonly leaves chance_of_playing null for players
     * whose status is fully available.
     */
    private function normaliseChanceOfPlaying(
        mixed $chance,
        string $status
    ): float {

        if (
            $chance !== null
            &&
            is_numeric(
                $chance
            )
        ) {

            return max(
                0.0,
                min(
                    100.0,
                    (float) $chance
                )
            );
        }


        /*
         * FPL status "a" means available.
         */
        if (
            $status === 'a'
            ||
            $status === ''
        ) {

            return 100.0;
        }


        /*
         * Known unavailable states must not receive projected
         * playing time merely because chance_of_playing is null.
         */
        if (
            in_array(
                $status,
                [
                    'i',
                    's',
                    'u'
                ],
                true
            )
        ) {

            return 0.0;
        }


        /*
         * Unknown non-available state.
         *
         * Keep the fallback conservative.
         */
        return 50.0;
    }


    /**
     * Normalise a minutes value onto the legal 0-90 range.
     */
    private function normaliseMinutes(
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


        return max(
            0.0,
            min(
                90.0,
                (float) $value
            )
        );
    }


    /**
     * Normalise a percentage onto the 0-100 scale.
     */
    private function normalisePercentage(
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


        return max(
            0.0,
            min(
                100.0,
                (float) $value
            )
        );
    }
}