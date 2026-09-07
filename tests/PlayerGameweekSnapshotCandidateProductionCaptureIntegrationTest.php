<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Production Capture Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

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
 * DATABASE
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


$gameweekRepository =
    new GameweekRepository(
        $connection
    );


$playerRepository =
    new PlayerRepository(
        $connection
    );


$candidateBuilder =
    new PlayerGameweekSnapshotCandidateCaptureService();


$candidateRepository =
    new PlayerGameweekSnapshotCandidateRepository(
        $connection
    );


$productionCapture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $gameweekRepository,
        $playerRepository,
        $candidateBuilder,
        $candidateRepository
    );


/*
 * ============================================================
 * RESOLVE CONTROLLED REAL GAMEWEEK
 * ============================================================
 *
 * Use the earliest stored deadline in the future relative to a
 * deliberately early timestamp.
 *
 * This keeps the test independent from the actual date on which
 * it is run while still exercising the real GameweekRepository.
 */

$allGameweeks =
    $gameweekRepository
        ->getAll();


$targetGameweek =
    null;


foreach (
    $allGameweeks
    as $gameweek
) {

    $deadline =
        $gameweek[
            'deadline_time'
        ]
        ?? null;


    if (
        !is_string(
            $deadline
        )
        ||
        trim(
            $deadline
        ) === ''
    ) {

        continue;
    }


    try {

        $deadlineDate =
            new DateTimeImmutable(
                $deadline
            );

    } catch (
        Exception $exception
    ) {

        continue;
    }


    /*
     * Prefer the latest stored gameweek so this test is less
     * likely to collide with existing candidate staging rows.
     */
    $targetGameweek =
        $gameweek;
}


productionCaptureIntegrationSection(
    'A. Real Database Foundation'
);


productionCaptureIntegrationAssert(
    is_array(
        $targetGameweek
    ),
    'A real stored gameweek with a valid deadline is available.'
);


if (
    !is_array(
        $targetGameweek
    )
) {

    productionCaptureIntegrationSection(
        'Player Snapshot Candidate Production Capture Integration Test Summary'
    );


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$gameweekId =
    (int) (
        $targetGameweek[
            'id'
        ]
        ?? 0
    );


$fplGameweekId =
    (int) (
        $targetGameweek[
            'fpl_gameweek_id'
        ]
        ?? 0
    );


$deadlineTime =
    (string) (
        $targetGameweek[
            'deadline_time'
        ]
        ?? ''
    );


$deadlineDate =
    new DateTimeImmutable(
        $deadlineTime
    );


/*
 * Two controlled pre-deadline capture times.
 *
 * Both remain safely before the real stored deadline.
 */

$firstCaptureDate =
    $deadlineDate
        ->modify(
            '-2 hours'
        );


$secondCaptureDate =
    $deadlineDate
        ->modify(
            '-1 hour'
        );


$firstCaptureTime =
    $firstCaptureDate
        ->format(
            'Y-m-d H:i:s'
        );


$secondCaptureTime =
    $secondCaptureDate
        ->format(
            'Y-m-d H:i:s'
        );


$players =
    $playerRepository
        ->getAll();


productionCaptureIntegrationAssert(
    $gameweekId > 0,
    'Real local gameweek identity is valid.'
);


productionCaptureIntegrationAssert(
    $fplGameweekId > 0,
    'Real FPL gameweek identity is valid.'
);


productionCaptureIntegrationAssert(
    $deadlineTime !== '',
    'Real gameweek deadline is available.'
);


productionCaptureIntegrationAssert(
    $firstCaptureDate < $deadlineDate,
    'First controlled capture occurs before deadline.'
);


productionCaptureIntegrationAssert(
    $secondCaptureDate < $deadlineDate,
    'Second controlled capture occurs before deadline.'
);


productionCaptureIntegrationAssert(
    $secondCaptureDate > $firstCaptureDate,
    'Second controlled capture is strictly newer than first.'
);


productionCaptureIntegrationAssert(
    count(
        $players
    ) > 0,
    'Real live player repository contains players.'
);


/*
 * ============================================================
 * EXPECTED VALID PLAYER COUNT
 * ============================================================
 *
 * This mirrors only the identity eligibility contract:
 *
 * - local player ID > 0
 * - FPL player ID > 0
 * - local team ID > 0
 *
 * It does not reproduce player-state mapping logic.
 */

$expectedValidPlayers =
    array_values(
        array_filter(
            $players,
            static function (
                array $player
            ): bool {

                return
                    (int) (
                        $player[
                            'id'
                        ]
                        ?? 0
                    ) > 0
                    &&
                    (int) (
                        $player[
                            'fpl_player_id'
                        ]
                        ?? 0
                    ) > 0
                    &&
                    (int) (
                        $player[
                            'team_id'
                        ]
                        ?? 0
                    ) > 0;
            }
        )
    );


$expectedCandidateCount =
    count(
        $expectedValidPlayers
    );


productionCaptureIntegrationAssert(
    $expectedCandidateCount > 0,
    'Real player pool contains valid candidate identities.'
);


/*
 * ============================================================
 * IMMUTABLE SNAPSHOT BASELINE
 * ============================================================
 *
 * Candidate capture must never write player_gameweek_snapshots.
 */

$immutableCountStatement =
    $connection
        ->prepare(
            "
                SELECT COUNT(*)
                FROM player_gameweek_snapshots
                WHERE gameweek_id = :gameweek_id
            "
        );


$immutableCountStatement
    ->execute(
        [
            ':gameweek_id' =>
                $gameweekId
        ]
    );


$immutableCountBefore =
    (int) $immutableCountStatement
        ->fetchColumn();


/*
 * ============================================================
 * TRANSACTIONAL SAFETY
 * ============================================================
 *
 * Remove any candidate staging rows for the controlled target
 * gameweek inside this transaction.
 *
 * Rollback restores the database exactly afterwards.
 */

$connection
    ->beginTransaction();


try {

    $deleteCandidates =
        $connection
            ->prepare(
                "
                    DELETE FROM
                        player_gameweek_snapshot_candidates
                    WHERE
                        gameweek_id = :gameweek_id
                "
            );


    $deleteCandidates
        ->execute(
            [
                ':gameweek_id' =>
                    $gameweekId
            ]
        );


    /*
     * ========================================================
     * B. FIRST REAL PRODUCTION CAPTURE
     * ========================================================
     */

    productionCaptureIntegrationSection(
        'B. First Real Production Capture'
    );


    $firstResult =
        $productionCapture
            ->capture(
                $firstCaptureTime
            );


    productionCaptureIntegrationAssert(
        (
            $firstResult[
                'status'
            ]
            ?? null
        ) === 'Captured',
        'Real production capture completes successfully.'
    );


    productionCaptureIntegrationAssert(
        (
            $firstResult[
                'gameweek_id'
            ]
            ?? null
        ) === $gameweekId,
        'Production capture resolves expected local gameweek.'
    );


    productionCaptureIntegrationAssert(
        (
            $firstResult[
                'fpl_gameweek_id'
            ]
            ?? null
        ) === $fplGameweekId,
        'Production capture resolves expected FPL gameweek.'
    );


    productionCaptureIntegrationAssert(
        (
            $firstResult[
                'players_considered'
            ]
            ?? null
        ) === count(
            $players
        ),
        'Production capture considers complete real player pool.'
    );


    productionCaptureIntegrationAssert(
        (
            $firstResult[
                'candidates_built'
            ]
            ?? null
        ) === $expectedCandidateCount,
        'Production capture builds one candidate per valid player identity.'
    );


    productionCaptureIntegrationAssert(
        (
            $firstResult[
                'saved'
            ]
            ?? null
        ) === $expectedCandidateCount,
        'First capture stores every newly built candidate.'
    );


    productionCaptureIntegrationAssert(
        (
            $firstResult[
                'unchanged'
            ]
            ?? null
        ) === 0,
        'First capture has no unchanged candidates.'
    );


    /*
     * ========================================================
     * C. REAL CANDIDATE STORAGE
     * ========================================================
     */

    productionCaptureIntegrationSection(
        'C. Real Candidate Storage'
    );


    $candidateCountStatement =
        $connection
            ->prepare(
                "
                    SELECT COUNT(*)
                    FROM player_gameweek_snapshot_candidates
                    WHERE gameweek_id = :gameweek_id
                "
            );


    $candidateCountStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $gameweekId
            ]
        );


    $candidateCount =
        (int) $candidateCountStatement
            ->fetchColumn();


    productionCaptureIntegrationAssert(
        $candidateCount === $expectedCandidateCount,
        'Real staging table contains exactly one candidate per valid player.'
    );


    $samplePlayer =
        $expectedValidPlayers[
            0
        ];


    $samplePlayerId =
        (int) $samplePlayer[
            'id'
        ];


    $storedCandidate =
        $candidateRepository
            ->getByPlayerAndGameweek(
                $samplePlayerId,
                $gameweekId
            );


    productionCaptureIntegrationAssert(
        is_array(
            $storedCandidate
        ),
        'A real captured candidate can be retrieved.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedCandidate[
                'generated_at'
            ]
            ?? null
        ) === $firstCaptureTime,
        'Stored candidate preserves controlled capture timestamp.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedCandidate[
                'deadline_time'
            ]
            ?? null
        ) === $deadlineDate
            ->format(
                'Y-m-d H:i:s'
            ),
        'Stored candidate preserves real gameweek deadline.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedCandidate[
                'player_id'
            ]
            ?? null
        ) === $samplePlayerId,
        'Stored candidate preserves real local player identity.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedCandidate[
                'fpl_player_id'
            ]
            ?? null
        )
        ===
        (int) $samplePlayer[
            'fpl_player_id'
        ],
        'Stored candidate preserves real FPL player identity.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedCandidate[
                'team_id'
            ]
            ?? null
        )
        ===
        (int) $samplePlayer[
            'team_id'
        ],
        'Stored candidate preserves real team identity.'
    );


    /*
     * ========================================================
     * D. REAL LIVE STATE MAPPING
     * ========================================================
     */

    productionCaptureIntegrationSection(
        'D. Real Live State Mapping'
    );


    $storedState =
        is_array(
            $storedCandidate[
                'player_state'
            ]
            ?? null
        )
            ? $storedCandidate[
                'player_state'
            ]
            : [];


    productionCaptureIntegrationAssert(
        (
            $storedState[
                'position'
            ]
            ?? null
        )
        ===
        (
            $samplePlayer[
                'position'
            ]
            ?? null
        ),
        'Candidate preserves real player position.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedState[
                'price'
            ]
            ?? null
        )
        ==
        (
            $samplePlayer[
                'price'
            ]
            ?? null
        ),
        'Candidate preserves real current price.'
    );


    productionCaptureIntegrationAssert(
        array_key_exists(
            'selected',
            $storedState
        )
        &&
        $storedState[
            'selected'
        ] === null,
        'Unavailable raw selected count remains explicitly null.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedState[
                'selected_by_percent'
            ]
            ?? null
        )
        ==
        (
            $samplePlayer[
                'selected_by_percent'
            ]
            ?? null
        ),
        'Candidate preserves real ownership percentage.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedState[
                'minutes'
            ]
            ?? null
        )
        ===
        (int) (
            $samplePlayer[
                'minutes'
            ]
            ?? 0
        ),
        'Candidate preserves real cumulative minutes.'
    );


    productionCaptureIntegrationAssert(
        (
            $storedState[
                'expected_goal_involvements'
            ]
            ?? null
        )
        ==
        (
            $samplePlayer[
                'expected_goal_involvements'
            ]
            ?? null
        ),
        'Candidate preserves real cumulative expected goal involvements.'
    );


    /*
     * ========================================================
     * E. NO FIXTURE-HISTORY DEPENDENCY
     * ========================================================
     */

    productionCaptureIntegrationSection(
        'E. No Fixture-History Dependency'
    );


    productionCaptureIntegrationAssert(
        !array_key_exists(
            'fixture_id',
            $storedState
        ),
        'Captured candidate requires no fixture identity.'
    );


    productionCaptureIntegrationAssert(
        !array_key_exists(
            'fixture_history',
            $storedState
        ),
        'Captured candidate requires no fixture-history evidence.'
    );


    /*
     * ========================================================
     * F. SAME-TIMESTAMP REPEAT IS HARMLESS
     * ========================================================
     *
     * saveLatest() only replaces with a strictly newer
     * generated_at value.
     */

    productionCaptureIntegrationSection(
        'F. Same-Timestamp Repeat Is Harmless'
    );


    $repeatResult =
        $productionCapture
            ->capture(
                $firstCaptureTime
            );


    productionCaptureIntegrationAssert(
        (
            $repeatResult[
                'status'
            ]
            ?? null
        ) === 'Captured',
        'Repeated production capture still completes.'
    );


    productionCaptureIntegrationAssert(
        (
            $repeatResult[
                'saved'
            ]
            ?? null
        ) === 0,
        'Same-timestamp repeat saves no replacement candidates.'
    );


    productionCaptureIntegrationAssert(
        (
            $repeatResult[
                'unchanged'
            ]
            ?? null
        ) === $expectedCandidateCount,
        'Same-timestamp repeat reports all candidates unchanged.'
    );


    $candidateCountStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $gameweekId
            ]
        );


    $candidateCountAfterRepeat =
        (int) $candidateCountStatement
            ->fetchColumn();


    productionCaptureIntegrationAssert(
        $candidateCountAfterRepeat === $expectedCandidateCount,
        'Repeated capture creates no duplicate candidate rows.'
    );


    /*
     * ========================================================
     * G. STRICTLY NEWER CAPTURE REPLACES STAGING EVIDENCE
     * ========================================================
     */

    productionCaptureIntegrationSection(
        'G. Strictly Newer Capture Replaces Staging Evidence'
    );


    $newerResult =
        $productionCapture
            ->capture(
                $secondCaptureTime
            );


    productionCaptureIntegrationAssert(
        (
            $newerResult[
                'status'
            ]
            ?? null
        ) === 'Captured',
        'Strictly newer production capture completes.'
    );


    productionCaptureIntegrationAssert(
        (
            $newerResult[
                'saved'
            ]
            ?? null
        ) === $expectedCandidateCount,
        'Strictly newer capture replaces all candidate staging rows.'
    );


    productionCaptureIntegrationAssert(
        (
            $newerResult[
                'unchanged'
            ]
            ?? null
        ) === 0,
        'Strictly newer capture has no unchanged valid candidates.'
    );


    $newerStoredCandidate =
        $candidateRepository
            ->getByPlayerAndGameweek(
                $samplePlayerId,
                $gameweekId
            );


    productionCaptureIntegrationAssert(
        (
            $newerStoredCandidate[
                'generated_at'
            ]
            ?? null
        ) === $secondCaptureTime,
        'Candidate staging evidence advances to newer capture timestamp.'
    );


    $candidateCountStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $gameweekId
            ]
        );


    $candidateCountAfterNewer =
        (int) $candidateCountStatement
            ->fetchColumn();


    productionCaptureIntegrationAssert(
        $candidateCountAfterNewer === $expectedCandidateCount,
        'Newer capture replaces rows without increasing candidate count.'
    );


    /*
     * ========================================================
     * H. IMMUTABLE SNAPSHOT ISOLATION
     * ========================================================
     */

    productionCaptureIntegrationSection(
        'H. Immutable Snapshot Isolation'
    );


    $immutableCountStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $gameweekId
            ]
        );


    $immutableCountDuringCapture =
        (int) $immutableCountStatement
            ->fetchColumn();


    productionCaptureIntegrationAssert(
        $immutableCountDuringCapture === $immutableCountBefore,
        'Candidate capture creates no immutable player snapshots.'
    );


    /*
     * ========================================================
     * I. TRANSACTIONAL SAFETY
     * ========================================================
     */

    productionCaptureIntegrationSection(
        'I. Transactional Safety'
    );


    productionCaptureIntegrationAssert(
        $connection
            ->inTransaction(),
        'Integration test database changes remain inside transaction.'
    );


} finally {

    if (
        $connection
            ->inTransaction()
    ) {

        $connection
            ->rollBack();
    }
}


/*
 * ============================================================
 * J. ROLLBACK COMPLETE
 * ============================================================
 */

productionCaptureIntegrationSection(
    'J. Rollback Complete'
);


$remainingCandidateStatement =
    $connection
        ->prepare(
            "
                SELECT COUNT(*)
                FROM player_gameweek_snapshot_candidates
                WHERE
                    gameweek_id = :gameweek_id
                    AND
                    generated_at IN (
                        :first_capture,
                        :second_capture
                    )
            "
        );


$remainingCandidateStatement
    ->execute(
        [
            ':gameweek_id' =>
                $gameweekId,

            ':first_capture' =>
                $firstCaptureTime,

            ':second_capture' =>
                $secondCaptureTime
        ]
    );


$remainingControlledCandidates =
    (int) $remainingCandidateStatement
        ->fetchColumn();


productionCaptureIntegrationAssert(
    $remainingControlledCandidates === 0,
    'Controlled integration-test candidate changes have been rolled back.'
);


$immutableCountStatement
    ->execute(
        [
            ':gameweek_id' =>
                $gameweekId
        ]
    );


$immutableCountAfter =
    (int) $immutableCountStatement
        ->fetchColumn();


productionCaptureIntegrationAssert(
    $immutableCountAfter === $immutableCountBefore,
    'Immutable player snapshot history is unchanged after rollback.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

productionCaptureIntegrationSection(
    'Player Snapshot Candidate Production Capture Integration Test Summary'
);


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