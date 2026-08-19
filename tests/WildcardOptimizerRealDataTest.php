<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Wildcard Optimizer Real Data Diagnostic<br>";
echo "============================================<br><br>";


/*
 * ============================================================
 * DISPLAY HELPERS
 * ============================================================
 */

function wildcardRealNumber(
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


function wildcardRealPrice(
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

function wildcardBenchReliabilityPenaltyPercent(
    mixed $confidence
): float {

    if (
        $confidence === null
        ||
        !is_numeric(
            $confidence
        )
    ) {

        return 10.0;
    }


    $confidence =
        (float) $confidence;


    if (
        $confidence >= 75.0
    ) {

        return 0.0;
    }


    if (
        $confidence >= 50.0
    ) {

        return 2.0;
    }


    if (
        $confidence >= 25.0
    ) {

        return 5.0;
    }


    if (
        $confidence >= 10.0
    ) {

        return 10.0;
    }


    return 18.0;
}


function wildcardRealSigned(
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
        $value > 0
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


    $optimizer =
        new WildcardOptimizer();

} catch (Throwable $exception) {

    echo "RESULT: SETUP FAILED ❌<br>";
    echo "Message: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        );

    exit;
}


/*
 * ============================================================
 * LOAD REAL PLAYER INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Real Player Pool<br>";
echo "============================================<br>";


$poolStartedAt =
    microtime(true);


try {

    $summaries =
        $service
            ->getAllPlayerSummaries();

} catch (Throwable $exception) {

    echo "RESULT: PLAYER POOL FAILED ❌<br>";
    echo "Message: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        );

    exit;
}


$poolRuntime =
    microtime(true)
    -
    $poolStartedAt;


echo "All Player Summaries: "
    . count(
        $summaries
    )
    . "<br>";


echo "Player Pool Runtime: "
    . number_format(
        $poolRuntime,
        4
    )
    . " seconds<br>";


/*
 * ============================================================
 * BUILD OPTIMIZER CANDIDATE POOL
 * ============================================================
 */

$candidates =
    [];


$positionCandidateCounts = [

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


    $teamId =
        (int) (
            $summary[
                'team_id'
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


    $price =
        $summary[
            'price'
        ]
        ?? null;


    $intelligence =
        $summary[
            'intelligence_score'
        ]
        ?? null;


    /*
     * WildcardOptimizer performs its own validation too,
     * but filtering here lets the diagnostic report the
     * usable real-data player pool clearly.
     */

    if (
        $playerId <= 0
        ||
        $teamId <= 0
        ||
        !in_array(
            $position,
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ],
            true
        )
        ||
        !is_numeric(
            $price
        )
        ||
        (float) $price <= 0
        ||
        !is_numeric(
            $intelligence
        )
    ) {

        continue;
    }


    $candidate = [

        'player_id' =>
            $playerId,

        'name' =>
            $summary[
                'name'
            ]
            ?? (
                'Player '
                . $playerId
            ),

        'team_id' =>
            $teamId,

        'team_name' =>
            $summary[
                'team_name'
            ]
            ?? (
                $summary[
                    'team_short_name'
                ]
                ?? (
                    'Team '
                    . $teamId
                )
            ),

        'position' =>
            $position,

        'price' =>
            (float) $price,

        'intelligence_score' =>
            (float) $intelligence,

        'strength_rating' =>
            is_numeric(
                $summary[
                    'strength_rating'
                ]
                ?? null
            )
                ? (float) $summary[
                    'strength_rating'
                ]
                : null,

        'value_rating' =>
            is_numeric(
                $summary[
                    'value_rating'
                ]
                ?? null
            )
                ? (float) $summary[
                    'value_rating'
                ]
                : null,

        'fixture_rating' =>
            is_numeric(
                $summary[
                    'fixture_rating'
                ]
                ?? null
            )
                ? (float) $summary[
                    'fixture_rating'
                ]
                : null,

        'availability_rating' =>
            is_numeric(
                $summary[
                    'availability_rating'
                ]
                ?? null
            )
                ? (float) $summary[
                    'availability_rating'
                ]
                : null,

        'sample_confidence' =>
            is_numeric(
                $summary[
                    'sample_confidence'
                ]
                ?? null
            )
                ? (float) $summary[
                    'sample_confidence'
                ]
                : null
    ];


    $candidates[] =
        $candidate;


    $positionCandidateCounts[
        $position
    ]++;
}


echo "Valid Wildcard Candidates: "
    . count(
        $candidates
    )
    . "<br><br>";


echo "<strong>Candidate Counts by Position</strong><br>";


foreach (
    $positionCandidateCounts
    as $position => $count
) {

    echo $position
        . ": "
        . $count
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * RUN WILDCARD OPTIMIZER
 * ============================================================
 */

echo "============================================<br>";
echo "Wildcard Optimization<br>";
echo "============================================<br>";


$startedAt =
    microtime(true);


try {

    $result =
        $optimizer
            ->optimize(
                $candidates,
                100.0
            );

} catch (Throwable $exception) {

    echo "RESULT: OPTIMIZER FAILED ❌<br>";
    echo "Message: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        );

    exit;
}


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


echo "Message: "
    . htmlspecialchars(
        (string) (
            $result[
                'message'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Budget: "
    . wildcardRealPrice(
        $result[
            'budget'
        ]
        ?? null
    )
    . "<br>";


echo "Squad Cost: "
    . wildcardRealPrice(
        $result[
            'cost'
        ]
        ?? null
    )
    . "<br>";


echo "Bank: "
    . wildcardRealPrice(
        $result[
            'bank'
        ]
        ?? null
    )
    . "<br>";


echo "Wildcard Score: "
    . wildcardRealNumber(
        $result[
            'wildcard_score'
        ]
        ?? null,
        2
    )
    . "<br>";
    
echo "Structure Score: "
    . wildcardRealNumber(
        $result[
            'structure_score'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Starting XI Score: "
    . wildcardRealNumber(
        $result[
            'starting_xi_score'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Bench Score: "
    . wildcardRealNumber(
        $result[
            'bench_score'
        ]
        ?? null,
        2
    )
    . "<br>";
    
    
echo "Raw Bench Score: "
    . wildcardRealNumber(
        $result[
            'raw_bench_score'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Bench Reliability Penalty: "
    . wildcardRealNumber(
        $result[
            'bench_reliability_penalty'
        ]
        ?? null,
        2
    )
    . "<br>";


echo "Best Formation: "
    . htmlspecialchars(
        (string) (
            $result[
                'formation'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


if (
    isset(
        $result[
            'search'
        ]
    )
    &&
    is_array(
        $result[
            'search'
        ]
    )
) {

    echo "Beam Width: "
        . (
            $result[
                'search'
            ]['beam_width']
            ?? 'N/A'
        )
        . "<br>";


    echo "Position Score Limit: "
        . (
            $result[
                'search'
            ]['position_score_limit']
            ?? 'N/A'
        )
        . "<br>";


    echo "Position Cheap Limit: "
        . (
            $result[
                'search'
            ]['position_cheap_limit']
            ?? 'N/A'
        )
        . "<br>";
        
    
    echo "GK Starter Min Confidence: "
        . wildcardRealNumber(
            $result[
                'search'
            ]['gk_starter_min_confidence']
            ?? null,
            1
        )
        . "%<br>";


    echo "GK Starter Quality Ratio: "
        . wildcardRealNumber(
            (
                $result[
                    'search'
                ]['gk_starter_quality_ratio']
                ?? null
            )
            !== null
                ? (
                    (
                        (float) $result[
                            'search'
                        ]['gk_starter_quality_ratio']
                    )
                    *
                    100
                )
                : null,
            1
        )
        . "%<br>";


    echo "GK Starter Score Floor: "
        . wildcardRealNumber(
            $result[
                'search'
            ]['gk_starter_score_floor']
            ?? null,
            2
        )
        . "<br>";


    echo "Final States Considered: "
        . (
            $result[
                'search'
            ]['final_states_considered']
            ?? 'N/A'
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * STOP EARLY IF OPTIMIZATION FAILED
 * ============================================================
 */

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

    echo "============================================<br>";
    echo "Wildcard Optimizer Real Data Diagnostic Complete<br>";
    echo "============================================<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$squad =
    $result[
        'squad'
    ]
    ?? [];


/*
 * ============================================================
 * POSITION OUTPUT
 * ============================================================
 */

$positionLabels = [

    'GK' =>
        'Goalkeepers',

    'DEF' =>
        'Defenders',

    'MID' =>
        'Midfielders',

    'FWD' =>
        'Forwards'
];


$positionPlayers = [

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
    $squad
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? null;


    if (
        isset(
            $positionPlayers[
                $position
            ]
        )
    ) {

        $positionPlayers[
            $position
        ][] =
            $player;
    }
}


foreach (
    $positionLabels
    as $position => $label
) {

    echo "============================================<br>";
    echo $label . "<br>";
    echo "============================================<br>";


    foreach (
        $positionPlayers[
            $position
        ]
        as $player
    ) {

        echo "<strong>"
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
                ?? 'Unknown Team'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
            . " | "
            . $position
            . " | "
            . wildcardRealPrice(
                $player[
                    'price'
                ]
                ?? null
            )
            . "<br>";


        echo "INT "
            . wildcardRealNumber(
                $player[
                    'intelligence_score'
                ]
                ?? null
            )
            . " | STR "
            . wildcardRealNumber(
                $player[
                    'strength_rating'
                ]
                ?? null
            )
            . " | VAL "
            . wildcardRealNumber(
                $player[
                    'value_rating'
                ]
                ?? null
            )
            . " | FIX "
            . wildcardRealNumber(
                $player[
                    'fixture_rating'
                ]
                ?? null
            )
            . "<br>";


        echo "Starter "
            . wildcardRealNumber(
                $player[
                    'starter_score'
                ]
                ?? null,
                2
            )
            . " | Value "
            . wildcardRealNumber(
                $player[
                    'squad_value_score'
                ]
                ?? null,
                2
            )
            . " | Wildcard "
            . wildcardRealNumber(
                $player[
                    'wildcard_score'
                ]
                ?? null,
                2
            )
            . "<br>";


        echo "Availability "
            . wildcardRealNumber(
                $player[
                    'availability_rating'
                ]
                ?? null
            )
            . " | Confidence "
            . wildcardRealNumber(
                $player[
                    'sample_confidence'
                ]
                ?? null
            )
            . "<br><br>";
    }
}


/*
 * ============================================================
 * POSITION AVERAGES
 * ============================================================
 */

echo "============================================<br>";
echo "Position Averages<br>";
echo "============================================<br>";


foreach (
    $positionPlayers
    as $position => $playersAtPosition
) {

    $intelligenceTotal =
        0.0;


    $wildcardTotal =
        0.0;


    $priceTotal =
        0.0;


    $count =
        count(
            $playersAtPosition
        );


    foreach (
        $playersAtPosition
        as $player
    ) {

        $intelligenceTotal +=
            (float) (
                $player[
                    'intelligence_score'
                ]
                ?? 0
            );


        $wildcardTotal +=
            (float) (
                $player[
                    'wildcard_score'
                ]
                ?? 0
            );


        $priceTotal +=
            (float) (
                $player[
                    'price'
                ]
                ?? 0
            );
    }


    $averageIntelligence =
        $count > 0
            ? (
                $intelligenceTotal
                /
                $count
            )
            : 0.0;


    $averageWildcard =
        $count > 0
            ? (
                $wildcardTotal
                /
                $count
            )
            : 0.0;


    echo $position
        . " | Avg INT "
        . number_format(
            $averageIntelligence,
            1
        )
        . " | Avg Wildcard "
        . number_format(
            $averageWildcard,
            2
        )
        . " | Spend "
        . wildcardRealPrice(
            $priceTotal
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * CLUB DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Club Distribution<br>";
echo "============================================<br>";


$clubDistribution =
    [];


foreach (
    $squad
    as $player
) {

    $teamId =
        (int) (
            $player[
                'team_id'
            ]
            ?? 0
        );


    $teamName =
        (string) (
            $player[
                'team_name'
            ]
            ?? (
                'Team '
                . $teamId
            )
        );


    if (
        !isset(
            $clubDistribution[
                $teamId
            ]
        )
    ) {

        $clubDistribution[
            $teamId
        ] = [

            'name' =>
                $teamName,

            'count' =>
                0
        ];
    }


    $clubDistribution[
        $teamId
    ]['count']++;
}


uasort(
    $clubDistribution,
    static function (
        array $a,
        array $b
    ): int {

        return (
            $b[
                'count'
            ]
            ?? 0
        )
        <=>
        (
            $a[
                'count'
            ]
            ?? 0
        );
    }
);


foreach (
    $clubDistribution
    as $club
) {

    echo htmlspecialchars(
        (string) (
            $club[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
        . ": "
        . (
            $club[
                'count'
            ]
            ?? 0
        )
        . "<br>";
}


echo "<br>";

/*
 * ============================================================
 * STARTING XI
 * ============================================================
 */

echo "============================================<br>";
echo "Best Starting XI<br>";
echo "============================================<br>";


foreach (
    $result[
        'starting_xi'
    ]
    ?? []
    as $player
) {

    echo "<strong>"
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
        . "</strong>";


    echo " | "
        . htmlspecialchars(
            (string) (
                $player[
                    'position'
                ]
                ?? 'N/A'
            ),
            ENT_QUOTES,
            'UTF-8'
        );


    echo " | "
        . wildcardRealPrice(
            $player[
                'price'
            ]
            ?? null
        );


    echo " | Starter "
        . wildcardRealNumber(
            $player[
                'starter_score'
            ]
            ?? null,
            2
        );


    echo " | Value "
        . wildcardRealNumber(
            $player[
                'squad_value_score'
            ]
            ?? null,
            2
        );


    echo " | Wildcard "
        . wildcardRealNumber(
            $player[
                'wildcard_score'
            ]
            ?? null,
            2
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * BENCH
 * ============================================================
 */

echo "============================================<br>";
echo "Ordered Bench<br>";
echo "============================================<br>";


foreach (
    $result[
        'bench'
    ]
    ?? []
    as $player
) {

    echo "Bench "
        . (
            $player[
                'bench_order'
            ]
            ?? '?'
        )
        . ": ";


    echo "<strong>"
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
        . "</strong>";


    echo " | "
        . htmlspecialchars(
            (string) (
                $player[
                    'position'
                ]
                ?? 'N/A'
            ),
            ENT_QUOTES,
            'UTF-8'
        );


    echo " | "
        . wildcardRealPrice(
            $player[
                'price'
            ]
            ?? null
        );


    echo " | Starter "
        . wildcardRealNumber(
            $player[
                'starter_score'
            ]
            ?? null,
            2
        );


    echo " | Value "
        . wildcardRealNumber(
            $player[
                'squad_value_score'
            ]
            ?? null,
            2
        );


    echo " | Wildcard "
        . wildcardRealNumber(
            $player[
                'wildcard_score'
            ]
            ?? null,
            2
        )
        . "<br>";
        
    $confidence =
        $player[
            'sample_confidence'
        ]
        ?? null;


    $reliabilityPenaltyPercent =
        wildcardBenchReliabilityPenaltyPercent(
            $confidence
        );


    $squadValueScore =
        is_numeric(
            $player[
                'squad_value_score'
            ]
            ?? null
        )
            ? (float) $player[
                'squad_value_score'
            ]
            : 0.0;


    $adjustedBenchValue =
        $squadValueScore
        *
        (
            1
            -
            (
                $reliabilityPenaltyPercent
                /
                100
            )
        );


    echo "Confidence "
        . wildcardRealNumber(
            $confidence,
            1
        )
        . "%"
        . " | Reliability Penalty "
        . wildcardRealNumber(
            $reliabilityPenaltyPercent,
            1
        )
        . "%"
        . " | Adjusted Bench Value "
        . wildcardRealNumber(
            $adjustedBenchValue,
            2
        )
        . "<br><br>";
}


echo "<br>";


/*
 * ============================================================
 * DIAGNOSTIC CHECKS
 * ============================================================
 */

echo "============================================<br>";
echo "Diagnostic Checks<br>";
echo "============================================<br>";


$checksPassed =
    0;


$checksFailed =
    0;


function wildcardRealCheck(
    string $description,
    bool $condition
): void {

    global $checksPassed;
    global $checksFailed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $checksPassed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $checksFailed++;
}


$validation =
    $result[
        'validation'
    ]
    ?? [];


wildcardRealCheck(
    'Wildcard optimizer returned success',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


wildcardRealCheck(
    'Generated squad contains 15 players',
    count(
        $squad
    )
    === 15
);


wildcardRealCheck(
    'Generated squad passes final validation',
    (
        $validation[
            'is_valid'
        ]
        ?? false
    )
    === true
);


$positionCounts =
    $validation[
        'position_counts'
    ]
    ?? [];


wildcardRealCheck(
    'Squad contains two goalkeepers',
    (
        $positionCounts[
            'GK'
        ]
        ?? 0
    )
    === 2
);


wildcardRealCheck(
    'Squad contains five defenders',
    (
        $positionCounts[
            'DEF'
        ]
        ?? 0
    )
    === 5
);


wildcardRealCheck(
    'Squad contains five midfielders',
    (
        $positionCounts[
            'MID'
        ]
        ?? 0
    )
    === 5
);


wildcardRealCheck(
    'Squad contains three forwards',
    (
        $positionCounts[
            'FWD'
        ]
        ?? 0
    )
    === 3
);


wildcardRealCheck(
    'Squad remains within £100m budget',
    (
        (float) (
            $result[
                'cost'
            ]
            ?? 999
        )
    )
    <= 100.0
);


wildcardRealCheck(
    'Squad bank is not negative',
    (
        (float) (
            $result[
                'bank'
            ]
            ?? -1
        )
    )
    >= 0
);


$playerIds =
    array_map(
        static function (
            array $player
        ): int {

            return (int) (
                $player[
                    'player_id'
                ]
                ?? 0
            );
        },
        $squad
    );


wildcardRealCheck(
    'Generated squad contains no duplicate players',
    count(
        $playerIds
    )
    ===
    count(
        array_unique(
            $playerIds
        )
    )
);


$clubLimitRespected =
    true;


foreach (
    $validation[
        'team_counts'
    ]
    ?? []
    as $count
) {

    if (
        $count > 3
    ) {

        $clubLimitRespected =
            false;

        break;
    }
}


wildcardRealCheck(
    'Maximum three-player club limit is respected',
    $clubLimitRespected
);


wildcardRealCheck(
    'Wildcard score is numeric',
    is_numeric(
        $result[
            'wildcard_score'
        ]
        ?? null
    )
);

wildcardRealCheck(
    'Structure score is numeric',
    is_numeric(
        $result[
            'structure_score'
        ]
        ?? null
    )
);


wildcardRealCheck(
    'Starting XI contains 11 players',
    count(
        $result[
            'starting_xi'
        ]
        ?? []
    )
    === 11
);


wildcardRealCheck(
    'Bench contains four players',
    count(
        $result[
            'bench'
        ]
        ?? []
    )
    === 4
);


wildcardRealCheck(
    'Best formation is returned',
    !empty(
        $result[
            'formation'
        ]
        ?? null
    )
);

$starterScoresPresent =
    true;


$valueScoresPresent =
    true;


foreach (
    $squad
    as $player
) {

    if (
        !is_numeric(
            $player[
                'starter_score'
            ]
            ?? null
        )
    ) {

        $starterScoresPresent =
            false;
    }


    if (
        !is_numeric(
            $player[
                'squad_value_score'
            ]
            ?? null
        )
    ) {

        $valueScoresPresent =
            false;
    }
}


wildcardRealCheck(
    'All selected players contain Starter Score',
    $starterScoresPresent
);


wildcardRealCheck(
    'All selected players contain Squad Value Score',
    $valueScoresPresent
);

$benchReliabilityDataValid =
    true;


foreach (
    $result[
        'bench'
    ]
    ?? []
    as $player
) {

    $confidence =
        $player[
            'sample_confidence'
        ]
        ?? null;


    $penalty =
        wildcardBenchReliabilityPenaltyPercent(
            $confidence
        );


    if (
        $penalty < 0
        ||
        $penalty > 100
    ) {

        $benchReliabilityDataValid =
            false;

        break;
    }
}


wildcardRealCheck(
    'Bench reliability penalties are valid',
    $benchReliabilityDataValid
);


echo "<br>";


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "============================================<br>";
echo "Wildcard Optimizer Real Data Diagnostic Complete<br>";
echo "============================================<br>";


echo "Checks Passed: "
    . $checksPassed
    . "<br>";


echo "Checks Failed: "
    . $checksFailed
    . "<br><br>";


if (
    $checksFailed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}