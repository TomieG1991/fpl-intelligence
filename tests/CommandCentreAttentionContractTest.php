<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Command Centre Attention Contract Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * ASSERTION HELPER
 * ============================================================
 */

function assertCommandCentreAttention(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: {$message}<br>";

    } else {

        $failed++;

        echo "FAIL: {$message}<br>";
    }
}


/*
 * ============================================================
 * PLAYER INTELLIGENCE TEST DOUBLE
 * ============================================================
 */

class CommandCentreAttentionPlayerIntelligenceDouble
{
    public int $callCount =
        0;


    public function getGameweekDecision(
        array $squad,
        float $bank = 0.0
    ): array {

        $this->callCount++;


        return [

            'status' =>
                'success',

            'gameweek' => [

                'status' =>
                    'success'
            ],

            'captaincy' => [

                'status' =>
                    'success'
            ],

            'transfers' => [

                'status' =>
                    'success'
            ],

            'decision' => [

                'status' =>
                    'success',

                'overall_action' =>
                    'Consider Transfer',

                'squad_risks' => [

                    'count' =>
                        2,

                    'critical_count' =>
                        0,

                    'high_count' =>
                        1,

                    'medium_count' =>
                        1,

                    'low_count' =>
                        0,

                    'starting_xi_risk_count' =>
                        1,

                    'has_critical_risk' =>
                        false,

                    'has_high_risk' =>
                        true,

                    'risks' => [

                        [
                            'player_id' =>
                                101,

                            'name' =>
                                'Risk Player',

                            'location' =>
                                'starting_xi',

                            'type' =>
                                'availability',

                            'severity' =>
                                'high',

                            'value' =>
                                50.0,

                            'message' =>
                                'Risk Player has a significant availability concern.'
                        ],

                        [
                            'player_id' =>
                                202,

                            'name' =>
                                'Confidence Player',

                            'location' =>
                                'bench',

                            'type' =>
                                'confidence',

                            'severity' =>
                                'medium',

                            'value' =>
                                40.0,

                            'message' =>
                                'Confidence Player has limited decision reliability.'
                        ]
                    ]
                ],

                'transfer_advice' => [

                    'action' =>
                        'Consider Transfer',

                    'priority' =>
                        'Medium',

                    'score' =>
                        61.5,

                    'recommendations' => [

                        [
                            'player_out_id' =>
                                101,

                            'player_in_id' =>
                                303
                        ]
                    ],

                    'message' =>
                        'A transfer is worth considering.'
                ],

                'key_insights' => [

                    'Two squad reliability risks require attention.',

                    'Transfer priority is currently medium.'
                ]
            ]
        ];
    }
}


/*
 * ============================================================
 * CHIP ORCHESTRATOR TEST DOUBLE
 * ============================================================
 */

class CommandCentreAttentionChipOrchestratorDouble
{
    public int $callCount =
        0;


    public function build(
        array $importedSquad,
        array $players,
        float $budget,
        int $targetGameweek
    ): array {

        $this->callCount++;


        return [

            'wildcard' => [

                'status' =>
                    'Ready',

                'recommendation' =>
                    'Hold',

                'confidence' =>
                    0.82,

                'explanation' =>
                    'Wildcard evidence does not currently support use.'
            ],

            'free_hit' => [

                'status' =>
                    'Ready',

                'recommendation' =>
                    'Hold',

                'confidence' =>
                    0.78,

                'explanation' =>
                    'Free Hit evidence does not currently support use.'
            ],

            'bench_boost' => [

                'status' =>
                    'Ready',

                'recommendation' =>
                    'Consider',

                'confidence' =>
                    0.71,

                'explanation' =>
                    'Bench Boost evidence warrants consideration.'
            ],

            'triple_captain' => [

                'status' =>
                    'Ready',

                'recommendation' =>
                    'Hold',

                'confidence' =>
                    0.84,

                'explanation' =>
                    'Triple Captain evidence does not currently support use.'
            ]
        ];
    }
}


/*
 * ============================================================
 * A. AUTHORITATIVE ATTENTION EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Attention Evidence<br>";
echo "============================================<br>";


$playerIntelligenceDouble =
    new CommandCentreAttentionPlayerIntelligenceDouble();


$chipOrchestratorDouble =
    new CommandCentreAttentionChipOrchestratorDouble();


$service =
    new CommandCentreService(
        $playerIntelligenceDouble,
        null,
        $chipOrchestratorDouble
    );


$mappedSquad = [

    [
        'player_id' =>
            101
    ]
];


$importedSquad = [

    'status' =>
        'success',

    'players' => [

        [
            'fpl_player_id' =>
                1001
        ]
    ]
];


$playerPool = [

    [
        'player_id' =>
            303
    ]
];


$result =
    $service
        ->build(
            $mappedSquad,
            1.5,
            $importedSquad,
            6,
            $playerPool,
            101.5
        );


$expectedSquadRisks = [

    'count' =>
        2,

    'critical_count' =>
        0,

    'high_count' =>
        1,

    'medium_count' =>
        1,

    'low_count' =>
        0,

    'starting_xi_risk_count' =>
        1,

    'has_critical_risk' =>
        false,

    'has_high_risk' =>
        true,

    'risks' => [

        [
            'player_id' =>
                101,

            'name' =>
                'Risk Player',

            'location' =>
                'starting_xi',

            'type' =>
                'availability',

            'severity' =>
                'high',

            'value' =>
                50.0,

            'message' =>
                'Risk Player has a significant availability concern.'
        ],

        [
            'player_id' =>
                202,

            'name' =>
                'Confidence Player',

            'location' =>
                'bench',

            'type' =>
                'confidence',

            'severity' =>
                'medium',

            'value' =>
                40.0,

            'message' =>
                'Confidence Player has limited decision reliability.'
        ]
    ]
];


$expectedTransferAdvice = [

    'action' =>
        'Consider Transfer',

    'priority' =>
        'Medium',

    'score' =>
        61.5,

    'recommendations' => [

        [
            'player_out_id' =>
                101,

            'player_in_id' =>
                303
        ]
    ],

    'message' =>
        'A transfer is worth considering.'
];


$expectedChips = [

    'wildcard' => [

        'status' =>
            'Ready',

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.82,

        'explanation' =>
            'Wildcard evidence does not currently support use.'
    ],

    'free_hit' => [

        'status' =>
            'Ready',

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.78,

        'explanation' =>
            'Free Hit evidence does not currently support use.'
    ],

    'bench_boost' => [

        'status' =>
            'Ready',

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.71,

        'explanation' =>
            'Bench Boost evidence warrants consideration.'
    ],

    'triple_captain' => [

        'status' =>
            'Ready',

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.84,

        'explanation' =>
            'Triple Captain evidence does not currently support use.'
    ]
];


assertCommandCentreAttention(
    $playerIntelligenceDouble->callCount === 1,
    'Gameweek Decision Intelligence is requested exactly once.'
);


assertCommandCentreAttention(
    $chipOrchestratorDouble->callCount === 1,
    'Chip Intelligence is requested exactly once.'
);


assertCommandCentreAttention(
    isset(
        $result[
            'attention'
        ]
    )
    &&
    is_array(
        $result[
            'attention'
        ]
    ),
    'Command Centre exposes Attention Required evidence.'
);


assertCommandCentreAttention(
    (
        $result[
            'attention'
        ][
            'squad_risks'
        ]
        ?? null
    )
    ===
    $expectedSquadRisks,
    'Attention Required preserves authoritative squad-risk evidence unchanged.'
);


assertCommandCentreAttention(
    (
        $result[
            'attention'
        ][
            'transfer_advice'
        ]
        ?? null
    )
    ===
    $expectedTransferAdvice,
    'Attention Required preserves authoritative transfer-advice evidence unchanged.'
);


assertCommandCentreAttention(
    (
        $result[
            'attention'
        ][
            'chips'
        ]
        ?? null
    )
    ===
    $expectedChips,
    'Attention Required preserves authoritative Chip Intelligence evidence unchanged.'
);


/*
 * ============================================================
 * B. NO INDEPENDENT ATTENTION CLASSIFICATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. No Independent Attention Classification<br>";
echo "============================================<br>";


assertCommandCentreAttention(
    (
        $result[
            'decision'
        ][
            'squad_risks'
        ]
        ?? null
    )
    ===
    (
        $result[
            'attention'
        ][
            'squad_risks'
        ]
        ?? null
    ),
    'Attention Required does not recalculate or alter squad-risk classification.'
);


assertCommandCentreAttention(
    (
        $result[
            'decision'
        ][
            'transfer_advice'
        ]
        ?? null
    )
    ===
    (
        $result[
            'attention'
        ][
            'transfer_advice'
        ]
        ?? null
    ),
    'Attention Required does not recalculate or alter transfer priority.'
);


assertCommandCentreAttention(
    (
        $result[
            'chips'
        ]
        ?? null
    )
    ===
    (
        $result[
            'attention'
        ][
            'chips'
        ]
        ?? null
    ),
    'Attention Required does not recalculate or filter chip recommendations.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";


echo "Assertions passed: {$passed}<br>";
echo "Assertions failed: {$failed}<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}