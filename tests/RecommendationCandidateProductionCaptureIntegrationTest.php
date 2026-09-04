<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE PRODUCTION CAPTURE
 * INTEGRATION TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This test exercises the complete database-backed production
 * candidate-capture path:
 *
 * GameweekRepository
 *      ↓
 * RecommendationCandidateProductionCapture
 *      ↓
 * RecommendationCandidateProductionService
 *      ↓
 * PlayerIntelligenceService
 *      ↓
 * PlayerProjectionEvidence
 * ChipRecommendationEvidence
 *      ↓
 * RecommendationCandidateCaptureService
 *      ↓
 * RecommendationCandidateRepository
 *      ↓
 * recommendation_candidates
 *
 * A synthetic positive FPL entry ID is used so genuine manager
 * recommendation history is never modified.
 */


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed =
    0;


$failed =
    0;


function productionCaptureIntegrationAssert(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;


        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;


    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


function productionCaptureIntegrationSection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * CONTROLLED CHIP DECISIONS
 * ============================================================
 *
 * These are already-calculated production-shaped chip results.
 *
 * The production capture path must preserve them. It must not
 * calculate or reinterpret Chip Intelligence itself.
 */

function buildProductionCaptureIntegrationChipResults(): array
{
    $wildcardDecision =
        new ChipDecision(
            'Wildcard',
            'Consider',
            0.72,
            'Controlled Wildcard recommendation evidence.'
        );


    $freeHitDecision =
        new ChipDecision(
            'Free Hit',
            'Hold',
            0.81,
            'Controlled Free Hit recommendation evidence.'
        );


    $benchBoostDecision =
        new ChipDecision(
            'Bench Boost',
            'Use',
            0.76,
            'Controlled Bench Boost recommendation evidence.'
        );


    $tripleCaptainDecision =
        new ChipDecision(
            'Triple Captain',
            'Consider',
            0.69,
            'Controlled Triple Captain recommendation evidence.'
        );


    return [

        'wildcard' => [

            'timing_result' => [

                'current_squad_projected_points' =>
                    61.25,

                'wildcard_squad_projected_points' =>
                    67.75,

                'projected_points_gain' =>
                    6.50,

                'future_projected_gain' =>
                    4.25,

                'decision' =>
                    $wildcardDecision
            ]
        ],


        'free_hit' => [

            'value_result' => [

                'current_starting_xi_projected_points' =>
                    55.50,

                'free_hit_starting_xi_projected_points' =>
                    60.00,

                'projected_points_gain' =>
                    4.50
            ],

            'decision' =>
                $freeHitDecision
        ],


        'bench_boost' => [

            'analysis' => [

                'projected_bench_points' =>
                    16.25,

                'bench_reliability' =>
                    0.78,

                'fixture_quality' =>
                    0.70,

                'full_squad_availability' =>
                    0.92
            ],

            'decision' =>
                $benchBoostDecision
        ],


        'triple_captain' => [

            'analysis' => [

                'captain_name' =>
                    'Controlled Captain',

                'projected_points' =>
                    10.75,

                'captain_score' =>
                    72.50,

                'schedule_type' =>
                    'single'
            ],

            'decision' =>
                $tripleCaptainDecision
        ]
    ];
}


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

productionCaptureIntegrationSection(
    'A. Database And Production Services'
);


$database =
    new Database();


$db =
    $database
        ->getConnection();


productionCaptureIntegrationAssert(
    $db instanceof PDO,
    'Database connection is available.'
);


$gameweekRepository =
    new GameweekRepository(
        $db
    );


$candidateRepository =
    new RecommendationCandidateRepository(
        $db
    );


$playerIntelligenceService =
    new PlayerIntelligenceService(
        $db
    );


$candidateCaptureService =
    new RecommendationCandidateCaptureService(
        $candidateRepository
    );


$productionService =
    new RecommendationCandidateProductionService(
        $playerIntelligenceService,
        new PlayerProjectionEvidence(),
        new ChipRecommendationEvidence(),
        $candidateCaptureService
    );


$productionCapture =
    new RecommendationCandidateProductionCapture(
        $gameweekRepository,
        $productionService
    );


productionCaptureIntegrationAssert(
    $productionCapture
        instanceof
        RecommendationCandidateProductionCapture,
    'Complete production capture stack is available.'
);


/*
 * ============================================================
 * SYNTHETIC ENTRY
 * ============================================================
 *
 * This value must never correspond to a genuine FPL entry used
 * by the application.
 */

$entryId =
    935005003;


/*
 * Remove any residue from an interrupted previous test run.
 */

$cleanupStatement =
    $db
        ->prepare(
            '
                DELETE FROM
                    recommendation_candidates
                WHERE
                    entry_id = :entry_id
            '
        );


$cleanupStatement
    ->bindValue(
        ':entry_id',
        $entryId,
        PDO::PARAM_INT
    );


$cleanupStatement
    ->execute();


productionCaptureIntegrationAssert(
    $candidateRepository
        ->getByEntryId(
            $entryId
        )
    === [],
    'Synthetic recommendation candidate state is clean before test.'
);


/*
 * ============================================================
 * RESOLVE A REAL FUTURE GAMEWEEK
 * ============================================================
 *
 * We deliberately derive the controlled generation time from a
 * stored deadline rather than relying on the wall clock.
 *
 * This keeps the test deterministic throughout the season.
 */

productionCaptureIntegrationSection(
    'B. Real Gameweek Deadline'
);


$gameweeks =
    $gameweekRepository
        ->getAll();


$gameweeksWithDeadline =
    array_values(
        array_filter(
            $gameweeks,
            static function (
                array $gameweek
            ): bool {

                return
                    trim(
                        (string) (
                            $gameweek[
                                'deadline_time'
                            ]
                            ?? ''
                        )
                    )
                    !== '';
            }
        )
    );


usort(
    $gameweeksWithDeadline,
    static function (
        array $a,
        array $b
    ): int {

        return
            strtotime(
                (string) $a[
                    'deadline_time'
                ]
            )
            <=>
            strtotime(
                (string) $b[
                    'deadline_time'
                ]
            );
    }
);


if (empty($gameweeksWithDeadline)) {

    throw new RuntimeException(
        'No gameweek deadline is available for production capture integration testing.'
    );
}


/*
 * Use the first stored deadline and generate one hour before it.
 */

$controlledTargetGameweek =
    $gameweeksWithDeadline[0];


$controlledDeadline =
    new DateTimeImmutable(
        (string) $controlledTargetGameweek[
            'deadline_time'
        ]
    );


$generatedAt =
    $controlledDeadline
        ->modify(
            '-1 hour'
        )
        ->format(
            'Y-m-d H:i:s'
        );


$resolvedGameweek =
    $gameweekRepository
        ->getNextDeadlineAfter(
            $generatedAt
        );


productionCaptureIntegrationAssert(
    is_array(
        $resolvedGameweek
    ),
    'A recommendation target gameweek is resolved.'
);


productionCaptureIntegrationAssert(
    (int) (
        $resolvedGameweek[
            'id'
        ]
        ?? 0
    )
    ===
    (int) $controlledTargetGameweek[
        'id'
    ],
    'Production deadline resolution selects the expected local gameweek.'
);


productionCaptureIntegrationAssert(
    (
        $resolvedGameweek[
            'deadline_time'
        ]
        ?? null
    )
    ===
    $controlledTargetGameweek[
        'deadline_time'
    ],
    'Authoritative stored deadline is preserved.'
);


/*
 * ============================================================
 * BUILD CONTROLLED LEGAL 15-PLAYER IMPORT
 * ============================================================
 */

productionCaptureIntegrationSection(
    'C. Controlled Imported Squad'
);


$summaries =
    $playerIntelligenceService
        ->getAllPlayerSummaries();


productionCaptureIntegrationAssert(
    !empty(
        $summaries
    ),
    'Current Player Intelligence summaries are available.'
);


/*
 * Build candidates by position.
 *
 * The imported squad requires official FPL player IDs. The
 * summaries use local player IDs, so resolve those through the
 * existing players table.
 */

$playerStatement =
    $db
        ->query(
            '
                SELECT
                    id,
                    fpl_player_id,
                    team_id
                FROM
                    players
            '
        );


$localPlayers =
    $playerStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$localById =
    [];


foreach (
    $localPlayers
    as $localPlayer
) {

    $localId =
        (int) (
            $localPlayer[
                'id'
            ]
            ?? 0
        );


    if ($localId > 0) {

        $localById[
            $localId
        ] =
            $localPlayer;
    }
}


$candidatesByPosition = [

    'GK' =>
        [],

    'DEF' =>
        [],

    'MID' =>
        [],

    'FWD' =>
        []
];


foreach (
    $summaries
    as $summary
) {

    $playerId =
        (int) (
            $summary[
                'player_id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
        ||
        !isset(
            $localById[
                $playerId
            ]
        )
    ) {

        continue;
    }


    $localPlayer =
        $localById[
            $playerId
        ];


    $fplPlayerId =
        (int) (
            $localPlayer[
                'fpl_player_id'
            ]
            ?? 0
        );


    $teamId =
        (int) (
            $summary[
                'team_id'
            ]
            ??
            (
                $localPlayer[
                    'team_id'
                ]
                ?? 0
            )
        );


    $position =
        strtoupper(
            trim(
                (string) (
                    $summary[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    $price =
        $summary[
            'price'
        ]
        ?? null;


    $intelligenceScore =
        $summary[
            'intelligence_score'
        ]
        ?? null;


    if (
        $fplPlayerId <= 0
        ||
        $teamId <= 0
        ||
        !isset(
            $candidatesByPosition[
                $position
            ]
        )
        ||
        !is_numeric(
            $price
        )
        ||
        (float) $price <= 0
        ||
        !is_numeric(
            $intelligenceScore
        )
    ) {

        continue;
    }


    $candidatesByPosition[
        $position
    ][] = [

        'player_id' =>
            $playerId,

        'fpl_player_id' =>
            $fplPlayerId,

        'team_id' =>
            $teamId,

        'position' =>
            $position,

        'price' =>
            (float) $price,

        'intelligence_score' =>
            (float) $intelligenceScore
    ];
}


/*
 * Prefer inexpensive players.
 *
 * This gives the controlled fixture the best chance of remaining
 * within the standard £100m squad budget as live prices change.
 */

foreach (
    $candidatesByPosition
    as &$positionCandidates
) {

    usort(
        $positionCandidates,
        static function (
            array $a,
            array $b
        ): int {

            $priceComparison =
                $a[
                    'price'
                ]
                <=>
                $b[
                    'price'
                ];


            if ($priceComparison !== 0) {

                return
                    $priceComparison;
            }


            return
                $a[
                    'player_id'
                ]
                <=>
                $b[
                    'player_id'
                ];
        }
    );
}


unset(
    $positionCandidates
);


$requiredCounts = [

    'GK' =>
        2,

    'DEF' =>
        5,

    'MID' =>
        5,

    'FWD' =>
        3
];


$selected =
    [];


$clubCounts =
    [];


foreach (
    $requiredCounts
    as $position => $requiredCount
) {

    $selectedForPosition =
        0;


    foreach (
        $candidatesByPosition[
            $position
        ]
        as $candidate
    ) {

        $teamId =
            (int) $candidate[
                'team_id'
            ];


        if (
            (
                $clubCounts[
                    $teamId
                ]
                ?? 0
            )
            >= 3
        ) {

            continue;
        }


        $selected[] =
            $candidate;


        $clubCounts[
            $teamId
        ] =
            (
                $clubCounts[
                    $teamId
                ]
                ?? 0
            )
            +
            1;


        $selectedForPosition++;


        if (
            $selectedForPosition
            >=
            $requiredCount
        ) {

            break;
        }
    }


    if (
        $selectedForPosition
        !==
        $requiredCount
    ) {

        throw new RuntimeException(
            'Unable to construct the required '
            . $position
            . ' integration squad.'
        );
    }
}


productionCaptureIntegrationAssert(
    count(
        $selected
    )
    === 15,
    'Controlled imported squad contains exactly 15 players.'
);


$positionCounts = [

    'GK' =>
        0,

    'DEF' =>
        0,

    'MID' =>
        0,

    'FWD' =>
        0
];


$squadCost =
    0.0;


foreach (
    $selected
    as $candidate
) {

    $positionCounts[
        $candidate[
            'position'
        ]
    ]++;


    $squadCost +=
        (float) $candidate[
            'price'
        ];
}


productionCaptureIntegrationAssert(
    $positionCounts
    ===
    $requiredCounts,
    'Controlled squad has legal FPL positional composition.'
);


productionCaptureIntegrationAssert(
    max(
        $clubCounts
    )
    <= 3,
    'Controlled squad contains no more than three players per club.'
);


productionCaptureIntegrationAssert(
    $squadCost <= 100.0,
    'Controlled squad remains within £100m.'
);


$importedPlayers =
    [];


foreach (
    $selected
    as $index => $candidate
) {

    $importedPlayers[] = [

        'fpl_player_id' =>
            (int) $candidate[
                'fpl_player_id'
            ],

        'squad_position' =>
            $index + 1,

        'multiplier' =>
            $index < 11
                ? 1
                : 0,

        'is_captain' =>
            false,

        'is_vice_captain' =>
            false
    ];
}


$importedSquad = [

    'status' =>
        'success',

    'message' =>
        'Controlled recommendation history integration squad.',

    'entry' => [

        'entry_id' =>
            $entryId,

        'team_name' =>
            'Recommendation History Integration',

        'manager_first_name' =>
            'Development',

        'manager_last_name' =>
            'Integration'
    ],

    'gameweek' =>
        null,

    'bank' =>
        round(
            100.0
            -
            $squadCost,
            1
        ),

    'team_value' =>
        round(
            $squadCost,
            1
        ),

    'player_count' =>
        15,

    'players' =>
        $importedPlayers
];


/*
 * ============================================================
 * CHIP EVIDENCE
 * ============================================================
 */

$chipResults =
    buildProductionCaptureIntegrationChipResults();


/*
 * ============================================================
 * FIRST COMPLETE PRODUCTION CAPTURE
 * ============================================================
 */

productionCaptureIntegrationSection(
    'D. Complete Production Capture'
);


$firstCapture =
    $productionCapture
        ->capture(
            $entryId,
            $importedSquad,
            $chipResults[
                'wildcard'
            ],
            $chipResults[
                'free_hit'
            ],
            $chipResults[
                'bench_boost'
            ],
            $chipResults[
                'triple_captain'
            ],
            $generatedAt
        );


productionCaptureIntegrationAssert(
    $firstCapture === true,
    'Complete production recommendation candidate is captured.'
);


$persisted =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            (int) $controlledTargetGameweek[
                'id'
            ]
        );


productionCaptureIntegrationAssert(
    is_array(
        $persisted
    ),
    'Captured recommendation candidate is persisted.'
);


productionCaptureIntegrationAssert(
    (
        $persisted[
            'entry_id'
        ]
        ?? null
    )
    ===
    $entryId,
    'Persisted candidate preserves synthetic entry ID.'
);


productionCaptureIntegrationAssert(
    (
        $persisted[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    (int) $controlledTargetGameweek[
        'id'
    ],
    'Persisted candidate preserves local gameweek ID.'
);


productionCaptureIntegrationAssert(
    (
        $persisted[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedAt,
    'Persisted candidate preserves generation timestamp.'
);


productionCaptureIntegrationAssert(
    (
        $persisted[
            'deadline_time'
        ]
        ?? null
    )
    ===
    $controlledTargetGameweek[
        'deadline_time'
    ],
    'Persisted candidate preserves authoritative deadline.'
);


/*
 * ============================================================
 * PLAYER PROJECTION EVIDENCE
 * ============================================================
 */

productionCaptureIntegrationSection(
    'E. Player Projection Evidence'
);


$projectionEvidence =
    $persisted[
        'player_projections'
    ]
    ?? [];


productionCaptureIntegrationAssert(
    count(
        $projectionEvidence
    )
    === 15,
    'Historical candidate contains projection evidence for all 15 squad players.'
);


$projectionPlayerIds =
    array_map(
        static function (
            array $projection
        ): int {

            return
                (int) (
                    $projection[
                        'player_id'
                    ]
                    ?? 0
                );
        },
        $projectionEvidence
    );


productionCaptureIntegrationAssert(
    count(
        array_filter(
            $projectionPlayerIds,
            static function (
                int $playerId
            ): bool {

                return
                    $playerId > 0;
            }
        )
    )
    === 15,
    'Historical projection evidence uses valid local player IDs.'
);


productionCaptureIntegrationAssert(
    array_key_exists(
        'projected_points',
        $projectionEvidence[0]
        ?? []
    ),
    'Historical player evidence preserves projected points field.'
);


productionCaptureIntegrationAssert(
    array_key_exists(
        'projected_minutes',
        $projectionEvidence[0]
        ?? []
    ),
    'Historical player evidence preserves projected minutes field.'
);


productionCaptureIntegrationAssert(
    array_key_exists(
        'projection_confidence',
        $projectionEvidence[0]
        ?? []
    ),
    'Historical player evidence preserves projection confidence field.'
);


productionCaptureIntegrationAssert(
    array_key_exists(
        'intelligence_score',
        $projectionEvidence[0]
        ?? []
    ),
    'Historical player evidence preserves Intelligence Score field.'
);


/*
 * ============================================================
 * GAMEWEEK DECISION EVIDENCE
 * ============================================================
 */

productionCaptureIntegrationSection(
    'F. Gameweek Decision Evidence'
);


productionCaptureIntegrationAssert(
    !empty(
        $persisted[
            'starting_xi'
        ]
        ?? []
    ),
    'Historical candidate preserves Starting XI recommendation.'
);


productionCaptureIntegrationAssert(
    !empty(
        $persisted[
            'captain_recommendation'
        ]
        ?? []
    ),
    'Historical candidate preserves captain recommendation.'
);


productionCaptureIntegrationAssert(
    !empty(
        $persisted[
            'transfer_recommendations'
        ]
        ?? []
    ),
    'Historical candidate preserves transfer recommendations.'
);


productionCaptureIntegrationAssert(
    !empty(
        $persisted[
            'gameweek_decision'
        ]
        ?? []
    ),
    'Historical candidate preserves Gameweek Decision output.'
);


/*
 * ============================================================
 * CHIP RECOMMENDATION EVIDENCE
 * ============================================================
 */

productionCaptureIntegrationSection(
    'G. Chip Recommendation Evidence'
);


$persistedChips =
    $persisted[
        'chip_recommendations'
    ]
    ?? [];


productionCaptureIntegrationAssert(
    array_keys(
        $persistedChips
    )
    ===
    [
        'wildcard',
        'free_hit',
        'bench_boost',
        'triple_captain'
    ],
    'Historical candidate preserves all four chip recommendation contracts.'
);


productionCaptureIntegrationAssert(
    (
        $persistedChips[
            'wildcard'
        ][
            'decision'
        ][
            'recommendation'
        ]
        ?? null
    )
    ===
    'Consider',
    'Wildcard recommendation is preserved exactly.'
);


productionCaptureIntegrationAssert(
    (
        $persistedChips[
            'free_hit'
        ][
            'decision'
        ][
            'recommendation'
        ]
        ?? null
    )
    ===
    'Hold',
    'Free Hit recommendation is preserved exactly.'
);


productionCaptureIntegrationAssert(
    (
        $persistedChips[
            'bench_boost'
        ][
            'decision'
        ][
            'recommendation'
        ]
        ?? null
    )
    ===
    'Use',
    'Bench Boost independent Use recommendation is preserved.'
);


productionCaptureIntegrationAssert(
    (
        $persistedChips[
            'triple_captain'
        ][
            'decision'
        ][
            'recommendation'
        ]
        ?? null
    )
    ===
    'Consider',
    'Triple Captain recommendation is preserved exactly.'
);


productionCaptureIntegrationAssert(
    (
        $persistedChips[
            'bench_boost'
        ][
            'analysis'
        ][
            'projected_bench_points'
        ]
        ?? null
    )
    ===
    16.25,
    'Raw chip supporting numeric evidence is preserved.'
);


/*
 * ============================================================
 * NEWER PRODUCTION CAPTURE REPLACES CANDIDATE
 * ============================================================
 */

productionCaptureIntegrationSection(
    'H. Latest Candidate Replacement'
);


$newerGeneratedAt =
    $controlledDeadline
        ->modify(
            '-30 minutes'
        )
        ->format(
            'Y-m-d H:i:s'
        );


$newerCapture =
    $productionCapture
        ->capture(
            $entryId,
            $importedSquad,
            $chipResults[
                'wildcard'
            ],
            $chipResults[
                'free_hit'
            ],
            $chipResults[
                'bench_boost'
            ],
            $chipResults[
                'triple_captain'
            ],
            $newerGeneratedAt
        );


productionCaptureIntegrationAssert(
    $newerCapture === true,
    'Newer complete production recommendation replaces existing candidate.'
);


$newerPersisted =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            (int) $controlledTargetGameweek[
                'id'
            ]
        );


productionCaptureIntegrationAssert(
    (
        $newerPersisted[
            'generated_at'
        ]
        ?? null
    )
    ===
    $newerGeneratedAt,
    'Persisted candidate advances to newer generation timestamp.'
);


/*
 * ============================================================
 * STALE PRODUCTION CAPTURE CANNOT REPLACE LATEST
 * ============================================================
 */

productionCaptureIntegrationSection(
    'I. Stale Candidate Protection'
);


$staleGeneratedAt =
    $controlledDeadline
        ->modify(
            '-45 minutes'
        )
        ->format(
            'Y-m-d H:i:s'
        );


$staleCapture =
    $productionCapture
        ->capture(
            $entryId,
            $importedSquad,
            $chipResults[
                'wildcard'
            ],
            $chipResults[
                'free_hit'
            ],
            $chipResults[
                'bench_boost'
            ],
            $chipResults[
                'triple_captain'
            ],
            $staleGeneratedAt
        );


productionCaptureIntegrationAssert(
    $staleCapture === false,
    'Older production recommendation cannot replace latest candidate.'
);


$afterStale =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            (int) $controlledTargetGameweek[
                'id'
            ]
        );


productionCaptureIntegrationAssert(
    (
        $afterStale[
            'generated_at'
        ]
        ?? null
    )
    ===
    $newerGeneratedAt,
    'Latest persisted candidate remains unchanged after stale capture.'
);


/*
 * ============================================================
 * SINGLE CANDIDATE IDENTITY
 * ============================================================
 */

productionCaptureIntegrationSection(
    'J. Candidate Identity'
);


$entryCandidates =
    $candidateRepository
        ->getByEntryId(
            $entryId
        );


productionCaptureIntegrationAssert(
    count(
        $entryCandidates
    )
    === 1,
    'Synthetic entry has exactly one candidate for controlled gameweek.'
);


/*
 * ============================================================
 * CLEANUP
 * ============================================================
 */

productionCaptureIntegrationSection(
    'K. Cleanup'
);


$cleanupStatement =
    $db
        ->prepare(
            '
                DELETE FROM
                    recommendation_candidates
                WHERE
                    entry_id = :entry_id
            '
        );


$cleanupStatement
    ->bindValue(
        ':entry_id',
        $entryId,
        PDO::PARAM_INT
    );


$cleanupStatement
    ->execute();


productionCaptureIntegrationAssert(
    $candidateRepository
        ->getByEntryId(
            $entryId
        )
    === [],
    'Synthetic recommendation candidate is removed after integration test.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

productionCaptureIntegrationSection(
    'Recommendation Candidate Production Capture Integration Test Summary'
);


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}