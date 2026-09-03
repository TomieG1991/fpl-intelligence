<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function chipDecisionEdgeCheck(
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


function chipDecisionThrowsInvalidArgument(
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
    'Chip Decision Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * Scenario A: Valid recommendation vocabulary
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Valid recommendation vocabulary<br>';

echo
    '============================================<br>';


foreach (
    [
        'Use',
        'Consider',
        'Hold'
    ]
    as $recommendation
) {

    $decision =
        new ChipDecision(
            'Wildcard',
            $recommendation,
            0.75,
            'Controlled test explanation.'
        );


    chipDecisionEdgeCheck(
        $recommendation
        . ' remains a valid recommendation',
        $decision->getRecommendation()
            ===
            $recommendation
    );
}


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Unsupported recommendation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Unsupported recommendation<br>';

echo
    '============================================<br>';


chipDecisionEdgeCheck(
    'Unsupported recommendation is rejected',
    chipDecisionThrowsInvalidArgument(
        function (): void {

            new ChipDecision(
                'Wildcard',
                'Definitely Use',
                0.75,
                'Invalid recommendation test.'
            );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Confidence boundaries
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Confidence boundaries<br>';

echo
    '============================================<br>';


$zeroConfidence =
    new ChipDecision(
        'Free Hit',
        'Hold',
        0.0,
        'Minimum confidence boundary.'
    );


chipDecisionEdgeCheck(
    'Zero confidence is valid',
    $zeroConfidence->getConfidence()
        ===
        0.0
);


$fullConfidence =
    new ChipDecision(
        'Free Hit',
        'Use',
        1.0,
        'Maximum confidence boundary.'
    );


chipDecisionEdgeCheck(
    'Full confidence is valid',
    $fullConfidence->getConfidence()
        ===
        1.0
);


chipDecisionEdgeCheck(
    'Confidence below zero is rejected',
    chipDecisionThrowsInvalidArgument(
        function (): void {

            new ChipDecision(
                'Bench Boost',
                'Hold',
                -0.01,
                'Invalid confidence test.'
            );
        }
    )
);


chipDecisionEdgeCheck(
    'Confidence above one is rejected',
    chipDecisionThrowsInvalidArgument(
        function (): void {

            new ChipDecision(
                'Bench Boost',
                'Use',
                1.01,
                'Invalid confidence test.'
            );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Required text fields
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Required text fields<br>';

echo
    '============================================<br>';


chipDecisionEdgeCheck(
    'Empty chip name is rejected',
    chipDecisionThrowsInvalidArgument(
        function (): void {

            new ChipDecision(
                '',
                'Hold',
                0.50,
                'Missing chip test.'
            );
        }
    )
);


chipDecisionEdgeCheck(
    'Whitespace-only chip name is rejected',
    chipDecisionThrowsInvalidArgument(
        function (): void {

            new ChipDecision(
                '   ',
                'Hold',
                0.50,
                'Missing chip test.'
            );
        }
    )
);


chipDecisionEdgeCheck(
    'Empty explanation is rejected',
    chipDecisionThrowsInvalidArgument(
        function (): void {

            new ChipDecision(
                'Triple Captain',
                'Consider',
                0.50,
                ''
            );
        }
    )
);


chipDecisionEdgeCheck(
    'Whitespace-only explanation is rejected',
    chipDecisionThrowsInvalidArgument(
        function (): void {

            new ChipDecision(
                'Triple Captain',
                'Consider',
                0.50,
                '   '
            );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Valid text is preserved
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Valid text is preserved<br>';

echo
    '============================================<br>';


$validDecision =
    new ChipDecision(
        'Bench Boost',
        'Consider',
        0.60,
        'Projected bench value is useful.'
    );


chipDecisionEdgeCheck(
    'Valid chip text remains unchanged',
    $validDecision->getChip()
        ===
        'Bench Boost'
);


chipDecisionEdgeCheck(
    'Valid explanation remains unchanged',
    $validDecision->getExplanation()
        ===
        'Projected bench value is useful.'
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