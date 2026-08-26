<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Expected Points Real Data Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function realProjectionCheck(
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


realProjectionCheck(
    'Player Intelligence returns real player summaries',
    !empty(
        $summaries
    )
);


realProjectionCheck(
    'Current player pool contains at least 20 players',
    count(
        $summaries
    )
    >= 20
);


echo "<br>";


/*
 * ============================================================
 * FIND RAYA
 * ============================================================
 */

$raya =
    null;


foreach (
    $summaries
    as $summary
) {

    $webName =
        trim(
            (string) (
                $summary[
                    'web_name'
                ]
                ??
                $summary[
                    'player_name'
                ]
                ??
                $summary[
                    'name'
                ]
                ??
                ''
            )
        );


    if (
        strcasecmp(
            $webName,
            'Raya'
        )
        === 0
    ) {

        $raya =
            $summary;

        break;
    }
}


/*
 * ============================================================
 * SCENARIO B
 * RAYA RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Raya Resolution<br>";
echo "============================================<br>";


realProjectionCheck(
    'Raya resolves from Player Intelligence',
    is_array(
        $raya
    )
);


if (
    $raya === null
) {

    echo "<br>";
    echo "Raya could not be resolved. Remaining scenarios cannot run.";
    echo "<br><br>";

} else {

    echo "Player ID: "
        . htmlspecialchars(
            (string) (
                $raya[
                    'id'
                ]
                ??
                $raya[
                    'player_id'
                ]
                ??
                'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Player: Raya<br>";


    echo "Position: "
        . htmlspecialchars(
            (string) (
                $raya[
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
     * PROJECTION CONTRACT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Projection Contract<br>";
    echo "============================================<br>";


    realProjectionCheck(
        'Raya has Projected Points available',
        (
            $raya[
                'has_projected_points'
            ]
            ?? false
        )
        === true
    );


    realProjectionCheck(
        'Raya Projected Points is numeric',
        is_numeric(
            $raya[
                'projected_points'
            ]
            ?? null
        )
    );


    realProjectionCheck(
        'Raya Projected Minutes is numeric',
        is_numeric(
            $raya[
                'projected_minutes'
            ]
            ?? null
        )
    );


    realProjectionCheck(
        'Raya Projected Minutes remains between 0 and 90',
        is_numeric(
            $raya[
                'projected_minutes'
            ]
            ?? null
        )
        &&
        (
            (float) $raya[
                'projected_minutes'
            ]
        )
        >= 0
        &&
        (
            (float) $raya[
                'projected_minutes'
            ]
        )
        <= 90
    );


    realProjectionCheck(
        'Raya Projection Confidence Percent is numeric',
        is_numeric(
            $raya[
                'projection_confidence_percent'
            ]
            ?? null
        )
    );


    realProjectionCheck(
        'Raya Projection Confidence Percent remains between 0 and 100',
        is_numeric(
            $raya[
                'projection_confidence_percent'
            ]
            ?? null
        )
        &&
        (
            (float) $raya[
                'projection_confidence_percent'
            ]
        )
        >= 0
        &&
        (
            (float) $raya[
                'projection_confidence_percent'
            ]
        )
        <= 100
    );


    realProjectionCheck(
        'Raya Projection Confidence Label uses supported state',
        in_array(
            (
                $raya[
                    'projection_confidence_label'
                ]
                ?? null
            ),
            [
                'High',
                'Moderate',
                'Low',
                'Very Low'
            ],
            true
        )
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO D
     * EXPECTED POINTS INPUTS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Expected Points Inputs<br>";
    echo "============================================<br>";


    $inputs =
        $raya[
            'projected_points_inputs'
        ]
        ?? [];


    realProjectionCheck(
        'Raya exposes Expected Points inputs',
        is_array(
            $inputs
        )
        &&
        !empty(
            $inputs
        )
    );


    realProjectionCheck(
        'Raya Expected Goals is numeric',
        is_numeric(
            $inputs[
                'expected_goals'
            ]
            ?? null
        )
    );


    realProjectionCheck(
        'Raya Expected Assists is numeric',
        is_numeric(
            $inputs[
                'expected_assists'
            ]
            ?? null
        )
    );


    realProjectionCheck(
        'Raya Clean Sheet Probability is numeric',
        is_numeric(
            $inputs[
                'clean_sheet_probability'
            ]
            ?? null
        )
    );


    realProjectionCheck(
        'Raya Clean Sheet Probability remains between 0 and 100',
        is_numeric(
            $inputs[
                'clean_sheet_probability'
            ]
            ?? null
        )
        &&
        (
            (float) $inputs[
                'clean_sheet_probability'
            ]
        )
        >= 0
        &&
        (
            (float) $inputs[
                'clean_sheet_probability'
            ]
        )
        <= 100
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * COMPONENT BREAKDOWN
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Component Breakdown<br>";
    echo "============================================<br>";


    $components =
        $raya[
            'projected_points_components'
        ]
        ?? [];


    realProjectionCheck(
        'Raya exposes Expected Points component breakdown',
        is_array(
            $components
        )
        &&
        !empty(
            $components
        )
    );


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

        realProjectionCheck(
            'Raya component is numeric: '
            . $component,
            array_key_exists(
                $component,
                $components
            )
            &&
            is_numeric(
                $components[
                    $component
                ]
            )
        );
    }


    $componentTotal =
        array_sum(
            $components
        );


    realProjectionCheck(
        'Raya Projected Points equals component total',
        is_numeric(
            $raya[
                'projected_points'
            ]
            ?? null
        )
        &&
        abs(
            (
                (float) $raya[
                    'projected_points'
                ]
            )
            -
            $componentTotal
        )
        < 0.011
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * SPECIALIST COMPONENT STATUS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Specialist Component Status<br>";
    echo "============================================<br>";


    $specialistComponents =
        $inputs[
            'specialist_components'
        ]
        ?? [];


    realProjectionCheck(
        'Goalkeeper saves are explicitly Modelled',
        (
            $specialistComponents[
                'saves'
            ]
            ?? null
        )
        ===
        'Modelled'
    );


    realProjectionCheck(
        'Bonus is explicitly Not Yet Modelled',
        (
            $specialistComponents[
                'bonus'
            ]
            ?? null
        )
        ===
        'Modelled'
    );


    realProjectionCheck(
        'Defensive contributions are Not Applicable to goalkeeper',
        (
            $specialistComponents[
                'defensive_contributions'
            ]
            ?? null
        )
        ===
        'Not Applicable'
    );


    realProjectionCheck(
        'Raya receives positive projected goalkeeper save points',
        (
            (float) (
                $components[
                    'saves'
                ]
                ?? 0
            )
        )
        > 0
    );


    realProjectionCheck(
        'Modelled bonus component is non-negative',
        (
            (float) (
                $components[
                    'bonus'
                ]
                ?? -1
            )
        )
        >= 0
    );


    realProjectionCheck(
        'Goalkeeper defensive contribution component remains zero',
        abs(
            (float) (
                $components[
                    'defensive_contributions'
                ]
                ?? -1
            )
        )
        < 0.001
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO G
     * REAL PROJECTION DIAGNOSTIC
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Raya Projection Diagnostic<br>";
    echo "============================================<br>";


    echo "Projected Points: "
        . number_format(
            (float) (
                $raya[
                    'projected_points'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Projected Minutes: "
        . number_format(
            (float) (
                $raya[
                    'projected_minutes'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Projection Confidence: "
        . number_format(
            (float) (
                $raya[
                    'projection_confidence_percent'
                ]
                ?? 0
            ),
            2
        )
        . "%";


    echo " ("
        . htmlspecialchars(
            (string) (
                $raya[
                    'projection_confidence_label'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . ")<br><br>";


    echo "Expected Goals: "
        . number_format(
            (float) (
                $inputs[
                    'expected_goals'
                ]
                ?? 0
            ),
            4
        )
        . "<br>";


    echo "Expected Assists: "
        . number_format(
            (float) (
                $inputs[
                    'expected_assists'
                ]
                ?? 0
            ),
            4
        )
        . "<br>";


    echo "Clean Sheet Probability: "
        . number_format(
            (float) (
                $inputs[
                    'clean_sheet_probability'
                ]
                ?? 0
            ),
            2
        )
        . "%<br><br>";
        
    echo "Goals Conceded: "
        . number_format(
            (float) (
                $components[
                    'goals_conceded'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Appearance: "
        . number_format(
            (float) (
                $components[
                    'appearance'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Goals: "
        . number_format(
            (float) (
                $components[
                    'goals'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Assists: "
        . number_format(
            (float) (
                $components[
                    'assists'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Clean Sheet: "
        . number_format(
            (float) (
                $components[
                    'clean_sheet'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Saves: "
        . number_format(
            (float) (
                $components[
                    'saves'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Bonus: "
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
        
    $bonusEvidence =
        $inputs[
            'evidence'
        ][
            'bonus'
        ]
        ?? [];


    echo "<br>";

    echo "Bonus Model Status: "
        . (
            $bonusEvidence[
                'status'
            ]
            ?? 'Unavailable'
        )
        . "<br>";


    echo "Raw BPS / 90: "
        . (
            isset(
                $bonusEvidence[
                    'bps_per_90'
                ]
            )
                ? number_format(
                    (float) $bonusEvidence[
                        'bps_per_90'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Position BPS Baseline: "
        . (
            isset(
                $bonusEvidence[
                    'position_baseline'
                ]
            )
                ? number_format(
                    (float) $bonusEvidence[
                        'position_baseline'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Bonus Appearance Sample: "
        . (
            $bonusEvidence[
                'appearance_sample_size'
            ]
            ?? 0
        )
        . "<br>";


    echo "Bonus Sample Confidence: "
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
        
    $goalsConcededEvidence =
        $inputs[
            'evidence'
        ][
            'goals_conceded'
        ]
        ?? [];


    echo "<br>";

    echo "Goals Conceded Model Status: "
        . (
            $goalsConcededEvidence[
                'status'
            ]
            ?? 'Unavailable'
        )
        . "<br>";


    echo "Raw xGC / 90: "
        . (
            isset(
                $goalsConcededEvidence[
                    'raw_xgc_per_90'
                ]
            )
                ? number_format(
                    (float) $goalsConcededEvidence[
                        'raw_xgc_per_90'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Position xGC Baseline: "
        . (
            isset(
                $goalsConcededEvidence[
                    'position_baseline'
                ]
            )
                ? number_format(
                    (float) $goalsConcededEvidence[
                        'position_baseline'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "xGC Appearance Sample: "
        . (
            $goalsConcededEvidence[
                'appearance_sample_size'
            ]
            ?? 0
        )
        . "<br>";


    echo "xGC Sample Confidence: "
        . number_format(
            (float) (
                $goalsConcededEvidence[
                    'sample_confidence_percent'
                ]
                ?? 0
            ),
            2
        )
        . "%<br>";


    echo "Regressed xGC / 90: "
        . (
            isset(
                $goalsConcededEvidence[
                    'regressed_xgc_per_90'
                ]
            )
                ? number_format(
                    (float) $goalsConcededEvidence[
                        'regressed_xgc_per_90'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Opponent Attack Rating: "
        . (
            isset(
                $goalsConcededEvidence[
                    'opponent_attack_rating'
                ]
            )
                ? number_format(
                    (float) $goalsConcededEvidence[
                        'opponent_attack_rating'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Goals Conceded Fixture Multiplier: "
        . number_format(
            (float) (
                $goalsConcededEvidence[
                    'fixture_multiplier'
                ]
                ?? 1
            ),
            3
        )
        . "<br>";


    echo "Projected xGC: "
        . number_format(
            (float) (
                $goalsConcededEvidence[
                    'projected_xgc'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Expected Goals-Conceded Deduction: "
        . number_format(
            (float) (
                $goalsConcededEvidence[
                    'expected_points'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Regressed BPS / 90: "
        . (
            isset(
                $bonusEvidence[
                    'regressed_bps_per_90'
                ]
            )
                ? number_format(
                    (float) $bonusEvidence[
                        'regressed_bps_per_90'
                    ],
                    2
                )
                : 'Unavailable'
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


    echo "Defensive Contributions: "
        . number_format(
            (float) (
                $components[
                    'defensive_contributions'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO H
     * EARLY-SEASON EVIDENCE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Early-Season Evidence<br>";
    echo "============================================<br>";


    $sample =
        $inputs[
            'sample'
        ]
        ?? [];


    $evidence =
        $inputs[
            'evidence'
        ]
        ?? [];


    realProjectionCheck(
        'Raya projection exposes fixture sample size',
        array_key_exists(
            'fixture_sample_size',
            $sample
        )
    );


    realProjectionCheck(
        'Raya currently has at least one fixture of projection evidence',
        (
            (int) (
                $sample[
                    'fixture_sample_size'
                ]
                ?? 0
            )
        )
        >= 1
    );


    realProjectionCheck(
        'Clean-sheet sample confidence remains bounded',
        is_numeric(
            $evidence[
                'clean_sheet_sample_confidence'
            ]
            ?? null
        )
        &&
        (
            (float) $evidence[
                'clean_sheet_sample_confidence'
            ]
        )
        >= 0
        &&
        (
            (float) $evidence[
                'clean_sheet_sample_confidence'
            ]
        )
        <= 1
    );


    echo "Fixture Sample Size: "
        . (int) (
            $sample[
                'fixture_sample_size'
            ]
            ?? 0
        )
        . "<br>";


    echo "Appearance Sample Size: "
        . (int) (
            $sample[
                'appearance_sample_size'
            ]
            ?? 0
        )
        . "<br>";


    echo "Clean Sheet Sample Confidence: "
        . number_format(
            (
                (float) (
                    $evidence[
                        'clean_sheet_sample_confidence'
                    ]
                    ?? 0
                )
            )
            *
            100,
            2
        )
        . "%<br><br>";
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Expected Points Real Data Test Summary<br>";
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