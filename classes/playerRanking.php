<?php

class PlayerRanking
{
    /**
     * Rank a collection of player intelligence models.
     *
     * Players are ranked by intelligence score,
     * highest score first.
     */
    public function rankPlayers(
        array $players
    ): array {

        /*
         * Remove players that do not have
         * a usable intelligence score.
         */
        $rankablePlayers = array_filter(
            $players,
            function ($player) {

                return isset(
                    $player['intelligence_score']
                )
                &&
                $player['intelligence_score'] !== null;
            }
        );


        /*
         * Sort highest intelligence score first.
         *
         * If two players have the same score,
         * strength rating is used as the
         * secondary ranking factor.
         */
        usort(
            $rankablePlayers,
            function ($a, $b) {

                $scoreComparison =
                    $b['intelligence_score']
                    <=>
                    $a['intelligence_score'];


                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }


                $strengthA =
                    $a['strength_rating']
                    ?? 0;

                $strengthB =
                    $b['strength_rating']
                    ?? 0;


                return
                    $strengthB
                    <=>
                    $strengthA;
            }
        );


        /*
         * Add ranking position.
         */
        $rank = 1;


        foreach (
            $rankablePlayers
            as &$player
        ) {

            $player['rank'] = $rank;

            $rank++;
        }


        unset($player);


        return array_values(
            $rankablePlayers
        );
    }


    /**
     * Return the top N ranked players.
     */
    public function getTopPlayers(
        array $players,
        int $limit = 10
    ): array {

        if ($limit <= 0) {
            return [];
        }


        $rankedPlayers =
            $this->rankPlayers(
                $players
            );


        return array_slice(
            $rankedPlayers,
            0,
            $limit
        );
    }


    /**
     * Find a player's ranking position.
     */
    public function getPlayerRank(
        array $players,
        int $playerId
    ): ?int {

        $rankedPlayers =
            $this->rankPlayers(
                $players
            );


        foreach (
            $rankedPlayers
            as $player
        ) {

            if (
                (int) (
                    $player['player_id']
                    ?? 0
                )
                === $playerId
            ) {

                return
                    (int) $player['rank'];
            }
        }


        return null;
    }


    /**
     * Get the number of rankable players.
     */
    public function getRankedPlayerCount(
        array $players
    ): int {

        return count(
            $this->rankPlayers(
                $players
            )
        );
    }
}