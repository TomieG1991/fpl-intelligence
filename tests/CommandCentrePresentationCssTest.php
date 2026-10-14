<?php

echo "============================================<br>";
echo "Command Centre Presentation CSS Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function commandCentreCssCheck(
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


$cssPath =
    __DIR__
    . '/../public/assets/css/app.css';


$cssSource =
    is_file($cssPath)
        ? file_get_contents($cssPath)
        : false;


$cssSource =
    is_string($cssSource)
        ? $cssSource
        : '';


echo "============================================<br>";
echo "A. Decision Layout<br>";
echo "============================================<br>";


commandCentreCssCheck(
    'Decision row has dedicated presentation rules',
    strpos(
        $cssSource,
        '.command-centre-decision-row'
    ) !== false
);


commandCentreCssCheck(
    'Primary decision has dedicated presentation rules',
    strpos(
        $cssSource,
        '.command-centre-primary-decision'
    ) !== false
);


commandCentreCssCheck(
    'Captaincy has dedicated presentation rules',
    strpos(
        $cssSource,
        '.command-centre-captaincy'
    ) !== false
);


echo "<br>";
echo "============================================<br>";
echo "B. Team Selection<br>";
echo "============================================<br>";


commandCentreCssCheck(
    'Team selection has dedicated presentation rules',
    strpos(
        $cssSource,
        '.command-centre-team-selection'
    ) !== false
);


commandCentreCssCheck(
    'Starting XI has a dedicated team layout',
    strpos(
        $cssSource,
        '.command-centre-team-layout'
    ) !== false
);


commandCentreCssCheck(
    'Bench has a dedicated presentation row',
    strpos(
        $cssSource,
        '.command-centre-bench-row'
    ) !== false
);


echo "<br>";
echo "============================================<br>";
echo "C. Planning & Attention<br>";
echo "============================================<br>";


commandCentreCssCheck(
    'Planning row has dedicated presentation rules',
    strpos(
        $cssSource,
        '.command-centre-planning-row'
    ) !== false
);


commandCentreCssCheck(
    'Attention row has dedicated presentation rules',
    strpos(
        $cssSource,
        '.command-centre-attention-row'
    ) !== false
);


commandCentreCssCheck(
    'Data Health has dedicated full-width presentation rules',
    strpos(
        $cssSource,
        '.command-centre-data-health-card'
    ) !== false
);


echo "<br>";
echo "============================================<br>";
echo "D. Secondary Dashboard<br>";
echo "============================================<br>";


commandCentreCssCheck(
    'Legacy dashboard has dedicated secondary presentation',
    strpos(
        $cssSource,
        '.dashboard-secondary-content'
    ) !== false
);


echo "<br>";
echo "============================================<br>";
echo "E. Responsive Presentation<br>";
echo "============================================<br>";


$responsiveCommandCentrePosition =
    strpos(
        $cssSource,
        'RESPONSIVE COMMAND CENTRE PRESENTATION'
    );


commandCentreCssCheck(
    'Command Centre exposes dedicated responsive presentation rules',
    $responsiveCommandCentrePosition !== false
);


commandCentreCssCheck(
    'Responsive rules include the planning row',
    $responsiveCommandCentrePosition !== false
    &&
    strpos(
        substr(
            $cssSource,
            $responsiveCommandCentrePosition
        ),
        '.command-centre-planning-row'
    ) !== false
);


commandCentreCssCheck(
    'Responsive rules include the team layout',
    $responsiveCommandCentrePosition !== false
    &&
    strpos(
        substr(
            $cssSource,
            $responsiveCommandCentrePosition
        ),
        '.command-centre-team-layout'
    ) !== false
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