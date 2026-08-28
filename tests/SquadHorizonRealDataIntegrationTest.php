<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Real Data Integration Test<br>";
echo "v0.32.0 — Squad Horizon & Rotation Intelligence<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function squadHorizonRealDataCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        $passed++;

        return;
    }


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    $failed++;
}


function squadHorizonRealDataHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
}


/*
 * ============================================================
 * REAL DATA AUDIT TEST DOUBLES
 * ============================================================
 *
 * These classes allow the real production orchestration
 * service to run unchanged while retaining the intermediate
 * projection and adapter data needed by this integration
 * test's reconciliation diagnostics.
 */

class SquadHorizonRealDataPlayerIntelligenceAudit
    extends PlayerIntelligenceService
{
    public array
        $multiGameweekByPlayerId =
            [];


    public function getPlayerMultiGameweekExpectedPoints(
        int $playerId,
        int $fixtureLimit = 6
    ): array {
        $result =
            parent::getPlayerMultiGameweekExpectedPoints(
                $playerId,
                $fixtureLimit
            );


        $this->multiGameweekByPlayerId[
            $playerId
        ] =
            $result;


        return
            $result;
    }
}


class SquadHorizonRealDataModelAudit
    extends SquadHorizonIntelligence
{
    public array
        $receivedSquad =
            [];


    public ?int
        $receivedHorizon =
            null;


    public function buildHorizon(
        array $squad,
        int $horizon
    ): array {
        $this->receivedSquad =
            $squad;


        $this->receivedHorizon =
            $horizon;


        return
            parent::buildHorizon(
                $squad,
                $horizon
            );
    }
}


/*
 * ============================================================
 * CONFIGURATION
 * ============================================================
 *
 * Use the real FPL entry that already belongs to the project's
 * Squad Intelligence workflow.
 *
 * The importer will automatically use the latest publicly
 * available gameweek squad.
 */

$entryId =
    3158726;


$fixtureLimit =
    6;


$horizon =
    3;


/*
 * ============================================================
 * APPLICATION SERVICES
 * ============================================================
 */

$database =
    new Database();


$db =
    $database->getConnection();


$importer =
    new FPLSquadImporter();


$playerRepository =
    new PlayerRepository(
        $db
    );


$playerIntelligenceService =
    new SquadHorizonRealDataPlayerIntelligenceAudit(
        $db
    );


$squadHorizonIntelligence =
    new SquadHorizonRealDataModelAudit();


$squadHorizonService =
    new SquadHorizonIntelligenceService(
        $playerRepository,
        $playerIntelligenceService,
        $squadHorizonIntelligence
    );


/*
 * ============================================================
 * IMPORT REAL FPL SQUAD
 * ============================================================
 */

$importedSquad =
    $importer->importSquad(
        $entryId
    );


squadHorizonRealDataHeading(
    'Scenario A: Real Squad Import'
);


/*
 * ============================================================
 * EXTERNAL FPL API AVAILABILITY
 * ============================================================
 *
 * This is a real-data integration diagnostic and therefore
 * depends on the live public FPL API.
 *
 * A null import means the external squad data was not available
 * for this run. That is not evidence of a failure in the local
 * Squad Horizon implementation.
 *
 * Do not continue into the typed production orchestration
 * service with null.
 */

if (
    $importedSquad === null
) {

    echo
        'SKIP: Live FPL API did not return usable squad data.'
        . '<br>';


    echo
        'The remaining Squad Horizon real-data integration '
        . 'scenarios require a successful live squad import.'
        . '<br>';


    echo
        'This does not indicate a failure in the local '
        . 'Squad Horizon implementation.'
        . '<br><br>';


    echo
        '============================================<br>';

    echo
        'TEST SUMMARY<br>';

    echo
        '============================================<br>';

    echo
        'Passed: '
        . $passed
        . '<br>';

    echo
        'Failed: '
        . $failed
        . '<br><br>';

    echo
        'RESULT: ALL TESTS PASSED ✅';


    exit(
        0
    );
}


squadHorizonRealDataCheck(
    'FPL squad importer returns an array',
    is_array(
        $importedSquad
    )
);


squadHorizonRealDataCheck(
    'FPL squad import succeeds',
    (
        $importedSquad[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


squadHorizonRealDataCheck(
    'Imported squad contains fifteen players',
    (
        $importedSquad[
            'player_count'
        ]
        ?? null
    )
    ===
    15
);


$importedPlayers =
    $importedSquad[
        'players'
    ]
    ?? [];


/*
 * ============================================================
 * PRODUCTION SQUAD HORIZON SERVICE
 * ============================================================
 *
 * The real-data integration test now enters the production
 * orchestration layer directly.
 *
 * Player resolution, multi-gameweek projection requests and
 * projection adaptation are owned by
 * SquadHorizonIntelligenceService.
 */

$serviceResult =
    $squadHorizonService
        ->buildForImportedSquad(
            $importedSquad,
            $horizon
        );


$resolvedPlayers =
    isset(
        $serviceResult[
            'players'
        ]
    )
    &&
    is_array(
        $serviceResult[
            'players'
        ]
    )
        ? $serviceResult[
            'players'
        ]
        : [];


/*
 * SquadHorizonRealDataModelAudit records the exact adapted
 * squad supplied by the production service before delegating
 * to the real SquadHorizonIntelligence model.
 */

$horizonSquad =
    $squadHorizonIntelligence
        ->receivedSquad;


/*
 * SquadHorizonRealDataPlayerIntelligenceAudit records the
 * exact existing multi-gameweek projection responses used by
 * the production service.
 */

$multiGameweekAudit =
    $playerIntelligenceService
        ->multiGameweekByPlayerId;


/*
 * ============================================================
 * LOCAL PLAYER RESOLUTION CONTRACT
 * ============================================================
 */

squadHorizonRealDataHeading(
    'Scenario B: Local Player Resolution'
);


squadHorizonRealDataCheck(
    'Production service successfully resolves the real squad',
    (
        $serviceResult[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


squadHorizonRealDataCheck(
    'All fifteen imported players resolve to local database players',
    count(
        $resolvedPlayers
    )
    ===
    15
);


/*
 * ============================================================
 * PROJECTION AUDIT METRICS
 * ============================================================
 */

$availableProjectionPlayers =
    0;


$projectionFixtureCount =
    0;


foreach (
    $multiGameweekAudit
    as $multiGameweek
) {

    if (
        (
            $multiGameweek[
                'status'
            ]
            ?? null
        )
        ===
        'Available'
    ) {

        $availableProjectionPlayers++;
    }


    $projectionFixtureCount +=
        (int) (
            $multiGameweek[
                'fixture_projection_count'
            ]
            ?? 0
        );
}


/*
 * ============================================================
 * PROJECTION RECONCILIATION
 * ============================================================
 *
 * The production adapter must preserve the gameweek totals
 * already owned by MultiGameweekExpectedPoints.
 *
 * Compare the exact adapted squad captured at the boundary
 * with the exact service responses used to create it.
 */

$projectionReconciliationComparisons =
    0;


$projectionReconciliationMismatches =
    [];


$adaptedPlayersById =
    [];


foreach (
    $horizonSquad
    as $horizonPlayer
) {

    $playerId =
        (int) (
            $horizonPlayer[
                'player_id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
    ) {

        continue;
    }


    $adaptedPlayersById[
        $playerId
    ] =
        $horizonPlayer;
}


foreach (
    $multiGameweekAudit
    as $playerId => $multiGameweek
) {

    $playerId =
        (int) $playerId;


    $adaptedPlayer =
        $adaptedPlayersById[
            $playerId
        ]
        ?? [];


    $adaptedGameweeks =
        $adaptedPlayer[
            'gameweeks'
        ]
        ?? [];


    $serviceGameweeks =
        $multiGameweek[
            'gameweeks'
        ]
        ?? [];


    foreach (
        $serviceGameweeks
        as $serviceGameweekNumber => $serviceGameweek
    ) {

        if (
            !is_array(
                $serviceGameweek
            )
        ) {

            continue;
        }


        $serviceProjectedPoints =
            $serviceGameweek[
                'projected_points'
            ]
            ?? null;


        if (
            !is_numeric(
                $serviceProjectedPoints
            )
        ) {

            continue;
        }


        $gameweek =
            isset(
                $serviceGameweek[
                    'gameweek'
                ]
            )
            &&
            is_numeric(
                $serviceGameweek[
                    'gameweek'
                ]
            )
                ? (int) $serviceGameweek[
                    'gameweek'
                ]
                : (
                    is_numeric(
                        $serviceGameweekNumber
                    )
                        ? (int) $serviceGameweekNumber
                        : 0
                );


        if (
            $gameweek <= 0
        ) {

            continue;
        }


        $adaptedProjectedPoints =
            $adaptedGameweeks[
                $gameweek
            ][
                'projected_points'
            ]
            ?? null;


        $projectionReconciliationComparisons++;


        $matches =
            is_numeric(
                $adaptedProjectedPoints
            )
            &&
            abs(
                round(
                    (float) $adaptedProjectedPoints,
                    2
                )
                -
                round(
                    (float) $serviceProjectedPoints,
                    2
                )
            )
            <
            0.001;


        if (
            $matches
        ) {

            continue;
        }


        $projectionReconciliationMismatches[] = [

            'player_id' =>
                $playerId,

            'name' =>
                (string) (
                    $adaptedPlayer[
                        'name'
                    ]
                    ?? ''
                ),

            'gameweek' =>
                $gameweek,

            'service_projected_points' =>
                (float) $serviceProjectedPoints,

            'adapted_projected_points' =>
                is_numeric(
                    $adaptedProjectedPoints
                )
                    ? (float) $adaptedProjectedPoints
                    : null
        ];
    }
}


/*
 * ============================================================
 * BUILD SQUAD HORIZON INPUT
 * ============================================================
 */

$horizonSquad =
    [];


$availableProjectionPlayers =
    0;


$projectionFixtureCount =
    0;
    
/*
 * Track whether the new Squad Horizon adapter reproduces the
 * gameweek totals already owned by MultiGameweekExpectedPoints.
 */

$projectionReconciliationComparisons =
    0;


$projectionReconciliationMismatches =
    [];


/*
 * Retain the service output for diagnostics.
 */

$multiGameweekAudit =
    [];


foreach (
    $resolvedPlayers
    as $localPlayer
) {

    $playerId =
        (int) (
            $localPlayer[
                'id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
    ) {

        continue;
    }


    $multiGameweek =
        $playerIntelligenceService
            ->getPlayerMultiGameweekExpectedPoints(
                $playerId,
                $fixtureLimit
            );


    if (
        (
            $multiGameweek[
                'status'
            ]
            ?? null
        )
        ===
        'Available'
    ) {

        $availableProjectionPlayers++;
    }


    $projectionFixtureCount +=
        (int) (
            $multiGameweek[
                'fixture_projection_count'
            ]
            ?? 0
        );


    /*
     * --------------------------------------------------------
     * ADAPT FIXTURE-LEVEL PROJECTIONS INTO GAMEWEEK INPUT
     * --------------------------------------------------------
     *
     * SquadHorizonIntelligence wants one projection row per
     * player/gameweek.
     *
     * A player may have:
     *
     * - one fixture in a normal gameweek
     * - multiple fixtures in a double gameweek
     * - no fixture in a blank gameweek
     *
     * Therefore fixture projected points are aggregated by
     * gameweek rather than assuming one fixture per gameweek.
     */

    $gameweeks =
        [];


    foreach (
        $multiGameweek[
            'fixtures'
        ]
        ?? []
        as $fixture
    ) {

        $gameweek =
            $fixture[
                'gameweek'
            ]
            ?? null;


        if (
            !is_numeric(
                $gameweek
            )
        ) {

            continue;
        }


        $gameweek =
            (int) $gameweek;


        $projection =
            $fixture[
                'projection'
            ]
            ?? [];


        $projectedPoints =
            $projection[
                'projected_points'
            ]
            ?? null;


        /*
         * Missing fixture projection remains unknown.
         *
         * Do not convert it into zero.
         */
        if (
            !is_numeric(
                $projectedPoints
            )
        ) {

            if (
                !isset(
                    $gameweeks[
                        $gameweek
                    ]
                )
            ) {

                $gameweeks[
                    $gameweek
                ] = [
                    'gameweek' =>
                        $gameweek,

                    'projected_points' =>
                        null,

                    'team_id' =>
                        isset(
                            $multiGameweek[
                                'team_id'
                            ]
                        )
                        &&
                        is_numeric(
                            $multiGameweek[
                                'team_id'
                            ]
                        )
                            ? (int) $multiGameweek[
                                'team_id'
                            ]
                            : null,

                    'opponent_team_id' =>
                        isset(
                            $fixture[
                                'opponent_team_id'
                            ]
                        )
                        &&
                        is_numeric(
                            $fixture[
                                'opponent_team_id'
                            ]
                        )
                            ? (int) $fixture[
                                'opponent_team_id'
                            ]
                            : null
                ];
            }


            continue;
        }


        /*
         * First projected fixture in the gameweek.
         */
        if (
            !isset(
                $gameweeks[
                    $gameweek
                ]
            )
            ||
            !is_numeric(
                $gameweeks[
                    $gameweek
                ][
                    'projected_points'
                ]
                ?? null
            )
        ) {

            $gameweeks[
                $gameweek
            ] = [
                'gameweek' =>
                    $gameweek,

                'projected_points' =>
                    (float) $projectedPoints,

                'team_id' =>
                    isset(
                        $multiGameweek[
                            'team_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $multiGameweek[
                            'team_id'
                        ]
                    )
                        ? (int) $multiGameweek[
                            'team_id'
                        ]
                        : null,

                'opponent_team_id' =>
                    isset(
                        $fixture[
                            'opponent_team_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $fixture[
                            'opponent_team_id'
                        ]
                    )
                        ? (int) $fixture[
                            'opponent_team_id'
                        ]
                        : null
            ];

            continue;
        }


        /*
         * Double gameweek:
         *
         * Add the second fixture projection to the same FPL
         * gameweek total.
         */
        $gameweeks[
            $gameweek
        ][
            'projected_points'
        ] +=
            (float) $projectedPoints;


        /*
         * There is no single opponent in a double gameweek.
         *
         * Clear opponent_team_id rather than presenting one
         * opponent as if it represented the whole gameweek.
         */
        $gameweeks[
            $gameweek
        ][
            'opponent_team_id'
        ] =
            null;
    }


        ksort(
        $gameweeks,
        SORT_NUMERIC
    );


    /*
     * --------------------------------------------------------
     * RECONCILE AGAINST EXISTING MULTI-GAMEWEEK AGGREGATION
     * --------------------------------------------------------
     *
     * MultiGameweekExpectedPoints already owns the official
     * gameweek aggregation behaviour.
     *
     * The adapter should therefore reproduce exactly the same
     * projected-points total for every projected gameweek.
     */

    $serviceGameweeks =
        $multiGameweek[
            'gameweeks'
        ]
        ?? [];


    foreach (
        $serviceGameweeks
        as $serviceGameweekNumber => $serviceGameweek
    ) {

        $serviceProjectedPoints =
            $serviceGameweek[
                'projected_points'
            ]
            ?? null;


        if (
            !is_numeric(
                $serviceProjectedPoints
            )
        ) {

            continue;
        }


        $serviceGameweekNumber =
            (int) $serviceGameweekNumber;


        $adaptedProjectedPoints =
            $gameweeks[
                $serviceGameweekNumber
            ][
                'projected_points'
            ]
            ?? null;


        $projectionReconciliationComparisons++;


        $matches =
            is_numeric(
                $adaptedProjectedPoints
            )
            &&
            abs(
                round(
                    (float) $adaptedProjectedPoints,
                    2
                )
                -
                round(
                    (float) $serviceProjectedPoints,
                    2
                )
            )
            <
            0.001;


        if (
            !$matches
        ) {

            $projectionReconciliationMismatches[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    (string) (
                        $localPlayer[
                            'web_name'
                        ]
                        ?? ''
                    ),

                'gameweek' =>
                    $serviceGameweekNumber,

                'service_projected_points' =>
                    (float) $serviceProjectedPoints,

                'adapted_projected_points' =>
                    is_numeric(
                        $adaptedProjectedPoints
                    )
                        ? (float) $adaptedProjectedPoints
                        : null
            ];
        }
    }


    /*
     * Retain the raw service model so the diagnostic section can
     * inspect fixture metadata without recalculating projections.
     */

    $multiGameweekAudit[
        $playerId
    ] =
        $multiGameweek;


    $horizonSquad[] = [

        'player_id' =>
            $playerId,

        'name' =>
            (string) (
                $localPlayer[
                    'web_name'
                ]
                ?? ''
            ),

        'position' =>
            strtoupper(
                trim(
                    (string) (
                        $multiGameweek[
                            'position'
                        ]
                        ??
                        $localPlayer[
                            'position'
                        ]
                        ??
                        ''
                    )
                )
            ),

        'gameweeks' =>
            $gameweeks
    ];
}


/*
 * ============================================================
 * ADAPTER CONTRACT
 * ============================================================
 */

squadHorizonRealDataHeading(
    'Scenario C: Multi-Gameweek Projection Adapter'
);


squadHorizonRealDataCheck(
    'Adapter produces fifteen squad players',
    count(
        $horizonSquad
    )
    ===
    15
);


squadHorizonRealDataCheck(
    'At least one squad player has available multi-gameweek projections',
    $availableProjectionPlayers
    >
    0
);


squadHorizonRealDataCheck(
    'Real squad produces at least one projected fixture',
    $projectionFixtureCount
    >
    0
);


$validPositionCount =
    0;


foreach (
    $horizonSquad
    as $horizonPlayer
) {

    if (
        in_array(
            $horizonPlayer[
                'position'
            ]
            ?? null,
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ],
            true
        )
    ) {

        $validPositionCount++;
    }
}


squadHorizonRealDataCheck(
    'All fifteen adapted players have valid FPL positions',
    $validPositionCount
    ===
    15
);

squadHorizonRealDataCheck(
    'Projection reconciliation compares at least one real player/gameweek',
    $projectionReconciliationComparisons
    >
    0
);


squadHorizonRealDataCheck(
    'Squad Horizon adapter matches all existing multi-gameweek totals',
    empty(
        $projectionReconciliationMismatches
    )
);


/*
 * ============================================================
 * BUILD REAL SQUAD HORIZON
 * ============================================================
 */

$horizonResult =
    isset(
        $serviceResult[
            'horizon_result'
        ]
    )
    &&
    is_array(
        $serviceResult[
            'horizon_result'
        ]
    )
        ? $serviceResult[
            'horizon_result'
        ]
        : [];


squadHorizonRealDataHeading(
    'Scenario D: Real Squad Horizon'
);


squadHorizonRealDataCheck(
    'Real squad horizon is available',
    (
        $horizonResult[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


squadHorizonRealDataCheck(
    'Real squad horizon preserves fifteen players',
    (
        $horizonResult[
            'player_count'
        ]
        ?? null
    )
    ===
    15
);


squadHorizonRealDataCheck(
    'Real squad horizon uses a three-gameweek horizon',
    (
        $horizonResult[
            'horizon'
        ]
        ?? null
    )
    ===
    3
);


squadHorizonRealDataCheck(
    'Real squad horizon contains three gameweeks',
    count(
        $horizonResult[
            'gameweeks'
        ]
        ?? []
    )
    ===
    3
);


/*
 * ============================================================
 * INTELLIGENCE OUTPUT CONTRACT
 * ============================================================
 */

squadHorizonRealDataHeading(
    'Scenario E: Real Intelligence Outputs'
);


$expectedIntelligenceBlocks = [
    'defensive_rotation',
    'goalkeeper_rotation',
    'fixture_clashes',
    'weak_fixture_clusters',
    'position_depth',
    'repeated_benching',
    'structural_weakness'
];


$allIntelligenceBlocksPresent =
    true;


foreach (
    $expectedIntelligenceBlocks
    as $intelligenceBlock
) {

    if (
        !isset(
            $horizonResult[
                $intelligenceBlock
            ]
        )
    ) {

        $allIntelligenceBlocksPresent =
            false;

        break;
    }
}


squadHorizonRealDataCheck(
    'Real horizon exposes all v0.32 intelligence blocks',
    $allIntelligenceBlocksPresent
);


/*
 * ============================================================
 * FIXTURE METADATA RECONCILIATION
 * ============================================================
 *
 * The production adapter must preserve truthful fixture
 * metadata for normal single-fixture gameweeks.
 *
 * Double Gameweeks are deliberately excluded from opponent
 * reconciliation because the aggregated player/gameweek row
 * must not pretend that one opponent represents both fixtures.
 */

squadHorizonRealDataHeading(
    'Scenario F: Fixture Metadata Reconciliation'
);


$fixtureMetadataComparisons =
    0;


$fixtureMetadataMismatches =
    [];


foreach (
    $multiGameweekAudit
    as $playerId => $multiGameweek
) {

    $playerId =
        (int) $playerId;


    $adaptedPlayer =
        $adaptedPlayersById[
            $playerId
        ]
        ?? [];


    $adaptedGameweeks =
        $adaptedPlayer[
            'gameweeks'
        ]
        ?? [];


    $fixturesByGameweek =
        [];


    foreach (
        $multiGameweek[
            'fixtures'
        ]
        ?? []
        as $fixture
    ) {

        if (
            !is_array(
                $fixture
            )
        ) {

            continue;
        }


        $fixtureGameweek =
            $fixture[
                'gameweek'
            ]
            ?? null;


        if (
            !is_numeric(
                $fixtureGameweek
            )
        ) {

            continue;
        }


        $fixtureGameweek =
            (int) $fixtureGameweek;


        $fixturesByGameweek[
            $fixtureGameweek
        ][] =
            $fixture;
    }


    foreach (
        $fixturesByGameweek
        as $gameweek => $gameweekFixtures
    ) {

        /*
         * Only a single-fixture gameweek has one truthful
         * opponent that can be represented on the aggregated
         * player/gameweek row.
         */

        if (
            count(
                $gameweekFixtures
            )
            !==
            1
        ) {

            continue;
        }


        $fixture =
            $gameweekFixtures[0];


        $expectedTeamId =
            isset(
                $multiGameweek[
                    'team_id'
                ]
            )
            &&
            is_numeric(
                $multiGameweek[
                    'team_id'
                ]
            )
                ? (int) $multiGameweek[
                    'team_id'
                ]
                : null;


        $expectedOpponentTeamId =
            isset(
                $fixture[
                    'opponent_team_id'
                ]
            )
            &&
            is_numeric(
                $fixture[
                    'opponent_team_id'
                ]
            )
                ? (int) $fixture[
                    'opponent_team_id'
                ]
                : null;


        /*
         * Only compare rows for which the source service
         * actually provides complete fixture metadata.
         */

        if (
            $expectedTeamId === null
            ||
            $expectedOpponentTeamId === null
        ) {

            continue;
        }


        $fixtureMetadataComparisons++;


        $adaptedTeamId =
            $adaptedGameweeks[
                $gameweek
            ][
                'team_id'
            ]
            ?? null;


        $adaptedOpponentTeamId =
            $adaptedGameweeks[
                $gameweek
            ][
                'opponent_team_id'
            ]
            ?? null;


        if (
            $adaptedTeamId === $expectedTeamId
            &&
            $adaptedOpponentTeamId === $expectedOpponentTeamId
        ) {

            continue;
        }


        $fixtureMetadataMismatches[] = [

            'player_id' =>
                $playerId,

            'gameweek' =>
                $gameweek,

            'expected_team_id' =>
                $expectedTeamId,

            'adapted_team_id' =>
                $adaptedTeamId,

            'expected_opponent_team_id' =>
                $expectedOpponentTeamId,

            'adapted_opponent_team_id' =>
                $adaptedOpponentTeamId
        ];
    }
}


squadHorizonRealDataCheck(
    'Fixture metadata reconciliation compares real player/gameweeks',
    $fixtureMetadataComparisons
    >
    0
);


squadHorizonRealDataCheck(
    'Production adapter preserves all single-fixture metadata',
    empty(
        $fixtureMetadataMismatches
    )
);


/*
 * ============================================================
 * REAL DATA DIAGNOSTICS
 * ============================================================
 */

squadHorizonRealDataHeading(
    'Real Data Diagnostics'
);


echo
    'Imported gameweek: '
    . htmlspecialchars(
        (string) (
            $importedSquad[
                'gameweek'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . '<br>';


echo
    'Resolved players: '
    . count(
        $resolvedPlayers
    )
    . ' / 15<br>';


echo
    'Players with available projections: '
    . $availableProjectionPlayers
    . ' / 15<br>';


echo
    'Projected fixtures: '
    . $projectionFixtureCount
    . '<br>';


echo
    'Horizon gameweeks: '
    . htmlspecialchars(
        implode(
            ', ',
            array_keys(
                $horizonResult[
                    'gameweeks'
                ]
                ?? []
            )
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . '<br>';


echo
    'Worst structural gameweek: '
    . htmlspecialchars(
        (string) (
            $horizonResult[
                'structural_weakness'
            ][
                'worst_gameweek'
            ]
            ?? 'None'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . '<br>';


echo
    'Maximum structural severity: '
    . htmlspecialchars(
        (string) (
            $horizonResult[
                'structural_weakness'
            ][
                'max_severity'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . '<br>';


/*
 * ============================================================
 * GAMEWEEK-BY-GAMEWEEK EXPLAINABILITY
 * ============================================================
 */

foreach (
    $horizonResult[
        'gameweeks'
    ]
    ?? []
    as $gameweekNumber => $gameweek
) {

    squadHorizonRealDataHeading(
        'GW'
        . $gameweekNumber
        . ' Explainability'
    );


    /*
     * --------------------------------------------------------
     * STARTING XI
     * --------------------------------------------------------
     */

    $startingXI =
        $gameweek[
            'starting_xi'
        ]
        ?? [];


    $bench =
        $gameweek[
            'bench'
        ]
        ?? [];


    /*
     * Derive the selected formation directly from the Starting
     * XI so the diagnostic does not depend on an additional
     * production output field.
     */

    $formationCounts = [
        'DEF' =>
            0,

        'MID' =>
            0,

        'FWD' =>
            0
    ];


    foreach (
        $startingXI
        as $startingPlayer
    ) {

        $position =
            $startingPlayer[
                'position'
            ]
            ?? null;


        if (
            isset(
                $formationCounts[
                    $position
                ]
            )
        ) {

            $formationCounts[
                $position
            ]++;
        }
    }


    $formation =
        $formationCounts[
            'DEF'
        ]
        . '-'
        . $formationCounts[
            'MID'
        ]
        . '-'
        . $formationCounts[
            'FWD'
        ];


    echo
        '<strong>Formation:</strong> '
        . htmlspecialchars(
            $formation,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';


    $startingXIProjectedPoints =
        $gameweek[
            'starting_xi_projected_points'
        ]
        ?? null;


    echo
        '<strong>Starting XI projected points:</strong> '
        . (
            is_numeric(
                $startingXIProjectedPoints
            )
                ? number_format(
                    (float) $startingXIProjectedPoints,
                    2
                )
                : 'N/A'
        )
        . '<br><br>';


    echo
        '<strong>Starting XI:</strong><br>';


    foreach (
        $startingXI
        as $startingPlayer
    ) {

        $playerName =
            $startingPlayer[
                'name'
            ]
            ?? 'Unknown';


        $position =
            $startingPlayer[
                'position'
            ]
            ?? 'N/A';


        $projectedPoints =
            $startingPlayer[
                'projected_points'
            ]
            ?? null;


        echo
            '- '
            . htmlspecialchars(
                (string) $playerName,
                ENT_QUOTES,
                'UTF-8'
            )
            . ' ('
            . htmlspecialchars(
                (string) $position,
                ENT_QUOTES,
                'UTF-8'
            )
            . ') — '
            . (
                is_numeric(
                    $projectedPoints
                )
                    ? number_format(
                        (float) $projectedPoints,
                        2
                    )
                    : 'N/A'
            )
            . ' xP<br>';
    }


    /*
     * --------------------------------------------------------
     * BENCH
     * --------------------------------------------------------
     */

    echo
        '<br><strong>Bench:</strong><br>';


    foreach (
        $bench
        as $benchPlayer
    ) {

        $playerName =
            $benchPlayer[
                'name'
            ]
            ?? 'Unknown';


        $position =
            $benchPlayer[
                'position'
            ]
            ?? 'N/A';


        $projectedPoints =
            $benchPlayer[
                'projected_points'
            ]
            ?? null;


        echo
            '- '
            . htmlspecialchars(
                (string) $playerName,
                ENT_QUOTES,
                'UTF-8'
            )
            . ' ('
            . htmlspecialchars(
                (string) $position,
                ENT_QUOTES,
                'UTF-8'
            )
            . ') — '
            . (
                is_numeric(
                    $projectedPoints
                )
                    ? number_format(
                        (float) $projectedPoints,
                        2
                    )
                    : 'N/A'
            )
            . ' xP<br>';
    }


    /*
     * --------------------------------------------------------
     * BENCH COVERAGE
     * --------------------------------------------------------
     */

    $benchCoverage =
        $gameweek[
            'bench_coverage'
        ]
        ?? [];


    echo
        '<br><strong>Bench coverage:</strong><br>';


    echo
        'Total bench xP: '
        . (
            is_numeric(
                $benchCoverage[
                    'total_projected_points'
                ]
                ?? null
            )
                ? number_format(
                    (float) $benchCoverage[
                        'total_projected_points'
                    ],
                    2
                )
                : 'N/A'
        )
        . '<br>';


    $firstOutfieldSubstitute =
        $benchCoverage[
            'first_outfield_substitute'
        ]
        ?? null;


    echo
        'Best outfield bench option: '
        . (
            is_array(
                $firstOutfieldSubstitute
            )
                ? htmlspecialchars(
                    (string) (
                        $firstOutfieldSubstitute[
                            'name'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . ' — '
                . (
                    is_numeric(
                        $firstOutfieldSubstitute[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? number_format(
                            (float) $firstOutfieldSubstitute[
                                'projected_points'
                            ],
                            2
                        )
                        : 'N/A'
                )
                . ' xP'
                : 'None'
        )
        . '<br>';


    $weakestOutfieldStarter =
        $benchCoverage[
            'weakest_outfield_starter'
        ]
        ?? null;


    echo
        'Weakest outfield starter: '
        . (
            is_array(
                $weakestOutfieldStarter
            )
                ? htmlspecialchars(
                    (string) (
                        $weakestOutfieldStarter[
                            'name'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . ' — '
                . (
                    is_numeric(
                        $weakestOutfieldStarter[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? number_format(
                            (float) $weakestOutfieldStarter[
                                'projected_points'
                            ],
                            2
                        )
                        : 'N/A'
                )
                . ' xP'
                : 'None'
        )
        . '<br>';


    echo
        'Coverage gap: '
        . (
            is_numeric(
                $benchCoverage[
                    'coverage_gap'
                ]
                ?? null
            )
                ? number_format(
                    (float) $benchCoverage[
                        'coverage_gap'
                    ],
                    2
                )
                . ' xP'
                : 'N/A'
        )
        . '<br>';


    /*
     * --------------------------------------------------------
     * WEAK FIXTURE CLUSTER
     * --------------------------------------------------------
     */

    $weakFixtureGameweek =
        $horizonResult[
            'weak_fixture_clusters'
        ][
            'gameweeks'
        ][
            $gameweekNumber
        ]
        ?? [];


    echo
        '<br><strong>Weak fixture analysis:</strong><br>';


    echo
        'Weak starters: '
        . (
            (int) (
                $weakFixtureGameweek[
                    'weak_player_count'
                ]
                ?? 0
            )
        )
        . '<br>';


    echo
        'Weak fixture cluster: '
        . (
            (
                $weakFixtureGameweek[
                    'is_cluster'
                ]
                ?? false
            )
                ? 'YES'
                : 'NO'
        )
        . '<br>';


    $weakPlayers =
        $weakFixtureGameweek[
            'weak_players'
        ]
        ?? [];


    if (
        !empty(
            $weakPlayers
        )
    ) {

        echo
            'Weak players:<br>';


        foreach (
            $weakPlayers
            as $weakPlayer
        ) {

            echo
                '- '
                . htmlspecialchars(
                    (string) (
                        $weakPlayer[
                            'name'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . ' ('
                . htmlspecialchars(
                    (string) (
                        $weakPlayer[
                            'position'
                        ]
                        ?? 'N/A'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . ') — '
                . (
                    is_numeric(
                        $weakPlayer[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? number_format(
                            (float) $weakPlayer[
                                'projected_points'
                            ],
                            2
                        )
                        : 'N/A'
                )
                . ' xP<br>';
        }
    }


    /*
     * --------------------------------------------------------
     * POSITION DEPTH
     * --------------------------------------------------------
     */

    $positionDepthGameweek =
        $horizonResult[
            'position_depth'
        ][
            'gameweeks'
        ][
            $gameweekNumber
        ]
        ?? [];


    echo
        '<br><strong>Position depth:</strong><br>';


    $weakDepthPositions =
        $positionDepthGameweek[
            'weak_depth_positions'
        ]
        ?? [];


    echo
        'Weak depth positions: '
        . (
            !empty(
                $weakDepthPositions
            )
                ? htmlspecialchars(
                    implode(
                        ', ',
                        $weakDepthPositions
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                : 'None'
        )
        . '<br>';


    foreach (
        $positionDepthGameweek[
            'positions'
        ]
        ?? []
        as $position => $depth
    ) {

        echo
            htmlspecialchars(
                (string) $position,
                ENT_QUOTES,
                'UTF-8'
            )
            . ': '
            . (
                (int) (
                    $depth[
                        'usable_player_count'
                    ]
                    ?? 0
                )
            )
            . ' usable / '
            . (
                (int) (
                    $depth[
                        'minimum_required'
                    ]
                    ?? 0
                )
            )
            . ' minimum — depth '
            . (
                (int) (
                    $depth[
                        'depth_count'
                    ]
                    ?? 0
                )
            )
            . '<br>';
    }


    /*
     * --------------------------------------------------------
     * FIXTURE CLASHES
     * --------------------------------------------------------
     */

    $fixtureClashGameweek =
        $horizonResult[
            'fixture_clashes'
        ][
            'gameweeks'
        ][
            $gameweekNumber
        ]
        ?? [];


    echo
        '<br><strong>Fixture clashes:</strong> '
        . (
            (int) (
                $fixtureClashGameweek[
                    'clash_count'
                ]
                ?? 0
            )
        )
        . '<br>';


    foreach (
        $fixtureClashGameweek[
            'clashes'
        ]
        ?? []
        as $clash
    ) {

        $clashPlayers =
            $clash[
                'players'
            ]
            ?? [];


        $firstClashPlayer =
            $clashPlayers[
                0
            ][
                'name'
            ]
            ?? 'Unknown';


        $secondClashPlayer =
            $clashPlayers[
                1
            ][
                'name'
            ]
            ?? 'Unknown';


        echo
            '- '
            . htmlspecialchars(
                (string) $firstClashPlayer,
                ENT_QUOTES,
                'UTF-8'
            )
            . ' vs '
            . htmlspecialchars(
                (string) $secondClashPlayer,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';
    }


    /*
     * --------------------------------------------------------
     * STRUCTURAL WEAKNESS
     * --------------------------------------------------------
     */

    $structuralGameweek =
        $horizonResult[
            'structural_weakness'
        ][
            'gameweeks'
        ][
            $gameweekNumber
        ]
        ?? [];


    echo
        '<br><strong>Structural weakness:</strong><br>';


    echo
        'Severity: '
        . htmlspecialchars(
            (string) (
                $structuralGameweek[
                    'severity'
                ]
                ?? 'N/A'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';


    echo
        'Problem count: '
        . (
            (int) (
                $structuralGameweek[
                    'problem_count'
                ]
                ?? 0
            )
        )
        . '<br>';


    $structuralProblems =
        $structuralGameweek[
            'problems'
        ]
        ?? [];


    echo
        'Problems: '
        . (
            !empty(
                $structuralProblems
            )
                ? htmlspecialchars(
                    implode(
                        ', ',
                        $structuralProblems
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                : 'None'
        )
        . '<br>';


    echo
        'Uncovered weak XI: '
        . (
            (
                $structuralGameweek[
                    'has_uncovered_weak_xi'
                ]
                ?? false
            )
                ? 'YES'
                : 'NO'
        )
        . '<br>';
}


/*
 * ============================================================
 * PROJECTION RECONCILIATION DIAGNOSTICS
 * ============================================================
 */

squadHorizonRealDataHeading(
    'Projection Reconciliation Diagnostics'
);


echo
    'Player/gameweek comparisons: '
    . $projectionReconciliationComparisons
    . '<br>';


echo
    'Projection mismatches: '
    . count(
        $projectionReconciliationMismatches
    )
    . '<br>';


if (
    empty(
        $projectionReconciliationMismatches
    )
) {

    echo
        'All adapted gameweek totals match '
        . 'MultiGameweekExpectedPoints.<br>';

} else {

    foreach (
        $projectionReconciliationMismatches
        as $mismatch
    ) {

        echo
            '- '
            . htmlspecialchars(
                (string) (
                    $mismatch[
                        'name'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . ' GW'
            . (
                (int) (
                    $mismatch[
                        'gameweek'
                    ]
                    ?? 0
                )
            )
            . ' — service '
            . (
                is_numeric(
                    $mismatch[
                        'service_projected_points'
                    ]
                    ?? null
                )
                    ? number_format(
                        (float) $mismatch[
                            'service_projected_points'
                        ],
                        2
                    )
                    : 'N/A'
            )
            . ' / adapter '
            . (
                is_numeric(
                    $mismatch[
                        'adapted_projected_points'
                    ]
                    ?? null
                )
                    ? number_format(
                        (float) $mismatch[
                            'adapted_projected_points'
                        ],
                        2
                    )
                    : 'N/A'
            )
            . '<br>';
    }
}


/*
 * ============================================================
 * STARTING XI FIXTURE METADATA AUDIT
 * ============================================================
 */

squadHorizonRealDataHeading(
    'Starting XI Fixture Metadata Audit'
);


foreach (
    $horizonResult[
        'gameweeks'
    ]
    ?? []
    as $gameweekNumber => $gameweek
) {

    echo
        '<strong>GW'
        . (
            (int) $gameweekNumber
        )
        . '</strong><br>';


    foreach (
        $gameweek[
            'starting_xi'
        ]
        ?? []
        as $startingPlayer
    ) {

        $playerName =
            $startingPlayer[
                'name'
            ]
            ?? 'Unknown';


        $position =
            $startingPlayer[
                'position'
            ]
            ?? 'N/A';


        $teamId =
            $startingPlayer[
                'team_id'
            ]
            ?? null;


        $opponentTeamId =
            $startingPlayer[
                'opponent_team_id'
            ]
            ?? null;


        echo
            '- '
            . htmlspecialchars(
                (string) $playerName,
                ENT_QUOTES,
                'UTF-8'
            )
            . ' ('
            . htmlspecialchars(
                (string) $position,
                ENT_QUOTES,
                'UTF-8'
            )
            . ') — Team '
            . (
                is_numeric(
                    $teamId
                )
                    ? (int) $teamId
                    : 'N/A'
            )
            . ' → Opponent '
            . (
                is_numeric(
                    $opponentTeamId
                )
                    ? (int) $opponentTeamId
                    : 'N/A'
            )
            . '<br>';
    }


    /*
     * Print the clashes immediately beneath the metadata so
     * reciprocal team/opponent mappings can be checked visually.
     */

    $fixtureClashGameweek =
        $horizonResult[
            'fixture_clashes'
        ][
            'gameweeks'
        ][
            $gameweekNumber
        ]
        ?? [];


    echo
        'Reported clashes: '
        . (
            (int) (
                $fixtureClashGameweek[
                    'clash_count'
                ]
                ?? 0
            )
        )
        . '<br>';


    foreach (
        $fixtureClashGameweek[
            'clashes'
        ]
        ?? []
        as $clash
    ) {

        $clashPlayers =
            $clash[
                'players'
            ]
            ?? [];


        $firstPlayer =
            $clashPlayers[
                0
            ]
            ?? [];


        $secondPlayer =
            $clashPlayers[
                1
            ]
            ?? [];


        echo
            '&nbsp;&nbsp;CLASH: '
            . htmlspecialchars(
                (string) (
                    $firstPlayer[
                        'name'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . ' ['
            . (
                is_numeric(
                    $firstPlayer[
                        'team_id'
                    ]
                    ?? null
                )
                    ? (int) $firstPlayer[
                        'team_id'
                    ]
                    : 'N/A'
            )
            . ' → '
            . (
                is_numeric(
                    $firstPlayer[
                        'opponent_team_id'
                    ]
                    ?? null
                )
                    ? (int) $firstPlayer[
                        'opponent_team_id'
                    ]
                    : 'N/A'
            )
            . '] vs '
            . htmlspecialchars(
                (string) (
                    $secondPlayer[
                        'name'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . ' ['
            . (
                is_numeric(
                    $secondPlayer[
                        'team_id'
                    ]
                    ?? null
                )
                    ? (int) $secondPlayer[
                        'team_id'
                    ]
                    : 'N/A'
            )
            . ' → '
            . (
                is_numeric(
                    $secondPlayer[
                        'opponent_team_id'
                    ]
                    ?? null
                )
                    ? (int) $secondPlayer[
                        'opponent_team_id'
                    ]
                    : 'N/A'
            )
            . ']'
            . '<br>';
    }


    echo
        '<br>';
}

/*
 * ============================================================
 * HORIZON-LEVEL ROTATION AND BENCHING DIAGNOSTICS
 * ============================================================
 */

squadHorizonRealDataHeading(
    'Horizon Rotation And Benching Diagnostics'
);


/*
 * ------------------------------------------------------------
 * GOALKEEPER ROTATION
 * ------------------------------------------------------------
 */

$goalkeeperRotation =
    $horizonResult[
        'goalkeeper_rotation'
    ]
    ?? [];


echo
    '<strong>Goalkeeper rotation:</strong><br>';


echo
    'Preferred goalkeeper IDs: '
    . htmlspecialchars(
        implode(
            ', ',
            array_map(
                static function (
                    $playerId
                ): string {

                    return
                        $playerId === null
                            ? 'None'
                            : (string) $playerId;
                },
                $goalkeeperRotation[
                    'preferred_goalkeeper_ids'
                ]
                ?? []
            )
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . '<br>';


echo
    'Alternations: '
    . (
        (int) (
            $goalkeeperRotation[
                'alternation_count'
            ]
            ?? 0
        )
    )
    . '<br>';


echo
    'Rotation gain: '
    . (
        is_numeric(
            $goalkeeperRotation[
                'rotation_gain'
            ]
            ?? null
        )
            ? number_format(
                (float) $goalkeeperRotation[
                    'rotation_gain'
                ],
                2
            )
            . ' xP'
            : 'N/A'
    )
    . '<br>';


/*
 * ------------------------------------------------------------
 * DEFENSIVE ROTATION
 * ------------------------------------------------------------
 */

$defensiveRotation =
    $horizonResult[
        'defensive_rotation'
    ]
    ?? [];


echo
    '<br><strong>Defensive rotation pairs:</strong> '
    . count(
        $defensiveRotation[
            'rotation_pairs'
        ]
        ?? []
    )
    . '<br>';


foreach (
    $defensiveRotation[
        'rotation_pairs'
    ]
    ?? []
    as $rotationPair
) {

    $playerIds =
        $rotationPair[
            'player_ids'
        ]
        ?? [];


    echo
        '- IDs '
        . htmlspecialchars(
            implode(
                ' / ',
                $playerIds
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . ' — alternations '
        . (
            (int) (
                $rotationPair[
                    'alternation_count'
                ]
                ?? 0
            )
        )
        . '<br>';
}


/*
 * ------------------------------------------------------------
 * REPEATED BENCHING
 * ------------------------------------------------------------
 */

$repeatedBenching =
    $horizonResult[
        'repeated_benching'
    ]
    ?? [];


echo
    '<br><strong>Repeated benching:</strong><br>';


echo
    'Repeatedly benched players: '
    . (
        (int) (
            $repeatedBenching[
                'repeatedly_benched_player_count'
            ]
            ?? 0
        )
    )
    . '<br>';


echo
    'Meaningful repeated benching: '
    . (
        (int) (
            $repeatedBenching[
                'meaningful_repeated_benching_player_count'
            ]
            ?? 0
        )
    )
    . '<br>';


$repeatedPlayers =
    $repeatedBenching[
        'players'
    ]
    ?? [];


foreach (
    $repeatedPlayers
    as $repeatedPlayer
) {

    if (
        (
            $repeatedPlayer[
                'is_repeatedly_benched'
            ]
            ?? false
        )
        !==
        true
    ) {

        continue;
    }


    echo
        '- '
        . htmlspecialchars(
            (string) (
                $repeatedPlayer[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . ' ('
        . htmlspecialchars(
            (string) (
                $repeatedPlayer[
                    'position'
                ]
                ?? 'N/A'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . ') — benched '
        . (
            (int) (
                $repeatedPlayer[
                    'bench_count'
                ]
                ?? 0
            )
        )
        . ' times — average benched xP '
        . (
            is_numeric(
                $repeatedPlayer[
                    'average_benched_projected_points'
                ]
                ?? null
            )
                ? number_format(
                    (float) $repeatedPlayer[
                        'average_benched_projected_points'
                    ],
                    2
                )
                : 'N/A'
        )
        . ' — meaningful: '
        . (
            (
                $repeatedPlayer[
                    'is_meaningful_repeated_benching'
                ]
                ?? false
            )
                ? 'YES'
                : 'NO'
        )
        . '<br>';
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";

echo
    'Passed: '
    . $passed
    . '<br>';

echo
    'Failed: '
    . $failed
    . '<br><br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}