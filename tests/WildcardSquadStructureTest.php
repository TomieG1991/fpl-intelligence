<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Wildcard Squad Structure Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function wildcardStructureTest(
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


function structurePlayer(
    int $id,
    string $name,
    string $position,
    float $score,
    float $price = 5.0,
    int $teamId = 1
): array {

    return [

        'player_id' =>
            $id,

        'name' =>
            $name,

        'team_id' =>
            $teamId,

        'team_name' =>
            'Team '
            . $teamId,

        'position' =>
            $position,

        'price' =>
            $price,

        'intelligence_score' =>
            $score,

        'wildcard_score' =>
            $score
    ];
}


/*
 * ============================================================
 * SYNTHETIC LEGAL SQUAD
 * ============================================================
 */

$squad = [

    structurePlayer(
        1,
        'Goalkeeper A',
        'GK',
        80,
        5.5,
        1
    ),

    structurePlayer(
        2,
        'Goalkeeper B',
        'GK',
        50,
        4.0,
        2
    ),


    structurePlayer(
        3,
        'Defender A',
        'DEF',
        78,
        6.0,
        3
    ),

    structurePlayer(
        4,
        'Defender B',
        'DEF',
        75,
        5.5,
        4
    ),

    structurePlayer(
        5,
        'Defender C',
        'DEF',
        72,
        5.0,
        5
    ),

    structurePlayer(
        6,
        'Defender D',
        'DEF',
        55,
        4.5,
        6
    ),

    structurePlayer(
        7,
        'Defender E',
        'DEF',
        45,
        4.0,
        7
    ),


    structurePlayer(
        8,
        'Midfielder A',
        'MID',
        90,
        12.0,
        8
    ),

    structurePlayer(
        9,
        'Midfielder B',
        'MID',
        86,
        10.0,
        9
    ),

    structurePlayer(
        10,
        'Midfielder C',
        'MID',
        82,
        8.0,
        10
    ),

    structurePlayer(
        11,
        'Midfielder D',
        'MID',
        76,
        7.0,
        11
    ),

    structurePlayer(
        12,
        'Midfielder E',
        'MID',
        40,
        4.5,
        12
    ),


    structurePlayer(
        13,
        'Forward A',
        'FWD',
        88,
        12.0,
        13
    ),

    structurePlayer(
        14,
        'Forward B',
        'FWD',
        84,
        9.0,
        14
    ),

    structurePlayer(
        15,
        'Forward C',
        'FWD',
        79,
        7.0,
        15
    )
];


$structure =
    new WildcardSquadStructure();


echo "============================================<br>";
echo "Scenario A: Valid Squad Analysis<br>";
echo "============================================<br>";


$result =
    $structure
        ->analyze(
            $squad
        );


wildcardStructureTest(
    'Structure analysis returns an array',
    is_array(
        $result
    )
);


wildcardStructureTest(
    'Valid squad returns success',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


wildcardStructureTest(
    'Starting XI contains 11 players',
    count(
        $result[
            'starting_xi'
        ]
        ?? []
    )
    === 11
);


wildcardStructureTest(
    'Bench contains four players',
    count(
        $result[
            'bench'
        ]
        ?? []
    )
    === 4
);


wildcardStructureTest(
    'Formation is returned',
    !empty(
        $result[
            'formation'
        ]
        ?? null
    )
);


wildcardStructureTest(
    'Starting XI score is numeric',
    is_numeric(
        $result[
            'starting_xi_score'
        ]
        ?? null
    )
);


wildcardStructureTest(
    'Bench score is numeric',
    is_numeric(
        $result[
            'bench_score'
        ]
        ?? null
    )
);


wildcardStructureTest(
    'Structure score is numeric',
    is_numeric(
        $result[
            'structure_score'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * FORMATION LEGALITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Formation Legality<br>";
echo "============================================<br>";


$starterCounts = [

    'GK' => 0,
    'DEF' => 0,
    'MID' => 0,
    'FWD' => 0
];


foreach (
    $result[
        'starting_xi'
    ]
    ?? []
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? null;


    if (
        isset(
            $starterCounts[
                $position
            ]
        )
    ) {

        $starterCounts[
            $position
        ]++;
    }
}


wildcardStructureTest(
    'Starting XI contains one goalkeeper',
    $starterCounts[
        'GK'
    ]
    === 1
);


wildcardStructureTest(
    'Starting XI contains at least three defenders',
    $starterCounts[
        'DEF'
    ]
    >= 3
);


wildcardStructureTest(
    'Starting XI contains at least two midfielders',
    $starterCounts[
        'MID'
    ]
    >= 2
);


wildcardStructureTest(
    'Starting XI contains at least one forward',
    $starterCounts[
        'FWD'
    ]
    >= 1
);


wildcardStructureTest(
    'Starting XI contains no more than five defenders',
    $starterCounts[
        'DEF'
    ]
    <= 5
);


wildcardStructureTest(
    'Starting XI contains no more than five midfielders',
    $starterCounts[
        'MID'
    ]
    <= 5
);


wildcardStructureTest(
    'Starting XI contains no more than three forwards',
    $starterCounts[
        'FWD'
    ]
    <= 3
);


echo "<br>";


/*
 * ============================================================
 * BEST FORMATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Best Formation Selection<br>";
echo "============================================<br>";


wildcardStructureTest(
    'Synthetic squad prefers 3-4-3',
    (
        $result[
            'formation'
        ]
        ?? null
    )
    ===
    '3-4-3'
);


$starterIds =
    array_map(
        static function (
            array $player
        ): int {

            return (int) (
                $player[
                    'player_id'
                ]
                ?? 0
            );
        },
        $result[
            'starting_xi'
        ]
        ?? []
    );


wildcardStructureTest(
    'Weak fifth midfielder is excluded from starting XI',
    !in_array(
        12,
        $starterIds,
        true
    )
);


wildcardStructureTest(
    'All three strong forwards start',
    in_array(
        13,
        $starterIds,
        true
    )
    &&
    in_array(
        14,
        $starterIds,
        true
    )
    &&
    in_array(
        15,
        $starterIds,
        true
    )
);


echo "<br>";


/*
 * ============================================================
 * BENCH ORDER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Bench Order<br>";
echo "============================================<br>";


$bench =
    $result[
        'bench'
    ]
    ?? [];


wildcardStructureTest(
    'Bench contains ordered outfield substitutes',
    isset(
        $bench[0][
            'bench_order'
        ]
    )
    &&
    isset(
        $bench[1][
            'bench_order'
        ]
    )
    &&
    isset(
        $bench[2][
            'bench_order'
        ]
    )
);


wildcardStructureTest(
    'Backup goalkeeper is first bench slot',
    isset(
        $bench[0]
    )
    &&
    (
        $bench[0][
            'position'
        ]
        ?? null
    )
    === 'GK'
    &&
    (
        $bench[0][
            'bench_order'
        ]
        ?? null
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * ALL FORMATIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Formation Evaluation<br>";
echo "============================================<br>";


wildcardStructureTest(
    'All eight legal formations are evaluated',
    count(
        $result[
            'formations'
        ]
        ?? []
    )
    === 8
);


echo "<br>";


/*
 * ============================================================
 * INVALID INPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Invalid Squad<br>";
echo "============================================<br>";


$shortSquad =
    array_slice(
        $squad,
        0,
        14
    );


$invalidResult =
    $structure
        ->analyze(
            $shortSquad
        );


wildcardStructureTest(
    'Fourteen-player squad is rejected',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


wildcardStructureTest(
    'Invalid squad returns no starting XI',
    empty(
        $invalidResult[
            'starting_xi'
        ]
        ?? []
    )
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Wildcard Squad Structure Test Summary<br>";
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