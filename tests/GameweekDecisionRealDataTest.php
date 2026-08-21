<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Decision Real Data Diagnostic<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekDecisionRealCheck(
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


/*
 * ============================================================
 * DISPLAY HELPERS
 * ============================================================
 */

function gameweekDecisionRealEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function gameweekDecisionRealValue(
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


/*
 * ============================================================
 * OVERALL TIMER
 * ============================================================
 */

$startedAt =
    microtime(
        true
    );


/*
 * ============================================================
 * SCENARIO A
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Setup<br>";
echo "============================================<br>";


$database =
    new Database();


$db =
    $database
        ->getConnection();


$service =
    new PlayerIntelligenceService(
        $db
    );


gameweekDecisionRealCheck(
    'Database connection is available',
    $db instanceof PDO
);


gameweekDecisionRealCheck(
    'Player Intelligence Service is available',
    $service instanceof PlayerIntelligenceService
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * REAL PLAYER SUMMARIES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Real Player Summaries<br>";
echo "============================================<br>";


$summaryStartedAt =
    microtime(
        true
    );


$summaries =
    $service
        ->getAllPlayerSummaries();


$summaryRuntime =
    microtime(
        true
    )
    -
    $summaryStartedAt;


gameweekDecisionRealCheck(
    'Real player summaries return an array',
    is_array(
        $summaries
    )
);


gameweekDecisionRealCheck(
    'Real player summaries are not empty',
    !empty(
        $summaries
    )
);


gameweekDecisionRealCheck(
    'Real player pool contains at least 300 players',
    count(
        $summaries
    )
    >= 300
);


echo "Player Summaries: "
    . count(
        $summaries
    )
    . "<br>";


echo "Summary Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * REAL-DATA SQUAD CONSTRUCTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Real-Data Squad Construction<br>";
echo "============================================<br>";


$positionRequirements = [

    'GK' =>
        2,

    'DEF' =>
        5,

    'MID' =>
        5,

    'FWD' =>
        3
];


$realSquad =
    [];


$selectedPlayerIds =
    [];


$teamCounts =
    [];


/*
 * Use the same construction philosophy as the existing
 * GameweekStartingXIRealDataTest.
 *
 * This is deliberately not intended to build an optimal fantasy
 * squad. It creates a legal 15-player squad using genuine current
 * Player Intelligence data so the complete production pipeline can
 * be examined.
 */

foreach (
    $positionRequirements
    as $requiredPosition => $requiredCount
) {

    $selected =
        0;


    foreach (
        $summaries
        as $summary
    ) {

        if (
            $selected
            >=
            $requiredCount
        ) {

            break;
        }


        $playerId =
            (int) (
                $summary[
                    'player_id'
                ]
                ?? 0
            );


        $position =
            strtoupper(
                trim(
                    (string) (
                        $summary[
                            'position'
                        ]
                        ?? ''
                    )
                )
            );


        if (
            $playerId <= 0
            ||
            $position
            !==
            $requiredPosition
            ||
            in_array(
                $playerId,
                $selectedPlayerIds,
                true
            )
        ) {

            continue;
        }


        /*
         * Require the fields used by Gameweek and Captain
         * Intelligence.
         */

        if (
            !is_numeric(
                $summary[
                    'intelligence_score'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'strength_rating'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'next_fixture_rating'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'availability_rating'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'sample_confidence'
                ]
                ?? null
            )
        ) {

            continue;
        }


        $profile =
            $service
                ->getPlayerProfile(
                    $playerId
                );


        if (
            $profile === null
        ) {

            continue;
        }


        $teamId =
            (int) (
                $profile[
                    'team'
                ][
                    'team_id'
                ]
                ?? 0
            );


        if (
            $teamId <= 0
        ) {

            continue;
        }


        /*
         * Standard FPL three-player-per-club limit.
         */

        if (
            (
                $teamCounts[
                    $teamId
                ]
                ?? 0
            )
            >= 3
        ) {

            continue;
        }


        $realSquad[] = [

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $profile[
                    'player'
                ][
                    'fpl_player_id'
                ]
                ?? null,

            'name' =>
                $summary[
                    'name'
                ]
                ?? $profile[
                    'player'
                ][
                    'name'
                ]
                ?? null,

            'position' =>
                $position,

            'team_id' =>
                $teamId,

            'team_name' =>
                $summary[
                    'team_name'
                ]
                ?? $profile[
                    'team'
                ][
                    'name'
                ]
                ?? null,

            'price' =>
                $summary[
                    'price'
                ]
                ?? null,

            'intelligence_score' =>
                $summary[
                    'intelligence_score'
                ]
                ?? null,

            'strength_rating' =>
                $summary[
                    'strength_rating'
                ]
                ?? null,

            'value_rating' =>
                $summary[
                    'value_rating'
                ]
                ?? null,

            'fixture_rating' =>
                $summary[
                    'fixture_rating'
                ]
                ?? null,

            'next_fixture_rating' =>
                $summary[
                    'next_fixture_rating'
                ]
                ?? null,

            'availability_rating' =>
                $summary[
                    'availability_rating'
                ]
                ?? null,

            'sample_confidence' =>
                $summary[
                    'sample_confidence'
                ]
                ?? null,

            'assessment_verdict' =>
                $summary[
                    'assessment_verdict'
                ]
                ?? null,

            'squad_position' =>
                count(
                    $realSquad
                )
                + 1,

            'multiplier' =>
                1,

            'is_captain' =>
                false,

            'is_vice_captain' =>
                false
        ];


        $selectedPlayerIds[] =
            $playerId;


        $teamCounts[
            $teamId
        ] =
            (
                $teamCounts[
                    $teamId
                ]
                ?? 0
            )
            +
            1;


        $selected++;
    }
}


gameweekDecisionRealCheck(
    'Real-data squad contains exactly 15 players',
    count(
        $realSquad
    )
    === 15
);


$positionCounts = [

    'GK' =>
        0,

    'DEF' =>
        0,

    'MID' =>
        0,

    'FWD' =>
        0
];


foreach (
    $realSquad
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? '';


    if (
        isset(
            $positionCounts[
                $position
            ]
        )
    ) {

        $positionCounts[
            $position
        ]++;
    }
}


gameweekDecisionRealCheck(
    'Real-data squad contains two goalkeepers',
    $positionCounts[
        'GK'
    ]
    === 2
);


gameweekDecisionRealCheck(
    'Real-data squad contains five defenders',
    $positionCounts[
        'DEF'
    ]
    === 5
);


gameweekDecisionRealCheck(
    'Real-data squad contains five midfielders',
    $positionCounts[
        'MID'
    ]
    === 5
);


gameweekDecisionRealCheck(
    'Real-data squad contains three forwards',
    $positionCounts[
        'FWD'
    ]
    === 3
);


$clubLimitValid =
    true;


foreach (
    $teamCounts
    as $teamCount
) {

    if (
        $teamCount > 3
    ) {

        $clubLimitValid =
            false;

        break;
    }
}


gameweekDecisionRealCheck(
    'Real-data squad respects three-player club limit',
    $clubLimitValid
);


echo "Squad Players: "
    . count(
        $realSquad
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * SQUAD CONTENT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Real-Data Squad<br>";
echo "============================================<br>";


foreach (
    $realSquad
    as $index => $player
) {

    echo "#"
        . (
            $index + 1
        )
        . " "
        . "<strong>"
        . gameweekDecisionRealEscape(
            $player[
                'name'
            ]
            ?? 'Unknown'
        )
        . "</strong>"
        . " | "
        . gameweekDecisionRealEscape(
            $player[
                'team_name'
            ]
            ?? 'Unknown'
        )
        . " | "
        . gameweekDecisionRealEscape(
            $player[
                'position'
            ]
            ?? ''
        )
        . " | INT "
        . gameweekDecisionRealValue(
            $player[
                'intelligence_score'
            ]
            ?? null
        )
        . " | FIX "
        . gameweekDecisionRealValue(
            $player[
                'next_fixture_rating'
            ]
            ?? null
        )
        . " | CONF "
        . gameweekDecisionRealValue(
            $player[
                'sample_confidence'
            ]
            ?? null
        )
        . "% | AVAIL "
        . gameweekDecisionRealValue(
            $player[
                'availability_rating'
            ]
            ?? null
        )
        . "%<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * COMPLETE GAMEWEEK DECISION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Complete Gameweek Decision<br>";
echo "============================================<br>";


$decisionStartedAt =
    microtime(
        true
    );


$result =
    $service
        ->getGameweekDecision(
            $realSquad,
            0.0
        );


$decisionRuntime =
    microtime(
        true
    )
    -
    $decisionStartedAt;


gameweekDecisionRealCheck(
    'Gameweek Decision Intelligence returns an array',
    is_array(
        $result
    )
);


gameweekDecisionRealCheck(
    'Gameweek Decision Intelligence returns success',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


gameweekDecisionRealCheck(
    'Overall gameweek action is returned',
    !empty(
        $result[
            'overall_action'
        ]
        ?? null
    )
);


$gameweek =
    $result[
        'gameweek'
    ]
    ?? [];


$captaincy =
    $result[
        'captaincy'
    ]
    ?? [];


$transfers =
    $result[
        'transfers'
    ]
    ?? [];


$decision =
    $result[
        'decision'
    ]
    ?? [];


echo "Overall Action: <strong>"
    . gameweekDecisionRealEscape(
        $result[
            'overall_action'
        ]
        ?? 'N/A'
    )
    . "</strong><br>";


echo "Recommended Formation: "
    . gameweekDecisionRealEscape(
        $gameweek[
            'formation'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Starting XI Score: "
    . gameweekDecisionRealValue(
        $gameweek[
            'starting_xi_score'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Bench Score: "
    . gameweekDecisionRealValue(
        $gameweek[
            'bench_score'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Decision Runtime: "
    . number_format(
        $decisionRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * CAPTAINCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Captaincy Recommendation<br>";
echo "============================================<br>";


$captain =
    $captaincy[
        'captain'
    ]
    ?? [];


$viceCaptain =
    $captaincy[
        'vice_captain'
    ]
    ?? [];


gameweekDecisionRealCheck(
    'Captain Intelligence returns success',
    (
        $captaincy[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


gameweekDecisionRealCheck(
    'Recommended captain exists',
    !empty(
        $captain
    )
);


gameweekDecisionRealCheck(
    'Recommended vice-captain exists',
    !empty(
        $viceCaptain
    )
);


gameweekDecisionRealCheck(
    'Recommended captain has numeric Captain Score',
    is_numeric(
        $captain[
            'captain_score'
        ]
        ?? null
    )
);


echo "<strong>Captain: "
    . gameweekDecisionRealEscape(
        $captain[
            'name'
        ]
        ?? 'N/A'
    )
    . "</strong><br>";


echo "Score "
    . gameweekDecisionRealValue(
        $captain[
            'captain_score'
        ]
        ?? null,
        2
    )
    . " | "
    . gameweekDecisionRealEscape(
        $captain[
            'classification'
        ]
        ?? 'N/A'
    )
    . "<br><br>";


echo "<strong>Vice-Captain: "
    . gameweekDecisionRealEscape(
        $viceCaptain[
            'name'
        ]
        ?? 'N/A'
    )
    . "</strong><br>";


echo "Score "
    . gameweekDecisionRealValue(
        $viceCaptain[
            'captain_score'
        ]
        ?? null,
        2
    )
    . " | "
    . gameweekDecisionRealEscape(
        $viceCaptain[
            'classification'
        ]
        ?? 'N/A'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * SQUAD RISK SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Squad Risk Summary<br>";
echo "============================================<br>";


$squadRisks =
    $decision[
        'squad_risks'
    ]
    ?? [];


$riskList =
    $squadRisks[
        'risks'
    ]
    ?? [];


gameweekDecisionRealCheck(
    'Decision returns squad risk analysis',
    is_array(
        $squadRisks
    )
);


gameweekDecisionRealCheck(
    'Squad risk count is numeric',
    is_numeric(
        $squadRisks[
            'count'
        ]
        ?? null
    )
);


gameweekDecisionRealCheck(
    'Detailed squad risk list is returned',
    is_array(
        $riskList
    )
);


gameweekDecisionRealCheck(
    'Reported risk count matches detailed risk list',
    (
        (int) (
            $squadRisks[
                'count'
            ]
            ?? -1
        )
    )
    ===
    count(
        $riskList
    )
);


echo "Total Risks: "
    . (
        $squadRisks[
            'count'
        ]
        ?? 0
    )
    . "<br>";


echo "Critical: "
    . (
        $squadRisks[
            'critical_count'
        ]
        ?? 0
    )
    . "<br>";


echo "High: "
    . (
        $squadRisks[
            'high_count'
        ]
        ?? 0
    )
    . "<br>";


echo "Medium: "
    . (
        $squadRisks[
            'medium_count'
        ]
        ?? 0
    )
    . "<br>";


echo "Low: "
    . (
        $squadRisks[
            'low_count'
        ]
        ?? 0
    )
    . "<br>";


echo "Starting XI Risks: "
    . (
        $squadRisks[
            'starting_xi_risk_count'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * DETAILED SQUAD RISKS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Detailed Squad Risks<br>";
echo "============================================<br>";


if (
    empty(
        $riskList
    )
) {

    echo "No squad risks detected.<br>";

} else {

    foreach (
        $riskList
        as $index => $risk
    ) {

        echo "<strong>#"
            . (
                $index + 1
            )
            . " "
            . gameweekDecisionRealEscape(
                $risk[
                    'name'
                ]
                ?? 'Unknown'
            )
            . "</strong><br>";


        echo "Location: "
            . gameweekDecisionRealEscape(
                $risk[
                    'location'
                ]
                ?? 'Unknown'
            )
            . " | Type: "
            . gameweekDecisionRealEscape(
                $risk[
                    'type'
                ]
                ?? 'Unknown'
            )
            . " | Severity: "
            . gameweekDecisionRealEscape(
                $risk[
                    'severity'
                ]
                ?? 'Unknown'
            )
            . " | Value: "
            . gameweekDecisionRealValue(
                $risk[
                    'value'
                ]
                ?? null
            )
            . "<br>";


        echo gameweekDecisionRealEscape(
            $risk[
                'message'
            ]
            ?? ''
        )
        . "<br><br>";
    }
}


$validRiskSeverities = [
    'critical',
    'high',
    'medium',
    'low'
];


$validRiskTypes = [
    'availability',
    'confidence'
];


$riskIntegrityValid =
    true;


foreach (
    $riskList
    as $risk
) {

    if (
        !in_array(
            $risk[
                'severity'
            ]
            ?? null,
            $validRiskSeverities,
            true
        )
        ||
        !in_array(
            $risk[
                'type'
            ]
            ?? null,
            $validRiskTypes,
            true
        )
        ||
        !is_numeric(
            $risk[
                'value'
            ]
            ?? null
        )
    ) {

        $riskIntegrityValid =
            false;

        break;
    }
}


gameweekDecisionRealCheck(
    'All detailed squad risks contain valid type, severity and numeric value',
    $riskIntegrityValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * DECISION TRANSFER ADVICE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Decision Transfer Advice<br>";
echo "============================================<br>";


$transferAdvice =
    $decision[
        'transfer_advice'
    ]
    ?? [];


gameweekDecisionRealCheck(
    'Decision engine returns transfer advice',
    is_array(
        $transferAdvice
    )
);


gameweekDecisionRealCheck(
    'Transfer advice contains action',
    !empty(
        $transferAdvice[
            'action'
        ]
        ?? null
    )
);


gameweekDecisionRealCheck(
    'Transfer advice contains priority',
    !empty(
        $transferAdvice[
            'priority'
        ]
        ?? null
    )
);


echo "Decision Transfer Action: "
    . gameweekDecisionRealEscape(
        $transferAdvice[
            'action'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Decision Transfer Priority: "
    . gameweekDecisionRealEscape(
        $transferAdvice[
            'priority'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Decision Transfer Score: "
    . gameweekDecisionRealValue(
        $transferAdvice[
            'score'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Decision Transfer Message: "
    . gameweekDecisionRealEscape(
        $transferAdvice[
            'message'
        ]
        ?? 'N/A'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO J
 * RAW TRANSFER INTELLIGENCE STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Raw Transfer Intelligence<br>";
echo "============================================<br>";


$transferAnalysis =
    $transfers[
        'analysis'
    ]
    ?? null;


$transferOptimizer =
    $transfers[
        'recommendations'
    ]
    ?? null;


gameweekDecisionRealCheck(
    'Raw transfer analysis is returned',
    is_array(
        $transferAnalysis
    )
);


gameweekDecisionRealCheck(
    'Raw transfer optimizer result is returned',
    is_array(
        $transferOptimizer
    )
);


$transferAnalysisValid =
    (
        $transferAnalysis[
            'validation'
        ][
            'is_valid'
        ]
        ?? false
    )
    ===
    true;


gameweekDecisionRealCheck(
    'Raw squad transfer analysis is valid',
    $transferAnalysisValid
);


echo "Transfer Analysis Valid: "
    . (
        $transferAnalysisValid
            ? 'YES'
            : 'NO'
    )
    . "<br>";


echo "Optimizer Status: "
    . gameweekDecisionRealEscape(
        $transferOptimizer[
            'status'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Players Considered: "
    . (
        $transferOptimizer[
            'players_considered'
        ]
        ?? 0
    )
    . "<br>";


echo "Bank: £"
    . gameweekDecisionRealValue(
        $transferOptimizer[
            'bank'
        ]
        ?? null,
        1
    )
    . "m<br><br>";


/*
 * ============================================================
 * SCENARIO K
 * RAW SINGLE-TRANSFER RECOMMENDATIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Raw Single-Transfer Recommendations<br>";
echo "============================================<br>";


$transferGroups =
    $transferOptimizer[
        'recommendations'
    ]
    ?? [];


gameweekDecisionRealCheck(
    'Transfer optimizer recommendation groups are returned',
    is_array(
        $transferGroups
    )
);


echo "Outgoing Players Considered: "
    . count(
        $transferGroups
    )
    . "<br><br>";


if (
    empty(
        $transferGroups
    )
) {

    echo "No transfer recommendation groups returned.<br><br>";

} else {

    foreach (
        $transferGroups
        as $groupIndex => $group
    ) {

        $outgoing =
            $group[
                'outgoing'
            ]
            ?? [];


        echo "<strong>Outgoing #"
            . (
                $groupIndex + 1
            )
            . ": "
            . gameweekDecisionRealEscape(
                $outgoing[
                    'name'
                ]
                ?? 'Unknown'
            )
            . "</strong><br>";


        echo "Transfer Priority: "
            . gameweekDecisionRealValue(
                $group[
                    'transfer_priority'
                ]
                ?? null,
                2
            )
            . " | Label: "
            . gameweekDecisionRealEscape(
                $group[
                    'priority_label'
                ]
                ?? 'N/A'
            )
            . "<br>";


        echo "Available Budget: £"
            . gameweekDecisionRealValue(
                $group[
                    'available_budget'
                ]
                ?? null,
                1
            )
            . "m | Legal Candidates: "
            . (
                $group[
                    'legal_candidate_count'
                ]
                ?? 0
            )
            . "<br>";


        $replacements =
            $group[
                'replacements'
            ]
            ?? [];


        echo "Ranked Replacements: "
            . count(
                $replacements
            )
            . "<br>";


        if (
            !empty(
                $replacements
            )
        ) {

            foreach (
                $replacements
                as $replacementIndex => $replacement
            ) {

                $incoming =
                    $replacement[
                        'player'
                    ]
                    ?? [];


                echo "&nbsp;&nbsp;#"
                    . (
                        $replacement[
                            'rank'
                        ]
                        ?? (
                            $replacementIndex + 1
                        )
                    )
                    . " "
                    . "<strong>"
                    . gameweekDecisionRealEscape(
                        $incoming[
                            'name'
                        ]
                        ?? 'Unknown'
                    )
                    . "</strong>"
                    . " | "
                    . gameweekDecisionRealEscape(
                        $incoming[
                            'team_name'
                        ]
                        ?? 'Unknown'
                    )
                    . " | £"
                    . gameweekDecisionRealValue(
                        $incoming[
                            'price'
                        ]
                        ?? null,
                        1
                    )
                    . "m"
                    . " | Decision "
                    . gameweekDecisionRealEscape(
                        $replacement[
                            'decision_type'
                        ]
                        ?? 'N/A'
                    )
                    . " | Score "
                    . gameweekDecisionRealValue(
                        $replacement[
                            'decision_score'
                        ]
                        ?? null,
                        2
                    )
                    . " | Bank After £"
                    . gameweekDecisionRealValue(
                        $replacement[
                            'budget_after'
                        ]
                        ?? null,
                        1
                    )
                    . "m<br>";
            }
        }


        echo "<br>";
    }
}


/*
 * ============================================================
 * SCENARIO L
 * KEY INSIGHTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Key Insights<br>";
echo "============================================<br>";


$keyInsights =
    $decision[
        'key_insights'
    ]
    ?? [];


gameweekDecisionRealCheck(
    'Decision engine returns key insights',
    is_array(
        $keyInsights
    )
);


gameweekDecisionRealCheck(
    'At least one key insight is returned',
    count(
        $keyInsights
    )
    > 0
);


foreach (
    $keyInsights
    as $index => $insight
) {

    echo "#"
        . (
            $index + 1
        )
        . " "
        . gameweekDecisionRealEscape(
            $insight
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * ACTION INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Action Integrity<br>";
echo "============================================<br>";


$validOverallActions = [
    'Hold',
    'Consider Transfer',
    'Make Transfer',
    'Urgent Action'
];


gameweekDecisionRealCheck(
    'Overall action uses a supported Gameweek Decision classification',
    in_array(
        $result[
            'overall_action'
        ]
        ?? null,
        $validOverallActions,
        true
    )
);


gameweekDecisionRealCheck(
    'Decision output action matches service action',
    (
        $decision[
            'overall_action'
        ]
        ?? null
    )
    ===
    (
        $result[
            'overall_action'
        ]
        ?? null
    )
);


gameweekDecisionRealCheck(
    'Decision preserves Gameweek formation',
    (
        $decision[
            'formation'
        ]
        ?? null
    )
    ===
    (
        $gameweek[
            'formation'
        ]
        ?? null
    )
);


gameweekDecisionRealCheck(
    'Decision preserves Captain Intelligence recommendation',
    (
        (int) (
            $decision[
                'captain'
            ][
                'player_id'
            ]
            ?? 0
        )
    )
    ===
    (
        (int) (
            $captain[
                'player_id'
            ]
            ?? 0
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO N
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario N: Performance<br>";
echo "============================================<br>";


$totalRuntime =
    microtime(
        true
    )
    -
    $startedAt;


gameweekDecisionRealCheck(
    'Complete Gameweek Decision diagnostic finishes within 30 seconds',
    $totalRuntime
    <= 30.0
);


echo "Summary Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br>";


echo "Decision Runtime: "
    . number_format(
        $decisionRuntime,
        4
    )
    . " seconds<br>";


echo "Total Runtime: "
    . number_format(
        $totalRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Gameweek Decision Real Data Test Summary<br>";
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