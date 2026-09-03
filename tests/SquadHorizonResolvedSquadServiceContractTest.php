<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed =
    0;


$failed =
    0;


function resolvedSquadServiceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


/*
 * ============================================================
 * START
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Squad Horizon Resolved Squad Service Contract Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * Scenario A: Resolved-squad entry point
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Resolved-squad entry point<br>';

echo
    '============================================<br>';


$methodExists =
    method_exists(
        'SquadHorizonIntelligenceService',
        'buildForResolvedSquad'
    );


resolvedSquadServiceCheck(
    'Service exposes buildForResolvedSquad()',
    $methodExists
);


/*
 * Stop cleanly during the RED stage.
 *
 * Calling a method that does not yet exist would produce a fatal
 * error in the browser and hide the intended test result.
 */

if (
    !$methodExists
) {

    echo
        '<br>';


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

    echo
        'RESULT: TESTS FAILED ❌<br>';


    exit;
}


/*
 * ============================================================
 * Scenario B: Public method contract
 * ============================================================
 */

echo
    '<br>';

echo
    '============================================<br>';

echo
    'Scenario B: Public method contract<br>';

echo
    '============================================<br>';


$method =
    new ReflectionMethod(
        'SquadHorizonIntelligenceService',
        'buildForResolvedSquad'
    );


resolvedSquadServiceCheck(
    'buildForResolvedSquad() is public',
    $method->isPublic()
);


$parameters =
    $method->getParameters();


resolvedSquadServiceCheck(
    'buildForResolvedSquad() has exactly two parameters',
    count(
        $parameters
    )
    ===
    2
);


resolvedSquadServiceCheck(
    'First parameter is resolvedPlayers',
    isset(
        $parameters[0]
    )
    &&
    $parameters[0]->getName()
    ===
    'resolvedPlayers'
);


resolvedSquadServiceCheck(
    'First parameter is typed array',
    isset(
        $parameters[0]
    )
    &&
    $parameters[0]->getType()
    instanceof
    ReflectionNamedType
    &&
    $parameters[0]
        ->getType()
        ->getName()
    ===
    'array'
);


resolvedSquadServiceCheck(
    'Second parameter is horizon',
    isset(
        $parameters[1]
    )
    &&
    $parameters[1]->getName()
    ===
    'horizon'
);


resolvedSquadServiceCheck(
    'Second parameter is typed int',
    isset(
        $parameters[1]
    )
    &&
    $parameters[1]->getType()
    instanceof
    ReflectionNamedType
    &&
    $parameters[1]
        ->getType()
        ->getName()
    ===
    'int'
);


resolvedSquadServiceCheck(
    'Horizon defaults to three gameweeks',
    isset(
        $parameters[1]
    )
    &&
    $parameters[1]
        ->isDefaultValueAvailable()
    &&
    $parameters[1]
        ->getDefaultValue()
    ===
    3
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '<br>';

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