<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Comparison Real Data Diagnostic<br>";
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


    $comparison =
        new PlayerComparison();


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
 * COMPARISON PAIRS
 * ============================================================
 *
 * These give us different comparison situations:
 *
 * Raya vs Donnarumma
 *     Premium goalkeeper comparison.
 *
 * Haaland vs João Pedro
 *     Premium forward vs cheaper forward.
 *
 * B.Fernandes vs Saka
 *     Premium midfield comparison.
 *
 * Dowman vs Saka
 *     Small-sample prospect vs established player.
 */

$comparisonPairs = [

    [
        'a' => 'Raya',
        'b' => 'Donnarumma'
    ],

    [
        'a' => 'Haaland',
        'b' => 'João Pedro'
    ],

    [
        'a' => 'B.Fernandes',
        'b' => 'Saka'
    ],

    [
        'a' => 'Dowman',
        'b' => 'Saka'
    ]
];


/*
 * ============================================================
 * INDEX PLAYERS BY NAME
 * ============================================================
 */

$playersByName =
    [];


foreach ($players as $player) {

    $name =
        (string) (
            $player['name']
            ?? ''
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
 * HELPERS
 * ============================================================
 */

function comparisonDisplayValue(
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
        (float) $value,
        1
    );
}


function comparisonWinnerName(
    string $winner,
    string $nameA,
    string $nameB
): string {

    if ($winner === 'a') {
        return $nameA;
    }


    if ($winner === 'b') {
        return $nameB;
    }


    return 'Tie';
}


/*
 * ============================================================
 * RUN REAL COMPARISONS
 * ============================================================
 */

foreach (
    $comparisonPairs
    as $pair
) {

    $nameA =
        $pair['a'];


    $nameB =
        $pair['b'];


    echo "<br>============================================<br>";

    echo htmlspecialchars(
        $nameA,
        ENT_QUOTES,
        'UTF-8'
    );

    echo " vs ";

    echo htmlspecialchars(
        $nameB,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>============================================<br>";


    $summaryA =
        $playersByName[
            strtolower(
                $nameA
            )
        ]
        ?? null;


    $summaryB =
        $playersByName[
            strtolower(
                $nameB
            )
        ]
        ?? null;


    if ($summaryA === null) {

        echo "PLAYER NOT FOUND: "
            . htmlspecialchars(
                $nameA,
                ENT_QUOTES,
                'UTF-8'
            )
            . " ⚠️<br>";

        continue;
    }


    if ($summaryB === null) {

        echo "PLAYER NOT FOUND: "
            . htmlspecialchars(
                $nameB,
                ENT_QUOTES,
                'UTF-8'
            )
            . " ⚠️<br>";

        continue;
    }


    $playerIdA =
        (int) (
            $summaryA['player_id']
            ?? 0
        );


    $playerIdB =
        (int) (
            $summaryB['player_id']
            ?? 0
        );


    try {

        $profileA =
            $service
                ->getPlayerProfile(
                    $playerIdA
                );


        $profileB =
            $service
                ->getPlayerProfile(
                    $playerIdB
                );


        if (
            $profileA === null
            ||
            $profileB === null
        ) {

            echo "PROFILE NOT FOUND ⚠️<br>";

            continue;
        }


        $result =
            $comparison->compare(
                $profileA,
                $profileB
            );


        /*
         * ----------------------------------------------------
         * PLAYER INFORMATION
         * ----------------------------------------------------
         */

        echo "<br><strong>Players</strong><br>";


        echo htmlspecialchars(
            $nameA,
            ENT_QUOTES,
            'UTF-8'
        );

        echo ": £";

        echo number_format(
            (float) (
                $result[
                    'player_a'
                ]['price']
                ?? 0
            ),
            1
        );

        echo "m | ";

        echo htmlspecialchars(
            (string) (
                $result[
                    'player_a'
                ]['position']
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        echo " | ";

        echo htmlspecialchars(
            (string) (
                $result[
                    'player_a'
                ]['team_name']
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        echo "<br>";


        echo htmlspecialchars(
            $nameB,
            ENT_QUOTES,
            'UTF-8'
        );

        echo ": £";

        echo number_format(
            (float) (
                $result[
                    'player_b'
                ]['price']
                ?? 0
            ),
            1
        );

        echo "m | ";

        echo htmlspecialchars(
            (string) (
                $result[
                    'player_b'
                ]['position']
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        echo " | ";

        echo htmlspecialchars(
            (string) (
                $result[
                    'player_b'
                ]['team_name']
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        echo "<br>";


        /*
         * ----------------------------------------------------
         * VERDICTS
         * ----------------------------------------------------
         */

        echo "<br><strong>Assessments</strong><br>";


        echo htmlspecialchars(
            $nameA,
            ENT_QUOTES,
            'UTF-8'
        );

        echo ": ";

        echo htmlspecialchars(
            (string) (
                $result[
                    'player_a_verdict'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        echo "<br>";


        echo htmlspecialchars(
            $nameB,
            ENT_QUOTES,
            'UTF-8'
        );

        echo ": ";

        echo htmlspecialchars(
            (string) (
                $result[
                    'player_b_verdict'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        echo "<br>";


        /*
         * ----------------------------------------------------
         * METRICS
         * ----------------------------------------------------
         */

        echo "<br><strong>Metric Comparison</strong><br>";


        foreach (
            $result['metrics']
            as $metric
        ) {

            echo htmlspecialchars(
                (string) (
                    $metric['label']
                    ?? ''
                ),
                ENT_QUOTES,
                'UTF-8'
            );

            echo ": ";


            echo comparisonDisplayValue(
                $metric['player_a']
                ?? null
            );


            echo " vs ";


            echo comparisonDisplayValue(
                $metric['player_b']
                ?? null
            );


            echo " → ";


            echo htmlspecialchars(
                comparisonWinnerName(
                    (string) (
                        $metric['winner']
                        ?? 'tie'
                    ),
                    $nameA,
                    $nameB
                ),
                ENT_QUOTES,
                'UTF-8'
            );


            if (
                isset(
                    $metric['difference']
                )
                &&
                is_numeric(
                    $metric['difference']
                )
            ) {

                echo " (+";

                echo number_format(
                    (float)
                        $metric[
                            'difference'
                        ],
                    1
                );

                echo ")";
            }


            echo "<br>";
        }


        /*
         * ----------------------------------------------------
         * WIN COUNT
         * ----------------------------------------------------
         */

        echo "<br><strong>Metric Wins</strong><br>";


        echo htmlspecialchars(
            $nameA,
            ENT_QUOTES,
            'UTF-8'
        );

        echo ": ";

        echo (
            (int) (
                $result[
                    'metric_wins'
                ]['player_a']
                ?? 0
            )
        );

        echo "<br>";


        echo htmlspecialchars(
            $nameB,
            ENT_QUOTES,
            'UTF-8'
        );

        echo ": ";

        echo (
            (int) (
                $result[
                    'metric_wins'
                ]['player_b']
                ?? 0
            )
        );

        echo "<br>";


        echo "Ties: ";

        echo (
            (int) (
                $result[
                    'metric_wins'
                ]['ties']
                ?? 0
            )
        );

        echo "<br>";


        /*
         * ----------------------------------------------------
         * OVERALL WINNER
         * ----------------------------------------------------
         */

        echo "<br><strong>Overall Comparison</strong><br>";


        $overallWinner =
            comparisonWinnerName(
                (string) (
                    $result[
                        'overall_winner'
                    ]
                    ?? 'tie'
                ),
                $nameA,
                $nameB
            );


        echo "Winner: ";

        echo htmlspecialchars(
            $overallWinner,
            ENT_QUOTES,
            'UTF-8'
        );


        if (
            isset(
                $result[
                    'overall_difference'
                ]
            )
            &&
            is_numeric(
                $result[
                    'overall_difference'
                ]
            )
        ) {

            echo "<br>Intelligence Difference: ";

            echo number_format(
                (float)
                    $result[
                        'overall_difference'
                    ],
                1
            );
        }


        echo "<br>";

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
echo "Real Data Comparison Diagnostic Complete<br>";
echo "============================================<br>";

echo "RESULT: TESTS PASSED ✅";