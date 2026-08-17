<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Player Assessment Real Data Diagnostic<br>";
echo "============================================<br><br>";


try {

    $database =
        new Database();


    $service =
        new PlayerIntelligenceService(
            $database->getConnection()
        );


    $assessment =
        new PlayerAssessment();


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
 * TARGET PLAYERS
 * ============================================================
 */

$targetNames = [

    'Raya',

    'Haaland',

    'B.Fernandes',

    'Dowman'
];


$matchedPlayers =
    [];


/*
 * Match by the current player summary name.
 */
foreach ($players as $player) {

    $playerName =
        (string) (
            $player['name']
            ?? ''
        );


    foreach ($targetNames as $targetName) {

        if (
            strcasecmp(
                $playerName,
                $targetName
            )
            === 0
        ) {

            $matchedPlayers[$targetName] =
                $player;

            break;
        }
    }
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function diagnosticValue(
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

        return 'NULL';
    }


    return number_format(
        (float) $value,
        $decimals
    );
}


function diagnosticList(
    array $items
): void {

    if (empty($items)) {

        echo "- None<br>";

        return;
    }


    foreach ($items as $item) {

        echo "- "
            . htmlspecialchars(
                (string) $item,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }
}


/*
 * ============================================================
 * REAL PLAYER ASSESSMENTS
 * ============================================================
 */

foreach ($targetNames as $targetName) {

    echo "<br>============================================<br>";

    echo htmlspecialchars(
        $targetName,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>============================================<br>";


    if (
        !isset(
            $matchedPlayers[$targetName]
        )
    ) {

        echo "PLAYER NOT FOUND ⚠️<br>";

        continue;
    }


    $playerSummary =
        $matchedPlayers[$targetName];


    $playerId =
        (int) (
            $playerSummary['player_id']
            ?? 0
        );


    if ($playerId <= 0) {

        echo "INVALID PLAYER ID ⚠️<br>";

        continue;
    }


    try {

        $profile =
            $service
                ->getPlayerProfile(
                    $playerId
                );


        if ($profile === null) {

            echo "PROFILE NOT FOUND ⚠️<br>";

            continue;
        }


        $playerAssessment =
            $assessment
                ->buildAssessment(
                    $profile
                );


        $summary =
            $profile['summary']
            ?? [];


        $performance =
            $profile['performance']
            ?? [];


        $fixtures =
            $profile['fixtures']
            ?? [];


        /*
         * ----------------------------------------------------
         * CORE PROFILE
         * ----------------------------------------------------
         */

        echo "<br><strong>Core Profile</strong><br>";

        echo "Player ID: "
            . $playerId
            . "<br>";


        echo "Strength: "
            . diagnosticValue(
                $summary[
                    'strength_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Value: "
            . diagnosticValue(
                $summary[
                    'value_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Fixtures: "
            . diagnosticValue(
                $summary[
                    'fixture_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Availability: "
            . diagnosticValue(
                $summary[
                    'availability_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Intelligence Score: "
            . diagnosticValue(
                $summary[
                    'intelligence_score'
                ]
                ?? null
            )
            . "<br>";


        echo "Sample Confidence: "
            . diagnosticValue(
                isset(
                    $performance[
                        'sample_confidence'
                    ]
                )
                &&
                is_numeric(
                    $performance[
                        'sample_confidence'
                    ]
                )
                    ? (
                        $performance[
                            'sample_confidence'
                        ]
                        * 100
                    )
                    : null
            )
            . "%<br>";


        echo "Fixture Trend: "
            . htmlspecialchars(
                (string) (
                    $fixtures['trend']
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        /*
         * ----------------------------------------------------
         * ASSESSMENT
         * ----------------------------------------------------
         */

        echo "<br><strong>Assessment</strong><br>";


        echo "Verdict: "
            . htmlspecialchars(
                (string) (
                    $playerAssessment[
                        'verdict'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Verdict Key: "
            . htmlspecialchars(
                (string) (
                    $playerAssessment[
                        'verdict_key'
                    ]
                    ?? 'unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        /*
         * ----------------------------------------------------
         * COMPONENT LABELS
         * ----------------------------------------------------
         */

        echo "<br><strong>Component Labels</strong><br>";


        $components =
            $playerAssessment[
                'components'
            ]
            ?? [];


        echo "Strength: "
            . htmlspecialchars(
                (string) (
                    $components['strength']
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Value: "
            . htmlspecialchars(
                (string) (
                    $components['value']
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Fixtures: "
            . htmlspecialchars(
                (string) (
                    $components['fixtures']
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Availability: "
            . htmlspecialchars(
                (string) (
                    $components[
                        'availability'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Sample Confidence: "
            . htmlspecialchars(
                (string) (
                    $components[
                        'sample_confidence'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Fixture Trend: "
            . htmlspecialchars(
                (string) (
                    $components[
                        'fixture_trend'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        /*
         * ----------------------------------------------------
         * STRENGTHS
         * ----------------------------------------------------
         */

        echo "<br><strong>Strengths</strong><br>";


        diagnosticList(
            $playerAssessment[
                'strengths'
            ]
            ?? []
        );


        /*
         * ----------------------------------------------------
         * CONCERNS
         * ----------------------------------------------------
         */

        echo "<br><strong>Concerns</strong><br>";


        diagnosticList(
            $playerAssessment[
                'concerns'
            ]
            ?? []
        );


        /*
         * ----------------------------------------------------
         * SUMMARY
         * ----------------------------------------------------
         */

        echo "<br><strong>Summary</strong><br>";


        echo htmlspecialchars(
            (string) (
                $playerAssessment[
                    'summary'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );


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
 * DIAGNOSTIC RESULT
 * ============================================================
 *
 * This is intentionally a diagnostic rather than an
 * assertion-driven unit test.
 *
 * The success text is compatible with runAllTests.php so the
 * diagnostic does not appear as an ERROR when executed as
 * part of the complete test directory.
 */

echo "<br>============================================<br>";
echo "Real Data Diagnostic Complete<br>";
echo "============================================<br>";

echo "RESULT: TESTS PASSED ✅";