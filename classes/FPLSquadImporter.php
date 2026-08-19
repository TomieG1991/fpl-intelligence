<?php


class FPLSquadImporter
{

    private const BASE_URL =
        'https://fantasy.premierleague.com/api';


    /*
     * ========================================================
     * IMPORT SQUAD
     * ========================================================
     */

    /**
     * Import the latest publicly available squad for an
     * FPL entry.
     *
     * If a gameweek is supplied, that gameweek is requested.
     * Otherwise the importer attempts to determine the most
     * appropriate publicly available gameweek automatically.
     */
    public function importSquad(
        int $entryId,
        ?int $gameweek = null
    ): ?array {

        /*
         * ----------------------------------------------------
         * VALIDATION
         * ----------------------------------------------------
         */

        if ($entryId <= 0) {
            return null;
        }


        if (
            $gameweek !== null
            &&
            (
                $gameweek < 1
                ||
                $gameweek > 38
            )
        ) {

            return null;
        }


        /*
         * ----------------------------------------------------
         * ENTRY
         * ----------------------------------------------------
         */

        $entry =
            $this->fetchJson(
                self::BASE_URL
                . '/entry/'
                . $entryId
                . '/'
            );


        if ($entry === null) {
            return null;
        }


        /*
         * ----------------------------------------------------
         * DETERMINE GAMEWEEK
         * ----------------------------------------------------
         */

        $requestedGameweek =
            $gameweek;


        if ($requestedGameweek === null) {

            $requestedGameweek =
                $this->determinePublicGameweek();
        }


        /*
         * No public gameweek exists yet.
         *
         * This is expected before the first deadline.
         */
        if ($requestedGameweek === null) {

            return $this->buildNoPublicSquadResult(
                $entryId,
                $entry,
                null
            );
        }


        /*
         * ----------------------------------------------------
         * PICKS
         * ----------------------------------------------------
         */

        $picks =
            $this->fetchJson(
                self::BASE_URL
                . '/entry/'
                . $entryId
                . '/event/'
                . $requestedGameweek
                . '/picks/',
                false
            );


        /*
         * A valid entry can exist even when that gameweek's
         * picks are not publicly available yet.
         */
        if (
            $picks === null
            ||
            !isset(
                $picks[
                    'picks'
                ]
            )
            ||
            !is_array(
                $picks[
                    'picks'
                ]
            )
        ) {

            return $this->buildNoPublicSquadResult(
                $entryId,
                $entry,
                $requestedGameweek
            );
        }


        /*
         * ----------------------------------------------------
         * NORMALISE PICKS
         * ----------------------------------------------------
         */

        $players =
            [];


        foreach (
            $picks[
                'picks'
            ]
            as $pick
        ) {

            $playerId =
                (int) (
                    $pick[
                        'element'
                    ]
                    ?? 0
                );


            if ($playerId <= 0) {
                continue;
            }


            $players[] = [

                'fpl_player_id' =>
                    $playerId,

                'squad_position' =>
                    isset(
                        $pick[
                            'position'
                        ]
                    )
                        ? (int) $pick[
                            'position'
                        ]
                        : null,

                'multiplier' =>
                    isset(
                        $pick[
                            'multiplier'
                        ]
                    )
                        ? (int) $pick[
                            'multiplier'
                        ]
                        : null,

                'is_captain' =>
                    (bool) (
                        $pick[
                            'is_captain'
                        ]
                        ?? false
                    ),

                'is_vice_captain' =>
                    (bool) (
                        $pick[
                            'is_vice_captain'
                        ]
                        ?? false
                    )
            ];
        }


        /*
         * ----------------------------------------------------
         * ENTRY HISTORY
         * ----------------------------------------------------
         */

        $entryHistory =
            $picks[
                'entry_history'
            ]
            ?? [];


        /*
         * FPL bank/value values are stored in tenths.
         *
         * Example:
         *
         * 15 => £1.5m
         */
        $bank =
            isset(
                $entryHistory[
                    'bank'
                ]
            )
            &&
            is_numeric(
                $entryHistory[
                    'bank'
                ]
            )
                ? round(
                    (float) $entryHistory[
                        'bank'
                    ]
                    /
                    10,
                    1
                )
                : null;


        $teamValue =
            isset(
                $entryHistory[
                    'value'
                ]
            )
            &&
            is_numeric(
                $entryHistory[
                    'value'
                ]
            )
                ? round(
                    (float) $entryHistory[
                        'value'
                    ]
                    /
                    10,
                    1
                )
                : null;


        /*
         * ----------------------------------------------------
         * RESULT
         * ----------------------------------------------------
         */

        return [

            'status' =>
                'success',

            'message' =>
                'Public FPL squad imported successfully.',

            'entry' => [

                'entry_id' =>
                    $entryId,

                'team_name' =>
                    $entry[
                        'name'
                    ]
                    ?? null,

                'manager_first_name' =>
                    $entry[
                        'player_first_name'
                    ]
                    ?? null,

                'manager_last_name' =>
                    $entry[
                        'player_last_name'
                    ]
                    ?? null,

                'started_event' =>
                    isset(
                        $entry[
                            'started_event'
                        ]
                    )
                        ? (int) $entry[
                            'started_event'
                        ]
                        : null
            ],

            'gameweek' =>
                $requestedGameweek,

            'bank' =>
                $bank,

            'team_value' =>
                $teamValue,

            'player_count' =>
                count(
                    $players
                ),

            'players' =>
                $players,

            'entry_history' => [

                'event' =>
                    isset(
                        $entryHistory[
                            'event'
                        ]
                    )
                        ? (int) $entryHistory[
                            'event'
                        ]
                        : null,

                'points' =>
                    isset(
                        $entryHistory[
                            'points'
                        ]
                    )
                        ? (int) $entryHistory[
                            'points'
                        ]
                        : null,

                'total_points' =>
                    isset(
                        $entryHistory[
                            'total_points'
                        ]
                    )
                        ? (int) $entryHistory[
                            'total_points'
                        ]
                        : null,

                'overall_rank' =>
                    isset(
                        $entryHistory[
                            'overall_rank'
                        ]
                    )
                        ? (int) $entryHistory[
                            'overall_rank'
                        ]
                        : null,

                'event_transfers' =>
                    isset(
                        $entryHistory[
                            'event_transfers'
                        ]
                    )
                        ? (int) $entryHistory[
                            'event_transfers'
                        ]
                        : null,

                'event_transfers_cost' =>
                    isset(
                        $entryHistory[
                            'event_transfers_cost'
                        ]
                    )
                        ? (int) $entryHistory[
                            'event_transfers_cost'
                        ]
                        : null
            ]
        ];
    }


    /*
     * ========================================================
     * DETERMINE PUBLIC GAMEWEEK
     * ========================================================
     */

    /**
     * Determine the latest gameweek whose manager picks should
     * be publicly available.
     */
    private function determinePublicGameweek(): ?int
    {

        $bootstrap =
            $this->fetchJson(
                self::BASE_URL
                . '/bootstrap-static/'
            );


        if (
            $bootstrap === null
            ||
            !isset(
                $bootstrap[
                    'events'
                ]
            )
            ||
            !is_array(
                $bootstrap[
                    'events'
                ]
            )
        ) {

            return null;
        }


        $latestFinished =
            null;


        $current =
            null;


        foreach (
            $bootstrap[
                'events'
            ]
            as $event
        ) {

            $eventId =
                (int) (
                    $event[
                        'id'
                    ]
                    ?? 0
                );


            if ($eventId <= 0) {
                continue;
            }


            if (
                (
                    $event[
                        'finished'
                    ]
                    ?? false
                )
                === true
            ) {

                $latestFinished =
                    $eventId;
            }


            if (
                (
                    $event[
                        'is_current'
                    ]
                    ?? false
                )
                === true
            ) {

                $current =
                    $eventId;
            }
        }


        /*
         * If a gameweek is currently active, its picks are
         * publicly visible after the deadline.
         */
        if ($current !== null) {

            return $current;
        }


        /*
         * Otherwise use the latest completed gameweek.
         */
        if ($latestFinished !== null) {

            return $latestFinished;
        }


        /*
         * Before GW1 there may be no publicly visible picks.
         */
        return null;
    }


    /*
     * ========================================================
     * NO PUBLIC SQUAD RESULT
     * ========================================================
     */

    private function buildNoPublicSquadResult(
        int $entryId,
        array $entry,
        ?int $gameweek
    ): array {

        return [

            'status' =>
                'no_public_squad',

            'message' =>
                'This FPL entry does not currently have a '
                . 'publicly available gameweek squad.',

            'entry' => [

                'entry_id' =>
                    $entryId,

                'team_name' =>
                    $entry[
                        'name'
                    ]
                    ?? null,

                'manager_first_name' =>
                    $entry[
                        'player_first_name'
                    ]
                    ?? null,

                'manager_last_name' =>
                    $entry[
                        'player_last_name'
                    ]
                    ?? null,

                'started_event' =>
                    isset(
                        $entry[
                            'started_event'
                        ]
                    )
                        ? (int) $entry[
                            'started_event'
                        ]
                        : null
            ],

            'gameweek' =>
                $gameweek,

            'bank' =>
                null,

            'team_value' =>
                null,

            'player_count' =>
                0,

            'players' =>
                [],

            'entry_history' =>
                []
        ];
    }


    /*
     * ========================================================
     * HTTP
     * ========================================================
     */

    /**
     * Fetch and decode JSON from the public FPL API.
     *
     * $strict = true:
     *     HTTP/API failure returns null.
     *
     * $strict = false:
     *     Used for endpoints where a missing resource can be
     *     expected, such as pre-deadline gameweek picks.
     */
    private function fetchJson(
        string $url,
        bool $strict = true
    ): ?array {

        $maxAttempts =
            3;


        for (
            $attempt = 1;
            $attempt <= $maxAttempts;
            $attempt++
        ) {

            /*
             * Prefer cURL when available.
             */
            if (
                function_exists(
                    'curl_init'
                )
            ) {

                $curl =
                    curl_init(
                        $url
                    );


                if ($curl === false) {

                    return null;
                }


                curl_setopt_array(
                    $curl,
                    [

                        CURLOPT_RETURNTRANSFER =>
                            true,

                        CURLOPT_FOLLOWLOCATION =>
                            true,

                        CURLOPT_CONNECTTIMEOUT =>
                            5,

                        CURLOPT_TIMEOUT =>
                            15,

                        CURLOPT_HTTPHEADER => [

                            'Accept: application/json',

                            'User-Agent: FPL-Intelligence/1.0'
                        ]
                    ]
                );


                $response =
                    curl_exec(
                        $curl
                    );


                $httpCode =
                    (int) curl_getinfo(
                        $curl,
                        CURLINFO_HTTP_CODE
                    );


                $curlError =
                    curl_errno(
                        $curl
                    );


                curl_close(
                    $curl
                );


                if (
                    $response !== false
                    &&
                    $curlError === 0
                    &&
                    $httpCode >= 200
                    &&
                    $httpCode < 300
                ) {

                    $decoded =
                        json_decode(
                            $response,
                            true
                        );


                    if (
                        is_array(
                            $decoded
                        )
                    ) {

                        return $decoded;
                    }
                }

            } else {

                /*
                 * -----------------------------------------------
                 * FILE_GET_CONTENTS FALLBACK
                 * -----------------------------------------------
                 */

                $context =
                    stream_context_create(
                        [

                            'http' => [

                                'method' =>
                                    'GET',

                                'timeout' =>
                                    15,

                                'ignore_errors' =>
                                    true,

                                'header' =>
                                    "Accept: application/json\r\n"
                                    . "User-Agent: FPL-Intelligence/1.0\r\n"
                            ]
                        ]
                    );


                $response =
                    @file_get_contents(
                        $url,
                        false,
                        $context
                    );


                if ($response !== false) {

                    $decoded =
                        json_decode(
                            $response,
                            true
                        );


                    if (
                        is_array(
                            $decoded
                        )
                    ) {

                        return $decoded;
                    }
                }
            }


            /*
             * Short delay before retrying.
             */
            if (
                $attempt
                <
                $maxAttempts
            ) {

                usleep(
                    250000
                    *
                    $attempt
                );
            }
        }


        return null;
    }
}