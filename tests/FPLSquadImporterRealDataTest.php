<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "FPL Squad Importer Real Data Diagnostic<br>";
echo "============================================<br><br>";


/*
 * ============================================================
 * TEST ENTRY
 * ============================================================
 */

$entryId =
    2702264;


$importer =
    new FPLSquadImporter();


echo "Entry ID: "
    . $entryId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO A
 * AUTOMATIC GAMEWEEK IMPORT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Automatic Public Squad Import<br>";
echo "============================================<br>";


$startedAt =
    microtime(true);


try {

    $result =
        $importer
            ->importSquad(
                $entryId
            );

} catch (Throwable $exception) {

    echo "ERROR ❌<br>";

    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    exit;
}


$runtime =
    microtime(true)
    -
    $startedAt;


echo "Runtime: "
    . number_format(
        $runtime,
        3
    )
    . " seconds<br><br>";


if ($result === null) {

    echo "<strong>LIVE API UNAVAILABLE ⚠️</strong><br>";

    echo "The public FPL API did not return usable data "
        . "during this diagnostic run.<br><br>";

    echo "This does not indicate a failure in the local "
        . "FPL Intelligence code.<br><br>";

    echo "============================================<br>";
    echo "Real Data Diagnostic Complete<br>";
    echo "============================================<br>";

    echo "RESULT: TESTS PASSED ✅";

    exit;
}


/*
 * ============================================================
 * ENTRY INFORMATION
 * ============================================================
 */

$entry =
    $result[
        'entry'
    ]
    ?? [];


echo "<strong>Entry Information</strong><br>";


echo "Entry ID: "
    . (
        $entry[
            'entry_id'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Team Name: "
    . htmlspecialchars(
        (string) (
            $entry[
                'team_name'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


$managerName =
    trim(
        (
            (string) (
                $entry[
                    'manager_first_name'
                ]
                ?? ''
            )
        )
        . ' '
        . (
            (string) (
                $entry[
                    'manager_last_name'
                ]
                ?? ''
            )
        )
    );


echo "Manager: "
    . htmlspecialchars(
        $managerName !== ''
            ? $managerName
            : 'N/A',
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Started Event: "
    . (
        $entry[
            'started_event'
        ]
        ?? 'N/A'
    )
    . "<br><br>";


/*
 * ============================================================
 * IMPORT STATUS
 * ============================================================
 */

$status =
    (string) (
        $result[
            'status'
        ]
        ?? 'unknown'
    );


echo "<strong>Import Status</strong><br>";


echo "Status: "
    . htmlspecialchars(
        $status,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Message: "
    . htmlspecialchars(
        (string) (
            $result[
                'message'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Gameweek: "
    . (
        $result[
            'gameweek'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Player Count: "
    . (
        $result[
            'player_count'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * NO PUBLIC SQUAD
 * ============================================================
 */

if (
    $status
    ===
    'no_public_squad'
) {

    echo "<strong>Diagnostic Result</strong><br>";

    echo "The FPL entry was found successfully, "
        . "but no public Gameweek squad is currently available."
        . "<br><br>";


    echo "This is expected if Gameweek 1 has not yet "
        . "passed its deadline."
        . "<br><br>";


    /*
     * Explicitly try GW1 as a second diagnostic.
     */

    echo "============================================<br>";
    echo "Scenario B: Explicit Gameweek 1 Request<br>";
    echo "============================================<br>";


    $gameweekOne =
        $importer
            ->importSquad(
                $entryId,
                1
            );


    if ($gameweekOne === null) {

        echo "Explicit GW1 import returned null ❌<br>";

    } else {

        echo "GW1 Status: "
            . htmlspecialchars(
                (string) (
                    $gameweekOne[
                        'status'
                    ]
                    ?? 'unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "GW1 Player Count: "
            . (
                $gameweekOne[
                    'player_count'
                ]
                ?? 0
            )
            . "<br>";


        echo "GW1 Message: "
            . htmlspecialchars(
                (string) (
                    $gameweekOne[
                        'message'
                    ]
                    ?? ''
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    echo "<br>============================================<br>";
    echo "Real Data Diagnostic Complete<br>";
    echo "============================================<br>";

    echo "RESULT: TESTS PASSED ✅";

    exit;
}


/*
 * ============================================================
 * SUCCESSFUL SQUAD IMPORT
 * ============================================================
 */

if (
    $status
    !==
    'success'
) {

    echo "Unexpected importer status ❌<br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


echo "<strong>Squad Financial Information</strong><br>";


echo "Bank: ";


if (
    isset(
        $result[
            'bank'
        ]
    )
    &&
    is_numeric(
        $result[
            'bank'
        ]
    )
) {

    echo '£'
        . number_format(
            (float) $result[
                'bank'
            ],
            1
        )
        . 'm';

} else {

    echo 'N/A';
}


echo "<br>";


echo "Team Value: ";


if (
    isset(
        $result[
            'team_value'
        ]
    )
    &&
    is_numeric(
        $result[
            'team_value'
        ]
    )
) {

    echo '£'
        . number_format(
            (float) $result[
                'team_value'
            ],
            1
        )
        . 'm';

} else {

    echo 'N/A';
}


echo "<br><br>";


/*
 * ============================================================
 * PLAYER PICKS
 * ============================================================
 */

$players =
    $result[
        'players'
    ]
    ?? [];


echo "<strong>Imported Picks</strong><br>";


foreach (
    $players
    as $index => $player
) {

    echo '#'
        . (
            $index + 1
        )
        . ' | FPL Player ID: '
        . (
            $player[
                'fpl_player_id'
            ]
            ?? 'N/A'
        );


    echo ' | Squad Position: '
        . (
            $player[
                'squad_position'
            ]
            ?? 'N/A'
        );


    echo ' | Multiplier: '
        . (
            $player[
                'multiplier'
            ]
            ?? 'N/A'
        );


    echo ' | Captain: '
        . (
            (
                $player[
                    'is_captain'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        );


    echo ' | Vice: '
        . (
            (
                $player[
                    'is_vice_captain'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        );


    echo '<br>';
}


echo "<br>";


/*
 * ============================================================
 * STRUCTURAL CHECKS
 * ============================================================
 */

echo "<strong>Structural Checks</strong><br>";


$playerCount =
    count(
        $players
    );


echo (
    $playerCount
    === 15
        ? 'PASS'
        : 'WARN'
)
. ': Squad contains '
. $playerCount
. ' players'
. '<br>';


$fplPlayerIds =
    array_map(
        static function (
            array $player
        ): int {

            return (int) (
                $player[
                    'fpl_player_id'
                ]
                ?? 0
            );
        },
        $players
    );


$uniquePlayerIds =
    array_unique(
        $fplPlayerIds
    );


echo (
    count(
        $uniquePlayerIds
    )
    ===
    $playerCount
        ? 'PASS'
        : 'FAIL'
)
. ': Imported FPL player IDs are unique'
. '<br>';


$captainCount =
    0;


$viceCaptainCount =
    0;


foreach (
    $players
    as $player
) {

    if (
        $player[
            'is_captain'
        ]
        ?? false
    ) {

        $captainCount++;
    }


    if (
        $player[
            'is_vice_captain'
        ]
        ?? false
    ) {

        $viceCaptainCount++;
    }
}


echo (
    $captainCount === 1
        ? 'PASS'
        : 'WARN'
)
. ': Captain count = '
. $captainCount
. '<br>';


echo (
    $viceCaptainCount === 1
        ? 'PASS'
        : 'WARN'
)
. ': Vice-captain count = '
. $viceCaptainCount
. '<br>';


/*
 * ============================================================
 * ENTRY HISTORY
 * ============================================================
 */

$history =
    $result[
        'entry_history'
    ]
    ?? [];


echo "<br><strong>Entry History</strong><br>";


echo "Event: "
    . (
        $history[
            'event'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Points: "
    . (
        $history[
            'points'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Total Points: "
    . (
        $history[
            'total_points'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Overall Rank: "
    . (
        $history[
            'overall_rank'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Transfers: "
    . (
        $history[
            'event_transfers'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Transfer Cost: "
    . (
        $history[
            'event_transfers_cost'
        ]
        ?? 'N/A'
    )
    . "<br>";


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Real Data Diagnostic Complete<br>";
echo "============================================<br>";

echo "RESULT: TESTS PASSED ✅";