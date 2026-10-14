<?php

echo "============================================<br>";
echo "Command Centre Dashboard Squad Horizon Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreSquadHorizonCheck(
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
 * A. AUTHORITATIVE SQUAD HORIZON EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Squad Horizon Evidence<br>";
echo "============================================<br>";


commandCentreSquadHorizonCheck(
    'Dashboard consumes Command Centre squad horizon evidence',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]squad_horizon[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreSquadHorizonCheck(
    'Dashboard does not directly build imported squad horizon intelligence',
    strpos(
        $dashboardSource,
        '->buildForImportedSquad('
    ) === false
);


commandCentreSquadHorizonCheck(
    'Dashboard does not directly build resolved squad horizon intelligence',
    strpos(
        $dashboardSource,
        '->buildForResolvedSquad('
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * B. MULTI-GAMEWEEK PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Multi-Gameweek Presentation<br>";
echo "============================================<br>";


commandCentreSquadHorizonCheck(
    'Command Centre presents Multi-Gameweek Squad Outlook',
    strpos(
        $dashboardSource,
        '>Multi-Gameweek Squad Outlook<'
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad outlook consumes the service horizon_result envelope',
    strpos(
        $dashboardSource,
        "'horizon_result'"
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad outlook consumes authoritative horizon length',
    strpos(
        $dashboardSource,
        "'horizon'"
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad outlook consumes authoritative Projection Confidence',
    strpos(
        $dashboardSource,
        "'projection_confidence'"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * C. HORIZON DETAIL
 * ============================================================
 */

echo "============================================<br>";
echo "C. Horizon Detail<br>";
echo "============================================<br>";


commandCentreSquadHorizonCheck(
    'Squad outlook consumes authoritative gameweek horizon evidence',
    strpos(
        $dashboardSource,
        "'gameweeks'"
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad outlook consumes authoritative Starting XI projected points',
    strpos(
        $dashboardSource,
        "'starting_xi_projected_points'"
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad outlook consumes authoritative structural weakness evidence',
    strpos(
        $dashboardSource,
        "'structural_weakness'"
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad outlook exposes upcoming gameweek labels',
    strpos(
        $dashboardSource,
        'Gameweek'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * D. FAILURE STATE
 * ============================================================
 */

echo "============================================<br>";
echo "D. Failure State<br>";
echo "============================================<br>";


commandCentreSquadHorizonCheck(
    'Squad outlook has an unavailable state',
    strpos(
        $dashboardSource,
        'Squad outlook unavailable'
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


commandCentreSquadHorizonCheck(
    'Squad outlook links to specialist Squad Intelligence',
    strpos(
        $dashboardSource,
        'Explore Squad Intelligence'
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad drill-down uses the shared specialist URL variable',
    strpos(
        $dashboardSource,
        '$squadIntelligenceUrl'
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad specialist URL uses the clean session-based destination',
    preg_match(
        '/\$squadIntelligenceUrl\s*=\s*[\'"]squad\.php[\'"]\s*;/s',
        $dashboardSource
    ) === 1
);


commandCentreSquadHorizonCheck(
    'Squad specialist URL does not expose manager ID in the link',
    preg_match(
        '/\$squadIntelligenceUrl\s*=\s*[\'"][^\'"]*entry_id=/s',
        $dashboardSource
    ) !== 1
    &&
    preg_match(
        '/\$squadIntelligenceUrl\s*=\s*\$managerContextService\s*->\s*buildUrl\s*\(/s',
        $dashboardSource
    ) !== 1
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


commandCentreSquadHorizonCheck(
    'Squad outlook section has a labelled heading',
    strpos(
        $dashboardSource,
        'id="command-centre-squad-horizon-title"'
    ) !== false
);


commandCentreSquadHorizonCheck(
    'Squad outlook section references its heading',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-squad-horizon-title"'
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
