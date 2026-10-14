<?php

echo "============================================<br>";
echo "Command Centre Failure & Empty States Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreFailureCheck(
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
echo "A. Manager Context States<br>";
echo "============================================<br>";


commandCentreFailureCheck(
    'Dashboard provides a no-manager state',
    strpos(
        $dashboardSource,
        'Enter your FPL entry ID'
    ) !== false
);


commandCentreFailureCheck(
    'Dashboard provides a manager-context unavailable state',
    strpos(
        $dashboardSource,
        'Command Centre unavailable'
    ) !== false
);


commandCentreFailureCheck(
    'Manager entry field requires a positive ID',
    strpos(
        $dashboardSource,
        'min="1"'
    ) !== false
    &&
    strpos(
        $dashboardSource,
        'required'
    ) !== false
);


echo "<br>";


echo "============================================<br>";
echo "B. Decision Surface Failure States<br>";
echo "============================================<br>";


$requiredStates = [

    'Starting XI unavailable',

    'Captaincy unavailable',

    'Transfer Intelligence unavailable',

    'Squad outlook unavailable',

    'Chip Intelligence unavailable',

    'Attention evidence unavailable',

    'Opportunity evidence unavailable',

    'Data health evidence unavailable',

    'Projection Confidence unavailable'
];


foreach (
    $requiredStates
    as $requiredState
) {

    commandCentreFailureCheck(
        'Dashboard provides state: '
        . $requiredState,
        strpos(
            $dashboardSource,
            $requiredState
        ) !== false
    );
}


echo "<br>";


echo "============================================<br>";
echo "C. Legitimate Empty States<br>";
echo "============================================<br>";


commandCentreFailureCheck(
    'Transfer Intelligence distinguishes no recommendation from unavailable evidence',
    strpos(
        $dashboardSource,
        'No transfer recommendation'
    ) !== false
);


commandCentreFailureCheck(
    'Attention distinguishes a clear state from unavailable evidence',
    strpos(
        $dashboardSource,
        'No immediate attention items'
    ) !== false
);


commandCentreFailureCheck(
    'Opportunities distinguishes a clear state from unavailable evidence',
    strpos(
        $dashboardSource,
        'No immediate opportunities surfaced'
    ) !== false
);


echo "<br>";


echo "============================================<br>";
echo "D. Safe Rendering<br>";
echo "============================================<br>";


commandCentreFailureCheck(
    'Dashboard escapes manager-facing dynamic output',
    substr_count(
        $dashboardSource,
        'htmlspecialchars('
    ) >= 20
);


commandCentreFailureCheck(
    'Dashboard guards array-backed Command Centre sections',
    substr_count(
        $dashboardSource,
        'is_array('
    ) >= 10
);


commandCentreFailureCheck(
    'Dashboard guards scalar presentation values',
    substr_count(
        $dashboardSource,
        'is_scalar('
    ) >= 5
);


commandCentreFailureCheck(
    'Dashboard does not expose raw throwable messages in Command Centre presentation',
    strpos(
        $dashboardSource,
        'getMessage()'
    ) === false
);


echo "<br>";


echo "============================================<br>";
echo "E. Graceful Production Boundary<br>";
echo "============================================<br>";


commandCentreFailureCheck(
    'Command Centre production build is protected by a throwable boundary',
    preg_match(
        '/try\s*\{[\s\S]*?'
        . '\$commandCentreService\s*->\s*build\s*\([\s\S]*?'
        . '\}\s*catch\s*\(\s*Throwable\b/s',
        $dashboardSource
    )
    === 1
);


commandCentreFailureCheck(
    'Command Centre failure boundary preserves the stable default envelope',
    preg_match(
        '/[\'"]manager[\'"]\s*=>\s*null/s',
        $dashboardSource
    )
    === 1
    &&
    preg_match(
        '/[\'"]decision[\'"]\s*=>\s*null/s',
        $dashboardSource
    )
    === 1
    &&
    preg_match(
        '/[\'"]data_health[\'"]\s*=>\s*null/s',
        $dashboardSource
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "F. Accessibility of Failure Information<br>";
echo "============================================<br>";


commandCentreFailureCheck(
    'Primary Command Centre unavailable state exposes status semantics',
    preg_match(
        '/Command Centre unavailable[\s\S]{0,1200}?role="status"|'
        . 'role="status"[\s\S]{0,1200}?Command Centre unavailable/s',
        $dashboardSource
    )
    === 1
);


commandCentreFailureCheck(
    'Data Health unavailable state exposes status semantics',
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
