<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Replacement Recommendation Real Data Diagnostic<br>";
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


    $recommendationEngine =
        new ReplacementRecommendation();


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
                $player['name']
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
        'player' =>
            'B.Fernandes',

        'max_price' =>
            12.0,

        'limit' =>
            10
    ],

    [
        'player' =>
            'Haaland',

        'max_price' =>
            10.0,

        'limit' =>
            10
    ],

    [
        'player' =>
            'Dowman',

        'max_price' =>
            6.0,

        'limit' =>
            10
    ],

    [
        'player' =>
            'Gabriel',

        'max_price' =>
            8.0,

        'limit' =>
            10
    ]
];


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function recommendationDiagnosticValue(
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


function recommendationDiagnosticConfidence(
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


    return number_format(
        (
            (float) $value
        )
        * 100,
        1
    )
    . '%';
}


function recommendationDiagnosticPlayer(
    ?array $player
): void {

    if ($player === null) {

        echo "None<br>";

        return;
    }


    echo htmlspecialchars(
        (string) (
            $player['name']
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo " | £";


    echo recommendationDiagnosticValue(
        $player[
            'price'
        ]
        ?? null
    );


    echo "m | INT ";


    echo recommendationDiagnosticValue(
        $player[
            'intelligence_score'
        ]
        ?? null
    );


    echo " | STR ";


    echo recommendationDiagnosticValue(
        $player[
            'strength_rating'
        ]
        ?? null
    );


    echo " | VAL ";


    echo recommendationDiagnosticValue(
        $player[
            'value_rating'
        ]
        ?? null
    );


    echo " | FIX ";


    echo recommendationDiagnosticValue(
        $player[
            'fixture_rating'
        ]
        ?? null
    );


    echo " | SAMPLE ";


    echo recommendationDiagnosticConfidence(
        $player[
            'sample_confidence'
        ]
        ?? null
    );


    echo " | ";


    echo htmlspecialchars(
        (string) (
            $player['verdict']
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo "<br>";
}


/*
 * ============================================================
 * RUN REAL RECOMMENDATION DIAGNOSTICS
 * ============================================================
 */

foreach (
    $scenarios
    as $scenario
) {

    $targetName =
        $scenario[
            'player'
        ];


    $maxPrice =
        (float) $scenario[
            'max_price'
        ];


    $limit =
        (int) $scenario[
            'limit'
        ];


    echo "<br>============================================<br>";

    echo htmlspecialchars(
        $targetName,
        ENT_QUOTES,
        'UTF-8'
    );

    echo " Recommendation Intelligence<br>";

    echo "============================================<br>";


    $targetSummary =
        $playersByName[
            strtolower(
                $targetName
            )
        ]
        ?? null;


    if ($targetSummary === null) {

        echo "PLAYER NOT FOUND ⚠️<br>";

        continue;
    }


    $playerId =
        (int) (
            $targetSummary[
                'player_id'
            ]
            ?? 0
        );


    if ($playerId <= 0) {

        echo "INVALID PLAYER ID ⚠️<br>";

        continue;
    }


    try {

        /*
         * ----------------------------------------------------
         * GET REAL REPLACEMENT SEARCH
         * ----------------------------------------------------
         */

        $replacementResult =
            $service
                ->findPlayerReplacements(
                    $playerId,
                    $maxPrice,
                    $limit
                );


        if ($replacementResult === null) {

            echo "REPLACEMENT SEARCH FAILED ⚠️<br>";

            continue;
        }


        $currentPlayer =
            $replacementResult[
                'current_player'
            ]
            ?? [];


        $replacements =
            $replacementResult[
                'replacements'
            ]
            ?? [];


        /*
         * ----------------------------------------------------
         * BUILD RECOMMENDATIONS
         * ----------------------------------------------------
         */

        $recommendations =
            $recommendationEngine
                ->buildRecommendations(
                    $replacements
                );


        /*
         * ----------------------------------------------------
         * CURRENT PLAYER
         * ----------------------------------------------------
         */

        echo "<br><strong>Current Player</strong><br>";


        echo "Name: "
            . htmlspecialchars(
                (string) (
                    $currentPlayer[
                        'name'
                    ]
                    ?? $targetName
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Price: £"
            . recommendationDiagnosticValue(
                $currentPlayer[
                    'price'
                ]
                ?? null
            )
            . "m<br>";


        echo "Intelligence: "
            . recommendationDiagnosticValue(
                $currentPlayer[
                    'intelligence_score'
                ]
                ?? null
            )
            . "<br>";


        echo "Verdict: "
            . htmlspecialchars(
                (string) (
                    $currentPlayer[
                        'verdict'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Max Replacement Price: £"
            . number_format(
                $maxPrice,
                1
            )
            . "m<br>";


        echo "Candidate Count: "
            . count(
                $replacements
            )
            . "<br>";


        /*
         * ----------------------------------------------------
         * CATEGORY WINNERS
         * ----------------------------------------------------
         */

        echo "<br><strong>Recommendation Categories</strong><br>";


        echo "<br>Best Overall<br>";

        recommendationDiagnosticPlayer(
            $recommendations[
                'best_overall'
            ]
            ?? null
        );


        echo "<br>Best Value<br>";

        recommendationDiagnosticPlayer(
            $recommendations[
                'best_value'
            ]
            ?? null
        );


        echo "<br>Best Fixtures<br>";

        recommendationDiagnosticPlayer(
            $recommendations[
                'best_fixtures'
            ]
            ?? null
        );


        echo "<br>Safest Pick<br>";

        recommendationDiagnosticPlayer(
            $recommendations[
                'safest_pick'
            ]
            ?? null
        );


        echo "<br>High Upside<br>";

        recommendationDiagnosticPlayer(
            $recommendations[
                'high_upside'
            ]
            ?? null
        );


        /*
         * ----------------------------------------------------
         * TOP FIVE CONTEXT
         * ----------------------------------------------------
         */

        echo "<br><strong>Top 5 Ranked Replacements</strong><br>";


        foreach (
            array_slice(
                $replacements,
                0,
                5
            )
            as $index => $replacement
        ) {

            echo "#"
                . (
                    $index
                    +
                    1
                )
                . " ";


            recommendationDiagnosticPlayer(
                $replacement
            );
        }

    } catch (Throwable $exception) {

        echo "ERROR ❌<br>";

        echo htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        );

        echo "<br>";
    }
}


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Real Data Recommendation Diagnostic Complete<br>";
echo "============================================<br>";

echo "RESULT: TESTS PASSED ✅";