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

function horizonProjectionConfidenceEdgeCheck(
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
 * SQUAD BUILDER
 * ============================================================
 */

function buildHorizonConfidenceEdgeSquad(
    callable $gameweekBuilder
): array {

    $squad =
        [];


    for (
        $playerNumber = 1;
        $playerNumber <= 15;
        $playerNumber++
    ) {

        $position =
            match (true) {

                $playerNumber <= 2 =>
                    'GK',

                $playerNumber <= 7 =>
                    'DEF',

                $playerNumber <= 12 =>
                    'MID',

                default =>
                    'FWD'
            };


        $isBenchPlayer =
            in_array(
                $playerNumber,
                [
                    2,
                    7,
                    13,
                    15
                ],
                true
            );


        $gameweeks =
            [];


        foreach (
            [3, 4, 5]
            as $gameweek
        ) {

            $projection =
                $gameweekBuilder(
                    $gameweek,
                    $playerNumber,
                    $isBenchPlayer
                );


            $gameweeks[
                $gameweek
            ] = [

                'gameweek' =>
                    $gameweek,

                'projected_points' =>
                    $projection[
                        'projected_points'
                    ],

                'projection_confidence' =>
                    $projection[
                        'projection_confidence'
                    ],

                'team_id' =>
                    $playerNumber,

                'opponent_team_id' =>
                    $projection[
                        'opponent_team_id'
                    ],

                'fixture_count' =>
                    $projection[
                        'fixture_count'
                    ],

                'schedule_type' =>
                    $projection[
                        'schedule_type'
                    ],

                'fixtures' =>
                    []
            ];
        }


        $squad[] = [

            'player_id' =>
                $playerNumber,

            'name' =>
                'Player '
                . $playerNumber,

            'position' =>
                $position,

            'team_id' =>
                $playerNumber,

            'gameweeks' =>
                $gameweeks
        ];
    }


    return
        $squad;
}


/*
 * ============================================================
 * START
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Squad Horizon Projection Confidence Aggregation Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new SquadHorizonIntelligence();


/*
 * ============================================================
 * Scenario A: Zero-point gameweek is ignored
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Zero-point gameweek is ignored<br>';

echo
    '============================================<br>';


$zeroGameweekSquad =
    buildHorizonConfidenceEdgeSquad(
        function (
            int $gameweek,
            int $playerNumber,
            bool $isBenchPlayer
        ): array {

            if (
                $gameweek === 4
            ) {

                return [

                    'projected_points' =>
                        0.0,

                    'projection_confidence' =>
                        null,

                    'opponent_team_id' =>
                        null,

                    'fixture_count' =>
                        0,

                    'schedule_type' =>
                        'Blank'
                ];
            }


            return [

                'projected_points' =>
                    $isBenchPlayer
                        ? 1.0
                        : 5.0,

                'projection_confidence' =>
                    $gameweek === 3
                        ? 0.80
                        : 0.60,

                'opponent_team_id' =>
                    20
                    +
                    $playerNumber,

                'fixture_count' =>
                    1,

                'schedule_type' =>
                    'Normal'
            ];
        }
    );


$zeroGameweekResult =
    $intelligence->buildHorizon(
        $zeroGameweekSquad,
        3
    );


horizonProjectionConfidenceEdgeCheck(
    'Zero-gameweek horizon remains available',
    (
        $zeroGameweekResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


horizonProjectionConfidenceEdgeCheck(
    'Zero-point gameweek does not invalidate horizon confidence',
    isset(
        $zeroGameweekResult[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $zeroGameweekResult[
            'projection_confidence'
        ]
    )
);


horizonProjectionConfidenceEdgeCheck(
    'Zero-point gameweek is excluded from weighting',
    isset(
        $zeroGameweekResult[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $zeroGameweekResult[
                'projection_confidence'
            ]
        )
        -
        0.70
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Positive-point GW with missing confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Positive-point GW with missing confidence<br>';

echo
    '============================================<br>';


$missingConfidenceSquad =
    buildHorizonConfidenceEdgeSquad(
        function (
            int $gameweek,
            int $playerNumber,
            bool $isBenchPlayer
        ): array {

            if (
                $gameweek === 4
            ) {

                return [

                    'projected_points' =>
                        $isBenchPlayer
                            ? 1.0
                            : 5.0,

                    'projection_confidence' =>
                        $playerNumber === 1
                            ? null
                            : 0.70,

                    'opponent_team_id' =>
                        20
                        +
                        $playerNumber,

                    'fixture_count' =>
                        1,

                    'schedule_type' =>
                        'Normal'
                ];
            }


            return [

                'projected_points' =>
                    $isBenchPlayer
                        ? 1.0
                        : 5.0,

                'projection_confidence' =>
                    0.80,

                'opponent_team_id' =>
                    20
                    +
                    $playerNumber,

                'fixture_count' =>
                    1,

                'schedule_type' =>
                    'Normal'
            ];
        }
    );


$missingConfidenceResult =
    $intelligence->buildHorizon(
        $missingConfidenceSquad,
        3
    );


horizonProjectionConfidenceEdgeCheck(
    'Missing-confidence horizon remains available',
    (
        $missingConfidenceResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


horizonProjectionConfidenceEdgeCheck(
    'Positive-point gameweek with missing confidence makes horizon confidence null',
    array_key_exists(
        'projection_confidence',
        $missingConfidenceResult
    )
    &&
    $missingConfidenceResult[
        'projection_confidence'
    ]
    ===
    null
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Entire horizon has zero projected points
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Entire horizon has zero projected points<br>';

echo
    '============================================<br>';


$allZeroSquad =
    buildHorizonConfidenceEdgeSquad(
        function (
            int $gameweek,
            int $playerNumber,
            bool $isBenchPlayer
        ): array {

            return [

                'projected_points' =>
                    0.0,

                'projection_confidence' =>
                    null,

                'opponent_team_id' =>
                    null,

                'fixture_count' =>
                    0,

                'schedule_type' =>
                    'Blank'
            ];
        }
    );


$allZeroResult =
    $intelligence->buildHorizon(
        $allZeroSquad,
        3
    );


horizonProjectionConfidenceEdgeCheck(
    'All-zero horizon remains available',
    (
        $allZeroResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


horizonProjectionConfidenceEdgeCheck(
    'All-zero horizon exposes projection confidence',
    array_key_exists(
        'projection_confidence',
        $allZeroResult
    )
);


horizonProjectionConfidenceEdgeCheck(
    'All-zero horizon has null projection confidence',
    $allZeroResult[
        'projection_confidence'
    ]
    ===
    null
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