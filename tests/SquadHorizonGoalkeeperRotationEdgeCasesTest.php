<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Goalkeeper Rotation Edge Cases Test<br>";
echo "v0.32.0 — Squad Horizon & Rotation Intelligence<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function goalkeeperEdgeCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        $passed++;

        return;
    }


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    $failed++;
}


function goalkeeperEdgeHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
}


/**
 * Build one synthetic player with projections for
 * GW2, GW3 and GW4.
 */
function buildGoalkeeperEdgePlayer(
    int $playerId,
    string $name,
    string $position,
    $gw2,
    $gw3,
    $gw4
): array {

    return [
        'player_id' =>
            $playerId,

        'name' =>
            $name,

        'position' =>
            $position,

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    $gw2
            ],

            3 => [
                'gameweek' =>
                    3,

                'projected_points' =>
                    $gw3
            ],

            4 => [
                'gameweek' =>
                    4,

                'projected_points' =>
                    $gw4
            ]
        ]
    ];
}


/**
 * Build a complete legal 15-player squad while allowing
 * the two goalkeeper projection profiles to vary.
 *
 * Goalkeeper B is deliberately placed BEFORE Goalkeeper A
 * in the squad array.
 *
 * This means deterministic tie handling cannot accidentally
 * depend on original array order.
 */
function buildGoalkeeperEdgeSquad(
    array $goalkeeperAProjections,
    array $goalkeeperBProjections
): array {

    return [

        /*
         * --------------------------------------------------------
         * GOALKEEPERS
         * --------------------------------------------------------
         */

        buildGoalkeeperEdgePlayer(
            2,
            'Goalkeeper B',
            'GK',
            $goalkeeperBProjections[2],
            $goalkeeperBProjections[3],
            $goalkeeperBProjections[4]
        ),

        buildGoalkeeperEdgePlayer(
            1,
            'Goalkeeper A',
            'GK',
            $goalkeeperAProjections[2],
            $goalkeeperAProjections[3],
            $goalkeeperAProjections[4]
        ),


        /*
         * --------------------------------------------------------
         * DEFENDERS
         * --------------------------------------------------------
         */

        buildGoalkeeperEdgePlayer(
            3,
            'Defender A',
            'DEF',
            6.0,
            6.0,
            6.0
        ),

        buildGoalkeeperEdgePlayer(
            4,
            'Defender B',
            'DEF',
            5.5,
            5.5,
            5.5
        ),

        buildGoalkeeperEdgePlayer(
            5,
            'Defender C',
            'DEF',
            5.0,
            5.0,
            5.0
        ),

        buildGoalkeeperEdgePlayer(
            6,
            'Defender D',
            'DEF',
            2.0,
            2.0,
            2.0
        ),

        buildGoalkeeperEdgePlayer(
            7,
            'Defender E',
            'DEF',
            1.5,
            1.5,
            1.5
        ),


        /*
         * --------------------------------------------------------
         * MIDFIELDERS
         * --------------------------------------------------------
         */

        buildGoalkeeperEdgePlayer(
            8,
            'Midfielder A',
            'MID',
            8.0,
            8.0,
            8.0
        ),

        buildGoalkeeperEdgePlayer(
            9,
            'Midfielder B',
            'MID',
            7.5,
            7.5,
            7.5
        ),

        buildGoalkeeperEdgePlayer(
            10,
            'Midfielder C',
            'MID',
            7.0,
            7.0,
            7.0
        ),

        buildGoalkeeperEdgePlayer(
            11,
            'Midfielder D',
            'MID',
            6.5,
            6.5,
            6.5
        ),

        buildGoalkeeperEdgePlayer(
            12,
            'Midfielder E',
            'MID',
            6.0,
            6.0,
            6.0
        ),


        /*
         * --------------------------------------------------------
         * FORWARDS
         * --------------------------------------------------------
         */

        buildGoalkeeperEdgePlayer(
            13,
            'Forward A',
            'FWD',
            9.0,
            9.0,
            9.0
        ),

        buildGoalkeeperEdgePlayer(
            14,
            'Forward B',
            'FWD',
            8.5,
            8.5,
            8.5
        ),

        buildGoalkeeperEdgePlayer(
            15,
            'Forward C',
            'FWD',
            4.0,
            4.0,
            4.0
        )
    ];
}


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$model =
    new SquadHorizonIntelligence();


/*
 * ============================================================
 * SCENARIO A
 * BOTH GOALKEEPER PROJECTIONS MISSING
 * ============================================================
 *
 *                GW2    GW3    GW4
 *
 * Goalkeeper A   5.0    null   5.0
 * Goalkeeper B   3.0    null   2.0
 *
 * GW3 contains no usable projection for either goalkeeper.
 *
 * We must NOT fabricate:
 *
 * A = 0
 * B = 0
 *
 * and then choose one through the normal tie-break.
 *
 * The correct preference is:
 *
 * [1, null, 1]
 *
 * The missing gameweek also breaks alternation continuity.
 * Therefore A -> unknown -> A is NOT an alternation.
 * ============================================================
 */

$missingProjectionSquad =
    buildGoalkeeperEdgeSquad(
        [
            2 => 5.0,
            3 => null,
            4 => 5.0
        ],
        [
            2 => 3.0,
            3 => null,
            4 => 2.0
        ]
    );


$missingProjectionResult =
    $model->buildHorizon(
        $missingProjectionSquad,
        3
    );


$missingProjectionRotation =
    $missingProjectionResult[
        'goalkeeper_rotation'
    ]
    ?? [];


goalkeeperEdgeHeading(
    'Scenario A: Missing Projection Preservation'
);


goalkeeperEdgeCheck(
    'Goalkeeper rotation intelligence remains available',
    isset(
        $missingProjectionResult[
            'goalkeeper_rotation'
        ]
    )
    &&
    is_array(
        $missingProjectionRotation
    )
);


goalkeeperEdgeCheck(
    'Missing goalkeeper projections produce an unknown preference',
    (
        $missingProjectionRotation[
            'preferred_goalkeeper_ids'
        ]
        ?? null
    )
    ===
    [
        1,
        null,
        1
    ]
);


goalkeeperEdgeCheck(
    'Missing gameweek does not create a false goalkeeper alternation',
    (
        $missingProjectionRotation[
            'alternation_count'
        ]
        ?? null
    )
    ===
    0
);


goalkeeperEdgeCheck(
    'Rotating total excludes the unknown gameweek rather than fabricating points',
    is_numeric(
        $missingProjectionRotation[
            'rotating_projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $missingProjectionRotation[
            'rotating_projected_points'
        ]
        -
        10.0
    )
    <
    0.001
);


goalkeeperEdgeCheck(
    'Goalkeeper A remains the best single goalkeeper',
    (
        $missingProjectionRotation[
            'best_single_goalkeeper'
        ][
            'player_id'
        ]
        ?? null
    )
    ===
    1
);


goalkeeperEdgeCheck(
    'Best single goalkeeper total uses only available projections',
    is_numeric(
        $missingProjectionRotation[
            'best_single_goalkeeper'
        ][
            'projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $missingProjectionRotation[
            'best_single_goalkeeper'
        ][
            'projected_points'
        ]
        -
        10.0
    )
    <
    0.001
);


goalkeeperEdgeCheck(
    'Missing projections do not manufacture a rotation gain',
    is_numeric(
        $missingProjectionRotation[
            'rotation_gain'
        ]
        ?? null
    )
    &&
    abs(
        (float) $missingProjectionRotation[
            'rotation_gain'
        ]
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO B
 * DETERMINISTIC TIE HANDLING
 * ============================================================
 *
 * Both goalkeepers have exactly the same projection in all
 * three gameweeks.
 *
 * Goalkeeper B appears FIRST in the original squad array,
 * but Goalkeeper A has the lower player ID.
 *
 * The deterministic rule is therefore:
 *
 * lower player_id wins an exact projection tie.
 *
 * Expected:
 *
 * preferred sequence = [1, 1, 1]
 * best single GK     = 1
 * alternations       = 0
 * rotation gain      = 0
 * ============================================================
 */

$tiedProjectionSquad =
    buildGoalkeeperEdgeSquad(
        [
            2 => 5.0,
            3 => 5.0,
            4 => 5.0
        ],
        [
            2 => 5.0,
            3 => 5.0,
            4 => 5.0
        ]
    );


$tiedProjectionResult =
    $model->buildHorizon(
        $tiedProjectionSquad,
        3
    );


$tiedProjectionRotation =
    $tiedProjectionResult[
        'goalkeeper_rotation'
    ]
    ?? [];


goalkeeperEdgeHeading(
    'Scenario B: Deterministic Tie Handling'
);


goalkeeperEdgeCheck(
    'Equal projections prefer the lower goalkeeper player ID',
    (
        $tiedProjectionRotation[
            'preferred_goalkeeper_ids'
        ]
        ?? null
    )
    ===
    [
        1,
        1,
        1
    ]
);


goalkeeperEdgeCheck(
    'Equal projections produce no goalkeeper alternations',
    (
        $tiedProjectionRotation[
            'alternation_count'
        ]
        ?? null
    )
    ===
    0
);


goalkeeperEdgeCheck(
    'Goalkeeper A wins the best-single-goalkeeper tie',
    (
        $tiedProjectionRotation[
            'best_single_goalkeeper'
        ][
            'player_id'
        ]
        ?? null
    )
    ===
    1
);


goalkeeperEdgeCheck(
    'Tied best single goalkeeper total equals 15.0',
    is_numeric(
        $tiedProjectionRotation[
            'best_single_goalkeeper'
        ][
            'projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $tiedProjectionRotation[
            'best_single_goalkeeper'
        ][
            'projected_points'
        ]
        -
        15.0
    )
    <
    0.001
);


goalkeeperEdgeCheck(
    'Equal goalkeeper projections produce zero rotation gain',
    is_numeric(
        $tiedProjectionRotation[
            'rotation_gain'
        ]
        ?? null
    )
    &&
    abs(
        (float) $tiedProjectionRotation[
            'rotation_gain'
        ]
    )
    <
    0.001
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";

echo
    'Passed: '
    . $passed
    . '<br>';

echo
    'Failed: '
    . $failed
    . '<br><br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}