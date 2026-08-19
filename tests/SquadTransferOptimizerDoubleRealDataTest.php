<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Transfer Optimizer Double Real Data Diagnostic<br>";
echo "============================================<br><br>";


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $service =
        new PlayerIntelligenceService(
            $db
        );


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $squadIntelligence =
        new SquadTransferIntelligence();


    $squadOptimizer =
        new SquadTransferOptimizer();


    $summaries =
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
 * DISPLAY HELPERS
 * ============================================================
 */

function doubleRealValue(
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


function doubleRealSigned(
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


function doubleRealPrice(
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


    return '£'
        . number_format(
            (float) $value,
            1
        )
        . 'm';
}


/*
 * ============================================================
 * BUILD REAL PLAYER POOL
 * ============================================================
 */

$allPlayers =
    [];


$candidatesByPosition = [

    'GK' =>
        [],

    'DEF' =>
        [],

    'MID' =>
        [],

    'FWD' =>
        []
];


foreach (
    $summaries
    as $summary
) {

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
        !isset(
            $candidatesByPosition[
                $position
            ]
        )
    ) {

        continue;
    }


    $localPlayer =
        $playerRepository
            ->getById(
                $playerId
            );


    if ($localPlayer === null) {
        continue;
    }


    $teamId =
        (int) (
            $localPlayer[
                'team_id'
            ]
            ?? 0
        );


    if ($teamId <= 0) {
        continue;
    }


    $intelligence =
        $summary[
            'intelligence_score'
        ]
        ?? null;


    if (
        $intelligence === null
        ||
        !is_numeric(
            $intelligence
        )
    ) {

        continue;
    }


    /*
     * TransferDecision expects sample confidence
     * in the 0-1 range.
     */
    $confidence =
        $summary[
            'sample_confidence'
        ]
        ?? null;


    if (
        $confidence !== null
        &&
        is_numeric(
            $confidence
        )
    ) {

        $confidence =
            (float) $confidence;


        if ($confidence > 1) {

            $confidence /=
                100;
        }


        $confidence =
            max(
                0,
                min(
                    1,
                    $confidence
                )
            );
    }


    $player = [

        'player_id' =>
            $playerId,

        'fpl_player_id' =>
            isset(
                $localPlayer[
                    'fpl_player_id'
                ]
            )
                ? (int) $localPlayer[
                    'fpl_player_id'
                ]
                : null,

        'name' =>
            $summary[
                'name'
            ]
            ?? (
                $localPlayer[
                    'web_name'
                ]
                ?? null
            ),

        'team_id' =>
            $teamId,

        'team_name' =>
            $summary[
                'team_name'
            ]
            ?? null,

        'position' =>
            $position,

        'price' =>
            $summary[
                'price'
            ]
            ?? null,

        'intelligence_score' =>
            (float) $intelligence,

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

        'availability_rating' =>
            $summary[
                'availability_rating'
            ]
            ?? null,

        'sample_confidence' =>
            $confidence,

        'verdict' =>
            $summary[
                'assessment_verdict'
            ]
            ?? null
    ];


    $allPlayers[] =
        $player;


    $candidatesByPosition[
        $position
    ][] =
        $player;
}


/*
 * ============================================================
 * SORT POSITION POOLS
 * ============================================================
 */

foreach (
    $candidatesByPosition
    as &$positionCandidates
) {

    usort(
        $positionCandidates,
        static function (
            array $a,
            array $b
        ): int {

            return (
                (float) (
                    $b[
                        'intelligence_score'
                    ]
                    ?? 0
                )
            )
            <=>
            (
                (float) (
                    $a[
                        'intelligence_score'
                    ]
                    ?? 0
                )
            );
        }
    );
}


unset(
    $positionCandidates
);


/*
 * ============================================================
 * BUILD VARIED VALID REAL-DATA SQUAD
 * ============================================================
 */

$requiredCounts = [

    'GK' =>
        2,

    'DEF' =>
        5,

    'MID' =>
        5,

    'FWD' =>
        3
];


$selectionPercentiles = [

    0.00,
    0.20,
    0.40,
    0.60,
    0.80,
    1.00
];


$squad =
    [];


$teamCounts =
    [];


foreach (
    $requiredCounts
    as $position => $requiredCount
) {

    $pool =
        $candidatesByPosition[
            $position
        ]
        ?? [];


    if (
        count(
            $pool
        )
        <
        $requiredCount
    ) {

        echo "NOT ENOUGH "
            . htmlspecialchars(
                $position,
                ENT_QUOTES,
                'UTF-8'
            )
            . " CANDIDATES ❌<br>";

        exit;
    }


    $selectedForPosition =
        [];


    foreach (
        $selectionPercentiles
        as $percentile
    ) {

        if (
            count(
                $selectedForPosition
            )
            >=
            $requiredCount
        ) {

            break;
        }


        $index =
            (int) round(
                (
                    count(
                        $pool
                    )
                    -
                    1
                )
                *
                $percentile
            );


        $candidate =
            $pool[
                $index
            ]
            ?? null;


        if ($candidate === null) {
            continue;
        }


        $playerId =
            (int) (
                $candidate[
                    'player_id'
                ]
                ?? 0
            );


        $teamId =
            (int) (
                $candidate[
                    'team_id'
                ]
                ?? 0
            );


        if (
            $playerId <= 0
            ||
            $teamId <= 0
        ) {

            continue;
        }


        $alreadySelected =
            false;


        foreach (
            $squad
            as $existing
        ) {

            if (
                (
                    (int) (
                        $existing[
                            'player_id'
                        ]
                        ?? 0
                    )
                )
                ===
                $playerId
            ) {

                $alreadySelected =
                    true;

                break;
            }
        }


        if ($alreadySelected) {
            continue;
        }


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


        $selectedForPosition[] =
            $candidate;


        $squad[] =
            $candidate;


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
    }


    if (
        count(
            $selectedForPosition
        )
        <
        $requiredCount
    ) {

        foreach (
            $pool
            as $candidate
        ) {

            if (
                count(
                    $selectedForPosition
                )
                >=
                $requiredCount
            ) {

                break;
            }


            $playerId =
                (int) (
                    $candidate[
                        'player_id'
                    ]
                    ?? 0
                );


            $teamId =
                (int) (
                    $candidate[
                        'team_id'
                    ]
                    ?? 0
                );


            if (
                $playerId <= 0
                ||
                $teamId <= 0
            ) {

                continue;
            }


            $alreadySelected =
                false;


            foreach (
                $squad
                as $existing
            ) {

                if (
                    (
                        (int) (
                            $existing[
                                'player_id'
                            ]
                            ?? 0
                        )
                    )
                    ===
                    $playerId
                ) {

                    $alreadySelected =
                        true;

                    break;
                }
            }


            if ($alreadySelected) {
                continue;
            }


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


            $selectedForPosition[] =
                $candidate;


            $squad[] =
                $candidate;


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
        }
    }


    if (
        count(
            $selectedForPosition
        )
        !==
        $requiredCount
    ) {

        echo "UNABLE TO BUILD VALID "
            . htmlspecialchars(
                $position,
                ENT_QUOTES,
                'UTF-8'
            )
            . " SQUAD ❌<br>";

        exit;
    }
}


/*
 * ============================================================
 * ANALYSE SQUAD
 * ============================================================
 */

$analysis =
    $squadIntelligence
        ->analyzeSquad(
            $squad,
            1.5
        );


$validation =
    $analysis[
        'validation'
    ]
    ?? [];


echo "============================================<br>";
echo "Squad Context<br>";
echo "============================================<br>";


echo "Valid Squad: "
    . (
        (
            $validation[
                'is_valid'
            ]
            ?? false
        )
            ? 'Yes'
            : 'No'
    )
    . "<br>";


echo "Bank: "
    . doubleRealPrice(
        $analysis[
            'bank'
        ]
        ?? null
    )
    . "<br>";


echo "Average Intelligence: "
    . doubleRealValue(
        $analysis[
            'summary'
        ]['average_intelligence']
        ?? null
    )
    . "<br>";


echo "Weakest Position: "
    . htmlspecialchars(
        (string) (
            $analysis[
                'summary'
            ]['weakest_position']
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


echo "<strong>Top Transfer Priorities</strong><br>";


foreach (
    array_slice(
        $analysis[
            'ranking'
        ]
        ?? [],
        0,
        5
    )
    as $priorityPlayer
) {

    echo '#'
        . (
            $priorityPlayer[
                'squad_rank'
            ]
            ?? '?'
        )
        . ' ';


    echo htmlspecialchars(
        (string) (
            $priorityPlayer[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo " | "
        . htmlspecialchars(
            (string) (
                $priorityPlayer[
                    'position'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );


    echo " | Priority "
        . doubleRealValue(
            $priorityPlayer[
                'transfer_priority'
            ]
            ?? null
        );


    echo "<br>";
}


/*
 * ============================================================
 * RUN DOUBLE OPTIMIZER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Double Transfer Recommendations<br>";
echo "============================================<br>";


$startedAt =
    microtime(true);


$result =
    $squadOptimizer
        ->findBestDoubleTransfers(
            $analysis,
            $allPlayers,
            5,
            10
        );


$runtime =
    microtime(true)
    -
    $startedAt;


echo "Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br>";


echo "Status: "
    . htmlspecialchars(
        (string) (
            $result[
                'status'
            ]
            ?? 'unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Priority Players Considered: "
    . (
        $result[
            'priority_players_considered'
        ]
        ?? 0
    )
    . "<br>";


echo "Outgoing Pairs Considered: "
    . (
        $result[
            'outgoing_pairs_considered'
        ]
        ?? 0
    )
    . "<br>";


echo "Total Legal Combinations Found: "
    . (
        $result[
            'total_found'
        ]
        ?? 0
    )
    . "<br>";


echo "Recommendations Returned: "
    . (
        $result[
            'count'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * DISPLAY COMBINATIONS
 * ============================================================
 */

$combinations =
    $result[
        'combinations'
    ]
    ?? [];


foreach (
    $combinations
    as $combination
) {

    $rank =
        $combination[
            'squad_optimizer'
        ]['rank']
        ?? '?';


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


    $currentA =
        $transferA[
            'current_player'
        ]
        ?? [];


    $replacementA =
        $transferA[
            'replacement'
        ]
        ?? [];


    $currentB =
        $transferB[
            'current_player'
        ]
        ?? [];


    $replacementB =
        $transferB[
            'replacement'
        ]
        ?? [];


    echo "============================================<br>";

    echo "<strong>#"
        . $rank
        . " "
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
        . "</strong><br>";

    echo "============================================<br>";


    echo "<strong>Transfer A</strong><br>";


    echo htmlspecialchars(
        (string) (
            $currentA[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo " → ";


    echo htmlspecialchars(
        (string) (
            $replacementA[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo "<br>";


    echo "Outgoing: "
        . doubleRealPrice(
            $currentA[
                'price'
            ]
            ?? null
        );


    echo " | Incoming: "
        . doubleRealPrice(
            $replacementA[
                'price'
            ]
            ?? null
        );


    echo " | Decision: "
        . htmlspecialchars(
            (string) (
                $transferA[
                    'decision_type'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        );


    echo " | Score "
        . doubleRealValue(
            $transferA[
                'decision_score'
            ]
            ?? null,
            2
        )
        . "<br>";


    echo "<strong>Transfer B</strong><br>";


    echo htmlspecialchars(
        (string) (
            $currentB[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo " → ";


    echo htmlspecialchars(
        (string) (
            $replacementB[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo "<br>";


    echo "Outgoing: "
        . doubleRealPrice(
            $currentB[
                'price'
            ]
            ?? null
        );


    echo " | Incoming: "
        . doubleRealPrice(
            $replacementB[
                'price'
            ]
            ?? null
        );


    echo " | Decision: "
        . htmlspecialchars(
            (string) (
                $transferB[
                    'decision_type'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        );


    echo " | Score "
        . doubleRealValue(
            $transferB[
                'decision_score'
            ]
            ?? null,
            2
        )
        . "<br>";


    $movements =
        $combination[
            'combined_movements'
        ]
        ?? [];


    echo "<strong>Combined Movements</strong><br>";


    echo "INT: "
        . doubleRealSigned(
            $movements[
                'intelligence'
            ]
            ?? null
        );


    echo " | Strength: "
        . doubleRealSigned(
            $movements[
                'strength'
            ]
            ?? null
        );


    echo " | Value: "
        . doubleRealSigned(
            $movements[
                'value'
            ]
            ?? null
        );


    echo " | Fixtures: "
        . doubleRealSigned(
            $movements[
                'fixtures'
            ]
            ?? null
        );


    echo " | Confidence: "
        . doubleRealSigned(
            $movements[
                'sample_confidence'
            ]
            ?? null
        )
        . "pp<br>";


    echo "Combination Score: "
        . doubleRealValue(
            $combination[
                'combination_score'
            ]
            ?? null,
            2
        )
        . "<br>";
        
    echo "Outgoing Priority Total: "
    . doubleRealValue(
        $combination[
            'squad_optimizer'
        ]['outgoing_priority_total']
        ?? null,
        1
    )
    . "<br>";


    echo "Squad Priority Bonus: +"
        . doubleRealValue(
            $combination[
                'squad_optimizer'
            ]['squad_priority_bonus']
            ?? null,
            2
        )
        . "<br>";


    echo "Squad Score: "
        . doubleRealValue(
            $combination[
                'squad_optimizer'
            ]['squad_score']
            ?? null,
            2
        )
        . "<br>";


    echo "Budget After: "
        . doubleRealPrice(
            $combination[
                'optimizer'
            ]['budget_after']
            ?? null
        )
        . "<br>";


    echo "Outgoing Priorities: "
        . doubleRealValue(
            $combination[
                'squad_optimizer'
            ]['outgoing_priority_a']
            ?? null
        );


    echo " + "
        . doubleRealValue(
            $combination[
                'squad_optimizer'
            ]['outgoing_priority_b']
            ?? null
        )
        . "<br>";


    echo "Summary: "
        . htmlspecialchars(
            (string) (
                $combination[
                    'squad_optimizer'
                ]['summary']
                ?? (
                    $combination[
                        'summary'
                    ]
                    ?? ''
                )
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br><br>";
}


/*
 * ============================================================
 * DIAGNOSTIC CHECKS
 * ============================================================
 */

echo "============================================<br>";
echo "Diagnostic Checks<br>";
echo "============================================<br>";


$checksPassed =
    true;


if (
    (
        $result[
            'status'
        ]
        ?? null
    )
    !==
    'success'
) {

    echo "FAIL: Double optimizer did not return success ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Double optimizer returned success<br>";
}


if (
    (
        $result[
            'priority_players_considered'
        ]
        ?? 0
    )
    !== 5
) {

    echo "FAIL: Expected five priority players ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Five priority players considered<br>";
}


if (
    (
        $result[
            'outgoing_pairs_considered'
        ]
        ?? 0
    )
    !== 10
) {

    echo "FAIL: Expected ten outgoing pairs ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Ten outgoing pairs considered<br>";
}


if (
    count(
        $combinations
    )
    > 10
) {

    echo "FAIL: Result limit exceeded ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Result limit respected<br>";
}


$allAffordable =
    true;


$allUniqueIncoming =
    true;


foreach (
    $combinations
    as $combination
) {

    if (
        (
            (float) (
                $combination[
                    'optimizer'
                ]['budget_after']
                ?? -999
            )
        )
        <
        -0.001
    ) {

        $allAffordable =
            false;
    }


    $incomingIdA =
        (int) (
            $combination[
                'transfer_a'
            ]['replacement']['player_id']
            ?? 0
        );


    $incomingIdB =
        (int) (
            $combination[
                'transfer_b'
            ]['replacement']['player_id']
            ?? 0
        );


    if (
        $incomingIdA <= 0
        ||
        $incomingIdB <= 0
        ||
        $incomingIdA === $incomingIdB
    ) {

        $allUniqueIncoming =
            false;
    }
}


if ($allAffordable) {

    echo "PASS: All returned combinations are affordable<br>";

} else {

    echo "FAIL: Unaffordable combination returned ❌<br>";

    $checksPassed =
        false;
}


if ($allUniqueIncoming) {

    echo "PASS: Incoming players are unique within each combination<br>";

} else {

    echo "FAIL: Duplicate incoming player found ❌<br>";

    $checksPassed =
        false;
}


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Real Data Double Transfer Diagnostic Complete<br>";
echo "============================================<br>";


if ($checksPassed) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}