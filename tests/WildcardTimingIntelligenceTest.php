<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        return;
    }


    $failed++;

    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


echo
    '============================================<br>';

echo
    'Wildcard Timing Intelligence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Wildcard improves the current squad
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Wildcard improves the current squad<br>';

echo
    '============================================<br>';


$improvement =
    $intelligence
        ->analyseImmediateValue(
            62.5,
            74.0
        );


wildcardTimingCheck(
    'Immediate analysis returns an array',
    is_array(
        $improvement
    )
);


wildcardTimingCheck(
    'Current squad projected points are preserved',
    abs(
        (
            $improvement[
                'current_squad_projected_points'
            ]
            ??
            0
        )
        -
        62.5
    ) < 0.0001
);


wildcardTimingCheck(
    'Wildcard squad projected points are preserved',
    abs(
        (
            $improvement[
                'wildcard_squad_projected_points'
            ]
            ??
            0
        )
        -
        74.0
    ) < 0.0001
);


wildcardTimingCheck(
    'Immediate improvement is calculated correctly',
    abs(
        (
            $improvement[
                'projected_points_gain'
            ]
            ??
            0
        )
        -
        11.5
    ) < 0.0001
);


wildcardTimingCheck(
    'Positive improvement is recognised',
    (
        $improvement[
            'improves_squad'
        ]
        ??
        false
    )
        === true
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Wildcard produces equal projection
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Wildcard produces equal projection<br>';

echo
    '============================================<br>';


$neutral =
    $intelligence
        ->analyseImmediateValue(
            70.0,
            70.0
        );


wildcardTimingCheck(
    'Equal squads produce zero projected gain',
    abs(
        (
            $neutral[
                'projected_points_gain'
            ]
            ??
            -999
        )
    ) < 0.0001
);


wildcardTimingCheck(
    'Equal projection is not classified as improvement',
    (
        $neutral[
            'improves_squad'
        ]
        ??
        true
    )
        === false
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Wildcard makes the squad worse
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Wildcard makes the squad worse<br>';

echo
    '============================================<br>';


$regression =
    $intelligence
        ->analyseImmediateValue(
            71.5,
            67.0
        );


wildcardTimingCheck(
    'Negative projected gain is preserved',
    abs(
        (
            $regression[
                'projected_points_gain'
            ]
            ??
            0
        )
        -
        (-4.5)
    ) < 0.0001
);


wildcardTimingCheck(
    'Negative projection is not classified as improvement',
    (
        $regression[
            'improves_squad'
        ]
        ??
        true
    )
        === false
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Result contract
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Result contract<br>';

echo
    '============================================<br>';


wildcardTimingCheck(
    'Result identifies immediate Wildcard value analysis',
    (
        $improvement[
            'analysis'
        ]
        ??
        null
    )
        ===
        'Immediate Wildcard Value'
);


wildcardTimingCheck(
    'Result exposes all required fields',
    array_key_exists(
        'current_squad_projected_points',
        $improvement
    )
    &&
    array_key_exists(
        'wildcard_squad_projected_points',
        $improvement
    )
    &&
    array_key_exists(
        'projected_points_gain',
        $improvement
    )
    &&
    array_key_exists(
        'improves_squad',
        $improvement
    )
);


echo
    '<br>';


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'TEST SUMMARY<br>';

echo
    '============================================<br>';

echo
    'Passed: '
    . $passed
    . '<br>';

echo
    'Failed: '
    . $failed
    . '<br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}