<?php


class TransferCombinationRanker
{

    /**
     * Rank transfer combinations and retain only the strongest
     * requested number of results.
     *
     * This preserves the existing TransferOptimizer ranking
     * contract without requiring the complete candidate
     * collection to be fully sorted.
     */
    public function rank(
        array $combinations,
        int $limit
    ): array {

        if (
            $limit <= 0
            ||
            $combinations === []
        ) {

            return [];
        }


        /*
         * If the complete collection fits inside the requested
         * limit, simply perform the authoritative sort.
         */
        if (
            count($combinations)
            <=
            $limit
        ) {

            usort(
                $combinations,
                function (
                    array $a,
                    array $b
                ): int {

                    return
                        $this->compare(
                            $a,
                            $b
                        );
                }
            );


            return $combinations;
        }


        /*
         * ====================================================
         * BOUNDED TOP-K COLLECTION
         * ====================================================
         *
         * Keep at most $limit combinations.
         *
         * The retained collection is maintained in authoritative
         * ranking order. The final element is therefore always
         * the weakest currently retained combination.
         *
         * A new combination only needs to enter the collection
         * when it outranks that weakest result.
         */
        $ranked =
            [];


        foreach (
            $combinations
            as $combination
        ) {

            if (
                !is_array(
                    $combination
                )
            ) {

                continue;
            }


            /*
             * Fill the initial bounded collection.
             */
            if (
                count($ranked)
                <
                $limit
            ) {

                $ranked[] =
                    $combination;


                usort(
                    $ranked,
                    function (
                        array $a,
                        array $b
                    ): int {

                        return
                            $this->compare(
                                $a,
                                $b
                            );
                    }
                );


                continue;
            }


            $weakestIndex =
                count($ranked)
                -
                1;


            /*
             * Comparator < 0 means the new combination belongs
             * ahead of the weakest retained combination.
             */
            if (
                $this->compare(
                    $combination,
                    $ranked[
                        $weakestIndex
                    ]
                )
                >=
                0
            ) {

                continue;
            }


            $ranked[
                $weakestIndex
            ] =
                $combination;


            usort(
                $ranked,
                function (
                    array $a,
                    array $b
                ): int {

                    return
                        $this->compare(
                            $a,
                            $b
                        );
                }
            );
        }


        return $ranked;
    }


    /**
     * Existing TransferOptimizer ranking contract.
     *
     * Priority:
     *
     * 1. Classification
     * 2. Combination score
     * 3. Intelligence movement
     * 4. Remaining budget
     */
    private function compare(
        array $a,
        array $b
    ): int {

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

            return
                $classificationB
                <=>
                $classificationA;
        }


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

            return
                $scoreB
                <=>
                $scoreA;
        }


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

            return
                $intelligenceB
                <=>
                $intelligenceA;
        }


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


        return
            $budgetB
            <=>
            $budgetA;
    }


    /**
     * Convert a combination classification into its existing
     * TransferOptimizer ranking priority.
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
     * Safely convert optional numeric ranking values.
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


        return
            (float) $value;
    }
}