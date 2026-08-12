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
     *
     * Example with a decay factor of 0.90:
     *
     * Age 0 = 1.00
     * Age 1 = 0.90
     * Age 2 = 0.81
     * Age 3 = 0.73
     * Age 4 = 0.66
     */
    public function calculateWeight(
        int $age,
        float $decayFactor = 0.90
    ): float {

        if ($age < 0) {
            $age = 0;
        }

        if ($decayFactor < 0) {
            $decayFactor = 0;
        }

        if ($decayFactor > 1) {
            $decayFactor = 1;
        }

        return round(
            pow($decayFactor, $age),
            4
        );
    }


    /**
     * Apply historical decay weights to a collection
     * of performance results.
     *
     * The array is expected to be in chronological order,
     * with the oldest match first and the most recent match
     * last.
     *
     * Each item must contain:
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
         * The latest match receives age 0.
         */
        $latestIndex =
            count($matches) - 1;


        $weightedTotal = 0.0;
        $weightTotal = 0.0;


        foreach ($matches as $index => $match) {

            $age =
                $latestIndex - $index;


            $weight =
                $this->calculateWeight(
                    $age,
                    $decayFactor
                );


            $performance =
                (float) $match['performance'];


            $weightedTotal +=
                $performance * $weight;


            $weightTotal +=
                $weight;
        }


        if ($weightTotal <= 0) {
            return null;
        }


        return round(
            $weightedTotal / $weightTotal,
            2
        );
    }
}