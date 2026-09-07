<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Recommendation Candidate Capture Player Rankings Test<br>";
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

function recommendationCandidateCapturePlayerRankingsCheck(
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
 * TEST DOUBLE REPOSITORY
 * ============================================================
 */

class RecommendationCandidateCapturePlayerRankingsRepository
    extends RecommendationCandidateRepository
{
    public ?RecommendationCandidate $candidate =
        null;


    public ?int $gameweekId =
        null;


    public function __construct()
    {
        /*
         * Deliberately do not call the parent constructor.
         *
         * This focused unit-test double does not use PDO or
         * persistence. It only captures the candidate passed to
         * saveLatest().
         */
    }


    public function saveLatest(
        int $gameweekId,
        RecommendationCandidate $candidate
    ): bool {

        $this->gameweekId =
            $gameweekId;


        $this->candidate =
            $candidate;


        return true;
    }
}


/*
 * ============================================================
 * SCENARIO A
 * CAPTURE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Capture Contract<br>";
echo "============================================<br>";


$reflection =
    new ReflectionClass(
        'RecommendationCandidateCaptureService'
    );


$method =
    $reflection
        ->getMethod(
            'capture'
        );


$parameterNames =
    array_map(
        static function (
            ReflectionParameter $parameter
        ): string {

            return
                $parameter->getName();
        },
        $method
            ->getParameters()
    );


recommendationCandidateCapturePlayerRankingsCheck(
    'capture() accepts playerRankings',
    in_array(
        'playerRankings',
        $parameterNames,
        true
    )
);


echo "<br>";


/*
 * ============================================================
 * STOP UNTIL PRODUCTION CONTRACT EXISTS
 * ============================================================
 */

if (
    !in_array(
        'playerRankings',
        $parameterNames,
        true
    )
) {

    echo "============================================<br>";
    echo "Recommendation Candidate Capture Player Rankings Test Summary<br>";
    echo "============================================<br>";


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    echo "RESULT: TESTS FAILED ❌";

    exit;
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$repository =
    new RecommendationCandidateCapturePlayerRankingsRepository();


$service =
    new RecommendationCandidateCaptureService(
        $repository
    );


$playerRankings = [

    [
        'player_id' => 101,
        'fpl_player_id' => 1001,
        'name' => 'Ranked Player',
        'position' => 'MID',
        'team_id' => 1,
        'price' => 8.0,
        'intelligence_score' => 87.5,
        'rank' => 1
    ],

    [
        'player_id' => 102,
        'fpl_player_id' => 1002,
        'name' => 'Second Player',
        'position' => 'FWD',
        'team_id' => 2,
        'price' => 9.0,
        'intelligence_score' => 79.0,
        'rank' => 2
    ]
];


$playerProjections = [

    [
        'player_id' => 101,
        'projected_points' => 7.5,
        'has_projected_points' => true
    ]
];


$startingXI = [];


for (
    $playerId = 101;
    $playerId <= 111;
    $playerId++
) {

    $startingXI[] = [
        'player_id' => $playerId
    ];
}


$gameweekDecisionResult = [

    'status' => 'success',

    'gameweek' => [
        'status' => 'success',
        'starting_xi' => $startingXI
    ],

    'captaincy' => [
        'status' => 'success',
        'captain' => [
            'player_id' => 101
        ],
        'vice_captain' => [
            'player_id' => 102
        ]
    ],

    'transfers' => [
        'status' => 'success',
        'recommendation' => 'Hold'
    ],

    'decision' => [
        'status' => 'success',
        'overall_action' => 'Hold'
    ]
];


$chipRecommendations = [

    'Wildcard' => [
        'recommendation' => 'Hold',
        'confidence' => 0.70
    ]
];


/*
 * ============================================================
 * SCENARIO B
 * CAPTURE PLAYER RANKINGS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Capture Player Rankings<br>";
echo "============================================<br>";


$result =
    $service
        ->capture(
            5,
            2702264,
            '2026-09-04 10:00:00',
            '2026-09-04 18:30:00',
            $playerRankings,
            $playerProjections,
            $gameweekDecisionResult,
            $chipRecommendations
        );


recommendationCandidateCapturePlayerRankingsCheck(
    'Capture succeeds',
    $result === true
);


recommendationCandidateCapturePlayerRankingsCheck(
    'Repository receives a RecommendationCandidate',
    $repository->candidate
    instanceof
    RecommendationCandidate
);


recommendationCandidateCapturePlayerRankingsCheck(
    'Captured candidate preserves player rankings unchanged',
    $repository
        ->candidate
        ?->getPlayerRankings()
    ===
    $playerRankings
);


recommendationCandidateCapturePlayerRankingsCheck(
    'Captured candidate preserves player projections separately',
    $repository
        ->candidate
        ?->getPlayerProjections()
    ===
    $playerProjections
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * EMPTY RANKINGS ARE REJECTED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Empty Rankings Are Rejected<br>";
echo "============================================<br>";


$exceptionThrown =
    false;


try {

    $service
        ->capture(
            5,
            2702264,
            '2026-09-04 10:00:00',
            '2026-09-04 18:30:00',
            [],
            $playerProjections,
            $gameweekDecisionResult,
            $chipRecommendations
        );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


recommendationCandidateCapturePlayerRankingsCheck(
    'Capture rejects empty player ranking evidence',
    $exceptionThrown
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * SOURCE EVIDENCE IS NOT ALTERED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Source Evidence Is Not Altered<br>";
echo "============================================<br>";


$sourceRankings =
    $playerRankings;


$service
    ->capture(
        5,
        2702264,
        '2026-09-04 11:00:00',
        '2026-09-04 18:30:00',
        $playerRankings,
        $playerProjections,
        $gameweekDecisionResult,
        $chipRecommendations
    );


recommendationCandidateCapturePlayerRankingsCheck(
    'Capture does not alter ranking evidence',
    $playerRankings
    ===
    $sourceRankings
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Recommendation Candidate Capture Player Rankings Test Summary<br>";
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

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}