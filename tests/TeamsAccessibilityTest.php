<?php

/*
 * ============================================================
 * TEAMS ACCESSIBILITY TEST
 * ============================================================
 */

echo "============================================<br>";
echo "Teams Accessibility Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function teamsAccessibilityCheck(
    string $message,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


$teamsPath =
    __DIR__
    . '/../public/teams.php';


$teamsSource =
    is_file(
        $teamsPath
    )
        ? file_get_contents(
            $teamsPath
        )
        : false;


/*
 * ============================================================
 * SCENARIO A
 * SOURCE AVAILABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Source Availability<br>";
echo "============================================<br>";


teamsAccessibilityCheck(
    'Teams page source is available.',
    is_string(
        $teamsSource
    )
);


/*
 * ============================================================
 * SCENARIO B
 * TABLE COLUMN HEADERS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Table Column Headers<br>";
echo "============================================<br>";


$teamTableHeaders =
    [];


if (
    is_string(
        $teamsSource
    )
) {

    preg_match_all(
        '/<th\b[^>]*>/i',
        $teamsSource,
        $teamTableHeaders
    );
}


$scopedTeamTableHeaders =
    array_filter(
        $teamTableHeaders[0]
        ?? [],
        static function (
            string $header
        ): bool {

            return preg_match(
                '/\bscope=["\']col["\']/i',
                $header
            )
            === 1;
        }
    );


teamsAccessibilityCheck(
    'Team Intelligence table contains the expected 14 column headers.',
    count(
        $teamTableHeaders[0]
        ?? []
    ) === 14
);


teamsAccessibilityCheck(
    'Every Team Intelligence table header is identified as a column header.',
    count(
        $teamTableHeaders[0]
        ?? []
    ) === 14
    &&
    count(
        $scopedTeamTableHeaders
    ) === 14
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Teams Accessibility Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}