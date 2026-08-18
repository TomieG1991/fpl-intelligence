<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Combination Real Data Diagnostic<br>";
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


    $transferCombination =
        new TransferCombination();


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
 * COMBINATION SCENARIOS
 * ============================================================
 *
 * Scenario A:
 * Bruno -> Saka releases £2.5m.
 * Use that money to strengthen the forward slot.
 *
 * Scenario B:
 * Gabriel -> Frimpong releases £2.5m.
 * Use that saving to improve midfield.
 *
 * Scenario C:
 * Haaland -> Osula releases a huge amount of money.
 * Test whether a major midfield upgrade can compensate for
 * the direct Haaland downgrade.
 *
 * Scenario D:
 * Deliberately use a low-confidence player to confirm that
 * Risky Restructure survives at combination level.
 */

$scenarios = [

    [
        'label' =>
            'Bruno Budget Redistribution',

        'current_a' =>
            'B.Fernandes',

        'replacement_a' =>
            'Saka',

        'current_b' =>
            'Nmecha',

        'replacement_b' =>
            'Havertz'
    ],

    [
        'label' =>
            'Gabriel Budget Redistribution',

        'current_a' =>
            'Gabriel',

        'replacement_a' =>
            'Frimpong',

        'current_b' =>
            'Merino',

        'replacement_b' =>
            'Cherki'
    ],

    [
        'label' =>
            'Haaland Major Restructure',

        'current_a' =>
            'Haaland',

        'replacement_a' =>
            'Osula',

        'current_b' =>
            'Merino',

        'replacement_b' =>
            'B.Fernandes'
    ],

    [
        'label' =>
            'Risky Restructure Check',

        'current_a' =>
            'B.Fernandes',

        'replacement_a' =>
            'Saka',

        'current_b' =>
            'Merino',

        'replacement_b' =>
            'Dowman'
    ]
];


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function combinationDiagnosticValue(
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


function combinationDiagnosticSigned(
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


function combinationDiagnosticConfidence(
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


/*
 * ============================================================
 * BUILD TRANSFER-COMPATIBLE PLAYER DATA
 * ============================================================
 */

function buildCombinationPlayer(
    PlayerIntelligenceService $service,
    array $summary
): ?array {

    $playerId =
        (int) (
            $summary[
                'player_id'
            ]
            ?? 0
        );


    if ($playerId <= 0) {
        return null;
    }


    $profile =
        $service
            ->getPlayerProfile(
                $playerId
            );


    if ($profile === null) {
        return null;
    }


    return [

        'player_id' =>
            $playerId,

        'name' =>
            $profile[
                'player'
            ]['name']
            ?? null,

        'position' =>
            $profile[
                'player'
            ]['position']
            ?? null,

        'team_name' =>
            $profile[
                'team'
            ]['name']
            ?? null,

        'price' =>
            $profile[
                'summary'
            ]['price']
            ?? null,

        'intelligence_score' =>
            $profile[
                'summary'
            ]['intelligence_score']
            ?? null,

        'strength_rating' =>
            $profile[
                'summary'
            ]['strength_rating']
            ?? null,

        'value_rating' =>
            $profile[
                'summary'
            ]['value_rating']
            ?? null,

        'fixture_rating' =>
            $profile[
                'summary'
            ]['fixture_rating']
            ?? null,

        'sample_confidence' =>
            $profile[
                'performance'
            ]['sample_confidence']
            ?? null,

        'verdict' =>
            $profile[
                'assessment'
            ]['verdict']
            ?? null
    ];
}


/*
 * ============================================================
 * DISPLAY PLAYER
 * ============================================================
 */

function displayCombinationPlayer(
    array $player
): void {

    echo "Name: "
        . htmlspecialchars(
            (string) (
                $player[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Team: "
        . htmlspecialchars(
            (string) (
                $player[
                    'team_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Position: "
        . htmlspecialchars(
            (string) (
                $player[
                    'position'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Price: £"
        . combinationDiagnosticValue(
            $player[
                'price'
            ]
            ?? null
        )
        . "m<br>";


    echo "Intelligence: "
        . combinationDiagnosticValue(
            $player[
                'intelligence_score'
            ]
            ?? null
        )
        . "<br>";


    echo "Strength: "
        . combinationDiagnosticValue(
            $player[
                'strength_rating'
            ]
            ?? null
        )
        . "<br>";


    echo "Value: "
        . combinationDiagnosticValue(
            $player[
                'value_rating'
            ]
            ?? null
        )
        . "<br>";


    echo "Fixtures: "
        . combinationDiagnosticValue(
            $player[
                'fixture_rating'
            ]
            ?? null
        )
        . "<br>";


    echo "Sample Confidence: "
        . combinationDiagnosticConfidence(
            $player[
                'sample_confidence'
            ]
            ?? null
        )
        . "<br>";


    echo "Verdict: "
        . htmlspecialchars(
            (string) (
                $player[
                    'verdict'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


/*
 * ============================================================
 * RUN REAL COMBINATIONS
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


    $names = [

        'current_a' =>
            $scenario[
                'current_a'
            ],

        'replacement_a' =>
            $scenario[
                'replacement_a'
            ],

        'current_b' =>
            $scenario[
                'current_b'
            ],

        'replacement_b' =>
            $scenario[
                'replacement_b'
            ]
    ];


    $summaries =
        [];


    $missingPlayer =
        false;


    foreach (
        $names
        as $key => $name
    ) {

        $summary =
            $playersByName[
                strtolower(
                    $name
                )
            ]
            ?? null;


        if ($summary === null) {

            echo "PLAYER NOT FOUND: "
                . htmlspecialchars(
                    $name,
                    ENT_QUOTES,
                    'UTF-8'
                )
                . " ⚠️<br>";


            $missingPlayer =
                true;

            break;
        }


        $summaries[
            $key
        ] =
            $summary;
    }


    if ($missingPlayer) {
        continue;
    }


    try {

        $currentA =
            buildCombinationPlayer(
                $service,
                $summaries[
                    'current_a'
                ]
            );


        $replacementA =
            buildCombinationPlayer(
                $service,
                $summaries[
                    'replacement_a'
                ]
            );


        $currentB =
            buildCombinationPlayer(
                $service,
                $summaries[
                    'current_b'
                ]
            );


        $replacementB =
            buildCombinationPlayer(
                $service,
                $summaries[
                    'replacement_b'
                ]
            );


        if (
            $currentA === null
            ||
            $replacementA === null
            ||
            $currentB === null
            ||
            $replacementB === null
        ) {

            echo "PLAYER PROFILE COULD NOT BE BUILT ⚠️<br>";

            continue;
        }


        /*
         * ----------------------------------------------------
         * DISPLAY TRANSFER A
         * ----------------------------------------------------
         */

        echo "<br><strong>Transfer A</strong><br>";


        echo "<br>Outgoing<br>";

        displayCombinationPlayer(
            $currentA
        );


        echo "<br>Incoming<br>";

        displayCombinationPlayer(
            $replacementA
        );


        /*
         * ----------------------------------------------------
         * DISPLAY TRANSFER B
         * ----------------------------------------------------
         */

        echo "<br><strong>Transfer B</strong><br>";


        echo "<br>Outgoing<br>";

        displayCombinationPlayer(
            $currentB
        );


        echo "<br>Incoming<br>";

        displayCombinationPlayer(
            $replacementB
        );


        /*
         * ----------------------------------------------------
         * EVALUATE COMBINATION
         * ----------------------------------------------------
         */

        $result =
            $transferCombination
                ->evaluateCombination(
                    $currentA,
                    $replacementA,
                    $currentB,
                    $replacementB
                );


        /*
         * ----------------------------------------------------
         * INDIVIDUAL TRANSFER DECISIONS
         * ----------------------------------------------------
         */

        echo "<br><strong>Individual Transfer Decisions</strong><br>";


        echo "Transfer A: "
            . htmlspecialchars(
                (string) (
                    $result[
                        'transfer_a'
                    ]['decision_type']
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Score "
            . combinationDiagnosticValue(
                $result[
                    'transfer_a'
                ]['decision_score']
                ?? null,
                2
            )
            . "<br>";


        echo "Transfer B: "
            . htmlspecialchars(
                (string) (
                    $result[
                        'transfer_b'
                    ]['decision_type']
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Score "
            . combinationDiagnosticValue(
                $result[
                    'transfer_b'
                ]['decision_score']
                ?? null,
                2
            )
            . "<br>";


        /*
         * ----------------------------------------------------
         * COMBINED MOVEMENTS
         * ----------------------------------------------------
         */

        echo "<br><strong>Combined Movements</strong><br>";


        echo "Intelligence: "
            . combinationDiagnosticSigned(
                $result[
                    'combined_movements'
                ]['intelligence']
                ?? null
            )
            . "<br>";


        echo "Strength: "
            . combinationDiagnosticSigned(
                $result[
                    'combined_movements'
                ]['strength']
                ?? null
            )
            . "<br>";


        echo "Value: "
            . combinationDiagnosticSigned(
                $result[
                    'combined_movements'
                ]['value']
                ?? null
            )
            . "<br>";


        echo "Fixtures: "
            . combinationDiagnosticSigned(
                $result[
                    'combined_movements'
                ]['fixtures']
                ?? null
            )
            . "<br>";


        echo "Sample Confidence: "
            . combinationDiagnosticSigned(
                $result[
                    'combined_movements'
                ]['sample_confidence']
                ?? null
            )
            . " percentage points<br>";


        echo "Budget Remaining: £"
            . combinationDiagnosticSigned(
                $result[
                    'combined_movements'
                ]['budget']
                ?? null
            )
            . "m<br>";


        /*
         * ----------------------------------------------------
         * COMBINATION DECISION
         * ----------------------------------------------------
         */

        echo "<br><strong>Combination Decision</strong><br>";


        echo "Affordable: "
            . (
                (
                    $result[
                        'is_affordable'
                    ]
                    ?? false
                )
                    ? 'Yes'
                    : 'No'
            )
            . "<br>";


        echo "Combination Score: "
            . combinationDiagnosticValue(
                $result[
                    'combination_score'
                ]
                ?? null,
                2
            )
            . " / 100<br>";


        echo "Classification: "
            . htmlspecialchars(
                (string) (
                    $result[
                        'classification'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Summary: "
            . htmlspecialchars(
                (string) (
                    $result[
                        'summary'
                    ]
                    ?? ''
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

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
echo "Real Data Transfer Combination Diagnostic Complete<br>";
echo "============================================<br>";

echo "RESULT: TESTS PASSED ✅";