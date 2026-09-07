<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Promotion Runner Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerSnapshotPromotionIntegrationAssert(
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


function playerSnapshotPromotionIntegrationSection(
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
    $database->getConnection();


/*
 * ============================================================
 * FIND CONTROLLED REAL FOREIGN-KEY IDENTITIES
 * ============================================================
 *
 * We need:
 *
 * - one real gameweek ID
 * - one real player ID
 * - that player's real FPL player ID
 * - that player's real team ID
 *
 * The test does NOT use the player's live state as historical
 * evidence. These identities exist only to satisfy real foreign
 * keys.
 */

$identityStatement =
    $db->query(
        "
            SELECT
                g.id AS gameweek_id,
                g.fpl_gameweek_id,
                p.id AS player_id,
                p.fpl_player_id,
                p.team_id
            FROM
                gameweeks g
            CROSS JOIN
                players p
            WHERE
                p.id > 0
            AND
                p.fpl_player_id > 0
            AND
                p.team_id > 0
            ORDER BY
                g.id DESC,
                p.id ASC
            LIMIT 1
        "
    );


$identity =
    $identityStatement->fetch(
        PDO::FETCH_ASSOC
    );


if (
    $identity === false
) {

    die(
        'No valid gameweek/player identity exists for '
        . 'player snapshot promotion integration test.'
    );
}


$gameweekId =
    (int) $identity[
        'gameweek_id'
    ];


$fplGameweekId =
    (int) $identity[
        'fpl_gameweek_id'
    ];


$playerId =
    (int) $identity[
        'player_id'
    ];


$fplPlayerId =
    (int) $identity[
        'fpl_player_id'
    ];


$teamId =
    (int) $identity[
        'team_id'
    ];


/*
 * ============================================================
 * CONTROLLED TIMESTAMPS
 * ============================================================
 *
 * These belong only to the candidate lifecycle.
 *
 * We deliberately do not depend on the real gameweek deadline
 * because this test is proving candidate deadline semantics.
 */

$generatedAt =
    '2035-01-01 12:00:00';


$deadlineTime =
    '2035-01-01 13:00:00';


$beforeDeadline =
    '2035-01-01 12:59:59';


$atDeadline =
    '2035-01-01 13:00:00';


$laterGeneratedAt =
    '2035-01-01 12:30:00';


/*
 * ============================================================
 * CONTROLLED PRE-DEADLINE PLAYER STATE
 * ============================================================
 *
 * Distinctive values make it easy to prove that promotion
 * preserves candidate evidence rather than reconstructing from
 * the current players table.
 */

$playerState = [

    'player_id' =>
        $playerId,

    'fpl_player_id' =>
        $fplPlayerId,

    'team_id' =>
        $teamId,

    'position' =>
        'MID',

    'price' =>
        7.5,

    /*
     * Raw absolute selected count is unavailable from the
     * current live players table.
     *
     * The new lifecycle therefore preserves this honestly
     * as null rather than reconstructing it later.
     */
    'selected' =>
        null,

    'selected_by_percent' =>
        12.34,

    'chance_of_playing' =>
        75,

    'status' =>
        'd',

    'news' =>
        'Controlled pre-deadline integration evidence',

    'minutes' =>
        321,

    'goals' =>
        4,

    'assists' =>
        5,

    'clean_sheets' =>
        6,

    'bonus' =>
        7,

    'bps' =>
        123,

    'ict_index' =>
        45.67,

    'expected_goals' =>
        2.34,

    'expected_assists' =>
        1.23,

    'expected_goal_involvements' =>
        3.57
];


/*
 * ============================================================
 * LATER MUTABLE CANDIDATE STATE
 * ============================================================
 *
 * This deliberately differs from the original evidence.
 *
 * Once the immutable snapshot exists, this later candidate
 * must never rewrite it.
 */

$laterPlayerState =
    $playerState;


$laterPlayerState[
    'price'
] =
    8.1;


$laterPlayerState[
    'selected_by_percent'
] =
    25.50;


$laterPlayerState[
    'chance_of_playing'
] =
    100;


$laterPlayerState[
    'news'
] =
    'Later mutable candidate evidence';


$laterPlayerState[
    'minutes'
] =
    999;


/*
 * ============================================================
 * START TRANSACTION
 * ============================================================
 *
 * Every database write performed by this test is rolled back.
 */

$db->beginTransaction();


try {

    /*
     * ========================================================
     * ENSURE CONTROLLED PLAYER/GAMEWEEK PAIR IS AVAILABLE
     * ========================================================
     *
     * The selected real player/gameweek pair may already have an
     * immutable historical snapshot.
     *
     * Delete it transactionally so the promotion path can be
     * tested from a known state.
     *
     * Rollback restores the original row exactly.
     */

    $deleteSnapshotStatement =
        $db->prepare(
            "
                DELETE FROM
                    player_gameweek_snapshots
                WHERE
                    player_id = :player_id
                AND
                    gameweek_id = :gameweek_id
            "
        );


    $deleteSnapshotStatement->execute([

        'player_id' =>
            $playerId,

        'gameweek_id' =>
            $gameweekId
    ]);


    $deleteCandidateStatement =
        $db->prepare(
            "
                DELETE FROM
                    player_gameweek_snapshot_candidates
                WHERE
                    player_id = :player_id
                AND
                    gameweek_id = :gameweek_id
            "
        );


    $deleteCandidateStatement->execute([

        'player_id' =>
            $playerId,

        'gameweek_id' =>
            $gameweekId
    ]);


    /*
     * ========================================================
     * REAL PRODUCTION OBJECTS
     * ========================================================
     */

    $candidateRepository =
        new PlayerGameweekSnapshotCandidateRepository(
            $db
        );


    $snapshotRepository =
        new PlayerGameweekSnapshotRepository(
            $db
        );


    $promotionService =
        new PlayerGameweekSnapshotCandidatePromotionService(
            $candidateRepository,
            $snapshotRepository
        );


    $runner =
        new PlayerGameweekSnapshotCandidatePromotionRunner(
            $candidateRepository,
            $promotionService
        );


    /*
     * ========================================================
     * CREATE CONTROLLED CANDIDATE
     * ========================================================
     */

    $candidate =
        new PlayerGameweekSnapshotCandidate(
            $gameweekId,
            $generatedAt,
            $deadlineTime,
            $playerState
        );


    /*
     * ========================================================
     * A. CONTROLLED IDENTITY
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'A. Controlled Database Identity'
    );


    playerSnapshotPromotionIntegrationAssert(
        $gameweekId > 0,
        'Real local gameweek identity is available.'
    );


    playerSnapshotPromotionIntegrationAssert(
        $fplGameweekId > 0,
        'Real FPL gameweek identity is available.'
    );


    playerSnapshotPromotionIntegrationAssert(
        $playerId > 0,
        'Real local player identity is available.'
    );


    playerSnapshotPromotionIntegrationAssert(
        $fplPlayerId > 0,
        'Real FPL player identity is available.'
    );


    playerSnapshotPromotionIntegrationAssert(
        $teamId > 0,
        'Real team identity is available.'
    );


    /*
     * ========================================================
     * B. STORE MUTABLE CANDIDATE
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'B. Store Mutable Candidate'
    );


    $candidateStored =
        $candidateRepository
            ->saveLatest(
                $gameweekId,
                $candidate
            );


    playerSnapshotPromotionIntegrationAssert(
        $candidateStored,
        'Controlled player snapshot candidate is stored.'
    );


    $storedCandidate =
        $candidateRepository
            ->getByPlayerAndGameweek(
                $playerId,
                $gameweekId
            );


    playerSnapshotPromotionIntegrationAssert(
        is_array(
            $storedCandidate
        ),
        'Stored candidate can be retrieved through real repository.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $storedCandidate[
                'generated_at'
            ]
            ?? null
        )
        ===
        $generatedAt,
        'Candidate preserves controlled generation timestamp.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $storedCandidate[
                'deadline_time'
            ]
            ?? null
        )
        ===
        $deadlineTime,
        'Candidate preserves controlled deadline.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $storedCandidate[
                'player_state'
            ][
                'news'
            ]
            ?? null
        )
        ===
        $playerState[
            'news'
        ],
        'Candidate preserves controlled pre-deadline player evidence.'
    );


    /*
     * ========================================================
     * C. BEFORE DEADLINE
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'C. Before Preserved Deadline'
    );


    $beforeResult =
        $runner->run(
            $beforeDeadline
        );


    playerSnapshotPromotionIntegrationAssert(
        (
            $beforeResult[
                'ready'
            ]
            ?? null
        )
        ===
        0,
        'Candidate is not ready before its preserved deadline.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $beforeResult[
                'promoted'
            ]
            ?? null
        )
        ===
        0,
        'Candidate is not promoted before its preserved deadline.'
    );


    $beforeSnapshot =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                $playerId,
                $gameweekId
            );


    playerSnapshotPromotionIntegrationAssert(
        $beforeSnapshot === null,
        'No immutable player snapshot exists before deadline.'
    );


    /*
     * ========================================================
     * D. EXACTLY AT DEADLINE
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'D. Exactly At Preserved Deadline'
    );


    $deadlineResult =
        $runner->run(
            $atDeadline
        );


    playerSnapshotPromotionIntegrationAssert(
        (
            $deadlineResult[
                'ready'
            ]
            ?? null
        )
        ===
        1,
        'Candidate becomes ready exactly at preserved deadline.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $deadlineResult[
                'promoted'
            ]
            ?? null
        )
        ===
        1,
        'Candidate is promoted exactly at preserved deadline.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $deadlineResult[
                'unchanged'
            ]
            ?? null
        )
        ===
        0,
        'First promotion is not reported as unchanged.'
    );


    $snapshot =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                $playerId,
                $gameweekId
            );


    playerSnapshotPromotionIntegrationAssert(
        is_array(
            $snapshot
        ),
        'Immutable player snapshot exists after promotion.'
    );


    /*
     * ========================================================
     * E. IMMUTABLE SNAPSHOT IDENTITY
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'E. Immutable Snapshot Identity'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'gameweek_id'
            ]
            ?? 0
        )
        ===
        $gameweekId,
        'Immutable snapshot preserves local gameweek ID.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'player_id'
            ]
            ?? 0
        )
        ===
        $playerId,
        'Immutable snapshot preserves local player ID.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'fpl_player_id'
            ]
            ?? 0
        )
        ===
        $fplPlayerId,
        'Immutable snapshot preserves FPL player ID.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'team_id'
            ]
            ?? 0
        )
        ===
        $teamId,
        'Immutable snapshot preserves team ID.'
    );


    /*
     * ========================================================
     * F. EXACT PRE-DEADLINE EVIDENCE
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'F. Pre-Deadline Evidence Survives Promotion'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $snapshot[
                'position'
            ]
            ?? null
        )
        ===
        'MID',
        'Position survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (float) (
            $snapshot[
                'price'
            ]
            ?? 0
        )
        ===
        7.5,
        'Price survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        array_key_exists(
            'selected',
            $snapshot
        )
        &&
        $snapshot[
            'selected'
        ]
        ===
        null,
        'Unavailable raw selected count remains null.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (float) (
            $snapshot[
                'selected_by_percent'
            ]
            ?? 0
        )
        ===
        12.34,
        'Ownership percentage survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'chance_of_playing'
            ]
            ?? 0
        )
        ===
        75,
        'Availability evidence survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $snapshot[
                'status'
            ]
            ?? null
        )
        ===
        'd',
        'Player status survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $snapshot[
                'news'
            ]
            ?? null
        )
        ===
        'Controlled pre-deadline integration evidence',
        'Player news survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'minutes'
            ]
            ?? 0
        )
        ===
        321,
        'Cumulative minutes survive promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'goals'
            ]
            ?? 0
        )
        ===
        4,
        'Cumulative goals survive promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'assists'
            ]
            ?? 0
        )
        ===
        5,
        'Cumulative assists survive promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'clean_sheets'
            ]
            ?? 0
        )
        ===
        6,
        'Cumulative clean sheets survive promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'bonus'
            ]
            ?? 0
        )
        ===
        7,
        'Cumulative bonus survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $snapshot[
                'bps'
            ]
            ?? 0
        )
        ===
        123,
        'Cumulative BPS survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (float) (
            $snapshot[
                'ict_index'
            ]
            ?? 0
        )
        ===
        45.67,
        'ICT index survives promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (float) (
            $snapshot[
                'expected_goals'
            ]
            ?? 0
        )
        ===
        2.34,
        'Expected goals survive promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (float) (
            $snapshot[
                'expected_assists'
            ]
            ?? 0
        )
        ===
        1.23,
        'Expected assists survive promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (float) (
            $snapshot[
                'expected_goal_involvements'
            ]
            ?? 0
        )
        ===
        3.57,
        'Expected goal involvements survive promotion.'
    );


    /*
     * ========================================================
     * G. NO RETROSPECTIVE FIXTURE DEPENDENCY
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'G. No Fixture-History Dependency'
    );


    playerSnapshotPromotionIntegrationAssert(
        !array_key_exists(
            'fixture_id',
            $snapshot
        ),
        'Immutable snapshot requires no fixture identity.'
    );


    playerSnapshotPromotionIntegrationAssert(
        !array_key_exists(
            'fixture_history',
            $snapshot
        ),
        'Immutable snapshot requires no fixture-history evidence.'
    );


    /*
     * ========================================================
     * H. REPEATED PROMOTION IS IDEMPOTENT
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'H. Repeated Promotion Is Idempotent'
    );


    $repeatResult =
        $runner->run(
            $atDeadline
        );


    playerSnapshotPromotionIntegrationAssert(
        (
            $repeatResult[
                'ready'
            ]
            ?? null
        )
        ===
        1,
        'Candidate remains safely discoverable after promotion.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $repeatResult[
                'promoted'
            ]
            ?? null
        )
        ===
        0,
        'Repeated run creates no second immutable snapshot.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $repeatResult[
                'unchanged'
            ]
            ?? null
        )
        ===
        1,
        'Repeated run reports existing snapshot as unchanged.'
    );


    $snapshotAfterRepeat =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                $playerId,
                $gameweekId
            );


    playerSnapshotPromotionIntegrationAssert(
        (
            $snapshotAfterRepeat[
                'news'
            ]
            ?? null
        )
        ===
        'Controlled pre-deadline integration evidence',
        'Repeated promotion leaves immutable evidence unchanged.'
    );


    /*
     * ========================================================
     * I. LATER MUTABLE CANDIDATE
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'I. Later Mutable Candidate'
    );


    $laterCandidate =
        new PlayerGameweekSnapshotCandidate(
            $gameweekId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterPlayerState
        );


    $laterStored =
        $candidateRepository
            ->saveLatest(
                $gameweekId,
                $laterCandidate
            );


    playerSnapshotPromotionIntegrationAssert(
        $laterStored,
        'Strictly newer pre-deadline candidate replaces staging evidence.'
    );


    $storedLaterCandidate =
        $candidateRepository
            ->getByPlayerAndGameweek(
                $playerId,
                $gameweekId
            );


    playerSnapshotPromotionIntegrationAssert(
        (
            $storedLaterCandidate[
                'player_state'
            ][
                'news'
            ]
            ?? null
        )
        ===
        'Later mutable candidate evidence',
        'Candidate staging row contains newer mutable evidence.'
    );


    /*
     * ========================================================
     * J. IMMUTABLE HISTORY CANNOT BE REWRITTEN
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'J. Immutable History Cannot Be Rewritten'
    );


    $afterLaterCandidateResult =
        $runner->run(
            $atDeadline
        );


    playerSnapshotPromotionIntegrationAssert(
        (
            $afterLaterCandidateResult[
                'promoted'
            ]
            ?? null
        )
        ===
        0,
        'Later candidate does not create another immutable snapshot.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (
            $afterLaterCandidateResult[
                'unchanged'
            ]
            ?? null
        )
        ===
        1,
        'Later candidate is blocked by existing immutable history.'
    );


    $finalSnapshot =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                $playerId,
                $gameweekId
            );


    playerSnapshotPromotionIntegrationAssert(
        (
            $finalSnapshot[
                'news'
            ]
            ?? null
        )
        ===
        'Controlled pre-deadline integration evidence',
        'Original immutable player news cannot be rewritten.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (float) (
            $finalSnapshot[
                'price'
            ]
            ?? 0
        )
        ===
        7.5,
        'Original immutable price cannot be rewritten.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (float) (
            $finalSnapshot[
                'selected_by_percent'
            ]
            ?? 0
        )
        ===
        12.34,
        'Original immutable ownership evidence cannot be rewritten.'
    );


    playerSnapshotPromotionIntegrationAssert(
        (int) (
            $finalSnapshot[
                'minutes'
            ]
            ?? 0
        )
        ===
        321,
        'Original immutable cumulative evidence cannot be rewritten.'
    );


    /*
     * ========================================================
     * K. TRANSACTIONAL ROLLBACK
     * ========================================================
     */

    playerSnapshotPromotionIntegrationSection(
        'K. Transactional Safety'
    );


    playerSnapshotPromotionIntegrationAssert(
        $db->inTransaction(),
        'Integration test database changes remain inside transaction.'
    );


} finally {

    if (
        $db->inTransaction()
    ) {

        $db->rollBack();
    }
}


/*
 * ============================================================
 * L. VERIFY ROLLBACK COMPLETED
 * ============================================================
 */

playerSnapshotPromotionIntegrationSection(
    'L. Rollback Complete'
);


playerSnapshotPromotionIntegrationAssert(
    !$db->inTransaction(),
    'Integration test transaction has been rolled back.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Promotion Runner Integration Test Summary<br>";
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