<?php

class TransferDecision
{
    /**
     * Evaluate a transfer from one player to another.
     */
    public function evaluateTransfer(
        array $currentPlayer,
        array $replacement
    ): array {

        $currentIntelligence =
            $this->normaliseRating(
                $currentPlayer[
                    'intelligence_score'
                ]
                ?? null
            );


        $replacementIntelligence =
            $this->normaliseRating(
                $replacement[
                    'intelligence_score'
                ]
                ?? null
            );


        $currentStrength =
            $this->normaliseRating(
                $currentPlayer[
                    'strength_rating'
                ]
                ?? null
            );


        $replacementStrength =
            $this->normaliseRating(
                $replacement[
                    'strength_rating'
                ]
                ?? null
            );


        $currentValue =
            $this->normaliseRating(
                $currentPlayer[
                    'value_rating'
                ]
                ?? null
            );


        $replacementValue =
            $this->normaliseRating(
                $replacement[
                    'value_rating'
                ]
                ?? null
            );


        $currentFixtures =
            $this->normaliseRating(
                $currentPlayer[
                    'fixture_rating'
                ]
                ?? null
            );


        $replacementFixtures =
            $this->normaliseRating(
                $replacement[
                    'fixture_rating'
                ]
                ?? null
            );


        $currentConfidence =
            $this->normaliseConfidence(
                $currentPlayer[
                    'sample_confidence'
                ]
                ?? null
            );


        $replacementConfidence =
            $this->normaliseConfidence(
                $replacement[
                    'sample_confidence'
                ]
                ?? null
            );


        $currentPrice =
            $this->normaliseNumber(
                $currentPlayer[
                    'price'
                ]
                ?? null
            );


        $replacementPrice =
            $this->normaliseNumber(
                $replacement[
                    'price'
                ]
                ?? null
            );


        /*
         * ====================================================
         * MOVEMENTS
         * ====================================================
         */

        $intelligenceMovement =
            $this->calculateMovement(
                $currentIntelligence,
                $replacementIntelligence
            );


        $strengthMovement =
            $this->calculateMovement(
                $currentStrength,
                $replacementStrength
            );


        $valueMovement =
            $this->calculateMovement(
                $currentValue,
                $replacementValue
            );


        $fixtureMovement =
            $this->calculateMovement(
                $currentFixtures,
                $replacementFixtures
            );


        $confidenceMovement =
            (
                $currentConfidence !== null
                &&
                $replacementConfidence !== null
            )
                ? round(
                    (
                        $replacementConfidence
                        -
                        $currentConfidence
                    )
                    * 100,
                    2
                )
                : null;


        /*
         * Positive = money released.
         * Negative = extra money required.
         */
        $budgetMovement =
            (
                $currentPrice !== null
                &&
                $replacementPrice !== null
            )
                ? round(
                    $currentPrice
                    -
                    $replacementPrice,
                    2
                )
                : null;


        /*
         * ====================================================
         * DECISION SCORE
         * ====================================================
         *
         * Transfer quality weights:
         *
         * 40% Intelligence movement
         * 20% Fixture movement
         * 15% Value movement
         * 10% Strength movement
         * 10% Budget benefit
         *  5% Confidence movement
         *
         * Movement components are converted into bounded
         * 0-100 contribution scores.
         */

        $decisionScore =
            $this->calculateDecisionScore(
                $intelligenceMovement,
                $fixtureMovement,
                $valueMovement,
                $strengthMovement,
                $budgetMovement,
                $confidenceMovement
            );


        $decisionType =
            $this->classifyTransfer(
                $decisionScore,
                $intelligenceMovement,
                $budgetMovement,
                $replacementConfidence
            );


        return [

            'current_player' =>
                $this->buildPlayerIdentity(
                    $currentPlayer
                ),

            'replacement' =>
                $this->buildPlayerIdentity(
                    $replacement
                ),

            'movements' => [

                'intelligence' =>
                    $intelligenceMovement,

                'strength' =>
                    $strengthMovement,

                'value' =>
                    $valueMovement,

                'fixtures' =>
                    $fixtureMovement,

                'sample_confidence' =>
                    $confidenceMovement,

                'budget' =>
                    $budgetMovement
            ],

            'decision_score' =>
                $decisionScore,

            'decision_type' =>
                $decisionType,

            'summary' =>
                $this->buildSummary(
                    $currentPlayer,
                    $replacement,
                    $decisionType,
                    $intelligenceMovement,
                    $budgetMovement,
                    $fixtureMovement
                )
        ];
    }


    /**
     * Calculate weighted transfer quality.
     */
    private function calculateDecisionScore(
        ?float $intelligenceMovement,
        ?float $fixtureMovement,
        ?float $valueMovement,
        ?float $strengthMovement,
        ?float $budgetMovement,
        ?float $confidenceMovement
    ): ?float {

        $components =
            [];


        if ($intelligenceMovement !== null) {

            $components[] = [
                'score' =>
                    $this->movementToScore(
                        $intelligenceMovement,
                        10
                    ),

                'weight' =>
                    0.40
            ];
        }


        if ($fixtureMovement !== null) {

            $components[] = [
                'score' =>
                    $this->movementToScore(
                        $fixtureMovement,
                        20
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
                        30
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
                        15
                    ),

                'weight' =>
                    0.10
            ];
        }


        if ($budgetMovement !== null) {

            /*
             * £5m released ~= maximum useful budget benefit.
             */
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


        if ($confidenceMovement !== null) {

            $components[] = [
                'score' =>
                    $this->movementToScore(
                        $confidenceMovement,
                        50
                    ),

                'weight' =>
                    0.05
            ];
        }


        if (empty($components)) {
            return null;
        }


        $weightedScore =
            0;


        $weightTotal =
            0;


        foreach ($components as $component) {

            $weightedScore +=
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
            $weightedScore
            /
            $weightTotal,
            2
        );
    }


    /**
     * Convert a movement into a 0-100 score.
     *
     * Zero movement = 50.
     * Positive movement > 50.
     * Negative movement < 50.
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
     * Interpret the transfer.
     */
    private function classifyTransfer(
        ?float $decisionScore,
        ?float $intelligenceMovement,
        ?float $budgetMovement,
        ?float $replacementConfidence
    ): string {

        if ($decisionScore === null) {
            return 'Insufficient Data';
        }


        /*
         * --------------------------------------------------------
         * CLEAR UPGRADE
         * --------------------------------------------------------
         *
         * A meaningful Intelligence improvement is authoritative
         * when supported by a reasonable performance sample.
         */

        if (
            $intelligenceMovement !== null
            &&
            $intelligenceMovement >= 2
            &&
            (
                $replacementConfidence === null
                ||
                $replacementConfidence >= 0.25
            )
        ) {
            return 'Upgrade';
        }


        /*
         * --------------------------------------------------------
         * RISKY PUNT
         * --------------------------------------------------------
         *
         * Extremely low-confidence incoming players are explicitly
         * flagged before strategic classifications are considered.
         */

        if (
            $replacementConfidence !== null
            &&
            $replacementConfidence < 0.25
        ) {
            return 'Risky Punt';
        }


        /*
         * --------------------------------------------------------
         * MATERIAL PLAYER DOWNGRADE
         * --------------------------------------------------------
         *
         * A loss of 3 or more Intelligence points represents a
         * meaningful reduction in the quality of the individual
         * FPL slot.
         *
         * Budget released may still make the wider squad strategy
         * attractive, but that should eventually be assessed by
         * Squad Intelligence rather than hiding the direct loss.
         */

        if (
            $intelligenceMovement !== null
            &&
            $intelligenceMovement <= -3
        ) {
            return 'Downgrade';
        }


        /*
         * --------------------------------------------------------
         * BUDGET ENABLER
         * --------------------------------------------------------
         *
         * A small sacrifice in player quality can be worthwhile
         * when meaningful budget is released and the wider transfer
         * profile remains positive.
         */

        if (
            $decisionScore >= 52.0
            &&
            $budgetMovement !== null
            &&
            $budgetMovement >= 2
            &&
            (
                $intelligenceMovement === null
                ||
                $intelligenceMovement > -3
            )
        ) {
            return 'Budget Enabler';
        }


        /*
         * --------------------------------------------------------
         * STRATEGIC SIDEGRADE
         * --------------------------------------------------------
         */

        if ($decisionScore >= 60) {
            return 'Strategic Sidegrade';
        }


        /*
         * --------------------------------------------------------
         * STANDARD SIDEGRADE
         * --------------------------------------------------------
         */

        if ($decisionScore >= 45) {
            return 'Sidegrade';
        }


        /*
         * --------------------------------------------------------
         * DOWNGRADE
         * --------------------------------------------------------
         */

        return 'Downgrade';
    }


    /**
     * Build a readable transfer summary.
     */
    private function buildSummary(
        array $currentPlayer,
        array $replacement,
        string $decisionType,
        ?float $intelligenceMovement,
        ?float $budgetMovement,
        ?float $fixtureMovement
    ): string {

        $currentName =
            (string) (
                $currentPlayer['name']
                ?? 'Current player'
            );


        $replacementName =
            (string) (
                $replacement['name']
                ?? 'Replacement'
            );


        $summary =
            $currentName
            . ' → '
            . $replacementName
            . ' is classified as '
            . strtolower(
                $decisionType
            )
            . '.';


        if ($intelligenceMovement !== null) {

            $summary .=
                ' Intelligence '
                . (
                    $intelligenceMovement >= 0
                        ? 'improves by '
                        : 'falls by '
                )
                . number_format(
                    abs(
                        $intelligenceMovement
                    ),
                    1
                )
                . ' points.';
        }


        if ($budgetMovement !== null) {

            if ($budgetMovement > 0) {

                $summary .=
                    ' The move releases £'
                    . number_format(
                        $budgetMovement,
                        1
                    )
                    . 'm.';

            } elseif ($budgetMovement < 0) {

                $summary .=
                    ' The move requires an additional £'
                    . number_format(
                        abs(
                            $budgetMovement
                        ),
                        1
                    )
                    . 'm.';
            }
        }


        if ($fixtureMovement !== null) {

            if ($fixtureMovement >= 2) {

                $summary .=
                    ' Fixture opportunity improves.';

            } elseif ($fixtureMovement <= -2) {

                $summary .=
                    ' Fixture opportunity worsens.';
            }
        }


        return $summary;
    }


    /**
     * Build a small player identity block.
     */
    private function buildPlayerIdentity(
        array $player
    ): array {

        return [

            'player_id' =>
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                ),

            'name' =>
                $player[
                    'name'
                ]
                ?? null,

            'position' =>
                $player[
                    'position'
                ]
                ?? null,

            'team_name' =>
                $player[
                    'team_name'
                ]
                ?? null,

            'price' =>
                $this->normaliseNumber(
                    $player[
                        'price'
                    ]
                    ?? null
                ),

            'intelligence_score' =>
                $this->normaliseRating(
                    $player[
                        'intelligence_score'
                    ]
                    ?? null
                ),

            'verdict' =>
                $player[
                    'verdict'
                ]
                ?? null
        ];
    }


    /**
     * Calculate replacement - current movement.
     */
    private function calculateMovement(
        ?float $current,
        ?float $replacement
    ): ?float {

        if (
            $current === null
            ||
            $replacement === null
        ) {
            return null;
        }


        return round(
            $replacement
            -
            $current,
            2
        );
    }


    private function normaliseRating(
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


        return round(
            max(
                0,
                min(
                    100,
                    (float) $value
                )
            ),
            2
        );
    }


    private function normaliseConfidence(
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


        return round(
            max(
                0,
                min(
                    1,
                    (float) $value
                )
            ),
            4
        );
    }


    private function normaliseNumber(
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


        return round(
            (float) $value,
            2
        );
    }
}