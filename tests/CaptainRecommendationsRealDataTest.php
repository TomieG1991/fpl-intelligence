<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Recommendations Real Data Diagnostic<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * CONFIGURATION
 * ============================================================
 *
 * Real FPL Entry ID used for this diagnostic.
 */

$entryId =
    3158726;


$recommendationLimit =
    5;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function captainRecommendationRealCheck(
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

function captainRecommendationRealName(
    ?array $player
): string {

    if ($player === null) {
        return 'N/A';
    }


    return (string) (
        $player[
            'name'
        ]
        ?? 'Unknown'
    );
}


function captainRecommendationRealScore(
    ?array $player
): string {

    if (
        $player === null
        ||
        !is_numeric(
            $player[
                'captain_score'
            ]
            ?? null
        )
    ) {

        return 'N/A';
    }


    return number_format(
        (float) $player[
            'captain_score'
        ],
        2
    );
}


function captainRecommendationRealClassification(
    ?array $player
): string {

    if ($player === null) {
        return 'N/A';
    }


    return (string) (
        $player[
            'classification'
        ]
        ?? 'N/A'
    );
}


/*
 * ============================================================
 * FINAL SUMMARY HELPER
 * ============================================================
 */

function captainRecommendationRealFinish(): void {

    global $passed;
    global $failed;


    echo "<br>============================================<br>";
    echo "Captain Recommendations Real Data Test Summary<br>";
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
}


/*
 * ============================================================
 * SCENARIO A
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


$importer =
    new FPLSquadImporter();


captainRecommendationRealCheck(
    'Database connection is available',
    $db instanceof PDO
);


captainRecommendationRealCheck(
    'Player Intelligence Service is available',
    $service instanceof PlayerIntelligenceService
);


captainRecommendationRealCheck(
    'FPL Squad Importer is available',
    $importer instanceof FPLSquadImporter
);


echo "FPL Entry ID: "
    . $entryId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * IMPORT REAL FPL SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Real FPL Squad Import<br>";
echo "============================================<br>";


$importStartedAt =
    microtime(
        true
    );


$importResult =
    $importer
        ->importSquad(
            $entryId
        );


$importRuntime =
    microtime(
        true
    )
    -
    $importStartedAt;


/*
 * The live FPL API is an external dependency.
 *
 * A null result means the API did not return usable data during
 * this diagnostic run. That is not a local application failure,
 * so the production-path portion of this test should be skipped.
 */

if (
    !is_array(
        $importResult
    )
) {

    echo "SKIP: Live FPL API did not return usable squad data.<br>";


    echo "Import Status: unavailable<br>";


    echo "Import Message: "
        . "The public FPL API did not return usable data during this diagnostic run."
        . "<br>";


    echo "Import Runtime: "
        . number_format(
            $importRuntime,
            4
        )
        . " seconds<br><br>";


    echo "Captain recommendation analysis cannot continue because "
        . "the external FPL squad data is temporarily unavailable.<br>";


    echo "This does not indicate a failure in the local "
        . "Captain Intelligence implementation.<br><br>";


    captainRecommendationRealFinish();

    exit;
}


captainRecommendationRealCheck(
    'FPL squad importer returns an array',
    true
);


$importStatus =
    $importResult[
        'status'
    ]
    ?? null;


if (
    $importStatus ===
    'no_public_squad'
) {

    echo "SKIP: FPL squad is not publicly available yet.<br>";

} else {

    captainRecommendationRealCheck(
        'FPL squad import returns success',
        $importStatus
        ===
        'success'
    );
}


echo "Import Status: "
    . htmlspecialchars(
        (string) (
            $importStatus
            ?? 'null'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Import Message: "
    . htmlspecialchars(
        (string) (
            $importResult[
                'message'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Import Runtime: "
    . number_format(
        $importRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * Stop here if FPL does not currently expose the squad.
 *
 * This avoids producing dozens of misleading follow-on
 * failures when the external import itself is unavailable.
 */


if (
    $importStatus ===
    'no_public_squad'
) {

    echo "<br>";
    echo "Real manager squad is not publicly available yet.<br>";
    echo "Captain Intelligence production-path testing is deferred "
        . "until FPL exposes the gameweek squad.<br><br>";


    captainRecommendationRealFinish();

    exit;
}


if (
    $importStatus !==
    'success'
) {

    echo "Captain recommendation analysis cannot continue because "
        . "the FPL squad import returned an unexpected status.<br>";


    captainRecommendationRealFinish();

    exit;
}


/*
 * ============================================================
 * SCENARIO C
 * MAP IMPORTED SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Imported Squad Mapping<br>";
echo "============================================<br>";


$mappedSquad =
    $service
        ->buildSquadFromFPLImport(
            $importResult
        );


captainRecommendationRealCheck(
    'Imported FPL squad maps to local intelligence players',
    is_array(
        $mappedSquad
    )
);


captainRecommendationRealCheck(
    'Mapped squad is complete',
    (
        $mappedSquad[
            'is_complete'
        ]
        ?? false
    )
    === true
);


$mappedPlayers =
    $mappedSquad[
        'players'
    ]
    ?? [];


captainRecommendationRealCheck(
    'Mapped squad contains exactly 15 players',
    count(
        $mappedPlayers
    )
    === 15
);


captainRecommendationRealCheck(
    'Mapped squad reports 15 mapped players',
    (
        $mappedSquad[
            'mapped_count'
        ]
        ?? 0
    )
    === 15
);


captainRecommendationRealCheck(
    'Mapped squad contains no unmapped players',
    (
        $mappedSquad[
            'unmapped_count'
        ]
        ?? -1
    )
    === 0
);


echo "Mapped Players: "
    . count(
        $mappedPlayers
    )
    . "<br>";


echo "Gameweek: "
    . htmlspecialchars(
        (string) (
            $mappedSquad[
                'gameweek'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Bank: £"
    . number_format(
        (float) (
            $mappedSquad[
                'bank'
            ]
            ?? 0
        ),
        1
    )
    . "m<br><br>";


if (
    !is_array(
        $mappedSquad
    )
    ||
    count(
        $mappedPlayers
    )
    !== 15
) {

    echo "Captain recommendation analysis cannot continue because "
        . "the imported squad did not map completely.<br>";


    captainRecommendationRealFinish();

    exit;
}


/*
 * ============================================================
 * SCENARIO D
 * CURRENT FPL CAPTAINCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Current FPL Captaincy<br>";
echo "============================================<br>";


$currentCaptain =
    null;


$currentViceCaptain =
    null;


foreach (
    $mappedPlayers
    as $player
) {

    if (
        (
            $player[
                'is_captain'
            ]
            ?? false
        )
        === true
    ) {

        $currentCaptain =
            $player;
    }


    if (
        (
            $player[
                'is_vice_captain'
            ]
            ?? false
        )
        === true
    ) {

        $currentViceCaptain =
            $player;
    }
}


captainRecommendationRealCheck(
    'Imported squad identifies current captain',
    is_array(
        $currentCaptain
    )
);


captainRecommendationRealCheck(
    'Imported squad identifies current vice-captain',
    is_array(
        $currentViceCaptain
    )
);


if (
    $currentCaptain !== null
) {

    echo "Current Captain: <strong>"
        . htmlspecialchars(
            (string) (
                $currentCaptain[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "</strong><br>";
}


if (
    $currentViceCaptain !== null
) {

    echo "Current Vice-Captain: <strong>"
        . htmlspecialchars(
            (string) (
                $currentViceCaptain[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "</strong><br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * CAPTAIN RECOMMENDATIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Captain Recommendations<br>";
echo "============================================<br>";


$recommendationStartedAt =
    microtime(
        true
    );


$recommendationResult =
    $service
        ->getCaptainRecommendations(
            $mappedPlayers,
            $recommendationLimit
        );


$recommendationRuntime =
    microtime(
        true
    )
    -
    $recommendationStartedAt;


captainRecommendationRealCheck(
    'Captain recommendation service returns an array',
    is_array(
        $recommendationResult
    )
);


captainRecommendationRealCheck(
    'Captain recommendation service returns success',
    (
        $recommendationResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


$recommendedCaptain =
    $recommendationResult[
        'captain'
    ]
    ?? null;


$recommendedViceCaptain =
    $recommendationResult[
        'vice_captain'
    ]
    ?? null;


captainRecommendationRealCheck(
    'Recommended captain is returned',
    is_array(
        $recommendedCaptain
    )
);


captainRecommendationRealCheck(
    'Recommended vice-captain is returned',
    is_array(
        $recommendedViceCaptain
    )
);


captainRecommendationRealCheck(
    'Recommended captain has rank one',
    (
        $recommendedCaptain[
            'rank'
        ]
        ?? null
    )
    === 1
);


captainRecommendationRealCheck(
    'Recommended vice-captain has rank two',
    (
        $recommendedViceCaptain[
            'rank'
        ]
        ?? null
    )
    === 2
);


captainRecommendationRealCheck(
    'Recommended captain and vice-captain are different players',
    (
        $recommendedCaptain[
            'player_id'
        ]
        ?? null
    )
    !==
    (
        $recommendedViceCaptain[
            'player_id'
        ]
        ?? null
    )
);


echo "Recommendation Runtime: "
    . number_format(
        $recommendationRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * CAPTAIN COMPARISON
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Current vs Recommended Captaincy<br>";
echo "============================================<br>";


echo "Current Captain: <strong>"
    . htmlspecialchars(
        captainRecommendationRealName(
            $currentCaptain
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "</strong><br>";


echo "Recommended Captain: <strong>"
    . htmlspecialchars(
        captainRecommendationRealName(
            $recommendedCaptain
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "</strong>"
    . " | Captain "
    . captainRecommendationRealScore(
        $recommendedCaptain
    )
    . " | "
    . htmlspecialchars(
        captainRecommendationRealClassification(
            $recommendedCaptain
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Current Vice-Captain: <strong>"
    . htmlspecialchars(
        captainRecommendationRealName(
            $currentViceCaptain
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "</strong><br>";


echo "Recommended Vice-Captain: <strong>"
    . htmlspecialchars(
        captainRecommendationRealName(
            $recommendedViceCaptain
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "</strong>"
    . " | Captain "
    . captainRecommendationRealScore(
        $recommendedViceCaptain
    )
    . " | "
    . htmlspecialchars(
        captainRecommendationRealClassification(
            $recommendedViceCaptain
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


$currentCaptainRecommendation =
    null;


$currentViceCaptainRecommendation =
    null;


foreach (
    $recommendationResult[
        'rankings'
    ]
    ?? []
    as $ranking
) {

    if (
        $currentCaptain !== null
        &&
        (
            $ranking[
                'player_id'
            ]
            ?? null
        )
        ===
        (
            $currentCaptain[
                'player_id'
            ]
            ?? null
        )
    ) {

        $currentCaptainRecommendation =
            $ranking;
    }


    if (
        $currentViceCaptain !== null
        &&
        (
            $ranking[
                'player_id'
            ]
            ?? null
        )
        ===
        (
            $currentViceCaptain[
                'player_id'
            ]
            ?? null
        )
    ) {

        $currentViceCaptainRecommendation =
            $ranking;
    }
}


captainRecommendationRealCheck(
    'Current captain appears in Captain Intelligence ranking',
    $currentCaptain === null
    ||
    $currentCaptainRecommendation !== null
);


captainRecommendationRealCheck(
    'Current vice-captain appears in Captain Intelligence ranking',
    $currentViceCaptain === null
    ||
    $currentViceCaptainRecommendation !== null
);


if (
    $currentCaptainRecommendation !== null
) {

    echo "Current Captain Intelligence Rank: #"
        . (
            $currentCaptainRecommendation[
                'rank'
            ]
            ?? 'N/A'
        )
        . " | Score "
        . captainRecommendationRealScore(
            $currentCaptainRecommendation
        )
        . "<br>";
}


if (
    $currentViceCaptainRecommendation !== null
) {

    echo "Current Vice-Captain Intelligence Rank: #"
        . (
            $currentViceCaptainRecommendation[
                'rank'
            ]
            ?? 'N/A'
        )
        . " | Score "
        . captainRecommendationRealScore(
            $currentViceCaptainRecommendation
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * TOP FIVE CAPTAIN RANKING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Top Five Captain Ranking<br>";
echo "============================================<br>";


$topRecommendations =
    array_slice(
        $recommendationResult[
            'rankings'
        ]
        ?? [],
        0,
        5
    );


captainRecommendationRealCheck(
    'Top five Captain Intelligence rankings are available',
    count(
        $topRecommendations
    )
    === 5
);


foreach (
    $topRecommendations
    as $captain
) {

    $components =
        $captain[
            'components'
        ]
        ?? [];


    echo "<strong>#"
        . (
            $captain[
                'rank'
            ]
            ?? '?'
        )
        . " "
        . htmlspecialchars(
            (string) (
                $captain[
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
            $captain[
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
            $captain[
                'position'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " | Captain "
    . number_format(
        (float) (
            $captain[
                'captain_score'
            ]
            ?? 0
        ),
        2
    )
    . " | "
    . htmlspecialchars(
        (string) (
            $captain[
                'classification'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


    echo "STR "
        . number_format(
            (float) (
                $components[
                    'strength'
                ]
                ?? 0
            ),
            1
        )
        . " | RAW FIX "
        . number_format(
            (float) (
                $components[
                    'raw_fixture'
                ]
                ?? 0
            ),
            1
        )
        . " | FIX "
        . number_format(
            (float) (
                $components[
                    'fixture'
                ]
                ?? 0
            ),
            1
        )
        . " | THREAT "
        . number_format(
            (float) (
                $components[
                    'attacking_threat'
                ]
                ?? 0
            ),
            1
        )
        . " | CORE "
        . number_format(
            (float) (
                $components[
                    'core_score'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "CONF "
        . number_format(
            (float) (
                $components[
                    'confidence'
                ]
                ?? 0
            ),
            1
        )
        . "% | CONF MOD "
        . number_format(
            (float) (
                $components[
                    'confidence_modifier'
                ]
                ?? 0
            ),
            3
        )
        . " | AVAIL "
        . number_format(
            (float) (
                $components[
                    'availability'
                ]
                ?? 0
            ),
            1
        )
        . "% | AVAIL MOD "
        . number_format(
            (float) (
                $components[
                    'availability_modifier'
                ]
                ?? 0
            ),
            3
        )
        . "<br>";


    if (
        (
            $captain[
                'current_is_captain'
            ]
            ?? false
        )
        === true
    ) {

        echo "Current FPL Captain<br>";
    }


    if (
        (
            $captain[
                'current_is_vice_captain'
            ]
            ?? false
        )
        === true
    ) {

        echo "Current FPL Vice-Captain<br>";
    }


    echo "<br>";
}


/*
 * ============================================================
 * SCENARIO H
 * COMPLETE RANKING INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Complete Ranking Integrity<br>";
echo "============================================<br>";


$rankings =
    $recommendationResult[
        'rankings'
    ]
    ?? [];


captainRecommendationRealCheck(
    'Complete real squad ranking contains all 15 players',
    count(
        $rankings
    )
    === 15
);


captainRecommendationRealCheck(
    'Captain service evaluated all imported squad players',
    (
        $recommendationResult[
            'evaluated_count'
        ]
        ?? null
    )
    === 15
);


captainRecommendationRealCheck(
    'Captain service rejected no imported squad players',
    (
        $recommendationResult[
            'rejected_count'
        ]
        ?? null
    )
    === 0
);


$ranksSequential =
    true;


$scoresOrdered =
    true;


$allScoresNumeric =
    true;


$previousScore =
    null;


foreach (
    $rankings
    as $index => $ranking
) {

    if (
        (
            $ranking[
                'rank'
            ]
            ?? null
        )
        !==
        (
            $index + 1
        )
    ) {

        $ranksSequential =
            false;
    }


    $score =
        $ranking[
            'captain_score'
        ]
        ?? null;


    if (
        !is_numeric(
            $score
        )
    ) {

        $allScoresNumeric =
            false;
        $scoresOrdered =
            false;

        continue;
    }


    $score =
        (float) $score;


    if (
        $previousScore !== null
        &&
        $score > $previousScore
    ) {

        $scoresOrdered =
            false;
    }


    $previousScore =
        $score;
}


captainRecommendationRealCheck(
    'Real squad Captain ranks are sequential',
    $ranksSequential
);


captainRecommendationRealCheck(
    'Real squad Captain Scores are numeric',
    $allScoresNumeric
);


captainRecommendationRealCheck(
    'Real squad is ordered by Captain Score',
    $scoresOrdered
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * CAPTAIN / VICE METADATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Captaincy Metadata Integrity<br>";
echo "============================================<br>";


$currentCaptainCount =
    0;


$currentViceCaptainCount =
    0;


foreach (
    $rankings
    as $ranking
) {

    if (
        (
            $ranking[
                'current_is_captain'
            ]
            ?? false
        )
        === true
    ) {

        $currentCaptainCount++;
    }


    if (
        (
            $ranking[
                'current_is_vice_captain'
            ]
            ?? false
        )
        === true
    ) {

        $currentViceCaptainCount++;
    }
}


captainRecommendationRealCheck(
    'Exactly one current FPL captain is preserved',
    $currentCaptainCount === 1
);


captainRecommendationRealCheck(
    'Exactly one current FPL vice-captain is preserved',
    $currentViceCaptainCount === 1
);


echo "Current Captain Flags: "
    . $currentCaptainCount
    . "<br>";


echo "Current Vice-Captain Flags: "
    . $currentViceCaptainCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO J
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


captainRecommendationRealCheck(
    'Complete real-squad Captain diagnostic finishes within 15 seconds',
    $totalRuntime
    <= 15.0
);


echo "Import Runtime: "
    . number_format(
        $importRuntime,
        4
    )
    . " seconds<br>";


echo "Recommendation Runtime: "
    . number_format(
        $recommendationRuntime,
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

captainRecommendationRealFinish();