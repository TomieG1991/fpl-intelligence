<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * PLAYER GAMEWEEK SNAPSHOT CANDIDATE REPOSITORY TEST
 * ============================================================
 *
 * Player snapshot candidates are mutable pre-deadline
 * staging evidence.
 *
 * For each player/gameweek:
 *
 * - the first candidate is inserted
 * - a strictly newer candidate replaces the existing candidate
 * - an older candidate must NOT replace a newer candidate
 * - an equal-timestamp candidate must NOT replace it
 * - different players remain independent
 * - different gameweeks remain independent
 *
 * No fixture-history evidence is required.
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


function snapshotCandidateRepositoryAssert(
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


function snapshotCandidateRepositorySection(
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
 * DATABASE
 * ============================================================
 */

$database =
    new Database();


$db =
    $database
        ->getConnection();


/*
 * ============================================================
 * FIND TWO REAL LOCAL GAMEWEEKS
 * ============================================================
 */

$gameweekStatement =
    $db->query(
        "
        SELECT
            id,
            deadline_time
        FROM gameweeks
        WHERE deadline_time IS NOT NULL
        ORDER BY id ASC
        LIMIT 2
        "
    );


$gameweekRows =
    $gameweekStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


if (
    count(
        $gameweekRows
    )
    <
    2
) {

    die(
        'PlayerGameweekSnapshotCandidateRepositoryTest '
        . 'requires at least two valid gameweeks.'
    );
}


$gameweekIdOne =
    (int) $gameweekRows[
        0
    ][
        'id'
    ];


$gameweekIdTwo =
    (int) $gameweekRows[
        1
    ][
        'id'
    ];


$deadlineOne =
    (string) $gameweekRows[
        0
    ][
        'deadline_time'
    ];


$deadlineTwo =
    (string) $gameweekRows[
        1
    ][
        'deadline_time'
    ];


/*
 * ============================================================
 * FIND TWO REAL PLAYERS
 * ============================================================
 */

$playerStatement =
    $db->query(
        "
        SELECT
            id,
            fpl_player_id,
            team_id,
            position
        FROM players
        WHERE id > 0
          AND fpl_player_id > 0
          AND team_id > 0
        ORDER BY id ASC
        LIMIT 2
        "
    );


$playerRows =
    $playerStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


if (
    count(
        $playerRows
    )
    <
    2
) {

    die(
        'PlayerGameweekSnapshotCandidateRepositoryTest '
        . 'requires at least two valid players.'
    );
}


$playerOne =
    $playerRows[
        0
    ];


$playerTwo =
    $playerRows[
        1
    ];


$playerIdOne =
    (int) $playerOne[
        'id'
    ];


$playerIdTwo =
    (int) $playerTwo[
        'id'
    ];


/*
 * ============================================================
 * TIMESTAMPS
 * ============================================================
 */

$deadlineTimestampOne =
    strtotime(
        $deadlineOne
    );


$deadlineTimestampTwo =
    strtotime(
        $deadlineTwo
    );


if (
    $deadlineTimestampOne === false
    ||
    $deadlineTimestampTwo === false
) {

    die(
        'PlayerGameweekSnapshotCandidateRepositoryTest '
        . 'could not parse gameweek deadlines.'
    );
}


$generatedEarlyOne =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
        -
        7200
    );


$generatedLaterOne =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
        -
        3600
    );


$generatedOlderOne =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
        -
        10800
    );


$generatedGameweekTwo =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampTwo
        -
        3600
    );


/*
 * ============================================================
 * PLAYER STATE FACTORY
 * ============================================================
 */

function buildSnapshotCandidatePlayerState(
    array $player,
    string $label,
    float $price,
    ?int $selected
): array {

    return [

        'player_id' =>
            (int) $player[
                'id'
            ],

        'fpl_player_id' =>
            (int) $player[
                'fpl_player_id'
            ],

        'team_id' =>
            (int) $player[
                'team_id'
            ],

        'position' =>
            $player[
                'position'
            ]
            ?? null,

        'price' =>
            $price,

        'selected' =>
            $selected,

        'selected_by_percent' =>
            12.5,

        'chance_of_playing' =>
            100,

        'status' =>
            'a',

        'news' =>
            $label,

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
}


/*
 * ============================================================
 * CANDIDATE FACTORY
 * ============================================================
 */

function buildSnapshotCandidate(
    int $gameweekId,
    string $generatedAt,
    string $deadlineTime,
    array $player,
    string $label,
    float $price,
    ?int $selected
): PlayerGameweekSnapshotCandidate {

    return
        new PlayerGameweekSnapshotCandidate(
            $gameweekId,
            $generatedAt,
            $deadlineTime,
            buildSnapshotCandidatePlayerState(
                $player,
                $label,
                $price,
                $selected
            )
        );
}


/*
 * ============================================================
 * CLEANUP
 * ============================================================
 *
 * Only candidate rows for the two selected test players and
 * gameweeks are touched.
 */

$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM player_gameweek_snapshot_candidates
        WHERE player_id IN (
            :player_one,
            :player_two
        )
        AND gameweek_id IN (
            :gameweek_one,
            :gameweek_two
        )
        "
    );


$cleanupParameters = [

    'player_one' =>
        $playerIdOne,

    'player_two' =>
        $playerIdTwo,

    'gameweek_one' =>
        $gameweekIdOne,

    'gameweek_two' =>
        $gameweekIdTwo
];


$cleanupStatement
    ->execute(
        $cleanupParameters
    );


/*
 * ============================================================
 * A. CLASS CONTRACT
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'A. Class Contract'
);


snapshotCandidateRepositoryAssert(
    class_exists(
        'PlayerGameweekSnapshotCandidateRepository'
    ),
    'PlayerGameweekSnapshotCandidateRepository class exists.'
);


if (
    !class_exists(
        'PlayerGameweekSnapshotCandidateRepository'
    )
) {

    /*
     * Clean up again defensively.
     */
    $cleanupStatement
        ->execute(
            $cleanupParameters
        );


    echo "<br>";
    echo "============================================<br>";
    echo "Player Gameweek Snapshot Candidate Repository Test Summary<br>";
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
 * REPOSITORY
 * ============================================================
 */

$repository =
    new PlayerGameweekSnapshotCandidateRepository(
        $db
    );


/*
 * ============================================================
 * B. INSERT FIRST CANDIDATE
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'B. Insert First Candidate'
);


$earlyCandidate =
    buildSnapshotCandidate(
        $gameweekIdOne,
        $generatedEarlyOne,
        $deadlineOne,
        $playerOne,
        'EARLY',
        7.5,
        100000
    );


$insertResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $earlyCandidate
        );


snapshotCandidateRepositoryAssert(
    $insertResult === true,
    'First candidate is stored.'
);


/*
 * ============================================================
 * C. RETRIEVE CANDIDATE
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'C. Retrieve Candidate'
);


$stored =
    $repository
        ->getByPlayerAndGameweek(
            $playerIdOne,
            $gameweekIdOne
        );


snapshotCandidateRepositoryAssert(
    is_array(
        $stored
    ),
    'Stored candidate can be retrieved.'
);


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekIdOne,
    'Stored candidate preserves local gameweek ID.'
);


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'player_id'
        ]
        ?? null
    )
    ===
    $playerIdOne,
    'Stored candidate preserves local player ID.'
);


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'fpl_player_id'
        ]
        ?? null
    )
    ===
    (int) $playerOne[
        'fpl_player_id'
    ],
    'Stored candidate preserves FPL player ID.'
);


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'team_id'
        ]
        ?? null
    )
    ===
    (int) $playerOne[
        'team_id'
    ],
    'Stored candidate preserves team ID.'
);


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedEarlyOne,
    'Stored candidate preserves generated timestamp.'
);


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'deadline_time'
        ]
        ?? null
    )
    ===
    $deadlineOne,
    'Stored candidate preserves deadline timestamp.'
);


/*
 * ============================================================
 * D. PLAYER STATE ROUND TRIP
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'D. Player State Round Trip'
);


$earlyState =
    buildSnapshotCandidatePlayerState(
        $playerOne,
        'EARLY',
        7.5,
        100000
    );


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'player_state'
        ]
        ?? null
    )
    ===
    $earlyState,
    'Complete player state survives persistence round trip.'
);


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'player_state'
        ][
            'selected'
        ]
        ?? null
    )
    ===
    100000,
    'Raw selected-manager count survives persistence.'
);


snapshotCandidateRepositoryAssert(
    (
        $stored[
            'player_state'
        ][
            'news'
        ]
        ?? null
    )
    ===
    'EARLY',
    'Time-sensitive player news survives persistence.'
);


/*
 * ============================================================
 * E. NEWER CANDIDATE REPLACES OLDER
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'E. Newer Candidate Replaces Older Candidate'
);


$laterCandidate =
    buildSnapshotCandidate(
        $gameweekIdOne,
        $generatedLaterOne,
        $deadlineOne,
        $playerOne,
        'LATER',
        7.6,
        110000
    );


$newerResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $laterCandidate
        );


snapshotCandidateRepositoryAssert(
    $newerResult === true,
    'Strictly newer candidate is accepted.'
);


$storedLater =
    $repository
        ->getByPlayerAndGameweek(
            $playerIdOne,
            $gameweekIdOne
        );


$laterState =
    buildSnapshotCandidatePlayerState(
        $playerOne,
        'LATER',
        7.6,
        110000
    );


snapshotCandidateRepositoryAssert(
    (
        $storedLater[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedLaterOne,
    'Newer generated timestamp replaces older timestamp.'
);


snapshotCandidateRepositoryAssert(
    (
        $storedLater[
            'player_state'
        ]
        ?? null
    )
    ===
    $laterState,
    'Newer player state replaces older player state.'
);


/*
 * ============================================================
 * F. OLDER CANDIDATE CANNOT REPLACE NEWER
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'F. Older Candidate Cannot Replace Newer Candidate'
);


$olderCandidate =
    buildSnapshotCandidate(
        $gameweekIdOne,
        $generatedOlderOne,
        $deadlineOne,
        $playerOne,
        'STALE',
        4.0,
        1
    );


$olderResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $olderCandidate
        );


snapshotCandidateRepositoryAssert(
    $olderResult === false,
    'Older candidate is rejected when newer evidence exists.'
);


$storedAfterOlder =
    $repository
        ->getByPlayerAndGameweek(
            $playerIdOne,
            $gameweekIdOne
        );


snapshotCandidateRepositoryAssert(
    (
        $storedAfterOlder[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedLaterOne,
    'Older candidate does not replace newer timestamp.'
);


snapshotCandidateRepositoryAssert(
    (
        $storedAfterOlder[
            'player_state'
        ]
        ?? null
    )
    ===
    $laterState,
    'Older candidate does not replace newer player state.'
);


/*
 * ============================================================
 * G. SAME TIMESTAMP DOES NOT REPLACE EXISTING
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'G. Same Timestamp Does Not Replace Existing Candidate'
);


$sameTimeCandidate =
    buildSnapshotCandidate(
        $gameweekIdOne,
        $generatedLaterOne,
        $deadlineOne,
        $playerOne,
        'SAME-TIME',
        15.0,
        999999
    );


$sameTimeResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $sameTimeCandidate
        );


snapshotCandidateRepositoryAssert(
    $sameTimeResult === false,
    'Equal-timestamp candidate is rejected.'
);


$storedAfterSameTime =
    $repository
        ->getByPlayerAndGameweek(
            $playerIdOne,
            $gameweekIdOne
        );


snapshotCandidateRepositoryAssert(
    (
        $storedAfterSameTime[
            'player_state'
        ]
        ?? null
    )
    ===
    $laterState,
    'Equal-timestamp candidate does not replace existing state.'
);


/*
 * ============================================================
 * H. DIFFERENT PLAYER IS INDEPENDENT
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'H. Different Player Is Independent'
);


$playerTwoCandidate =
    buildSnapshotCandidate(
        $gameweekIdOne,
        $generatedLaterOne,
        $deadlineOne,
        $playerTwo,
        'PLAYER-TWO',
        5.5,
        50000
    );


$playerTwoResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $playerTwoCandidate
        );


snapshotCandidateRepositoryAssert(
    $playerTwoResult === true,
    'Different player can store an independent candidate.'
);


$storedPlayerTwo =
    $repository
        ->getByPlayerAndGameweek(
            $playerIdTwo,
            $gameweekIdOne
        );


snapshotCandidateRepositoryAssert(
    is_array(
        $storedPlayerTwo
    ),
    'Different player candidate can be retrieved.'
);


snapshotCandidateRepositoryAssert(
    (
        $storedPlayerTwo[
            'player_state'
        ][
            'news'
        ]
        ?? null
    )
    ===
    'PLAYER-TWO',
    'Different player preserves independent state.'
);


/*
 * ============================================================
 * I. DIFFERENT GAMEWEEK IS INDEPENDENT
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'I. Different Gameweek Is Independent'
);


$gameweekTwoCandidate =
    buildSnapshotCandidate(
        $gameweekIdTwo,
        $generatedGameweekTwo,
        $deadlineTwo,
        $playerOne,
        'GAMEWEEK-TWO',
        7.7,
        120000
    );


$gameweekTwoResult =
    $repository
        ->saveLatest(
            $gameweekIdTwo,
            $gameweekTwoCandidate
        );


snapshotCandidateRepositoryAssert(
    $gameweekTwoResult === true,
    'Same player can store an independent candidate for another gameweek.'
);


$storedGameweekTwo =
    $repository
        ->getByPlayerAndGameweek(
            $playerIdOne,
            $gameweekIdTwo
        );


snapshotCandidateRepositoryAssert(
    (
        $storedGameweekTwo[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekIdTwo,
    'Second gameweek preserves its own gameweek identity.'
);


snapshotCandidateRepositoryAssert(
    (
        $storedGameweekTwo[
            'player_state'
        ][
            'news'
        ]
        ?? null
    )
    ===
    'GAMEWEEK-TWO',
    'Second gameweek preserves independent player state.'
);


/*
 * ============================================================
 * J. BLANK-GAMEWEEK COMPATIBILITY
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'J. Blank-Gameweek Compatibility'
);


snapshotCandidateRepositoryAssert(
    !array_key_exists(
        'fixture_id',
        $storedLater[
            'player_state'
        ]
        ?? []
    ),
    'Candidate persistence does not require a fixture ID.'
);


snapshotCandidateRepositoryAssert(
    !array_key_exists(
        'fixture_history',
        $storedLater[
            'player_state'
        ]
        ?? []
    ),
    'Candidate persistence does not require fixture-history evidence.'
);


/*
 * ============================================================
 * K. INVALID GAMEWEEK ARGUMENT
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'K. Invalid Gameweek Argument'
);


$invalidGameweekRejected =
    false;


try {

    $repository
        ->saveLatest(
            0,
            $laterCandidate
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekRejected =
        true;
}


snapshotCandidateRepositoryAssert(
    $invalidGameweekRejected,
    'Non-positive repository gameweek ID is rejected.'
);


/*
 * ============================================================
 * L. MISMATCHED GAMEWEEK ARGUMENT
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'L. Mismatched Gameweek Argument'
);


$mismatchedGameweekRejected =
    false;


try {

    $repository
        ->saveLatest(
            $gameweekIdTwo,
            $laterCandidate
        );

} catch (
    InvalidArgumentException $exception
) {

    $mismatchedGameweekRejected =
        true;
}


snapshotCandidateRepositoryAssert(
    $mismatchedGameweekRejected,
    'Candidate gameweek must match repository gameweek argument.'
);


/*
 * ============================================================
 * M. READY FOR PROMOTION
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'M. Ready For Promotion'
);


$beforeDeadline =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
        -
        1
    );


$atDeadline =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
    );


$notReady =
    $repository
        ->getReadyForPromotion(
            $beforeDeadline
        );


$notReadyContainsTestCandidate =
    false;


foreach (
    $notReady
    as $candidateRow
) {

    if (
        (
            $candidateRow[
                'gameweek_id'
            ]
            ?? null
        )
        ===
        $gameweekIdOne
        &&
        in_array(
            $candidateRow[
                'player_id'
            ]
            ?? null,
            [
                $playerIdOne,
                $playerIdTwo
            ],
            true
        )
    ) {

        $notReadyContainsTestCandidate =
            true;

        break;
    }
}


snapshotCandidateRepositoryAssert(
    !$notReadyContainsTestCandidate,
    'Candidates are not ready before their preserved deadline.'
);


$ready =
    $repository
        ->getReadyForPromotion(
            $atDeadline
        );


$readyPlayerIds =
    [];


foreach (
    $ready
    as $candidateRow
) {

    if (
        (
            $candidateRow[
                'gameweek_id'
            ]
            ?? null
        )
        ===
        $gameweekIdOne
    ) {

        $readyPlayerIds[] =
            $candidateRow[
                'player_id'
            ]
            ?? null;
    }
}


snapshotCandidateRepositoryAssert(
    in_array(
        $playerIdOne,
        $readyPlayerIds,
        true
    ),
    'First player candidate becomes ready exactly at deadline.'
);


snapshotCandidateRepositoryAssert(
    in_array(
        $playerIdTwo,
        $readyPlayerIds,
        true
    ),
    'Second player candidate becomes ready exactly at deadline.'
);


/*
 * ============================================================
 * N. CLEANUP
 * ============================================================
 */

snapshotCandidateRepositorySection(
    'N. Cleanup'
);


$cleanupStatement
    ->execute(
        $cleanupParameters
    );


$remainingStatement =
    $db->prepare(
        "
        SELECT COUNT(*)
        FROM player_gameweek_snapshot_candidates
        WHERE player_id IN (
            :player_one,
            :player_two
        )
        AND gameweek_id IN (
            :gameweek_one,
            :gameweek_two
        )
        "
    );


$remainingStatement
    ->execute(
        $cleanupParameters
    );


$remaining =
    (int) $remainingStatement
        ->fetchColumn();


snapshotCandidateRepositoryAssert(
    $remaining === 0,
    'Repository test candidate rows are removed.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Repository Test Summary<br>";
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