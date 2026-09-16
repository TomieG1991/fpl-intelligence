<?php

class FreeHitDescriptorRanker
{
    /*
     * ============================================================
     * SELECT TOP DESCRIPTORS
     * ============================================================
     *
     * Return the strongest descriptors using the exact ordering
     * contract currently used by FreeHitOptimizer:
     *
     * 1. projected points descending;
     * 2. price ascending;
     * 3. key ascending.
     *
     * When more descriptors exist than the requested limit, a
     * bounded insertion structure is maintained so the complete
     * descriptor collection does not need to be fully sorted.
     *
     * The final retained descriptors are sorted using the same
     * comparator so callers receive exactly ordered top-K output.
     */

    public function selectTop(
        array $descriptors,
        int $limit
    ): array {

        if (
            $limit <= 0
            ||
            empty(
                $descriptors
            )
        ) {

            return
                [];
        }


        /*
         * Small collections do not need bounded selection.
         *
         * Sorting the complete collection here also guarantees
         * identical ordering when every descriptor survives.
         */

        if (
            count(
                $descriptors
            )
            <=
            $limit
        ) {

            usort(
                $descriptors,
                [
                    $this,
                    'compare'
                ]
            );


            return
                $descriptors;
        }


        /*
         * ============================================================
         * BOUNDED TOP-K SELECTION
         * ============================================================
         *
         * Keep the retained descriptors ordered strongest-to-weakest.
         *
         * Once the structure reaches the requested limit, descriptors
         * weaker than or equal to the current final survivor can be
         * rejected without sorting the complete input collection.
         */

        $selected =
            [];


        foreach (
            $descriptors
            as $descriptor
        ) {

            $selectedCount =
                count(
                    $selected
                );


            /*
             * Once full, compare against the weakest retained
             * descriptor first.
             */

            if (
                $selectedCount
                >=
                $limit
            ) {

                $weakest =
                    $selected[
                        $selectedCount
                        -
                        1
                    ];


                if (
                    $this->compare(
                        $descriptor,
                        $weakest
                    )
                    >=
                    0
                ) {

                    continue;
                }
            }


            /*
             * Find the descriptor's exact insertion position using
             * the same comparator as the original full sort.
             */

            $low =
                0;


            $high =
                $selectedCount;


            while (
                $low
                <
                $high
            ) {

                $middle =
                    intdiv(
                        $low
                        +
                        $high,
                        2
                    );


                if (
                    $this->compare(
                        $descriptor,
                        $selected[
                            $middle
                        ]
                    )
                    <
                    0
                ) {

                    $high =
                        $middle;

                } else {

                    $low =
                        $middle
                        +
                        1;
                }
            }


            array_splice(
                $selected,
                $low,
                0,
                [
                    $descriptor
                ]
            );


            /*
             * Discard the weakest descriptor immediately when the
             * bounded collection exceeds the requested limit.
             */

            if (
                count(
                    $selected
                )
                >
                $limit
            ) {

                array_pop(
                    $selected
                );
            }
        }


        return
            $selected;
    }


    /*
     * ============================================================
     * DESCRIPTOR COMPARATOR
     * ============================================================
     *
     * This intentionally mirrors the existing FreeHitOptimizer
     * descriptor comparator exactly.
     */

    private function compare(
        array $a,
        array $b
    ): int {

        $pointsA =
            (float) $a[
                'projected_points'
            ];


        $pointsB =
            (float) $b[
                'projected_points'
            ];


        if (
            $pointsA
            !==
            $pointsB
        ) {

            return
                $pointsB
                <=>
                $pointsA;
        }


        $priceA =
            (float) $a[
                'price'
            ];


        $priceB =
            (float) $b[
                'price'
            ];


        if (
            $priceA
            !==
            $priceB
        ) {

            return
                $priceA
                <=>
                $priceB;
        }


        return
            strcmp(
                (string) $a[
                    'key'
                ],
                (string) $b[
                    'key'
                ]
            );
    }
}