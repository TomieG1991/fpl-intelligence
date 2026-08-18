<?php


class TransferOptimizer
{

    private TransferCombination $transferCombination;


    public function __construct()
    {

        $this->transferCombination =
            new TransferCombination();
    }


    /**
     * Search candidate replacement pairs and return the
     * strongest affordable transfer combinations.
     */
    public function optimize(
        array $currentPlayerA,
        array $currentPlayerB,
        array $candidatePoolA,
        array $candidatePoolB,
        float $bank = 0.0,
        int $limit = 10
    ): array {

        /*
         * ====================================================
         * BASIC VALIDATION
         * ====================================================
         */

        if (
            $bank < 0
            ||
            $limit <= 0
        ) {

            return $this->emptyResult(
                $bank,
                $limit
            );
        }


        $currentPlayerIdA =
            $this->playerId(
                $currentPlayerA
            );


        $currentPlayerIdB =
            $this->playerId(
                $currentPlayerB
            );


        if (
            $currentPlayerIdA <= 0
            ||
            $currentPlayerIdB <= 0
            ||
            $currentPlayerIdA === $currentPlayerIdB
        ) {

            return $this->emptyResult(
                $bank,
                $limit
            );
        }


        $currentPositionA =
            $this->position(
                $currentPlayerA
            );


        $currentPositionB =
            $this->position(
                $currentPlayerB
            );


        if (
            $currentPositionA === null
            ||
            $currentPositionB === null
        ) {

            return $this->emptyResult(
                $bank,
                $limit
            );
        }


        /*
         * ====================================================
         * SEARCH COMBINATIONS
         * ====================================================
         */

        $combinations = [];


        foreach (
            $candidatePoolA
            as $candidateA
        ) {

            if (
                !$this->validCandidate(
                    $candidateA,
                    $currentPositionA
                )
            ) {

                continue;
            }


            $candidateIdA =
                $this->playerId(
                    $candidateA
                );


            /*
             * Cannot replace a player with themselves.
             */

            if (
                $candidateIdA
                ===
                $currentPlayerIdA
            ) {

                continue;
            }


            /*
             * Do not allow the other outgoing player to become
             * the incoming player.
             */

            if (
                $candidateIdA
                ===
                $currentPlayerIdB
            ) {

                continue;
            }


            foreach (
                $candidatePoolB
                as $candidateB
            ) {

                if (
                    !$this->validCandidate(
                        $candidateB,
                        $currentPositionB
                    )
                ) {

                    continue;
                }


                $candidateIdB =
                    $this->playerId(
                        $candidateB
                    );


                /*
                 * Cannot replace a player with themselves.
                 */

                if (
                    $candidateIdB
                    ===
                    $currentPlayerIdB
                ) {

                    continue;
                }


                /*
                 * Do not allow the other outgoing player to
                 * become the incoming player.
                 */

                if (
                    $candidateIdB
                    ===
                    $currentPlayerIdA
                ) {

                    continue;
                }


                /*
                 * The same player cannot occupy both incoming
                 * slots.
                 */

                if (
                    $candidateIdA
                    ===
                    $candidateIdB
                ) {

                    continue;
                }


                /*
                 * =================================================
                 * EVALUATE COMBINATION
                 * =================================================
                 */

                $combination =
                    $this->transferCombination
                        ->evaluateCombination(
                            $currentPlayerA,
                            $candidateA,
                            $currentPlayerB,
                            $candidateB
                        );


                if (
                    !is_array(
                        $combination
                    )
                ) {

                    continue;
                }


                /*
                 * TransferCombination calculates affordability
                 * assuming no additional bank.
                 *
                 * The optimizer can have money already in the
                 * bank, so affordability is recalculated here.
                 */

                $combinationBudget =
                    $combination[
                        'combined_movements'
                    ]['budget']
                    ?? null;


                if (
                    $combinationBudget === null
                    ||
                    !is_numeric(
                        $combinationBudget
                    )
                ) {

                    continue;
                }


                $budgetAfterTransfers =
                    round(
                        (float) $combinationBudget
                        +
                        $bank,
                        2
                    );


                /*
                 * We only rank affordable strategies.
                 */

                if (
                    $budgetAfterTransfers
                    <
                    0
                ) {

                    continue;
                }


                /*
                 * Store optimizer-specific information without
                 * changing TransferCombination itself.
                 */

                $combination[
                    'optimizer'
                ] = [

                    'bank_before' =>
                        round(
                            $bank,
                            2
                        ),

                    'budget_after' =>
                        $budgetAfterTransfers,

                    'is_affordable' =>
                        true
                ];


                $combinations[] =
                    $combination;
            }
        }


        /*
         * ====================================================
         * RANK COMBINATIONS
         * ====================================================
         */

        usort(
            $combinations,
            function (
                array $a,
                array $b
            ): int {

                return $this->compareCombinations(
                    $a,
                    $b
                );
            }
        );


        /*
         * ====================================================
         * LIMIT RESULTS
         * ====================================================
         */

        $totalFound =
            count(
                $combinations
            );


        $ranked =
            array_slice(
                $combinations,
                0,
                $limit
            );


        foreach (
            $ranked
            as $index => &$combination
        ) {

            $combination[
                'optimizer'
            ]['rank'] =
                $index + 1;
        }


        unset(
            $combination
        );


        return [

            'current_player_a' =>
                $currentPlayerA,

            'current_player_b' =>
                $currentPlayerB,

            'bank' =>
                round(
                    $bank,
                    2
                ),

            'limit' =>
                $limit,

            'total_found' =>
                $totalFound,

            'count' =>
                count(
                    $ranked
                ),

            'combinations' =>
                $ranked
        ];
    }


    /**
     * Compare two combinations for ranking.
     *
     * Priority:
     *
     * 1. Classification
     * 2. Combination score
     * 3. Intelligence movement
     * 4. Remaining budget
     */
    private function compareCombinations(
        array $a,
        array $b
    ): int {

        /*
         * ====================================================
         * CLASSIFICATION
         * ====================================================
         */

        $classificationA =
            $this->classificationWeight(
                $a[
                    'classification'
                ]
                ?? null
            );


        $classificationB =
            $this->classificationWeight(
                $b[
                    'classification'
                ]
                ?? null
            );


        if (
            $classificationA
            !==
            $classificationB
        ) {

            return $classificationB
                <=>
                $classificationA;
        }


        /*
         * ====================================================
         * COMBINATION SCORE
         * ====================================================
         */

        $scoreA =
            $this->numericValue(
                $a[
                    'combination_score'
                ]
                ?? null
            );


        $scoreB =
            $this->numericValue(
                $b[
                    'combination_score'
                ]
                ?? null
            );


        if (
            $scoreA
            !==
            $scoreB
        ) {

            return $scoreB
                <=>
                $scoreA;
        }


        /*
         * ====================================================
         * INTELLIGENCE MOVEMENT
         * ====================================================
         */

        $intelligenceA =
            $this->numericValue(
                $a[
                    'combined_movements'
                ]['intelligence']
                ?? null
            );


        $intelligenceB =
            $this->numericValue(
                $b[
                    'combined_movements'
                ]['intelligence']
                ?? null
            );


        if (
            $intelligenceA
            !==
            $intelligenceB
        ) {

            return $intelligenceB
                <=>
                $intelligenceA;
        }


        /*
         * ====================================================
         * REMAINING BUDGET
         * ====================================================
         */

        $budgetA =
            $this->numericValue(
                $a[
                    'optimizer'
                ]['budget_after']
                ?? null
            );


        $budgetB =
            $this->numericValue(
                $b[
                    'optimizer'
                ]['budget_after']
                ?? null
            );


        return $budgetB
            <=>
            $budgetA;
    }


    /**
     * Convert a combination classification into a ranking
     * priority.
     */
    private function classificationWeight(
        mixed $classification
    ): int {

        return match (
            strtolower(
                trim(
                    (string) $classification
                )
            )
        ) {

            'strong improvement' =>
                6,

            'improvement' =>
                5,

            'balanced restructure' =>
                4,

            'neutral restructure' =>
                3,

            'risky restructure' =>
                2,

            'downgrade' =>
                1,

            'unaffordable' =>
                0,

            'insufficient data' =>
                -1,

            default =>
                0
        };
    }


    /**
     * Determine whether a candidate has the minimum data
     * required by the optimizer.
     */
    private function validCandidate(
        mixed $candidate,
        string $requiredPosition
    ): bool {

        if (
            !is_array(
                $candidate
            )
        ) {

            return false;
        }


        $playerId =
            $this->playerId(
                $candidate
            );


        $position =
            $this->position(
                $candidate
            );


        if (
            $playerId <= 0
            ||
            $position === null
        ) {

            return false;
        }


        return $position
            ===
            $requiredPosition;
    }


    /**
     * Extract player ID.
     */
    private function playerId(
        array $player
    ): int {

        return (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );
    }


    /**
     * Extract player position.
     */
    private function position(
        array $player
    ): ?string {

        $position =
            $player[
                'position'
            ]
            ?? null;


        if (
            !is_string(
                $position
            )
            ||
            trim(
                $position
            )
            === ''
        ) {

            return null;
        }


        return strtoupper(
            trim(
                $position
            )
        );
    }


    /**
     * Safely convert optional numeric values for ranking.
     */
    private function numericValue(
        mixed $value
    ): float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return -999999.0;
        }


        return (float) $value;
    }


    /**
     * Return a predictable empty optimizer structure.
     */
    private function emptyResult(
        float $bank,
        int $limit
    ): array {

        return [

            'current_player_a' =>
                null,

            'current_player_b' =>
                null,

            'bank' =>
                round(
                    $bank,
                    2
                ),

            'limit' =>
                $limit,

            'total_found' =>
                0,

            'count' =>
                0,

            'combinations' =>
                []
        ];
    }
}