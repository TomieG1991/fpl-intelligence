<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function freeHitRealCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        $passed++;


        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';


        return;
    }


    $failed++;


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


function freeHitRealHeading(
    string $heading
): void {

    echo
        '<br>'
        . '============================================<br>'
        . htmlspecialchars(
            $heading,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>'
        . '============================================<br>';
}


/*
 * ============================================================
 * HEADER
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Free Hit Intelligence Real Data Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

freeHitRealHeading(
    'Scenario A: Real Data Setup'
);


try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $playerIntelligenceService =
        new PlayerIntelligenceService(
            $db
        );


    $freeHitOptimizer =
        new FreeHitOptimizer();


    $freeHitService =
        new FreeHitIntelligenceService(
            $playerIntelligenceService,
            $freeHitOptimizer
        );


    freeHitRealCheck(
        'Real database connection is available',
        $db instanceof PDO
    );


    freeHitRealCheck(
        'Real Player Intelligence service is available',
        $playerIntelligenceService
        instanceof
        PlayerIntelligenceService
    );


    freeHitRealCheck(
        'Real Free Hit service is available',
        $freeHitService
        instanceof
        FreeHitIntelligenceService
    );

} catch (
    Throwable $exception
) {

    freeHitRealCheck(
        'Real-data setup completes without exception',
        false
    );


    echo
        'Message: '
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';


    echo
        '<br>'
        . '============================================<br>';

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
        . '<br>';


    echo
        'RESULT: TESTS FAILED ❌<br>';


    exit;
}


/*
 * ============================================================
 * LOAD REAL PLAYER INTELLIGENCE
 * ============================================================
 */

freeHitRealHeading(
    'Scenario B: Real Candidate Pool'
);


$poolStartedAt =
    microtime(
        true
    );


try {

    $summaries =
        $playerIntelligenceService
            ->getAllPlayerSummaries();

} catch (
    Throwable $exception
) {

    freeHitRealCheck(
        'Real player summaries load successfully',
        false
    );


    echo
        'Message: '
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';


    exit;
}


$poolRuntime =
    microtime(
        true
    )
    -
    $poolStartedAt;


freeHitRealCheck(
    'Real player summaries are available',
    !empty(
        $summaries
    )
);


echo
    'Player Summaries: '
    . count(
        $summaries
    )
    . '<br>';


echo
    'Player Summary Runtime: '
    . number_format(
        $poolRuntime,
        4
    )
    . ' seconds<br>';


/*
 * ============================================================
 * BUILD FREE HIT CANDIDATE POOL
 * ============================================================
 */

$candidates =
    [];


$positionCandidateCounts = [

    'GK' =>
        0,

    'DEF' =>
        0,

    'MID' =>
        0,

    'FWD' =>
        0
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


    $teamId =
        (int) (
            $summary[
                'team_id'
            ]
            ?? 0
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


    if (
        $playerId <= 0
        ||
        $teamId <= 0
        ||
        !in_array(
            $position,
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ],
            true
        )
        ||
        !is_numeric(
            $price
        )
        ||
        (float) $price <= 0
    ) {

        continue;
    }


    $candidate = [

        'player_id' =>
            $playerId,

        'name' =>
            $summary[
                'name'
            ]
            ?? (
                'Player '
                . $playerId
            ),

        'team_id' =>
            $teamId,

        'team_name' =>
            $summary[
                'team_name'
            ]
            ?? (
                $summary[
                    'team_short_name'
                ]
                ?? (
                    'Team '
                    . $teamId
                )
            ),

        'position' =>
            $position,

        'price' =>
            (float) $price
    ];


    $candidates[] =
        $candidate;


    $positionCandidateCounts[
        $position
    ]++;
}


freeHitRealCheck(
    'Real Free Hit candidate pool is available',
    !empty(
        $candidates
    )
);


freeHitRealCheck(
    'Candidate pool contains at least two goalkeepers',
    $positionCandidateCounts[
        'GK'
    ]
    >=
    2
);


freeHitRealCheck(
    'Candidate pool contains at least five defenders',
    $positionCandidateCounts[
        'DEF'
    ]
    >=
    5
);


freeHitRealCheck(
    'Candidate pool contains at least five midfielders',
    $positionCandidateCounts[
        'MID'
    ]
    >=
    5
);


freeHitRealCheck(
    'Candidate pool contains at least three forwards',
    $positionCandidateCounts[
        'FWD'
    ]
    >=
    3
);


echo
    'Valid Free Hit Candidates: '
    . count(
        $candidates
    )
    . '<br>';


foreach (
    $positionCandidateCounts
    as $position => $count
) {

    echo
        $position
        . ': '
        . $count
        . '<br>';
}


/*
 * ============================================================
 * RUN REAL FREE HIT INTELLIGENCE
 * ============================================================
 */

freeHitRealHeading(
    'Scenario C: Real Free Hit Optimization'
);


$freeHitStartedAt =
    microtime(
        true
    );


try {

    $result =
        $freeHitService
            ->build(
                $candidates,
                100.0
            );

} catch (
    Throwable $exception
) {

    freeHitRealCheck(
        'Real Free Hit build completes without exception',
        false
    );


    echo
        'Message: '
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';


    exit;
}


$freeHitRuntime =
    microtime(
        true
    )
    -
    $freeHitStartedAt;


freeHitRealCheck(
    'Real Free Hit build returns a result',
    is_array(
        $result
    )
);


freeHitRealCheck(
    'Real Free Hit intelligence is Available',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


$projectedPlayerCount =
    (int) (
        $result[
            'projected_player_count'
        ]
        ?? 0
    );


freeHitRealCheck(
    'At least fifteen real players have usable one-gameweek projections',
    $projectedPlayerCount
    >=
    15
);


$optimizerResult =
    $result[
        'optimizer_result'
    ]
    ?? [];


freeHitRealCheck(
    'Real Free Hit optimizer succeeds',
    (
        $optimizerResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


$squad =
    isset(
        $optimizerResult[
            'squad'
        ]
    )
    &&
    is_array(
        $optimizerResult[
            'squad'
        ]
    )
        ? $optimizerResult[
            'squad'
        ]
        : [];


freeHitRealCheck(
    'Real Free Hit squad contains exactly fifteen players',
    count(
        $squad
    )
    ===
    15
);


echo
    'Projected Candidates: '
    . $projectedPlayerCount
    . '<br>';


echo
    'Free Hit Runtime: '
    . number_format(
        $freeHitRuntime,
        4
    )
    . ' seconds<br>';


/*
 * ============================================================
 * VALIDATE REAL SQUAD LEGALITY
 * ============================================================
 */

freeHitRealHeading(
    'Scenario D: Real Free Hit Squad Legality'
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


$teamCounts =
    [];


$playerIds =
    [];


$totalPrice =
    0.0;


$allSquadPlayersHaveProjectedPoints =
    true;


foreach (
    $squad
    as $player
) {

    $playerId =
        (int) (
            $player[
                'player_id'
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


    $position =
        strtoupper(
            trim(
                (string) (
                    $player[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    $price =
        $player[
            'price'
        ]
        ?? null;


    $projectedPoints =
        $player[
            'projected_points'
        ]
        ?? null;


    if (
        array_key_exists(
            $position,
            $positionCounts
        )
    ) {

        $positionCounts[
            $position
        ]++;
    }


    if (
        $teamId > 0
    ) {

        $teamCounts[
            $teamId
        ] =
            (
                $teamCounts[
                    $teamId
                ]
                ?? 0
            )
            +
            1;
    }


    if (
        $playerId > 0
    ) {

        $playerIds[] =
            $playerId;
    }


    if (
        is_numeric(
            $price
        )
    ) {

        $totalPrice +=
            (float) $price;
    }


    if (
        !is_numeric(
            $projectedPoints
        )
    ) {

        $allSquadPlayersHaveProjectedPoints =
            false;
    }
}


freeHitRealCheck(
    'Real squad contains two goalkeepers',
    $positionCounts[
        'GK'
    ]
    ===
    2
);


freeHitRealCheck(
    'Real squad contains five defenders',
    $positionCounts[
        'DEF'
    ]
    ===
    5
);


freeHitRealCheck(
    'Real squad contains five midfielders',
    $positionCounts[
        'MID'
    ]
    ===
    5
);


freeHitRealCheck(
    'Real squad contains three forwards',
    $positionCounts[
        'FWD'
    ]
    ===
    3
);


freeHitRealCheck(
    'Real squad contains fifteen unique player IDs',
    count(
        array_unique(
            $playerIds
        )
    )
    ===
    15
);


$clubLimitValid =
    true;


foreach (
    $teamCounts
    as $teamCount
) {

    if (
        $teamCount > 3
    ) {

        $clubLimitValid =
            false;


        break;
    }
}


freeHitRealCheck(
    'Real squad respects maximum three players per club',
    $clubLimitValid
);


freeHitRealCheck(
    'Real squad remains within the £100.0m Free Hit budget',
    $totalPrice
    <=
    100.0
);


freeHitRealCheck(
    'Every selected real player retains one-gameweek projected points',
    $allSquadPlayersHaveProjectedPoints
);


/*
 * ============================================================
 * DISPLAY SELECTED SQUAD
 * ============================================================
 */

freeHitRealHeading(
    'Selected Real Free Hit Squad'
);


foreach (
    [
        'GK',
        'DEF',
        'MID',
        'FWD'
    ]
    as $displayPosition
) {

    echo
        '<strong>'
        . htmlspecialchars(
            $displayPosition,
            ENT_QUOTES,
            'UTF-8'
        )
        . '</strong><br>';


    foreach (
        $squad
        as $player
    ) {

        if (
            strtoupper(
                trim(
                    (string) (
                        $player[
                            'position'
                        ]
                        ?? ''
                    )
                )
            )
            !==
            $displayPosition
        ) {

            continue;
        }


        echo
            htmlspecialchars(
                (string) (
                    $player[
                        'name'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . ' | '
            . htmlspecialchars(
                (string) (
                    $player[
                        'team_name'
                    ]
                    ?? (
                        'Team '
                        . (
                            $player[
                                'team_id'
                            ]
                            ?? '?'
                        )
                    )
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . ' | £'
            . number_format(
                (float) (
                    $player[
                        'price'
                    ]
                    ?? 0
                ),
                1
            )
            . 'm | xPts: '
            . number_format(
                (float) (
                    $player[
                        'projected_points'
                    ]
                    ?? 0
                ),
                2
            )
            . '<br>';
    }


    echo
        '<br>';
}


echo
    '<strong>Total Squad Price:</strong> £'
    . number_format(
        $totalPrice,
        1
    )
    . 'm<br>';


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '<br>'
    . '============================================<br>';

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
    . '<br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}