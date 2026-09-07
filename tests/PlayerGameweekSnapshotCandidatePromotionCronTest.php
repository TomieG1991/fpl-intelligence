<?php

/*
 * ============================================================
 * PLAYER GAMEWEEK SNAPSHOT CANDIDATE PROMOTION CRON TEST
 * ============================================================
 *
 * Protects the thin operational entry point responsible for
 * promoting pre-deadline player snapshot candidates into
 * immutable player_gameweek_snapshots history.
 *
 * The cron must:
 *
 * - load the project autoloader
 * - construct the real production dependency chain
 * - use current UTC time during normal execution
 * - call the promotion runner exactly once
 * - display ready/promoted/unchanged accounting
 * - validate ready = promoted + unchanged
 * - handle failures explicitly
 *
 * The test must never promote real live candidates.
 *
 * Therefore the production cron supports these controlled
 * test seams:
 *
 * $playerSnapshotPromotionCronRunner
 * $playerSnapshotPromotionCronTimestamp
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


function playerSnapshotPromotionCronAssert(
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


function playerSnapshotPromotionCronSection(
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
 * CRON PATH
 * ============================================================
 */

$cronPath =
    __DIR__
    . '/../cron/promotePlayerGameweekSnapshotCandidates.php';


/*
 * ============================================================
 * A. CRON FILE EXISTS
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'A. Promotion Cron File'
);


$cronExists =
    is_file(
        $cronPath
    );


playerSnapshotPromotionCronAssert(
    $cronExists,
    'Player snapshot candidate promotion cron exists.'
);


if (
    !$cronExists
) {

    playerSnapshotPromotionCronSection(
        'Player Snapshot Candidate Promotion Cron Test Summary'
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


/*
 * ============================================================
 * READ CRON SOURCE
 * ============================================================
 */

$cronSource =
    file_get_contents(
        $cronPath
    );


if (
    $cronSource === false
) {

    die(
        'Player snapshot promotion cron source could not be read.'
    );
}


/*
 * ============================================================
 * B. PROJECT BOOTSTRAP
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'B. Project Bootstrap'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        "../classes/autoload.php"
    ),
    'Promotion cron loads the project autoloader.'
);


/*
 * ============================================================
 * C. PRODUCTION DEPENDENCY WIRING
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'C. Production Dependency Wiring'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'new Database'
    ),
    'Promotion cron constructs Database during normal execution.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'new PlayerGameweekSnapshotCandidateRepository'
    ),
    'Promotion cron constructs player snapshot candidate repository.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'new PlayerGameweekSnapshotRepository'
    ),
    'Promotion cron constructs immutable snapshot repository.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'new PlayerGameweekSnapshotCandidatePromotionService'
    ),
    'Promotion cron constructs player snapshot promotion service.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'new PlayerGameweekSnapshotCandidatePromotionRunner'
    ),
    'Promotion cron constructs player snapshot promotion runner.'
);


/*
 * ============================================================
 * D. UTC EXECUTION TIME
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'D. UTC Execution Time'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'gmdate('
    ),
    'Promotion cron derives normal execution time in UTC.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        "'Y-m-d H:i:s'"
    ),
    'Promotion cron uses MySQL-compatible UTC timestamp format.'
);


/*
 * ============================================================
 * TEST DOUBLE — PROMOTION RUNNER
 * ============================================================
 */

class PlayerSnapshotPromotionCronRunnerDouble
{
    private array $result;


    private array $calls =
        [];


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
    }


    public function run(
        string $timestamp
    ): array {

        $this->calls[] =
            $timestamp;


        return
            $this->result;
    }


    public function getCalls(): array
    {

        return
            $this->calls;
    }
}


/*
 * ============================================================
 * E. CONTROLLED SUCCESSFUL EXECUTION
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'E. Controlled Successful Execution'
);


$playerSnapshotPromotionCronRunner =
    new PlayerSnapshotPromotionCronRunnerDouble(
        [
            'status' =>
                'Complete',

            'ready' =>
                654,

            'promoted' =>
                650,

            'unchanged' =>
                4
        ]
    );


$playerSnapshotPromotionCronTimestamp =
    '2026-09-18 18:30:00';


ob_start();


include $cronPath;


$successOutput =
    (string) ob_get_clean();


playerSnapshotPromotionCronAssert(
    $playerSnapshotPromotionCronRunner
        ->getCalls()
    ===
    [
        '2026-09-18 18:30:00'
    ],
    'Promotion cron calls runner exactly once with controlled timestamp.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $successOutput,
        'Player Gameweek Snapshot Candidate Promotion'
    ),
    'Promotion cron displays player snapshot promotion heading.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $successOutput,
        'Candidates Ready: 654'
    ),
    'Promotion cron displays ready candidate count.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $successOutput,
        'Snapshots Promoted: 650'
    ),
    'Promotion cron displays promoted snapshot count.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $successOutput,
        'Candidates Unchanged: 4'
    ),
    'Promotion cron displays unchanged candidate count.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $successOutput,
        'RESULT: PLAYER SNAPSHOT PROMOTION COMPLETE'
    ),
    'Successful cron execution displays completion result.'
);


/*
 * ============================================================
 * F. ZERO-CANDIDATE EXECUTION
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'F. Zero-Candidate Execution'
);


$playerSnapshotPromotionCronRunner =
    new PlayerSnapshotPromotionCronRunnerDouble(
        [
            'status' =>
                'Complete',

            'ready' =>
                0,

            'promoted' =>
                0,

            'unchanged' =>
                0
        ]
    );


$playerSnapshotPromotionCronTimestamp =
    '2026-09-18 18:31:00';


ob_start();


include $cronPath;


$emptyOutput =
    (string) ob_get_clean();


playerSnapshotPromotionCronAssert(
    $playerSnapshotPromotionCronRunner
        ->getCalls()
    ===
    [
        '2026-09-18 18:31:00'
    ],
    'Zero-candidate cron execution still calls runner exactly once.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $emptyOutput,
        'Candidates Ready: 0'
    ),
    'Zero-candidate execution reports zero ready candidates.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $emptyOutput,
        'Snapshots Promoted: 0'
    ),
    'Zero-candidate execution reports zero promotions.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $emptyOutput,
        'Candidates Unchanged: 0'
    ),
    'Zero-candidate execution reports zero unchanged candidates.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $emptyOutput,
        'RESULT: PLAYER SNAPSHOT PROMOTION COMPLETE'
    ),
    'Zero-candidate execution is a successful completion.'
);


/*
 * ============================================================
 * G. ACCOUNTING VALIDATION CONTRACT
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'G. Accounting Validation Contract'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        '$ready'
    ),
    'Promotion cron reads ready accounting.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        '$promoted'
    ),
    'Promotion cron reads promoted accounting.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        '$unchanged'
    ),
    'Promotion cron reads unchanged accounting.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        '$promoted'
    )
    &&
    str_contains(
        $cronSource,
        '$unchanged'
    ),
    'Promotion cron contains accounting components required for balance validation.'
);


/*
 * ============================================================
 * H. FAILURE HANDLING CONTRACT
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'H. Failure Handling Contract'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'catch'
    ),
    'Promotion cron has failure handling.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'Throwable'
    ),
    'Promotion cron catches Throwable failures.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'RESULT: PLAYER SNAPSHOT PROMOTION FAILED'
    ),
    'Promotion cron exposes explicit failure result.'
);


playerSnapshotPromotionCronAssert(
    str_contains(
        $cronSource,
        'exit(1)'
    ),
    'Promotion cron returns failure exit status after exception.'
);


/*
 * ============================================================
 * I. THIN ENTRY-POINT BOUNDARY
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'I. Thin Entry-Point Boundary'
);


playerSnapshotPromotionCronAssert(
    !str_contains(
        $cronSource,
        'INSERT INTO player_gameweek_snapshots'
    ),
    'Promotion cron does not write immutable snapshots directly.'
);


playerSnapshotPromotionCronAssert(
    !str_contains(
        $cronSource,
        'UPDATE player_gameweek_snapshot_candidates'
    ),
    'Promotion cron does not mutate candidates directly.'
);


playerSnapshotPromotionCronAssert(
    !str_contains(
        $cronSource,
        'DELETE FROM player_gameweek_snapshot_candidates'
    ),
    'Promotion cron does not delete candidates.'
);


playerSnapshotPromotionCronAssert(
    !str_contains(
        $cronSource,
        'SELECT'
    ),
    'Promotion cron contains no domain SQL.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

playerSnapshotPromotionCronSection(
    'Player Snapshot Candidate Promotion Cron Test Summary'
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