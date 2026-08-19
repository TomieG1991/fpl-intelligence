<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Transfer Optimizer Real Data Diagnostic<br>";
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

function squadOptimizerRealValue(
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


function squadOptimizerRealPrice(
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


function squadOptimizerRealSigned(
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
     * TransferDecision expects confidence in 0-1 form.
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


        if (
            $confidence > 1
        ) {

            $confidence /=
                100;
        }
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
 * BUILD SAME VARIED REAL-DATA SQUAD
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
 * SQUAD ANALYSIS
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


echo "Bank: £"
    . number_format(
        (float) (
            $analysis[
                'bank'
            ]
            ?? 0
        ),
        1
    )
    . "m<br>";


echo "Average Intelligence: "
    . squadOptimizerRealValue(
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


/*
 * ============================================================
 * RUN SINGLE-TRANSFER OPTIMIZER
 * ============================================================
 */

echo "============================================<br>";
echo "Single Transfer Recommendations<br>";
echo "============================================<br>";


$startedAt =
    microtime(true);


$result =
    $squadOptimizer
        ->findBestSingleTransfers(
            $analysis,
            $allPlayers,
            5,
            5
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
            'players_considered'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * DISPLAY RECOMMENDATIONS
 * ============================================================
 */

$recommendations =
    $result[
        'recommendations'
    ]
    ?? [];


foreach (
    $recommendations
    as $index => $recommendation
) {

    $outgoing =
        $recommendation[
            'outgoing'
        ]
        ?? [];


    echo "<br>============================================<br>";


    echo "Priority #"
        . (
            $index + 1
        )
        . ": "
        . htmlspecialchars(
            (string) (
                $outgoing[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "============================================<br>";


    echo "Position: "
        . htmlspecialchars(
            (string) (
                $outgoing[
                    'position'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Current Price: "
        . squadOptimizerRealPrice(
            $outgoing[
                'price'
            ]
            ?? null
        )
        . "<br>";


    echo "Transfer Priority: "
        . squadOptimizerRealValue(
            $recommendation[
                'transfer_priority'
            ]
            ?? null
        )
        . " | "
        . htmlspecialchars(
            (string) (
                $recommendation[
                    'priority_label'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Available Budget: "
        . squadOptimizerRealPrice(
            $recommendation[
                'available_budget'
            ]
            ?? null
        )
        . "<br>";


    echo "Legal Candidates: "
        . (
            $recommendation[
                'legal_candidate_count'
            ]
            ?? 0
        )
        . "<br>";


    echo "Recommendations Returned: "
        . (
            $recommendation[
                'replacement_count'
            ]
            ?? 0
        )
        . "<br><br>";


    $replacements =
        $recommendation[
            'replacements'
        ]
        ?? [];


    if (empty($replacements)) {

        echo "No legal replacements found.<br>";

        continue;
    }


    foreach (
        $replacements
        as $replacement
    ) {

        $player =
            $replacement[
                'player'
            ]
            ?? [];


        $decision =
            $replacement[
                'decision'
            ]
            ?? [];


        $movements =
            $decision[
                'movements'
            ]
            ?? [];


        echo "<strong>#"
            . (
                $replacement[
                    'rank'
                ]
                ?? '?'
            )
            . " "
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
            . "</strong><br>";


        echo htmlspecialchars(
            (string) (
                $player[
                    'team_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " | "
        . htmlspecialchars(
            (string) (
                $player[
                    'position'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " | "
        . squadOptimizerRealPrice(
            $player[
                'price'
            ]
            ?? null
        )
        . "<br>";


        echo "Decision: "
            . htmlspecialchars(
                (string) (
                    $replacement[
                        'decision_type'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Score "
            . squadOptimizerRealValue(
                $replacement[
                    'decision_score'
                ]
                ?? null,
                2
            )
            . "<br>";


        echo "INT: "
            . squadOptimizerRealValue(
                $player[
                    'intelligence_score'
                ]
                ?? null
            );


        echo " | INT Move: "
            . squadOptimizerRealSigned(
                $movements[
                    'intelligence'
                ]
                ?? null
            );


        echo " | STR Move: "
            . squadOptimizerRealSigned(
                $movements[
                    'strength'
                ]
                ?? null
            );


        echo " | VAL Move: "
            . squadOptimizerRealSigned(
                $movements[
                    'value'
                ]
                ?? null
            );


        echo " | FIX Move: "
            . squadOptimizerRealSigned(
                $movements[
                    'fixtures'
                ]
                ?? null
            )
            . "<br>";


        echo "Budget After: "
            . squadOptimizerRealPrice(
                $replacement[
                    'budget_after'
                ]
                ?? null
            )
            . "<br>";


        echo "Summary: "
            . htmlspecialchars(
                (string) (
                    $decision[
                        'summary'
                    ]
                    ?? ''
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br><br>";
    }
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

    echo "FAIL: Squad optimizer did not return success ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Squad optimizer returned success<br>";
}


if (
    (
        $result[
            'players_considered'
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


$illegalReplacementFound =
    false;


$squadPlayerIds =
    [];


foreach (
    $squad
    as $squadPlayer
) {

    $squadPlayerIds[] =
        (int) (
            $squadPlayer[
                'player_id'
            ]
            ?? 0
        );
}


foreach (
    $recommendations
    as $recommendation
) {

    $outgoing =
        $recommendation[
            'outgoing'
        ]
        ?? [];


    $outgoingPosition =
        $outgoing[
            'position'
        ]
        ?? null;


    $availableBudget =
        (float) (
            $recommendation[
                'available_budget'
            ]
            ?? 0
        );


    foreach (
        $recommendation[
            'replacements'
        ]
        ?? []
        as $replacement
    ) {

        $player =
            $replacement[
                'player'
            ]
            ?? [];


        if (
            (
                $player[
                    'position'
                ]
                ?? null
            )
            !==
            $outgoingPosition
        ) {

            $illegalReplacementFound =
                true;
        }


        if (
            (
                (float) (
                    $player[
                        'price'
                    ]
                    ?? PHP_FLOAT_MAX
                )
            )
            >
            $availableBudget
        ) {

            $illegalReplacementFound =
                true;
        }


        if (
            in_array(
                (
                    (int) (
                        $player[
                            'player_id'
                        ]
                        ?? 0
                    )
                ),
                $squadPlayerIds,
                true
            )
        ) {

            $illegalReplacementFound =
                true;
        }
    }
}


if ($illegalReplacementFound) {

    echo "FAIL: Illegal replacement found ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Returned replacements respect squad legality<br>";
}


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Real Data Squad Transfer Optimizer Diagnostic Complete<br>";
echo "============================================<br>";


if ($checksPassed) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}