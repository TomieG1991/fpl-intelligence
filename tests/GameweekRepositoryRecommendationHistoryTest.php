<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * GAMEWEEK REPOSITORY RECOMMENDATION HISTORY TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * Recommendation history must identify the gameweek whose
 * deadline is still ahead of the recommendation generation
 * time.
 *
 * This must not depend on FPL's is_current / is_next flags.
 *
 * The target gameweek is:
 *
 *     the gameweek with the earliest deadline strictly after
 *     the supplied timestamp.
 *
 * This test uses existing gameweek rows only.
 *
 * It does not insert, update or delete gameweek data.
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


function recommendationGameweekAssert(
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


function recommendationGameweekSection(
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


$repository =
    new GameweekRepository(
        $db
    );


/*
 * ============================================================
 * EXISTING GAMEWEEK DATA
 * ============================================================
 */

$gameweeks =
    $repository
        ->getAll();


$gameweeksWithDeadlines =
    array_values(
        array_filter(
            $gameweeks,
            static function (
                array $gameweek
            ): bool {

                return isset(
                    $gameweek[
                        'deadline_time'
                    ]
                )
                &&
                trim(
                    (string) $gameweek[
                        'deadline_time'
                    ]
                )
                !== '';
            }
        )
    );


usort(
    $gameweeksWithDeadlines,
    static function (
        array $a,
        array $b
    ): int {

        return strcmp(
            (string) $a[
                'deadline_time'
            ],
            (string) $b[
                'deadline_time'
            ]
        );
    }
);


if (
    count(
        $gameweeksWithDeadlines
    )
    < 2
) {

    die(
        'At least two stored gameweeks with deadlines are required for this test.'
    );
}


/*
 * ============================================================
 * CONTROLLED EXISTING ROWS
 * ============================================================
 *
 * Use two consecutive rows in deadline order.
 */

$firstGameweek =
    $gameweeksWithDeadlines[
        0
    ];


$secondGameweek =
    $gameweeksWithDeadlines[
        1
    ];


$firstDeadline =
    new DateTimeImmutable(
        (string) $firstGameweek[
            'deadline_time'
        ]
    );


$secondDeadline =
    new DateTimeImmutable(
        (string) $secondGameweek[
            'deadline_time'
        ]
    );


$beforeFirstDeadline =
    $firstDeadline
        ->modify(
            '-1 second'
        )
        ->format(
            'Y-m-d H:i:s'
        );


$exactFirstDeadline =
    $firstDeadline
        ->format(
            'Y-m-d H:i:s'
        );


$afterFirstDeadline =
    $firstDeadline
        ->modify(
            '+1 second'
        )
        ->format(
            'Y-m-d H:i:s'
        );


/*
 * ============================================================
 * A. REPOSITORY CONTRACT
 * ============================================================
 */

recommendationGameweekSection(
    'A. Recommendation Deadline Repository Contract'
);


$methodExists =
    method_exists(
        $repository,
        'getNextDeadlineAfter'
    );


recommendationGameweekAssert(
    $methodExists,
    'GameweekRepository exposes getNextDeadlineAfter().'
);


if (
    !$methodExists
) {

    echo "<br>";
    echo "<strong>EXPECTED RED: recommendation deadline lookup does not exist yet.</strong><br>";

    echo "<br>";
    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    exit;
}


/*
 * ============================================================
 * B. BEFORE FIRST DEADLINE
 * ============================================================
 */

recommendationGameweekSection(
    'B. First Future Deadline'
);


$resultBeforeFirst =
    $repository
        ->getNextDeadlineAfter(
            $beforeFirstDeadline
        );


recommendationGameweekAssert(
    is_array(
        $resultBeforeFirst
    ),
    'A future gameweek is returned when a later deadline exists.'
);


recommendationGameweekAssert(
    (
        (int) (
            $resultBeforeFirst[
                'id'
            ]
            ?? 0
        )
    )
    ===
    (int) $firstGameweek[
        'id'
    ],
    'The earliest gameweek strictly after the timestamp is returned.'
);


/*
 * ============================================================
 * C. LOCAL GAMEWEEK ID
 * ============================================================
 */

recommendationGameweekSection(
    'C. Local Gameweek Identity'
);


recommendationGameweekAssert(
    (
        (int) $resultBeforeFirst[
            'id'
        ]
    )
    ===
    (int) $firstGameweek[
        'id'
    ],
    'Local gameweeks.id is preserved.'
);


/*
 * ============================================================
 * D. OFFICIAL FPL GAMEWEEK ID
 * ============================================================
 */

recommendationGameweekSection(
    'D. Official FPL Gameweek Identity'
);


recommendationGameweekAssert(
    (
        (int) $resultBeforeFirst[
            'fpl_gameweek_id'
        ]
    )
    ===
    (int) $firstGameweek[
        'fpl_gameweek_id'
    ],
    'Official FPL gameweek ID is preserved.'
);


/*
 * ============================================================
 * E. DEADLINE PRESERVED
 * ============================================================
 */

recommendationGameweekSection(
    'E. Stored Deadline'
);


recommendationGameweekAssert(
    (
        (string) $resultBeforeFirst[
            'deadline_time'
        ]
    )
    ===
    (string) $firstGameweek[
        'deadline_time'
    ],
    'Stored gameweek deadline is returned unchanged.'
);


/*
 * ============================================================
 * F. EXACT DEADLINE IS NOT FUTURE
 * ============================================================
 */

recommendationGameweekSection(
    'F. Exact Deadline Boundary'
);


$resultAtFirstDeadline =
    $repository
        ->getNextDeadlineAfter(
            $exactFirstDeadline
        );


recommendationGameweekAssert(
    is_array(
        $resultAtFirstDeadline
    ),
    'A later gameweek is returned at the exact earlier deadline.'
);


recommendationGameweekAssert(
    (
        (int) (
            $resultAtFirstDeadline[
                'id'
            ]
            ?? 0
        )
    )
    ===
    (int) $secondGameweek[
        'id'
    ],
    'Gameweek whose deadline equals the timestamp is excluded.'
);


/*
 * ============================================================
 * G. PASSED DEADLINE IS SKIPPED
 * ============================================================
 */

recommendationGameweekSection(
    'G. Passed Deadline'
);


$resultAfterFirst =
    $repository
        ->getNextDeadlineAfter(
            $afterFirstDeadline
        );


recommendationGameweekAssert(
    is_array(
        $resultAfterFirst
    ),
    'A later gameweek remains available after an earlier deadline passes.'
);


recommendationGameweekAssert(
    (
        (int) (
            $resultAfterFirst[
                'id'
            ]
            ?? 0
        )
    )
    ===
    (int) $secondGameweek[
        'id'
    ],
    'Already-passed gameweek is skipped.'
);


/*
 * ============================================================
 * H. EARLIEST FUTURE DEADLINE WINS
 * ============================================================
 */

recommendationGameweekSection(
    'H. Deadline Ordering'
);


recommendationGameweekAssert(
    (
        (string) $resultAfterFirst[
            'deadline_time'
        ]
    )
    ===
    (string) $secondGameweek[
        'deadline_time'
    ],
    'Earliest remaining future deadline is selected.'
);


/*
 * ============================================================
 * I. NO FUTURE GAMEWEEK
 * ============================================================
 */

recommendationGameweekSection(
    'I. No Future Deadline'
);


$lastGameweek =
    $gameweeksWithDeadlines[
        count(
            $gameweeksWithDeadlines
        )
        -
        1
    ];


$lastDeadline =
    new DateTimeImmutable(
        (string) $lastGameweek[
            'deadline_time'
        ]
    );


$afterLastDeadline =
    $lastDeadline
        ->modify(
            '+1 second'
        )
        ->format(
            'Y-m-d H:i:s'
        );


$resultAfterLast =
    $repository
        ->getNextDeadlineAfter(
            $afterLastDeadline
        );


recommendationGameweekAssert(
    $resultAfterLast === null,
    'Null is returned when no future gameweek deadline exists.'
);


/*
 * ============================================================
 * J. LOOKUP DOES NOT DEPEND ON CURRENT/NEXT FLAGS
 * ============================================================
 *
 * We deliberately use the earliest stored gameweeks rather than
 * whichever rows FPL currently marks current or next.
 */

recommendationGameweekSection(
    'J. Historical Flag Independence'
);


recommendationGameweekAssert(
    (
        (int) (
            $resultBeforeFirst[
                'id'
            ]
            ?? 0
        )
    )
    ===
    (int) $firstGameweek[
        'id'
    ],
    'Deadline lookup works independently of current/next event flags.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

recommendationGameweekSection(
    'Gameweek Repository Recommendation History Test Summary'
);


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if (
    $failed === 0
) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}