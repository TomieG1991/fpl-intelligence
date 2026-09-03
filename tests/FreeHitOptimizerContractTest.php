<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function freeHitOptimizerContractCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

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


echo
    '============================================<br>';

echo
    'Free Hit Optimizer Contract Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * SCENARIO A: FREE HIT OPTIMIZER CLASS EXISTS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Free Hit Optimizer Contract<br>';

echo
    '============================================<br>';


$classExists =
    class_exists(
        'FreeHitOptimizer'
    );


freeHitOptimizerContractCheck(
    'FreeHitOptimizer class exists',
    $classExists
);


if ($classExists) {

    $optimizer =
        new FreeHitOptimizer();


    freeHitOptimizerContractCheck(
        'FreeHitOptimizer can be instantiated',
        $optimizer
        instanceof
        FreeHitOptimizer
    );


    freeHitOptimizerContractCheck(
        'FreeHitOptimizer exposes an optimize method',
        method_exists(
            $optimizer,
            'optimize'
        )
    );

} else {

    freeHitOptimizerContractCheck(
        'FreeHitOptimizer can be instantiated',
        false
    );


    freeHitOptimizerContractCheck(
        'FreeHitOptimizer exposes an optimize method',
        false
    );
}


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B: EMPTY PLAYER POOL IS INVALID
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Empty Player Pool Is Invalid<br>';

echo
    '============================================<br>';


$optimizer =
    new FreeHitOptimizer();


$emptyPoolResult =
    $optimizer
        ->optimize(
            []
        );


freeHitOptimizerContractCheck(
    'Empty player pool returns an array result',
    is_array(
        $emptyPoolResult
    )
);


freeHitOptimizerContractCheck(
    'Empty player pool does not return success',
    (
        $emptyPoolResult[
            'status'
        ]
        ?? null
    )
    !==
    'success'
);


freeHitOptimizerContractCheck(
    'Empty player pool provides a failure status',
    isset(
        $emptyPoolResult[
            'status'
        ]
    )
    &&
    is_string(
        $emptyPoolResult[
            'status'
        ]
    )
    &&
    trim(
        $emptyPoolResult[
            'status'
        ]
    )
    !==
    ''
);


freeHitOptimizerContractCheck(
    'Empty player pool provides an explanation',
    isset(
        $emptyPoolResult[
            'message'
        ]
    )
    &&
    is_string(
        $emptyPoolResult[
            'message'
        ]
    )
    &&
    trim(
        $emptyPoolResult[
            'message'
        ]
    )
    !==
    ''
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C: NON-POSITIVE BUDGET IS INVALID
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Non-Positive Budget Is Invalid<br>';

echo
    '============================================<br>';


$invalidBudgetResult =
    $optimizer
        ->optimize(
            [
                [
                    'player_id' =>
                        1
                ]
            ],
            0.0
        );


freeHitOptimizerContractCheck(
    'Zero budget returns an array result',
    is_array(
        $invalidBudgetResult
    )
);


freeHitOptimizerContractCheck(
    'Zero budget does not return success',
    (
        $invalidBudgetResult[
            'status'
        ]
        ?? null
    )
    !==
    'success'
);


freeHitOptimizerContractCheck(
    'Zero budget provides a failure status',
    isset(
        $invalidBudgetResult[
            'status'
        ]
    )
    &&
    is_string(
        $invalidBudgetResult[
            'status'
        ]
    )
    &&
    trim(
        $invalidBudgetResult[
            'status'
        ]
    )
    !==
    ''
);


freeHitOptimizerContractCheck(
    'Zero budget provides an explanation',
    isset(
        $invalidBudgetResult[
            'message'
        ]
    )
    &&
    is_string(
        $invalidBudgetResult[
            'message'
        ]
    )
    &&
    trim(
        $invalidBudgetResult[
            'message'
        ]
    )
    !==
    ''
);


echo
    '<br>';
    
/*
 * ============================================================
 * SCENARIO D: SUCCESSFUL FREE HIT RETURNS LEGAL SQUAD SHAPE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Successful Free Hit Returns Legal Squad Shape<br>';

echo
    '============================================<br>';


$validPlayers = [];


/*
 * Two goalkeepers
 */
for (
    $i = 1;
    $i <= 2;
    $i++
) {

    $validPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'GK',

        'team_id' =>
            $i,

        'price' =>
            4.5,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five defenders
 */
for (
    $i = 3;
    $i <= 7;
    $i++
) {

    $validPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            5.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five midfielders
 */
for (
    $i = 8;
    $i <= 12;
    $i++
) {

    $validPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Three forwards
 */
for (
    $i = 13;
    $i <= 15;
    $i++
) {

    $validPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


$validResult =
    $optimizer
        ->optimize(
            $validPlayers,
            100.0
        );


freeHitOptimizerContractCheck(
    'Valid player pool returns success',
    (
        $validResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


freeHitOptimizerContractCheck(
    'Successful result contains a squad',
    isset(
        $validResult[
            'squad'
        ]
    )
    &&
    is_array(
        $validResult[
            'squad'
        ]
    )
);


freeHitOptimizerContractCheck(
    'Successful Free Hit squad contains exactly fifteen players',
    isset(
        $validResult[
            'squad'
        ]
    )
    &&
    is_array(
        $validResult[
            'squad'
        ]
    )
    &&
    count(
        $validResult[
            'squad'
        ]
    )
    ===
    15
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E: INVALID POSITION STRUCTURE IS REJECTED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Invalid Position Structure Is Rejected<br>';

echo
    '============================================<br>';


$invalidPositionPlayers = [];


/*
 * One goalkeeper
 */
$invalidPositionPlayers[] = [
    'player_id' =>
        1,

    'position' =>
        'GK',

    'team_id' =>
        1,

    'price' =>
        4.5,

    'projected_points' =>
        5.0
];


/*
 * Six defenders
 */
for (
    $i = 2;
    $i <= 7;
    $i++
) {

    $invalidPositionPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            5.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five midfielders
 */
for (
    $i = 8;
    $i <= 12;
    $i++
) {

    $invalidPositionPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Three forwards
 */
for (
    $i = 13;
    $i <= 15;
    $i++
) {

    $invalidPositionPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


$invalidPositionResult =
    $optimizer
        ->optimize(
            $invalidPositionPlayers,
            100.0
        );


freeHitOptimizerContractCheck(
    'Invalid position structure does not return success',
    (
        $invalidPositionResult[
            'status'
        ]
        ?? null
    )
    !==
    'success'
);


freeHitOptimizerContractCheck(
    'Invalid position structure provides a failure status',
    isset(
        $invalidPositionResult[
            'status'
        ]
    )
    &&
    is_string(
        $invalidPositionResult[
            'status'
        ]
    )
    &&
    trim(
        $invalidPositionResult[
            'status'
        ]
    )
    !==
    ''
);


freeHitOptimizerContractCheck(
    'Invalid position structure provides an explanation',
    isset(
        $invalidPositionResult[
            'message'
        ]
    )
    &&
    is_string(
        $invalidPositionResult[
            'message'
        ]
    )
    &&
    trim(
        $invalidPositionResult[
            'message'
        ]
    )
    !==
    ''
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO F: MORE THAN THREE PLAYERS FROM ONE CLUB IS REJECTED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: More Than Three Players From One Club Is Rejected<br>';

echo
    '============================================<br>';


$tooManyFromOneClubPlayers = [];


/*
 * Two goalkeepers
 */
for (
    $i = 1;
    $i <= 2;
    $i++
) {

    $tooManyFromOneClubPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'GK',

        'team_id' =>
            $i === 1
                ? 1
                : 2,

        'price' =>
            4.5,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five defenders
 *
 * Three of these defenders are from team 1.
 * Combined with goalkeeper 1, that creates
 * four players from the same club.
 */
for (
    $i = 3;
    $i <= 7;
    $i++
) {

    $tooManyFromOneClubPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i <= 5
                ? 1
                : $i,

        'price' =>
            5.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five midfielders
 */
for (
    $i = 8;
    $i <= 12;
    $i++
) {

    $tooManyFromOneClubPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Three forwards
 */
for (
    $i = 13;
    $i <= 15;
    $i++
) {

    $tooManyFromOneClubPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


$tooManyFromOneClubResult =
    $optimizer
        ->optimize(
            $tooManyFromOneClubPlayers,
            100.0
        );


freeHitOptimizerContractCheck(
    'Squad with more than three players from one club does not return success',
    (
        $tooManyFromOneClubResult[
            'status'
        ]
        ?? null
    )
    !==
    'success'
);


freeHitOptimizerContractCheck(
    'Club limit failure provides a failure status',
    isset(
        $tooManyFromOneClubResult[
            'status'
        ]
    )
    &&
    is_string(
        $tooManyFromOneClubResult[
            'status'
        ]
    )
    &&
    trim(
        $tooManyFromOneClubResult[
            'status'
        ]
    )
    !==
    ''
);


freeHitOptimizerContractCheck(
    'Club limit failure provides an explanation',
    isset(
        $tooManyFromOneClubResult[
            'message'
        ]
    )
    &&
    is_string(
        $tooManyFromOneClubResult[
            'message'
        ]
    )
    &&
    trim(
        $tooManyFromOneClubResult[
            'message'
        ]
    )
    !==
    ''
);


echo
    '<br>';    
    

/*
 * ============================================================
 * SCENARIO G: SQUAD OVER BUDGET IS REJECTED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Squad Over Budget Is Rejected<br>';

echo
    '============================================<br>';


$overBudgetPlayers = [];


/*
 * Two goalkeepers
 */
for (
    $i = 1;
    $i <= 2;
    $i++
) {

    $overBudgetPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'GK',

        'team_id' =>
            $i,

        'price' =>
            10.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five defenders
 */
for (
    $i = 3;
    $i <= 7;
    $i++
) {

    $overBudgetPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            10.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five midfielders
 */
for (
    $i = 8;
    $i <= 12;
    $i++
) {

    $overBudgetPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            10.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Three forwards
 */
for (
    $i = 13;
    $i <= 15;
    $i++
) {

    $overBudgetPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            10.0,

        'projected_points' =>
            6.0
    ];
}


$overBudgetResult =
    $optimizer
        ->optimize(
            $overBudgetPlayers,
            100.0
        );


freeHitOptimizerContractCheck(
    'Squad over budget does not return success',
    (
        $overBudgetResult[
            'status'
        ]
        ?? null
    )
    !==
    'success'
);


freeHitOptimizerContractCheck(
    'Budget failure provides a failure status',
    isset(
        $overBudgetResult[
            'status'
        ]
    )
    &&
    is_string(
        $overBudgetResult[
            'status'
        ]
    )
    &&
    trim(
        $overBudgetResult[
            'status'
        ]
    )
    !==
    ''
);


freeHitOptimizerContractCheck(
    'Budget failure provides an explanation',
    isset(
        $overBudgetResult[
            'message'
        ]
    )
    &&
    is_string(
        $overBudgetResult[
            'message'
        ]
    )
    &&
    trim(
        $overBudgetResult[
            'message'
        ]
    )
    !==
    ''
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO H: DUPLICATE PLAYERS ARE REJECTED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario H: Duplicate Players Are Rejected<br>';

echo
    '============================================<br>';


$duplicatePlayers = [];


/*
 * Two goalkeepers
 */
for (
    $i = 1;
    $i <= 2;
    $i++
) {

    $duplicatePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'GK',

        'team_id' =>
            $i,

        'price' =>
            4.5,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five defenders
 */
for (
    $i = 3;
    $i <= 7;
    $i++
) {

    $duplicatePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            5.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five midfielders
 */
for (
    $i = 8;
    $i <= 12;
    $i++
) {

    $duplicatePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Three forwards
 */
for (
    $i = 13;
    $i <= 15;
    $i++
) {

    $duplicatePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Force one duplicate player ID while keeping
 * the position structure, club limit and budget valid.
 */
$duplicatePlayers[
    14
][
    'player_id'
] =
    13;


$duplicateResult =
    $optimizer
        ->optimize(
            $duplicatePlayers,
            100.0
        );


freeHitOptimizerContractCheck(
    'Squad with duplicate player IDs does not return success',
    (
        $duplicateResult[
            'status'
        ]
        ?? null
    )
    !==
    'success'
);


freeHitOptimizerContractCheck(
    'Duplicate player failure provides a failure status',
    isset(
        $duplicateResult[
            'status'
        ]
    )
    &&
    is_string(
        $duplicateResult[
            'status'
        ]
    )
    &&
    trim(
        $duplicateResult[
            'status'
        ]
    )
    !==
    ''
);


freeHitOptimizerContractCheck(
    'Duplicate player failure provides an explanation',
    isset(
        $duplicateResult[
            'message'
        ]
    )
    &&
    is_string(
        $duplicateResult[
            'message'
        ]
    )
    &&
    trim(
        $duplicateResult[
            'message'
        ]
    )
    !==
    ''
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO I: HIGHER PROJECTED POINTS PLAYER IS SELECTED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario I: Higher Projected Points Player Is Selected<br>';

echo
    '============================================<br>';


$optimizationPlayers = [];


/*
 * Two goalkeepers
 */
for (
    $i = 1;
    $i <= 2;
    $i++
) {

    $optimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'GK',

        'team_id' =>
            $i,

        'price' =>
            4.5,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five defenders
 */
for (
    $i = 3;
    $i <= 7;
    $i++
) {

    $optimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            5.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five midfielders
 */
for (
    $i = 8;
    $i <= 12;
    $i++
) {

    $optimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Three forwards
 */
for (
    $i = 13;
    $i <= 15;
    $i++
) {

    $optimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Add one extra midfielder who is strictly better
 * than midfielder 12 while costing exactly the same.
 *
 * A real Free Hit optimizer should select player 16
 * instead of player 12.
 */
$optimizationPlayers[] = [
    'player_id' =>
        16,

    'position' =>
        'MID',

    'team_id' =>
        16,

    'price' =>
        6.0,

    'projected_points' =>
        10.0
];


$optimizationResult =
    $optimizer
        ->optimize(
            $optimizationPlayers,
            100.0
        );


$selectedPlayerIds = [];


foreach (
    $optimizationResult[
        'squad'
    ]
    ?? []
    as $player
) {

    $selectedPlayerIds[] =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );
}


freeHitOptimizerContractCheck(
    'Optimizable player pool returns success',
    (
        $optimizationResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


freeHitOptimizerContractCheck(
    'Higher projected points midfielder is selected',
    in_array(
        16,
        $selectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Lower projected points midfielder is excluded',
    !in_array(
        12,
        $selectedPlayerIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO J: CLUB LIMIT USES NEXT-BEST LEGAL ALTERNATIVE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario J: Club Limit Uses Next-Best Legal Alternative<br>';

echo
    '============================================<br>';


$clubLimitOptimizationPlayers = [];


/*
 * Two goalkeepers
 */
$clubLimitOptimizationPlayers[] = [
    'player_id' =>
        1,

    'position' =>
        'GK',

    'team_id' =>
        1,

    'price' =>
        4.5,

    'projected_points' =>
        10.0
];


$clubLimitOptimizationPlayers[] = [
    'player_id' =>
        2,

    'position' =>
        'GK',

    'team_id' =>
        2,

    'price' =>
        4.5,

    'projected_points' =>
        5.0
];


/*
 * Five defenders
 *
 * Players 3, 4 and 5 are also from team 1.
 * Selecting all three alongside goalkeeper 1 would
 * create four players from the same club.
 *
 * Player 8 is the next-best legal defender and should
 * replace the weakest conflicting team-1 defender.
 */
$clubLimitOptimizationPlayers[] = [
    'player_id' =>
        3,

    'position' =>
        'DEF',

    'team_id' =>
        1,

    'price' =>
        5.0,

    'projected_points' =>
        9.0
];


$clubLimitOptimizationPlayers[] = [
    'player_id' =>
        4,

    'position' =>
        'DEF',

    'team_id' =>
        1,

    'price' =>
        5.0,

    'projected_points' =>
        8.0
];


$clubLimitOptimizationPlayers[] = [
    'player_id' =>
        5,

    'position' =>
        'DEF',

    'team_id' =>
        1,

    'price' =>
        5.0,

    'projected_points' =>
        7.0
];


$clubLimitOptimizationPlayers[] = [
    'player_id' =>
        6,

    'position' =>
        'DEF',

    'team_id' =>
        6,

    'price' =>
        5.0,

    'projected_points' =>
        6.0
];


$clubLimitOptimizationPlayers[] = [
    'player_id' =>
        7,

    'position' =>
        'DEF',

    'team_id' =>
        7,

    'price' =>
        5.0,

    'projected_points' =>
        5.0
];


$clubLimitOptimizationPlayers[] = [
    'player_id' =>
        8,

    'position' =>
        'DEF',

    'team_id' =>
        8,

    'price' =>
        5.0,

    'projected_points' =>
        4.0
];


/*
 * Five midfielders
 */
for (
    $i = 9;
    $i <= 13;
    $i++
) {

    $clubLimitOptimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Three forwards
 */
for (
    $i = 14;
    $i <= 16;
    $i++
) {

    $clubLimitOptimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


$clubLimitOptimizationResult =
    $optimizer
        ->optimize(
            $clubLimitOptimizationPlayers,
            100.0
        );


$clubLimitSelectedPlayerIds = [];


foreach (
    $clubLimitOptimizationResult[
        'squad'
    ]
    ?? []
    as $player
) {

    $clubLimitSelectedPlayerIds[] =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );
}


freeHitOptimizerContractCheck(
    'Legal alternative exists so club-limit optimization returns success',
    (
        $clubLimitOptimizationResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


freeHitOptimizerContractCheck(
    'Next-best legal defender is selected',
    in_array(
        8,
        $clubLimitSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Weakest conflicting defender is excluded',
    !in_array(
        5,
        $clubLimitSelectedPlayerIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO K: BUDGET USES NEXT-BEST AFFORDABLE ALTERNATIVE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario K: Budget Uses Next-Best Affordable Alternative<br>';

echo
    '============================================<br>';


$budgetOptimizationPlayers = [];


/*
 * Two goalkeepers
 */
for (
    $i = 1;
    $i <= 2;
    $i++
) {

    $budgetOptimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'GK',

        'team_id' =>
            $i,

        'price' =>
            4.5,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five defenders
 */
for (
    $i = 3;
    $i <= 7;
    $i++
) {

    $budgetOptimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            5.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five midfielders
 *
 * Player 12 is the highest projected midfielder,
 * but is deliberately too expensive.
 */
for (
    $i = 8;
    $i <= 11;
    $i++
) {

    $budgetOptimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            6.0
    ];
}


$budgetOptimizationPlayers[] = [
    'player_id' =>
        12,

    'position' =>
        'MID',

    'team_id' =>
        12,

    'price' =>
        20.0,

    'projected_points' =>
        10.0
];


/*
 * Cheaper alternative midfielder.
 */
$budgetOptimizationPlayers[] = [
    'player_id' =>
        13,

    'position' =>
        'MID',

    'team_id' =>
        13,

    'price' =>
        6.0,

    'projected_points' =>
        5.0
];


/*
 * Three forwards
 */
for (
    $i = 14;
    $i <= 16;
    $i++
) {

    $budgetOptimizationPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


$budgetOptimizationResult =
    $optimizer
        ->optimize(
            $budgetOptimizationPlayers,
            85.0
        );


$budgetSelectedPlayerIds = [];


foreach (
    $budgetOptimizationResult[
        'squad'
    ]
    ?? []
    as $player
) {

    $budgetSelectedPlayerIds[] =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );
}


freeHitOptimizerContractCheck(
    'Affordable legal alternative exists so budget optimization returns success',
    (
        $budgetOptimizationResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


freeHitOptimizerContractCheck(
    'Cheaper alternative midfielder is selected',
    in_array(
        13,
        $budgetSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Unaffordable midfielder is excluded',
    !in_array(
        12,
        $budgetSelectedPlayerIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO L: BUDGET CAN REQUIRE MULTIPLE DOWNGRADES
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario L: Budget Can Require Multiple Downgrades<br>';

echo
    '============================================<br>';


$multipleDowngradePlayers = [];


/*
 * Two goalkeepers
 *
 * Total cost: 9.0
 */
for (
    $i = 1;
    $i <= 2;
    $i++
) {

    $multipleDowngradePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'GK',

        'team_id' =>
            $i,

        'price' =>
            4.5,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five defenders
 *
 * Total cost: 25.0
 */
for (
    $i = 3;
    $i <= 7;
    $i++
) {

    $multipleDowngradePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            5.0,

        'projected_points' =>
            5.0
    ];
}


/*
 * Three normal midfielders.
 */
for (
    $i = 8;
    $i <= 10;
    $i++
) {

    $multipleDowngradePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Two expensive high-projection midfielders.
 *
 * Both are initially selected.
 */
$multipleDowngradePlayers[] = [
    'player_id' =>
        11,

    'position' =>
        'MID',

    'team_id' =>
        11,

    'price' =>
        11.0,

    'projected_points' =>
        10.0
];


$multipleDowngradePlayers[] = [
    'player_id' =>
        12,

    'position' =>
        'MID',

    'team_id' =>
        12,

    'price' =>
        11.0,

    'projected_points' =>
        9.0
];


/*
 * Two cheaper alternatives.
 *
 * Each saves only 5.0.
 *
 * The squad is 10.0 over budget, so neither
 * replacement can solve the problem alone.
 * Both replacements are required.
 */
$multipleDowngradePlayers[] = [
    'player_id' =>
        13,

    'position' =>
        'MID',

    'team_id' =>
        13,

    'price' =>
        6.0,

    'projected_points' =>
        5.5
];


$multipleDowngradePlayers[] = [
    'player_id' =>
        14,

    'position' =>
        'MID',

    'team_id' =>
        14,

    'price' =>
        6.0,

    'projected_points' =>
        5.0
];


/*
 * Three forwards
 *
 * Total cost: 21.0
 */
for (
    $i = 15;
    $i <= 17;
    $i++
) {

    $multipleDowngradePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Initial strongest squad cost:
 *
 * GK  =  9.0
 * DEF = 25.0
 * MID = 40.0
 * FWD = 21.0
 * ----------------
 *       95.0
 *
 * Budget = 85.0
 *
 * Replacing:
 *
 * 11 -> 13 saves 5.0
 * 12 -> 14 saves 5.0
 *
 * Both downgrades are therefore required.
 */
$multipleDowngradeResult =
    $optimizer
        ->optimize(
            $multipleDowngradePlayers,
            85.0
        );


$multipleDowngradeSelectedPlayerIds = [];


foreach (
    $multipleDowngradeResult[
        'squad'
    ]
    ?? []
    as $player
) {

    $multipleDowngradeSelectedPlayerIds[] =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );
}


freeHitOptimizerContractCheck(
    'Multiple affordable downgrades allow optimization to return success',
    (
        $multipleDowngradeResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


freeHitOptimizerContractCheck(
    'First cheaper midfielder is selected',
    in_array(
        13,
        $multipleDowngradeSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Second cheaper midfielder is selected',
    in_array(
        14,
        $multipleDowngradeSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'First expensive midfielder is excluded',
    !in_array(
        11,
        $multipleDowngradeSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Second expensive midfielder is excluded',
    !in_array(
        12,
        $multipleDowngradeSelectedPlayerIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO M: OPTIMIZER FINDS BEST OVERALL BUDGET COMBINATION
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario M: Optimizer Finds Best Overall Budget Combination<br>';

echo
    '============================================<br>';


$globalBudgetPlayers = [];


/*
 * Two goalkeepers.
 *
 * Selected cost: 9.0
 */
for (
    $i = 1;
    $i <= 2;
    $i++
) {

    $globalBudgetPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'GK',

        'team_id' =>
            $i,

        'price' =>
            4.5,

        'projected_points' =>
            5.0
    ];
}


/*
 * Five initially selected defenders.
 *
 * Player 3 is the expensive defender.
 *
 * Selected cost: 27.0
 */
$globalBudgetPlayers[] = [
    'player_id' =>
        3,

    'position' =>
        'DEF',

    'team_id' =>
        3,

    'price' =>
        7.0,

    'projected_points' =>
        8.0
];


for (
    $i = 4;
    $i <= 7;
    $i++
) {

    $globalBudgetPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            3.0,

        'projected_points' =>
            7.0
    ];
}


/*
 * Defender 8 is the global budget solution.
 *
 * Replacing defender 3 with defender 8:
 *
 * saves 4.0
 * loses 2.0 projected points
 *
 * This single replacement solves the full
 * budget shortfall.
 */
$globalBudgetPlayers[] = [
    'player_id' =>
        8,

    'position' =>
        'DEF',

    'team_id' =>
        8,

    'price' =>
        3.0,

    'projected_points' =>
        6.0
];


/*
 * Four fixed high-projection midfielders.
 *
 * Their projected points are deliberately above
 * the alternative midfielder so that the alternative
 * is NOT selected in the initial squad.
 */
for (
    $i = 9;
    $i <= 12;
    $i++
) {

    $globalBudgetPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            11.0
    ];
}


/*
 * Expensive fifth midfielder.
 */
$globalBudgetPlayers[] = [
    'player_id' =>
        13,

    'position' =>
        'MID',

    'team_id' =>
        13,

    'price' =>
        12.0,

    'projected_points' =>
        10.0
];


/*
 * Greedy alternative midfielder.
 *
 * Replacing midfielder 13 with midfielder 14:
 *
 * saves only 2.0
 * loses only 0.5 projected points
 *
 * Because 0.5 is a smaller immediate loss than the
 * defender downgrade's 2.0 loss, the current greedy
 * algorithm should choose this replacement first.
 *
 * But the squad will still be 2.0 over budget.
 */
$globalBudgetPlayers[] = [
    'player_id' =>
        14,

    'position' =>
        'MID',

    'team_id' =>
        14,

    'price' =>
        10.0,

    'projected_points' =>
        9.5
];


/*
 * Three forwards.
 *
 * Selected cost: 21.0
 */
for (
    $i = 15;
    $i <= 17;
    $i++
) {

    $globalBudgetPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Initial strongest squad cost:
 *
 * GK  =  9.0
 * DEF = 27.0
 * MID = 36.0
 * FWD = 21.0
 * ----------------
 *       93.0
 *
 * Budget = 89.0
 *
 * Required saving = 4.0
 *
 *
 * Current greedy path:
 *
 * MID 13 -> 14
 *
 * save 2.0
 * lose 0.5 projected points
 *
 * Squad cost = 91.0
 *
 * It still needs another 2.0 saving.
 *
 * Then:
 *
 * DEF 3 -> 8
 *
 * save 4.0
 * lose 2.0 projected points
 *
 * Total projected-points loss = 2.5
 *
 *
 * Better global path:
 *
 * DEF 3 -> 8 only
 *
 * save 4.0
 * lose 2.0 projected points
 *
 * Total projected-points loss = 2.0
 *
 *
 * Therefore the optimal squad should:
 *
 * include defender 8
 * retain midfielder 13
 * exclude midfielder 14
 */
$globalBudgetResult =
    $optimizer
        ->optimize(
            $globalBudgetPlayers,
            81.0
        );


$globalBudgetSelectedPlayerIds = [];


foreach (
    $globalBudgetResult[
        'squad'
    ]
    ?? []
    as $player
) {

    $globalBudgetSelectedPlayerIds[] =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );
}


freeHitOptimizerContractCheck(
    'Globally optimizable budget pool returns success',
    (
        $globalBudgetResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


freeHitOptimizerContractCheck(
    'Globally superior defender alternative is selected',
    in_array(
        8,
        $globalBudgetSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Higher-value expensive midfielder is retained',
    in_array(
        13,
        $globalBudgetSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Greedy-only midfielder alternative is excluded',
    !in_array(
        14,
        $globalBudgetSelectedPlayerIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO N: CLUB LIMIT DOES NOT BLOCK BETTER GLOBAL SQUAD
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario N: Club Limit Does Not Block Better Global Squad<br>';

echo
    '============================================<br>';


$globalClubPlayers = [];


/*
 * Goalkeepers.
 *
 * Player 1 is from club 1 and has only a very small
 * projected-points advantage over goalkeeper 3.
 *
 * The current position-by-position greedy selection
 * should initially choose players 1 and 2.
 */
$globalClubPlayers[] = [
    'player_id' =>
        1,

    'position' =>
        'GK',

    'team_id' =>
        1,

    'price' =>
        5.0,

    'projected_points' =>
        10.0
];


$globalClubPlayers[] = [
    'player_id' =>
        2,

    'position' =>
        'GK',

    'team_id' =>
        2,

    'price' =>
        5.0,

    'projected_points' =>
        9.9
];


$globalClubPlayers[] = [
    'player_id' =>
        3,

    'position' =>
        'GK',

    'team_id' =>
        3,

    'price' =>
        5.0,

    'projected_points' =>
        9.8
];


/*
 * Five defenders.
 *
 * Two are also from club 1.
 *
 * Because goalkeeper 1 has already consumed one club
 * slot, defenders 4 and 5 bring club 1 to the maximum
 * three-player limit.
 */
$globalClubPlayers[] = [
    'player_id' =>
        4,

    'position' =>
        'DEF',

    'team_id' =>
        1,

    'price' =>
        5.0,

    'projected_points' =>
        10.0
];


$globalClubPlayers[] = [
    'player_id' =>
        5,

    'position' =>
        'DEF',

    'team_id' =>
        1,

    'price' =>
        5.0,

    'projected_points' =>
        9.9
];


$globalClubPlayers[] = [
    'player_id' =>
        6,

    'position' =>
        'DEF',

    'team_id' =>
        4,

    'price' =>
        5.0,

    'projected_points' =>
        9.8
];


$globalClubPlayers[] = [
    'player_id' =>
        7,

    'position' =>
        'DEF',

    'team_id' =>
        5,

    'price' =>
        5.0,

    'projected_points' =>
        9.7
];


$globalClubPlayers[] = [
    'player_id' =>
        8,

    'position' =>
        'DEF',

    'team_id' =>
        6,

    'price' =>
        5.0,

    'projected_points' =>
        9.6
];


/*
 * Exceptional club-1 midfielder.
 *
 * This player is far stronger than the fifth
 * non-club-1 midfielder.
 *
 * A globally better squad should free one club-1
 * slot by replacing goalkeeper 1 with goalkeeper 3,
 * allowing midfielder 9 to be selected.
 */
$globalClubPlayers[] = [
    'player_id' =>
        9,

    'position' =>
        'MID',

    'team_id' =>
        1,

    'price' =>
        5.0,

    'projected_points' =>
        20.0
];


$globalClubPlayers[] = [
    'player_id' =>
        10,

    'position' =>
        'MID',

    'team_id' =>
        7,

    'price' =>
        5.0,

    'projected_points' =>
        10.0
];


$globalClubPlayers[] = [
    'player_id' =>
        11,

    'position' =>
        'MID',

    'team_id' =>
        8,

    'price' =>
        5.0,

    'projected_points' =>
        9.0
];


$globalClubPlayers[] = [
    'player_id' =>
        12,

    'position' =>
        'MID',

    'team_id' =>
        9,

    'price' =>
        5.0,

    'projected_points' =>
        8.0
];


$globalClubPlayers[] = [
    'player_id' =>
        13,

    'position' =>
        'MID',

    'team_id' =>
        10,

    'price' =>
        5.0,

    'projected_points' =>
        7.0
];


$globalClubPlayers[] = [
    'player_id' =>
        14,

    'position' =>
        'MID',

    'team_id' =>
        11,

    'price' =>
        5.0,

    'projected_points' =>
        6.0
];


/*
 * Three forwards.
 */
for (
    $i = 15;
    $i <= 17;
    $i++
) {

    $globalClubPlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            5.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Every player costs 5.0, so budget is deliberately
 * irrelevant to this scenario.
 *
 * Current greedy club-limit path:
 *
 * GK:
 * 1 + 2 selected
 *
 * DEF:
 * 4 + 5 selected
 *
 * Club 1 now has:
 * 1, 4, 5
 *
 * MID:
 * Player 9 is blocked because club 1 already has
 * three selected players.
 *
 *
 * Better global squad:
 *
 * Replace:
 *
 * GK 1
 * 10.0 projected points
 *
 * with:
 *
 * GK 3
 * 9.8 projected points
 *
 * Loss = only 0.2
 *
 *
 * This frees a club-1 slot so MID 9 can replace
 * MID 14:
 *
 * 20.0 instead of 6.0
 *
 * Gain = 14.0
 *
 *
 * Net improvement:
 *
 * 14.0 - 0.2 = 13.8 projected points
 *
 * Therefore a globally optimized squad should
 * sacrifice goalkeeper 1 and include midfielder 9.
 */
$globalClubResult =
    $optimizer
        ->optimize(
            $globalClubPlayers,
            100.0
        );


$globalClubSelectedPlayerIds = [];


foreach (
    $globalClubResult[
        'squad'
    ]
    ?? []
    as $player
) {

    $globalClubSelectedPlayerIds[] =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );
}


freeHitOptimizerContractCheck(
    'Globally optimizable club-limit pool returns success',
    (
        $globalClubResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


freeHitOptimizerContractCheck(
    'Exceptional club-limited midfielder is selected',
    in_array(
        9,
        $globalClubSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Alternative goalkeeper is selected to free club slot',
    in_array(
        3,
        $globalClubSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Lower-value club goalkeeper is sacrificed',
    !in_array(
        1,
        $globalClubSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Weakest midfielder is excluded for exceptional midfielder',
    !in_array(
        14,
        $globalClubSelectedPlayerIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO O: STARTING XI VALUE BEATS BENCH VALUE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario O: Starting XI Value Beats Bench Value<br>';

echo
    '============================================<br>';


$startingXiValuePlayers = [];


/*
 * Goalkeeper 1 is the clear starter.
 */
$startingXiValuePlayers[] = [
    'player_id' =>
        1,

    'position' =>
        'GK',

    'team_id' =>
        1,

    'price' =>
        5.0,

    'projected_points' =>
        8.0
];


/*
 * Expensive backup goalkeeper.
 *
 * This player improves the total projected points
 * of the 15-player squad, but they would not start
 * ahead of goalkeeper 1.
 */
$startingXiValuePlayers[] = [
    'player_id' =>
        2,

    'position' =>
        'GK',

    'team_id' =>
        2,

    'price' =>
        6.0,

    'projected_points' =>
        6.0
];


/*
 * Cheap backup goalkeeper.
 *
 * Using this player instead frees 2.0 of budget
 * without weakening the Starting XI.
 */
$startingXiValuePlayers[] = [
    'player_id' =>
        3,

    'position' =>
        'GK',

    'team_id' =>
        3,

    'price' =>
        4.0,

    'projected_points' =>
        2.0
];


/*
 * Five defenders.
 */
for (
    $i = 4;
    $i <= 8;
    $i++
) {

    $startingXiValuePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'DEF',

        'team_id' =>
            $i,

        'price' =>
            5.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Four fixed midfielders.
 */
for (
    $i = 9;
    $i <= 12;
    $i++
) {

    $startingXiValuePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'MID',

        'team_id' =>
            $i,

        'price' =>
            6.0,

        'projected_points' =>
            9.5
    ];
}


/*
 * Expensive high-value fifth midfielder.
 *
 * This player should be preferred for the Starting XI
 * if the optimizer uses the cheap backup goalkeeper.
 */
$startingXiValuePlayers[] = [
    'player_id' =>
        13,

    'position' =>
        'MID',

    'team_id' =>
        13,

    'price' =>
        8.0,

    'projected_points' =>
        10.0
];


/*
 * Cheaper fifth midfielder.
 *
 * Keeping the expensive backup goalkeeper forces the
 * optimizer toward this weaker Starting XI option.
 */
$startingXiValuePlayers[] = [
    'player_id' =>
        14,

    'position' =>
        'MID',

    'team_id' =>
        14,

    'price' =>
        6.0,

    'projected_points' =>
        9.0
];


/*
 * Three forwards.
 */
for (
    $i = 15;
    $i <= 17;
    $i++
) {

    $startingXiValuePlayers[] = [
        'player_id' =>
            $i,

        'position' =>
            'FWD',

        'team_id' =>
            $i,

        'price' =>
            7.0,

        'projected_points' =>
            7.0
    ];
}


/*
 * Compare the important choice:
 *
 * OPTION A
 * --------
 * GK 2:
 * price 6.0
 * projected points 6.0
 *
 * MID 14:
 * price 6.0
 * projected points 5.0
 *
 * Combined:
 * price 12.0
 * total squad projections 11.0
 *
 *
 * OPTION B
 * --------
 * GK 3:
 * price 4.0
 * projected points 2.0
 *
 * MID 13:
 * price 8.0
 * projected points 10.0
 *
 * Combined:
 * price 12.0
 * total squad projections 12.0
 *
 *
 * More importantly, goalkeeper 3 is only the backup.
 * Goalkeeper 1 remains the Starting XI goalkeeper.
 *
 * Therefore the 8-point difference between GK 2 and
 * GK 3 is irrelevant to the Starting XI; what matters
 * is allowing the 10-point midfielder into the XI.
 */
$startingXiValueResult =
    $optimizer
        ->optimize(
            $startingXiValuePlayers,
            88.0
        );


$startingXiValueSelectedPlayerIds = [];


foreach (
    $startingXiValueResult[
        'squad'
    ]
    ?? []
    as $player
) {

    $startingXiValueSelectedPlayerIds[] =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );
}


freeHitOptimizerContractCheck(
    'Starting-XI-value pool returns success',
    (
        $startingXiValueResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


freeHitOptimizerContractCheck(
    'Cheap backup goalkeeper is selected',
    in_array(
        3,
        $startingXiValueSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Expensive backup goalkeeper is excluded',
    !in_array(
        2,
        $startingXiValueSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Higher-value Starting XI midfielder is selected',
    in_array(
        13,
        $startingXiValueSelectedPlayerIds,
        true
    )
);


freeHitOptimizerContractCheck(
    'Weaker midfielder is excluded',
    !in_array(
        14,
        $startingXiValueSelectedPlayerIds,
        true
    )
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


if ($failed === 0) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}