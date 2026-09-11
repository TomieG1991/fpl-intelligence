<?php

class BootstrapDataValidator
{

    /*
     * ========================================================
     * VALIDATE
     * ========================================================
     */

    public static function validate(
        array $data
    ): void {

        /*
         * ====================================================
         * TEAMS
         * ====================================================
         */

        if (
            !isset(
                $data[
                    'teams'
                ]
            )
            ||
            !is_array(
                $data[
                    'teams'
                ]
            )
        ) {

            throw new RuntimeException(
                'FPL bootstrap data does not contain teams'
            );
        }


        if (
            empty(
                $data[
                    'teams'
                ]
            )
        ) {

            throw new RuntimeException(
                'FPL bootstrap data contains no teams'
            );
        }


        /*
         * ====================================================
         * PLAYERS
         * ====================================================
         */

        if (
            !isset(
                $data[
                    'elements'
                ]
            )
            ||
            !is_array(
                $data[
                    'elements'
                ]
            )
        ) {

            throw new RuntimeException(
                'FPL bootstrap data does not contain players'
            );
        }


        if (
            empty(
                $data[
                    'elements'
                ]
            )
        ) {

            throw new RuntimeException(
                'FPL bootstrap data contains no players'
            );
        }


        /*
         * ====================================================
         * GAMEWEEKS
         * ====================================================
         */

        if (
            !isset(
                $data[
                    'events'
                ]
            )
            ||
            !is_array(
                $data[
                    'events'
                ]
            )
        ) {

            throw new RuntimeException(
                'FPL bootstrap data does not contain gameweeks'
            );
        }


        if (
            empty(
                $data[
                    'events'
                ]
            )
        ) {

            throw new RuntimeException(
                'FPL bootstrap data contains no gameweeks'
            );
        }
    }
}