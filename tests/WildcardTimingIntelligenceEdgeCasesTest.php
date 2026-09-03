<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingEdgeCheck(
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


function wildcardTimingThrowsInvalidArgument(
    callable $callback
): bool {

    try {

        $callback();

    } catch (
        InvalidArgumentException $exception
    ) {

        return true;

    } catch (
        Throwable $exception
    ) {

        return false;
    }


    return false;
}


echo
    '============================================<br>';

echo
    'Wildcard Timing Intelligence Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Zero projections
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Zero projections<br>';

echo
    '============================================<br>';


$zeroResult =
    $intelligence
        ->analyseImmediateValue(
            0.0,
            0.0
        );


wildcardTimingEdgeCheck(
    'Zero current projection is preserved',
    (
        $zeroResult[
            'current_squad_projected_points'
        ]
        ??
        null
    )
        ===
        0.0
);


wildcardTimingEdgeCheck(
    'Zero Wildcard projection is preserved',
    (
        $zeroResult[
            'wildcard_squad_projected_points'
        ]
        ??
        null
    )
        ===
        0.0
);


wildcardTimingEdgeCheck(
    'Zero versus zero produces zero gain',
    (
        $zeroResult[
            'projected_points_gain'
        ]
        ??
        null
    )
        ===
        0.0
);


wildcardTimingEdgeCheck(
    'Zero versus zero is not an improvement',
    (
        $zeroResult[
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
 * Scenario B: Small positive difference
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Small positive difference<br>';

echo
    '============================================<br>';


$smallGain =
    $intelligence
        ->analyseImmediateValue(
            60.0,
            60.1
        );


wildcardTimingEdgeCheck(
    'Small positive gain is preserved',
    abs(
        (
            $smallGain[
                'projected_points_gain'
            ]
            ??
            0
        )
        -
        0.1
    ) < 0.0001
);


wildcardTimingEdgeCheck(
    'Any positive gain counts as improvement at measurement level',
    (
        $smallGain[
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
 * Scenario C: Small negative difference
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Small negative difference<br>';

echo
    '============================================<br>';


$smallLoss =
    $intelligence
        ->analyseImmediateValue(
            60.1,
            60.0
        );


wildcardTimingEdgeCheck(
    'Small negative gain is preserved',
    abs(
        (
            $smallLoss[
                'projected_points_gain'
            ]
            ??
            0
        )
        -
        (-0.1)
    ) < 0.0001
);


wildcardTimingEdgeCheck(
    'Small negative gain is not an improvement',
    (
        $smallLoss[
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
 * Scenario D: Invalid negative projected totals
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Invalid negative projected totals<br>';

echo
    '============================================<br>';


wildcardTimingEdgeCheck(
    'Negative current squad projection is rejected',
    wildcardTimingThrowsInvalidArgument(
        function () use (
            $intelligence
        ): void {

            $intelligence
                ->analyseImmediateValue(
                    -0.1,
                    50.0
                );
        }
    )
);


wildcardTimingEdgeCheck(
    'Negative Wildcard squad projection is rejected',
    wildcardTimingThrowsInvalidArgument(
        function () use (
            $intelligence
        ): void {

            $intelligence
                ->analyseImmediateValue(
                    50.0,
                    -0.1
                );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Deterministic repeatability
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Deterministic repeatability<br>';

echo
    '============================================<br>';


$first =
    $intelligence
        ->analyseImmediateValue(
            63.25,
            70.75
        );


$second =
    $intelligence
        ->analyseImmediateValue(
            63.25,
            70.75
        );


wildcardTimingEdgeCheck(
    'Repeated analysis returns identical output',
    $first
        ===
        $second
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