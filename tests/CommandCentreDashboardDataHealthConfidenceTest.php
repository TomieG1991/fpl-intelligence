<?php

echo "============================================<br>";
echo "Command Centre Dashboard Data Health & Confidence Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreHealthCheck(
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
 * A. AUTHORITATIVE DATA HEALTH
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Data Health<br>";
echo "============================================<br>";


commandCentreHealthCheck(
    'Command Centre consumes its authoritative data-health envelope',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]data_health[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreHealthCheck(
    'Command Centre data health consumes bootstrap evidence',
    strpos(
        $dashboardSource,
        "'bootstrap'"
    ) !== false
);


commandCentreHealthCheck(
    'Command Centre data health consumes fixture evidence',
    strpos(
        $dashboardSource,
        "'fixtures'"
    ) !== false
);


commandCentreHealthCheck(
    'Command Centre data health consumes player fixture-history evidence',
    strpos(
        $dashboardSource,
        "'player_fixture_history'"
    ) !== false
);


commandCentreHealthCheck(
    'Command Centre data health consumes authoritative status',
    strpos(
        $dashboardSource,
        "'status'"
    ) !== false
);


commandCentreHealthCheck(
    'Command Centre data health consumes authoritative reason',
    strpos(
        $dashboardSource,
        "'reason'"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * B. CONFIDENCE EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "B. Confidence Evidence<br>";
echo "============================================<br>";


commandCentreHealthCheck(
    'Command Centre reuses authoritative Squad Horizon projection confidence',
    strpos(
        $dashboardSource,
        "'projection_confidence'"
    ) !== false
);


commandCentreHealthCheck(
    'Dashboard uses the established Projection Confidence terminology',
    strpos(
        $dashboardSource,
        'Projection Confidence'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * C. PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "C. Data Health & Confidence Presentation<br>";
echo "============================================<br>";


commandCentreHealthCheck(
    'Command Centre presents Data Health & Confidence',
    strpos(
        $dashboardSource,
        '>Data Health &amp; Confidence<'
    ) !== false
);


commandCentreHealthCheck(
    'Command Centre labels Bootstrap health',
    strpos(
        $dashboardSource,
        "'Bootstrap'"
    ) !== false
);


commandCentreHealthCheck(
    'Command Centre labels Fixtures health',
    strpos(
        $dashboardSource,
        "'Fixtures'"
    ) !== false
);


commandCentreHealthCheck(
    'Command Centre labels Player Fixture History health',
    strpos(
        $dashboardSource,
        "'Player Fixture History'"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * D. NO NEW HEALTH OR CONFIDENCE MODEL
 * ============================================================
 */

echo "============================================<br>";
echo "D. No New Health or Confidence Model<br>";
echo "============================================<br>";


commandCentreHealthCheck(
    'Command Centre introduces no aggregate health score',
    strpos(
        $dashboardSource,
        'data_health_score'
    ) === false
);


commandCentreHealthCheck(
    'Command Centre introduces no new confidence threshold',
    strpos(
        $dashboardSource,
        'COMMAND_CENTRE_CONFIDENCE'
    ) === false
);


commandCentreHealthCheck(
    'Dashboard does not directly recalculate Update Health inside presentation',
    substr_count(
        $dashboardSource,
        '->evaluate('
    ) === 3
);


echo "<br>";


/*
 * ============================================================
 * E. UNAVAILABLE STATE
 * ============================================================
 */

echo "============================================<br>";
echo "E. Unavailable State<br>";
echo "============================================<br>";


commandCentreHealthCheck(
    'Data Health & Confidence has an unavailable state',
    strpos(
        $dashboardSource,
        'Data health evidence unavailable'
    ) !== false
);


commandCentreHealthCheck(
    'Projection Confidence has an unavailable state',
    strpos(
        $dashboardSource,
        'Projection Confidence unavailable'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * F. SPECIALIST DRILL-DOWN
 * ============================================================
 */

echo "============================================<br>";
echo "F. Specialist Drill-down<br>";
echo "============================================<br>";


commandCentreHealthCheck(
    'Command Centre links to full Data Health detail',
    strpos(
        $dashboardSource,
        'View Full Data Health'
    ) !== false
);


commandCentreHealthCheck(
    'Data Health drill-down uses the shared specialist URL variable',
    strpos(
        $dashboardSource,
        '$dataHealthUrl'
    ) !== false
);


commandCentreHealthCheck(
    'Data Health specialist URL uses the clean session-based destination',
    preg_match(
        '/\$dataHealthUrl\s*=\s*[\'"]health\.php[\'"]\s*;/s',
        $dashboardSource
    ) === 1
);


commandCentreHealthCheck(
    'Data Health specialist URL does not expose manager ID in the link',
    preg_match(
        '/\$dataHealthUrl\s*=\s*[\'"][^\'"]*entry_id=/s',
        $dashboardSource
    ) !== 1
    &&
    preg_match(
        '/\$dataHealthUrl\s*=\s*\$managerContextService\s*->\s*buildUrl\s*\(/s',
        $dashboardSource
    ) !== 1
);


echo "<br>";


/*
 * ============================================================
 * G. ACCESSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "G. Accessibility<br>";
echo "============================================<br>";


commandCentreHealthCheck(
    'Data Health & Confidence section has a labelled heading',
    strpos(
        $dashboardSource,
        'id="command-centre-data-health-title"'
    ) !== false
);


commandCentreHealthCheck(
    'Data Health & Confidence section references its heading',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-data-health-title"'
    ) !== false
);


commandCentreHealthCheck(
    'Data Health state is exposed as status information',
    strpos(
        $dashboardSource,
        'id="command-centre-data-health-status"'
    ) !== false
    &&
    strpos(
        $dashboardSource,
        'role="status"'
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
