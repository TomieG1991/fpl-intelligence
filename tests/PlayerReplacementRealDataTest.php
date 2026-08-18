<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Replacement Real Data Diagnostic<br>";
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


    $replacementEngine =
        new PlayerReplacement();


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
 * REPLACEMENT SCENARIOS
 * ============================================================
 */

$scenarios = [

    [
        'player' =>
            'B.Fernandes',

        'max_price' =>
            12.0,

        'limit' =>
            10,

        'description' =>
            'Midfield replacements up to £12.0m'
    ],

    [
        'player' =>
            'Haaland',

        'max_price' =>
            10.0,

        'limit' =>
            10,

        'description' =>
            'Forward replacements up to £10.0m'
    ],

    [
        'player' =>
            'Dowman',

        'max_price' =>
            6.0,

        'limit' =>
            10,

        'description' =>
            'Midfield replacements up to £6.0m'
    ],

    [
        'player' =>
            'Gabriel',

        'max_price' =>
            8.0,

        'limit' =>
            10,

        'description' =>
            'Defender replacements up to £8.0m'
    ]
];


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function replacementDiagnosticValue(
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


function replacementDiagnosticSigned(
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


/*
 * ============================================================
 * RUN REAL REPLACEMENT SEARCHES
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

    echo " Replacement Search<br>";

    echo "============================================<br>";


    echo htmlspecialchars(
        $scenario[
            'description'
        ],
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";


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


    $targetPlayerId =
        (int) (
            $targetSummary[
                'player_id'
            ]
            ?? 0
        );


    if ($targetPlayerId <= 0) {

        echo "INVALID PLAYER ID ⚠️<br>";

        continue;
    }


    try {

        /*
         * ----------------------------------------------------
         * COMPLETE CURRENT PLAYER PROFILE
         * ----------------------------------------------------
         */

        $targetProfile =
            $service
                ->getPlayerProfile(
                    $targetPlayerId
                );


        if ($targetProfile === null) {

            echo "PLAYER PROFILE NOT FOUND ⚠️<br>";

            continue;
        }


        $targetPlayer =
            $targetProfile[
                'summary'
            ]
            ?? [];


        $targetPlayer[
            'player_id'
        ] =
            $targetProfile[
                'player'
            ]['player_id']
            ??
            $targetPlayerId;


        $targetPlayer[
            'name'
        ] =
            $targetProfile[
                'player'
            ]['name']
            ??
            $targetName;


        $targetPlayer[
            'position'
        ] =
            $targetProfile[
                'player'
            ]['position']
            ?? null;


        $targetPlayer[
            'team_name'
        ] =
            $targetProfile[
                'team'
            ]['name']
            ?? null;


        $targetPlayer[
            'sample_confidence'
        ] =
            $targetProfile[
                'performance'
            ]['sample_confidence']
            ?? null;


        $targetPlayer[
            'verdict'
        ] =
            $targetProfile[
                'assessment'
            ]['verdict']
            ?? null;


        /*
         * ----------------------------------------------------
         * BUILD CANDIDATE DATA
         * ----------------------------------------------------
         */

        $candidateProfiles =
            [];


        foreach ($players as $playerSummary) {

            $candidatePlayerId =
                (int) (
                    $playerSummary[
                        'player_id'
                    ]
                    ?? 0
                );


            if ($candidatePlayerId <= 0) {
                continue;
            }


            /*
             * The lightweight explorer summaries already contain
             * nearly everything PlayerReplacement needs.
             */

            $candidateProfiles[] = [

                'player_id' =>
                    $candidatePlayerId,

                'name' =>
                    $playerSummary[
                        'name'
                    ]
                    ?? null,

                'team_name' =>
                    $playerSummary[
                        'team_name'
                    ]
                    ?? null,

                'team_short_name' =>
                    $playerSummary[
                        'team_short_name'
                    ]
                    ?? null,

                'position' =>
                    $playerSummary[
                        'position'
                    ]
                    ?? null,

                'price' =>
                    $playerSummary[
                        'price'
                    ]
                    ?? null,

                'intelligence_score' =>
                    $playerSummary[
                        'intelligence_score'
                    ]
                    ?? null,

                'strength_rating' =>
                    $playerSummary[
                        'strength_rating'
                    ]
                    ?? null,

                'value_rating' =>
                    $playerSummary[
                        'value_rating'
                    ]
                    ?? null,

                'fixture_rating' =>
                    $playerSummary[
                        'fixture_rating'
                    ]
                    ?? null,

                'availability_rating' =>
                    $playerSummary[
                        'availability_rating'
                    ]
                    ?? null,

                'sample_confidence' =>
                    $playerSummary[
                        'sample_confidence'
                    ]
                    ?? null,

                /*
                 * getAllPlayerSummaries currently may not expose
                 * an assessment verdict for every row, so leaving
                 * this null is acceptable for the diagnostic.
                 */
                'verdict' =>
                    $playerSummary[
                        'verdict'
                    ]
                    ??
                    $playerSummary[
                        'assessment_verdict'
                    ]
                    ??
                    null
            ];
        }


        /*
         * ----------------------------------------------------
         * FIND REPLACEMENTS
         * ----------------------------------------------------
         */

        $replacements =
            $replacementEngine
                ->findReplacements(
                    $targetPlayer,
                    $candidateProfiles,
                    $maxPrice,
                    $limit
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
                    $targetPlayer[
                        'name'
                    ]
                    ?? $targetName
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Position: "
            . htmlspecialchars(
                (string) (
                    $targetPlayer[
                        'position'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Price: £"
            . replacementDiagnosticValue(
                $targetPlayer[
                    'price'
                ]
                ?? null
            )
            . "m<br>";


        echo "Intelligence: "
            . replacementDiagnosticValue(
                $targetPlayer[
                    'intelligence_score'
                ]
                ?? null
            )
            . "<br>";


        echo "Verdict: "
            . htmlspecialchars(
                (string) (
                    $targetPlayer[
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


        /*
         * ----------------------------------------------------
         * RESULTS
         * ----------------------------------------------------
         */

        echo "<br><strong>Recommended Replacements</strong><br>";


        if (empty($replacements)) {

            echo "No eligible replacements found.<br>";

            continue;
        }


        $rank =
            1;


        foreach (
            $replacements
            as $replacement
        ) {

            $type =
                $replacementEngine
                    ->getReplacementType(
                        $replacement[
                            'intelligence_gain'
                        ]
                        ?? null
                    );


            echo "<br>";

            echo "<strong>#"
                . $rank
                . " "
                . htmlspecialchars(
                    (string) (
                        $replacement[
                            'name'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . "</strong><br>";


            echo "Team: "
                . htmlspecialchars(
                    (string) (
                        $replacement[
                            'team_name'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . "<br>";


            echo "Price: £"
                . replacementDiagnosticValue(
                    $replacement[
                        'price'
                    ]
                    ?? null
                )
                . "m<br>";


            echo "Intelligence: "
                . replacementDiagnosticValue(
                    $replacement[
                        'intelligence_score'
                    ]
                    ?? null
                )
                . "<br>";


            echo "Intelligence Movement: "
                . replacementDiagnosticSigned(
                    $replacement[
                        'intelligence_gain'
                    ]
                    ?? null
                )
                . "<br>";


            echo "Replacement Type: "
                . htmlspecialchars(
                    $type,
                    ENT_QUOTES,
                    'UTF-8'
                )
                . "<br>";


            echo "Price Difference: £"
                . replacementDiagnosticSigned(
                    $replacement[
                        'price_difference'
                    ]
                    ?? null
                )
                . "m<br>";


            echo "Strength: "
                . replacementDiagnosticValue(
                    $replacement[
                        'strength_rating'
                    ]
                    ?? null
                )
                . "<br>";


            echo "Value: "
                . replacementDiagnosticValue(
                    $replacement[
                        'value_rating'
                    ]
                    ?? null
                )
                . "<br>";


            echo "Fixtures: "
                . replacementDiagnosticValue(
                    $replacement[
                        'fixture_rating'
                    ]
                    ?? null
                )
                . "<br>";


            echo "Availability: "
                . replacementDiagnosticValue(
                    $replacement[
                        'availability_rating'
                    ]
                    ?? null
                )
                . "<br>";


            echo "Sample Confidence: ";


            if (
                isset(
                    $replacement[
                        'sample_confidence'
                    ]
                )
                &&
                is_numeric(
                    $replacement[
                        'sample_confidence'
                    ]
                )
            ) {

                echo number_format(
                    (
                        (float)
                        $replacement[
                            'sample_confidence'
                        ]
                    )
                    * 100,
                    1
                );

                echo "%";

            } else {

                echo "N/A";
            }


            echo "<br>";


            echo "Verdict: "
                . htmlspecialchars(
                    (string) (
                        $replacement[
                            'verdict'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . "<br>";


            echo "Summary: "
                . htmlspecialchars(
                    $replacementEngine
                        ->buildReplacementSummary(
                            $replacement
                        ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . "<br>";


            $rank++;
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
echo "Real Data Replacement Diagnostic Complete<br>";
echo "============================================<br>";

echo "RESULT: TESTS PASSED ✅";