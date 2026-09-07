<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Test<br>";
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

function snapshotCandidateCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Class Contract<br>";
echo "============================================<br>";


snapshotCandidateCheck(
    'PlayerGameweekSnapshotCandidate class exists',
    class_exists(
        'PlayerGameweekSnapshotCandidate'
    )
);


if (
    !class_exists(
        'PlayerGameweekSnapshotCandidate'
    )
) {

    echo "<br>";

    echo "============================================<br>";
    echo "Player Gameweek Snapshot Candidate Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


/*
 * ============================================================
 * SHARED VALID CANDIDATE STATE
 * ============================================================
 */

$playerState = [

    'player_id' =>
        123,

    'fpl_player_id' =>
        456,

    'team_id' =>
        7,

    'position' =>
        'MID',

    'price' =>
        7.5,

    'selected' =>
        1234567,

    'selected_by_percent' =>
        12.5,

    'chance_of_playing' =>
        100,

    'status' =>
        'a',

    'news' =>
        '',

    'minutes' =>
        180,

    'goals' =>
        1,

    'assists' =>
        2,

    'clean_sheets' =>
        1,

    'bonus' =>
        3,

    'bps' =>
        48,

    'ict_index' =>
        22.4,

    'expected_goals' =>
        0.75,

    'expected_assists' =>
        0.62,

    'expected_goal_involvements' =>
        1.37
];


$candidate =
    new PlayerGameweekSnapshotCandidate(
        8,
        '2026-09-18 17:30:00',
        '2026-09-18 18:30:00',
        $playerState
    );


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * IDENTITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Identity<br>";
echo "============================================<br>";


snapshotCandidateCheck(
    'Candidate preserves local gameweek identity',
    $candidate->getGameweekId()
    ===
    8
);


snapshotCandidateCheck(
    'Candidate preserves player identity',
    $candidate->getPlayerId()
    ===
    123
);


snapshotCandidateCheck(
    'Candidate preserves FPL player identity',
    $candidate->getFplPlayerId()
    ===
    456
);


snapshotCandidateCheck(
    'Candidate preserves team identity',
    $candidate->getTeamId()
    ===
    7
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * PRE-DEADLINE LIFECYCLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Pre-Deadline Lifecycle<br>";
echo "============================================<br>";


snapshotCandidateCheck(
    'Candidate preserves generation timestamp',
    $candidate->getGeneratedAt()
    ===
    '2026-09-18 17:30:00'
);


snapshotCandidateCheck(
    'Candidate preserves deadline timestamp',
    $candidate->getDeadlineTime()
    ===
    '2026-09-18 18:30:00'
);


$atDeadlineRejected =
    false;


try {

    new PlayerGameweekSnapshotCandidate(
        8,
        '2026-09-18 18:30:00',
        '2026-09-18 18:30:00',
        $playerState
    );

} catch (
    InvalidArgumentException $exception
) {

    $atDeadlineRejected =
        true;
}


snapshotCandidateCheck(
    'Candidate generated exactly at deadline is rejected',
    $atDeadlineRejected
);


$afterDeadlineRejected =
    false;


try {

    new PlayerGameweekSnapshotCandidate(
        8,
        '2026-09-18 18:31:00',
        '2026-09-18 18:30:00',
        $playerState
    );

} catch (
    InvalidArgumentException $exception
) {

    $afterDeadlineRejected =
        true;
}


snapshotCandidateCheck(
    'Candidate generated after deadline is rejected',
    $afterDeadlineRejected
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * INVALID GAMEWEEK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Invalid Gameweek<br>";
echo "============================================<br>";


$invalidGameweekRejected =
    false;


try {

    new PlayerGameweekSnapshotCandidate(
        0,
        '2026-09-18 17:30:00',
        '2026-09-18 18:30:00',
        $playerState
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekRejected =
        true;
}


snapshotCandidateCheck(
    'Non-positive gameweek identity is rejected',
    $invalidGameweekRejected
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * INVALID PLAYER IDENTITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Invalid Player Identity<br>";
echo "============================================<br>";


$invalidPlayerState =
    $playerState;


$invalidPlayerState[
    'player_id'
] =
    0;


$invalidPlayerRejected =
    false;


try {

    new PlayerGameweekSnapshotCandidate(
        8,
        '2026-09-18 17:30:00',
        '2026-09-18 18:30:00',
        $invalidPlayerState
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidPlayerRejected =
        true;
}


snapshotCandidateCheck(
    'Non-positive local player identity is rejected',
    $invalidPlayerRejected
);


$invalidFplPlayerState =
    $playerState;


$invalidFplPlayerState[
    'fpl_player_id'
] =
    0;


$invalidFplPlayerRejected =
    false;


try {

    new PlayerGameweekSnapshotCandidate(
        8,
        '2026-09-18 17:30:00',
        '2026-09-18 18:30:00',
        $invalidFplPlayerState
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidFplPlayerRejected =
        true;
}


snapshotCandidateCheck(
    'Non-positive FPL player identity is rejected',
    $invalidFplPlayerRejected
);


$invalidTeamState =
    $playerState;


$invalidTeamState[
    'team_id'
] =
    0;


$invalidTeamRejected =
    false;


try {

    new PlayerGameweekSnapshotCandidate(
        8,
        '2026-09-18 17:30:00',
        '2026-09-18 18:30:00',
        $invalidTeamState
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidTeamRejected =
        true;
}


snapshotCandidateCheck(
    'Non-positive team identity is rejected',
    $invalidTeamRejected
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * INVALID TIMESTAMPS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Invalid Timestamps<br>";
echo "============================================<br>";


$invalidGeneratedRejected =
    false;


try {

    new PlayerGameweekSnapshotCandidate(
        8,
        'not-a-date',
        '2026-09-18 18:30:00',
        $playerState
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGeneratedRejected =
        true;
}


snapshotCandidateCheck(
    'Invalid generation timestamp is rejected',
    $invalidGeneratedRejected
);


$invalidDeadlineRejected =
    false;


try {

    new PlayerGameweekSnapshotCandidate(
        8,
        '2026-09-18 17:30:00',
        'not-a-date',
        $playerState
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidDeadlineRejected =
        true;
}


snapshotCandidateCheck(
    'Invalid deadline timestamp is rejected',
    $invalidDeadlineRejected
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * PLAYER STATE PRESERVATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Player State Preservation<br>";
echo "============================================<br>";


snapshotCandidateCheck(
    'Candidate preserves player state exactly',
    $candidate->getPlayerState()
    ===
    $playerState
);


snapshotCandidateCheck(
    'Blank-gameweek eligibility does not require fixture evidence',
    !array_key_exists(
        'fixture_id',
        $candidate->getPlayerState()
    )
    &&
    !array_key_exists(
        'fixture_history',
        $candidate->getPlayerState()
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * EXPORT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Export Contract<br>";
echo "============================================<br>";


$export =
    $candidate->toArray();


snapshotCandidateCheck(
    'Candidate exports an array',
    is_array(
        $export
    )
);


snapshotCandidateCheck(
    'Export preserves gameweek identity',
    (
        $export[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    8
);


snapshotCandidateCheck(
    'Export preserves generation timestamp',
    (
        $export[
            'generated_at'
        ]
        ?? null
    )
    ===
    '2026-09-18 17:30:00'
);


snapshotCandidateCheck(
    'Export preserves deadline timestamp',
    (
        $export[
            'deadline_time'
        ]
        ?? null
    )
    ===
    '2026-09-18 18:30:00'
);


snapshotCandidateCheck(
    'Export preserves exact player state',
    (
        $export[
            'player_state'
        ]
        ?? null
    )
    ===
    $playerState
);


snapshotCandidateCheck(
    'Export does not manufacture fixture-history eligibility',
    !array_key_exists(
        'fixture_history',
        $export
    )
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Test Summary<br>";
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

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}