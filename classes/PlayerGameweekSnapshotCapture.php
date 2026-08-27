<?php

class PlayerGameweekSnapshotCapture
{
    private PDO $db;

    private GameweekRepository $gameweekRepository;

    private PlayerRepository $playerRepository;

    private PlayerGameweekSnapshotRepository $snapshotRepository;
    
    private PlayerFixtureHistoryRepository $fixtureHistoryRepository;


    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;


        $this->gameweekRepository =
            new GameweekRepository(
                $db
            );


        $this->playerRepository =
            new PlayerRepository(
                $db
            );


        $this->snapshotRepository =
            new PlayerGameweekSnapshotRepository(
                $db
            );
            
        $this->fixtureHistoryRepository =
        new PlayerFixtureHistoryRepository(
            $db
        );
    }
    
    
    /**
     * Determine whether a gameweek is safe to capture
     * as an immutable historical snapshot.
     *
     * A gameweek is eligible only when FPL has marked
     * it both finished and data checked.
     */
    public function isGameweekEligible(
        array $gameweek
    ): bool {

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


    /**
     * Capture the latest completed gameweek.
     *
     * Historical snapshots are immutable.
     *
     * Existing player/gameweek rows are never updated.
     */
    public function captureLatestCompletedGameweek(): array
    {
        /*
         * ====================================================
         * RESOLVE LATEST COMPLETED GAMEWEEK
         * ====================================================
         */

        $gameweeks =
            $this->gameweekRepository
                ->getAll();


        $latestCompletedGameweek =
            null;


        foreach (
            $gameweeks
            as $gameweek
        ) {

            /*
             * A gameweek is eligible only once FPL has marked
             * it as both finished and checked.
             */
            if (
                !$this->isGameweekEligible(
                    $gameweek
                )
            ) {

                continue;
            }


            if (
                $latestCompletedGameweek === null
                ||
                (
                    (int) (
                        $gameweek[
                            'fpl_gameweek_id'
                        ]
                        ?? 0
                    )
                    >
                    (int) (
                        $latestCompletedGameweek[
                            'fpl_gameweek_id'
                        ]
                        ?? 0
                    )
                )
            ) {

                $latestCompletedGameweek =
                    $gameweek;
            }
        }


        /*
         * ====================================================
         * NO COMPLETED GAMEWEEK
         * ====================================================
         */

        if (
            $latestCompletedGameweek === null
        ) {

            return [

                'status' =>
                    'Unavailable',

                'reason' =>
                    'No completed gameweek is available',

                'gameweek_id' =>
                    null,

                'fpl_gameweek_id' =>
                    null,

                'players_considered' =>
                    0,

                'inserted' =>
                    0,

                'existing' =>
                    0,

                'skipped' =>
                    0
            ];
        }


        $gameweekId =
            (int) (
                $latestCompletedGameweek[
                    'id'
                ]
                ?? 0
            );


        $fplGameweekId =
            (int) (
                $latestCompletedGameweek[
                    'fpl_gameweek_id'
                ]
                ?? 0
            );


        /*
         * Defensive guard.
         *
         * We should never capture against an invalid internal
         * gameweek ID.
         */
        if (
            $gameweekId <= 0
            ||
            $fplGameweekId <= 0
        ) {

            return [

                'status' =>
                    'Unavailable',

                'reason' =>
                    'Completed gameweek identity is invalid',

                'gameweek_id' =>
                    $gameweekId > 0
                        ? $gameweekId
                        : null,

                'fpl_gameweek_id' =>
                    $fplGameweekId > 0
                        ? $fplGameweekId
                        : null,

                'players_considered' =>
                    0,

                'inserted' =>
                    0,

                'existing' =>
                    0,

                'skipped' =>
                    0
            ];
        }


        /*
         * ====================================================
         * LOAD CURRENT PLAYER STATE
         * ====================================================
         */
         
        /*
         * ====================================================
         * LOAD COMPLETED-GAMEWEEK MARKET HISTORY
         * ====================================================
         *
         * Raw selected-manager count is not available from the
         * live bootstrap player state.
         *
         * The completed FPL player-history rows contain the exact
         * historical selected count, so build one lookup here.
         */

        $fixtureHistoryRows =
            $this->fixtureHistoryRepository
                ->getByGameweekId(
                    $gameweekId
                );


        $historyByPlayerId =
            [];


        foreach (
            $fixtureHistoryRows
            as $fixtureHistoryRow
        ) {

            $historyPlayerId =
                (int) (
                    $fixtureHistoryRow[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                $historyPlayerId <= 0
            ) {

                continue;
            }


            /*
             * A normal gameweek contains one history row per player.
             *
             * For future Double Gameweeks there can be multiple fixture
             * rows. Selected is a gameweek market-state field, so retain
             * the first valid value encountered rather than generating
             * duplicate snapshot records.
             */
            if (
                !isset(
                    $historyByPlayerId[
                        $historyPlayerId
                    ]
                )
            ) {

                $historyByPlayerId[
                    $historyPlayerId
                ] =
                    $fixtureHistoryRow;
            }
        }

        $players =
            $this->playerRepository
                ->getAll();


        $playersConsidered =
            count(
                $players
            );


        $inserted =
            0;


        $existing =
            0;


        $skipped =
            0;


        /*
         * ====================================================
         * BUILD AND CAPTURE SNAPSHOTS
         * ====================================================
         */

        foreach (
            $players
            as $player
        ) {

            $playerId =
                (int) (
                    $player[
                        'id'
                    ]
                    ?? 0
                );


            $fplPlayerId =
                (int) (
                    $player[
                        'fpl_player_id'
                    ]
                    ?? 0
                );


            $teamId =
                (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                );


            /*
             * A snapshot without these identities would not be
             * useful and could violate relational integrity.
             */
            if (
                $playerId <= 0
                ||
                $fplPlayerId <= 0
                ||
                $teamId <= 0
            ) {

                $skipped++;

                continue;
            }


            /*
             * =================================================
             * CHECK EXISTING SNAPSHOT FIRST
             * =================================================
             *
             * insertIfAbsent() remains the final database-level
             * protection.
             *
             * This read allows the capture report to distinguish
             * an existing row from a failed/skipped insert.
             */

            $existingSnapshot =
                $this->snapshotRepository
                    ->getByPlayerAndGameweek(
                        $playerId,
                        $gameweekId
                    );


            if (
                is_array(
                    $existingSnapshot
                )
            ) {

                $existing++;

                continue;
            }
            
            $historicalMarketRow =
                $historyByPlayerId[
                    $playerId
                ]
                ?? null;


            $historicalSelected =
                is_array(
                    $historicalMarketRow
                )
                    ? $this->integerOrNull(
                        $historicalMarketRow[
                            'selected'
                        ]
                        ?? null
                    )
                    : null;


            /*
             * =================================================
             * SNAPSHOT PAYLOAD
             * =================================================
             */

            $snapshot = [

                'gameweek_id' =>
                    $gameweekId,

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    $fplPlayerId,

                'team_id' =>
                    $teamId,

                'position' =>
                    $player[
                        'position'
                    ]
                    ?? null,

                'price' =>
                    $this->numericOrNull(
                        $player[
                            'price'
                        ]
                        ?? null
                    ),
                    
                'selected' =>
                    $historicalSelected,

                'selected_by_percent' =>
                    $this->numericOrNull(
                        $player[
                            'selected_by_percent'
                        ]
                        ?? null
                    ),

                'chance_of_playing' =>
                    $this->integerOrNull(
                        $player[
                            'chance_of_playing'
                        ]
                        ?? null
                    ),

                'status' =>
                    $player[
                        'status'
                    ]
                    ?? null,

                'news' =>
                    $player[
                        'news'
                    ]
                    ?? null,

                'minutes' =>
                    (int) (
                        $player[
                            'minutes'
                        ]
                        ?? 0
                    ),

                'goals' =>
                    (int) (
                        $player[
                            'goals'
                        ]
                        ?? 0
                    ),

                'assists' =>
                    (int) (
                        $player[
                            'assists'
                        ]
                        ?? 0
                    ),

                'clean_sheets' =>
                    (int) (
                        $player[
                            'clean_sheets'
                        ]
                        ?? 0
                    ),

                'bonus' =>
                    (int) (
                        $player[
                            'bonus'
                        ]
                        ?? 0
                    ),

                'bps' =>
                    (int) (
                        $player[
                            'bps'
                        ]
                        ?? 0
                    ),

                'ict_index' =>
                    $this->numericOrNull(
                        $player[
                            'ict_index'
                        ]
                        ?? null
                    ),

                'expected_goals' =>
                    $this->numericOrNull(
                        $player[
                            'expected_goals'
                        ]
                        ?? null
                    ),

                'expected_assists' =>
                    $this->numericOrNull(
                        $player[
                            'expected_assists'
                        ]
                        ?? null
                    ),

                'expected_goal_involvements' =>
                    $this->numericOrNull(
                        $player[
                            'expected_goal_involvements'
                        ]
                        ?? null
                    )
            ];


            /*
             * =================================================
             * IMMUTABLE INSERT
             * =================================================
             */

            $wasInserted =
                $this->snapshotRepository
                    ->insertIfAbsent(
                        $snapshot
                    );


            if (
                $wasInserted
            ) {

                $inserted++;

            } else {

                /*
                 * A duplicate could have appeared between the
                 * existence check and INSERT IGNORE.
                 *
                 * It still counts as an existing snapshot.
                 */
                $existing++;
            }
        }


        /*
         * ====================================================
         * RESULT
         * ====================================================
         */

        return [

            'status' =>
                'Complete',

            'gameweek_id' =>
                $gameweekId,

            'fpl_gameweek_id' =>
                $fplGameweekId,

            'finished' =>
                !empty(
                    $latestCompletedGameweek[
                        'finished'
                    ]
                    ?? false
                ),

            'data_checked' =>
                !empty(
                    $latestCompletedGameweek[
                        'data_checked'
                    ]
                    ?? false
                ),

            'players_considered' =>
                $playersConsidered,

            'inserted' =>
                $inserted,

            'existing' =>
                $existing,

            'skipped' =>
                $skipped
        ];
    }


    /**
     * Return a numeric value or null.
     */
    private function numericOrNull(
        mixed $value
    ): ?float {

        if (
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return
            (float) $value;
    }


    /**
     * Return an integer value or null.
     */
    private function integerOrNull(
        mixed $value
    ): ?int {

        if (
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return
            (int) $value;
    }
}