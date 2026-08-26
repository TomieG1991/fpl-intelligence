<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Expected Points Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerExpectedPointsCheck(
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


$projection =
    new PlayerExpectedPoints(
        new ExpectedMinutes(),
        new ExpectedPointsInputs(),
        new ExpectedPoints(),
        new ProjectionConfidence()
    );


/*
 * ============================================================
 * BASE PLAYER / FORM
 * ============================================================
 */

$player = [

    'id' =>
        101,

    'fpl_player_id' =>
        501,

    'position' =>
        'MID',

    'status' =>
        'a',

    'chance_of_playing' =>
        null
];


$form = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'participation_rate' =>
        100,

    'raw_metrics' => [

        'average_appearance_minutes' =>
            90
    ],

    'weighted_metrics' => [

        'expected_goals_per_90' =>
            0.50,

        'expected_assists_per_90' =>
            0.25,

        'clean_sheet_rate' =>
            40.0
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * COMPLETE PROJECTION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Complete Projection Contract<br>";
echo "============================================<br>";


$model =
    $projection
        ->project(
            $player,
            $form,
            [
                'fixture_opportunity' =>
                    50,

                'opponent_attack_rating' =>
                    50
            ]
        );


playerExpectedPointsCheck(
    'Player Expected Points returns an array',
    is_array(
        $model
    )
);


playerExpectedPointsCheck(
    'Player ID is preserved',
    (
        (int) (
            $model[
                'player_id'
            ]
            ?? 0
        )
    )
    ===
    101
);


playerExpectedPointsCheck(
    'FPL Player ID is preserved',
    (
        (int) (
            $model[
                'fpl_player_id'
            ]
            ?? 0
        )
    )
    ===
    501
);


playerExpectedPointsCheck(
    'Position is preserved',
    (
        $model[
            'position'
        ]
        ?? null
    )
    ===
    'MID'
);


playerExpectedPointsCheck(
    'Projected Points is numeric',
    is_numeric(
        $model[
            'projected_points'
        ]
        ?? null
    )
);


playerExpectedPointsCheck(
    'Projected Minutes is numeric',
    is_numeric(
        $model[
            'projected_minutes'
        ]
        ?? null
    )
);


playerExpectedPointsCheck(
    'Projection Confidence is numeric',
    is_numeric(
        $model[
            'projection_confidence'
        ]
        ?? null
    )
);


playerExpectedPointsCheck(
    'Projection Confidence Percent is numeric',
    is_numeric(
        $model[
            'projection_confidence_percent'
        ]
        ?? null
    )
);


playerExpectedPointsCheck(
    'Projection Confidence Label is present',
    is_string(
        $model[
            'projection_confidence_label'
        ]
        ?? null
    )
);


echo "Projected Points: "
    . number_format(
        (float) (
            $model[
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
            $model[
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
            $model[
                'projection_confidence_percent'
            ]
            ?? 0
        ),
        2
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Bounds<br>";
echo "============================================<br>";


playerExpectedPointsCheck(
    'Projected Minutes remain between 0 and 90',
    (
        (float) $model[
            'projected_minutes'
        ]
    )
    >= 0
    &&
    (
        (float) $model[
            'projected_minutes'
        ]
    )
    <= 90
);


playerExpectedPointsCheck(
    'Projection Confidence remains between 0 and 1',
    (
        (float) $model[
            'projection_confidence'
        ]
    )
    >= 0
    &&
    (
        (float) $model[
            'projection_confidence'
        ]
    )
    <= 1
);


playerExpectedPointsCheck(
    'Projection Confidence Percent remains between 0 and 100',
    (
        (float) $model[
            'projection_confidence_percent'
        ]
    )
    >= 0
    &&
    (
        (float) $model[
            'projection_confidence_percent'
        ]
    )
    <= 100
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * FIXTURE EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Fixture Effect<br>";
echo "============================================<br>";


$poorFixture =
    $projection
        ->project(
            $player,
            $form,
            [
                'fixture_opportunity' =>
                    0,

                'opponent_attack_rating' =>
                    50
            ]
        );


$excellentFixture =
    $projection
        ->project(
            $player,
            $form,
            [
                'fixture_opportunity' =>
                    100,

                'opponent_attack_rating' =>
                    50
            ]
        );


playerExpectedPointsCheck(
    'Better attacking fixture produces higher projected points',
    (
        (float) (
            $excellentFixture[
                'projected_points'
            ]
            ?? 0
        )
    )
    >
    (
        (float) (
            $poorFixture[
                'projected_points'
            ]
            ?? 0
        )
    )
);


echo "Poor Fixture Projection: "
    . number_format(
        (float) (
            $poorFixture[
                'projected_points'
            ]
            ?? 0
        ),
        2
    )
    . "<br>";


echo "Excellent Fixture Projection: "
    . number_format(
        (float) (
            $excellentFixture[
                'projected_points'
            ]
            ?? 0
        ),
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * AVAILABILITY EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Availability Effect<br>";
echo "============================================<br>";


$fullyAvailable =
    $projection
        ->project(
            $player,
            $form
        );


$halfAvailablePlayer =
    $player;


$halfAvailablePlayer[
    'status'
] =
    'd';


$halfAvailablePlayer[
    'chance_of_playing'
] =
    50;


$halfAvailable =
    $projection
        ->project(
            $halfAvailablePlayer,
            $form
        );


playerExpectedPointsCheck(
    'Lower availability reduces projected minutes',
    (
        (float) (
            $halfAvailable[
                'projected_minutes'
            ]
            ?? 90
        )
    )
    <
    (
        (float) (
            $fullyAvailable[
                'projected_minutes'
            ]
            ?? 0
        )
    )
);


playerExpectedPointsCheck(
    'Lower availability reduces projected points',
    (
        (float) (
            $halfAvailable[
                'projected_points'
            ]
            ?? 999
        )
    )
    <
    (
        (float) (
            $fullyAvailable[
                'projected_points'
            ]
            ?? 0
        )
    )
);


echo "Fully Available Minutes: "
    . number_format(
        (float) $fullyAvailable[
            'projected_minutes'
        ],
        2
    )
    . "<br>";


echo "50% Available Minutes: "
    . number_format(
        (float) $halfAvailable[
            'projected_minutes'
        ],
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * NO HISTORY FALLBACK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: No-History Fallback<br>";
echo "============================================<br>";


$noHistory =
    $projection
        ->project(
            $player,
            []
        );


playerExpectedPointsCheck(
    'No-history player still receives controlled projected minutes',
    is_numeric(
        $noHistory[
            'projected_minutes'
        ]
        ?? null
    )
);


playerExpectedPointsCheck(
    'No-history Expected Minutes uses fallback evidence',
    (
        $noHistory[
            'expected_minutes_model'
        ][
            'evidence_source'
        ]
        ?? null
    )
    ===
    'Fallback'
);


playerExpectedPointsCheck(
    'No-history player still receives controlled Projected Points',
    is_numeric(
        $noHistory[
            'projected_points'
        ]
        ?? null
    )
);


playerExpectedPointsCheck(
    'No-history projection confidence is below complete evidence',
    (
        (float) (
            $noHistory[
                'projection_confidence_percent'
            ]
            ?? 100
        )
    )
    <
    (
        (float) (
            $fullyAvailable[
                'projection_confidence_percent'
            ]
            ?? 0
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * INVALID POSITION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Invalid Position<br>";
echo "============================================<br>";


$invalidPlayer =
    $player;


$invalidPlayer[
    'position'
] =
    'INVALID';


$invalidModel =
    $projection
        ->project(
            $invalidPlayer,
            $form
        );


playerExpectedPointsCheck(
    'Invalid position preserves Projected Points key',
    array_key_exists(
        'projected_points',
        $invalidModel
    )
);


playerExpectedPointsCheck(
    'Invalid position has unavailable Projected Points',
    $invalidModel[
        'projected_points'
    ]
    === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * COMPONENT TOTAL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Component Total<br>";
echo "============================================<br>";


$componentTotal =
    array_sum(
        $model[
            'components'
        ]
        ?? []
    );


playerExpectedPointsCheck(
    'Projected Points equals component total',
    abs(
        (
            (float) (
                $model[
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
 * ============================================================
 * SCENARIO H
 * EXPLAINABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Explainability<br>";
echo "============================================<br>";


playerExpectedPointsCheck(
    'Complete projection exposes component breakdown',
    isset(
        $model[
            'components'
        ]
    )
    &&
    is_array(
        $model[
            'components'
        ]
    )
);


playerExpectedPointsCheck(
    'Complete projection exposes input model',
    isset(
        $model[
            'inputs'
        ]
    )
    &&
    is_array(
        $model[
            'inputs'
        ]
    )
);


playerExpectedPointsCheck(
    'Complete projection exposes Expected Minutes model',
    isset(
        $model[
            'expected_minutes_model'
        ]
    )
    &&
    is_array(
        $model[
            'expected_minutes_model'
        ]
    )
);


playerExpectedPointsCheck(
    'Complete projection exposes Confidence model',
    isset(
        $model[
            'confidence_model'
        ]
    )
    &&
    is_array(
        $model[
            'confidence_model'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * SPECIALIST COMPONENT STATUS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Specialist Component Status<br>";
echo "============================================<br>";


$goalkeeperPlayer = [

    'id' =>
        202,

    'fpl_player_id' =>
        602,

    'position' =>
        'GK',

    'status' =>
        'a',

    'chance_of_playing' =>
        null
];


$goalkeeperForm = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'participation_rate' =>
        100,

    'raw_metrics' => [

        'average_appearance_minutes' =>
            90
    ],

    'weighted_metrics' => [

        'saves_per_90' =>
            4.0,
            
        'bps_per_90' =>
            35.0,

        'clean_sheet_rate' =>
            40.0
    ]
];


$goalkeeperProjection =
    $projection
        ->project(
            $goalkeeperPlayer,
            $goalkeeperForm,
            [
                'fixture_opportunity' =>
                    50,

                'opponent_attack_rating' =>
                    50
            ]
        );


$specialistComponents =
    $goalkeeperProjection[
        'inputs'
    ][
        'specialist_components'
    ]
    ?? [];


playerExpectedPointsCheck(
    'Saves are explicitly Modelled for goalkeeper projection data',
    (
        $specialistComponents[
            'saves'
        ]
        ?? null
    )
    ===
    'Modelled'
);


playerExpectedPointsCheck(
    'Bonus is explicitly Modelled',
    (
        $specialistComponents[
            'bonus'
        ]
        ?? null
    )
    ===
    'Modelled'
);

playerExpectedPointsCheck(
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

playerExpectedPointsCheck(
    'Goalkeeper projection includes positive expected saves',
    (
        (float) (
            $goalkeeperProjection[
                'inputs'
            ][
                'expected_saves'
            ]
            ?? 0
        )
    )
    > 0
);

playerExpectedPointsCheck(
    'Goalkeeper projection includes positive save points',
    (
        (float) (
            $goalkeeperProjection[
                'components'
            ][
                'saves'
            ]
            ?? 0
        )
    )
    > 0
);

playerExpectedPointsCheck(
    'Goalkeeper projection includes positive expected bonus points',
    (
        (float) (
            $goalkeeperProjection[
                'components'
            ][
                'bonus'
            ]
            ?? 0
        )
    )
    > 0
);


$defenderPlayer = [

    'id' =>
        303,

    'fpl_player_id' =>
        703,

    'position' =>
        'DEF',

    'status' =>
        'a',

    'chance_of_playing' =>
        null
];


$defenderForm = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'participation_rate' =>
        100,

    'raw_metrics' => [

        'average_appearance_minutes' =>
            90
    ],

    'weighted_metrics' => [

        'cbit_per_90' =>
            10.0,

        'clean_sheet_rate' =>
            40.0
    ]
];


$defenderProjection =
    $projection
        ->project(
            $defenderPlayer,
            $defenderForm,
            [
                'fixture_opportunity' =>
                    50,

                'opponent_attack_rating' =>
                    50
            ]
        );


playerExpectedPointsCheck(
    'Defender defensive contributions are Modelled',
    (
        $defenderProjection[
            'inputs'
        ][
            'specialist_components'
        ][
            'defensive_contributions'
        ]
        ?? null
    )
    ===
    'Modelled'
);


playerExpectedPointsCheck(
    'Defender receives positive expected defensive contribution points',
    (
        (float) (
            $defenderProjection[
                'components'
            ][
                'defensive_contributions'
            ]
            ?? 0
        )
    )
    > 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * ZERO PARTICIPATION SAFETY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Zero Participation Safety<br>";
echo "============================================<br>";


$zeroParticipationForm = [

    'form_rating' =>
        100,

    'performance_rating' =>
        100,

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        1,

    'participation_rate' =>
        0,

    'raw_metrics' => [

        'average_appearance_minutes' =>
            90
    ],

    'weighted_metrics' => [

        'expected_goals_per_90' =>
            2.0,

        'expected_assists_per_90' =>
            1.0,

        'clean_sheet_rate' =>
            100
    ]
];


$zeroParticipation =
    $projection
        ->project(
            $player,
            $zeroParticipationForm,
            [
                'fixture_opportunity' =>
                    100
            ]
        );


playerExpectedPointsCheck(
    'Perfect Form cannot override zero projected participation',
    abs(
        (
            (float) (
                $zeroParticipation[
                    'projected_minutes'
                ]
                ?? -1
            )
        )
    )
    < 0.001
);


playerExpectedPointsCheck(
    'Zero projected minutes prevent attacking projection from remaining high',
    abs(
        (
            (float) (
                $zeroParticipation[
                    'inputs'
                ][
                    'expected_goals'
                ]
                ?? -1
            )
        )
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * CONFIDENCE LABEL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Confidence Label Contract<br>";
echo "============================================<br>";


playerExpectedPointsCheck(
    'Projection confidence label uses supported state',
    in_array(
        (
            $model[
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
 * ============================================================
 * SCENARIO L
 * TOP-LEVEL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Top-Level Contract<br>";
echo "============================================<br>";


foreach (
    [
        'player_id',
        'fpl_player_id',
        'position',
        'projected_points',
        'projected_minutes',
        'projection_confidence',
        'projection_confidence_percent',
        'projection_confidence_label',
        'components',
        'inputs',
        'expected_minutes_model',
        'confidence_model'
    ]
    as $field
) {

    playerExpectedPointsCheck(
        'Player Expected Points exposes '
        . $field,
        array_key_exists(
            $field,
            $model
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Expected Points Test Summary<br>";
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