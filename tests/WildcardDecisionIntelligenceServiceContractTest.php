<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardDecisionContractCheck(
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
    'Wildcard Decision Intelligence Service Contract Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * CLASS CONTRACT
 * ============================================================
 */

wildcardDecisionContractCheck(
    'WildcardDecisionIntelligenceService class exists',
    class_exists(
        'WildcardDecisionIntelligenceService'
    )
);


if (
    class_exists(
        'WildcardDecisionIntelligenceService'
    )
) {

    $reflection =
        new ReflectionClass(
            'WildcardDecisionIntelligenceService'
        );


    wildcardDecisionContractCheck(
        'WildcardDecisionIntelligenceService is instantiable',
        $reflection
            ->isInstantiable()
    );


    /*
     * ============================================================
     * CONSTRUCTOR CONTRACT
     * ============================================================
     */

    $constructor =
        $reflection
            ->getConstructor();


    wildcardDecisionContractCheck(
        'Constructor exists',
        $constructor
        instanceof
        ReflectionMethod
    );


    if (
        $constructor
        instanceof
        ReflectionMethod
    ) {

        $constructorParameters =
            $constructor
                ->getParameters();


        wildcardDecisionContractCheck(
            'Constructor has three dependencies',
            count(
                $constructorParameters
            )
            ===
            3
        );


        $expectedConstructorTypes = [

            'SquadHorizonIntelligenceService',

            'WildcardHorizonIntelligenceService',

            'WildcardTimingIntelligenceService'
        ];


        foreach (
            $expectedConstructorTypes
            as $index => $expectedType
        ) {

            $parameter =
                $constructorParameters[
                    $index
                ]
                ??
                null;


            $parameterType =
                $parameter
                instanceof
                ReflectionParameter
                &&
                $parameter
                    ->getType()
                instanceof
                ReflectionNamedType
                    ? $parameter
                        ->getType()
                        ->getName()
                    : null;


            wildcardDecisionContractCheck(
                'Constructor dependency '
                    . (
                        $index
                        +
                        1
                    )
                    . ' is '
                    . $expectedType,
                $parameterType
                ===
                $expectedType
            );
        }
    }


    /*
     * ============================================================
     * BUILD METHOD CONTRACT
     * ============================================================
     */

    wildcardDecisionContractCheck(
        'Public build method exists',
        $reflection
            ->hasMethod(
                'build'
            )
    );


    if (
        $reflection
            ->hasMethod(
                'build'
            )
    ) {

        $buildMethod =
            $reflection
                ->getMethod(
                    'build'
                );


        wildcardDecisionContractCheck(
            'Build method is public',
            $buildMethod
                ->isPublic()
        );


        $buildParameters =
            $buildMethod
                ->getParameters();


        wildcardDecisionContractCheck(
            'Build method has four parameters',
            count(
                $buildParameters
            )
            ===
            4
        );


        $expectedBuildParameterNames = [

            'importedSquad',

            'players',

            'budget',

            'horizon'
        ];


        foreach (
            $expectedBuildParameterNames
            as $index => $expectedName
        ) {

            wildcardDecisionContractCheck(
                'Build parameter '
                    . (
                        $index
                        +
                        1
                    )
                    . ' is '
                    . $expectedName,
                (
                    $buildParameters[
                        $index
                    ]
                    ??
                    null
                )
                instanceof
                ReflectionParameter
                &&
                $buildParameters[
                    $index
                ]
                    ->getName()
                ===
                $expectedName
            );
        }


        $returnType =
            $buildMethod
                ->getReturnType();


        wildcardDecisionContractCheck(
            'Build method returns array',
            $returnType
            instanceof
            ReflectionNamedType
            &&
            $returnType
                ->getName()
            ===
            'array'
        );
    }
}


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