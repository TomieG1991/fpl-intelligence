<?php

class TransferCombination
{
    private TransferDecision $transferDecision;


    public function __construct()
    {
        $this->transferDecision =
            new TransferDecision();
    }


    /**
     * Evaluate a linked two-transfer combination.
     */
    public function evaluateCombination(
        array $currentPlayerA,
        array $replacementA,
        array $currentPlayerB,
        array $replacementB
    ): array {

        /*
         * ====================================================
         * INDIVIDUAL TRANSFER DECISIONS
         * ====================================================
         */

        $decisionA =
            $this->transferDecision
                ->evaluateTransfer(
                    $currentPlayerA,
                    $replacementA
                );


        $decisionB =
            $this->transferDecision
                ->evaluateTransfer(
                    $currentPlayerB,
                    $replacementB
                );


        /*
         * ====================================================
         * COMBINED MOVEMENTS
         * ====================================================
         */

        $combinedIntelligenceMovement =
            $this->combineMovements(
                $decisionA[
                    'movements'
                ]['intelligence']
                ?? null,

                $decisionB[
                    'movements'
                ]['intelligence']
                ?? null
            );


        $combinedStrengthMovement =
            $this->combineMovements(
                $decisionA[
                    'movements'
                ]['strength']
                ?? null,

                $decisionB[
                    'movements'
                ]['strength']
                ?? null
            );


        $combinedValueMovement =
            $this->combineMovements(
                $decisionA[
                    'movements'
                ]['value']
                ?? null,

                $decisionB[
                    'movements'
                ]['value']
                ?? null
            );


        $combinedFixtureMovement =
            $this->combineMovements(
                $decisionA[
                    'movements'
                ]['fixtures']
                ?? null,

                $decisionB[
                    'movements'
                ]['fixtures']
                ?? null
            );


        $combinedConfidenceMovement =
            $this->combineMovements(
                $decisionA[
                    'movements'
                ]['sample_confidence']
                ?? null,

                $decisionB[
                    'movements'
                ]['sample_confidence']
                ?? null
            );


        /*
         * Positive = money left over after both transfers.
         * Negative = combination requires extra budget.
         */
        $combinedBudgetMovement =
            $this->combineMovements(
                $decisionA[
                    'movements'
                ]['budget']
                ?? null,

                $decisionB[
                    'movements'
                ]['budget']
                ?? null
            );


        /*
         * ====================================================
         * COMBINATION SCORE
         * ====================================================
         *
         * Two-player combination weights:
         *
         * 45% combined Intelligence
         * 20% combined Fixtures
         * 15% combined Value
         * 10% combined Strength
         * 10% remaining/released budget
         */

        $combinationScore =
            $this->calculateCombinationScore(
                $combinedIntelligenceMovement,
                $combinedFixtureMovement,
                $combinedValueMovement,
                $combinedStrengthMovement,
                $combinedBudgetMovement
            );


        /*
         * ====================================================
         * AFFORDABILITY
         * ====================================================
         */

        $isAffordable =
            (
                $combinedBudgetMovement === null
                ||
                $combinedBudgetMovement >= 0
            );


        /*
         * ====================================================
         * CLASSIFICATION
         * ====================================================
         */

        $classification =
            $this->classifyCombination(
                $combinationScore,
                $combinedIntelligenceMovement,
                $combinedBudgetMovement,
                $decisionA,
                $decisionB
            );


        return [

            'transfer_a' =>
                $decisionA,

            'transfer_b' =>
                $decisionB,

            'combined_movements' => [

                'intelligence' =>
                    $combinedIntelligenceMovement,

                'strength' =>
                    $combinedStrengthMovement,

                'value' =>
                    $combinedValueMovement,

                'fixtures' =>
                    $combinedFixtureMovement,

                'sample_confidence' =>
                    $combinedConfidenceMovement,

                'budget' =>
                    $combinedBudgetMovement
            ],

            'is_affordable' =>
                $isAffordable,

            'combination_score' =>
                $combinationScore,

            'classification' =>
                $classification,

            'summary' =>
                $this->buildSummary(
                    $decisionA,
                    $decisionB,
                    $classification,
                    $combinedIntelligenceMovement,
                    $combinedBudgetMovement,
                    $isAffordable
                )
        ];
    }


    /**
     * Combine two movement values.
     */
    private function combineMovements(
        mixed $movementA,
        mixed $movementB
    ): ?float {

        if (
            !is_numeric(
                $movementA
            )
            ||
            !is_numeric(
                $movementB
            )
        ) {

            return null;
        }


        return round(
            (float) $movementA
            +
            (float) $movementB,
            2
        );
    }


    /**
     * Calculate overall two-transfer quality.
     */
    private function calculateCombinationScore(
        ?float $intelligenceMovement,
        ?float $fixtureMovement,
        ?float $valueMovement,
        ?float $strengthMovement,
        ?float $budgetMovement
    ): ?float {

        $components =
            [];


        if ($intelligenceMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $intelligenceMovement,
                        12
                    ),

                'weight' =>
                    0.45
            ];
        }


        if ($fixtureMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $fixtureMovement,
                        25
                    ),

                'weight' =>
                    0.20
            ];
        }


        if ($valueMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $valueMovement,
                        40
                    ),

                'weight' =>
                    0.15
            ];
        }


        if ($strengthMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $strengthMovement,
                        20
                    ),

                'weight' =>
                    0.10
            ];
        }


        if ($budgetMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $budgetMovement,
                        5
                    ),

                'weight' =>
                    0.10
            ];
        }


        if (empty($components)) {
            return null;
        }


        $weightedTotal =
            0;


        $weightTotal =
            0;


        foreach ($components as $component) {

            $weightedTotal +=
                $component['score']
                *
                $component['weight'];


            $weightTotal +=
                $component['weight'];
        }


        if ($weightTotal <= 0) {
            return null;
        }


        return round(
            $weightedTotal
            /
            $weightTotal,
            2
        );
    }


    /**
     * Convert movement to a bounded 0-100 score.
     */
    private function movementToScore(
        float $movement,
        float $scale
    ): float {

        if ($scale <= 0) {
            return 50.0;
        }


        $score =
            50
            +
            (
                (
                    $movement
                    /
                    $scale
                )
                *
                50
            );


        return round(
            max(
                0,
                min(
                    100,
                    $score
                )
            ),
            2
        );
    }


    /**
     * Classify the overall two-transfer strategy.
     */
    private function classifyCombination(
        ?float $score,
        ?float $intelligenceMovement,
        ?float $budgetMovement,
        array $decisionA,
        array $decisionB
    ): string {

        if ($score === null) {
            return 'Insufficient Data';
        }


        /*
         * Combination cannot be recommended if it cannot
         * be afforded from the two outgoing players.
         */
        if (
            $budgetMovement !== null
            &&
            $budgetMovement < 0
        ) {
            return 'Unaffordable';
        }


        /*
         * Extremely risky direct transfers should remain visible
         * at combination level.
         *
         * A strong second transfer does not remove the uncertainty
         * attached to a very-low-confidence incoming player.
         */
        if (
            (
                $decisionA[
                    'decision_type'
                ]
                ?? null
            )
            === 'Risky Punt'
            ||
            (
                $decisionB[
                    'decision_type'
                ]
                ?? null
            )
            === 'Risky Punt'
        ) {
            return 'Risky Restructure';
        }


        /*
         * Strong combined squad improvement.
         */
        if (
            $intelligenceMovement !== null
            &&
            $intelligenceMovement >= 4
            &&
            $score >= 60
        ) {
            return 'Strong Improvement';
        }


        /*
         * Clear positive improvement.
         */
        if (
            $intelligenceMovement !== null
            &&
            $intelligenceMovement >= 1
            &&
            $score >= 55
        ) {
            return 'Improvement';
        }


        /*
         * Small combined INT movement but a healthy overall
         * score can represent a useful redistribution.
         */
        if (
            $score >= 52
            &&
            (
                $intelligenceMovement === null
                ||
                $intelligenceMovement > -3
            )
        ) {
            return 'Balanced Restructure';
        }


        /*
         * Material combined Intelligence loss should remain
         * a downgrade regardless of released budget.
         */
        if (
            $intelligenceMovement !== null
            &&
            $intelligenceMovement <= -3
        ) {
            return 'Downgrade';
        }


        if ($score >= 45) {
            return 'Neutral Restructure';
        }


        return 'Downgrade';
    }


    /**
     * Build readable combination summary.
     */
    private function buildSummary(
        array $decisionA,
        array $decisionB,
        string $classification,
        ?float $intelligenceMovement,
        ?float $budgetMovement,
        bool $isAffordable
    ): string {

        $currentA =
            (string) (
                $decisionA[
                    'current_player'
                ]['name']
                ?? 'Player A'
            );


        $replacementA =
            (string) (
                $decisionA[
                    'replacement'
                ]['name']
                ?? 'Replacement A'
            );


        $currentB =
            (string) (
                $decisionB[
                    'current_player'
                ]['name']
                ?? 'Player B'
            );


        $replacementB =
            (string) (
                $decisionB[
                    'replacement'
                ]['name']
                ?? 'Replacement B'
            );


        $summary =
            $currentA
            . ' → '
            . $replacementA
            . ' plus '
            . $currentB
            . ' → '
            . $replacementB
            . ' is classified as '
            . strtolower(
                $classification
            )
            . '.';


        if ($intelligenceMovement !== null) {

            if ($intelligenceMovement > 0) {

                $summary .=
                    ' Combined Intelligence improves by '
                    . number_format(
                        $intelligenceMovement,
                        1
                    )
                    . ' points.';

            } elseif ($intelligenceMovement < 0) {

                $summary .=
                    ' Combined Intelligence falls by '
                    . number_format(
                        abs(
                            $intelligenceMovement
                        ),
                        1
                    )
                    . ' points.';

            } else {

                $summary .=
                    ' Combined Intelligence is unchanged.';
            }
        }


        if ($budgetMovement !== null) {

            if ($budgetMovement > 0) {

                $summary .=
                    ' The combination leaves £'
                    . number_format(
                        $budgetMovement,
                        1
                    )
                    . 'm available.';

            } elseif ($budgetMovement < 0) {

                $summary .=
                    ' The combination requires an additional £'
                    . number_format(
                        abs(
                            $budgetMovement
                        ),
                        1
                    )
                    . 'm.';

            } else {

                $summary .=
                    ' The combination uses the full available budget.';
            }
        }


        if (!$isAffordable) {

            $summary .=
                ' The combination is not affordable from the outgoing players alone.';
        }


        return $summary;
    }
}