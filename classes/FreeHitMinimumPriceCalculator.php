<?php

class FreeHitMinimumPriceCalculator
{
    /*
     * ============================================================
     * CALCULATE MINIMUM REMAINING PRICE
     * ============================================================
     *
     * Public compatibility entry point for raw positional pools.
     *
     * Raw pools are normalized before the prepared calculation is
     * performed.
     */

    public function calculate(
        array $selectedPlayerIds,
        array $requiredByPosition,
        array $priceSortedSearchPools
    ): ?float {

        $preparedPools =
            $this->preparePools(
                $priceSortedSearchPools
            );


        return
            $this->calculatePrepared(
                $selectedPlayerIds,
                $requiredByPosition,
                $preparedPools
            );
    }


    /*
     * ============================================================
     * PREPARE POOLS
     * ============================================================
     *
     * Convert the cheapest-first positional search pools into the
     * minimal structure required by the repeated feasibility
     * calculation.
     *
     * Candidate validation and numeric conversion happen here once
     * rather than on every feasibility call.
     *
     * The supplied pools are already cheapest-first, so their order
     * is deliberately preserved.
     */

    public function preparePools(
        array $priceSortedSearchPools
    ): array {

        $preparedPools = [
            'GK' =>
                [],

            'DEF' =>
                [],

            'MID' =>
                [],

            'FWD' =>
                []
        ];


        foreach (
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ]
            as $position
        ) {

            if (
                !isset(
                    $priceSortedSearchPools[
                        $position
                    ]
                )
                ||
                !is_array(
                    $priceSortedSearchPools[
                        $position
                    ]
                )
            ) {

                continue;
            }


            foreach (
                $priceSortedSearchPools[
                    $position
                ]
                as $candidate
            ) {

                if (
                    !is_array(
                        $candidate
                    )
                ) {

                    continue;
                }


                $playerId =
                    $candidate[
                        'player_id'
                    ]
                    ??
                    null;


                $price =
                    $candidate[
                        'price'
                    ]
                    ??
                    null;


                if (
                    !is_numeric(
                        $playerId
                    )
                    ||
                    !is_numeric(
                        $price
                    )
                ) {

                    continue;
                }


                $playerId =
                    (int) $playerId;


                if (
                    $playerId <= 0
                ) {

                    continue;
                }


                $preparedPools[
                    $position
                ][] = [
                    'player_id' =>
                        $playerId,

                    'price' =>
                        (float) $price
                ];
            }
        }


        return
            $preparedPools;
    }


    /*
     * ============================================================
     * CALCULATE FROM PREPARED POOLS
     * ============================================================
     *
     * This is the hot-path calculation.
     *
     * Candidate structure has already been validated and numeric
     * values have already been normalized by preparePools().
     *
     * The only state-dependent operation required for each search
     * state is therefore excluding players that have already been
     * selected.
     *
     * Club-limit interactions are deliberately ignored. This makes
     * the result an optimistic lower bound, which is safe for
     * rejecting states that cannot possibly fit inside the budget.
     */

    public function calculatePrepared(
        array $selectedPlayerIds,
        array $requiredByPosition,
        array $preparedPools
    ): ?float {

        $minimumPrice =
            0.0;


        foreach (
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ]
            as $position
        ) {

            $requiredCount =
                $requiredByPosition[
                    $position
                ]
                ??
                0;


            if (
                !is_numeric(
                    $requiredCount
                )
            ) {

                return
                    null;
            }


            $requiredCount =
                (int) $requiredCount;


            if (
                $requiredCount < 0
            ) {

                return
                    null;
            }


            if (
                $requiredCount === 0
            ) {

                continue;
            }


            if (
                !isset(
                    $preparedPools[
                        $position
                    ]
                )
                ||
                !is_array(
                    $preparedPools[
                        $position
                    ]
                )
            ) {

                return
                    null;
            }


            $foundCount =
                0;


            foreach (
                $preparedPools[
                    $position
                ]
                as $candidate
            ) {

                /*
                 * Prepared candidates have already been validated.
                 *
                 * No is_array(), is_numeric(), casts or unrelated
                 * player fields are required in this hot loop.
                 */

                $playerId =
                    $candidate[
                        'player_id'
                    ];


                if (
                    isset(
                        $selectedPlayerIds[
                            $playerId
                        ]
                    )
                ) {

                    continue;
                }


                $minimumPrice +=
                    $candidate[
                        'price'
                    ];


                $foundCount++;


                if (
                    $foundCount
                    >=
                    $requiredCount
                ) {

                    break;
                }
            }


            if (
                $foundCount
                <
                $requiredCount
            ) {

                return
                    null;
            }
        }


        return
            $minimumPrice;
    }
}