<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Lifecycle Test<br>";
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

function snapshotLifecycleCheck(
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
 * DATABASE / REPOSITORIES
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


$snapshotRepository =
    new PlayerGameweekSnapshotRepository(
        $connection
    );


/*
 * ============================================================
 * SCENARIO A
 * LIVE UPDATER ISOLATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Live Updater Isolation<br>";
echo "============================================<br>";


$updateFile =
    __DIR__
    . '/../cron/updateFPLData.php';


$updateSource =
    is_file(
        $updateFile
    )
        ? file_get_contents(
            $updateFile
        )
        : false;


snapshotLifecycleCheck(
    'Live FPL updater exists',
    is_string(
        $updateSource
    )
);


if (
    !is_string(
        $updateSource
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


snapshotLifecycleCheck(
    'Live updater does not construct PlayerGameweekSnapshotRepository',
    !str_contains(
        $updateSource,
        'new PlayerGameweekSnapshotRepository'
    )
);


snapshotLifecycleCheck(
    'Live updater does not write player gameweek snapshots',
    !str_contains(
        $updateSource,
        'playerGameweekSnapshotRepository'
    )
);


snapshotLifecycleCheck(
    'Live updater no longer tracks snapshot import counts',
    !str_contains(
        $updateSource,
        'playerSnapshotsImported'
    )
);


snapshotLifecycleCheck(
    'Live updater still updates current players',
    str_contains(
        $updateSource,
        'INSERT INTO players'
    )
    &&
    str_contains(
        $updateSource,
        'ON DUPLICATE KEY UPDATE'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * GAMEWEEK LIFECYCLE FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Gameweek Lifecycle Foundation<br>";
echo "============================================<br>";


$currentGameweek =
    $gameweekRepository
        ->getCurrent();


$previousGameweek =
    $gameweekRepository
        ->getPrevious();


snapshotLifecycleCheck(
    'Gameweek repository exposes current-gameweek state',
    method_exists(
        $gameweekRepository,
        'getCurrent'
    )
);


snapshotLifecycleCheck(
    'Gameweek repository exposes previous-gameweek state',
    method_exists(
        $gameweekRepository,
        'getPrevious'
    )
);


snapshotLifecycleCheck(
    'Stored gameweek records expose finished state',
    count(
        array_filter(
            $gameweekRepository
                ->getAll(),
            static function (
                array $gameweek
            ): bool {

                return array_key_exists(
                    'finished',
                    $gameweek
                );
            }
        )
    )
    ===
    count(
        $gameweekRepository
            ->getAll()
    )
);


if (
    is_array(
        $currentGameweek
    )
) {

    echo "Current Gameweek: GW"
        . (
            $currentGameweek[
                'fpl_gameweek_id'
            ]
            ?? '—'
        )
        . " — Finished "
        . (
            !empty(
                $currentGameweek[
                    'finished'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";
}


if (
    is_array(
        $previousGameweek
    )
) {

    echo "Previous Gameweek: GW"
        . (
            $previousGameweek[
                'fpl_gameweek_id'
            ]
            ?? '—'
        )
        . " — Finished "
        . (
            !empty(
                $previousGameweek[
                    'finished'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * EXISTING SNAPSHOT FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Existing Snapshot Foundation<br>";
echo "============================================<br>";


$snapshotSummaryStatement =
    $connection
        ->query(
            "
                SELECT
                    COUNT(*) AS row_count,
                    COUNT(DISTINCT gameweek_id) AS gameweek_count,
                    COUNT(DISTINCT player_id) AS player_count
                FROM player_gameweek_snapshots
            "
        );


$snapshotSummary =
    $snapshotSummaryStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$snapshotRows =
    (int) (
        $snapshotSummary[
            'row_count'
        ]
        ?? 0
    );


$snapshotGameweeks =
    (int) (
        $snapshotSummary[
            'gameweek_count'
        ]
        ?? 0
    );


$snapshotPlayers =
    (int) (
        $snapshotSummary[
            'player_count'
        ]
        ?? 0
    );


snapshotLifecycleCheck(
    'Historical snapshot storage exists',
    $snapshotRows > 0
);


snapshotLifecycleCheck(
    'Historical snapshots retain gameweek identity',
    $snapshotGameweeks > 0
);


snapshotLifecycleCheck(
    'Historical snapshots retain player identity',
    $snapshotPlayers > 0
);


echo "Snapshot Rows: "
    . number_format(
        $snapshotRows
    )
    . "<br>";


echo "Snapshot Gameweeks: "
    . number_format(
        $snapshotGameweeks
    )
    . "<br>";


echo "Snapshot Players: "
    . number_format(
        $snapshotPlayers
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * IMMUTABLE WRITE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Immutable Snapshot Write Contract<br>";
echo "============================================<br>";


$immutableMethodExists =
    method_exists(
        $snapshotRepository,
        'insertIfAbsent'
    );


snapshotLifecycleCheck(
    'Snapshot repository exposes immutable insertIfAbsent contract',
    $immutableMethodExists
);


if (
    !$immutableMethodExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "PlayerGameweekSnapshotRepository::insertIfAbsent()<br>";
    echo "<br>";

    echo "The new method should:<br>";
    echo "- insert a snapshot when player/gameweek does not exist<br>";
    echo "- leave an existing snapshot unchanged<br>";
    echo "- return true when inserted<br>";
    echo "- return false when the snapshot already exists<br><br>";


    echo "============================================<br>";
    echo "Player Gameweek Snapshot Lifecycle Test Summary<br>";
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
 * SCENARIO E
 * EXISTING SNAPSHOT IMMUTABILITY
 * ============================================================
 *
 * Use an existing real snapshot inside a transaction.
 *
 * The transaction is always rolled back so this test cannot
 * permanently modify historical data.
 */

echo "============================================<br>";
echo "Scenario E: Existing Snapshot Immutability<br>";
echo "============================================<br>";


$existingSnapshotStatement =
    $connection
        ->query(
            "
                SELECT *
                FROM player_gameweek_snapshots
                ORDER BY
                    gameweek_id ASC,
                    player_id ASC
                LIMIT 1
            "
        );


$existingSnapshot =
    $existingSnapshotStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


snapshotLifecycleCheck(
    'A real existing snapshot resolves for immutability testing',
    is_array(
        $existingSnapshot
    )
);


if (
    !is_array(
        $existingSnapshot
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$originalPrice =
    $existingSnapshot[
        'price'
    ];


$originalOwnership =
    $existingSnapshot[
        'selected_by_percent'
    ];


$replacementSnapshot =
    [

        'gameweek_id' =>
            (int) $existingSnapshot[
                'gameweek_id'
            ],

        'player_id' =>
            (int) $existingSnapshot[
                'player_id'
            ],

        'fpl_player_id' =>
            (int) $existingSnapshot[
                'fpl_player_id'
            ],

        'team_id' =>
            (int) $existingSnapshot[
                'team_id'
            ],

        'position' =>
            $existingSnapshot[
                'position'
            ],

        /*
         * Deliberately different values.
         *
         * These MUST NOT replace the stored snapshot.
         */
        'price' =>
            is_numeric(
                $originalPrice
            )
                ? (float) $originalPrice
                    + 1.0
                : 99.0,

        'selected_by_percent' =>
            is_numeric(
                $originalOwnership
            )
                ? min(
                    100.0,
                    (float) $originalOwnership
                    + 10.0
                )
                : 99.0,

        'chance_of_playing' =>
            $existingSnapshot[
                'chance_of_playing'
            ],

        'status' =>
            $existingSnapshot[
                'status'
            ],

        'news' =>
            $existingSnapshot[
                'news'
            ],

        'minutes' =>
            (int) $existingSnapshot[
                'minutes'
            ],

        'goals' =>
            (int) $existingSnapshot[
                'goals'
            ],

        'assists' =>
            (int) $existingSnapshot[
                'assists'
            ],

        'clean_sheets' =>
            (int) $existingSnapshot[
                'clean_sheets'
            ],

        'bonus' =>
            (int) $existingSnapshot[
                'bonus'
            ],

        'bps' =>
            (int) $existingSnapshot[
                'bps'
            ],

        'ict_index' =>
            $existingSnapshot[
                'ict_index'
            ],

        'expected_goals' =>
            $existingSnapshot[
                'expected_goals'
            ],

        'expected_assists' =>
            $existingSnapshot[
                'expected_assists'
            ],

        'expected_goal_involvements' =>
            $existingSnapshot[
                'expected_goal_involvements'
            ]
    ];


$connection
    ->beginTransaction();


try {

    $inserted =
        $snapshotRepository
            ->insertIfAbsent(
                $replacementSnapshot
            );


    $afterAttempt =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                (int) $existingSnapshot[
                    'player_id'
                ],
                (int) $existingSnapshot[
                    'gameweek_id'
                ]
            );


    snapshotLifecycleCheck(
        'Duplicate immutable snapshot insert reports not inserted',
        $inserted === false
    );


    snapshotLifecycleCheck(
        'Duplicate immutable insert preserves historical price',
        is_array(
            $afterAttempt
        )
        &&
        (
            $afterAttempt[
                'price'
            ]
            ?? null
        )
        ==
        $originalPrice
    );


    snapshotLifecycleCheck(
        'Duplicate immutable insert preserves historical ownership',
        is_array(
            $afterAttempt
        )
        &&
        (
            $afterAttempt[
                'selected_by_percent'
            ]
            ?? null
        )
        ==
        $originalOwnership
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


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * PRE-DEADLINE CANDIDATE CAPTURE LIFECYCLE
 * ============================================================
 *
 * Historical player state must now originate from evidence
 * captured before the target gameweek deadline.
 *
 * The operational capture entry point stages mutable
 * candidates only. It must not write immutable snapshots
 * directly.
 */

echo "============================================<br>";
echo "Scenario F: Pre-Deadline Candidate Capture Lifecycle<br>";
echo "============================================<br>";


$candidateCaptureFile =
    __DIR__
    . '/../cron/capturePlayerGameweekSnapshotCandidates.php';


$candidateCaptureSource =
    is_file(
        $candidateCaptureFile
    )
        ? file_get_contents(
            $candidateCaptureFile
        )
        : false;


snapshotLifecycleCheck(
    'Pre-deadline player snapshot candidate capture cron exists',
    is_string(
        $candidateCaptureSource
    )
);


snapshotLifecycleCheck(
    'Candidate capture uses production capture orchestration',
    is_string(
        $candidateCaptureSource
    )
    &&
    str_contains(
        $candidateCaptureSource,
        'new PlayerGameweekSnapshotCandidateProductionCapture'
    )
);


snapshotLifecycleCheck(
    'Candidate capture persists through mutable candidate repository',
    is_string(
        $candidateCaptureSource
    )
    &&
    str_contains(
        $candidateCaptureSource,
        'new PlayerGameweekSnapshotCandidateRepository'
    )
);


snapshotLifecycleCheck(
    'Candidate capture does not construct immutable snapshot repository',
    is_string(
        $candidateCaptureSource
    )
    &&
    !str_contains(
        $candidateCaptureSource,
        'new PlayerGameweekSnapshotRepository'
    )
);


snapshotLifecycleCheck(
    'Candidate capture has no fixture-history dependency',
    is_string(
        $candidateCaptureSource
    )
    &&
    !str_contains(
        $candidateCaptureSource,
        'player_fixture_history'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * DEADLINE PROMOTION LIFECYCLE
 * ============================================================
 *
 * Immutable player snapshots are created by promoting the
 * latest pre-deadline candidate at or after its preserved
 * deadline.
 */

echo "============================================<br>";
echo "Scenario G: Deadline Promotion Lifecycle<br>";
echo "============================================<br>";


$candidatePromotionFile =
    __DIR__
    . '/../cron/promotePlayerGameweekSnapshotCandidates.php';


$candidatePromotionSource =
    is_file(
        $candidatePromotionFile
    )
        ? file_get_contents(
            $candidatePromotionFile
        )
        : false;


snapshotLifecycleCheck(
    'Player snapshot candidate promotion cron exists',
    is_string(
        $candidatePromotionSource
    )
);


snapshotLifecycleCheck(
    'Promotion cron uses candidate promotion runner',
    is_string(
        $candidatePromotionSource
    )
    &&
    str_contains(
        $candidatePromotionSource,
        'new PlayerGameweekSnapshotCandidatePromotionRunner'
    )
);


snapshotLifecycleCheck(
    'Promotion cron uses candidate promotion service',
    is_string(
        $candidatePromotionSource
    )
    &&
    str_contains(
        $candidatePromotionSource,
        'new PlayerGameweekSnapshotCandidatePromotionService'
    )
);


snapshotLifecycleCheck(
    'Promotion cron reads mutable candidate repository',
    is_string(
        $candidatePromotionSource
    )
    &&
    str_contains(
        $candidatePromotionSource,
        'new PlayerGameweekSnapshotCandidateRepository'
    )
);


snapshotLifecycleCheck(
    'Promotion cron writes through immutable snapshot repository',
    is_string(
        $candidatePromotionSource
    )
    &&
    str_contains(
        $candidatePromotionSource,
        'new PlayerGameweekSnapshotRepository'
    )
);


snapshotLifecycleCheck(
    'Promotion lifecycle has no fixture-history dependency',
    is_string(
        $candidatePromotionSource
    )
    &&
    !str_contains(
        $candidatePromotionSource,
        'player_fixture_history'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * OPERATIONAL LIFECYCLE BOUNDARY
 * ============================================================
 *
 * Current-state import, candidate capture and immutable
 * promotion are deliberately separate operational concerns.
 *
 * updateFPLData.php refreshes current state.
 *
 * capturePlayerGameweekSnapshotCandidates.php preserves the
 * latest pre-deadline evidence.
 *
 * promotePlayerGameweekSnapshotCandidates.php freezes that
 * evidence after the deadline.
 */

echo "============================================<br>";
echo "Scenario H: Operational Lifecycle Boundary<br>";
echo "============================================<br>";


snapshotLifecycleCheck(
    'Live updater remains separate from candidate capture',
    !str_contains(
        $updateSource,
        'PlayerGameweekSnapshotCandidateProductionCapture'
    )
);


snapshotLifecycleCheck(
    'Live updater remains separate from candidate promotion',
    !str_contains(
        $updateSource,
        'PlayerGameweekSnapshotCandidatePromotionRunner'
    )
);


snapshotLifecycleCheck(
    'Candidate capture and promotion use separate operational entry points',
    is_string(
        $candidateCaptureSource
    )
    &&
    is_string(
        $candidatePromotionSource
    )
    &&
    realpath(
        $candidateCaptureFile
    )
    !==
    realpath(
        $candidatePromotionFile
    )
);


snapshotLifecycleCheck(
    'Candidate capture cannot directly perform deadline promotion',
    is_string(
        $candidateCaptureSource
    )
    &&
    !str_contains(
        $candidateCaptureSource,
        'PlayerGameweekSnapshotCandidatePromotionService'
    )
    &&
    !str_contains(
        $candidateCaptureSource,
        'PlayerGameweekSnapshotCandidatePromotionRunner'
    )
);


snapshotLifecycleCheck(
    'Candidate promotion does not reconstruct state from current players',
    is_string(
        $candidatePromotionSource
    )
    &&
    !str_contains(
        $candidatePromotionSource,
        'new PlayerRepository'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * RETROSPECTIVE CAPTURE RETIREMENT
 * ============================================================
 *
 * Historical player snapshots must no longer be reconstructed
 * after a gameweek has completed from the current live player
 * pool.
 *
 * The old completed-gameweek capture entry point may remain
 * temporarily while migration work is completed, but it must
 * be explicitly retired and must not execute retrospective
 * reconstruction.
 */

echo "============================================<br>";
echo "Scenario I: Retrospective Capture Retirement<br>";
echo "============================================<br>";


$legacyCaptureFile =
    __DIR__
    . '/../cron/capturePlayerGameweekSnapshots.php';


$legacyCaptureSource =
    is_file(
        $legacyCaptureFile
    )
        ? file_get_contents(
            $legacyCaptureFile
        )
        : false;


/*
 * During migration we deliberately allow the old file to
 * remain present.
 *
 * What matters is that it can no longer invoke retrospective
 * reconstruction.
 */

snapshotLifecycleCheck(
    'Legacy completed-gameweek capture entry point remains identifiable during migration',
    is_string(
        $legacyCaptureSource
    )
);


snapshotLifecycleCheck(
    'Legacy capture entry point no longer constructs retrospective capture service',
    is_string(
        $legacyCaptureSource
    )
    &&
    !str_contains(
        $legacyCaptureSource,
        'new PlayerGameweekSnapshotCapture'
    )
);


snapshotLifecycleCheck(
    'Legacy capture entry point no longer invokes completed-gameweek reconstruction',
    is_string(
        $legacyCaptureSource
    )
    &&
    !str_contains(
        $legacyCaptureSource,
        'captureLatestCompletedGameweek'
    )
);


snapshotLifecycleCheck(
    'Legacy capture entry point explains that retrospective capture is retired',
    is_string(
        $legacyCaptureSource
    )
    &&
    str_contains(
        $legacyCaptureSource,
        'RETIRED'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * FIXTURE-HISTORY IMPORT ISOLATION
 * ============================================================
 *
 * Completed fixture-history import provides factual outcome
 * evidence only.
 *
 * It must not reconstruct historical player snapshots from
 * the current live player pool.
 *
 * Player snapshot history now comes exclusively from the
 * pre-deadline candidate capture and deadline promotion
 * lifecycle.
 */

echo "============================================<br>";
echo "Scenario J: Fixture-History Import Isolation<br>";
echo "============================================<br>";


$fixtureHistoryUpdaterFile =
    __DIR__
    . '/../cron/updatePlayerFixtureHistory.php';


$fixtureHistoryUpdaterSource =
    is_file(
        $fixtureHistoryUpdaterFile
    )
        ? file_get_contents(
            $fixtureHistoryUpdaterFile
        )
        : false;


snapshotLifecycleCheck(
    'Player fixture-history updater exists',
    is_string(
        $fixtureHistoryUpdaterSource
    )
);


snapshotLifecycleCheck(
    'Fixture-history updater no longer constructs retrospective snapshot capture service',
    is_string(
        $fixtureHistoryUpdaterSource
    )
    &&
    !str_contains(
        $fixtureHistoryUpdaterSource,
        'new PlayerGameweekSnapshotCapture('
    )
);


snapshotLifecycleCheck(
    'Fixture-history updater no longer invokes completed-gameweek reconstruction',
    is_string(
        $fixtureHistoryUpdaterSource
    )
    &&
    !str_contains(
        $fixtureHistoryUpdaterSource,
        'captureLatestCompletedGameweek'
    )
);


snapshotLifecycleCheck(
    'Fixture-history updater no longer uses retrospective snapshot capture gate',
    is_string(
        $fixtureHistoryUpdaterSource
    )
    &&
    !str_contains(
        $fixtureHistoryUpdaterSource,
        'new PlayerGameweekSnapshotCaptureGate'
    )
);


snapshotLifecycleCheck(
    'Fixture-history updater does not construct immutable player snapshot repository',
    is_string(
        $fixtureHistoryUpdaterSource
    )
    &&
    !str_contains(
        $fixtureHistoryUpdaterSource,
        'new PlayerGameweekSnapshotRepository'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * LEGACY RETROSPECTIVE SERVICE RETIREMENT
 * ============================================================
 *
 * The old retrospective snapshot reconstruction service and
 * its automatic-capture gate are no longer part of the player
 * snapshot architecture.
 *
 * Historical player snapshots must originate from the
 * pre-deadline candidate lifecycle only.
 */

echo "============================================<br>";
echo "Scenario K: Legacy Retrospective Service Retirement<br>";
echo "============================================<br>";


$legacyCaptureServiceFile =
    __DIR__
    . '/../classes/PlayerGameweekSnapshotCapture.php';


$legacyCaptureGateFile =
    __DIR__
    . '/../classes/PlayerGameweekSnapshotCaptureGate.php';


snapshotLifecycleCheck(
    'Legacy retrospective snapshot capture service has been removed',
    !is_file(
        $legacyCaptureServiceFile
    )
);


snapshotLifecycleCheck(
    'Legacy automatic snapshot capture gate has been removed',
    !is_file(
        $legacyCaptureGateFile
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * SNAPSHOT LIFECYCLE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Snapshot Lifecycle Diagnostic<br>";
echo "============================================<br><br>";


echo "Live players table:<br>";
echo "Refreshable current FPL state<br><br>";


echo "Historical snapshot table:<br>";
echo "Immutable player/gameweek state once captured<br><br>";


echo "Live updater snapshot writes: "
    . (
        !str_contains(
            $updateSource,
            'PlayerGameweekSnapshotRepository'
        )
            ? 'Disabled'
            : 'Still Present'
    )
    . "<br>";


echo "Immutable repository method: "
    . (
        $immutableMethodExists
            ? 'Available'
            : 'Missing'
    )
    . "<br>";


echo "Historical Snapshot Rows: "
    . number_format(
        $snapshotRows
    )
    . "<br>";


echo "Historical Snapshot Gameweeks: "
    . number_format(
        $snapshotGameweeks
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Lifecycle Test Summary<br>";
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