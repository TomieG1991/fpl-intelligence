<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Production Capture Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerSnapshotProductionAssert(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


function playerSnapshotProductionSection(
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


/*
 * ============================================================
 * TEST DOUBLE — GAMEWEEK REPOSITORY
 * ============================================================
 */

class PlayerSnapshotProductionGameweekRepository
{
    public int $lookupCalls =
        0;


    public ?string $lastTimestamp =
        null;


    public ?array $result;


    public function __construct(
        ?array $result
    ) {

        $this->result =
            $result;
    }


    public function getNextDeadlineAfter(
        string $timestamp
    ): ?array {

        $this->lookupCalls++;


        $this->lastTimestamp =
            $timestamp;


        return
            $this->result;
    }
}


/*
 * ============================================================
 * TEST DOUBLE — PLAYER REPOSITORY
 * ============================================================
 */

class PlayerSnapshotProductionPlayerRepository
{
    public int $getAllCalls =
        0;


    public array $players;


    public function __construct(
        array $players
    ) {

        $this->players =
            $players;
    }


    public function getAll(): array
    {
        $this->getAllCalls++;


        return
            $this->players;
    }
}


/*
 * ============================================================
 * TEST DOUBLE — CANDIDATE BUILDER
 * ============================================================
 */

class PlayerSnapshotProductionCandidateBuilder
{
    public int $buildCalls =
        0;


    public ?array $lastBuild =
        null;


    public array $result;


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
    }


    public function buildCandidates(
        int $gameweekId,
        string $generatedAt,
        string $deadlineTime,
        array $players
    ): array {

        $this->buildCalls++;


        $this->lastBuild = [

            'gameweek_id' =>
                $gameweekId,

            'generated_at' =>
                $generatedAt,

            'deadline_time' =>
                $deadlineTime,

            'players' =>
                $players
        ];


        return
            $this->result;
    }
}


/*
 * ============================================================
 * TEST DOUBLE — CANDIDATE REPOSITORY
 * ============================================================
 */

class PlayerSnapshotProductionCandidateRepository
{
    public int $saveCalls =
        0;


    public array $saved =
        [];


    public array $results;


    public function __construct(
        array $results = []
    ) {

        $this->results =
            $results;
    }


    public function saveLatest(
        int $gameweekId,
        PlayerGameweekSnapshotCandidate $candidate
    ): bool {

        $this->saveCalls++;


        $this->saved[] = [

            'gameweek_id' =>
                $gameweekId,

            'candidate' =>
                $candidate
        ];


        if (
            array_key_exists(
                $this->saveCalls - 1,
                $this->results
            )
        ) {

            return
                (bool) $this->results[
                    $this->saveCalls - 1
                ];
        }


        return true;
    }
}


/*
 * ============================================================
 * CONTROLLED EVIDENCE
 * ============================================================
 */

$generatedAt =
    '2026-09-18 17:30:00';


$targetGameweek = [

    'id' =>
        8,

    'fpl_gameweek_id' =>
        4,

    'name' =>
        'Gameweek 4',

    'deadline_time' =>
        '2026-09-18 18:30:00'
];


$players = [

    [
        'id' =>
            101,

        'fpl_player_id' =>
            501,

        'team_id' =>
            11
    ],

    [
        'id' =>
            102,

        'fpl_player_id' =>
            502,

        'team_id' =>
            12
    ]
];


$stateOne = [

    'player_id' =>
        101,

    'fpl_player_id' =>
        501,

    'team_id' =>
        11,

    'position' =>
        'MID',

    'price' =>
        7.5,

    'selected' =>
        null,

    'selected_by_percent' =>
        12.5,

    'chance_of_playing' =>
        100,

    'status' =>
        'a',

    'news' =>
        '',

    'minutes' =>
        180,

    'goals' =>
        1,

    'assists' =>
        2,

    'clean_sheets' =>
        1,

    'bonus' =>
        3,

    'bps' =>
        48,

    'ict_index' =>
        22.4,

    'expected_goals' =>
        0.75,

    'expected_assists' =>
        0.62,

    'expected_goal_involvements' =>
        1.37
];


$stateTwo =
    $stateOne;


$stateTwo[
    'player_id'
] =
    102;


$stateTwo[
    'fpl_player_id'
] =
    502;


$stateTwo[
    'team_id'
] =
    12;


$candidateOne =
    new PlayerGameweekSnapshotCandidate(
        8,
        $generatedAt,
        $targetGameweek[
            'deadline_time'
        ],
        $stateOne
    );


$candidateTwo =
    new PlayerGameweekSnapshotCandidate(
        8,
        $generatedAt,
        $targetGameweek[
            'deadline_time'
        ],
        $stateTwo
    );


/*
 * ============================================================
 * A. CLASS CONTRACT
 * ============================================================
 */

playerSnapshotProductionSection(
    'A. Class Contract'
);


$classExists =
    class_exists(
        'PlayerGameweekSnapshotCandidateProductionCapture'
    );


playerSnapshotProductionAssert(
    $classExists,
    'PlayerGameweekSnapshotCandidateProductionCapture exists.'
);


if (
    !$classExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Player Gameweek Snapshot Candidate Production Capture Test Summary<br>";
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
 * B. VALID CAPTURE
 * ============================================================
 */

playerSnapshotProductionSection(
    'B. Valid Capture'
);


$gameweekRepository =
    new PlayerSnapshotProductionGameweekRepository(
        $targetGameweek
    );


$playerRepository =
    new PlayerSnapshotProductionPlayerRepository(
        $players
    );


$builder =
    new PlayerSnapshotProductionCandidateBuilder(
        [
            $candidateOne,
            $candidateTwo
        ]
    );


$candidateRepository =
    new PlayerSnapshotProductionCandidateRepository();


$capture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $gameweekRepository,
        $playerRepository,
        $builder,
        $candidateRepository
    );


$result =
    $capture->capture(
        $generatedAt
    );


playerSnapshotProductionAssert(
    is_array(
        $result
    ),
    'Production capture returns a result array.'
);


playerSnapshotProductionAssert(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Captured',
    'Successful production capture reports Captured status.'
);


playerSnapshotProductionAssert(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    8,
    'Result preserves resolved local gameweek ID.'
);


playerSnapshotProductionAssert(
    (
        $result[
            'fpl_gameweek_id'
        ]
        ?? null
    )
    ===
    4,
    'Result preserves resolved FPL gameweek ID.'
);


playerSnapshotProductionAssert(
    (
        $result[
            'players_considered'
        ]
        ?? null
    )
    ===
    2,
    'Result reports live players considered.'
);


playerSnapshotProductionAssert(
    (
        $result[
            'candidates_built'
        ]
        ?? null
    )
    ===
    2,
    'Result reports candidates built.'
);


playerSnapshotProductionAssert(
    (
        $result[
            'saved'
        ]
        ?? null
    )
    ===
    2,
    'Result reports newly saved or refreshed candidates.'
);


playerSnapshotProductionAssert(
    (
        $result[
            'unchanged'
        ]
        ?? null
    )
    ===
    0,
    'Result reports no unchanged candidates on first capture.'
);


/*
 * ============================================================
 * C. DEADLINE LOOKUP
 * ============================================================
 */

playerSnapshotProductionSection(
    'C. Deadline Lookup'
);


playerSnapshotProductionAssert(
    $gameweekRepository->lookupCalls
    ===
    1,
    'Target gameweek is resolved exactly once.'
);


playerSnapshotProductionAssert(
    $gameweekRepository->lastTimestamp
    ===
    $generatedAt,
    'Generation timestamp drives deadline lookup.'
);


/*
 * ============================================================
 * D. LIVE PLAYER LOAD
 * ============================================================
 */

playerSnapshotProductionSection(
    'D. Live Player Load'
);


playerSnapshotProductionAssert(
    $playerRepository->getAllCalls
    ===
    1,
    'Current live player pool is loaded exactly once.'
);


/*
 * ============================================================
 * E. CANDIDATE BUILD DELEGATION
 * ============================================================
 */

playerSnapshotProductionSection(
    'E. Candidate Build Delegation'
);


playerSnapshotProductionAssert(
    $builder->buildCalls
    ===
    1,
    'Candidate builder is called exactly once.'
);


playerSnapshotProductionAssert(
    (
        $builder->lastBuild[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    8,
    'Resolved local gameweek ID is delegated to candidate builder.'
);


playerSnapshotProductionAssert(
    (
        $builder->lastBuild[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedAt,
    'Original generation timestamp is delegated unchanged.'
);


playerSnapshotProductionAssert(
    (
        $builder->lastBuild[
            'deadline_time'
        ]
        ?? null
    )
    ===
    '2026-09-18 18:30:00',
    'Authoritative stored deadline is delegated unchanged.'
);


playerSnapshotProductionAssert(
    (
        $builder->lastBuild[
            'players'
        ]
        ?? null
    )
    ===
    $players,
    'Live player rows are delegated unchanged to candidate builder.'
);


/*
 * ============================================================
 * F. CANDIDATE PERSISTENCE
 * ============================================================
 */

playerSnapshotProductionSection(
    'F. Candidate Persistence'
);


playerSnapshotProductionAssert(
    $candidateRepository->saveCalls
    ===
    2,
    'Every built candidate is offered to persistence.'
);


playerSnapshotProductionAssert(
    (
        $candidateRepository->saved[
            0
        ][
            'gameweek_id'
        ]
        ?? null
    )
    ===
    8,
    'Persistence receives resolved local gameweek ID.'
);


playerSnapshotProductionAssert(
    (
        $candidateRepository->saved[
            0
        ][
            'candidate'
        ]
        ?? null
    )
    ===
    $candidateOne,
    'First built candidate is delegated unchanged.'
);


playerSnapshotProductionAssert(
    (
        $candidateRepository->saved[
            1
        ][
            'candidate'
        ]
        ?? null
    )
    ===
    $candidateTwo,
    'Second built candidate is delegated unchanged.'
);


/*
 * ============================================================
 * G. UNCHANGED CANDIDATE ACCOUNTING
 * ============================================================
 */

playerSnapshotProductionSection(
    'G. Unchanged Candidate Accounting'
);


$unchangedGameweekRepository =
    new PlayerSnapshotProductionGameweekRepository(
        $targetGameweek
    );


$unchangedPlayerRepository =
    new PlayerSnapshotProductionPlayerRepository(
        $players
    );


$unchangedBuilder =
    new PlayerSnapshotProductionCandidateBuilder(
        [
            $candidateOne,
            $candidateTwo
        ]
    );


$unchangedCandidateRepository =
    new PlayerSnapshotProductionCandidateRepository(
        [
            false,
            true
        ]
    );


$unchangedCapture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $unchangedGameweekRepository,
        $unchangedPlayerRepository,
        $unchangedBuilder,
        $unchangedCandidateRepository
    );


$unchangedResult =
    $unchangedCapture->capture(
        $generatedAt
    );


playerSnapshotProductionAssert(
    (
        $unchangedResult[
            'saved'
        ]
        ?? null
    )
    ===
    1,
    'Repository true results count as saved candidates.'
);


playerSnapshotProductionAssert(
    (
        $unchangedResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    1,
    'Repository false results count as unchanged candidates.'
);


/*
 * ============================================================
 * H. NO FUTURE GAMEWEEK
 * ============================================================
 */

playerSnapshotProductionSection(
    'H. No Future Gameweek'
);


$noGameweekRepository =
    new PlayerSnapshotProductionGameweekRepository(
        null
    );


$noGameweekPlayerRepository =
    new PlayerSnapshotProductionPlayerRepository(
        $players
    );


$noGameweekBuilder =
    new PlayerSnapshotProductionCandidateBuilder(
        [
            $candidateOne
        ]
    );


$noGameweekCandidateRepository =
    new PlayerSnapshotProductionCandidateRepository();


$noGameweekCapture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $noGameweekRepository,
        $noGameweekPlayerRepository,
        $noGameweekBuilder,
        $noGameweekCandidateRepository
    );


$noGameweekResult =
    $noGameweekCapture->capture(
        $generatedAt
    );


playerSnapshotProductionAssert(
    (
        $noGameweekResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable',
    'No future deadline reports Unavailable status.'
);


playerSnapshotProductionAssert(
    $noGameweekPlayerRepository->getAllCalls
    ===
    0,
    'No future gameweek prevents live player loading.'
);


playerSnapshotProductionAssert(
    $noGameweekBuilder->buildCalls
    ===
    0,
    'No future gameweek prevents candidate construction.'
);


playerSnapshotProductionAssert(
    $noGameweekCandidateRepository->saveCalls
    ===
    0,
    'No future gameweek prevents persistence.'
);


/*
 * ============================================================
 * I. INVALID GENERATION TIMESTAMP
 * ============================================================
 */

playerSnapshotProductionSection(
    'I. Invalid Generation Timestamp'
);


$invalidGameweekRepository =
    new PlayerSnapshotProductionGameweekRepository(
        $targetGameweek
    );


$invalidPlayerRepository =
    new PlayerSnapshotProductionPlayerRepository(
        $players
    );


$invalidBuilder =
    new PlayerSnapshotProductionCandidateBuilder(
        []
    );


$invalidCandidateRepository =
    new PlayerSnapshotProductionCandidateRepository();


$invalidCapture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $invalidGameweekRepository,
        $invalidPlayerRepository,
        $invalidBuilder,
        $invalidCandidateRepository
    );


$invalidTimestampRejected =
    false;


try {

    $invalidCapture->capture(
        'not-a-date'
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidTimestampRejected =
        true;
}


playerSnapshotProductionAssert(
    $invalidTimestampRejected,
    'Invalid generation timestamp is rejected.'
);


playerSnapshotProductionAssert(
    $invalidGameweekRepository->lookupCalls
    ===
    0,
    'Invalid timestamp is rejected before deadline lookup.'
);


/*
 * ============================================================
 * J. INVALID RESOLVED GAMEWEEK
 * ============================================================
 */

playerSnapshotProductionSection(
    'J. Invalid Resolved Gameweek'
);


$badGameweekRepository =
    new PlayerSnapshotProductionGameweekRepository(
        [
            'id' =>
                0,

            'fpl_gameweek_id' =>
                4,

            'deadline_time' =>
                '2026-09-18 18:30:00'
        ]
    );


$badPlayerRepository =
    new PlayerSnapshotProductionPlayerRepository(
        $players
    );


$badBuilder =
    new PlayerSnapshotProductionCandidateBuilder(
        []
    );


$badCandidateRepository =
    new PlayerSnapshotProductionCandidateRepository();


$badCapture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $badGameweekRepository,
        $badPlayerRepository,
        $badBuilder,
        $badCandidateRepository
    );


$invalidGameweekRejected =
    false;


try {

    $badCapture->capture(
        $generatedAt
    );

} catch (
    RuntimeException $exception
) {

    $invalidGameweekRejected =
        true;
}


playerSnapshotProductionAssert(
    $invalidGameweekRejected,
    'Resolved gameweek requires positive local identity.'
);


playerSnapshotProductionAssert(
    $badPlayerRepository->getAllCalls
    ===
    0,
    'Invalid resolved gameweek is rejected before player loading.'
);


/*
 * ============================================================
 * K. INVALID FPL GAMEWEEK ID
 * ============================================================
 */

playerSnapshotProductionSection(
    'K. Invalid FPL Gameweek Identity'
);


$badFplGameweekRepository =
    new PlayerSnapshotProductionGameweekRepository(
        [
            'id' =>
                8,

            'fpl_gameweek_id' =>
                0,

            'deadline_time' =>
                '2026-09-18 18:30:00'
        ]
    );


$badFplPlayerRepository =
    new PlayerSnapshotProductionPlayerRepository(
        $players
    );


$badFplBuilder =
    new PlayerSnapshotProductionCandidateBuilder(
        []
    );


$badFplCandidateRepository =
    new PlayerSnapshotProductionCandidateRepository();


$badFplCapture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $badFplGameweekRepository,
        $badFplPlayerRepository,
        $badFplBuilder,
        $badFplCandidateRepository
    );


$invalidFplGameweekRejected =
    false;


try {

    $badFplCapture->capture(
        $generatedAt
    );

} catch (
    RuntimeException $exception
) {

    $invalidFplGameweekRejected =
        true;
}


playerSnapshotProductionAssert(
    $invalidFplGameweekRejected,
    'Resolved gameweek requires positive FPL identity.'
);


/*
 * ============================================================
 * L. INVALID DEADLINE
 * ============================================================
 */

playerSnapshotProductionSection(
    'L. Invalid Deadline'
);


$badDeadlineRepository =
    new PlayerSnapshotProductionGameweekRepository(
        [
            'id' =>
                8,

            'fpl_gameweek_id' =>
                4,

            'deadline_time' =>
                'not-a-date'
        ]
    );


$badDeadlinePlayerRepository =
    new PlayerSnapshotProductionPlayerRepository(
        $players
    );


$badDeadlineBuilder =
    new PlayerSnapshotProductionCandidateBuilder(
        []
    );


$badDeadlineCandidateRepository =
    new PlayerSnapshotProductionCandidateRepository();


$badDeadlineCapture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $badDeadlineRepository,
        $badDeadlinePlayerRepository,
        $badDeadlineBuilder,
        $badDeadlineCandidateRepository
    );


$invalidDeadlineRejected =
    false;


try {

    $badDeadlineCapture->capture(
        $generatedAt
    );

} catch (
    RuntimeException $exception
) {

    $invalidDeadlineRejected =
        true;
}


playerSnapshotProductionAssert(
    $invalidDeadlineRejected,
    'Resolved gameweek requires valid deadline.'
);


playerSnapshotProductionAssert(
    $badDeadlinePlayerRepository->getAllCalls
    ===
    0,
    'Invalid deadline is rejected before player loading.'
);


/*
 * ============================================================
 * M. EMPTY LIVE PLAYER POOL
 * ============================================================
 */

playerSnapshotProductionSection(
    'M. Empty Live Player Pool'
);


$emptyGameweekRepository =
    new PlayerSnapshotProductionGameweekRepository(
        $targetGameweek
    );


$emptyPlayerRepository =
    new PlayerSnapshotProductionPlayerRepository(
        []
    );


$emptyBuilder =
    new PlayerSnapshotProductionCandidateBuilder(
        []
    );


$emptyCandidateRepository =
    new PlayerSnapshotProductionCandidateRepository();


$emptyCapture =
    new PlayerGameweekSnapshotCandidateProductionCapture(
        $emptyGameweekRepository,
        $emptyPlayerRepository,
        $emptyBuilder,
        $emptyCandidateRepository
    );


$emptyResult =
    $emptyCapture->capture(
        $generatedAt
    );


playerSnapshotProductionAssert(
    (
        $emptyResult[
            'status'
        ]
        ?? null
    )
    ===
    'Captured',
    'Empty live player pool remains a valid capture cycle.'
);


playerSnapshotProductionAssert(
    (
        $emptyResult[
            'players_considered'
        ]
        ?? null
    )
    ===
    0,
    'Empty live player pool reports zero players considered.'
);


playerSnapshotProductionAssert(
    (
        $emptyResult[
            'candidates_built'
        ]
        ?? null
    )
    ===
    0,
    'Empty live player pool reports zero candidates built.'
);


playerSnapshotProductionAssert(
    $emptyCandidateRepository->saveCalls
    ===
    0,
    'Empty candidate set performs no persistence writes.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Production Capture Test Summary<br>";
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