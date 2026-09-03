<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function wildcardHorizonContractCheck(
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
    'Wildcard Horizon Intelligence Service Contract Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * Scenario A: Class contract
 * ============================================================
 */

echo
    '<br>';

echo
    '============================================<br>';

echo
    'Scenario A: Wildcard Horizon service contract<br>';

echo
    '============================================<br>';


wildcardHorizonContractCheck(
    'Wildcard Horizon service class exists',
    class_exists(
        'WildcardHorizonIntelligenceService'
    )
);


if (
    class_exists(
        'WildcardHorizonIntelligenceService'
    )
) {

    $reflection =
        new ReflectionClass(
            'WildcardHorizonIntelligenceService'
        );


    wildcardHorizonContractCheck(
        'Wildcard Horizon service exposes a public constructor',
        $reflection->hasMethod(
            '__construct'
        )
        &&
        $reflection->getMethod(
            '__construct'
        )->isPublic()
    );


    wildcardHorizonContractCheck(
        'Wildcard Horizon service exposes build method',
        $reflection->hasMethod(
            'build'
        )
    );


    if (
        $reflection->hasMethod(
            'build'
        )
    ) {

        $buildMethod =
            $reflection->getMethod(
                'build'
            );


        wildcardHorizonContractCheck(
            'Build method is public',
            $buildMethod->isPublic()
        );


        wildcardHorizonContractCheck(
            'Build method accepts three parameters',
            $buildMethod->getNumberOfParameters()
            ===
            3
        );


        $parameters =
            $buildMethod->getParameters();


        wildcardHorizonContractCheck(
            'First build parameter is player pool',
            isset(
                $parameters[0]
            )
            &&
            $parameters[0]->getName()
            ===
            'players'
        );


        wildcardHorizonContractCheck(
            'Second build parameter is budget',
            isset(
                $parameters[1]
            )
            &&
            $parameters[1]->getName()
            ===
            'budget'
        );


        wildcardHorizonContractCheck(
            'Third build parameter is horizon',
            isset(
                $parameters[2]
            )
            &&
            $parameters[2]->getName()
            ===
            'horizon'
        );
    }

} else {

    wildcardHorizonContractCheck(
        'Wildcard Horizon service exposes a public constructor',
        false
    );


    wildcardHorizonContractCheck(
        'Wildcard Horizon service exposes build method',
        false
    );


    wildcardHorizonContractCheck(
        'Build method is public',
        false
    );


    wildcardHorizonContractCheck(
        'Build method accepts three parameters',
        false
    );


    wildcardHorizonContractCheck(
        'First build parameter is player pool',
        false
    );


    wildcardHorizonContractCheck(
        'Second build parameter is budget',
        false
    );


    wildcardHorizonContractCheck(
        'Third build parameter is horizon',
        false
    );
}


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