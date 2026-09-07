<?php

/*
 * ============================================================
 * PLAYER GAMEWEEK SNAPSHOT CANDIDATE CAPTURE CRON TEST
 * ============================================================
 *
 * Protects the thin operational entry point responsible for
 * capturing the latest live player state before the next FPL
 * deadline.
 *
 * The cron must:
 *
 * - load the project autoloader
 * - construct the real production dependency chain
 * - use current UTC time during normal execution
 * - call PlayerGameweekSnapshotCandidateProductionCapture once
 * - display capture accounting
 * - support an unavailable result cleanly
 * - contain no direct domain SQL
 *
 * The test must never capture real live player candidates.
 *
 * Therefore the production cron supports these controlled
 * test seams:
 *
 * $playerSnapshotCaptureCronService
 * $playerSnapshotCaptureCronTimestamp
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


function playerSnapshotCaptureCronAssert(
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


function playerSnapshotCaptureCronSection(
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
    . '/../cron/capturePlayerGameweekSnapshotCandidates.php';


/*
 * ============================================================
 * A. CRON FILE EXISTS
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'A. Candidate Capture Cron File'
);


$cronExists =
    is_file(
        $cronPath
    );


playerSnapshotCaptureCronAssert(
    $cronExists,
    'Player snapshot candidate capture cron exists.'
);


if (
    !$cronExists
) {

    playerSnapshotCaptureCronSection(
        'Player Snapshot Candidate Capture Cron Test Summary'
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
        'Player snapshot candidate capture cron source '
        . 'could not be read.'
    );
}


/*
 * ============================================================
 * B. PROJECT BOOTSTRAP
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'B. Project Bootstrap'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        "../classes/autoload.php"
    ),
    'Capture cron loads the project autoloader.'
);


/*
 * ============================================================
 * C. PRODUCTION DEPENDENCY WIRING
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'C. Production Dependency Wiring'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'new Database'
    ),
    'Capture cron constructs Database during normal execution.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'new GameweekRepository'
    ),
    'Capture cron constructs GameweekRepository.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'new PlayerRepository'
    ),
    'Capture cron constructs PlayerRepository.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'new PlayerGameweekSnapshotCandidateCaptureService'
    ),
    'Capture cron constructs candidate capture service.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'new PlayerGameweekSnapshotCandidateRepository'
    ),
    'Capture cron constructs candidate repository.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'new PlayerGameweekSnapshotCandidateProductionCapture'
    ),
    'Capture cron constructs production capture orchestration.'
);


/*
 * ============================================================
 * D. UTC EXECUTION TIME
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'D. UTC Execution Time'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'gmdate('
    ),
    'Capture cron derives normal execution time in UTC.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        "'Y-m-d H:i:s'"
    ),
    'Capture cron uses MySQL-compatible UTC timestamp format.'
);


/*
 * ============================================================
 * TEST DOUBLE — PRODUCTION CAPTURE SERVICE
 * ============================================================
 */

class PlayerSnapshotCaptureCronServiceDouble
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


    public function capture(
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
 * E. CONTROLLED SUCCESSFUL CAPTURE
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'E. Controlled Successful Capture'
);


$playerSnapshotCaptureCronService =
    new PlayerSnapshotCaptureCronServiceDouble(
        [
            'status' =>
                'Captured',

            'gameweek_id' =>
                7,

            'fpl_gameweek_id' =>
                3,

            'players_considered' =>
                654,

            'candidates_built' =>
                652,

            'saved' =>
                650,

            'unchanged' =>
                2
        ]
    );


$playerSnapshotCaptureCronTimestamp =
    '2026-09-10 08:00:00';


ob_start();


include $cronPath;


$successOutput =
    (string) ob_get_clean();


playerSnapshotCaptureCronAssert(
    $playerSnapshotCaptureCronService
        ->getCalls()
    ===
    [
        '2026-09-10 08:00:00'
    ],
    'Capture cron calls production capture exactly once with controlled timestamp.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $successOutput,
        'Player Gameweek Snapshot Candidate Capture'
    ),
    'Capture cron displays player snapshot candidate heading.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $successOutput,
        'Local Gameweek ID: 7'
    ),
    'Capture cron displays local gameweek identity.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $successOutput,
        'FPL Gameweek: 3'
    ),
    'Capture cron displays FPL gameweek identity.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $successOutput,
        'Players Considered: 654'
    ),
    'Capture cron displays players considered count.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $successOutput,
        'Candidates Built: 652'
    ),
    'Capture cron displays candidates built count.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $successOutput,
        'Candidates Saved: 650'
    ),
    'Capture cron displays saved candidate count.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $successOutput,
        'Candidates Unchanged: 2'
    ),
    'Capture cron displays unchanged candidate count.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $successOutput,
        'RESULT: PLAYER SNAPSHOT CANDIDATE CAPTURE COMPLETE'
    ),
    'Successful capture displays completion result.'
);


/*
 * ============================================================
 * F. CAPTURE ACCOUNTING CONTRACT
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'F. Capture Accounting Contract'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        '$playersConsidered'
    ),
    'Capture cron reads players-considered accounting.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        '$candidatesBuilt'
    ),
    'Capture cron reads candidates-built accounting.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        '$saved'
    ),
    'Capture cron reads saved accounting.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        '$unchanged'
    ),
    'Capture cron reads unchanged accounting.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        '$saved'
    )
    &&
    str_contains(
        $cronSource,
        '$unchanged'
    ),
    'Capture cron contains components required for candidate accounting validation.'
);


/*
 * ============================================================
 * G. UNAVAILABLE CAPTURE
 * ============================================================
 *
 * No future deadline is a valid lifecycle result, not a crash.
 */

playerSnapshotCaptureCronSection(
    'G. Unavailable Capture'
);


$playerSnapshotCaptureCronService =
    new PlayerSnapshotCaptureCronServiceDouble(
        [
            'status' =>
                'Unavailable',

            'gameweek_id' =>
                null,

            'fpl_gameweek_id' =>
                null,

            'players_considered' =>
                0,

            'candidates_built' =>
                0,

            'saved' =>
                0,

            'unchanged' =>
                0
        ]
    );


$playerSnapshotCaptureCronTimestamp =
    '2027-06-01 08:00:00';


ob_start();


include $cronPath;


$unavailableOutput =
    (string) ob_get_clean();


playerSnapshotCaptureCronAssert(
    $playerSnapshotCaptureCronService
        ->getCalls()
    ===
    [
        '2027-06-01 08:00:00'
    ],
    'Unavailable capture still calls production capture exactly once.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $unavailableOutput,
        'No future player snapshot deadline is currently available.'
    ),
    'Unavailable capture explains that no future deadline exists.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $unavailableOutput,
        'RESULT: PLAYER SNAPSHOT CANDIDATE CAPTURE UNAVAILABLE'
    ),
    'Unavailable capture exposes explicit lifecycle result.'
);


/*
 * ============================================================
 * H. FAILURE HANDLING CONTRACT
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'H. Failure Handling Contract'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'catch'
    ),
    'Capture cron has failure handling.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'Throwable'
    ),
    'Capture cron catches Throwable failures.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'RESULT: PLAYER SNAPSHOT CANDIDATE CAPTURE FAILED'
    ),
    'Capture cron exposes explicit failure result.'
);


playerSnapshotCaptureCronAssert(
    str_contains(
        $cronSource,
        'exit(1)'
    ),
    'Capture cron returns failure exit status after exception.'
);


/*
 * ============================================================
 * I. THIN ENTRY-POINT BOUNDARY
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'I. Thin Entry-Point Boundary'
);


playerSnapshotCaptureCronAssert(
    !str_contains(
        $cronSource,
        'INSERT INTO player_gameweek_snapshot_candidates'
    ),
    'Capture cron does not write candidate rows directly.'
);


playerSnapshotCaptureCronAssert(
    !str_contains(
        $cronSource,
        'UPDATE player_gameweek_snapshot_candidates'
    ),
    'Capture cron does not update candidate rows directly.'
);


playerSnapshotCaptureCronAssert(
    !str_contains(
        $cronSource,
        'INSERT INTO player_gameweek_snapshots'
    ),
    'Capture cron does not write immutable snapshots directly.'
);


playerSnapshotCaptureCronAssert(
    !str_contains(
        $cronSource,
        'player_fixture_history'
    ),
    'Capture cron has no fixture-history dependency.'
);


playerSnapshotCaptureCronAssert(
    !str_contains(
        $cronSource,
        'PlayerGameweekSnapshotRepository'
    ),
    'Capture cron does not construct immutable snapshot repository.'
);


playerSnapshotCaptureCronAssert(
    !str_contains(
        $cronSource,
        'SELECT'
    ),
    'Capture cron contains no domain SQL.'
);


/*
 * ============================================================
 * J. LIVE UPDATER REMAINS SEPARATE
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'J. Live Updater Remains Separate'
);


$updatePath =
    __DIR__
    . '/../cron/updateFPLData.php';


$updateSource =
    is_file(
        $updatePath
    )
        ? file_get_contents(
            $updatePath
        )
        : false;


playerSnapshotCaptureCronAssert(
    is_string(
        $updateSource
    ),
    'Live FPL updater exists for boundary verification.'
);


playerSnapshotCaptureCronAssert(
    is_string(
        $updateSource
    )
    &&
    !str_contains(
        $updateSource,
        'PlayerGameweekSnapshotCandidateRepository'
    ),
    'Live updater does not persist snapshot candidates directly.'
);


playerSnapshotCaptureCronAssert(
    is_string(
        $updateSource
    )
    &&
    !str_contains(
        $updateSource,
        'PlayerGameweekSnapshotCandidateProductionCapture'
    ),
    'Live updater does not orchestrate snapshot candidate capture.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

playerSnapshotCaptureCronSection(
    'Player Snapshot Candidate Capture Cron Test Summary'
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