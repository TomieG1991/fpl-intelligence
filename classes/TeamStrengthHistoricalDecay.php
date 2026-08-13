<?php

class TeamStrengthHistoricalDecay
{
    /**
     * Calculate the weight applied to a historical match.
     *
     * The most recent match has an age of 0 and receives
     * a weight of 1.00.
     *
     * Each older match receives progressively less weight.
     */
    public function calculateWeight(
        int $age,
        float $decayFactor = 0.90
    ): float {

        /*
         * Negative ages are treated as the
         * most recent possible match.
         */
        $age =
            max(
                0,
                $age
            );


        /*
         * Keep decay inside the valid 0-1 range.
         */
        $decayFactor =
            max(
                0,
                min(
                    1,
                    $decayFactor
                )
            );


        return round(
            pow(
                $decayFactor,
                $age
            ),
            4
        );
    }


    /**
     * Apply historical decay weights to a collection
     * of performance results.
     *
     * Matches are expected in chronological order:
     *
     * oldest -> newest
     *
     * Each usable match must contain:
     *
     * [
     *     'performance' => 0-100
     * ]
     */
    public function calculateWeightedPerformance(
        array $matches,
        float $decayFactor = 0.90
    ): ?float {

        if (empty($matches)) {
            return null;
        }


        /*
         * Remove malformed performance entries while
         * preserving chronological order.
         */
        $validMatches = [];


        foreach ($matches as $match) {

            if (
                !array_key_exists(
                    'performance',
                    $match
                )
                ||
                $match['performance'] === null
                ||
                !is_numeric(
                    $match['performance']
                )
            ) {

                continue;
            }


            $validMatches[] = [

                'performance' =>
                    max(
                        0,
                        min(
                            100,
                            (float)
                                $match[
                                    'performance'
                                ]
                        )
                    )
            ];
        }


        if (empty($validMatches)) {
            return null;
        }


        /*
         * The newest valid match receives age 0.
         */
        $latestIndex =
            count($validMatches)
            -
            1;


        $weightedTotal =
            0.0;


        $weightTotal =
            0.0;


        foreach (
            $validMatches
            as $index => $match
        ) {

            $age =
                $latestIndex
                -
                $index;


            $weight =
                $this->calculateWeight(
                    $age,
                    $decayFactor
                );


            $performance =
                $match['performance'];


            $weightedTotal +=
                $performance
                *
                $weight;


            $weightTotal +=
                $weight;
        }


        if ($weightTotal <= 0) {
            return null;
        }


        $weightedPerformance =
            $weightedTotal
            /
            $weightTotal;


        return round(
            max(
                0,
                min(
                    100,
                    $weightedPerformance
                )
            ),
            2
        );
    }
}