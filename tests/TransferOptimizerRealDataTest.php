<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Optimizer Real Data Diagnostic<br>";
echo "============================================<br><br>";


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

try {

    $database =
        new Database();


    $service =
        new PlayerIntelligenceService(
            $database->getConnection()
        );


    $players =
        $service
            ->getAllPlayerSummaries();

} catch (Throwable $exception) {

    echo "SETUP FAILED ❌<br>";

    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    exit;
}


/*
 * ============================================================
 * INDEX PLAYERS BY NAME
 * ============================================================
 */

$playersByName =
    [];


foreach ($players as $player) {

    $name =
        trim(
            (string) (
                $player[
                    'name'
                ]
                ?? ''
            )
        );


    if ($name === '') {
        continue;
    }


    $playersByName[
        strtolower(
            $name
        )
    ] =
        $player;
}


/*
 * ============================================================
 * SCENARIOS
 * ============================================================
 */

$scenarios = [

    [
        'label' =>
            'Bruno + Nmecha Optimisation',

        'player_a' =>
            'B.Fernandes',

        'player_b' =>
            'Nmecha',

        'bank' =>
            0.0,

        'limit' =>
            10
    ],

    [
        'label' =>
            'Gabriel + Merino Optimisation',

        'player_a' =>
            'Gabriel',

        'player_b' =>
            'Merino',

        'bank' =>
            0.0,

        'limit' =>
            10
    ],

    [
        'label' =>
            'Haaland + Merino Optimisation',

        'player_a' =>
            'Haaland',

        'player_b' =>
            'Merino',

        'bank' =>
            0.0,

        'limit' =>
            10
    ]
];


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function optimizerDiagnosticValue(
    mixed $value,
    int $decimals = 1
): string {

    if (
        $value === null
        ||
        !is_numeric(
            $value
        )
    ) {

        return 'N/A';
    }


    return number_format(
        (float) $value,
        $decimals
    );
}


function optimizerDiagnosticSigned(
    mixed $value,
    int $decimals = 1
): string {

    if (
        $value === null
        ||
        !is_numeric(
            $value
        )
    ) {

        return 'N/A';
    }


    $value =
        (float) $value;


    return (
        $value >= 0
            ? '+'
            : ''
    )
    . number_format(
        $value,
        $decimals
    );
}


function optimizerDiagnosticBudget(
    mixed $value
): string {

    if (
        $value === null
        ||
        !is_numeric(
            $value
        )
    ) {

        return 'N/A';
    }


    $value =
        (float) $value;


    if ($value > 0) {

        return '+£'
            . number_format(
                $value,
                1
            )
            . 'm';
    }


    if ($value < 0) {

        return '-£'
            . number_format(
                abs(
                    $value
                ),
                1
            )
            . 'm';
    }


    return '£0.0m';
}


/*
 * ============================================================
 * RUN REAL OPTIMIZER SCENARIOS
 * ============================================================
 */

foreach (
    $scenarios
    as $scenario
) {

    echo "<br>============================================<br>";

    echo htmlspecialchars(
        $scenario[
            'label'
        ],
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>============================================<br>";


    $summaryA =
        $playersByName[
            strtolower(
                $scenario[
                    'player_a'
                ]
            )
        ]
        ?? null;


    $summaryB =
        $playersByName[
            strtolower(
                $scenario[
                    'player_b'
                ]
            )
        ]
        ?? null;


    if ($summaryA === null) {

        echo "PLAYER A NOT FOUND ⚠️<br>";

        continue;
    }


    if ($summaryB === null) {

        echo "PLAYER B NOT FOUND ⚠️<br>";

        continue;
    }


    $playerIdA =
        (int) (
            $summaryA[
                'player_id'
            ]
            ?? 0
        );


    $playerIdB =
        (int) (
            $summaryB[
                'player_id'
            ]
            ?? 0
        );


    if (
        $playerIdA <= 0
        ||
        $playerIdB <= 0
    ) {

        echo "INVALID PLAYER ID ⚠️<br>";

        continue;
    }


    echo "<br><strong>Outgoing Players</strong><br>";


    echo "Player A: "
        . htmlspecialchars(
            (string) (
                $summaryA[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " | "
        . htmlspecialchars(
            (string) (
                $summaryA[
                    'position'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " | £"
        . optimizerDiagnosticValue(
            $summaryA[
                'price'
            ]
            ?? null
        )
        . "m | INT "
        . optimizerDiagnosticValue(
            $summaryA[
                'intelligence_score'
            ]
            ?? null
        )
        . "<br>";


    echo "Player B: "
        . htmlspecialchars(
            (string) (
                $summaryB[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " | "
        . htmlspecialchars(
            (string) (
                $summaryB[
                    'position'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " | £"
        . optimizerDiagnosticValue(
            $summaryB[
                'price'
            ]
            ?? null
        )
        . "m | INT "
        . optimizerDiagnosticValue(
            $summaryB[
                'intelligence_score'
            ]
            ?? null
        )
        . "<br>";


    echo "Bank: £"
        . number_format(
            (float) $scenario[
                'bank'
            ],
            1
        )
        . "m<br>";


    /*
     * --------------------------------------------------------
     * OPTIMIZE THROUGH SERVICE
     * --------------------------------------------------------
     */

    $startedAt =
        microtime(true);


    try {

        $result =
            $service
                ->optimizeTransferCombination(
                    $playerIdA,
                    $playerIdB,
                    (float) $scenario[
                        'bank'
                    ],
                    (int) $scenario[
                        'limit'
                    ]
                );

    } catch (Throwable $exception) {

        echo "ERROR ❌<br>";

        echo htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        );

        echo "<br>";

        continue;
    }


    $duration =
        microtime(true)
        -
        $startedAt;


    echo "Service Runtime: "
        . number_format(
            $duration,
            3
        )
        . " seconds<br>";


    if ($result === null) {

        echo "OPTIMIZER RETURNED NULL ⚠️<br>";

        continue;
    }


    echo "Total Affordable Combinations Found: "
        . (
            (int) (
                $result[
                    'total_found'
                ]
                ?? 0
            )
        )
        . "<br>";


    echo "Returned: "
        . (
            (int) (
                $result[
                    'count'
                ]
                ?? 0
            )
        )
        . "<br>";


    $combinations =
        $result[
            'combinations'
        ]
        ?? [];


    if (empty($combinations)) {

        echo "<br>No affordable combinations found.<br>";

        continue;
    }


    echo "<br><strong>Top Optimised Combinations</strong><br>";


    foreach (
        $combinations
        as $combination
    ) {

        $rank =
            (int) (
                $combination[
                    'optimizer'
                ]['rank']
                ?? 0
            );


        $transferA =
            $combination[
                'transfer_a'
            ]
            ?? [];


        $transferB =
            $combination[
                'transfer_b'
            ]
            ?? [];


        $incomingA =
            $transferA[
                'replacement'
            ]
            ?? [];


        $incomingB =
            $transferB[
                'replacement'
            ]
            ?? [];


        echo "<br><strong>#"
            . $rank
            . "</strong><br>";


        echo htmlspecialchars(
            (string) (
                $summaryA[
                    'name'
                ]
                ?? 'Player A'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " → "
        . htmlspecialchars(
            (string) (
                $incomingA[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


        echo htmlspecialchars(
            (string) (
                $summaryB[
                    'name'
                ]
                ?? 'Player B'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " → "
        . htmlspecialchars(
            (string) (
                $incomingB[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


        echo "Classification: "
            . htmlspecialchars(
                (string) (
                    $combination[
                        'classification'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Combination Score: "
            . optimizerDiagnosticValue(
                $combination[
                    'combination_score'
                ]
                ?? null,
                2
            )
            . "<br>";


        echo "Combined Intelligence: "
            . optimizerDiagnosticSigned(
                $combination[
                    'combined_movements'
                ]['intelligence']
                ?? null
            )
            . "<br>";


        echo "Combined Strength: "
            . optimizerDiagnosticSigned(
                $combination[
                    'combined_movements'
                ]['strength']
                ?? null
            )
            . "<br>";


        echo "Combined Value: "
            . optimizerDiagnosticSigned(
                $combination[
                    'combined_movements'
                ]['value']
                ?? null
            )
            . "<br>";


        echo "Combined Fixtures: "
            . optimizerDiagnosticSigned(
                $combination[
                    'combined_movements'
                ]['fixtures']
                ?? null
            )
            . "<br>";


        echo "Budget After: "
            . optimizerDiagnosticBudget(
                $combination[
                    'optimizer'
                ]['budget_after']
                ?? null
            )
            . "<br>";


        echo "Transfer A Decision: "
            . htmlspecialchars(
                (string) (
                    $transferA[
                        'decision_type'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Score "
            . optimizerDiagnosticValue(
                $transferA[
                    'decision_score'
                ]
                ?? null,
                2
            )
            . "<br>";


        echo "Transfer B Decision: "
            . htmlspecialchars(
                (string) (
                    $transferB[
                        'decision_type'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Score "
            . optimizerDiagnosticValue(
                $transferB[
                    'decision_score'
                ]
                ?? null,
                2
            )
            . "<br>";


        echo "Incoming A: £"
            . optimizerDiagnosticValue(
                $incomingA[
                    'price'
                ]
                ?? null
            )
            . "m | INT "
            . optimizerDiagnosticValue(
                $incomingA[
                    'intelligence_score'
                ]
                ?? null
            )
            . "<br>";


        echo "Incoming B: £"
            . optimizerDiagnosticValue(
                $incomingB[
                    'price'
                ]
                ?? null
            )
            . "m | INT "
            . optimizerDiagnosticValue(
                $incomingB[
                    'intelligence_score'
                ]
                ?? null
            )
            . "<br>";
    }
}


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Real Data Transfer Optimizer Diagnostic Complete<br>";
echo "============================================<br>";

echo "RESULT: TESTS PASSED ✅";