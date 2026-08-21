<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Starting XI Real Data Diagnostic<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekRealCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * DISPLAY HELPERS
 * ============================================================
 */

function gameweekRealValue(
    mixed $value,
    int $decimals = 1
): string {

    if (
        $value === null
        ||
        !is_numeric(
            $value
        )
    ) {

        return 'N/A';
    }


    return number_format(
        (float) $value,
        $decimals
    );
}


function gameweekRealPlayerLine(
    array $player
): void {

    $components =
        $player[
            'gameweek_components'
        ]
        ?? [];


    echo "<strong>"
        . htmlspecialchars(
            (string) (
                $player[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "</strong><br>";


    echo htmlspecialchars(
        (string) (
            $player[
                'team_name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " | "
    . htmlspecialchars(
        (string) (
            $player[
                'position'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " | GW "
    . gameweekRealValue(
        $player[
            'gameweek_score'
        ]
        ?? null,
        2
    )
    . "<br>";


    echo "INT "
        . gameweekRealValue(
            $components[
                'intelligence'
            ]
            ?? null
        )
        . " | STR "
        . gameweekRealValue(
            $components[
                'strength'
            ]
            ?? null
        )
        . " | FIX "
        . gameweekRealValue(
            $components[
                'fixture'
            ]
            ?? null
        )
        . " | CORE "
        . gameweekRealValue(
            $components[
                'core_score'
            ]
            ?? null,
            2
        )
        . "<br>";


    echo "CONF "
        . gameweekRealValue(
            $components[
                'confidence'
            ]
            ?? null
        )
        . "% | CONF MOD "
        . gameweekRealValue(
            $components[
                'confidence_modifier'
            ]
            ?? null,
            3
        )
        . " | AVAIL "
        . gameweekRealValue(
            $components[
                'availability'
            ]
            ?? null
        )
        . "% | AVAIL MOD "
        . gameweekRealValue(
            $components[
                'availability_modifier'
            ]
            ?? null,
            3
        )
        . "<br>";
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Setup<br>";
echo "============================================<br>";


$startedAt =
    microtime(
        true
    );


$database =
    new Database();


$db =
    $database
        ->getConnection();


$service =
    new PlayerIntelligenceService(
        $db
    );


gameweekRealCheck(
    'Database connection is available',
    $db instanceof PDO
);


gameweekRealCheck(
    'Player Intelligence Service is available',
    $service instanceof PlayerIntelligenceService
);


echo "<br>";


/*
 * ============================================================
 * REAL PLAYER SUMMARIES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Real Player Summaries<br>";
echo "============================================<br>";


$summaryStartedAt =
    microtime(
        true
    );


$summaries =
    $service
        ->getAllPlayerSummaries();


$summaryRuntime =
    microtime(
        true
    )
    -
    $summaryStartedAt;


gameweekRealCheck(
    'Real player summaries return an array',
    is_array(
        $summaries
    )
);


gameweekRealCheck(
    'Real player summaries are not empty',
    !empty(
        $summaries
    )
);


gameweekRealCheck(
    'Real player pool contains at least 300 players',
    count(
        $summaries
    )
    >= 300
);


echo "Player Summaries: "
    . count(
        $summaries
    )
    . "<br>";


echo "Summary Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * BUILD REAL-DATA 15-PLAYER SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Real-Data Squad Construction<br>";
echo "============================================<br>";


$positionRequirements = [

    'GK' =>
        2,

    'DEF' =>
        5,

    'MID' =>
        5,

    'FWD' =>
        3
];


$realSquad =
    [];


$selectedPlayerIds =
    [];


$teamCounts =
    [];


/*
 * Prefer players with usable Gameweek inputs.
 *
 * We are not trying to construct the strongest possible squad.
 * The purpose is to create a realistic legal real-data squad
 * that exercises the complete Gameweek Intelligence pipeline.
 */

foreach (
    $positionRequirements
    as $requiredPosition => $requiredCount
) {

    $selected =
        0;


    foreach (
        $summaries
        as $summary
    ) {

        if (
            $selected
            >=
            $requiredCount
        ) {

            break;
        }


        $playerId =
            (int) (
                $summary[
                    'player_id'
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


        if (
            $playerId <= 0
            ||
            $position !== $requiredPosition
            ||
            in_array(
                $playerId,
                $selectedPlayerIds,
                true
            )
        ) {

            continue;
        }


        /*
         * Use players with the main Gameweek inputs available.
         */

        if (
            !is_numeric(
                $summary[
                    'intelligence_score'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'strength_rating'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'next_fixture_rating'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'availability_rating'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'sample_confidence'
                ]
                ?? null
            )
        ) {

            continue;
        }


        /*
         * Load the full profile so we can obtain a reliable team ID
         * and preserve the normal squad structure used elsewhere.
         */

        $profile =
            $service
                ->getPlayerProfile(
                    $playerId
                );


        if (
            $profile === null
        ) {

            continue;
        }


        $teamId =
            (int) (
                $profile[
                    'team'
                ][
                    'team_id'
                ]
                ?? 0
            );


        if (
            $teamId <= 0
        ) {

            continue;
        }


        /*
         * Respect the normal FPL maximum of three players per club.
         */

        if (
            (
                $teamCounts[
                    $teamId
                ]
                ?? 0
            )
            >= 3
        ) {

            continue;
        }


        $realSquad[] = [

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $profile[
                    'player'
                ][
                    'fpl_player_id'
                ]
                ?? null,

            'name' =>
                $summary[
                    'name'
                ]
                ?? $profile[
                    'player'
                ][
                    'name'
                ]
                ?? null,

            'position' =>
                $position,

            'team_id' =>
                $teamId,

            'team_name' =>
                $summary[
                    'team_name'
                ]
                ?? $profile[
                    'team'
                ][
                    'name'
                ]
                ?? null,

            'price' =>
                $summary[
                    'price'
                ]
                ?? null,

            'intelligence_score' =>
                $summary[
                    'intelligence_score'
                ]
                ?? null,

            'strength_rating' =>
                $summary[
                    'strength_rating'
                ]
                ?? null,

            'fixture_rating' =>
                $summary[
                    'fixture_rating'
                ]
                ?? null,

            'next_fixture_rating' =>
                $summary[
                    'next_fixture_rating'
                ]
                ?? null,

            'availability_rating' =>
                $summary[
                    'availability_rating'
                ]
                ?? null,

            'sample_confidence' =>
                $summary[
                    'sample_confidence'
                ]
                ?? null,

            'squad_position' =>
                count(
                    $realSquad
                )
                + 1,

            'multiplier' =>
                1,

            'is_captain' =>
                false,

            'is_vice_captain' =>
                false
        ];


        $selectedPlayerIds[] =
            $playerId;


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


        $selected++;
    }
}


gameweekRealCheck(
    'Real-data squad contains exactly 15 players',
    count(
        $realSquad
    )
    === 15
);


$realPositionCounts = [

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
    $realSquad
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? '';


    if (
        isset(
            $realPositionCounts[
                $position
            ]
        )
    ) {

        $realPositionCounts[
            $position
        ]++;
    }
}


gameweekRealCheck(
    'Real-data squad contains two goalkeepers',
    $realPositionCounts[
        'GK'
    ]
    === 2
);


gameweekRealCheck(
    'Real-data squad contains five defenders',
    $realPositionCounts[
        'DEF'
    ]
    === 5
);


gameweekRealCheck(
    'Real-data squad contains five midfielders',
    $realPositionCounts[
        'MID'
    ]
    === 5
);


gameweekRealCheck(
    'Real-data squad contains three forwards',
    $realPositionCounts[
        'FWD'
    ]
    === 3
);


$clubLimitValid =
    true;


foreach (
    $teamCounts
    as $count
) {

    if (
        $count > 3
    ) {

        $clubLimitValid =
            false;

        break;
    }
}


gameweekRealCheck(
    'Real-data squad respects three-player club limit',
    $clubLimitValid
);


echo "Squad Players: "
    . count(
        $realSquad
    )
    . "<br><br>";


/*
 * ============================================================
 * GAMEWEEK STARTING XI SERVICE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Gameweek Starting XI<br>";
echo "============================================<br>";


$optimizationStartedAt =
    microtime(
        true
    );


$result =
    $service
        ->getGameweekStartingXI(
            $realSquad
        );


$optimizationRuntime =
    microtime(
        true
    )
    -
    $optimizationStartedAt;


gameweekRealCheck(
    'Gameweek Starting XI service returns an array',
    is_array(
        $result
    )
);


gameweekRealCheck(
    'Gameweek Starting XI service returns success',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


gameweekRealCheck(
    'Recommended formation is returned',
    !empty(
        $result[
            'formation'
        ]
        ?? null
    )
);


gameweekRealCheck(
    'Starting XI contains exactly 11 players',
    count(
        $result[
            'starting_xi'
        ]
        ?? []
    )
    === 11
);


gameweekRealCheck(
    'Bench contains exactly four players',
    count(
        $result[
            'bench'
        ]
        ?? []
    )
    === 4
);


gameweekRealCheck(
    'All 15 squad players match current summaries',
    (
        $result[
            'summary_matches'
        ]
        ?? null
    )
    === 15
);


gameweekRealCheck(
    'Real-data Gameweek evaluation requires no summary fallbacks',
    (
        $result[
            'summary_fallbacks'
        ]
        ?? null
    )
    === 0
);


echo "Recommended Formation: "
    . htmlspecialchars(
        (string) (
            $result[
                'formation'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Starting XI Score: "
    . gameweekRealValue(
        $result[
            'starting_xi_score'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Bench Score: "
    . gameweekRealValue(
        $result[
            'bench_score'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Optimization Runtime: "
    . number_format(
        $optimizationRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * STARTING XI OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Recommended Starting XI<br>";
echo "============================================<br>";


$startingXI =
    $result[
        'starting_xi'
    ]
    ?? [];


$currentPosition =
    null;


foreach (
    $startingXI
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? '';


    if (
        $position !==
        $currentPosition
    ) {

        if (
            $currentPosition !== null
        ) {

            echo "<br>";
        }


        echo "<strong>"
            . htmlspecialchars(
                $position,
                ENT_QUOTES,
                'UTF-8'
            )
            . "</strong><br><br>";


        $currentPosition =
            $position;
    }


    gameweekRealPlayerLine(
        $player
    );


    echo "<br>";
}


$startingScoresNumeric =
    true;


foreach (
    $startingXI
    as $player
) {

    if (
        !is_numeric(
            $player[
                'gameweek_score'
            ]
            ?? null
        )
    ) {

        $startingScoresNumeric =
            false;

        break;
    }
}


gameweekRealCheck(
    'Every Starting XI player has a numeric Gameweek Score',
    $startingScoresNumeric
);


echo "<br>";


/*
 * ============================================================
 * BENCH OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Recommended Bench<br>";
echo "============================================<br>";


$bench =
    $result[
        'bench'
    ]
    ?? [];


foreach (
    $bench
    as $player
) {

    echo "<strong>Bench "
        . (
            $player[
                'bench_order'
            ]
            ?? '?'
        )
        . "</strong><br>";


    gameweekRealPlayerLine(
        $player
    );


    echo "<br>";
}


$benchOrderValid =
    true;


foreach (
    $bench
    as $index => $player
) {

    if (
        (
            $player[
                'bench_order'
            ]
            ?? null
        )
        !==
        (
            $index + 1
        )
    ) {

        $benchOrderValid =
            false;

        break;
    }
}


gameweekRealCheck(
    'Bench ordering is sequential',
    $benchOrderValid
);


gameweekRealCheck(
    'Backup goalkeeper is Bench 4',
    (
        $bench[
            3
        ][
            'position'
        ]
        ?? null
    )
    ===
    'GK'
);


echo "<br>";


/*
 * ============================================================
 * FORMATION COMPARISON
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Formation Comparison<br>";
echo "============================================<br>";


$formations =
    $result[
        'formations'
    ]
    ?? [];


gameweekRealCheck(
    'All eight legal formations are returned',
    count(
        $formations
    )
    === 8
);


$previousFormationScore =
    null;


$formationsOrdered =
    true;


foreach (
    $formations
    as $formation
) {

    $formationScore =
        (float) (
            $formation[
                'starting_xi_score'
            ]
            ?? 0
        );


    echo htmlspecialchars(
        (string) (
            $formation[
                'formation'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " | Starting XI "
    . gameweekRealValue(
        $formation[
            'starting_xi_score'
        ]
        ?? null,
        2
    )
    . " | Bench "
    . gameweekRealValue(
        $formation[
            'bench_score'
        ]
        ?? null,
        2
    )
    . "<br>";


    if (
        $previousFormationScore !== null
        &&
        $formationScore >
        $previousFormationScore
    ) {

        $formationsOrdered =
            false;
    }


    $previousFormationScore =
        $formationScore;
}


gameweekRealCheck(
    'Formation results are ordered by Starting XI Score',
    $formationsOrdered
);


echo "<br>";


/*
 * ============================================================
 * POSITION DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Starting XI Position Distribution<br>";
echo "============================================<br>";


$startingPositionCounts = [

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
    $startingXI
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? '';


    if (
        isset(
            $startingPositionCounts[
                $position
            ]
        )
    ) {

        $startingPositionCounts[
            $position
        ]++;
    }
}


foreach (
    $startingPositionCounts
    as $position => $count
) {

    echo $position
        . ": "
        . $count
        . "<br>";
}


gameweekRealCheck(
    'Starting XI contains exactly one goalkeeper',
    $startingPositionCounts[
        'GK'
    ]
    === 1
);


gameweekRealCheck(
    'Starting XI defence is legal',
    $startingPositionCounts[
        'DEF'
    ]
    >= 3
    &&
    $startingPositionCounts[
        'DEF'
    ]
    <= 5
);


gameweekRealCheck(
    'Starting XI midfield is legal',
    $startingPositionCounts[
        'MID'
    ]
    >= 2
    &&
    $startingPositionCounts[
        'MID'
    ]
    <= 5
);


gameweekRealCheck(
    'Starting XI forwards are legal',
    $startingPositionCounts[
        'FWD'
    ]
    >= 1
    &&
    $startingPositionCounts[
        'FWD'
    ]
    <= 3
);


echo "<br>";


/*
 * ============================================================
 * RELIABILITY / SCORE INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Score & Reliability Integrity<br>";
echo "============================================<br>";


$allEvaluatedPlayers =
    array_merge(
        $startingXI,
        $bench
    );


$allScoresValid =
    true;


$allModifiersValid =
    true;


foreach (
    $allEvaluatedPlayers
    as $player
) {

    $score =
        $player[
            'gameweek_score'
        ]
        ?? null;


    if (
        !is_numeric(
            $score
        )
        ||
        $score < 0
        ||
        $score > 100
    ) {

        $allScoresValid =
            false;
    }


    $components =
        $player[
            'gameweek_components'
        ]
        ?? [];


    $confidenceModifier =
        $components[
            'confidence_modifier'
        ]
        ?? null;


    $availabilityModifier =
        $components[
            'availability_modifier'
        ]
        ?? null;


    if (
        !is_numeric(
            $confidenceModifier
        )
        ||
        $confidenceModifier < 0
        ||
        $confidenceModifier > 1
        ||
        !is_numeric(
            $availabilityModifier
        )
        ||
        $availabilityModifier < 0
        ||
        $availabilityModifier > 1
    ) {

        $allModifiersValid =
            false;
    }
}


gameweekRealCheck(
    'All real-data Gameweek Scores remain between 0 and 100',
    $allScoresValid
);


gameweekRealCheck(
    'All reliability modifiers remain between 0 and 1',
    $allModifiersValid
);


gameweekRealCheck(
    'Starting XI Score is numeric',
    is_numeric(
        $result[
            'starting_xi_score'
        ]
        ?? null
    )
);


gameweekRealCheck(
    'Bench Score is numeric',
    is_numeric(
        $result[
            'bench_score'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Performance<br>";
echo "============================================<br>";


$totalRuntime =
    microtime(
        true
    )
    -
    $startedAt;


gameweekRealCheck(
    'Complete real-data Gameweek diagnostic finishes within 15 seconds',
    $totalRuntime
    <= 15.0
);


echo "Summary Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br>";


echo "Optimization Runtime: "
    . number_format(
        $optimizationRuntime,
        4
    )
    . " seconds<br>";


echo "Total Runtime: "
    . number_format(
        $totalRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Gameweek Starting XI Real Data Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}