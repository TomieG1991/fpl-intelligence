<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Effective Confidence Risk Test<br>";
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

function gameweekEffectiveRiskCheck(
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
 * PLAYER BUILDER
 * ============================================================
 */

function buildEffectiveRiskPlayer(
    int $playerId,
    string $name,
    string $position,
    float $gameweekScore = 60.0,
    float $sampleConfidence = 1.00,
    mixed $effectiveConfidence = 1.00,
    float $availability = 1.00,
    bool $includeEffectiveConfidence = true
): array {

    $player = [

        'player_id' =>
            $playerId,

        'name' =>
            $name,

        'position' =>
            $position,

        'gameweek_score' =>
            $gameweekScore,

        'sample_confidence' =>
            $sampleConfidence,

        'availability_rating' =>
            $availability,

        'components' => [

            'confidence' =>
                $sampleConfidence,

            'availability' =>
                $availability
        ]
    ];


    if (
        $includeEffectiveConfidence
    ) {

        $player[
            'effective_confidence'
        ] =
            $effectiveConfidence;
    }


    return $player;
}


/*
 * ============================================================
 * RESULT BUILDERS
 * ============================================================
 */

function buildEffectiveRiskGameweek(
    array $replacementPlayer
): array {

    $startingXI = [

        $replacementPlayer,

        buildEffectiveRiskPlayer(
            2,
            'Defender A',
            'DEF'
        ),

        buildEffectiveRiskPlayer(
            3,
            'Defender B',
            'DEF'
        ),

        buildEffectiveRiskPlayer(
            4,
            'Defender C',
            'DEF'
        ),

        buildEffectiveRiskPlayer(
            5,
            'Defender D',
            'DEF'
        ),

        buildEffectiveRiskPlayer(
            6,
            'Midfielder A',
            'MID'
        ),

        buildEffectiveRiskPlayer(
            7,
            'Midfielder B',
            'MID'
        ),

        buildEffectiveRiskPlayer(
            8,
            'Midfielder C',
            'MID'
        ),

        buildEffectiveRiskPlayer(
            9,
            'Forward A',
            'FWD'
        ),

        buildEffectiveRiskPlayer(
            10,
            'Forward B',
            'FWD'
        ),

        buildEffectiveRiskPlayer(
            11,
            'Forward C',
            'FWD'
        )
    ];


    $bench = [

        buildEffectiveRiskPlayer(
            12,
            'Bench Midfielder',
            'MID'
        ),

        buildEffectiveRiskPlayer(
            13,
            'Bench Defender A',
            'DEF'
        ),

        buildEffectiveRiskPlayer(
            14,
            'Bench Defender B',
            'DEF'
        ),

        buildEffectiveRiskPlayer(
            15,
            'Backup Goalkeeper',
            'GK'
        )
    ];


    return [

        'status' =>
            'success',

        'message' =>
            'Gameweek Starting XI generated successfully.',

        'formation' =>
            '4-3-3',

        'starting_xi_score' =>
            60.0,

        'bench_score' =>
            45.0,

        'starting_xi' =>
            $startingXI,

        'bench' =>
            $bench,

        'formations' =>
            []
    ];
}


function buildEffectiveRiskCaptainResult(): array
{

    return [

        'status' =>
            'success',

        'message' =>
            'Captain recommendations generated successfully.',

        'captain' => [

            'player_id' =>
                9,

            'name' =>
                'Forward A',

            'position' =>
                'FWD',

            'captain_score' =>
                70.0,

            'classification' =>
                'Good Option'
        ],

        'vice_captain' => [

            'player_id' =>
                6,

            'name' =>
                'Midfielder A',

            'position' =>
                'MID',

            'captain_score' =>
                65.0,

            'classification' =>
                'Good Option'
        ],

        'alternatives' =>
            [],

        'rankings' =>
            []
    ];
}


function buildEffectiveRiskTransferResult(): array
{

    return [

        'status' =>
            'success',

        'priority' =>
            'Low',

        'score' =>
            35.0,

        'recommendations' =>
            []
    ];
}


/*
 * ============================================================
 * RISK HELPERS
 * ============================================================
 */

function findEffectiveConfidenceRisk(
    array $decision,
    int $playerId
): ?array {

    $risks =
        $decision[
            'squad_risks'
        ][
            'risks'
        ]
        ?? [];


    foreach (
        $risks
        as $risk
    ) {

        if (
            (
                (int) (
                    $risk[
                        'player_id'
                    ]
                    ?? 0
                )
            )
            ===
            $playerId
            &&
            (
                $risk[
                    'type'
                ]
                ?? null
            )
            ===
            'confidence'
        ) {

            return $risk;
        }
    }


    return null;
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Setup<br>";
echo "============================================<br>";


$engine =
    new GameweekDecisionEngine();


$captainResult =
    buildEffectiveRiskCaptainResult();


$transferResult =
    buildEffectiveRiskTransferResult();


gameweekEffectiveRiskCheck(
    'Gameweek Decision Engine is available',
    $engine instanceof GameweekDecisionEngine
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * HEALTHY EFFECTIVE CONFIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Full-Match Early-Season Starter<br>";
echo "============================================<br>";


$fullStarter =
    buildEffectiveRiskPlayer(
        1,
        'Full Match Starter',
        'GK',
        60.0,
        0.10,
        0.64
    );


$fullStarterDecision =
    $engine
        ->evaluate(
            buildEffectiveRiskGameweek(
                $fullStarter
            ),
            $captainResult,
            $transferResult
        );


gameweekEffectiveRiskCheck(
    'Full-match starter decision succeeds',
    (
        $fullStarterDecision[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


$fullStarterRisk =
    findEffectiveConfidenceRisk(
        $fullStarterDecision,
        1
    );


gameweekEffectiveRiskCheck(
    '64% Effective Confidence prevents a false GW1 confidence risk',
    $fullStarterRisk === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * LEGACY SAMPLE CONFIDENCE FALLBACK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Legacy Sample Confidence Fallback<br>";
echo "============================================<br>";


$legacyPlayer =
    buildEffectiveRiskPlayer(
        1,
        'Legacy Low Confidence Player',
        'GK',
        60.0,
        0.10,
        null,
        1.00,
        false
    );


$legacyDecision =
    $engine
        ->evaluate(
            buildEffectiveRiskGameweek(
                $legacyPlayer
            ),
            $captainResult,
            $transferResult
        );


$legacyRisk =
    findEffectiveConfidenceRisk(
        $legacyDecision,
        1
    );


gameweekEffectiveRiskCheck(
    'Legacy player without Effective Confidence still uses sample confidence',
    is_array(
        $legacyRisk
    )
);


gameweekEffectiveRiskCheck(
    'Legacy 10% sample confidence remains high risk in Starting XI',
    (
        $legacyRisk[
            'severity'
        ]
        ?? null
    )
    ===
    'high'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * SHORT CAMEO
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Short Cameo Reliability Risk<br>";
echo "============================================<br>";


$cameoPlayer =
    buildEffectiveRiskPlayer(
        1,
        'Short Cameo Player',
        'GK',
        60.0,
        0.0056,
        0.036
    );


$cameoDecision =
    $engine
        ->evaluate(
            buildEffectiveRiskGameweek(
                $cameoPlayer
            ),
            $captainResult,
            $transferResult
        );


$cameoRisk =
    findEffectiveConfidenceRisk(
        $cameoDecision,
        1
    );


gameweekEffectiveRiskCheck(
    'Short cameo generates confidence risk',
    is_array(
        $cameoRisk
    )
);


gameweekEffectiveRiskCheck(
    '3.6% Effective Confidence remains high risk in Starting XI',
    (
        $cameoRisk[
            'severity'
        ]
        ?? null
    )
    ===
    'high'
);


gameweekEffectiveRiskCheck(
    'Short cameo risk stores Effective Confidence rather than raw sample confidence',
    is_numeric(
        $cameoRisk[
            'value'
        ]
        ?? null
    )
    &&
    abs(
        (float) $cameoRisk[
            'value'
        ]
        -
        3.6
    )
    <
    0.01
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * PARTIAL PARTICIPATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Partial Participation Risk<br>";
echo "============================================<br>";


$partialPlayer =
    buildEffectiveRiskPlayer(
        1,
        'Half Match Player',
        'GK',
        60.0,
        0.05,
        0.32
    );


$partialDecision =
    $engine
        ->evaluate(
            buildEffectiveRiskGameweek(
                $partialPlayer
            ),
            $captainResult,
            $transferResult
        );


$partialRisk =
    findEffectiveConfidenceRisk(
        $partialDecision,
        1
    );


gameweekEffectiveRiskCheck(
    '32% Effective Confidence generates a limited-confidence risk',
    is_array(
        $partialRisk
    )
);


gameweekEffectiveRiskCheck(
    '32% Effective Confidence produces medium risk rather than high risk',
    (
        $partialRisk[
            'severity'
        ]
        ?? null
    )
    ===
    'medium'
);


gameweekEffectiveRiskCheck(
    'Partial-participation risk stores 32% Effective Confidence',
    is_numeric(
        $partialRisk[
            'value'
        ]
        ?? null
    )
    &&
    abs(
        (float) $partialRisk[
            'value'
        ]
        -
        32.0
    )
    <
    0.01
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * NO TEAM EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: No Completed Team Match Evidence<br>";
echo "============================================<br>";


$noEvidencePlayer =
    buildEffectiveRiskPlayer(
        1,
        'Unplayed Team Player',
        'GK',
        60.0,
        0.00,
        null
    );


$noEvidenceDecision =
    $engine
        ->evaluate(
            buildEffectiveRiskGameweek(
                $noEvidencePlayer
            ),
            $captainResult,
            $transferResult
        );


$noEvidenceRisk =
    findEffectiveConfidenceRisk(
        $noEvidenceDecision,
        1
    );


gameweekEffectiveRiskCheck(
    'Explicit null Effective Confidence does not create a false reliability risk',
    $noEvidenceRisk === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * MATURE CONFIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Mature Confidence<br>";
echo "============================================<br>";


$maturePlayer =
    buildEffectiveRiskPlayer(
        1,
        'Mature Player',
        'GK',
        60.0,
        1.00,
        1.00
    );


$matureDecision =
    $engine
        ->evaluate(
            buildEffectiveRiskGameweek(
                $maturePlayer
            ),
            $captainResult,
            $transferResult
        );


$matureRisk =
    findEffectiveConfidenceRisk(
        $matureDecision,
        1
    );


gameweekEffectiveRiskCheck(
    'Full Effective Confidence creates no confidence risk',
    $matureRisk === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * SAMPLE CONFIDENCE REMAINS AVAILABLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Raw Sample Confidence Preservation<br>";
echo "============================================<br>";


gameweekEffectiveRiskCheck(
    'Player input still preserves raw sample confidence',
    array_key_exists(
        'sample_confidence',
        $fullStarter
    )
    &&
    abs(
        (float) $fullStarter[
            'sample_confidence'
        ]
        -
        0.10
    )
    <
    0.0001
);


gameweekEffectiveRiskCheck(
    'Player input exposes Effective Confidence separately',
    array_key_exists(
        'effective_confidence',
        $fullStarter
    )
    &&
    abs(
        (float) $fullStarter[
            'effective_confidence'
        ]
        -
        0.64
    )
    <
    0.0001
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Gameweek Effective Confidence Risk Test Summary<br>";
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