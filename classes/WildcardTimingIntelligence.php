<?php

class WildcardTimingIntelligence
{
    public function analyseImmediateValue(
        float $currentSquadProjectedPoints,
        float $wildcardSquadProjectedPoints
    ): array {

        if (
            $currentSquadProjectedPoints
            <
            0.0
        ) {

            throw new InvalidArgumentException(
                'Current squad projected points cannot be negative.'
            );
        }


        if (
            $wildcardSquadProjectedPoints
            <
            0.0
        ) {

            throw new InvalidArgumentException(
                'Wildcard squad projected points cannot be negative.'
            );
        }


        $projectedPointsGain =
            $wildcardSquadProjectedPoints
            -
            $currentSquadProjectedPoints;


        return [
            'analysis' =>
                'Immediate Wildcard Value',

            'current_squad_projected_points' =>
                $currentSquadProjectedPoints,

            'wildcard_squad_projected_points' =>
                $wildcardSquadProjectedPoints,

            'projected_points_gain' =>
                $projectedPointsGain,

            'improves_squad' =>
                $projectedPointsGain
                >
                0.0
        ];
    }
    
    
    public function compareTiming(
        float $immediateProjectedGain,
        float $futureProjectedGain
    ): array {

        $timingAdvantage =
            $immediateProjectedGain
            -
            $futureProjectedGain;


        if (
            $timingAdvantage
            >
            0.0
        ) {

            $betterTiming =
                'Now';

        } elseif (
            $timingAdvantage
            <
            0.0
        ) {

            $betterTiming =
                'Wait';

        } else {

            $betterTiming =
                'Neutral';
        }


        return [
            'analysis' =>
                'Wildcard Timing Comparison',

            'immediate_projected_gain' =>
                $immediateProjectedGain,

            'future_projected_gain' =>
                $futureProjectedGain,

            'timing_advantage' =>
                $timingAdvantage,

            'better_timing' =>
                $betterTiming
        ];
    }
    
    
    public function calculateTimingConfidence(
        float $immediateProjectedGain,
        float $futureProjectedGain
    ): float {

        $timingSeparation =
            abs(
                $immediateProjectedGain
                -
                $futureProjectedGain
            );


        return
            min(
                1.0,
                $timingSeparation
                /
                10.0
            );
    }
    
    
    public function combineConfidence(
        float $timingConfidence,
        float $projectionConfidence
    ): float {

        if (
            $timingConfidence
            <
            0.0
            ||
            $timingConfidence
            >
            1.0
        ) {

            throw new InvalidArgumentException(
                'Timing confidence must be between 0.0 and 1.0.'
            );
        }


        if (
            $projectionConfidence
            <
            0.0
            ||
            $projectionConfidence
            >
            1.0
        ) {

            throw new InvalidArgumentException(
                'Projection confidence must be between 0.0 and 1.0.'
            );
        }


        return
            min(
                $timingConfidence,
                $projectionConfidence
            );
    }
    
    
    public function createDecision(
        float $immediateProjectedGain,
        float $futureProjectedGain,
        ?float $projectionConfidence = null
    ): ChipDecision {

        $timingComparison =
            $this->compareTiming(
                $immediateProjectedGain,
                $futureProjectedGain
            );


        $timingAdvantage =
            $timingComparison[
                'timing_advantage'
            ];


        if (
            $immediateProjectedGain
            <=
            0.0
        ) {

            $recommendation =
                'Hold';

            $explanation =
                'The Wildcard does not currently improve the projected squad.';
        } elseif (
            $futureProjectedGain
            >
            $immediateProjectedGain
        ) {

            $recommendation =
                'Hold';

            $explanation =
                'Waiting is projected to provide greater Wildcard value.';
        } elseif (
            $immediateProjectedGain
            >=
            10.0
            &&
            $timingAdvantage
            >=
            5.0
        ) {

            $recommendation =
                'Use';

            $explanation =
                'The Wildcard provides a strong immediate projected gain and a clear advantage over waiting.';
        } else {

            $recommendation =
                'Consider';

            $explanation =
                'The Wildcard improves the squad now, but the immediate advantage is not strong enough for a clear Use recommendation.';
        }


        $timingConfidence =
            $this->calculateTimingConfidence(
                $immediateProjectedGain,
                $futureProjectedGain
            );


        if (
            $projectionConfidence
            ===
            null
        ) {

            $confidence =
                $timingConfidence;

        } else {

            $confidence =
                $this->combineConfidence(
                    $timingConfidence,
                    $projectionConfidence
                );
        }


        return
            new ChipDecision(
                'Wildcard',
                $recommendation,
                $confidence,
                $explanation
            );
    }
    
    
}