<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION SNAPSHOT TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * Establishes the immutable in-memory contract used to preserve
 * exactly what FPL Intelligence knew and recommended before a
 * gameweek deadline.
 *
 * This test deliberately does not involve database persistence.
 * Repository and capture-service behaviour will be introduced
 * separately after this domain contract is established.
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


function testThrows(
    callable $callback,
    string $message
): void {

    try {

        $callback();

        testResult(
            false,
            $message
        );

    } catch (Throwable $e) {

        testResult(
            true,
            $message
        );
    }
}


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

        'position' =>
            'MID',

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

        'position' =>
            'FWD',

        'projected_points' =>
            5.75,

        'projected_minutes' =>
            76.0,

        'projection_confidence' =>
            0.71
    ]
];


$startingXI = [

    [
        'player_id' =>
            1,

        'name' =>
            'Goalkeeper',

        'position' =>
            'GK'
    ],

    [
        'player_id' =>
            2,

        'name' =>
            'Defender One',

        'position' =>
            'DEF'
    ],

    [
        'player_id' =>
            3,

        'name' =>
            'Defender Two',

        'position' =>
            'DEF'
    ],

    [
        'player_id' =>
            4,

        'name' =>
            'Defender Three',

        'position' =>
            'DEF'
    ],

    [
        'player_id' =>
            5,

        'name' =>
            'Midfielder One',

        'position' =>
            'MID'
    ],

    [
        'player_id' =>
            6,

        'name' =>
            'Midfielder Two',

        'position' =>
            'MID'
    ],

    [
        'player_id' =>
            7,

        'name' =>
            'Midfielder Three',

        'position' =>
            'MID'
    ],

    [
        'player_id' =>
            8,

        'name' =>
            'Midfielder Four',

        'position' =>
            'MID'
    ],

    [
        'player_id' =>
            9,

        'name' =>
            'Forward One',

        'position' =>
            'FWD'
    ],

    [
        'player_id' =>
            10,

        'name' =>
            'Forward Two',

        'position' =>
            'FWD'
    ],

    [
        'player_id' =>
            11,

        'name' =>
            'Forward Three',

        'position' =>
            'FWD'
    ]
];


$captainRecommendation = [

    'captain' => [

        'player_id' =>
            5,

        'name' =>
            'Midfielder One',

        'captain_score' =>
            72.5
    ],

    'vice_captain' => [

        'player_id' =>
            9,

        'name' =>
            'Forward One',

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
        18.50,

    'key_insights' => [

        'Squad structure is currently stable.'
    ]
];


$chipRecommendations = [

    'Wildcard' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.62,

        'explanation' =>
            'Current Wildcard improvement is limited.'
    ],

    'Free Hit' => [

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.74,

        'explanation' =>
            'One-gameweek improvement is meaningful.'
    ],

    'Bench Boost' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.81,

        'explanation' =>
            'Projected bench value is below the required threshold.'
    ],

    'Triple Captain' => [

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.69,

        'explanation' =>
            'Captain opportunity is useful but not exceptional.'
    ]
];


$capturedAt =
    '2026-09-10 18:30:00';


$deadlineTime =
    '2026-09-11 18:30:00';


/*
 * ============================================================
 * A. VALID SNAPSHOT CONSTRUCTION
 * ============================================================
 */

echo "============================================<br>";
echo "A. Valid Snapshot Construction<br>";
echo "============================================<br>";


$snapshot =
    new RecommendationSnapshot(
        4,
        2702264,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );


testResult(
    $snapshot instanceof RecommendationSnapshot,
    'Valid evidence creates a RecommendationSnapshot.'
);


testResult(
    $snapshot->getGameweek() === 4,
    'Snapshot preserves the gameweek.'
);


testResult(
    $snapshot->getEntryId() === 2702264,
    'Snapshot preserves the FPL entry ID.'
);


echo "<br>";


/*
 * ============================================================
 * B. POSITIVE GAMEWEEK REQUIREMENT
 * ============================================================
 */

echo "============================================<br>";
echo "B. Positive Gameweek Requirement<br>";
echo "============================================<br>";


testThrows(
    static function () use (
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            0,
            2702264,
            $capturedAt,
            $deadlineTime,
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Gameweek zero is rejected.'
);


testThrows(
    static function () use (
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            -1,
            2702264,
            $capturedAt,
            $deadlineTime,
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Negative gameweek is rejected.'
);


echo "<br>";


/*
 * ============================================================
 * C. POSITIVE FPL ENTRY ID REQUIREMENT
 * ============================================================
 */

echo "============================================<br>";
echo "C. Positive FPL Entry ID Requirement<br>";
echo "============================================<br>";


testThrows(
    static function () use (
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            4,
            0,
            $capturedAt,
            $deadlineTime,
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Entry ID zero is rejected.'
);


testThrows(
    static function () use (
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            4,
            -100,
            $capturedAt,
            $deadlineTime,
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Negative entry ID is rejected.'
);


echo "<br>";


/*
 * ============================================================
 * D. CAPTURE TIMESTAMP REQUIREMENT
 * ============================================================
 */

echo "============================================<br>";
echo "D. Capture Timestamp Requirement<br>";
echo "============================================<br>";


testThrows(
    static function () use (
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            4,
            2702264,
            '',
            $deadlineTime,
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Empty capture timestamp is rejected.'
);


testThrows(
    static function () use (
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            4,
            2702264,
            'not-a-date',
            $deadlineTime,
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Invalid capture timestamp is rejected.'
);


echo "<br>";


/*
 * ============================================================
 * E. DEADLINE TIMESTAMP REQUIREMENT
 * ============================================================
 */

echo "============================================<br>";
echo "E. Deadline Timestamp Requirement<br>";
echo "============================================<br>";


testThrows(
    static function () use (
        $capturedAt,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            4,
            2702264,
            $capturedAt,
            '',
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Empty deadline timestamp is rejected.'
);


testThrows(
    static function () use (
        $capturedAt,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            4,
            2702264,
            $capturedAt,
            'invalid-deadline',
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Invalid deadline timestamp is rejected.'
);


echo "<br>";


/*
 * ============================================================
 * F. CAPTURE MUST OCCUR BEFORE DEADLINE
 * ============================================================
 */

echo "============================================<br>";
echo "F. Capture Must Occur Before Deadline<br>";
echo "============================================<br>";


testThrows(
    static function () use (
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            4,
            2702264,
            '2026-09-11 18:30:00',
            '2026-09-11 18:30:00',
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Capture exactly at the deadline is rejected.'
);


testThrows(
    static function () use (
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    ): void {

        new RecommendationSnapshot(
            4,
            2702264,
            '2026-09-11 18:31:00',
            '2026-09-11 18:30:00',
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );
    },
    'Capture after the deadline is rejected.'
);


echo "<br>";


/*
 * ============================================================
 * G. RECOMMENDATION SECTION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "G. Recommendation Section Contract<br>";
echo "============================================<br>";


testResult(
    is_array(
        $snapshot->getPlayerProjections()
    ),
    'Player projections are exposed as an array.'
);


testResult(
    is_array(
        $snapshot->getStartingXI()
    ),
    'Starting XI is exposed as an array.'
);


testResult(
    is_array(
        $snapshot->getCaptainRecommendation()
    ),
    'Captain recommendation is exposed as an array.'
);


testResult(
    is_array(
        $snapshot->getTransferRecommendations()
    ),
    'Transfer recommendations are exposed as an array.'
);


testResult(
    is_array(
        $snapshot->getGameweekDecision()
    ),
    'Gameweek Decision is exposed as an array.'
);


testResult(
    is_array(
        $snapshot->getChipRecommendations()
    ),
    'Chip recommendations are exposed as an array.'
);


echo "<br>";


/*
 * ============================================================
 * H. PLAYER PROJECTIONS PRESERVED
 * ============================================================
 */

echo "============================================<br>";
echo "H. Player Projections Preserved<br>";
echo "============================================<br>";


$storedProjections =
    $snapshot->getPlayerProjections();


testResult(
    $storedProjections === $playerProjections,
    'Player projection evidence is preserved exactly.'
);


testResult(
    (
        $storedProjections[0]['projected_points']
        ?? null
    ) === 7.25,
    'Projected points remain unchanged.'
);


testResult(
    (
        $storedProjections[0]['projected_minutes']
        ?? null
    ) === 82.0,
    'Projected minutes remain unchanged.'
);


testResult(
    (
        $storedProjections[0]['projection_confidence']
        ?? null
    ) === 0.78,
    'Projection confidence remains unchanged.'
);


testResult(
    (
        $storedProjections[0]['components']['attacking']
        ?? null
    ) === 4.2,
    'Projection component evidence remains unchanged.'
);


echo "<br>";


/*
 * ============================================================
 * I. STARTING XI PRESERVED
 * ============================================================
 */

echo "============================================<br>";
echo "I. Starting XI Preserved<br>";
echo "============================================<br>";


$storedStartingXI =
    $snapshot->getStartingXI();


testResult(
    $storedStartingXI === $startingXI,
    'Starting XI recommendation is preserved exactly.'
);


testResult(
    count(
        $storedStartingXI
    ) === 11,
    'All eleven recommended starters are preserved.'
);


testResult(
    (
        $storedStartingXI[0]['player_id']
        ?? null
    ) === 1,
    'Starting XI player identity is preserved.'
);


echo "<br>";


/*
 * ============================================================
 * J. CAPTAIN RECOMMENDATION PRESERVED
 * ============================================================
 */

echo "============================================<br>";
echo "J. Captain Recommendation Preserved<br>";
echo "============================================<br>";


$storedCaptain =
    $snapshot->getCaptainRecommendation();


testResult(
    $storedCaptain === $captainRecommendation,
    'Captain recommendation is preserved exactly.'
);


testResult(
    (
        $storedCaptain['captain']['player_id']
        ?? null
    ) === 5,
    'Recommended captain identity is preserved.'
);


testResult(
    (
        $storedCaptain['vice_captain']['player_id']
        ?? null
    ) === 9,
    'Recommended vice-captain identity is preserved.'
);


echo "<br>";


/*
 * ============================================================
 * K. TRANSFER RECOMMENDATIONS PRESERVED
 * ============================================================
 */

echo "============================================<br>";
echo "K. Transfer Recommendations Preserved<br>";
echo "============================================<br>";


$storedTransfers =
    $snapshot->getTransferRecommendations();


testResult(
    $storedTransfers === $transferRecommendations,
    'Transfer recommendation evidence is preserved exactly.'
);


testResult(
    (
        $storedTransfers['recommendation']
        ?? null
    ) === 'Roll Transfer',
    'Transfer recommendation action is preserved.'
);


testResult(
    (
        $storedTransfers['projected_gain']
        ?? null
    ) === 1.4,
    'Transfer projected gain is preserved.'
);


echo "<br>";


/*
 * ============================================================
 * L. GAMEWEEK DECISION PRESERVED
 * ============================================================
 */

echo "============================================<br>";
echo "L. Gameweek Decision Preserved<br>";
echo "============================================<br>";


$storedDecision =
    $snapshot->getGameweekDecision();


testResult(
    $storedDecision === $gameweekDecision,
    'Gameweek Decision evidence is preserved exactly.'
);


testResult(
    (
        $storedDecision['overall_action']
        ?? null
    ) === 'Hold',
    'Gameweek overall action is preserved.'
);


testResult(
    (
        $storedDecision['formation']
        ?? null
    ) === '3-4-3',
    'Recommended formation is preserved.'
);


echo "<br>";


/*
 * ============================================================
 * M. CHIP RECOMMENDATIONS PRESERVED
 * ============================================================
 */

echo "============================================<br>";
echo "M. Chip Recommendations Preserved<br>";
echo "============================================<br>";


$storedChips =
    $snapshot->getChipRecommendations();


testResult(
    $storedChips === $chipRecommendations,
    'Chip recommendation evidence is preserved exactly.'
);


testResult(
    (
        $storedChips['Wildcard']['recommendation']
        ?? null
    ) === 'Hold',
    'Wildcard recommendation is preserved.'
);


testResult(
    (
        $storedChips['Free Hit']['recommendation']
        ?? null
    ) === 'Consider',
    'Free Hit recommendation is preserved.'
);


testResult(
    (
        $storedChips['Bench Boost']['recommendation']
        ?? null
    ) === 'Hold',
    'Bench Boost recommendation is preserved.'
);


testResult(
    (
        $storedChips['Triple Captain']['recommendation']
        ?? null
    ) === 'Consider',
    'Triple Captain recommendation is preserved.'
);


echo "<br>";


/*
 * ============================================================
 * N. SNAPSHOT EXPORT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "N. Snapshot Export Contract<br>";
echo "============================================<br>";


$export =
    $snapshot->toArray();


testResult(
    is_array(
        $export
    ),
    'Snapshot exports to an array.'
);


testResult(
    (
        $export['gameweek']
        ?? null
    ) === 4,
    'Export contains the gameweek.'
);


testResult(
    (
        $export['entry_id']
        ?? null
    ) === 2702264,
    'Export contains the FPL entry ID.'
);


testResult(
    (
        $export['captured_at']
        ?? null
    ) === $capturedAt,
    'Export contains the capture timestamp.'
);


testResult(
    (
        $export['deadline_time']
        ?? null
    ) === $deadlineTime,
    'Export contains the deadline timestamp.'
);


testResult(
    (
        $export['player_projections']
        ?? null
    ) === $playerProjections,
    'Export contains the preserved player projections.'
);


testResult(
    (
        $export['starting_xi']
        ?? null
    ) === $startingXI,
    'Export contains the preserved Starting XI.'
);


testResult(
    (
        $export['captain_recommendation']
        ?? null
    ) === $captainRecommendation,
    'Export contains the preserved captain recommendation.'
);


testResult(
    (
        $export['transfer_recommendations']
        ?? null
    ) === $transferRecommendations,
    'Export contains the preserved transfer recommendations.'
);


testResult(
    (
        $export['gameweek_decision']
        ?? null
    ) === $gameweekDecision,
    'Export contains the preserved Gameweek Decision.'
);


testResult(
    (
        $export['chip_recommendations']
        ?? null
    ) === $chipRecommendations,
    'Export contains the preserved chip recommendations.'
);


echo "<br>";


/*
 * ============================================================
 * O. SNAPSHOT STATE IS NOT MUTATED THROUGH RETURNED ARRAYS
 * ============================================================
 */

echo "============================================<br>";
echo "O. Snapshot State Isolation<br>";
echo "============================================<br>";


$modifiedProjections =
    $snapshot->getPlayerProjections();


$modifiedProjections[0]['projected_points'] =
    99.99;


$freshProjections =
    $snapshot->getPlayerProjections();


testResult(
    (
        $freshProjections[0]['projected_points']
        ?? null
    ) === 7.25,
    'Changing a returned projection array does not mutate snapshot state.'
);


$modifiedCaptain =
    $snapshot->getCaptainRecommendation();


$modifiedCaptain['captain']['player_id'] =
    999;


$freshCaptain =
    $snapshot->getCaptainRecommendation();


testResult(
    (
        $freshCaptain['captain']['player_id']
        ?? null
    ) === 5,
    'Changing returned captain evidence does not mutate snapshot state.'
);


$modifiedExport =
    $snapshot->toArray();


$modifiedExport['gameweek'] =
    99;


$modifiedExport['chip_recommendations']['Wildcard']['recommendation'] =
    'Use';


$freshExport =
    $snapshot->toArray();


testResult(
    (
        $freshExport['gameweek']
        ?? null
    ) === 4,
    'Changing an exported snapshot does not mutate stored scalar state.'
);


testResult(
    (
        $freshExport['chip_recommendations']['Wildcard']['recommendation']
        ?? null
    ) === 'Hold',
    'Changing exported recommendation evidence does not mutate snapshot state.'
);


echo "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Recommendation Snapshot Test Summary<br>";
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