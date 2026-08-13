<?php

class TransferTargetFinder
{
    /**
     * Convert a player model into the flat
     * decision-friendly structure expected by
     * the transfer target finder.
     *
     * Supports:
     *
     * 1. New PlayerIntelligenceEngine profiles:
     *
     *    [
     *        'summary' => [
     *            'player_id' => ...,
     *            'position' => ...,
     *            'price' => ...,
     *            'intelligence_score' => ...,
     *            ...
     *        ]
     *    ]
     *
     * 2. Existing flat player intelligence arrays.
     */
    private function normalisePlayer(
        array $player
    ): array {

        if (
            isset($player['summary'])
            &&
            is_array($player['summary'])
        ) {

            return $player['summary'];
        }


        return $player;
    }


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
     *
     * Supports both PlayerIntelligenceEngine profiles
     * and existing flat player intelligence arrays.
     */
    public function findTargets(
        array $currentPlayer,
        array $players,
        ?float $maximumPrice = null
    ): array {

        $targets = [];


        /*
         * ----------------------------------------------------
         * Normalise current player.
         * ----------------------------------------------------
         */

        $currentPlayer =
            $this->normalisePlayer(
                $currentPlayer
            );


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
             * Normalise candidate player.
             * ------------------------------------------------
             */

            $player =
                $this->normalisePlayer(
                    $player
                );


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
                !array_key_exists(
                    'intelligence_score',
                    $player
                )
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
             * Merge candidate information with transfer
             * recommendation information.
             *
             * Existing player fields are preserved and
             * transfer-specific information is added.
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
                        $scoreB
                        <=>
                        $scoreA;
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