<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Defensive Contributions Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function expectedDcCheck(
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


$model =
    new ExpectedDefensiveContributions();


/*
 * ============================================================
 * SCENARIO A
 * DEFENDER CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Defender Contract<br>";
echo "============================================<br>";


$defender =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'weighted_metrics' => [

                    'cbit_per_90' =>
                        10.0
                ]
            ],
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


expectedDcCheck(
    'Defender uses CBIT',
    (
        $defender[
            'metric'
        ]
        ?? null
    )
    ===
    'cbit_per_90'
);


expectedDcCheck(
    'Defender threshold is 10',
    (
        (int) (
            $defender[
                'threshold'
            ]
            ?? 0
        )
    )
    ===
    10
);


expectedDcCheck(
    'Defender projection is Modelled',
    (
        $defender[
            'status'
        ]
        ?? null
    )
    ===
    'Modelled'
);


expectedDcCheck(
    'Defender expected points remain between zero and two',
    (
        (float) $defender[
            'expected_points'
        ]
    )
    >= 0
    &&
    (
        (float) $defender[
            'expected_points'
        ]
    )
    <= 2
);


echo "Defender Threshold Probability: "
    . number_format(
        (float) $defender[
            'threshold_probability_percent'
        ],
        2
    )
    . "%<br>";

echo "Defender Expected DC Points: "
    . number_format(
        (float) $defender[
            'expected_points'
        ],
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * MIDFIELDER CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Midfielder Contract<br>";
echo "============================================<br>";


$midfielder =
    $model
        ->calculate(
            'MID',
            90,
            [
                'weighted_metrics' => [

                    'cbirt_per_90' =>
                        12.0
                ]
            ]
        );


expectedDcCheck(
    'Midfielder uses CBIRT',
    (
        $midfielder[
            'metric'
        ]
        ?? null
    )
    ===
    'cbirt_per_90'
);


expectedDcCheck(
    'Midfielder threshold is 12',
    (
        (int) (
            $midfielder[
                'threshold'
            ]
            ?? 0
        )
    )
    ===
    12
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * FORWARD CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Forward Contract<br>";
echo "============================================<br>";


$forward =
    $model
        ->calculate(
            'FWD',
            90,
            [
                'weighted_metrics' => [

                    'cbirt_per_90' =>
                        12.0
                ]
            ]
        );


expectedDcCheck(
    'Forward uses CBIRT',
    (
        $forward[
            'metric'
        ]
        ?? null
    )
    ===
    'cbirt_per_90'
);


expectedDcCheck(
    'Forward threshold is 12',
    (
        (int) (
            $forward[
                'threshold'
            ]
            ?? 0
        )
    )
    ===
    12
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * GOALKEEPER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Goalkeeper<br>";
echo "============================================<br>";


$goalkeeper =
    $model
        ->calculate(
            'GK',
            90,
            [
                'weighted_metrics' => [

                    'cbit_per_90' =>
                        20
                ]
            ]
        );


expectedDcCheck(
    'Goalkeeper defensive contributions are Not Applicable',
    (
        $goalkeeper[
            'status'
        ]
        ?? null
    )
    ===
    'Not Applicable'
);


expectedDcCheck(
    'Goalkeeper receives zero expected defensive contribution points',
    abs(
        (float) (
            $goalkeeper[
                'expected_points'
            ]
            ?? -1
        )
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * ACTION RATE EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Action Rate Effect<br>";
echo "============================================<br>";


$low =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'cbit_per_90' =>
                        4
                ]
            ]
        );


$medium =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'cbit_per_90' =>
                        8
                ]
            ]
        );


$high =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'cbit_per_90' =>
                        14
                ]
            ]
        );


expectedDcCheck(
    'Higher defensive action rate increases threshold probability',
    (
        (float) $medium[
            'threshold_probability'
        ]
    )
    >
    (
        (float) $low[
            'threshold_probability'
        ]
    )
    &&
    (
        (float) $high[
            'threshold_probability'
        ]
    )
    >
    (
        (float) $medium[
            'threshold_probability'
        ]
    )
);


expectedDcCheck(
    'Higher defensive action rate increases expected DC points',
    (
        (float) $high[
            'expected_points'
        ]
    )
    >
    (
        (float) $low[
            'expected_points'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * MINUTES EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Minutes Effect<br>";
echo "============================================<br>";


$minutes90 =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'weighted_metrics' => [

                    'cbit_per_90' =>
                        10
                ]
            ]
        );


$minutes45 =
    $model
        ->calculate(
            'DEF',
            45,
            [
                'weighted_metrics' => [

                    'cbit_per_90' =>
                        10
                ]
            ]
        );


expectedDcCheck(
    'Reduced projected minutes reduce expected defensive actions',
    (
        (float) $minutes45[
            'expected_actions'
        ]
    )
    <
    (
        (float) $minutes90[
            'expected_actions'
        ]
    )
);


expectedDcCheck(
    'Reduced projected minutes reduce expected DC points',
    (
        (float) $minutes45[
            'expected_points'
        ]
    )
    <
    (
        (float) $minutes90[
            'expected_points'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * OPPONENT ATTACK EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Opponent Attack Effect<br>";
echo "============================================<br>";


$weakOpponent =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'weighted_metrics' => [

                    'cbit_per_90' =>
                        10
                ]
            ],
            [
                'opponent_attack_rating' =>
                    0
            ]
        );


$strongOpponent =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'weighted_metrics' => [

                    'cbit_per_90' =>
                        10
                ]
            ],
            [
                'opponent_attack_rating' =>
                    100
            ]
        );


expectedDcCheck(
    'Strong opponent attack increases defensive action opportunity',
    (
        (float) $strongOpponent[
            'expected_actions'
        ]
    )
    >
    (
        (float) $weakOpponent[
            'expected_actions'
        ]
    )
);


expectedDcCheck(
    'Strong opponent attack increases expected defensive contribution points',
    (
        (float) $strongOpponent[
            'expected_points'
        ]
    )
    >
    (
        (float) $weakOpponent[
            'expected_points'
        ]
    )
);


expectedDcCheck(
    'Weak opponent multiplier is 0.85',
    abs(
        (
            (float) $weakOpponent[
                'opportunity_multiplier'
            ]
        )
        -
        0.85
    )
    < 0.001
);


expectedDcCheck(
    'Strong opponent multiplier is 1.15',
    abs(
        (
            (float) $strongOpponent[
                'opportunity_multiplier'
            ]
        )
        -
        1.15
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * MISSING DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Missing Data<br>";
echo "============================================<br>";


$missing =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'weighted_metrics' =>
                    []
            ]
        );


expectedDcCheck(
    'Missing CBIT history is Insufficient Data',
    (
        $missing[
            'status'
        ]
        ?? null
    )
    ===
    'Insufficient Data'
);


expectedDcCheck(
    'Missing CBIT history produces zero expected DC points',
    abs(
        (float) (
            $missing[
                'expected_points'
            ]
            ?? -1
        )
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * POISSON PROBABILITY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Poisson Probability Contract<br>";
echo "============================================<br>";


expectedDcCheck(
    'Zero expected actions have zero probability of reaching threshold',
    abs(
        $model
            ->poissonAtLeast(
                0,
                10
            )
    )
    < 0.001
);


expectedDcCheck(
    'Zero threshold is always satisfied',
    abs(
        $model
            ->poissonAtLeast(
                5,
                0
            )
        -
        1
    )
    < 0.001
);


$probabilityLow =
    $model
        ->poissonAtLeast(
            5,
            10
        );


$probabilityHigh =
    $model
        ->poissonAtLeast(
            12,
            10
        );


expectedDcCheck(
    'Higher lambda increases Poisson threshold probability',
    $probabilityHigh
    >
    $probabilityLow
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * EARLY-SAMPLE REGRESSION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Early-Sample Regression<br>";
echo "============================================<br>";


$earlyDefender =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    1,

                'weighted_metrics' => [

                    'cbit_per_90' =>
                        15.0
                ]
            ],
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


expectedDcCheck(
    'Defender baseline is seven CBIT per 90',
    abs(
        (
            (float) (
                $earlyDefender[
                    'position_baseline'
                ]
                ?? 0
            )
        )
        -
        7.0
    )
    < 0.001
);


expectedDcCheck(
    'One appearance produces 20 percent sample confidence',
    abs(
        (
            (float) (
                $earlyDefender[
                    'sample_confidence'
                ]
                ?? 0
            )
        )
        -
        0.20
    )
    < 0.001
);


expectedDcCheck(
    'One-match 15 CBIT rate regresses to 8.6 CBIT per 90',
    abs(
        (
            (float) (
                $earlyDefender[
                    'regressed_actions_per_90'
                ]
                ?? 0
            )
        )
        -
        8.60
    )
    < 0.001
);


expectedDcCheck(
    'Early sample produces lower expected DC points than unregressed 15 CBIT rate',
    (
        (float) (
            $earlyDefender[
                'expected_points'
            ]
            ?? 0
        )
    )
    < 1.9
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * MATURE SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Mature Sample<br>";
echo "============================================<br>";


$matureDefender =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'cbit_per_90' =>
                        15.0
                ]
            ]
        );


expectedDcCheck(
    'Five appearances produce full sample confidence',
    abs(
        (
            (float) (
                $matureDefender[
                    'sample_confidence'
                ]
                ?? 0
            )
        )
        -
        1.0
    )
    < 0.001
);


expectedDcCheck(
    'Mature sample preserves player defensive action rate',
    abs(
        (
            (float) (
                $matureDefender[
                    'regressed_actions_per_90'
                ]
                ?? 0
            )
        )
        -
        15.0
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Bounds<br>";
echo "============================================<br>";


$extreme =
    $model
        ->calculate(
            'DEF',
            999,
            [
                'weighted_metrics' => [

                    'cbit_per_90' =>
                        999
                ]
            ],
            [
                'opponent_attack_rating' =>
                    999
            ]
        );


expectedDcCheck(
    'Projected minutes are capped at 90',
    abs(
        (
            (float) $extreme[
                'projected_minutes'
            ]
        )
        -
        90
    )
    < 0.001
);


expectedDcCheck(
    'Threshold probability cannot exceed one',
    (
        (float) $extreme[
            'threshold_probability'
        ]
    )
    <= 1
);


expectedDcCheck(
    'Expected defensive contribution points cannot exceed two',
    (
        (float) $extreme[
            'expected_points'
        ]
    )
    <= 2
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Expected Defensive Contributions Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}