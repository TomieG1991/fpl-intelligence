<?php

class FreeHitDescriptorRanker
{
    /*
     * ============================================================
     * SELECT TOP DESCRIPTORS
     * ============================================================
     *
     * Large Free Hit search ordering:
     *
     * 1. projected points descending;
     * 2. price ascending;
     * 3. key ascending.
     */

    public function selectTop(
        array $descriptors,
        int $limit
    ): array {

        return
            $this->selectTopWithComparator(
                $descriptors,
                $limit,
                [
                    $this,
                    'compare'
                ]
            );
    }


    /*
     * ============================================================
     * SELECT TOP STARTING-XI DESCRIPTORS
     * ============================================================
     *
     * Small Free Hit search ordering:
     *
     * 1. Starting XI projected points descending;
     * 2. price ascending;
     * 3. beam key ascending.
     *
     * This mirrors the existing small-path full-sort comparator
     * exactly while allowing bounded top-K selection.
     */

    public function selectTopStartingXI(
        array $descriptors,
        int $limit
    ): array {

        return
            $this->selectTopWithComparator(
                $descriptors,
                $limit,
                [
                    $this,
                    'compareStartingXI'
                ]
            );
    }


    /*
     * ============================================================
     * BOUNDED TOP-K SELECTION
     * ============================================================
     *
     * Keep only the strongest descriptors required by the caller.
     *
     * The retained collection remains ordered strongest-to-weakest.
     * Once the requested limit is reached, descriptors weaker than
     * or equal to the current final survivor can be rejected
     * immediately.
     */

    private function selectTopWithComparator(
        array $descriptors,
        int $limit,
        callable $comparator
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
         * If every descriptor survives, use the ordinary complete
         * sort. This preserves the exact established ordering while
         * avoiding unnecessary bounded-selection overhead.
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
                $comparator
            );


            return
                $descriptors;
        }


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
             * descriptor before performing the insertion search.
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
                    $comparator(
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
             * Binary-search the exact insertion position using the
             * same comparator that defines the original full-sort
             * contract.
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
                    $comparator(
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
     * LARGE-PATH DESCRIPTOR COMPARATOR
     * ============================================================
     *
     * Preserve the existing large Free Hit search contract exactly.
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


    /*
     * ============================================================
     * SMALL-PATH DESCRIPTOR COMPARATOR
     * ============================================================
     *
     * Preserve the existing searchFreeHitFormation() ordering
     * exactly:
     *
     * 1. starting_points descending;
     * 2. price ascending;
     * 3. beam_key ascending.
     */

    private function compareStartingXI(
        array $a,
        array $b
    ): int {

        $pointsComparison =
            (
                (float) (
                    $b[
                        'starting_points'
                    ]
                    ??
                    0.0
                )
            )
            <=>
            (
                (float) (
                    $a[
                        'starting_points'
                    ]
                    ??
                    0.0
                )
            );


        if (
            $pointsComparison
            !==
            0
        ) {

            return
                $pointsComparison;
        }


        $priceComparison =
            (
                (float) (
                    $a[
                        'price'
                    ]
                    ??
                    0.0
                )
            )
            <=>
            (
                (float) (
                    $b[
                        'price'
                    ]
                    ??
                    0.0
                )
            );


        if (
            $priceComparison
            !==
            0
        ) {

            return
                $priceComparison;
        }


        return
            strcmp(
                (string) (
                    $a[
                        'beam_key'
                    ]
                    ??
                    ''
                ),
                (string) (
                    $b[
                        'beam_key'
                    ]
                    ??
                    ''
                )
            );
    }
}