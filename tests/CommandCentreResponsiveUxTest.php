<?php

echo "============================================<br>";
echo "Command Centre Responsive UX Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreResponsiveCheck(
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


$cssPath =
    __DIR__
    . '/../public/assets/css/app.css';


$dashboardSource =
    is_file(
        $dashboardPath
    )
        ? file_get_contents(
            $dashboardPath
        )
        : false;


$cssSource =
    is_file(
        $cssPath
    )
        ? file_get_contents(
            $cssPath
        )
        : false;


$dashboardSource =
    is_string(
        $dashboardSource
    )
        ? $dashboardSource
        : '';


$cssSource =
    is_string(
        $cssSource
    )
        ? $cssSource
        : '';


echo "============================================<br>";
echo "A. Command Centre Styling Boundary<br>";
echo "============================================<br>";


commandCentreResponsiveCheck(
    'Dashboard exposes a Command Centre styling hook',
    preg_match(
        '/class="[^"]*\bcommand-centre\b[^"]*"/',
        $dashboardSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Application CSS defines the Command Centre boundary',
    preg_match(
        '/\.command-centre\s*\{/',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Application CSS styles the manager entry form',
    strpos(
        $cssSource,
        '.command-centre-entry-form'
    ) !== false
);


echo "<br>";


echo "============================================<br>";
echo "B. Responsive Grid<br>";
echo "============================================<br>";


commandCentreResponsiveCheck(
    'Command Centre has a dedicated grid rule',
    preg_match(
        '/\.command-centre\s*>\s*\.dashboard-grid\s*\{[^}]*grid-template-columns/s',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Command Centre collapses to one column on tablet/mobile',
    preg_match(
        '/@media\s*\(\s*max-width:\s*800px\s*\)[\s\S]*?'
        . '\.command-centre\s*>\s*\.dashboard-grid\s*\{[^}]*'
        . 'grid-template-columns:\s*1fr/s',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Command Centre cards can shrink without horizontal overflow',
    preg_match(
        '/\.command-centre\s+\.dashboard-card\s*\{[^}]*min-width:\s*0/s',
        $cssSource
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "C. Long Decision Content<br>";
echo "============================================<br>";


commandCentreResponsiveCheck(
    'Command Centre player names may wrap instead of being forcibly truncated',
    preg_match(
        '/\.command-centre\s+\.player-name\s*\{[^}]*white-space:\s*normal/s',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Command Centre metadata permits safe word wrapping',
    preg_match(
        '/\.command-centre\s+\.player-meta\s*\{[^}]*overflow-wrap:\s*anywhere/s',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Command Centre stat footers permit safe word wrapping',
    preg_match(
        '/\.command-centre\s+\.stat-footer\s*\{[^}]*overflow-wrap:\s*anywhere/s',
        $cssSource
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "D. Interactive Controls<br>";
echo "============================================<br>";


commandCentreResponsiveCheck(
    'Manager entry input has visible focus styling',
    preg_match(
        '/\.command-centre-entry-form\s+input:focus-visible\s*\{/',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Manager entry form stacks on small screens',
    preg_match(
        '/@media\s*\(\s*max-width:\s*560px\s*\)[\s\S]*?'
        . '\.command-centre-entry-form\s*\{[^}]*'
        . 'flex-direction:\s*column/s',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Manager entry controls expand on small screens',
    preg_match(
        '/@media\s*\(\s*max-width:\s*560px\s*\)[\s\S]*?'
        . '\.command-centre-entry-form\s+input[\s\S]*?'
        . 'width:\s*100%/s',
        $cssSource
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "E. Existing Responsive Shell<br>";
echo "============================================<br>";


commandCentreResponsiveCheck(
    'Existing 800px application breakpoint remains',
    preg_match(
        '/@media\s*\(\s*max-width:\s*800px\s*\)/',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Existing 560px application breakpoint remains',
    preg_match(
        '/@media\s*\(\s*max-width:\s*560px\s*\)/',
        $cssSource
    )
    === 1
);


commandCentreResponsiveCheck(
    'Shared button-link keyboard focus styling remains',
    preg_match(
        '/\.button-link:focus-visible\s*\{/',
        $cssSource
    )
    === 1
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
