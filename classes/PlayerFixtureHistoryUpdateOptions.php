<?php

class PlayerFixtureHistoryUpdateOptions
{
    public static function resolve(
        array $query,
        array $arguments
    ): array {

        $full =
            false;


        $limit =
            25;


        $offset =
            0;


        /*
         * ====================================================
         * BROWSER OPTIONS
         * ====================================================
         */

        if (
            isset(
                $query[
                    'full'
                ]
            )
            &&
            (string) $query[
                'full'
            ]
            ===
            '1'
        ) {

            $full =
                true;
        }


        if (
            array_key_exists(
                'limit',
                $query
            )
        ) {

            $limit =
                (int) $query[
                    'limit'
                ];
        }


        if (
            array_key_exists(
                'offset',
                $query
            )
        ) {

            $offset =
                (int) $query[
                    'offset'
                ];
        }


        /*
         * ====================================================
         * CLI OPTIONS
         * ====================================================
         */

        foreach (
            $arguments
            as $argument
        ) {

            if (
                $argument
                ===
                '--full'
            ) {

                $full =
                    true;

                continue;
            }


            if (
                str_starts_with(
                    $argument,
                    '--limit='
                )
            ) {

                $limit =
                    (int) substr(
                        $argument,
                        strlen(
                            '--limit='
                        )
                    );

                continue;
            }


            if (
                str_starts_with(
                    $argument,
                    '--offset='
                )
            ) {

                $offset =
                    (int) substr(
                        $argument,
                        strlen(
                            '--offset='
                        )
                    );
            }
        }


        /*
         * ====================================================
         * NORMALISE LIMIT
         * ====================================================
         */

        $limit =
            max(
                1,
                min(
                    100,
                    $limit
                )
            );


        /*
         * ====================================================
         * NORMALISE OFFSET
         * ====================================================
         */

        $offset =
            max(
                0,
                $offset
            );


        return [

            'full' =>
                $full,

            'limit' =>
                $limit,

            'offset' =>
                $offset
        ];
    }
}