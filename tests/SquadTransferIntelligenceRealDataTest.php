<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Transfer Intelligence Real Data Diagnostic<br>";
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

function squadRealValue(
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


function squadRealPrice(
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
 * BUILD REAL PLAYER CANDIDATES
 * ============================================================
 */

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


    $intelligenceScore =
        $summary[
            'intelligence_score'
        ]
        ?? null;


    if (
        $intelligenceScore === null
        ||
        !is_numeric(
            $intelligenceScore
        )
    ) {

        continue;
    }


    /*
     * Use the repository only to obtain the local team ID.
     *
     * We deliberately avoid loading a complete player profile
     * for every candidate because the summary already contains
     * the intelligence data this diagnostic requires.
     */
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


    $candidatesByPosition[
        $position
    ][] = [

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
            (float) $intelligenceScore,

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
            $summary[
                'sample_confidence'
            ]
            ?? null,

        'verdict' =>
            $summary[
                'assessment_verdict'
            ]
            ?? null
    ];
}


/*
 * Sort each position strongest Intelligence first.
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
 * AUTOMATICALLY BUILD A VARIED VALID SQUAD
 * ============================================================
 *
 * We deliberately select players from different areas of each
 * position ranking rather than simply taking the top 15.
 *
 * That gives SquadTransferIntelligence something meaningful
 * to analyse.
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


$squad =
    [];


$teamCounts =
    [];


/*
 * Preferred percentile points for creating a mixture of
 * strong, middle and weaker real players.
 */
$selectionPercentiles = [

    0.00,
    0.20,
    0.40,
    0.60,
    0.80,
    1.00
];


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


    /*
     * First try deliberately spaced players.
     */
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
            as $existingPlayer
        ) {

            if (
                (
                    (int) (
                        $existingPlayer[
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


    /*
     * If percentile selection was blocked by club limits,
     * fill remaining slots from the complete position pool.
     */
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
                as $existingPlayer
            ) {

                if (
                    (
                        (int) (
                            $existingPlayer[
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
            . " SELECTION ❌<br>";

        exit;
    }
}


/*
 * ============================================================
 * DISPLAY GENERATED SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Generated Real-Data Squad<br>";
echo "============================================<br><br>";


foreach (
    $squad
    as $player
) {

    echo htmlspecialchars(
        (string) (
            $player[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo " | ";


    echo htmlspecialchars(
        (string) (
            $player[
                'team_name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    );


    echo " | "
        . htmlspecialchars(
            (string) (
                $player[
                    'position'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );


    echo " | "
        . squadRealPrice(
            $player[
                'price'
            ]
            ?? null
        );


    echo " | INT "
        . squadRealValue(
            $player[
                'intelligence_score'
            ]
            ?? null
        );


    echo "<br>";
}


/*
 * ============================================================
 * ANALYSE REAL SQUAD
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Squad Analysis<br>";
echo "============================================<br>";


$startedAt =
    microtime(true);


$analysis =
    $squadIntelligence
        ->analyzeSquad(
            $squad,
            1.5
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


$validation =
    $analysis[
        'validation'
    ]
    ?? [];


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


echo "Player Count: "
    . (
        $validation[
            'player_count'
        ]
        ?? 0
    )
    . "<br>";


echo "GK: "
    . (
        $validation[
            'position_counts'
        ]['GK']
        ?? 0
    )
    . "<br>";


echo "DEF: "
    . (
        $validation[
            'position_counts'
        ]['DEF']
        ?? 0
    )
    . "<br>";


echo "MID: "
    . (
        $validation[
            'position_counts'
        ]['MID']
        ?? 0
    )
    . "<br>";


echo "FWD: "
    . (
        $validation[
            'position_counts'
        ]['FWD']
        ?? 0
    )
    . "<br>";


if (
    !empty(
        $validation[
            'issues'
        ]
        ?? []
    )
) {

    echo "<br><strong>Validation Issues</strong><br>";


    foreach (
        $validation[
            'issues'
        ]
        as $issue
    ) {

        echo "- "
            . htmlspecialchars(
                (string) $issue,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }
}


/*
 * ============================================================
 * SQUAD SUMMARY
 * ============================================================
 */

$summary =
    $analysis[
        'summary'
    ]
    ?? [];


echo "<br>============================================<br>";
echo "Squad Intelligence Summary<br>";
echo "============================================<br>";


echo "Average Intelligence: "
    . squadRealValue(
        $summary[
            'average_intelligence'
        ]
        ?? null
    )
    . "<br>";


echo "Weakest Position: "
    . htmlspecialchars(
        (string) (
            $summary[
                'weakest_position'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "<br><strong>Position Intelligence Averages</strong><br>";


foreach (
    $summary[
        'position_averages'
    ]
    ?? []
    as $position => $average
) {

    echo htmlspecialchars(
        (string) $position,
        ENT_QUOTES,
        'UTF-8'
    )
    . ": "
    . squadRealValue(
        $average
    )
    . "<br>";
}


/*
 * ============================================================
 * TRANSFER PRIORITY RANKING
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Transfer Priority Ranking<br>";
echo "============================================<br>";


$ranking =
    $analysis[
        'ranking'
    ]
    ?? [];


foreach (
    $ranking
    as $player
) {

    echo "<br><strong>#"
        . (
            $player[
                'squad_rank'
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
    . squadRealPrice(
        $player[
            'price'
        ]
        ?? null
    )
    . "<br>";


    echo "Transfer Priority: "
        . squadRealValue(
            $player[
                'transfer_priority'
            ]
            ?? null
        )
        . " | "
        . htmlspecialchars(
            (string) (
                $player[
                    'priority_label'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "INT: "
        . squadRealValue(
            $player[
                'intelligence_score'
            ]
            ?? null
        );


    echo " | Value: "
        . squadRealValue(
            $player[
                'value_rating'
            ]
            ?? null
        );


    echo " | Fixtures: "
        . squadRealValue(
            $player[
                'fixture_rating'
            ]
            ?? null
        );


    echo " | Availability: "
        . squadRealValue(
            $player[
                'availability_rating'
            ]
            ?? null
        );


    echo " | Confidence: "
        . squadRealValue(
            $player[
                'sample_confidence'
            ]
            ?? null
        );


    echo "<br>";


    echo "Reasons: ";


    $reasons =
        $player[
            'priority_reasons'
        ]
        ?? [];


    if (empty($reasons)) {

        echo "None";

    } else {

        echo htmlspecialchars(
            implode(
                '; ',
                $reasons
            ),
            ENT_QUOTES,
            'UTF-8'
        );
    }


    echo "<br>";
}


/*
 * ============================================================
 * TOP FIVE TRANSFER PRIORITIES
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Top Five Transfer Priorities<br>";
echo "============================================<br>";


$weakestPlayers =
    $analysis[
        'weakest_players'
    ]
    ?? [];


foreach (
    $weakestPlayers
    as $index => $player
) {

    echo '#'
        . (
            $index + 1
        )
        . ' ';


    echo htmlspecialchars(
        (string) (
            $player[
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
                $player[
                    'position'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );


    echo " | Priority "
        . squadRealValue(
            $player[
                'transfer_priority'
            ]
            ?? null
        );


    echo " | "
        . htmlspecialchars(
            (string) (
                $player[
                    'priority_label'
                ]
                ?? ''
            ),
            ENT_QUOTES,
            'UTF-8'
        );


    echo "<br>";
}


/*
 * ============================================================
 * BASIC DIAGNOSTIC CHECKS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Diagnostic Checks<br>";
echo "============================================<br>";


$checksPassed =
    true;


if (
    (
        $validation[
            'is_valid'
        ]
        ?? false
    )
    !== true
) {

    echo "FAIL: Generated squad is not valid ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Generated squad is valid<br>";
}


if (
    count(
        $ranking
    )
    !== 15
) {

    echo "FAIL: Ranking does not contain 15 players ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Ranking contains all 15 players<br>";
}


if (
    count(
        $weakestPlayers
    )
    !== 5
) {

    echo "FAIL: Top priority list does not contain five players ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Top priority list contains five players<br>";
}


if (
    empty(
        $summary[
            'weakest_position'
        ]
        ?? null
    )
) {

    echo "FAIL: Weakest position was not calculated ❌<br>";

    $checksPassed =
        false;

} else {

    echo "PASS: Weakest position was calculated<br>";
}


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Real Data Squad Transfer Intelligence Diagnostic Complete<br>";
echo "============================================<br>";


if ($checksPassed) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}