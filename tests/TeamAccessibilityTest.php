<?php

/*
 * ============================================================
 * TEAM PROFILE ACCESSIBILITY TEST
 * ============================================================
 */

echo "============================================<br>";
echo "Team Profile Accessibility Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function teamAccessibilityCheck(
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


$teamPath =
    __DIR__
    . '/../public/team.php';


$teamSource =
    is_file(
        $teamPath
    )
        ? file_get_contents(
            $teamPath
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


teamAccessibilityCheck(
    'Team profile page source is available.',
    is_string(
        $teamSource
    )
);


/*
 * ============================================================
 * SCENARIO B
 * PLAYER TABLE COLUMN HEADERS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Player Table Column Headers<br>";
echo "============================================<br>";


$playerTableHeaders =
    [];


if (
    is_string(
        $teamSource
    )
) {

    preg_match(
        '/<table\s+class=["\']team-profile-player-table["\'][^>]*>(.*?)<\/table>/is',
        $teamSource,
        $playerTableMatch
    );


    if (
        isset(
            $playerTableMatch[1]
        )
    ) {

        preg_match_all(
            '/<th\b[^>]*>/i',
            $playerTableMatch[1],
            $playerTableHeaders
        );
    }
}


$scopedPlayerTableHeaders =
    array_filter(
        $playerTableHeaders[0]
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


teamAccessibilityCheck(
    'Team profile player table contains the expected 9 column headers.',
    count(
        $playerTableHeaders[0]
        ?? []
    ) === 9
);


teamAccessibilityCheck(
    'Every team profile player table header is identified as a column header.',
    count(
        $playerTableHeaders[0]
        ?? []
    ) === 9
    &&
    count(
        $scopedPlayerTableHeaders
    ) === 9
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Team Profile Accessibility Test Summary<br>";
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