<?php

class SquadHorizonIntelligenceService
{
    
    private PlayerRepository
    $playerRepository;


    private PlayerIntelligenceService
        $playerIntelligenceService;


    private SquadHorizonIntelligence
        $squadHorizonIntelligence;


    /**
     * Build the application-level Squad Horizon orchestration
     * service.
     *
     * Existing services retain ownership of player projections,
     * while SquadHorizonIntelligence retains ownership of the
     * squad-level planning calculations.
     */
    public function __construct(
        PlayerRepository $playerRepository,
        PlayerIntelligenceService $playerIntelligenceService,
        SquadHorizonIntelligence $squadHorizonIntelligence
    ) {

        $this->playerRepository =
            $playerRepository;


        $this->playerIntelligenceService =
            $playerIntelligenceService;


        $this->squadHorizonIntelligence =
            $squadHorizonIntelligence;
    }
    
    /**
     * Build squad-horizon intelligence from an imported FPL squad.
     *
     * The real orchestration contract will be implemented in the
     * next controlled stage.
     */
    public function buildForImportedSquad(
        array $importedSquad,
        int $horizon = 3
    ): array {

        /*
         * --------------------------------------------------------
         * VALIDATE IMPORTED SQUAD
         * --------------------------------------------------------
         *
         * Squad Horizon requires a successful imported FPL squad
         * containing player data before any projection or squad
         * intelligence work can begin.
         */

        $importStatus =
            $importedSquad[
                'status'
            ]
            ?? null;


        $importedPlayers =
            $importedSquad[
                'players'
            ]
            ?? [];


        if (
            $importStatus
            !==
            'success'
            ||
            !is_array(
                $importedPlayers
            )
            ||
            empty(
                $importedPlayers
            )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'player_count' =>
                    0,

                'players' =>
                    [],

                'horizon_result' =>
                    null
            ];
        }
        
        
        /*
         * --------------------------------------------------------
         * REQUIRE COMPLETE FPL SQUAD
         * --------------------------------------------------------
         *
         * Squad Horizon Intelligence evaluates the structure of a
         * complete FPL squad.
         *
         * A valid production squad must therefore contain exactly
         * 15 imported player rows before any repository lookups or
         * projection calculations are attempted.
         */

        $requiredPlayerCount =
            15;


        $importedPlayerCount =
            count(
                $importedPlayers
            );


        if (
            $importedPlayerCount
            !==
            $requiredPlayerCount
        ) {

            return [

                'status' =>
                    'Unavailable',

                'imported_player_count' =>
                    $importedPlayerCount,

                'required_player_count' =>
                    $requiredPlayerCount,

                'player_count' =>
                    0,

                'players' =>
                    [],

                'horizon_result' =>
                    null
            ];
        }
        


        /*
         * --------------------------------------------------------
         * RESOLVE IMPORTED PLAYERS
         * --------------------------------------------------------
         *
         * FPLSquadImporter identifies players using the official
         * FPL player ID.
         *
         * Squad Horizon works with the local player records, so
         * resolve each imported FPL player ID through the existing
         * PlayerRepository.
         */

        $resolvedPlayers =
            [];


        $unresolvedFplPlayerIds =
            [];


        foreach (
            $importedPlayers
            as $importedPlayer
        ) {

            if (
                !is_array(
                    $importedPlayer
                )
            ) {

                continue;
            }


            $fplPlayerId =
                (int) (
                    $importedPlayer[
                        'fpl_player_id'
                    ]
                    ?? 0
                );


            if (
                $fplPlayerId <= 0
            ) {

                continue;
            }


            $localPlayer =
                $this->playerRepository
                    ->getByFplPlayerId(
                        $fplPlayerId
                    );


            if (
                !is_array(
                    $localPlayer
                )
            ) {

                $unresolvedFplPlayerIds[] =
                    $fplPlayerId;


                continue;
            }


            $resolvedPlayers[] =
                $localPlayer;
        }
        
        
        /*
         * --------------------------------------------------------
         * REQUIRE COMPLETE PLAYER RESOLUTION
         * --------------------------------------------------------
         *
         * Squad-level intelligence must never be calculated from a
         * partially resolved imported squad.
         *
         * If any valid imported FPL player cannot be mapped to a
         * local player record, return an explicit unavailable result
         * rather than silently analysing an incomplete squad.
         */

        if (
            !empty(
                $unresolvedFplPlayerIds
            )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'imported_player_count' =>
                    count(
                        $importedPlayers
                    ),

                'resolved_player_count' =>
                    count(
                        $resolvedPlayers
                    ),

                'unresolved_fpl_player_ids' =>
                    $unresolvedFplPlayerIds,

                'player_count' =>
                    0,

                'players' =>
                    [],

                'horizon_result' =>
                    null
            ];
        }
        
        
        /*
         * --------------------------------------------------------
         * REQUEST MULTI-GAMEWEEK PLAYER PROJECTIONS
         * --------------------------------------------------------
         *
         * Each resolved local player is passed through the existing
         * PlayerIntelligenceService multi-gameweek Expected Points
         * pipeline.
         *
         * The projection responses are collected here so the next
         * orchestration stage can adapt them into the input contract
         * required by SquadHorizonIntelligence.
         *
         * No Squad Horizon calculation is performed at this stage.
         */

        $playerProjections =
            [];


        foreach (
            $resolvedPlayers
            as $resolvedPlayer
        ) {

            $localPlayerId =
                isset(
                    $resolvedPlayer[
                        'id'
                    ]
                )
                &&
                is_numeric(
                    $resolvedPlayer[
                        'id'
                    ]
                )
                    ? (int) $resolvedPlayer[
                        'id'
                    ]
                    : 0;


            if (
                $localPlayerId <= 0
            ) {

                continue;
            }


            $playerProjections[
                $localPlayerId
            ] =
                $this->playerIntelligenceService
                    ->getPlayerMultiGameweekExpectedPoints(
                        $localPlayerId,
                        6
                    );
        }
        
        
        /*
         * --------------------------------------------------------
         * ADAPT PLAYER PROJECTIONS FOR SQUAD HORIZON
         * --------------------------------------------------------
         *
         * SquadHorizonIntelligence consumes a squad-level player
         * structure rather than the service-facing projection
         * response returned by PlayerIntelligenceService.
         *
         * Preserve the existing multi-gameweek projected points
         * exactly. This layer reshapes data only; it does not
         * recalculate Expected Points.
         */

        $adaptedSquad =
            [];


        foreach (
            $resolvedPlayers
            as $resolvedPlayer
        ) {

            $localPlayerId =
                isset(
                    $resolvedPlayer[
                        'id'
                    ]
                )
                &&
                is_numeric(
                    $resolvedPlayer[
                        'id'
                    ]
                )
                    ? (int) $resolvedPlayer[
                        'id'
                    ]
                    : 0;


            if (
                $localPlayerId <= 0
            ) {

                continue;
            }


            $projection =
                $playerProjections[
                    $localPlayerId
                ]
                ?? [];


            $projectionGameweeks =
                isset(
                    $projection[
                        'gameweeks'
                    ]
                )
                &&
                is_array(
                    $projection[
                        'gameweeks'
                    ]
                )
                    ? $projection[
                        'gameweeks'
                    ]
                    : [];


            $adaptedGameweeks =
                [];


            foreach (
                $projectionGameweeks
                as $gameweekKey => $gameweekProjection
            ) {

                if (
                    !is_array(
                        $gameweekProjection
                    )
                ) {

                    continue;
                }


                $gameweek =
                    isset(
                        $gameweekProjection[
                            'gameweek'
                        ]
                    )
                    &&
                    is_numeric(
                        $gameweekProjection[
                            'gameweek'
                        ]
                    )
                        ? (int) $gameweekProjection[
                            'gameweek'
                        ]
                        : (
                            is_numeric(
                                $gameweekKey
                            )
                                ? (int) $gameweekKey
                                : 0
                        );


                if (
                    $gameweek <= 0
                ) {

                    continue;
                }


                $projectedPoints =
                    isset(
                        $gameweekProjection[
                            'projected_points'
                        ]
                    )
                    &&
                    is_numeric(
                        $gameweekProjection[
                            'projected_points'
                        ]
                    )
                        ? (float) $gameweekProjection[
                            'projected_points'
                        ]
                        : null;


                /*
                 * Preserve the player's local team ID on every adapted
                 * gameweek row.
                 */

                $teamId =
                    isset(
                        $resolvedPlayer[
                            'team_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $resolvedPlayer[
                            'team_id'
                        ]
                    )
                        ? (int) $resolvedPlayer[
                            'team_id'
                        ]
                        : null;


                /*
                 * Derive opponent metadata from the fixture-level rows
                 * exposed at the top level of PlayerIntelligenceService's
                 * multi-gameweek response.
                 *
                 * MultiGameweekExpectedPoints remains the source of truth
                 * for the aggregated projected-points total above.
                 *
                 * Fixture metadata is matched separately by gameweek.
                 */

                $projectionFixtures =
                    isset(
                        $projection[
                            'fixtures'
                        ]
                    )
                    &&
                    is_array(
                        $projection[
                            'fixtures'
                        ]
                    )
                        ? $projection[
                            'fixtures'
                        ]
                        : [];


                $gameweekFixtures =
                    [];


                foreach (
                    $projectionFixtures
                    as $fixtureRow
                ) {

                    if (
                        !is_array(
                            $fixtureRow
                        )
                    ) {

                        continue;
                    }


                    $fixtureGameweek =
                        $fixtureRow[
                            'gameweek'
                        ]
                        ?? null;


                    if (
                        !is_numeric(
                            $fixtureGameweek
                        )
                        ||
                        (int) $fixtureGameweek
                        !==
                        $gameweek
                    ) {

                        continue;
                    }


                    $gameweekFixtures[] =
                        $fixtureRow;
                }


                /*
                 * An aggregated player/gameweek row may expose a single
                 * opponent only when that gameweek contains exactly one
                 * fixture with valid opponent metadata.
                 *
                 * A Double Gameweek deliberately receives null because no
                 * single opponent truthfully represents the whole gameweek.
                 */

                $opponentTeamId =
                    null;


                if (
                    count(
                        $gameweekFixtures
                    )
                    ===
                    1
                ) {

                    $fixtureOpponentTeamId =
                        $gameweekFixtures[0][
                            'opponent_team_id'
                        ]
                        ?? null;


                    if (
                        is_numeric(
                            $fixtureOpponentTeamId
                        )
                    ) {

                        $opponentTeamId =
                            (int) $fixtureOpponentTeamId;
                    }
                }


                $adaptedGameweeks[
                    $gameweek
                ] = [

                    'gameweek' =>
                        $gameweek,

                    'projected_points' =>
                        $projectedPoints,

                    'team_id' =>
                        $teamId,

                    'opponent_team_id' =>
                        $opponentTeamId
                ];
            }


            $adaptedSquad[] = [

                'player_id' =>
                    $localPlayerId,

                'name' =>
                    $resolvedPlayer[
                        'web_name'
                    ]
                    ?? null,

                'position' =>
                    $resolvedPlayer[
                        'position'
                    ]
                    ?? null,

                'team_id' =>
                    isset(
                        $resolvedPlayer[
                            'team_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $resolvedPlayer[
                            'team_id'
                        ]
                    )
                        ? (int) $resolvedPlayer[
                            'team_id'
                        ]
                        : null,

                'gameweeks' =>
                    $adaptedGameweeks
            ];
        }
        
        
        /*
         * --------------------------------------------------------
         * BUILD SQUAD HORIZON INTELLIGENCE
         * --------------------------------------------------------
         */

        $horizonResult =
            $this->squadHorizonIntelligence
                ->buildHorizon(
                    $adaptedSquad,
                    $horizon
                );


        /*
         * At this stage the production service has resolved the
         * imported FPL squad into local player records.
         *
         * Projection adaptation and Squad Horizon calculation are
         * deliberately added in later stages.
         */

        return [

            'status' =>
                !empty(
                    $resolvedPlayers
                )
                    ? 'Available'
                    : 'Unavailable',

            'player_count' =>
                count(
                    $resolvedPlayers
                ),

            'players' =>
                $resolvedPlayers,

            'horizon_result' =>
                $horizonResult
        ];
    }
}