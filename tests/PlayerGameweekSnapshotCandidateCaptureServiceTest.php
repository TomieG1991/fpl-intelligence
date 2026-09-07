<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Capture Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function candidateCaptureAssert(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


function candidateCaptureSection(
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
 * SYNTHETIC PLAYER STATE
 * ============================================================
 */

$players = [

    [
        'id' =>
            101,

        'fpl_player_id' =>
            501,

        'team_id' =>
            11,

        'position' =>
            'MID',

        'price' =>
            '7.5',

        'selected_by_percent' =>
            '12.5',

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
            '22.4',

        'expected_goals' =>
            '0.75',

        'expected_assists' =>
            '0.62',

        'expected_goal_involvements' =>
            '1.37'
    ],

    [
        'id' =>
            102,

        'fpl_player_id' =>
            502,

        'team_id' =>
            12,

        'position' =>
            'DEF',

        'price' =>
            '5.0',

        'selected_by_percent' =>
            '4.2',

        'chance_of_playing' =>
            null,

        'status' =>
            'a',

        'news' =>
            'Synthetic player news',

        'minutes' =>
            90,

        'goals' =>
            0,

        'assists' =>
            0,

        'clean_sheets' =>
            1,

        'bonus' =>
            1,

        'bps' =>
            24,

        'ict_index' =>
            '8.1',

        'expected_goals' =>
            '0.10',

        'expected_assists' =>
            '0.15',

        'expected_goal_involvements' =>
            '0.25'
    ]
];


/*
 * ============================================================
 * A. CLASS CONTRACT
 * ============================================================
 */

candidateCaptureSection(
    'A. Class Contract'
);


candidateCaptureAssert(
    class_exists(
        'PlayerGameweekSnapshotCandidateCaptureService'
    ),
    'PlayerGameweekSnapshotCandidateCaptureService class exists.'
);


if (
    !class_exists(
        'PlayerGameweekSnapshotCandidateCaptureService'
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Player Gameweek Snapshot Candidate Capture Service Test Summary<br>";
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
 * B. BUILD CANDIDATES
 * ============================================================
 */

candidateCaptureSection(
    'B. Build Candidates'
);


$service =
    new PlayerGameweekSnapshotCandidateCaptureService();


$generatedAt =
    '2026-09-18 17:30:00';


$deadlineTime =
    '2026-09-18 18:30:00';


$candidates =
    $service->buildCandidates(
        8,
        $generatedAt,
        $deadlineTime,
        $players
    );


candidateCaptureAssert(
    count(
        $candidates
    )
    ===
    2,
    'One candidate is built for each valid live player.'
);


candidateCaptureAssert(
    $candidates[
        0
    ]
    instanceof PlayerGameweekSnapshotCandidate,
    'First result is a PlayerGameweekSnapshotCandidate.'
);


candidateCaptureAssert(
    $candidates[
        1
    ]
    instanceof PlayerGameweekSnapshotCandidate,
    'Second result is a PlayerGameweekSnapshotCandidate.'
);


/*
 * ============================================================
 * C. GAMEWEEK LIFECYCLE
 * ============================================================
 */

candidateCaptureSection(
    'C. Gameweek Lifecycle'
);


candidateCaptureAssert(
    $candidates[
        0
    ]->getGameweekId()
    ===
    8,
    'Candidate targets supplied pre-deadline gameweek.'
);


candidateCaptureAssert(
    $candidates[
        0
    ]->getGeneratedAt()
    ===
    $generatedAt,
    'Candidate preserves generation timestamp.'
);


candidateCaptureAssert(
    $candidates[
        0
    ]->getDeadlineTime()
    ===
    $deadlineTime,
    'Candidate preserves gameweek deadline.'
);


/*
 * ============================================================
 * D. LIVE PLAYER STATE MAPPING
 * ============================================================
 */

candidateCaptureSection(
    'D. Live Player State Mapping'
);


$firstState =
    $candidates[
        0
    ]->getPlayerState();


candidateCaptureAssert(
    (
        $firstState[
            'player_id'
        ]
        ?? null
    )
    ===
    101,
    'Local player identity is preserved.'
);


candidateCaptureAssert(
    (
        $firstState[
            'fpl_player_id'
        ]
        ?? null
    )
    ===
    501,
    'FPL player identity is preserved.'
);


candidateCaptureAssert(
    (
        $firstState[
            'team_id'
        ]
        ?? null
    )
    ===
    11,
    'Team identity is preserved.'
);


candidateCaptureAssert(
    (
        $firstState[
            'position'
        ]
        ?? null
    )
    ===
    'MID',
    'Position is preserved.'
);


candidateCaptureAssert(
    (
        $firstState[
            'price'
        ]
        ?? null
    )
    ===
    7.5,
    'Price is normalised to numeric live evidence.'
);


candidateCaptureAssert(
    (
        $firstState[
            'selected_by_percent'
        ]
        ?? null
    )
    ===
    12.5,
    'Live ownership percentage is preserved.'
);


candidateCaptureAssert(
    array_key_exists(
        'selected',
        $firstState
    )
    &&
    $firstState[
        'selected'
    ]
    ===
    null,
    'Unavailable raw selected-manager count remains null.'
);


candidateCaptureAssert(
    (
        $firstState[
            'chance_of_playing'
        ]
        ?? null
    )
    ===
    100,
    'Availability evidence is preserved.'
);


candidateCaptureAssert(
    (
        $firstState[
            'minutes'
        ]
        ?? null
    )
    ===
    180,
    'Cumulative minutes are preserved.'
);


candidateCaptureAssert(
    (
        $firstState[
            'expected_goal_involvements'
        ]
        ?? null
    )
    ===
    1.37,
    'Cumulative expected goal involvements are preserved.'
);


/*
 * ============================================================
 * E. NULL AND NEWS PRESERVATION
 * ============================================================
 */

candidateCaptureSection(
    'E. Null And News Preservation'
);


$secondState =
    $candidates[
        1
    ]->getPlayerState();


candidateCaptureAssert(
    array_key_exists(
        'chance_of_playing',
        $secondState
    )
    &&
    $secondState[
        'chance_of_playing'
    ]
    ===
    null,
    'Null chance-of-playing evidence remains null.'
);


candidateCaptureAssert(
    (
        $secondState[
            'news'
        ]
        ?? null
    )
    ===
    'Synthetic player news',
    'Time-sensitive player news is preserved.'
);


/*
 * ============================================================
 * F. BLANK-GAMEWEEK COMPATIBILITY
 * ============================================================
 */

candidateCaptureSection(
    'F. Blank-Gameweek Compatibility'
);


candidateCaptureAssert(
    !array_key_exists(
        'fixture_id',
        $firstState
    ),
    'Candidate construction does not require fixture ID.'
);


candidateCaptureAssert(
    !array_key_exists(
        'fixture_history',
        $firstState
    ),
    'Candidate construction does not require fixture history.'
);


/*
 * ============================================================
 * G. INVALID PLAYER IDENTITIES ARE SKIPPED
 * ============================================================
 */

candidateCaptureSection(
    'G. Invalid Player Identities Are Skipped'
);


$playersWithInvalidRows =
    $players;


$playersWithInvalidRows[] = [

    'id' =>
        0,

    'fpl_player_id' =>
        999,

    'team_id' =>
        11
];


$playersWithInvalidRows[] = [

    'id' =>
        999,

    'fpl_player_id' =>
        0,

    'team_id' =>
        11
];


$playersWithInvalidRows[] = [

    'id' =>
        998,

    'fpl_player_id' =>
        998,

    'team_id' =>
        0
];


$validCandidates =
    $service->buildCandidates(
        8,
        $generatedAt,
        $deadlineTime,
        $playersWithInvalidRows
    );


candidateCaptureAssert(
    count(
        $validCandidates
    )
    ===
    2,
    'Players with invalid relational identities are skipped.'
);


/*
 * ============================================================
 * H. PRE-DEADLINE BOUNDARY
 * ============================================================
 */

candidateCaptureSection(
    'H. Pre-Deadline Boundary'
);


$atDeadlineRejected =
    false;


try {

    $service->buildCandidates(
        8,
        $deadlineTime,
        $deadlineTime,
        $players
    );

} catch (
    InvalidArgumentException $exception
) {

    $atDeadlineRejected =
        true;
}


candidateCaptureAssert(
    $atDeadlineRejected,
    'Candidate capture at the deadline is rejected.'
);


$afterDeadlineRejected =
    false;


try {

    $service->buildCandidates(
        8,
        '2026-09-18 18:31:00',
        $deadlineTime,
        $players
    );

} catch (
    InvalidArgumentException $exception
) {

    $afterDeadlineRejected =
        true;
}


candidateCaptureAssert(
    $afterDeadlineRejected,
    'Candidate capture after the deadline is rejected.'
);


/*
 * ============================================================
 * I. INVALID GAMEWEEK
 * ============================================================
 */

candidateCaptureSection(
    'I. Invalid Gameweek'
);


$invalidGameweekRejected =
    false;


try {

    $service->buildCandidates(
        0,
        $generatedAt,
        $deadlineTime,
        $players
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekRejected =
        true;
}


candidateCaptureAssert(
    $invalidGameweekRejected,
    'Non-positive gameweek identity is rejected.'
);


/*
 * ============================================================
 * J. EMPTY PLAYER POOL
 * ============================================================
 */

candidateCaptureSection(
    'J. Empty Player Pool'
);


$emptyCandidates =
    $service->buildCandidates(
        8,
        $generatedAt,
        $deadlineTime,
        []
    );


candidateCaptureAssert(
    $emptyCandidates
    ===
    [],
    'Empty live player pool produces no candidates.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Capture Service Test Summary<br>";
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