<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Capture Test<br>";
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

function snapshotCaptureCheck(
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


$playerRepository =
    new PlayerRepository(
        $connection
    );


/*
 * ============================================================
 * SCENARIO A
 * COMPLETED GAMEWEEK RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Completed Gameweek Resolution<br>";
echo "============================================<br>";


$gameweeks =
    $gameweekRepository
        ->getAll();


$completedGameweeks =
    array_values(
        array_filter(
            $gameweeks,
            static function (
                array $gameweek
            ): bool {

                return !empty(
                    $gameweek[
                        'finished'
                    ]
                    ?? false
                );
            }
        )
    );


snapshotCaptureCheck(
    'Stored gameweeks are available',
    !empty(
        $gameweeks
    )
);


snapshotCaptureCheck(
    'At least one completed gameweek resolves',
    !empty(
        $completedGameweeks
    )
);


$latestCompletedGameweek =
    null;


foreach (
    $completedGameweeks
    as $completedGameweek
) {

    if (
        $latestCompletedGameweek === null
        ||
        (
            (int) (
                $completedGameweek[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
            >
            (int) (
                $latestCompletedGameweek[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
        )
    ) {

        $latestCompletedGameweek =
            $completedGameweek;
    }
}


snapshotCaptureCheck(
    'Latest completed gameweek resolves',
    is_array(
        $latestCompletedGameweek
    )
);


if (
    is_array(
        $latestCompletedGameweek
    )
) {

    echo "Latest Completed Gameweek: GW"
        . (
            $latestCompletedGameweek[
                'fpl_gameweek_id'
            ]
            ?? '—'
        )
        . "<br>";


    echo "Finished: "
        . (
            !empty(
                $latestCompletedGameweek[
                    'finished'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";


    echo "Data Checked: "
        . (
            !empty(
                $latestCompletedGameweek[
                    'data_checked'
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
 * SCENARIO B
 * PLAYER SOURCE FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Player Source Foundation<br>";
echo "============================================<br>";


$players =
    $playerRepository
        ->getAll();


snapshotCaptureCheck(
    'Current player source contains rows',
    is_array(
        $players
    )
    &&
    !empty(
        $players
    )
);


$validPlayers =
    0;


foreach (
    $players
    as $player
) {

    if (
        (int) (
            $player[
                'id'
            ]
            ?? 0
        )
        > 0
        &&
        (int) (
            $player[
                'fpl_player_id'
            ]
            ?? 0
        )
        > 0
        &&
        (int) (
            $player[
                'team_id'
            ]
            ?? 0
        )
        > 0
    ) {

        $validPlayers++;
    }
}


snapshotCaptureCheck(
    'Player source exposes snapshot-compatible identities',
    $validPlayers > 0
);


echo "Players Loaded: "
    . number_format(
        count(
            $players
        )
    )
    . "<br>";


echo "Players With Valid Snapshot Identity: "
    . number_format(
        $validPlayers
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * EXISTING SNAPSHOT STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Existing Snapshot State<br>";
echo "============================================<br>";


$existingSnapshots =
    [];


if (
    is_array(
        $latestCompletedGameweek
    )
) {

    $existingSnapshots =
        $snapshotRepository
            ->getByGameweekId(
                (int) $latestCompletedGameweek[
                    'id'
                ]
            );
}


snapshotCaptureCheck(
    'Existing snapshot state can be queried for completed gameweek',
    is_array(
        $existingSnapshots
    )
);


echo "Existing Snapshots For Latest Completed GW: "
    . number_format(
        count(
            $existingSnapshots
        )
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * CAPTURE SERVICE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Capture Service Contract<br>";
echo "============================================<br>";


$captureServiceExists =
    class_exists(
        'PlayerGameweekSnapshotCapture'
    );


snapshotCaptureCheck(
    'PlayerGameweekSnapshotCapture service exists',
    $captureServiceExists
);


if (
    !$captureServiceExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "classes/PlayerGameweekSnapshotCapture.php<br><br>";

    echo "Required responsibilities:<br>";
    echo "- resolve the latest completed gameweek<br>";
    echo "- reject non-completed gameweeks<br>";
    echo "- load current player state<br>";
    echo "- build snapshot payloads<br>";
    echo "- use insertIfAbsent() only<br>";
    echo "- never overwrite an existing snapshot<br>";
    echo "- report inserted / existing / skipped counts<br><br>";


    echo "============================================<br>";
    echo "Player Gameweek Snapshot Capture Test Summary<br>";
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
 * SERVICE METHOD CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Service Method Contract<br>";
echo "============================================<br>";


$capture =
    new PlayerGameweekSnapshotCapture(
        $connection
    );


$methodExists =
    method_exists(
        $capture,
        'captureLatestCompletedGameweek'
    );


snapshotCaptureCheck(
    'Capture service exposes captureLatestCompletedGameweek()',
    $methodExists
);


if (
    !$methodExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "PlayerGameweekSnapshotCapture::captureLatestCompletedGameweek()<br><br>";


    echo "============================================<br>";
    echo "Player Gameweek Snapshot Capture Test Summary<br>";
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
 * SCENARIO F
 * SAFE REAL CAPTURE
 * ============================================================
 *
 * Current completed gameweek snapshots already exist.
 *
 * Therefore a safe rerun should insert zero rows and report
 * the existing snapshots rather than rewriting them.
 */

echo "============================================<br>";
echo "Scenario F: Safe Real Capture<br>";
echo "============================================<br>";


$captureResult =
    $capture
        ->captureLatestCompletedGameweek();


snapshotCaptureCheck(
    'Capture returns an array',
    is_array(
        $captureResult
    )
);


snapshotCaptureCheck(
    'Capture explicitly reports success',
    (
        $captureResult[
            'status'
        ]
        ?? null
    )
    ===
    'Complete'
);


snapshotCaptureCheck(
    'Capture exposes gameweek identity',
    is_numeric(
        $captureResult[
            'gameweek_id'
        ]
        ?? null
    )
);


snapshotCaptureCheck(
    'Capture exposes official FPL gameweek identity',
    is_numeric(
        $captureResult[
            'fpl_gameweek_id'
        ]
        ?? null
    )
);


snapshotCaptureCheck(
    'Capture exposes players considered',
    is_numeric(
        $captureResult[
            'players_considered'
        ]
        ?? null
    )
);


snapshotCaptureCheck(
    'Capture exposes inserted count',
    is_numeric(
        $captureResult[
            'inserted'
        ]
        ?? null
    )
);


snapshotCaptureCheck(
    'Capture exposes existing count',
    is_numeric(
        $captureResult[
            'existing'
        ]
        ?? null
    )
);


snapshotCaptureCheck(
    'Capture exposes skipped count',
    is_numeric(
        $captureResult[
            'skipped'
        ]
        ?? null
    )
);


$inserted =
    (int) (
        $captureResult[
            'inserted'
        ]
        ?? -1
    );


$existing =
    (int) (
        $captureResult[
            'existing'
        ]
        ?? -1
    );


$skipped =
    (int) (
        $captureResult[
            'skipped'
        ]
        ?? -1
    );


$playersConsidered =
    (int) (
        $captureResult[
            'players_considered'
        ]
        ?? -1
    );


snapshotCaptureCheck(
    'Safe rerun does not insert duplicate completed-gameweek snapshots',
    $inserted === 0
);


snapshotCaptureCheck(
    'Safe rerun reports existing completed-gameweek snapshots',
    $existing > 0
);


snapshotCaptureCheck(
    'Capture accounting matches players considered',
    $playersConsidered
    ===
    (
        $inserted
        +
        $existing
        +
        $skipped
    )
);


echo "Players Considered: "
    . number_format(
        $playersConsidered
    )
    . "<br>";


echo "Inserted: "
    . number_format(
        $inserted
    )
    . "<br>";


echo "Existing: "
    . number_format(
        $existing
    )
    . "<br>";


echo "Skipped: "
    . number_format(
        $skipped
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * IMMUTABILITY AFTER CAPTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Capture Immutability<br>";
echo "============================================<br>";


$afterSnapshots =
    $snapshotRepository
        ->getByGameweekId(
            (int) (
                $captureResult[
                    'gameweek_id'
                ]
                ?? 0
            )
        );


snapshotCaptureCheck(
    'Snapshot count remains stable after safe rerun',
    count(
        $afterSnapshots
    )
    ===
    count(
        $existingSnapshots
    )
);


echo "Before Capture: "
    . number_format(
        count(
            $existingSnapshots
        )
    )
    . "<br>";


echo "After Capture: "
    . number_format(
        count(
            $afterSnapshots
        )
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * CAPTURE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Snapshot Capture Diagnostic<br>";
echo "============================================<br><br>";


echo "Gameweek: GW"
    . (
        $captureResult[
            'fpl_gameweek_id'
        ]
        ?? '—'
    )
    . "<br>";


echo "Status: "
    . htmlspecialchars(
        (string) (
            $captureResult[
                'status'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Players Considered: "
    . number_format(
        $playersConsidered
    )
    . "<br>";


echo "Snapshots Inserted: "
    . number_format(
        $inserted
    )
    . "<br>";


echo "Snapshots Already Present: "
    . number_format(
        $existing
    )
    . "<br>";


echo "Snapshots Skipped: "
    . number_format(
        $skipped
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Capture Test Summary<br>";
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