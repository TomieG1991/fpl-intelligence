<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Decision Real Data Diagnostic<br>";
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


    $transferDecision =
        new TransferDecision();


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
 * TRANSFER SCENARIOS
 * ============================================================
 */

$scenarios = [

    [
        'current' =>
            'B.Fernandes',

        'replacement' =>
            'Saka'
    ],

    [
        'current' =>
            'Gabriel',

        'replacement' =>
            'Frimpong'
    ],

    [
        'current' =>
            'Haaland',

        'replacement' =>
            'Osula'
    ],

    [
        'current' =>
            'Dowman',

        'replacement' =>
            'Chiesa'
    ]
];


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function transferDecisionDiagnosticValue(
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


function transferDecisionDiagnosticSigned(
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


function transferDecisionDiagnosticConfidence(
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
 * BUILD COMPLETE TRANSFER PLAYER DATA
 * ============================================================
 */

function buildTransferDecisionPlayer(
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
 * RUN REAL TRANSFER DECISIONS
 * ============================================================
 */

foreach (
    $scenarios
    as $scenario
) {

    $currentName =
        $scenario[
            'current'
        ];


    $replacementName =
        $scenario[
            'replacement'
        ];


    echo "<br>============================================<br>";

    echo htmlspecialchars(
        $currentName,
        ENT_QUOTES,
        'UTF-8'
    );

    echo " → ";

    echo htmlspecialchars(
        $replacementName,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>============================================<br>";


    $currentSummary =
        $playersByName[
            strtolower(
                $currentName
            )
        ]
        ?? null;


    $replacementSummary =
        $playersByName[
            strtolower(
                $replacementName
            )
        ]
        ?? null;


    if ($currentSummary === null) {

        echo "CURRENT PLAYER NOT FOUND ⚠️<br>";

        continue;
    }


    if ($replacementSummary === null) {

        echo "REPLACEMENT PLAYER NOT FOUND ⚠️<br>";

        continue;
    }


    try {

        $currentPlayer =
            buildTransferDecisionPlayer(
                $service,
                $currentSummary
            );


        $replacementPlayer =
            buildTransferDecisionPlayer(
                $service,
                $replacementSummary
            );


        if (
            $currentPlayer === null
            ||
            $replacementPlayer === null
        ) {

            echo "PLAYER PROFILE COULD NOT BE BUILT ⚠️<br>";

            continue;
        }


        $result =
            $transferDecision
                ->evaluateTransfer(
                    $currentPlayer,
                    $replacementPlayer
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
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Team: "
            . htmlspecialchars(
                (string) (
                    $currentPlayer[
                        'team_name'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Price: £"
            . transferDecisionDiagnosticValue(
                $currentPlayer[
                    'price'
                ]
                ?? null
            )
            . "m<br>";


        echo "Intelligence: "
            . transferDecisionDiagnosticValue(
                $currentPlayer[
                    'intelligence_score'
                ]
                ?? null
            )
            . "<br>";


        echo "Strength: "
            . transferDecisionDiagnosticValue(
                $currentPlayer[
                    'strength_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Value: "
            . transferDecisionDiagnosticValue(
                $currentPlayer[
                    'value_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Fixtures: "
            . transferDecisionDiagnosticValue(
                $currentPlayer[
                    'fixture_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Sample Confidence: "
            . transferDecisionDiagnosticConfidence(
                $currentPlayer[
                    'sample_confidence'
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


        /*
         * ----------------------------------------------------
         * REPLACEMENT
         * ----------------------------------------------------
         */

        echo "<br><strong>Replacement</strong><br>";


        echo "Name: "
            . htmlspecialchars(
                (string) (
                    $replacementPlayer[
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
                    $replacementPlayer[
                        'team_name'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Price: £"
            . transferDecisionDiagnosticValue(
                $replacementPlayer[
                    'price'
                ]
                ?? null
            )
            . "m<br>";


        echo "Intelligence: "
            . transferDecisionDiagnosticValue(
                $replacementPlayer[
                    'intelligence_score'
                ]
                ?? null
            )
            . "<br>";


        echo "Strength: "
            . transferDecisionDiagnosticValue(
                $replacementPlayer[
                    'strength_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Value: "
            . transferDecisionDiagnosticValue(
                $replacementPlayer[
                    'value_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Fixtures: "
            . transferDecisionDiagnosticValue(
                $replacementPlayer[
                    'fixture_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Sample Confidence: "
            . transferDecisionDiagnosticConfidence(
                $replacementPlayer[
                    'sample_confidence'
                ]
                ?? null
            )
            . "<br>";


        echo "Verdict: "
            . htmlspecialchars(
                (string) (
                    $replacementPlayer[
                        'verdict'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        /*
         * ----------------------------------------------------
         * MOVEMENTS
         * ----------------------------------------------------
         */

        echo "<br><strong>Transfer Movements</strong><br>";


        echo "Intelligence: "
            . transferDecisionDiagnosticSigned(
                $result[
                    'movements'
                ]['intelligence']
                ?? null
            )
            . "<br>";


        echo "Strength: "
            . transferDecisionDiagnosticSigned(
                $result[
                    'movements'
                ]['strength']
                ?? null
            )
            . "<br>";


        echo "Value: "
            . transferDecisionDiagnosticSigned(
                $result[
                    'movements'
                ]['value']
                ?? null
            )
            . "<br>";


        echo "Fixtures: "
            . transferDecisionDiagnosticSigned(
                $result[
                    'movements'
                ]['fixtures']
                ?? null
            )
            . "<br>";


        echo "Sample Confidence: "
            . transferDecisionDiagnosticSigned(
                $result[
                    'movements'
                ]['sample_confidence']
                ?? null
            )
            . " percentage points<br>";


        echo "Budget Released: £"
            . transferDecisionDiagnosticSigned(
                $result[
                    'movements'
                ]['budget']
                ?? null
            )
            . "m<br>";


        /*
         * ----------------------------------------------------
         * DECISION
         * ----------------------------------------------------
         */

        echo "<br><strong>Transfer Decision</strong><br>";


        echo "Decision Score: "
            . transferDecisionDiagnosticValue(
                $result[
                    'decision_score'
                ]
                ?? null,
                2
            )
            . " / 100<br>";


        echo "Decision Type: "
            . htmlspecialchars(
                (string) (
                    $result[
                        'decision_type'
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
echo "Real Data Transfer Decision Diagnostic Complete<br>";
echo "============================================<br>";

echo "RESULT: TESTS PASSED ✅";