<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Priority Weight Calibration Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function transferPriorityWeightCheck(
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


function transferPriorityWeightSection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


function transferPriorityHistoricalPlayer(
    int $playerId,
    float|int|null $intelligence,
    float|int|null $value,
    float|int|null $fixture,
    float|int|null $availability,
    float|int|null $bestRealisedTransferGain
): array {

    return [

        'player_id' =>
            $playerId,

        'name' =>
            'Player '
            . $playerId,

        'intelligence_score' =>
            $intelligence,

        'value_rating' =>
            $value,

        'fixture_rating' =>
            $fixture,

        'availability_rating' =>
            $availability,

        'best_realised_transfer_gain' =>
            $bestRealisedTransferGain
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario A: Class Contract'
);


$classExists =
    class_exists(
        'TransferPriorityWeightCalibrationService'
    );


transferPriorityWeightCheck(
    'TransferPriorityWeightCalibrationService class exists',
    $classExists
);


transferPriorityWeightCheck(
    'TransferPriorityWeightCalibrationService exposes evaluate()',
    $classExists
    &&
    method_exists(
        'TransferPriorityWeightCalibrationService',
        'evaluate'
    )
);


if (!$classExists) {

    exit;
}


/*
 * ============================================================
 * WEIGHT CANDIDATES
 * ============================================================
 */

$weightCandidates = [

    /*
     * Current executable production weights.
     */
    [
        'intelligence_weight' =>
            0.45,

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.15,

        'availability_weight' =>
            0.20
    ],

    /*
     * Fixture-heavy alternative.
     */
    [
        'intelligence_weight' =>
            0.10,

        'value_weight' =>
            0.10,

        'fixture_weight' =>
            0.70,

        'availability_weight' =>
            0.10
    ],

    /*
     * Availability-heavy alternative.
     */
    [
        'intelligence_weight' =>
            0.10,

        'value_weight' =>
            0.10,

        'fixture_weight' =>
            0.10,

        'availability_weight' =>
            0.70
    ]
];


/*
 * ============================================================
 * HISTORICAL GAMEWEEK A
 * ============================================================
 *
 * Player 101:
 *
 * I 40 => weakness 60
 * V 60 => weakness 40
 * F 80 => weakness 20
 * A 90 => weakness 10
 *
 * Production:
 * 60*.45 + 40*.20 + 20*.15 + 10*.20
 * = 40.0
 *
 *
 * Player 102:
 *
 * I 60 => weakness 40
 * V 60 => weakness 40
 * F 20 => weakness 80
 * A 90 => weakness 10
 *
 * Production:
 * 40*.45 + 40*.20 + 80*.15 + 10*.20
 * = 40.0
 *
 *
 * Production therefore ties 101 and 102.
 *
 * Pure calibration tie-break:
 *
 * 1. transfer priority descending
 * 2. Intelligence weakness descending
 * 3. Fixture weakness descending
 * 4. Value weakness descending
 * 5. Availability weakness descending
 * 6. player_id ascending
 *
 * Player 101 therefore wins the production tie because its
 * Intelligence weakness is larger.
 *
 * Under fixture-heavy weights Player 102 becomes the clear
 * outgoing priority.
 *
 * Realised opportunity:
 *
 * 101 => +3
 * 102 => +9  <- best historical outgoing opportunity
 * 103 => +1
 */

$historicalGameweekA = [

    'gameweek_id' =>
        1,

    'players' => [

        transferPriorityHistoricalPlayer(
            101,
            40,
            60,
            80,
            90,
            3
        ),

        transferPriorityHistoricalPlayer(
            102,
            60,
            60,
            20,
            90,
            9
        ),

        transferPriorityHistoricalPlayer(
            103,
            80,
            80,
            80,
            80,
            1
        )
    ]
];


/*
 * ============================================================
 * HISTORICAL GAMEWEEK B
 * ============================================================
 *
 * Player 201 is the best realised outgoing opportunity because
 * of an availability problem.
 *
 * Availability-heavy weighting should identify him.
 *
 * Production:
 *
 * 201:
 * I weakness 20
 * V weakness 20
 * F weakness 20
 * A weakness 90
 *
 * 20*.45 + 20*.20 + 20*.15 + 90*.20
 * = 34.0
 *
 * 202:
 * I weakness 60
 * V weakness 40
 * F weakness 30
 * A weakness 10
 *
 * 60*.45 + 40*.20 + 30*.15 + 10*.20
 * = 41.5
 *
 * Production therefore selects 202.
 *
 * Availability-heavy:
 *
 * 201 = 69.0
 * 202 = 19.0
 *
 * so 201 is selected.
 */

$historicalGameweekB = [

    'gameweek_id' =>
        2,

    'players' => [

        transferPriorityHistoricalPlayer(
            201,
            80,
            80,
            80,
            10,
            8
        ),

        transferPriorityHistoricalPlayer(
            202,
            40,
            60,
            70,
            90,
            2
        ),

        transferPriorityHistoricalPlayer(
            203,
            85,
            85,
            85,
            85,
            0
        )
    ]
];


/*
 * ============================================================
 * HISTORICAL GAMEWEEK C
 * ZERO / NEGATIVE REALISED OPPORTUNITY
 * ============================================================
 *
 * Negative and zero realised transfer gains are genuine
 * historical evidence.
 *
 * The best opportunity here is zero: holding/selling player 302
 * would have avoided the negative realised replacement outcome
 * associated with player 301.
 */

$historicalGameweekC = [

    'gameweek_id' =>
        3,

    'players' => [

        transferPriorityHistoricalPlayer(
            301,
            30,
            50,
            50,
            50,
            -2
        ),

        transferPriorityHistoricalPlayer(
            302,
            70,
            70,
            70,
            70,
            0
        )
    ]
];


/*
 * ============================================================
 * HISTORICAL GAMEWEEK D
 * UNAVAILABLE REALISED BENCHMARK
 * ============================================================
 *
 * Candidate priorities can still be calculated, but the
 * gameweek cannot be compared if the selected player's realised
 * transfer gain or the best realised outgoing opportunity is
 * unavailable.
 */

$historicalGameweekD = [

    'gameweek_id' =>
        4,

    'players' => [

        transferPriorityHistoricalPlayer(
            401,
            20,
            20,
            20,
            20,
            null
        ),

        transferPriorityHistoricalPlayer(
            402,
            80,
            80,
            80,
            80,
            5
        )
    ]
];


$historicalGameweeks = [

    $historicalGameweekA,
    $historicalGameweekB,
    $historicalGameweekC,
    $historicalGameweekD
];


$service =
    new TransferPriorityWeightCalibrationService();


/*
 * ============================================================
 * SCENARIO B
 * RESULT CONTRACT
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario B: Result Contract'
);


$result =
    $service->evaluate(
        $historicalGameweeks,
        $weightCandidates
    );


transferPriorityWeightCheck(
    'Evaluation result is an array',
    is_array(
        $result
    )
);


transferPriorityWeightCheck(
    'Result exposes evaluations',
    isset(
        $result[
            'evaluations'
        ]
    )
    &&
    is_array(
        $result[
            'evaluations'
        ]
    )
);


$evaluations =
    $result[
        'evaluations'
    ]
    ?? [];


transferPriorityWeightCheck(
    'Every caller-supplied weight candidate is evaluated',
    count(
        $evaluations
    )
    === 3
);


/*
 * ============================================================
 * SCENARIO C
 * WEIGHT EVIDENCE
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario C: Weight Evidence'
);


$productionEvaluation =
    $evaluations[
        0
    ]
    ?? [];


$fixtureEvaluation =
    $evaluations[
        1
    ]
    ?? [];


$availabilityEvaluation =
    $evaluations[
        2
    ]
    ?? [];


transferPriorityWeightCheck(
    'Production Intelligence weight is preserved',
    ($productionEvaluation['intelligence_weight'] ?? null) === 0.45
);


transferPriorityWeightCheck(
    'Production Value weight is preserved',
    ($productionEvaluation['value_weight'] ?? null) === 0.20
);


transferPriorityWeightCheck(
    'Production Fixture weight is preserved',
    ($productionEvaluation['fixture_weight'] ?? null) === 0.15
);


transferPriorityWeightCheck(
    'Production Availability weight is preserved',
    ($productionEvaluation['availability_weight'] ?? null) === 0.20
);


transferPriorityWeightCheck(
    'Fixture-heavy candidate is preserved',
    ($fixtureEvaluation['fixture_weight'] ?? null) === 0.70
);


transferPriorityWeightCheck(
    'Availability-heavy candidate is preserved',
    ($availabilityEvaluation['availability_weight'] ?? null) === 0.70
);


/*
 * ============================================================
 * SCENARIO D
 * PRODUCTION PRIORITY REPLAY
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario D: Production Priority Replay'
);


$productionGameweeks =
    $productionEvaluation[
        'gameweeks'
    ]
    ?? [];


$productionGameweekA =
    $productionGameweeks[
        0
    ]
    ?? [];


$productionPrioritiesA =
    $productionGameweekA[
        'player_priorities'
    ]
    ?? [];


$priority101 =
    null;


$priority102 =
    null;


foreach (
    $productionPrioritiesA
    as $priorityRow
) {

    if (
        ($priorityRow['player_id'] ?? null)
        ===
        101
    ) {

        $priority101 =
            $priorityRow[
                'transfer_priority'
            ]
            ?? null;
    }


    if (
        ($priorityRow['player_id'] ?? null)
        ===
        102
    ) {

        $priority102 =
            $priorityRow[
                'transfer_priority'
            ]
            ?? null;
    }
}


transferPriorityWeightCheck(
    'Production priority for Player 101 replays to 40.0',
    $priority101 === 40.0
);


transferPriorityWeightCheck(
    'Production priority for Player 102 replays to 40.0',
    $priority102 === 40.0
);


transferPriorityWeightCheck(
    'Production tie selects Player 101 using deterministic tie-break',
    (
        $productionGameweekA[
            'selected_outgoing'
        ]['player_id']
        ?? null
    )
    === 101
);


/*
 * ============================================================
 * SCENARIO E
 * FIXTURE-HEAVY SELECTION
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario E: Fixture-Heavy Selection'
);


$fixtureGameweeks =
    $fixtureEvaluation[
        'gameweeks'
    ]
    ?? [];


$fixtureGameweekA =
    $fixtureGameweeks[
        0
    ]
    ?? [];


transferPriorityWeightCheck(
    'Fixture-heavy candidate selects Player 102',
    (
        $fixtureGameweekA[
            'selected_outgoing'
        ]['player_id']
        ?? null
    )
    === 102
);


transferPriorityWeightCheck(
    'Fixture-heavy Player 102 priority is 65.0',
    (
        $fixtureGameweekA[
            'selected_outgoing'
        ]['transfer_priority']
        ?? null
    )
    === 65.0
);


/*
 * ============================================================
 * SCENARIO F
 * AVAILABILITY-HEAVY SELECTION
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario F: Availability-Heavy Selection'
);


$availabilityGameweeks =
    $availabilityEvaluation[
        'gameweeks'
    ]
    ?? [];


$availabilityGameweekB =
    $availabilityGameweeks[
        1
    ]
    ?? [];


transferPriorityWeightCheck(
    'Availability-heavy candidate selects Player 201',
    (
        $availabilityGameweekB[
            'selected_outgoing'
        ]['player_id']
        ?? null
    )
    === 201
);


transferPriorityWeightCheck(
    'Availability-heavy Player 201 priority is 69.0',
    (
        $availabilityGameweekB[
            'selected_outgoing'
        ]['transfer_priority']
        ?? null
    )
    === 69.0
);


/*
 * ============================================================
 * SCENARIO G
 * REALISED OUTGOING OPPORTUNITY
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario G: Realised Outgoing Opportunity'
);


transferPriorityWeightCheck(
    'Production GW1 selected realised gain is preserved',
    ($productionGameweekA['selected_realised_gain'] ?? null) === 3
);


transferPriorityWeightCheck(
    'Production GW1 best realised outgoing player is Player 102',
    (
        $productionGameweekA[
            'best_realised_outgoing'
        ]['player_id']
        ?? null
    )
    === 102
);


transferPriorityWeightCheck(
    'Production GW1 best realised gain is 9',
    ($productionGameweekA['best_realised_gain'] ?? null) === 9
);


transferPriorityWeightCheck(
    'Production GW1 outgoing opportunity points lost is 6',
    ($productionGameweekA['selection_points_lost'] ?? null) === 6
);


transferPriorityWeightCheck(
    'Fixture-heavy GW1 selects the optimal realised outgoing opportunity',
    ($fixtureGameweekA['selection_points_lost'] ?? null) === 0
);


/*
 * ============================================================
 * SCENARIO H
 * SECOND WEIGHT-DEPENDENT GAMEWEEK
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario H: Availability Opportunity'
);


$productionGameweekB =
    $productionGameweeks[
        1
    ]
    ?? [];


transferPriorityWeightCheck(
    'Production GW2 selects Player 202',
    (
        $productionGameweekB[
            'selected_outgoing'
        ]['player_id']
        ?? null
    )
    === 202
);


transferPriorityWeightCheck(
    'Production GW2 loses 6 realised transfer-opportunity points',
    ($productionGameweekB['selection_points_lost'] ?? null) === 6
);


transferPriorityWeightCheck(
    'Availability-heavy GW2 selects the optimal outgoing player',
    ($availabilityGameweekB['selection_points_lost'] ?? null) === 0
);


/*
 * ============================================================
 * SCENARIO I
 * ZERO / NEGATIVE REALISED GAINS
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario I: Zero and Negative Realised Gain'
);


$productionGameweekC =
    $productionGameweeks[
        2
    ]
    ?? [];


transferPriorityWeightCheck(
    'Negative selected realised transfer gain remains valid evidence',
    ($productionGameweekC['selected_realised_gain'] ?? null) === -2
);


transferPriorityWeightCheck(
    'Zero best realised transfer gain remains valid evidence',
    ($productionGameweekC['best_realised_gain'] ?? null) === 0
);


transferPriorityWeightCheck(
    'Negative versus zero realised opportunity produces two points lost',
    ($productionGameweekC['selection_points_lost'] ?? null) === 2
);


/*
 * ============================================================
 * SCENARIO J
 * UNAVAILABLE REALISED EVIDENCE
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario J: Unavailable Realised Evidence'
);


$productionGameweekD =
    $productionGameweeks[
        3
    ]
    ?? [];


transferPriorityWeightCheck(
    'Priority can still select an outgoing player when realised evidence is missing',
    (
        $productionGameweekD[
            'selected_outgoing'
        ]['player_id']
        ?? null
    )
    === 401
);


transferPriorityWeightCheck(
    'Missing selected realised gain remains explicit null',
    array_key_exists(
        'selected_realised_gain',
        $productionGameweekD
    )
    &&
    $productionGameweekD[
        'selected_realised_gain'
    ]
    === null
);


transferPriorityWeightCheck(
    'Unavailable gameweek has null selection points lost',
    array_key_exists(
        'selection_points_lost',
        $productionGameweekD
    )
    &&
    $productionGameweekD[
        'selection_points_lost'
    ]
    === null
);


/*
 * ============================================================
 * SCENARIO K
 * AGGREGATE METRICS
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario K: Aggregate Metrics'
);


$productionMetrics =
    $productionEvaluation[
        'metrics'
    ]
    ?? [];


transferPriorityWeightCheck(
    'Production metrics count all four historical gameweeks',
    ($productionMetrics['total_gameweeks'] ?? null) === 4
);


transferPriorityWeightCheck(
    'Production metrics count three comparable gameweeks',
    ($productionMetrics['comparable_gameweeks'] ?? null) === 3
);


transferPriorityWeightCheck(
    'Production metrics count one unavailable gameweek',
    ($productionMetrics['unavailable_gameweeks'] ?? null) === 1
);


transferPriorityWeightCheck(
    'Production total outgoing opportunity points lost is 14',
    ($productionMetrics['total_selection_points_lost'] ?? null) === 14
);


transferPriorityWeightCheck(
    'Production mean outgoing opportunity points lost is 4.67',
    ($productionMetrics['mean_selection_points_lost'] ?? null) === 4.67
);


transferPriorityWeightCheck(
    'Production records zero optimal outgoing selections',
    ($productionMetrics['optimal_outgoing_selections'] ?? null) === 0
);


/*
 * ============================================================
 * SCENARIO L
 * PLAYER MEMBERSHIP PRESERVATION
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario L: Historical Membership'
);


transferPriorityWeightCheck(
    'All valid historical players remain in the priority evidence',
    count(
        $productionPrioritiesA
    )
    === 3
);


transferPriorityWeightCheck(
    'Historical player identity is preserved',
    array_column(
        $productionPrioritiesA,
        'player_id'
    )
    === [
        101,
        102,
        103
    ]
);


/*
 * ============================================================
 * SCENARIO M
 * MISSING COMPONENT RENORMALISATION
 * ============================================================
 *
 * Production SquadTransferIntelligence currently normalises a
 * missing metric to zero before weakness conversion.
 *
 * However, for historical calibration we must not invent a
 * maximum weakness from unavailable evidence.
 *
 * Candidate weights therefore renormalise across the available
 * historical components only.
 */

transferPriorityWeightSection(
    'Scenario M: Missing Component Renormalisation'
);


$missingComponentHistory = [

    [
        'gameweek_id' =>
            10,

        'players' => [

            transferPriorityHistoricalPlayer(
                501,
                40,
                null,
                80,
                80,
                4
            ),

            transferPriorityHistoricalPlayer(
                502,
                60,
                60,
                60,
                60,
                2
            )
        ]
    ]
];


$missingComponentResult =
    $service->evaluate(
        $missingComponentHistory,
        [
            $weightCandidates[
                0
            ]
        ]
    );


$missingComponentPlayers =
    $missingComponentResult[
        'evaluations'
    ][0]['gameweeks'][0]['player_priorities']
    ?? [];


$player501Priority =
    null;


foreach (
    $missingComponentPlayers
    as $priorityRow
) {

    if (
        ($priorityRow['player_id'] ?? null)
        ===
        501
    ) {

        $player501Priority =
            $priorityRow[
                'transfer_priority'
            ]
            ?? null;

        break;
    }
}


/*
 * Available weights:
 *
 * Intelligence 0.45
 * Fixture      0.15
 * Availability 0.20
 *
 * total available = 0.80
 *
 * weighted weakness:
 *
 * (60*.45 + 20*.15 + 20*.20) / .80
 * = 42.5
 */
transferPriorityWeightCheck(
    'Missing Value component renormalises remaining candidate weights',
    $player501Priority === 42.5
);


/*
 * ============================================================
 * SCENARIO N
 * ALL COMPONENTS UNAVAILABLE
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario N: All Components Unavailable'
);


$allMissingResult =
    $service->evaluate(
        [
            [
                'gameweek_id' =>
                    11,

                'players' => [

                    transferPriorityHistoricalPlayer(
                        601,
                        null,
                        null,
                        null,
                        null,
                        5
                    )
                ]
            ]
        ],
        [
            $weightCandidates[
                0
            ]
        ]
    );


$allMissingPlayer =
    $allMissingResult[
        'evaluations'
    ][0]['gameweeks'][0]['player_priorities'][0]
    ?? [];


transferPriorityWeightCheck(
    'All unavailable priority components produce null transfer priority',
    array_key_exists(
        'transfer_priority',
        $allMissingPlayer
    )
    &&
    $allMissingPlayer[
        'transfer_priority'
    ]
    === null
);


transferPriorityWeightCheck(
    'Gameweek with no scoreable outgoing player has null selected outgoing',
    array_key_exists(
        'selected_outgoing',
        $allMissingResult[
            'evaluations'
        ][0]['gameweeks'][0]
        ?? []
    )
    &&
    $allMissingResult[
        'evaluations'
    ][0]['gameweeks'][0]['selected_outgoing']
    === null
);


/*
 * ============================================================
 * SCENARIO O
 * MALFORMED HISTORICAL ROWS
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario O: Malformed Historical Rows'
);


$malformedResult =
    $service->evaluate(
        [
            'malformed gameweek',

            [
                'gameweek_id' =>
                    12,

                'players' => [

                    'malformed player',

                    transferPriorityHistoricalPlayer(
                        701,
                        50,
                        50,
                        50,
                        50,
                        1
                    ),

                    [
                        'player_id' =>
                            0,

                        'intelligence_score' =>
                            10,

                        'value_rating' =>
                            10,

                        'fixture_rating' =>
                            10,

                        'availability_rating' =>
                            10,

                        'best_realised_transfer_gain' =>
                            10
                    ]
                ]
            ]
        ],
        [
            $weightCandidates[
                0
            ]
        ]
    );


$malformedGameweeks =
    $malformedResult[
        'evaluations'
    ][0]['gameweeks']
    ?? [];


transferPriorityWeightCheck(
    'Malformed non-array historical gameweek is ignored',
    count(
        $malformedGameweeks
    )
    === 1
);


transferPriorityWeightCheck(
    'Malformed and non-positive player identities are ignored',
    count(
        $malformedGameweeks[
            0
        ]['player_priorities']
        ?? []
    )
    === 1
);


/*
 * ============================================================
 * SCENARIO P
 * EMPTY CANDIDATE SET
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario P: Empty Candidate Set'
);


$emptyCandidateResult =
    $service->evaluate(
        $historicalGameweeks,
        []
    );


transferPriorityWeightCheck(
    'Empty caller candidate set produces no evaluations',
    ($emptyCandidateResult['evaluations'] ?? null) === []
);


/*
 * ============================================================
 * SCENARIO Q
 * EMPTY HISTORICAL EVIDENCE
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario Q: Empty Historical Evidence'
);


$emptyHistoryResult =
    $service->evaluate(
        [],
        [
            $weightCandidates[
                0
            ]
        ]
    );


$emptyHistoryEvaluation =
    $emptyHistoryResult[
        'evaluations'
    ][0]
    ?? [];


transferPriorityWeightCheck(
    'Empty history still evaluates caller-supplied candidate',
    ($emptyHistoryEvaluation['intelligence_weight'] ?? null) === 0.45
);


transferPriorityWeightCheck(
    'Empty history produces no gameweek evaluations',
    ($emptyHistoryEvaluation['gameweeks'] ?? null) === []
);


$emptyHistoryMetrics =
    $emptyHistoryEvaluation[
        'metrics'
    ]
    ?? [];


transferPriorityWeightCheck(
    'Empty history reports zero total gameweeks',
    ($emptyHistoryMetrics['total_gameweeks'] ?? null) === 0
);


transferPriorityWeightCheck(
    'Empty history reports zero comparable gameweeks',
    ($emptyHistoryMetrics['comparable_gameweeks'] ?? null) === 0
);


transferPriorityWeightCheck(
    'Empty history reports zero unavailable gameweeks',
    ($emptyHistoryMetrics['unavailable_gameweeks'] ?? null) === 0
);


transferPriorityWeightCheck(
    'Empty history reports zero total points lost',
    ($emptyHistoryMetrics['total_selection_points_lost'] ?? null) === 0
);


transferPriorityWeightCheck(
    'Empty history reports null mean points lost',
    array_key_exists(
        'mean_selection_points_lost',
        $emptyHistoryMetrics
    )
    &&
    $emptyHistoryMetrics[
        'mean_selection_points_lost'
    ]
    === null
);


transferPriorityWeightCheck(
    'Empty history reports zero optimal outgoing selections',
    ($emptyHistoryMetrics['optimal_outgoing_selections'] ?? null) === 0
);


/*
 * ============================================================
 * SCENARIO R
 * INVALID WEIGHT CANDIDATES
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario R: Invalid Weight Candidates'
);


$invalidCandidates = [

    'candidate must be an array',

    [
        'intelligence_weight' =>
            0.45,

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.15
    ],

    [
        'intelligence_weight' =>
            'bad',

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.15,

        'availability_weight' =>
            0.20
    ],

    [
        'intelligence_weight' =>
            -0.05,

        'value_weight' =>
            0.30,

        'fixture_weight' =>
            0.30,

        'availability_weight' =>
            0.45
    ],

    [
        'intelligence_weight' =>
            1.05,

        'value_weight' =>
            0.00,

        'fixture_weight' =>
            0.00,

        'availability_weight' =>
            -0.05
    ],

    [
        'intelligence_weight' =>
            0.40,

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.15,

        'availability_weight' =>
            0.15
    ]
];


foreach (
    $invalidCandidates
    as $index => $invalidCandidate
) {

    $threw =
        false;


    try {

        $service->evaluate(
            [],
            [
                $invalidCandidate
            ]
        );

    } catch (
        InvalidArgumentException $exception
    ) {

        $threw =
            true;
    }


    transferPriorityWeightCheck(
        'Invalid weight candidate '
        . ($index + 1)
        . ' is rejected',
        $threw
    );
}


/*
 * ============================================================
 * SCENARIO S
 * CALLER OWNS CANDIDATE GRID
 * ============================================================
 */

transferPriorityWeightSection(
    'Scenario S: Caller Owns Candidate Grid'
);


$singleCandidate =
    [
        [
            'intelligence_weight' =>
                0.25,

            'value_weight' =>
                0.25,

            'fixture_weight' =>
                0.25,

            'availability_weight' =>
                0.25
        ]
    ];


$singleCandidateResult =
    $service->evaluate(
        [],
        $singleCandidate
    );


transferPriorityWeightCheck(
    'Service evaluates exactly the candidate grid supplied by caller',
    count(
        $singleCandidateResult[
            'evaluations'
        ]
        ?? []
    )
    === 1
);


transferPriorityWeightCheck(
    'Service does not silently add current production weights',
    (
        $singleCandidateResult[
            'evaluations'
        ][0]['intelligence_weight']
        ?? null
    )
    === 0.25
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Transfer Priority Weight Calibration Service Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}