<?php

class PlayerFormHistory
{
    private PlayerFixtureHistoryRepository $historyRepository;
    
    /**
     * Request-level recent-history caches.
     *
     * Rows are stored chronologically, oldest to newest.
     *
     * If a five-match window has already been loaded, a later
     * three-match request can be satisfied from that cached data
     * without another database query.
     */
    private array $fixtureHistoryCache = [];

    private array $appearanceHistoryCache = [];


    /**
     * Initialise the historical form window service.
     */
    public function __construct(
        PlayerFixtureHistoryRepository $historyRepository
    ) {

        $this->historyRepository =
            $historyRepository;
    }


    /**
     * Build a clean recent-history package for one player.
     *
     * Fixture history:
     * - includes official zero-minute rows
     * - represents recent team-fixture participation evidence
     *
     * Appearance history:
     * - includes only rows where minutes > 0
     * - represents recent on-pitch performance evidence
     *
     * These windows intentionally remain separate so future
     * Player Form Intelligence can distinguish performance
     * quality from recent participation.
     */
    public function buildHistory(
        int $playerId,
        int $fixtureLimit = 5,
        int $appearanceLimit = 5
    ): array {

        if ($playerId <= 0) {

            return $this->emptyHistory(
                $playerId,
                $fixtureLimit,
                $appearanceLimit
            );
        }


        $fixtureLimit =
            $this->normaliseLimit(
                $fixtureLimit
            );


        $appearanceLimit =
            $this->normaliseLimit(
                $appearanceLimit
            );


        if (
            $fixtureLimit <= 0
            &&
            $appearanceLimit <= 0
        ) {

            return $this->emptyHistory(
                $playerId,
                $fixtureLimit,
                $appearanceLimit
            );
        }


        $fixtureWindow =
            $fixtureLimit > 0
                ? $this->getFixtureWindow(
                    $playerId,
                    $fixtureLimit
                )
                : [];


        $appearanceWindow =
            $appearanceLimit > 0
                ? $this->getAppearanceWindow(
                    $playerId,
                    $appearanceLimit
                )
                : [];


        $zeroMinuteRows =
            0;


        $fixtureMinutes =
            0;


        $appearanceMinutes =
            0;


        foreach (
            $fixtureWindow
            as $row
        ) {

            $minutes =
                (int) (
                    $row[
                        'minutes'
                    ]
                    ?? 0
                );


            $fixtureMinutes +=
                max(
                    0,
                    $minutes
                );


            if ($minutes <= 0) {

                $zeroMinuteRows++;
            }
        }


        foreach (
            $appearanceWindow
            as $row
        ) {

            $appearanceMinutes +=
                max(
                    0,
                    (int) (
                        $row[
                            'minutes'
                        ]
                        ?? 0
                    )
                );
        }


        $fixtureSampleSize =
            count(
                $fixtureWindow
            );


        $appearanceSampleSize =
            count(
                $appearanceWindow
            );


        $participationRate =
            $fixtureSampleSize > 0
                ? (
                    $fixtureSampleSize
                    -
                    $zeroMinuteRows
                )
                /
                $fixtureSampleSize
                *
                100
                : null;


        return [

            'player_id' =>
                $playerId,

            'fixture_limit' =>
                $fixtureLimit,

            'appearance_limit' =>
                $appearanceLimit,

            'fixture_window' =>
                $fixtureWindow,

            'appearance_window' =>
                $appearanceWindow,

            'fixture_sample_size' =>
                $fixtureSampleSize,

            'appearance_sample_size' =>
                $appearanceSampleSize,

            'zero_minute_rows' =>
                $zeroMinuteRows,

            'fixture_minutes' =>
                $fixtureMinutes,

            'appearance_minutes' =>
                $appearanceMinutes,

            'participation_rate' =>
                $participationRate !== null
                    ? round(
                        $participationRate,
                        2
                    )
                    : null,

            'has_fixture_history' =>
                $fixtureSampleSize > 0,

            'has_appearance_history' =>
                $appearanceSampleSize > 0
        ];
    }


    /**
     * Build the standard five-fixture / five-appearance
     * recent-history package.
     */
    public function buildDefaultHistory(
        int $playerId
    ): array {

        return $this->buildHistory(
            $playerId,
            5,
            5
        );
    }


    /**
     * Build the short recent-history package that will be
     * useful for future last-three Form Intelligence.
     */
    public function buildShortHistory(
        int $playerId
    ): array {

        return $this->buildHistory(
            $playerId,
            3,
            3
        );
    }
    
    
    /**
     * Clear cached historical windows.
     *
     * Normal application requests do not mutate historical data
     * while Player Intelligence is being calculated, so cached
     * windows remain safe for the lifetime of that request.
     *
     * Tests that deliberately rewrite fixture history can clear
     * one player or the complete cache before recalculating.
     */
    public function clearCache(
        ?int $playerId = null
    ): void {

        if (
            $playerId === null
        ) {

            $this->fixtureHistoryCache =
                [];


            $this->appearanceHistoryCache =
                [];


            return;
        }


        unset(
            $this->fixtureHistoryCache[
                $playerId
            ],
            $this->appearanceHistoryCache[
                $playerId
            ]
        );
    }
    
    
    /**
     * Return a recent fixture window using the request-level cache
     * whenever a sufficiently large window has already been loaded.
     */
    private function getFixtureWindow(
        int $playerId,
        int $limit
    ): array {

        $cached =
            $this->fixtureHistoryCache[
                $playerId
            ]
            ?? null;


        if (
            is_array(
                $cached
            )
            &&
            (
                (int) (
                    $cached[
                        'limit'
                    ]
                    ?? 0
                )
            )
            >=
            $limit
        ) {

            return array_slice(
                $cached[
                    'rows'
                ]
                ?? [],
                -$limit
            );
        }


        $rows =
            $this
                ->historyRepository
                ->getRecentByPlayerId(
                    $playerId,
                    $limit
                );


        $this->fixtureHistoryCache[
            $playerId
        ] = [

            'limit' =>
                $limit,

            'rows' =>
                $rows
        ];


        return $rows;
    }


    /**
     * Return a recent appearance window using the request-level
     * cache whenever possible.
     */
    private function getAppearanceWindow(
        int $playerId,
        int $limit
    ): array {

        $cached =
            $this->appearanceHistoryCache[
                $playerId
            ]
            ?? null;


        if (
            is_array(
                $cached
            )
            &&
            (
                (int) (
                    $cached[
                        'limit'
                    ]
                    ?? 0
                )
            )
            >=
            $limit
        ) {

            return array_slice(
                $cached[
                    'rows'
                ]
                ?? [],
                -$limit
            );
        }


        $rows =
            $this
                ->historyRepository
                ->getRecentAppearancesByPlayerId(
                    $playerId,
                    $limit
                );


        $this->appearanceHistoryCache[
            $playerId
        ] = [

            'limit' =>
                $limit,

            'rows' =>
                $rows
        ];


        return $rows;
    }


    /**
     * Keep requested windows within the same maximum supported
     * by PlayerFixtureHistoryRepository.
     */
    private function normaliseLimit(
        int $limit
    ): int {

        if ($limit <= 0) {

            return 0;
        }


        return min(
            20,
            $limit
        );
    }


    /**
     * Return a predictable empty structure.
     */
    private function emptyHistory(
        int $playerId,
        int $fixtureLimit,
        int $appearanceLimit
    ): array {

        return [

            'player_id' =>
                $playerId,

            'fixture_limit' =>
                max(
                    0,
                    min(
                        20,
                        $fixtureLimit
                    )
                ),

            'appearance_limit' =>
                max(
                    0,
                    min(
                        20,
                        $appearanceLimit
                    )
                ),

            'fixture_window' =>
                [],

            'appearance_window' =>
                [],

            'fixture_sample_size' =>
                0,

            'appearance_sample_size' =>
                0,

            'zero_minute_rows' =>
                0,

            'fixture_minutes' =>
                0,

            'appearance_minutes' =>
                0,

            'participation_rate' =>
                null,

            'has_fixture_history' =>
                false,

            'has_appearance_history' =>
                false
        ];
    }
}