<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Bonus Real Data Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function realBonusCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


/*
 * ============================================================
 * LOAD REAL PLAYER INTELLIGENCE
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


$service =
    new PlayerIntelligenceService(
        $connection
    );


$summaries =
    $service
        ->getAllPlayerSummaries();


echo "Player Summaries: "
    . count(
        $summaries
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO A
 * REAL PLAYER POOL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Player Pool<br>";
echo "============================================<br>";


realBonusCheck(
    'Player Intelligence returns real player summaries',
    !empty(
        $summaries
    )
);


realBonusCheck(
    'Current player pool contains at least 20 players',
    count(
        $summaries
    )
    >= 20
);


echo "<br>";


/*
 * ============================================================
 * FIND STRONGEST REAL BONUS CANDIDATE
 * ============================================================
 * Select the player with the highest projected BPS among
 * players whose bonus model is available.
 *
 * Expected Bonus is based on projected BPS after sample
 * regression and projected-minutes scaling, so this is the
 * correct value to rank for the positive-end diagnostic.
 */

$selectedPlayer =
    null;


$highestProjectedBps =
    null;


foreach (
    $summaries
    as $summary
) {

    $inputs =
        $summary[
            'projected_points_inputs'
        ]
        ?? [];


    $bonusEvidence =
        $inputs[
            'evidence'
        ][
            'bonus'
        ]
        ?? null;


    if (
        !is_array(
            $bonusEvidence
        )
    ) {

        continue;
    }


    if (
        (
            $bonusEvidence[
                'status'
            ]
            ?? null
        )
        !==
        'Modelled'
    ) {

        continue;
    }


    $projectedBps =
        $bonusEvidence[
            'projected_bps'
        ]
        ?? null;


    if (
        !is_numeric(
            $projectedBps
        )
    ) {

        continue;
    }


    $projectedBps =
        (float) $projectedBps;


    if (
        $highestProjectedBps === null
        ||
        $projectedBps
        >
        $highestProjectedBps
    ) {

        $highestProjectedBps =
            $projectedBps;


        $selectedPlayer =
            $summary;
    }
}


/*
 * ============================================================
 * SCENARIO B
 * HIGH-BPS PLAYER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: High-BPS Player Resolution<br>";
echo "============================================<br>";


realBonusCheck(
    'A real player with modelled BPS history is available',
    is_array(
        $selectedPlayer
    )
);


if (
    $selectedPlayer === null
) {

    echo "<br>";
    echo "No suitable high-BPS player could be resolved.";
    echo "<br><br>";

} else {

    $playerName =
        trim(
            (string) (
                $selectedPlayer[
                    'web_name'
                ]
                ??
                $selectedPlayer[
                    'player_name'
                ]
                ??
                $selectedPlayer[
                    'name'
                ]
                ??
                'Unknown'
            )
        );


    $position =
        strtoupper(
            trim(
                (string) (
                    $selectedPlayer[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    $inputs =
        $selectedPlayer[
            'projected_points_inputs'
        ]
        ?? [];


    $components =
        $selectedPlayer[
            'projected_points_components'
        ]
        ?? [];


    $bonusEvidence =
        $inputs[
            'evidence'
        ][
            'bonus'
        ]
        ?? [];


    echo "Player: "
        . htmlspecialchars(
            $playerName,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Position: "
        . htmlspecialchars(
            $position,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO C
     * BONUS MODEL CONTRACT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Bonus Model Contract<br>";
    echo "============================================<br>";


    realBonusCheck(
        'Bonus model status is Modelled',
        (
            $bonusEvidence[
                'status'
            ]
            ?? null
        )
        ===
        'Modelled'
    );


    realBonusCheck(
        'Raw BPS per 90 is numeric',
        is_numeric(
            $bonusEvidence[
                'bps_per_90'
            ]
            ?? null
        )
    );


    realBonusCheck(
        'Position BPS baseline is numeric',
        is_numeric(
            $bonusEvidence[
                'position_baseline'
            ]
            ?? null
        )
    );


    realBonusCheck(
        'Regressed BPS per 90 is numeric',
        is_numeric(
            $bonusEvidence[
                'regressed_bps_per_90'
            ]
            ?? null
        )
    );


    realBonusCheck(
        'Projected BPS is numeric',
        is_numeric(
            $bonusEvidence[
                'projected_bps'
            ]
            ?? null
        )
    );


    realBonusCheck(
        'Expected bonus points are numeric',
        is_numeric(
            $bonusEvidence[
                'expected_points'
            ]
            ?? null
        )
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO D
     * SAMPLE REGRESSION
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Sample Regression<br>";
    echo "============================================<br>";


    realBonusCheck(
        'Appearance sample size is non-negative',
        (
            (int) (
                $bonusEvidence[
                    'appearance_sample_size'
                ]
                ?? -1
            )
        )
        >= 0
    );


    realBonusCheck(
        'Bonus sample confidence remains between 0 and 1',
        is_numeric(
            $bonusEvidence[
                'sample_confidence'
            ]
            ?? null
        )
        &&
        (
            (float) $bonusEvidence[
                'sample_confidence'
            ]
        )
        >= 0
        &&
        (
            (float) $bonusEvidence[
                'sample_confidence'
            ]
        )
        <= 1
    );


    realBonusCheck(
        'Regressed BPS remains bounded by raw rate and position baseline',
        (
            (float) $bonusEvidence[
                'regressed_bps_per_90'
            ]
        )
        >=
        min(
            (float) $bonusEvidence[
                'bps_per_90'
            ],
            (float) $bonusEvidence[
                'position_baseline'
            ]
        )
        -
        0.001
        &&
        (
            (float) $bonusEvidence[
                'regressed_bps_per_90'
            ]
        )
        <=
        max(
            (float) $bonusEvidence[
                'bps_per_90'
            ],
            (float) $bonusEvidence[
                'position_baseline'
            ]
        )
        +
        0.001
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * POSITIVE BONUS EXPECTATION
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Positive Bonus Expectation<br>";
    echo "============================================<br>";


    realBonusCheck(
        'High-BPS real player projects positive expected bonus',
        (
            (float) (
                $bonusEvidence[
                    'expected_points'
                ]
                ?? 0
            )
        )
        >
        0
    );


    realBonusCheck(
        'Expected bonus remains below theoretical three-point maximum',
        (
            (float) (
                $bonusEvidence[
                    'expected_points'
                ]
                ?? 999
            )
        )
        <
        3
    );


    realBonusCheck(
        'Projected bonus component matches bonus model output',
        abs(
            (
                (float) (
                    $components[
                        'bonus'
                    ]
                    ?? 0
                )
            )
            -
            (
                (float) (
                    $bonusEvidence[
                        'expected_points'
                    ]
                    ?? 0
                )
            )
        )
        < 0.011
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * PLAYER PROJECTION CONTRACT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Player Projection Contract<br>";
    echo "============================================<br>";


    realBonusCheck(
        'Selected player has Projected Points available',
        (
            $selectedPlayer[
                'has_projected_points'
            ]
            ?? false
        )
        ===
        true
    );


    realBonusCheck(
        'Selected player Projected Points are numeric',
        is_numeric(
            $selectedPlayer[
                'projected_points'
            ]
            ?? null
        )
    );


    realBonusCheck(
        'Selected player Projected Minutes remain between 0 and 90',
        is_numeric(
            $selectedPlayer[
                'projected_minutes'
            ]
            ?? null
        )
        &&
        (
            (float) $selectedPlayer[
                'projected_minutes'
            ]
        )
        >= 0
        &&
        (
            (float) $selectedPlayer[
                'projected_minutes'
            ]
        )
        <= 90
    );


    $componentTotal =
        array_sum(
            $components
        );


    realBonusCheck(
        'Projected Points equal full component total',
        abs(
            (
                (float) (
                    $selectedPlayer[
                        'projected_points'
                    ]
                    ?? 0
                )
            )
            -
            $componentTotal
        )
        < 0.011
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO G
     * REAL BONUS DIAGNOSTIC
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Real Bonus Diagnostic<br>";
    echo "============================================<br>";


    echo "Player: "
        . htmlspecialchars(
            $playerName,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Position: "
        . htmlspecialchars(
            $position,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Raw BPS / 90: "
        . number_format(
            (float) (
                $bonusEvidence[
                    'bps_per_90'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Position BPS Baseline: "
        . number_format(
            (float) (
                $bonusEvidence[
                    'position_baseline'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Appearance Sample Size: "
        . (int) (
            $bonusEvidence[
                'appearance_sample_size'
            ]
            ?? 0
        )
        . "<br>";


    echo "Sample Confidence: "
        . number_format(
            (float) (
                $bonusEvidence[
                    'sample_confidence_percent'
                ]
                ?? 0
            ),
            2
        )
        . "%<br>";


    echo "Regressed BPS / 90: "
        . number_format(
            (float) (
                $bonusEvidence[
                    'regressed_bps_per_90'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Projected Minutes: "
        . number_format(
            (float) (
                $selectedPlayer[
                    'projected_minutes'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Projected BPS: "
        . number_format(
            (float) (
                $bonusEvidence[
                    'projected_bps'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Expected Bonus Points: "
        . number_format(
            (float) (
                $bonusEvidence[
                    'expected_points'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Bonus Component: "
        . number_format(
            (float) (
                $components[
                    'bonus'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Total Projected Points: "
        . number_format(
            (float) (
                $selectedPlayer[
                    'projected_points'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO H
     * OTHER MODELLED COMPONENTS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Full Projection Breakdown<br>";
    echo "============================================<br>";


    foreach (
        [
            'appearance',
            'goals',
            'assists',
            'clean_sheet',
            'saves',
            'bonus',
            'defensive_contributions'
        ]
        as $component
    ) {

        echo ucfirst(
            str_replace(
                '_',
                ' ',
                $component
            )
        )
            . ': '
            . number_format(
                (float) (
                    $components[
                        $component
                    ]
                    ?? 0
                ),
                2
            )
            . "<br>";
    }


    echo "<br>";
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Expected Bonus Real Data Test Summary<br>";
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