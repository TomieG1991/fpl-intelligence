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

function startingXiConfidenceEdgeCheck(
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

function buildStartingXiConfidenceEdgeSquad(
    callable $projectionBuilder
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


        /*
         * Players 2, 7, 13 and 15 are deliberately weaker.
         *
         * This gives us a deterministic legal Starting XI:
         *
         * GK  = 1
         * DEF = 3, 4, 5, 6
         * MID = 8, 9, 10, 11, 12
         * FWD = 14
         */

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


        $projection =
            $projectionBuilder(
                $playerNumber,
                $isBenchPlayer
            );


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

            'gameweeks' => [

                3 => [

                    'gameweek' =>
                        3,

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
                        20
                        +
                        $playerNumber,

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
                ]
            ]
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
    'Squad Horizon Starting XI Projection Confidence Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new SquadHorizonIntelligence();


/*
 * ============================================================
 * Scenario A: Zero-point Blank starter
 * ============================================================
 *
 * Player 1 is still selected because all alternative goalkeeper
 * projections are lower.
 *
 * Its zero projected points and null confidence must not affect
 * the confidence of the ten positive projected contributors.
 */

echo
    '============================================<br>';

echo
    'Scenario A: Zero-point Blank starter<br>';

echo
    '============================================<br>';


$blankStarterSquad =
    buildStartingXiConfidenceEdgeSquad(
        function (
            int $playerNumber,
            bool $isBenchPlayer
        ): array {

            if (
                $playerNumber === 1
            ) {

                return [

                    'projected_points' =>
                        0.0,

                    'projection_confidence' =>
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
                        ? -1.0
                        : 5.0,

                'projection_confidence' =>
                    0.80,

                'fixture_count' =>
                    1,

                'schedule_type' =>
                    'Normal'
            ];
        }
    );


$blankStarterResult =
    $intelligence->buildHorizon(
        $blankStarterSquad,
        1
    );


$blankStarterGameweek =
    $blankStarterResult[
        'gameweeks'
    ][3]
    ??
    [];


startingXiConfidenceEdgeCheck(
    'Blank-starter horizon remains available',
    (
        $blankStarterResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


startingXiConfidenceEdgeCheck(
    'Zero-point Blank starter remains in Starting XI',
    in_array(
        1,
        array_map(
            function (
                array $player
            ): int {

                return
                    (int) (
                        $player[
                            'player_id'
                        ]
                        ??
                        0
                    );
            },
            $blankStarterGameweek[
                'starting_xi'
            ]
            ??
            []
        ),
        true
    )
);


startingXiConfidenceEdgeCheck(
    'Zero-point Blank starter does not reduce confidence',
    isset(
        $blankStarterGameweek[
            'starting_xi_projection_confidence'
        ]
    )
    &&
    is_numeric(
        $blankStarterGameweek[
            'starting_xi_projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $blankStarterGameweek[
                'starting_xi_projection_confidence'
            ]
        )
        -
        0.80
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Positive points with missing confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Positive points with missing confidence<br>';

echo
    '============================================<br>';


$missingConfidenceSquad =
    buildStartingXiConfidenceEdgeSquad(
        function (
            int $playerNumber,
            bool $isBenchPlayer
        ): array {

            return [

                'projected_points' =>
                    $isBenchPlayer
                        ? 1.0
                        : 5.0,

                'projection_confidence' =>
                    $playerNumber === 1
                        ? null
                        : 0.80,

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
        1
    );


$missingConfidenceGameweek =
    $missingConfidenceResult[
        'gameweeks'
    ][3]
    ??
    [];


startingXiConfidenceEdgeCheck(
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


startingXiConfidenceEdgeCheck(
    'Positive-point player with missing confidence remains selected',
    in_array(
        1,
        array_map(
            function (
                array $player
            ): int {

                return
                    (int) (
                        $player[
                            'player_id'
                        ]
                        ??
                        0
                    );
            },
            $missingConfidenceGameweek[
                'starting_xi'
            ]
            ??
            []
        ),
        true
    )
);


startingXiConfidenceEdgeCheck(
    'Positive projected points with missing confidence make Starting XI confidence null',
    array_key_exists(
        'starting_xi_projection_confidence',
        $missingConfidenceGameweek
    )
    &&
    $missingConfidenceGameweek[
        'starting_xi_projection_confidence'
    ]
    ===
    null
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Entire Starting XI has zero projected points
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Entire Starting XI has zero projected points<br>';

echo
    '============================================<br>';


$zeroSquad =
    buildStartingXiConfidenceEdgeSquad(
        function (
            int $playerNumber,
            bool $isBenchPlayer
        ): array {

            return [

                'projected_points' =>
                    0.0,

                'projection_confidence' =>
                    $isBenchPlayer
                        ? null
                        : 0.80,

                'fixture_count' =>
                    0,

                'schedule_type' =>
                    'Blank'
            ];
        }
    );


$zeroResult =
    $intelligence->buildHorizon(
        $zeroSquad,
        1
    );


$zeroGameweek =
    $zeroResult[
        'gameweeks'
    ][3]
    ??
    [];


startingXiConfidenceEdgeCheck(
    'Zero-point horizon remains available',
    (
        $zeroResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


startingXiConfidenceEdgeCheck(
    'Zero-point Starting XI projected points remain zero',
    isset(
        $zeroGameweek[
            'starting_xi_projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $zeroGameweek[
                'starting_xi_projected_points'
            ]
        )
        -
        0.0
    ) < 0.0001
);


startingXiConfidenceEdgeCheck(
    'All-zero Starting XI has null projection confidence',
    array_key_exists(
        'starting_xi_projection_confidence',
        $zeroGameweek
    )
    &&
    $zeroGameweek[
        'starting_xi_projection_confidence'
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