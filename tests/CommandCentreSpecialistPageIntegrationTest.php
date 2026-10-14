<?php

echo "============================================<br>";
echo "Command Centre Specialist Page Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreSpecialistCheck(
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


echo "============================================<br>";
echo "A. Manager-Aware Specialist URLs<br>";
echo "============================================<br>";


$specialistUrls = [

    '$gameweekIntelligenceUrl' =>
        'gameweek.php',

    '$transferIntelligenceUrl' =>
        'transfers.php',

    '$squadIntelligenceUrl' =>
        'squad.php',

    '$chipIntelligenceUrl' =>
        'chips.php',

    '$dataHealthUrl' =>
        'health.php',

    '$fixtureIntelligenceUrl' =>
        'fixtures.php',

    '$playerIntelligenceUrl' =>
        'players.php'
];


foreach (
    $specialistUrls
    as $variable => $page
) {

    commandCentreSpecialistCheck(
        $page
        . ' uses a clean session-backed URL',
        preg_match(
            '/'
            . preg_quote(
                $variable,
                '/'
            )
            . '\s*=\s*'
            . preg_quote(
                "'"
                . $page
                . "'",
                '/'
            )
            . '\s*;/s',
            $dashboardSource
        )
        === 1
    );
}


echo "<br>";


echo "============================================<br>";
echo "B. Command Centre Drill-Downs<br>";
echo "============================================<br>";


$commandCentreLinks = [

    '$gameweekIntelligenceUrl' => [
        'View Gameweek Intelligence',
        'Review Gameweek Intelligence',
        'Explore Gameweek Opportunities'
    ],

    '$transferIntelligenceUrl' => [
        'Explore Transfer Intelligence'
    ],

    '$squadIntelligenceUrl' => [
        'Explore Squad Intelligence'
    ],

    '$chipIntelligenceUrl' => [
        'Explore Chip Intelligence'
    ],

    '$dataHealthUrl' => [
        'View Full Data Health'
    ]
];


foreach (
    $commandCentreLinks
    as $variable => $labels
) {

    foreach (
        $labels
        as $label
    ) {

        commandCentreSpecialistCheck(
            $label
            . ' uses the manager-aware specialist URL',
            preg_match(
                '/href="<\?=\s*htmlspecialchars\s*\(\s*'
                . preg_quote(
                    $variable,
                    '/'
                )
                . '[\s\S]{0,220}?'
                . 'class="button-link"[\s\S]{0,120}?'
                . preg_quote(
                    $label,
                    '/'
                )
                . '/s',
                $dashboardSource
            )
            === 1
        );
    }
}


echo "<br>";


echo "============================================<br>";
echo "C. Legacy Dashboard Gateways<br>";
echo "============================================<br>";


$legacyGatewayLinks = [

    '$fixtureIntelligenceUrl' =>
        'View Fixtures',

    '$playerIntelligenceUrl' =>
        'View Players',

    '$transferIntelligenceUrl' =>
        'View Transfers'
];


foreach (
    $legacyGatewayLinks
    as $variable => $label
) {

    commandCentreSpecialistCheck(
        $label
        . ' preserves manager context',
        preg_match(
            '/href="<\?=\s*htmlspecialchars\s*\(\s*'
            . preg_quote(
                $variable,
                '/'
            )
            . '[\s\S]{0,220}?'
            . 'class="button-link"[\s\S]{0,120}?'
            . preg_quote(
                $label,
                '/'
            )
            . '/s',
            $dashboardSource
        )
        === 1
    );
}


commandCentreSpecialistCheck(
    'Dashboard no longer hard-codes the Fixtures gateway URL',
    strpos(
        $dashboardSource,
        'href="fixtures.php"'
    )
    === false
);


commandCentreSpecialistCheck(
    'Dashboard no longer hard-codes the Players gateway URL',
    strpos(
        $dashboardSource,
        'href="players.php"'
    )
    === false
);


commandCentreSpecialistCheck(
    'Dashboard no longer hard-codes the Transfers gateway URL',
    strpos(
        $dashboardSource,
        'href="transfers.php"'
    )
    === false
);


echo "<br>";


echo "============================================<br>";
echo "D. Integration Boundary<br>";
echo "============================================<br>";


commandCentreSpecialistCheck(
    'Dashboard does not propagate manager context through specialist URLs',
    strpos(
        $dashboardSource,
        '$managerContextService->buildUrl('
    )
    === false
);


commandCentreSpecialistCheck(
    'Dashboard does not manually concatenate entry_id into specialist URLs',
    preg_match(
        '/(?:gameweek|transfers|squad|chips|health|fixtures|players)\.php'
        . '\?entry_id=/',
        $dashboardSource
    )
    === 0
);


commandCentreSpecialistCheck(
    'Dashboard does not duplicate query-string construction for manager context',
    strpos(
        $dashboardSource,
        "'?entry_id='"
    )
    === false
    &&
    strpos(
        $dashboardSource,
        '"?entry_id="'
    )
    === false
);


echo "<br>";


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
