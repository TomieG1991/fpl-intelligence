<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION SNAPSHOT REPOSITORY TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * Establishes persistence behaviour for immutable historical
 * recommendation snapshots.
 *
 * The repository must:
 *
 * - preserve one authoritative snapshot per entry/gameweek;
 * - return the inserted historical evidence;
 * - refuse to overwrite an existing snapshot;
 * - keep different entries and gameweeks independent;
 * - preserve recommendation evidence exactly through JSON
 *   persistence.
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


function testResult(
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

    } else {

        $failed++;

        echo "FAIL: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }
}


/*
 * ============================================================
 * DATABASE CONNECTION
 * ============================================================
 */

$database =
    new Database();


$db =
    $database->getConnection();


$repository =
    new RecommendationSnapshotRepository(
        $db
    );


/*
 * ============================================================
 * LOCATE CONTROLLED GAMEWEEKS
 * ============================================================
 *
 * We use existing gameweek rows rather than inventing unrelated
 * gameweek identities.
 */

$gameweekStmt =
    $db->query(
        "
        SELECT
            id,
            fpl_gameweek_id,
            deadline_time
        FROM gameweeks
        WHERE deadline_time IS NOT NULL
        ORDER BY fpl_gameweek_id ASC
        LIMIT 2
        "
    );


$gameweeks =
    $gameweekStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


if (
    count(
        $gameweeks
    ) < 2
) {

    die(
        'RecommendationSnapshotRepositoryTest requires at least '
        . 'two gameweeks with deadline times.<br>'
    );
}


$firstGameweek =
    $gameweeks[0];


$secondGameweek =
    $gameweeks[1];


$firstGameweekId =
    (int) $firstGameweek['id'];


$secondGameweekId =
    (int) $secondGameweek['id'];


/*
 * ============================================================
 * CONTROLLED TEST ENTRY IDS
 * ============================================================
 *
 * These intentionally do not represent real FPL managers.
 */

$entryIdA =
    900000001;


$entryIdB =
    900000002;


/*
 * ============================================================
 * CLEAN PREVIOUS TEST DATA
 * ============================================================
 */

$cleanupStmt =
    $db->prepare(
        "
        DELETE FROM recommendation_snapshots
        WHERE entry_id IN (
            :entry_id_a,
            :entry_id_b
        )
        "
    );


$cleanupStmt->execute([

    ':entry_id_a' =>
        $entryIdA,

    ':entry_id_b' =>
        $entryIdB
]);


/*
 * ============================================================
 * CONTROLLED SNAPSHOT EVIDENCE
 * ============================================================
 */

$playerProjections = [

    [
        'player_id' =>
            101,

        'name' =>
            'Projection Player',

        'projected_points' =>
            7.25,

        'projected_minutes' =>
            82.0,

        'projection_confidence' =>
            0.78,

        'components' => [

            'appearance' =>
                1.8,

            'attacking' =>
                4.2
        ]
    ],

    [
        'player_id' =>
            102,

        'name' =>
            'Second Player',

        'projected_points' =>
            5.75,

        'projected_minutes' =>
            76.0,

        'projection_confidence' =>
            0.71
    ]
];


$startingXI = [

    ['player_id' => 1],
    ['player_id' => 2],
    ['player_id' => 3],
    ['player_id' => 4],
    ['player_id' => 5],
    ['player_id' => 6],
    ['player_id' => 7],
    ['player_id' => 8],
    ['player_id' => 9],
    ['player_id' => 10],
    ['player_id' => 11]
];


$captainRecommendation = [

    'captain' => [

        'player_id' =>
            5,

        'name' =>
            'Captain Player',

        'captain_score' =>
            72.5
    ],

    'vice_captain' => [

        'player_id' =>
            9,

        'name' =>
            'Vice Captain Player',

        'captain_score' =>
            68.0
    ]
];


$transferRecommendations = [

    'status' =>
        'success',

    'recommendation' =>
        'Roll Transfer',

    'projected_gain' =>
        1.4,

    'confidence' =>
        0.67
];


$gameweekDecision = [

    'status' =>
        'success',

    'overall_action' =>
        'Hold',

    'formation' =>
        '3-4-3',

    'starting_xi_score' =>
        71.25,

    'bench_score' =>
        18.50
];


$chipRecommendations = [

    'Wildcard' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.62
    ],

    'Free Hit' => [

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.74
    ],

    'Bench Boost' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.81
    ],

    'Triple Captain' => [

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.69
    ]
];


$capturedAt =
    '2026-08-01 12:00:00';


$deadlineTime =
    '2026-08-20 18:30:00';


$snapshot =
    new RecommendationSnapshot(
        (int) $firstGameweek['fpl_gameweek_id'],
        $entryIdA,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );


/*
 * ============================================================
 * A. INSERT NEW SNAPSHOT
 * ============================================================
 */

echo "============================================<br>";
echo "A. Insert New Snapshot<br>";
echo "============================================<br>";


$inserted =
    $repository->insertIfAbsent(
        $firstGameweekId,
        $snapshot
    );


testResult(
    $inserted === true,
    'A new recommendation snapshot is inserted.'
);


echo "<br>";


/*
 * ============================================================
 * B. RETRIEVE BY ENTRY AND GAMEWEEK
 * ============================================================
 */

echo "============================================<br>";
echo "B. Retrieve By Entry And Gameweek<br>";
echo "============================================<br>";


$stored =
    $repository->getByEntryAndGameweek(
        $entryIdA,
        $firstGameweekId
    );


testResult(
    is_array(
        $stored
    ),
    'Inserted snapshot can be retrieved.'
);


testResult(
    (
        (int) (
            $stored['gameweek_id']
            ?? 0
        )
    ) === $firstGameweekId,
    'Stored snapshot preserves the local gameweek ID.'
);


testResult(
    (
        (int) (
            $stored['entry_id']
            ?? 0
        )
    ) === $entryIdA,
    'Stored snapshot preserves the FPL entry ID.'
);


testResult(
    (
        $stored['captured_at']
        ?? null
    ) === $capturedAt,
    'Stored snapshot preserves the capture timestamp.'
);


testResult(
    (
        $stored['deadline_time']
        ?? null
    ) === $deadlineTime,
    'Stored snapshot preserves the deadline timestamp.'
);


echo "<br>";


/*
 * ============================================================
 * C. JSON EVIDENCE ROUND TRIP
 * ============================================================
 */

echo "============================================<br>";
echo "C. JSON Evidence Round Trip<br>";
echo "============================================<br>";


testResult(
    (
        $stored['player_projections']
        ?? null
    ) === $playerProjections,
    'Player projections survive persistence unchanged.'
);


testResult(
    (
        $stored['starting_xi']
        ?? null
    ) === $startingXI,
    'Starting XI survives persistence unchanged.'
);


testResult(
    (
        $stored['captain_recommendation']
        ?? null
    ) === $captainRecommendation,
    'Captain recommendation survives persistence unchanged.'
);


testResult(
    (
        $stored['transfer_recommendations']
        ?? null
    ) === $transferRecommendations,
    'Transfer recommendations survive persistence unchanged.'
);


testResult(
    (
        $stored['gameweek_decision']
        ?? null
    ) === $gameweekDecision,
    'Gameweek Decision survives persistence unchanged.'
);


testResult(
    (
        $stored['chip_recommendations']
        ?? null
    ) === $chipRecommendations,
    'Chip recommendations survive persistence unchanged.'
);


echo "<br>";


/*
 * ============================================================
 * D. DUPLICATE SNAPSHOT IS NOT INSERTED
 * ============================================================
 */

echo "============================================<br>";
echo "D. Duplicate Snapshot Is Not Inserted<br>";
echo "============================================<br>";


$replacementProjections =
    $playerProjections;


$replacementProjections[0]['projected_points'] =
    99.99;


$replacementCaptain =
    $captainRecommendation;


$replacementCaptain['captain']['player_id'] =
    999;


$replacementSnapshot =
    new RecommendationSnapshot(
        (int) $firstGameweek['fpl_gameweek_id'],
        $entryIdA,
        '2026-08-02 12:00:00',
        $deadlineTime,
        $replacementProjections,
        $startingXI,
        $replacementCaptain,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );


$duplicateInserted =
    $repository->insertIfAbsent(
        $firstGameweekId,
        $replacementSnapshot
    );


testResult(
    $duplicateInserted === false,
    'A second snapshot for the same entry/gameweek is rejected.'
);


echo "<br>";


/*
 * ============================================================
 * E. ORIGINAL HISTORICAL SNAPSHOT REMAINS UNCHANGED
 * ============================================================
 */

echo "============================================<br>";
echo "E. Original Historical Snapshot Remains Unchanged<br>";
echo "============================================<br>";


$storedAfterDuplicate =
    $repository->getByEntryAndGameweek(
        $entryIdA,
        $firstGameweekId
    );


testResult(
    (
        $storedAfterDuplicate['captured_at']
        ?? null
    ) === $capturedAt,
    'Duplicate capture does not replace the original capture time.'
);


testResult(
    (
        $storedAfterDuplicate[
            'player_projections'
        ][0][
            'projected_points'
        ]
        ?? null
    ) === 7.25,
    'Duplicate capture does not replace original projection evidence.'
);


testResult(
    (
        $storedAfterDuplicate[
            'captain_recommendation'
        ][
            'captain'
        ][
            'player_id'
        ]
        ?? null
    ) === 5,
    'Duplicate capture does not replace original captain evidence.'
);


echo "<br>";


/*
 * ============================================================
 * F. DIFFERENT ENTRY MAY USE SAME GAMEWEEK
 * ============================================================
 */

echo "============================================<br>";
echo "F. Different Entry May Use Same Gameweek<br>";
echo "============================================<br>";


$secondEntrySnapshot =
    new RecommendationSnapshot(
        (int) $firstGameweek['fpl_gameweek_id'],
        $entryIdB,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );


$secondEntryInserted =
    $repository->insertIfAbsent(
        $firstGameweekId,
        $secondEntrySnapshot
    );


testResult(
    $secondEntryInserted === true,
    'A different entry may store a snapshot for the same gameweek.'
);


$storedSecondEntry =
    $repository->getByEntryAndGameweek(
        $entryIdB,
        $firstGameweekId
    );


testResult(
    (
        (int) (
            $storedSecondEntry['entry_id']
            ?? 0
        )
    ) === $entryIdB,
    'Different entry snapshot is independently retrievable.'
);


echo "<br>";


/*
 * ============================================================
 * G. SAME ENTRY MAY USE DIFFERENT GAMEWEEK
 * ============================================================
 */

echo "============================================<br>";
echo "G. Same Entry May Use Different Gameweek<br>";
echo "============================================<br>";


$secondGameweekSnapshot =
    new RecommendationSnapshot(
        (int) $secondGameweek['fpl_gameweek_id'],
        $entryIdA,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );


$secondGameweekInserted =
    $repository->insertIfAbsent(
        $secondGameweekId,
        $secondGameweekSnapshot
    );


testResult(
    $secondGameweekInserted === true,
    'The same entry may store a snapshot for another gameweek.'
);


$storedSecondGameweek =
    $repository->getByEntryAndGameweek(
        $entryIdA,
        $secondGameweekId
    );


testResult(
    (
        (int) (
            $storedSecondGameweek['gameweek_id']
            ?? 0
        )
    ) === $secondGameweekId,
    'Different gameweek snapshot is independently retrievable.'
);


echo "<br>";


/*
 * ============================================================
 * H. MISSING SNAPSHOT RETURNS NULL
 * ============================================================
 */

echo "============================================<br>";
echo "H. Missing Snapshot Returns Null<br>";
echo "============================================<br>";


$missing =
    $repository->getByEntryAndGameweek(
        900000003,
        $firstGameweekId
    );


testResult(
    $missing === null,
    'Missing recommendation snapshot returns null.'
);


echo "<br>";


/*
 * ============================================================
 * I. RETRIEVE ENTRY HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "I. Retrieve Entry History<br>";
echo "============================================<br>";


$history =
    $repository->getByEntryId(
        $entryIdA
    );


testResult(
    is_array(
        $history
    ),
    'Entry recommendation history is returned as an array.'
);


testResult(
    count(
        $history
    ) === 2,
    'Entry history contains both stored gameweeks.'
);


testResult(
    (
        (int) (
            $history[0]['gameweek_id']
            ?? 0
        )
    ) === $firstGameweekId,
    'Entry history is ordered by gameweek ID ascending.'
);


testResult(
    (
        (int) (
            $history[1]['gameweek_id']
            ?? 0
        )
    ) === $secondGameweekId,
    'Later gameweek appears after the earlier gameweek.'
);


echo "<br>";


/*
 * ============================================================
 * CLEAN UP CONTROLLED TEST DATA
 * ============================================================
 */

$cleanupStmt->execute([

    ':entry_id_a' =>
        $entryIdA,

    ':entry_id_b' =>
        $entryIdB
]);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Recommendation Snapshot Repository Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if (
    $failed === 0
) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}