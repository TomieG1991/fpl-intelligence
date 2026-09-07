<?php

class PlayerGameweekOutcomeAvailability
{
    /**
     * Determine whether a gameweek is authoritative enough
     * for completed-gameweek outcome evaluation.
     *
     * Availability requires:
     *
     * - a valid local gameweek identity
     * - a valid FPL gameweek identity
     * - FPL to have marked the gameweek as finished
     * - FPL to have marked the gameweek data as checked
     *
     * This class deliberately does not inspect fixture-history
     * evidence, recommendation snapshots or backtesting data.
     */
    public function isAvailable(
        array $gameweek
    ): bool {

        /*
         * ====================================================
         * VALIDATE GAMEWEEK IDENTITY
         * ====================================================
         */

        $gameweekId =
            (int) (
                $gameweek[
                    'id'
                ]
                ?? 0
            );


        $fplGameweekId =
            (int) (
                $gameweek[
                    'fpl_gameweek_id'
                ]
                ?? 0
            );


        if (
            $gameweekId <= 0
            ||
            $fplGameweekId <= 0
        ) {

            return false;
        }


        /*
         * ====================================================
         * CHECK AUTHORITATIVE COMPLETION STATE
         * ====================================================
         *
         * This deliberately matches the existing historical
         * snapshot eligibility boundary:
         *
         * finished AND data_checked
         */

        return
            !empty(
                $gameweek[
                    'finished'
                ]
                ?? false
            )
            &&
            !empty(
                $gameweek[
                    'data_checked'
                ]
                ?? false
            );
    }
}