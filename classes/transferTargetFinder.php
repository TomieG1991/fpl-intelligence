<?php

class TransferTargetFinder
{
    /**
     * Find suitable transfer targets for a current player.
     *
     * Candidates must:
     *
     * - Be a different player
     * - Play the same position
     * - Have an intelligence score
     * - Have a usable availability rating
     * - Be within the supplied budget
     *
     * Each candidate is passed through the existing
     * TransferRecommendation model.
     */
    public function findTargets(
        array $currentPlayer,
        array $players,
        ?float $maximumPrice = null
    ): array {

        $targets = [];


        /*
         * Use the current player's price as the default
         * maximum budget when no budget is supplied.
         */
        if ($maximumPrice === null) {

            $maximumPrice =
                isset($currentPlayer['price'])
                    ? (float) $currentPlayer['price']
                    : null;
        }


        $transferRecommendation =
            new TransferRecommendation();


        foreach ($players as $player) {

            /*
             * ------------------------------------------------
             * Exclude the current player
             * ------------------------------------------------
             */

            if (
                isset($currentPlayer['player_id'])
                &&
                isset($player['player_id'])
                &&
                (int) $player['player_id']
                    ===
                (int) $currentPlayer['player_id']
            ) {

                continue;
            }


            /*
             * ------------------------------------------------
             * Position filter
             * ------------------------------------------------
             */

            if (
                isset($currentPlayer['position'])
                &&
                isset($player['position'])
                &&
                $player['position']
                    !==
                $currentPlayer['position']
            ) {

                continue;
            }


            /*
             * ------------------------------------------------
             * Intelligence score is required
             * ------------------------------------------------
             */

            if (
                !isset($player['intelligence_score'])
                ||
                $player['intelligence_score'] === null
            ) {

                continue;
            }


            /*
             * ------------------------------------------------
             * Availability filter
             *
             * A player with a zero availability rating
             * is not considered a realistic target.
             * ------------------------------------------------
             */

            if (
                isset($player['availability_rating'])
                &&
                $player['availability_rating'] !== null
                &&
                (float) $player['availability_rating'] <= 0
            ) {

                continue;
            }


            /*
             * ------------------------------------------------
             * Budget filter
             * ------------------------------------------------
             */

            if (
                $maximumPrice !== null
                &&
                isset($player['price'])
                &&
                $player['price'] !== null
                &&
                (float) $player['price']
                    >
                $maximumPrice
            ) {

                continue;
            }


            /*
             * ------------------------------------------------
             * Generate transfer recommendation
             * ------------------------------------------------
             */

            $recommendation =
                $transferRecommendation->buildRecommendation(
                    $currentPlayer,
                    $player
                );


            /*
             * ------------------------------------------------
             * Merge original player information with
             * transfer recommendation information.
             *
             * The recommendation fields are added rather
             * than replacing the original player data.
             * ------------------------------------------------
             */

            $target =
                array_merge(
                    $player,
                    [

                        'intelligence_difference' =>
                            $recommendation[
                                'intelligence_difference'
                            ],

                        'strength_difference' =>
                            $recommendation[
                                'strength_difference'
                            ],

                        'value_difference' =>
                            $recommendation[
                                'value_difference'
                            ],

                        'availability_difference' =>
                            $recommendation[
                                'availability_difference'
                            ],

                        'fixture_difference' =>
                            $recommendation[
                                'fixture_difference'
                            ],

                        'transfer_score' =>
                            $recommendation[
                                'transfer_score'
                            ],

                        'recommendation' =>
                            $recommendation[
                                'recommendation'
                            ],

                        'reason' =>
                            $recommendation[
                                'reason'
                            ]
                    ]
                );


            $targets[] =
                $target;
        }


        /*
         * ----------------------------------------------------
         * Rank targets by transfer score.
         *
         * Secondary tie-breaker:
         * higher intelligence score wins.
         * ----------------------------------------------------
         */

        usort(
            $targets,
            function (
                array $a,
                array $b
            ): int {

                $scoreA =
                    isset($a['transfer_score'])
                        ? (float) $a['transfer_score']
                        : -INF;

                $scoreB =
                    isset($b['transfer_score'])
                        ? (float) $b['transfer_score']
                        : -INF;


                if ($scoreA !== $scoreB) {

                    return
                        $scoreB <=> $scoreA;
                }


                $intelligenceA =
                    isset($a['intelligence_score'])
                        ? (float) $a['intelligence_score']
                        : -INF;

                $intelligenceB =
                    isset($b['intelligence_score'])
                        ? (float) $b['intelligence_score']
                        : -INF;


                return
                    $intelligenceB
                    <=>
                    $intelligenceA;
            }
        );


        return $targets;
    }


    /**
     * Return the top N transfer targets.
     *
     * Invalid or zero limits return an empty array.
     */
    public function getTopTargets(
        array $targets,
        int $limit
    ): array {

        if ($limit <= 0) {
            return [];
        }


        return array_slice(
            $targets,
            0,
            $limit
        );
    }
}