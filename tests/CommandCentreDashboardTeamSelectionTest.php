<?php

echo "============================================<br>";
echo "Command Centre Dashboard Team Selection Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreTeamSelectionCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

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


$dashboardPath =
    __DIR__
    . '/../public/index.php';


$dashboardSource =
    is_file(
        $dashboardPath
    )
        ? file_get_contents(
            $dashboardPath
        )
        : false;


$dashboardSource =
    is_string(
        $dashboardSource
    )
        ? $dashboardSource
        : '';


/*
 * ============================================================
 * A. AUTHORITATIVE COMMAND CENTRE EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Command Centre Evidence<br>";
echo "============================================<br>";


commandCentreTeamSelectionCheck(
    'Dashboard consumes Command Centre Starting XI evidence',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]starting_xi[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreTeamSelectionCheck(
    'Dashboard consumes Command Centre captaincy evidence',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]captaincy[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreTeamSelectionCheck(
    'Dashboard does not directly calculate the Starting XI',
    strpos(
        $dashboardSource,
        '->getGameweekStartingXI('
    ) === false
);


commandCentreTeamSelectionCheck(
    'Dashboard does not directly calculate captain recommendations',
    strpos(
        $dashboardSource,
        '->getCaptainRecommendations('
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * B. STARTING XI PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Starting XI Presentation<br>";
echo "============================================<br>";


commandCentreTeamSelectionCheck(
    'Command Centre presents Starting XI & Bench',
    strpos(
        $dashboardSource,
        'Starting XI &amp; Bench'
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Starting XI presentation consumes the authoritative formation',
    strpos(
        $dashboardSource,
        "'formation'"
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Starting XI presentation consumes authoritative starters',
    strpos(
        $dashboardSource,
        "'starting_xi'"
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Starting XI presentation consumes authoritative bench evidence',
    strpos(
        $dashboardSource,
        "'bench'"
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Starting XI presentation exposes player names',
    strpos(
        $dashboardSource,
        "'name'"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * C. CAPTAINCY PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "C. Captaincy Presentation<br>";
echo "============================================<br>";


commandCentreTeamSelectionCheck(
    'Command Centre presents Captaincy',
    strpos(
        $dashboardSource,
        '>Captaincy<'
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Captaincy presentation consumes captain evidence',
    strpos(
        $dashboardSource,
        "'captain'"
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Captaincy presentation consumes vice-captain evidence',
    strpos(
        $dashboardSource,
        "'vice_captain'"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * D. FAILURE STATES
 * ============================================================
 */

echo "============================================<br>";
echo "D. Failure States<br>";
echo "============================================<br>";


commandCentreTeamSelectionCheck(
    'Starting XI has an unavailable state',
    strpos(
        $dashboardSource,
        'Starting XI unavailable'
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Captaincy has an unavailable state',
    strpos(
        $dashboardSource,
        'Captaincy unavailable'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * E. SPECIALIST DRILL-DOWN
 * ============================================================
 */

echo "============================================<br>";
echo "E. Specialist Drill-down<br>";
echo "============================================<br>";


commandCentreTeamSelectionCheck(
    'Team selection links to full Gameweek Intelligence',
    substr_count(
        $dashboardSource,
        'View Gameweek Intelligence'
    )
    >= 2
);


commandCentreTeamSelectionCheck(
    'Team selection drill-down uses the manager-aware Gameweek URL',
    strpos(
        $dashboardSource,
        '$gameweekIntelligenceUrl'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * F. ACCESSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "F. Accessibility<br>";
echo "============================================<br>";


commandCentreTeamSelectionCheck(
    'Starting XI section has a labelled heading',
    strpos(
        $dashboardSource,
        'id="command-centre-team-selection-title"'
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Starting XI section references its heading',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-team-selection-title"'
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Captaincy section has a labelled heading',
    strpos(
        $dashboardSource,
        'id="command-centre-captaincy-title"'
    ) !== false
);


commandCentreTeamSelectionCheck(
    'Captaincy section references its heading',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-captaincy-title"'
    ) !== false
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

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}