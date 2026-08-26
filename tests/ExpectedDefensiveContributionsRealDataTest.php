<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Defensive Contributions Real Data Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function realDcCheck(
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


realDcCheck(
    'Player Intelligence returns real player summaries',
    !empty(
        $summaries
    )
);


realDcCheck(
    'Current player pool contains at least 20 players',
    count(
        $summaries
    )
    >= 20
);


echo "<br>";


/*
 * ============================================================
 * FIND A REAL DEFENDER WITH MODELLED DC DATA
 * ============================================================
 */

$selectedDefender =
    null;


foreach (
    $summaries
    as $summary
) {

    if (
        strtoupper(
            trim(
                (string) (
                    $summary[
                        'position'
                    ]
                    ?? ''
                )
            )
        )
        !==
        'DEF'
    ) {

        continue;
    }


    $inputs =
        $summary[
            'projected_points_inputs'
        ]
        ?? [];


    $dcEvidence =
        $inputs[
            'evidence'
        ][
            'defensive_contributions'
        ]
        ?? null;


    if (
        !is_array(
            $dcEvidence
        )
    ) {

        continue;
    }


    if (
        (
            $dcEvidence[
                'status'
            ]
            ?? null
        )
        !==
        'Modelled'
    ) {

        continue;
    }


    if (
        !is_numeric(
            $dcEvidence[
                'actions_per_90'
            ]
            ?? null
        )
        ||
        (
            (float) $dcEvidence[
                'actions_per_90'
            ]
        )
        <= 0
    ) {

        continue;
    }


    $selectedDefender =
        $summary;

    break;
}


/*
 * ============================================================
 * SCENARIO B
 * REAL DEFENDER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Real Defender Resolution<br>";
echo "============================================<br>";


realDcCheck(
    'A real defender with modelled defensive contribution data is available',
    is_array(
        $selectedDefender
    )
);


if (
    $selectedDefender === null
) {

    echo "<br>";
    echo "No suitable defender could be resolved.";
    echo "<br><br>";

} else {

    $playerName =
        trim(
            (string) (
                $selectedDefender[
                    'web_name'
                ]
                ??
                $selectedDefender[
                    'player_name'
                ]
                ??
                $selectedDefender[
                    'name'
                ]
                ??
                'Unknown'
            )
        );


    $inputs =
        $selectedDefender[
            'projected_points_inputs'
        ]
        ?? [];


    $components =
        $selectedDefender[
            'projected_points_components'
        ]
        ?? [];


    $dcEvidence =
        $inputs[
            'evidence'
        ][
            'defensive_contributions'
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
            (string) (
                $selectedDefender[
                    'position'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO C
     * DEFENSIVE CONTRIBUTION CONTRACT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Defensive Contribution Contract<br>";
    echo "============================================<br>";


    realDcCheck(
        'Defensive contribution status is Modelled',
        (
            $dcEvidence[
                'status'
            ]
            ?? null
        )
        ===
        'Modelled'
    );


    realDcCheck(
        'Defender uses CBIT per 90',
        (
            $dcEvidence[
                'metric'
            ]
            ?? null
        )
        ===
        'cbit_per_90'
    );


    realDcCheck(
        'Defender threshold is 10',
        (
            (int) (
                $dcEvidence[
                    'threshold'
                ]
                ?? 0
            )
        )
        ===
        10
    );


    realDcCheck(
        'CBIT per 90 is positive',
        is_numeric(
            $dcEvidence[
                'actions_per_90'
            ]
            ?? null
        )
        &&
        (
            (float) $dcEvidence[
                'actions_per_90'
            ]
        )
        > 0
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO D
     * PROJECTED ACTIONS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Projected Actions<br>";
    echo "============================================<br>";


    realDcCheck(
        'Projected defensive actions are numeric',
        is_numeric(
            $dcEvidence[
                'expected_actions'
            ]
            ?? null
        )
    );


    realDcCheck(
        'Projected defensive actions are non-negative',
        (
            (float) (
                $dcEvidence[
                    'expected_actions'
                ]
                ?? -1
            )
        )
        >= 0
    );


    realDcCheck(
        'Projected minutes remain between 0 and 90',
        is_numeric(
            $selectedDefender[
                'projected_minutes'
            ]
            ?? null
        )
        &&
        (
            (float) $selectedDefender[
                'projected_minutes'
            ]
        )
        >= 0
        &&
        (
            (float) $selectedDefender[
                'projected_minutes'
            ]
        )
        <= 90
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * THRESHOLD PROBABILITY
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Threshold Probability<br>";
    echo "============================================<br>";


    realDcCheck(
        'Threshold probability is numeric',
        is_numeric(
            $dcEvidence[
                'threshold_probability'
            ]
            ?? null
        )
    );


    realDcCheck(
        'Threshold probability remains between 0 and 1',
        (
            (float) (
                $dcEvidence[
                    'threshold_probability'
                ]
                ?? -1
            )
        )
        >= 0
        &&
        (
            (float) (
                $dcEvidence[
                    'threshold_probability'
                ]
                ?? 2
            )
        )
        <= 1
    );


    realDcCheck(
        'Threshold probability percent remains between 0 and 100',
        is_numeric(
            $dcEvidence[
                'threshold_probability_percent'
            ]
            ?? null
        )
        &&
        (
            (float) $dcEvidence[
                'threshold_probability_percent'
            ]
        )
        >= 0
        &&
        (
            (float) $dcEvidence[
                'threshold_probability_percent'
            ]
        )
        <= 100
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * EXPECTED POINTS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Expected Defensive Contribution Points<br>";
    echo "============================================<br>";


    realDcCheck(
        'Expected defensive contribution points are numeric',
        is_numeric(
            $dcEvidence[
                'expected_points'
            ]
            ?? null
        )
    );


    realDcCheck(
        'Expected defensive contribution points remain between 0 and 2',
        (
            (float) (
                $dcEvidence[
                    'expected_points'
                ]
                ?? -1
            )
        )
        >= 0
        &&
        (
            (float) (
                $dcEvidence[
                    'expected_points'
                ]
                ?? 3
            )
        )
        <= 2
    );


    realDcCheck(
        'Projected component matches defensive contribution model',
        abs(
            (
                (float) (
                    $components[
                        'defensive_contributions'
                    ]
                    ?? 0
                )
            )
            -
            (
                (float) (
                    $dcEvidence[
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
     * SCENARIO G
     * REAL DATA DIAGNOSTIC
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Real Defender Diagnostic<br>";
    echo "============================================<br>";


    echo "Player: "
        . htmlspecialchars(
            $playerName,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "CBIT / 90: "
        . number_format(
            (float) (
                $dcEvidence[
                    'actions_per_90'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Projected Minutes: "
        . number_format(
            (float) (
                $selectedDefender[
                    'projected_minutes'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Opponent Attack Rating: "
        . (
            is_numeric(
                $dcEvidence[
                    'opponent_attack_rating'
                ]
                ?? null
            )
                ? number_format(
                    (float) $dcEvidence[
                        'opponent_attack_rating'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Opportunity Multiplier: "
        . number_format(
            (float) (
                $dcEvidence[
                    'opportunity_multiplier'
                ]
                ?? 1
            ),
            3
        )
        . "<br>";
        
        
    echo "Position Baseline: "
        . number_format(
            (float) (
                $dcEvidence[
                    'position_baseline'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Appearance Sample Size: "
        . (int) (
            $dcEvidence[
                'appearance_sample_size'
            ]
            ?? 0
        )
        . "<br>";


    echo "Sample Confidence: "
        . number_format(
            (float) (
                $dcEvidence[
                    'sample_confidence_percent'
                ]
                ?? 0
            ),
            2
        )
        . "%<br>";


    echo "Regressed CBIT / 90: "
        . number_format(
            (float) (
                $dcEvidence[
                    'regressed_actions_per_90'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";    


    echo "Projected CBIT: "
        . number_format(
            (float) (
                $dcEvidence[
                    'expected_actions'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Threshold: "
        . (int) (
            $dcEvidence[
                'threshold'
            ]
            ?? 0
        )
        . "<br>";


    echo "Threshold Probability: "
        . number_format(
            (float) (
                $dcEvidence[
                    'threshold_probability_percent'
                ]
                ?? 0
            ),
            2
        )
        . "%<br>";


    echo "Expected Defensive Contribution Points: "
        . number_format(
            (float) (
                $dcEvidence[
                    'expected_points'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Total Projected Points: "
        . number_format(
            (float) (
                $selectedDefender[
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
     * COMPONENT TOTAL
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Projection Component Total<br>";
    echo "============================================<br>";


    $componentTotal =
        array_sum(
            $components
        );


    realDcCheck(
        'Defender Projected Points equal component total',
        is_numeric(
            $selectedDefender[
                'projected_points'
            ]
            ?? null
        )
        &&
        abs(
            (
                (float) $selectedDefender[
                    'projected_points'
                ]
            )
            -
            $componentTotal
        )
        < 0.011
    );


    echo "Component Total: "
        . number_format(
            $componentTotal,
            2
        )
        . "<br><br>";
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Expected Defensive Contributions Real Data Test Summary<br>";
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