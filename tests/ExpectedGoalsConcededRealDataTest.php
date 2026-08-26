<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Goals Conceded Real Data Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function realGoalsConcededCheck(
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


realGoalsConcededCheck(
    'Player Intelligence returns real player summaries',
    !empty(
        $summaries
    )
);


realGoalsConcededCheck(
    'Current player pool contains at least 20 players',
    count(
        $summaries
    )
    >= 20
);


echo "<br>";


/*
 * ============================================================
 * RESOLVE MODELLED GK / DEF PROJECTIONS
 * ============================================================
 */

$modelledPlayers =
    [];


foreach (
    $summaries
    as $summary
) {

    $position =
        strtoupper(
            trim(
                (string) (
                    $summary[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    if (
        !in_array(
            $position,
            [
                'GK',
                'DEF'
            ],
            true
        )
    ) {

        continue;
    }


    $inputs =
        $summary[
            'projected_points_inputs'
        ]
        ?? [];


    $evidence =
        $inputs[
            'evidence'
        ][
            'goals_conceded'
        ]
        ?? null;


    if (
        !is_array(
            $evidence
        )
    ) {

        continue;
    }


    if (
        (
            $evidence[
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
            $evidence[
                'projected_xgc'
            ]
            ?? null
        )
        ||
        !is_numeric(
            $evidence[
                'expected_points'
            ]
            ?? null
        )
    ) {

        continue;
    }


    $modelledPlayers[] =
        $summary;
}


/*
 * ============================================================
 * SCENARIO B
 * MODELLED PLAYER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Modelled Player Resolution<br>";
echo "============================================<br>";


realGoalsConcededCheck(
    'At least one real GK or DEF has modelled goals-conceded evidence',
    !empty(
        $modelledPlayers
    )
);


realGoalsConcededCheck(
    'Multiple modelled GK or DEF projections are available',
    count(
        $modelledPlayers
    )
    >= 2
);


echo "Modelled GK / DEF Players: "
    . count(
        $modelledPlayers
    )
    . "<br><br>";


/*
 * ============================================================
 * RESOLVE LOWEST / HIGHEST PROJECTED XGC
 * ============================================================
 */

$lowestPlayer =
    null;


$highestPlayer =
    null;


$lowestProjectedXgc =
    null;


$highestProjectedXgc =
    null;


foreach (
    $modelledPlayers
    as $summary
) {

    $evidence =
        $summary[
            'projected_points_inputs'
        ][
            'evidence'
        ][
            'goals_conceded'
        ]
        ?? [];


    $projectedXgc =
        (float) (
            $evidence[
                'projected_xgc'
            ]
            ?? 0
        );


    if (
        $lowestProjectedXgc === null
        ||
        $projectedXgc
        <
        $lowestProjectedXgc
    ) {

        $lowestProjectedXgc =
            $projectedXgc;


        $lowestPlayer =
            $summary;
    }


    if (
        $highestProjectedXgc === null
        ||
        $projectedXgc
        >
        $highestProjectedXgc
    ) {

        $highestProjectedXgc =
            $projectedXgc;


        $highestPlayer =
            $summary;
    }
}


/*
 * ============================================================
 * SCENARIO C
 * LOW / HIGH RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Low / High xGC Resolution<br>";
echo "============================================<br>";


realGoalsConcededCheck(
    'Lowest projected xGC player resolves',
    is_array(
        $lowestPlayer
    )
);


realGoalsConcededCheck(
    'Highest projected xGC player resolves',
    is_array(
        $highestPlayer
    )
);


realGoalsConcededCheck(
    'Highest projected xGC exceeds lowest projected xGC',
    $lowestProjectedXgc !== null
    &&
    $highestProjectedXgc !== null
    &&
    $highestProjectedXgc
    >
    $lowestProjectedXgc
);


echo "<br>";


if (
    $lowestPlayer !== null
    &&
    $highestPlayer !== null
) {

    $lowInputs =
        $lowestPlayer[
            'projected_points_inputs'
        ]
        ?? [];


    $highInputs =
        $highestPlayer[
            'projected_points_inputs'
        ]
        ?? [];


    $lowEvidence =
        $lowInputs[
            'evidence'
        ][
            'goals_conceded'
        ]
        ?? [];


    $highEvidence =
        $highInputs[
            'evidence'
        ][
            'goals_conceded'
        ]
        ?? [];


    $lowComponents =
        $lowestPlayer[
            'projected_points_components'
        ]
        ?? [];


    $highComponents =
        $highestPlayer[
            'projected_points_components'
        ]
        ?? [];


    $lowName =
        trim(
            (string) (
                $lowestPlayer[
                    'web_name'
                ]
                ??
                $lowestPlayer[
                    'player_name'
                ]
                ??
                $lowestPlayer[
                    'name'
                ]
                ??
                'Unknown'
            )
        );


    $highName =
        trim(
            (string) (
                $highestPlayer[
                    'web_name'
                ]
                ??
                $highestPlayer[
                    'player_name'
                ]
                ??
                $highestPlayer[
                    'name'
                ]
                ??
                'Unknown'
            )
        );


    $lowPosition =
        strtoupper(
            trim(
                (string) (
                    $lowestPlayer[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    $highPosition =
        strtoupper(
            trim(
                (string) (
                    $highestPlayer[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    /*
     * ========================================================
     * SCENARIO D
     * MODEL CONTRACT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Model Contract<br>";
    echo "============================================<br>";


    foreach (
        [
            'raw_xgc_per_90',
            'position_baseline',
            'appearance_sample_size',
            'sample_confidence',
            'regressed_xgc_per_90',
            'fixture_multiplier',
            'projected_minutes',
            'projected_xgc',
            'expected_deduction_magnitude',
            'expected_points'
        ]
        as $field
    ) {

        realGoalsConcededCheck(
            'Low-xGC model exposes '
            . $field,
            array_key_exists(
                $field,
                $lowEvidence
            )
        );


        realGoalsConcededCheck(
            'High-xGC model exposes '
            . $field,
            array_key_exists(
                $field,
                $highEvidence
            )
        );
    }


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * SAMPLE REGRESSION
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Sample Regression<br>";
    echo "============================================<br>";


    realGoalsConcededCheck(
        'Low-xGC sample confidence remains between zero and one',
        is_numeric(
            $lowEvidence[
                'sample_confidence'
            ]
            ?? null
        )
        &&
        (
            (float) $lowEvidence[
                'sample_confidence'
            ]
        )
        >= 0
        &&
        (
            (float) $lowEvidence[
                'sample_confidence'
            ]
        )
        <= 1
    );


    realGoalsConcededCheck(
        'High-xGC sample confidence remains between zero and one',
        is_numeric(
            $highEvidence[
                'sample_confidence'
            ]
            ?? null
        )
        &&
        (
            (float) $highEvidence[
                'sample_confidence'
            ]
        )
        >= 0
        &&
        (
            (float) $highEvidence[
                'sample_confidence'
            ]
        )
        <= 1
    );


    realGoalsConcededCheck(
        'Low-xGC regressed rate remains between raw rate and baseline',
        (
            (float) $lowEvidence[
                'regressed_xgc_per_90'
            ]
        )
        >=
        min(
            (float) $lowEvidence[
                'raw_xgc_per_90'
            ],
            (float) $lowEvidence[
                'position_baseline'
            ]
        )
        -
        0.001
        &&
        (
            (float) $lowEvidence[
                'regressed_xgc_per_90'
            ]
        )
        <=
        max(
            (float) $lowEvidence[
                'raw_xgc_per_90'
            ],
            (float) $lowEvidence[
                'position_baseline'
            ]
        )
        +
        0.001
    );


    realGoalsConcededCheck(
        'High-xGC regressed rate remains between raw rate and baseline',
        (
            (float) $highEvidence[
                'regressed_xgc_per_90'
            ]
        )
        >=
        min(
            (float) $highEvidence[
                'raw_xgc_per_90'
            ],
            (float) $highEvidence[
                'position_baseline'
            ]
        )
        -
        0.001
        &&
        (
            (float) $highEvidence[
                'regressed_xgc_per_90'
            ]
        )
        <=
        max(
            (float) $highEvidence[
                'raw_xgc_per_90'
            ],
            (float) $highEvidence[
                'position_baseline'
            ]
        )
        +
        0.001
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * DEDUCTION DIRECTION
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Deduction Direction<br>";
    echo "============================================<br>";


    realGoalsConcededCheck(
        'Higher projected xGC produces larger deduction magnitude',
        (
            (float) $highEvidence[
                'expected_deduction_magnitude'
            ]
        )
        >
        (
            (float) $lowEvidence[
                'expected_deduction_magnitude'
            ]
        )
    );


    realGoalsConcededCheck(
        'Higher projected xGC produces more negative expected points',
        (
            (float) $highEvidence[
                'expected_points'
            ]
        )
        <
        (
            (float) $lowEvidence[
                'expected_points'
            ]
        )
    );


    realGoalsConcededCheck(
        'Low-xGC expected points remain non-positive',
        (
            (float) $lowEvidence[
                'expected_points'
            ]
        )
        <= 0
    );


    realGoalsConcededCheck(
        'High-xGC expected points remain non-positive',
        (
            (float) $highEvidence[
                'expected_points'
            ]
        )
        <= 0
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO G
     * COMPONENT INTEGRATION
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Component Integration<br>";
    echo "============================================<br>";


    realGoalsConcededCheck(
        'Low-xGC goals-conceded component matches model output',
        abs(
            (
                (float) (
                    $lowComponents[
                        'goals_conceded'
                    ]
                    ?? 0
                )
            )
            -
            (
                (float) $lowEvidence[
                    'expected_points'
                ]
            )
        )
        <
        0.011
    );


    realGoalsConcededCheck(
        'High-xGC goals-conceded component matches model output',
        abs(
            (
                (float) (
                    $highComponents[
                        'goals_conceded'
                    ]
                    ?? 0
                )
            )
            -
            (
                (float) $highEvidence[
                    'expected_points'
                ]
            )
        )
        <
        0.011
    );


    $lowComponentTotal =
        array_sum(
            $lowComponents
        );


    $highComponentTotal =
        array_sum(
            $highComponents
        );


    realGoalsConcededCheck(
        'Low-xGC Projected Points equal full component total',
        abs(
            (
                (float) (
                    $lowestPlayer[
                        'projected_points'
                    ]
                    ?? 0
                )
            )
            -
            $lowComponentTotal
        )
        <
        0.011
    );


    realGoalsConcededCheck(
        'High-xGC Projected Points equal full component total',
        abs(
            (
                (float) (
                    $highestPlayer[
                        'projected_points'
                    ]
                    ?? 0
                )
            )
            -
            $highComponentTotal
        )
        <
        0.011
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO H
     * LOW-xGC REAL DIAGNOSTIC
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Low-xGC Real Diagnostic<br>";
    echo "============================================<br>";


    echo "Player: "
        . htmlspecialchars(
            $lowName,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Position: "
        . htmlspecialchars(
            $lowPosition,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Raw xGC / 90: "
        . number_format(
            (float) $lowEvidence[
                'raw_xgc_per_90'
            ],
            2
        )
        . "<br>";


    echo "Position Baseline: "
        . number_format(
            (float) $lowEvidence[
                'position_baseline'
            ],
            2
        )
        . "<br>";


    echo "Appearance Sample: "
        . (int) $lowEvidence[
            'appearance_sample_size'
        ]
        . "<br>";


    echo "Sample Confidence: "
        . number_format(
            (float) (
                $lowEvidence[
                    'sample_confidence_percent'
                ]
                ?? 0
            ),
            2
        )
        . "%<br>";


    echo "Regressed xGC / 90: "
        . number_format(
            (float) $lowEvidence[
                'regressed_xgc_per_90'
            ],
            2
        )
        . "<br>";


    echo "Opponent Attack Rating: "
        . (
            isset(
                $lowEvidence[
                    'opponent_attack_rating'
                ]
            )
                ? number_format(
                    (float) $lowEvidence[
                        'opponent_attack_rating'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Fixture Multiplier: "
        . number_format(
            (float) $lowEvidence[
                'fixture_multiplier'
            ],
            3
        )
        . "<br>";


    echo "Projected Minutes: "
        . number_format(
            (float) $lowEvidence[
                'projected_minutes'
            ],
            2
        )
        . "<br>";


    echo "Projected xGC: "
        . number_format(
            (float) $lowEvidence[
                'projected_xgc'
            ],
            2
        )
        . "<br>";


    echo "Expected Deduction: "
        . number_format(
            (float) $lowEvidence[
                'expected_points'
            ],
            2
        )
        . "<br>";


    echo "Total Projected Points: "
        . number_format(
            (float) (
                $lowestPlayer[
                    'projected_points'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO I
     * HIGH-xGC REAL DIAGNOSTIC
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario I: High-xGC Real Diagnostic<br>";
    echo "============================================<br>";


    echo "Player: "
        . htmlspecialchars(
            $highName,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Position: "
        . htmlspecialchars(
            $highPosition,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Raw xGC / 90: "
        . number_format(
            (float) $highEvidence[
                'raw_xgc_per_90'
            ],
            2
        )
        . "<br>";


    echo "Position Baseline: "
        . number_format(
            (float) $highEvidence[
                'position_baseline'
            ],
            2
        )
        . "<br>";


    echo "Appearance Sample: "
        . (int) $highEvidence[
            'appearance_sample_size'
        ]
        . "<br>";


    echo "Sample Confidence: "
        . number_format(
            (float) (
                $highEvidence[
                    'sample_confidence_percent'
                ]
                ?? 0
            ),
            2
        )
        . "%<br>";


    echo "Regressed xGC / 90: "
        . number_format(
            (float) $highEvidence[
                'regressed_xgc_per_90'
            ],
            2
        )
        . "<br>";


    echo "Opponent Attack Rating: "
        . (
            isset(
                $highEvidence[
                    'opponent_attack_rating'
                ]
            )
                ? number_format(
                    (float) $highEvidence[
                        'opponent_attack_rating'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Fixture Multiplier: "
        . number_format(
            (float) $highEvidence[
                'fixture_multiplier'
            ],
            3
        )
        . "<br>";


    echo "Projected Minutes: "
        . number_format(
            (float) $highEvidence[
                'projected_minutes'
            ],
            2
        )
        . "<br>";


    echo "Projected xGC: "
        . number_format(
            (float) $highEvidence[
                'projected_xgc'
            ],
            2
        )
        . "<br>";


    echo "Expected Deduction: "
        . number_format(
            (float) $highEvidence[
                'expected_points'
            ],
            2
        )
        . "<br>";


    echo "Total Projected Points: "
        . number_format(
            (float) (
                $highestPlayer[
                    'projected_points'
                ]
                ?? 0
            ),
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
echo "Expected Goals Conceded Real Data Test Summary<br>";
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