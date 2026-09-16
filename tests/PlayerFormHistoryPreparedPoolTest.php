<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Form History Prepared Pool Test<br>";
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

function preparedPoolTestResult(
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


/*
 * ============================================================
 * FAKE HISTORY REPOSITORY
 * ============================================================
 *
 * No database is involved in this contract test.
 *
 * The repository contains authoritative chronological windows
 * for three representative players:
 *
 *     Player 101:
 *         more than five fixture rows
 *         more than five appearance rows
 *
 *     Player 202:
 *         fewer than five rows
 *
 *     Player 303:
 *         no history
 *
 * Query counters let us prove that prepared history is actually
 * consumed rather than silently falling back to per-player
 * repository retrieval.
 * ============================================================
 */

class PreparedPoolHistoryRepository
    extends PlayerFixtureHistoryRepository
{
    public int $fixtureQueries =
        0;

    public int $appearanceQueries =
        0;


    private array $fixtureRows;

    private array $appearanceRows;


    public function __construct()
    {
        /*
         * Deliberately do not call the parent constructor.
         *
         * This fake overrides every repository method exercised
         * by this test, so no PDO connection is required.
         */

        $this->fixtureRows = [

            101 => [
                ['id' => 1, 'minutes' => 90],
                ['id' => 2, 'minutes' => 0],
                ['id' => 3, 'minutes' => 75],
                ['id' => 4, 'minutes' => 90],
                ['id' => 5, 'minutes' => 0],
                ['id' => 6, 'minutes' => 82],
                ['id' => 7, 'minutes' => 90]
            ],

            202 => [
                ['id' => 21, 'minutes' => 0],
                ['id' => 22, 'minutes' => 45]
            ]
        ];


        $this->appearanceRows = [

            101 => [
                ['id' => 1, 'minutes' => 90],
                ['id' => 3, 'minutes' => 75],
                ['id' => 4, 'minutes' => 90],
                ['id' => 6, 'minutes' => 82],
                ['id' => 7, 'minutes' => 90],
                ['id' => 8, 'minutes' => 64]
            ],

            202 => [
                ['id' => 22, 'minutes' => 45]
            ]
        ];
    }


    public function getRecentByPlayerId(
        int $playerId,
        int $limit = 5
    ): array {

        $this->fixtureQueries++;


        $rows =
            $this->fixtureRows[
                $playerId
            ]
            ?? [];


        return array_slice(
            $rows,
            -$limit
        );
    }


    public function getRecentAppearancesByPlayerId(
        int $playerId,
        int $limit = 5
    ): array {

        $this->appearanceQueries++;


        $rows =
            $this->appearanceRows[
                $playerId
            ]
            ?? [];


        return array_slice(
            $rows,
            -$limit
        );
    }
}


/*
 * ============================================================
 * AUTHORITATIVE WINDOWS
 * ============================================================
 *
 * First build the histories through today's production path.
 * These become the behavioural reference that the prepared
 * history must reproduce exactly.
 * ============================================================
 */

$referenceRepository =
    new PreparedPoolHistoryRepository();


$referenceHistory =
    new PlayerFormHistory(
        $referenceRepository
    );


$reference101 =
    $referenceHistory
        ->buildDefaultHistory(
            101
        );


$reference202 =
    $referenceHistory
        ->buildDefaultHistory(
            202
        );


$reference303 =
    $referenceHistory
        ->buildDefaultHistory(
            303
        );


$reference101Short =
    $referenceHistory
        ->buildShortHistory(
            101
        );


/*
 * ============================================================
 * PREPARED POOL
 * ============================================================
 *
 * This is the contract we want production to support.
 *
 * The prepared rows are already chronological and represent the
 * same maximum five-row windows that today's repository returns.
 * ============================================================
 */

$preparedFixtureHistory = [

    101 => [
        ['id' => 3, 'minutes' => 75],
        ['id' => 4, 'minutes' => 90],
        ['id' => 5, 'minutes' => 0],
        ['id' => 6, 'minutes' => 82],
        ['id' => 7, 'minutes' => 90]
    ],

    202 => [
        ['id' => 21, 'minutes' => 0],
        ['id' => 22, 'minutes' => 45]
    ],

    303 => []
];


$preparedAppearanceHistory = [

    101 => [
        ['id' => 3, 'minutes' => 75],
        ['id' => 4, 'minutes' => 90],
        ['id' => 6, 'minutes' => 82],
        ['id' => 7, 'minutes' => 90],
        ['id' => 8, 'minutes' => 64]
    ],

    202 => [
        ['id' => 22, 'minutes' => 45]
    ],

    303 => []
];


$preparedRepository =
    new PreparedPoolHistoryRepository();


$preparedHistory =
    new PlayerFormHistory(
        $preparedRepository
    );


/*
 * This method intentionally does not exist yet.
 *
 * The test must therefore RED before we make any production
 * change.
 */
$preparedHistory
    ->prepareHistoryPool(
        $preparedFixtureHistory,
        $preparedAppearanceHistory,
        5,
        5
    );


$prepared101 =
    $preparedHistory
        ->buildDefaultHistory(
            101
        );


$prepared202 =
    $preparedHistory
        ->buildDefaultHistory(
            202
        );


$prepared303 =
    $preparedHistory
        ->buildDefaultHistory(
            303
        );


$prepared101Short =
    $preparedHistory
        ->buildShortHistory(
            101
        );


/*
 * ============================================================
 * A. DEFAULT HISTORY EQUIVALENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Default History Equivalence<br>";
echo "============================================<br>";


preparedPoolTestResult(
    $prepared101 === $reference101,
    'Prepared five-row history exactly matches authoritative history for a full-window player.'
);


preparedPoolTestResult(
    $prepared202 === $reference202,
    'Prepared history exactly matches authoritative history when fewer than five rows exist.'
);


preparedPoolTestResult(
    $prepared303 === $reference303,
    'Prepared history exactly matches authoritative empty history.'
);


echo "<br>";


/*
 * ============================================================
 * B. SHORT WINDOW EQUIVALENCE
 * ============================================================
 */

echo "============================================<br>";
echo "B. Short Window Equivalence<br>";
echo "============================================<br>";


preparedPoolTestResult(
    $prepared101Short === $reference101Short,
    'Prepared five-row history preserves authoritative three-row slicing.'
);


preparedPoolTestResult(
    array_column(
        $prepared101Short[
            'fixture_window'
        ],
        'id'
    )
    ===
    [5, 6, 7],
    'Short fixture window contains the newest three prepared fixture rows in chronological order.'
);


preparedPoolTestResult(
    array_column(
        $prepared101Short[
            'appearance_window'
        ],
        'id'
    )
    ===
    [6, 7, 8],
    'Short appearance window contains the newest three prepared appearance rows in chronological order.'
);


echo "<br>";


/*
 * ============================================================
 * C. PARTICIPATION SEMANTICS
 * ============================================================
 */

echo "============================================<br>";
echo "C. Participation Semantics<br>";
echo "============================================<br>";


preparedPoolTestResult(
    (
        $prepared101[
            'zero_minute_rows'
        ]
        ?? null
    )
    ===
    (
        $reference101[
            'zero_minute_rows'
        ]
        ?? null
    ),
    'Prepared history preserves zero-minute participation evidence.'
);


preparedPoolTestResult(
    (
        $prepared101[
            'participation_rate'
        ]
        ?? null
    )
    ===
    (
        $reference101[
            'participation_rate'
        ]
        ?? null
    ),
    'Prepared history preserves participation-rate calculation.'
);


preparedPoolTestResult(
    (
        $prepared101[
            'fixture_minutes'
        ]
        ?? null
    )
    ===
    (
        $reference101[
            'fixture_minutes'
        ]
        ?? null
    ),
    'Prepared history preserves fixture-minute totals.'
);


preparedPoolTestResult(
    (
        $prepared101[
            'appearance_minutes'
        ]
        ?? null
    )
    ===
    (
        $reference101[
            'appearance_minutes'
        ]
        ?? null
    ),
    'Prepared history preserves appearance-minute totals.'
);


echo "<br>";


/*
 * ============================================================
 * D. NO PER-PLAYER FALLBACK QUERIES
 * ============================================================
 */

echo "============================================<br>";
echo "D. Prepared Pool Query Boundary<br>";
echo "============================================<br>";


preparedPoolTestResult(
    $preparedRepository
        ->fixtureQueries
    ===
    0,
    'Prepared fixture histories require no per-player repository retrieval.'
);


preparedPoolTestResult(
    $preparedRepository
        ->appearanceQueries
    ===
    0,
    'Prepared appearance histories require no per-player repository retrieval.'
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";

echo "Assertions passed: "
    . $passed
    . "<br>";

echo "Assertions failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TEST FAILED ❌";
}