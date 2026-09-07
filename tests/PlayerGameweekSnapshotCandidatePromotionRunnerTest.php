<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Promotion Runner Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerSnapshotRunnerAssert(
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


function playerSnapshotRunnerSection(
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
 * TEST DOUBLE — CANDIDATE REPOSITORY
 * ============================================================
 */

class PlayerSnapshotRunnerCandidateRepository
{
    private array $readyCandidates;


    public array $calls =
        [];


    public function __construct(
        array $readyCandidates
    ) {

        $this->readyCandidates =
            $readyCandidates;
    }


    public function getReadyForPromotion(
        string $timestamp
    ): array {

        $this->calls[] =
            $timestamp;


        return
            $this->readyCandidates;
    }
}


/*
 * ============================================================
 * TEST DOUBLE — PROMOTION SERVICE
 * ============================================================
 */

class PlayerSnapshotRunnerPromotionService
{
    private array $results;


    public array $calls =
        [];


    public function __construct(
        array $results = []
    ) {

        $this->results =
            $results;
    }


    public function promote(
        int $playerId,
        int $gameweekId
    ): bool {

        $this->calls[] = [

            'player_id' =>
                $playerId,

            'gameweek_id' =>
                $gameweekId
        ];


        $key =
            $playerId
            . '|'
            . $gameweekId;


        return
            $this->results[
                $key
            ]
            ?? false;
    }
}


/*
 * ============================================================
 * CONTROLLED CANDIDATES
 * ============================================================
 */

$candidateOne = [

    'id' =>
        1001,

    'gameweek_id' =>
        8,

    'player_id' =>
        101,

    'fpl_player_id' =>
        501,

    'team_id' =>
        11,

    'generated_at' =>
        '2026-09-18 17:30:00',

    'deadline_time' =>
        '2026-09-18 18:30:00',

    'player_state' => [

        'player_id' =>
            101,

        'fpl_player_id' =>
            501,

        'team_id' =>
            11
    ]
];


$candidateTwo = [

    'id' =>
        1002,

    'gameweek_id' =>
        8,

    'player_id' =>
        102,

    'fpl_player_id' =>
        502,

    'team_id' =>
        12,

    'generated_at' =>
        '2026-09-18 17:35:00',

    'deadline_time' =>
        '2026-09-18 18:30:00',

    'player_state' => [

        'player_id' =>
            102,

        'fpl_player_id' =>
            502,

        'team_id' =>
            12
    ]
];


$candidateThree = [

    'id' =>
        1003,

    'gameweek_id' =>
        9,

    'player_id' =>
        103,

    'fpl_player_id' =>
        503,

    'team_id' =>
        13,

    'generated_at' =>
        '2026-09-25 17:30:00',

    'deadline_time' =>
        '2026-09-25 18:30:00',

    'player_state' => [

        'player_id' =>
            103,

        'fpl_player_id' =>
            503,

        'team_id' =>
            13
    ]
];


/*
 * ============================================================
 * A. CLASS CONTRACT
 * ============================================================
 */

playerSnapshotRunnerSection(
    'A. Class Contract'
);


$classExists =
    class_exists(
        'PlayerGameweekSnapshotCandidatePromotionRunner'
    );


playerSnapshotRunnerAssert(
    $classExists,
    'PlayerGameweekSnapshotCandidatePromotionRunner exists.'
);


if (!$classExists) {

    echo "<br>";
    echo "============================================<br>";
    echo "Player Gameweek Snapshot Candidate Promotion Runner Test Summary<br>";
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
 * B. NO READY CANDIDATES
 * ============================================================
 */

playerSnapshotRunnerSection(
    'B. No Ready Candidates'
);


$emptyRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        []
    );


$emptyPromotionService =
    new PlayerSnapshotRunnerPromotionService();


$emptyRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $emptyRepository,
        $emptyPromotionService
    );


$emptyResult =
    $emptyRunner->run(
        '2026-09-18 18:30:00'
    );


playerSnapshotRunnerAssert(
    (
        $emptyResult[
            'status'
        ]
        ?? null
    )
    ===
    'Complete',
    'Empty promotion run completes successfully.'
);


playerSnapshotRunnerAssert(
    (
        $emptyResult[
            'ready'
        ]
        ?? null
    )
    ===
    0,
    'Empty promotion run reports zero ready candidates.'
);


playerSnapshotRunnerAssert(
    (
        $emptyResult[
            'promoted'
        ]
        ?? null
    )
    ===
    0,
    'Empty promotion run reports zero promoted candidates.'
);


playerSnapshotRunnerAssert(
    (
        $emptyResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    0,
    'Empty promotion run reports zero unchanged candidates.'
);


playerSnapshotRunnerAssert(
    $emptyPromotionService->calls
    ===
    [],
    'Promotion service is not called when nothing is ready.'
);


playerSnapshotRunnerAssert(
    $emptyRepository->calls
    ===
    [
        '2026-09-18 18:30:00'
    ],
    'Repository is queried exactly once using run timestamp.'
);


/*
 * ============================================================
 * C. SINGLE READY CANDIDATE
 * ============================================================
 */

playerSnapshotRunnerSection(
    'C. Single Ready Candidate'
);


$singleRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        [
            $candidateOne
        ]
    );


$singlePromotionService =
    new PlayerSnapshotRunnerPromotionService(
        [
            '101|8' =>
                true
        ]
    );


$singleRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $singleRepository,
        $singlePromotionService
    );


$singleResult =
    $singleRunner->run(
        '2026-09-18 18:30:00'
    );


playerSnapshotRunnerAssert(
    (
        $singleResult[
            'ready'
        ]
        ?? null
    )
    ===
    1,
    'Single ready candidate is counted.'
);


playerSnapshotRunnerAssert(
    (
        $singleResult[
            'promoted'
        ]
        ?? null
    )
    ===
    1,
    'Successful single promotion is counted.'
);


playerSnapshotRunnerAssert(
    (
        $singleResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    0,
    'Successful promotion is not counted as unchanged.'
);


playerSnapshotRunnerAssert(
    $singlePromotionService->calls
    ===
    [
        [
            'player_id' =>
                101,

            'gameweek_id' =>
                8
        ]
    ],
    'Runner passes exact player/gameweek identity to promotion service.'
);


/*
 * ============================================================
 * D. MULTIPLE READY CANDIDATES
 * ============================================================
 */

playerSnapshotRunnerSection(
    'D. Multiple Ready Candidates'
);


$multipleRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        [
            $candidateOne,
            $candidateTwo,
            $candidateThree
        ]
    );


$multiplePromotionService =
    new PlayerSnapshotRunnerPromotionService(
        [
            '101|8' =>
                true,

            '102|8' =>
                true,

            '103|9' =>
                true
        ]
    );


$multipleRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $multipleRepository,
        $multiplePromotionService
    );


$multipleResult =
    $multipleRunner->run(
        '2026-09-25 18:30:00'
    );


playerSnapshotRunnerAssert(
    (
        $multipleResult[
            'ready'
        ]
        ?? null
    )
    ===
    3,
    'Multiple ready candidates are counted.'
);


playerSnapshotRunnerAssert(
    (
        $multipleResult[
            'promoted'
        ]
        ?? null
    )
    ===
    3,
    'All successful promotions are counted.'
);


playerSnapshotRunnerAssert(
    (
        $multipleResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    0,
    'No unchanged candidates are reported when all promotions succeed.'
);


playerSnapshotRunnerAssert(
    $multiplePromotionService->calls
    ===
    [
        [
            'player_id' =>
                101,

            'gameweek_id' =>
                8
        ],

        [
            'player_id' =>
                102,

            'gameweek_id' =>
                8
        ],

        [
            'player_id' =>
                103,

            'gameweek_id' =>
                9
        ]
    ],
    'Runner preserves repository processing order.'
);


/*
 * ============================================================
 * E. UNCHANGED PROMOTION
 * ============================================================
 */

playerSnapshotRunnerSection(
    'E. Unchanged Promotion'
);


$unchangedRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        [
            $candidateOne,
            $candidateTwo
        ]
    );


$unchangedPromotionService =
    new PlayerSnapshotRunnerPromotionService(
        [
            '101|8' =>
                true,

            '102|8' =>
                false
        ]
    );


$unchangedRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $unchangedRepository,
        $unchangedPromotionService
    );


$unchangedResult =
    $unchangedRunner->run(
        '2026-09-18 18:30:00'
    );


playerSnapshotRunnerAssert(
    (
        $unchangedResult[
            'ready'
        ]
        ?? null
    )
    ===
    2,
    'Ready count includes candidates whose snapshot already exists.'
);


playerSnapshotRunnerAssert(
    (
        $unchangedResult[
            'promoted'
        ]
        ?? null
    )
    ===
    1,
    'Only successful insertion is counted as promoted.'
);


playerSnapshotRunnerAssert(
    (
        $unchangedResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    1,
    'False promotion result is counted as unchanged.'
);


playerSnapshotRunnerAssert(
    count(
        $unchangedPromotionService->calls
    )
    ===
    2,
    'Runner continues after unchanged promotion.'
);


/*
 * ============================================================
 * F. TIMESTAMP NORMALISATION
 * ============================================================
 */

playerSnapshotRunnerSection(
    'F. Timestamp Normalisation'
);


$normalisedRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        []
    );


$normalisedService =
    new PlayerSnapshotRunnerPromotionService();


$normalisedRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $normalisedRepository,
        $normalisedService
    );


$normalisedRunner->run(
    '2026-09-18T18:30:00'
);


playerSnapshotRunnerAssert(
    $normalisedRepository->calls
    ===
    [
        '2026-09-18 18:30:00'
    ],
    'Promotion timestamp is normalised before repository lookup.'
);


/*
 * ============================================================
 * G. INVALID TIMESTAMP
 * ============================================================
 */

playerSnapshotRunnerSection(
    'G. Invalid Timestamp'
);


$invalidRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        []
    );


$invalidService =
    new PlayerSnapshotRunnerPromotionService();


$invalidRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $invalidRepository,
        $invalidService
    );


$invalidRejected =
    false;


try {

    $invalidRunner->run(
        'not-a-date'
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidRejected =
        true;
}


playerSnapshotRunnerAssert(
    $invalidRejected,
    'Invalid promotion timestamp is rejected.'
);


playerSnapshotRunnerAssert(
    $invalidRepository->calls
    ===
    [],
    'Invalid timestamp is rejected before repository lookup.'
);


playerSnapshotRunnerAssert(
    $invalidService->calls
    ===
    [],
    'Invalid timestamp is rejected before promotion service access.'
);


/*
 * ============================================================
 * H. INVALID CANDIDATE TYPE
 * ============================================================
 */

playerSnapshotRunnerSection(
    'H. Invalid Candidate Type'
);


$invalidTypeRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        [
            'invalid'
        ]
    );


$invalidTypeService =
    new PlayerSnapshotRunnerPromotionService();


$invalidTypeRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $invalidTypeRepository,
        $invalidTypeService
    );


$invalidTypeRejected =
    false;


try {

    $invalidTypeRunner->run(
        '2026-09-18 18:30:00'
    );

} catch (
    RuntimeException $exception
) {

    $invalidTypeRejected =
        true;
}


playerSnapshotRunnerAssert(
    $invalidTypeRejected,
    'Non-array promotion-ready candidate is rejected.'
);


playerSnapshotRunnerAssert(
    $invalidTypeService->calls
    ===
    [],
    'Invalid candidate type is rejected before promotion.'
);


/*
 * ============================================================
 * I. INVALID PLAYER IDENTITY
 * ============================================================
 */

playerSnapshotRunnerSection(
    'I. Invalid Player Identity'
);


$invalidPlayer =
    $candidateOne;


$invalidPlayer[
    'player_id'
] =
    0;


$invalidPlayerRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        [
            $invalidPlayer
        ]
    );


$invalidPlayerService =
    new PlayerSnapshotRunnerPromotionService();


$invalidPlayerRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $invalidPlayerRepository,
        $invalidPlayerService
    );


$invalidPlayerRejected =
    false;


try {

    $invalidPlayerRunner->run(
        '2026-09-18 18:30:00'
    );

} catch (
    RuntimeException $exception
) {

    $invalidPlayerRejected =
        true;
}


playerSnapshotRunnerAssert(
    $invalidPlayerRejected,
    'Promotion-ready candidate requires positive player ID.'
);


playerSnapshotRunnerAssert(
    $invalidPlayerService->calls
    ===
    [],
    'Invalid player identity is rejected before promotion.'
);


/*
 * ============================================================
 * J. INVALID GAMEWEEK IDENTITY
 * ============================================================
 */

playerSnapshotRunnerSection(
    'J. Invalid Gameweek Identity'
);


$invalidGameweek =
    $candidateOne;


$invalidGameweek[
    'gameweek_id'
] =
    0;


$invalidGameweekRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        [
            $invalidGameweek
        ]
    );


$invalidGameweekService =
    new PlayerSnapshotRunnerPromotionService();


$invalidGameweekRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $invalidGameweekRepository,
        $invalidGameweekService
    );


$invalidGameweekRejected =
    false;


try {

    $invalidGameweekRunner->run(
        '2026-09-18 18:30:00'
    );

} catch (
    RuntimeException $exception
) {

    $invalidGameweekRejected =
        true;
}


playerSnapshotRunnerAssert(
    $invalidGameweekRejected,
    'Promotion-ready candidate requires positive gameweek ID.'
);


playerSnapshotRunnerAssert(
    $invalidGameweekService->calls
    ===
    [],
    'Invalid gameweek identity is rejected before promotion.'
);


/*
 * ============================================================
 * K. IDENTITY-ONLY BOUNDARY
 * ============================================================
 */

playerSnapshotRunnerSection(
    'K. Identity-Only Promotion Boundary'
);


$identityRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        [
            $candidateOne
        ]
    );


$identityService =
    new PlayerSnapshotRunnerPromotionService(
        [
            '101|8' =>
                true
        ]
    );


$identityRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $identityRepository,
        $identityService
    );


$identityRunner->run(
    '2026-09-18 18:30:00'
);


playerSnapshotRunnerAssert(
    $identityService->calls
    ===
    [
        [
            'player_id' =>
                101,

            'gameweek_id' =>
                8
        ]
    ],
    'Runner passes identity only and does not interpret player-state evidence.'
);


/*
 * ============================================================
 * L. REPEATED RUN IS HARMLESS
 * ============================================================
 */

playerSnapshotRunnerSection(
    'L. Repeated Run Is Harmless'
);


$repeatRepository =
    new PlayerSnapshotRunnerCandidateRepository(
        [
            $candidateOne
        ]
    );


$repeatService =
    new PlayerSnapshotRunnerPromotionService(
        [
            '101|8' =>
                false
        ]
    );


$repeatRunner =
    new PlayerGameweekSnapshotCandidatePromotionRunner(
        $repeatRepository,
        $repeatService
    );


$repeatResult =
    $repeatRunner->run(
        '2026-09-18 18:31:00'
    );


playerSnapshotRunnerAssert(
    (
        $repeatResult[
            'ready'
        ]
        ?? null
    )
    ===
    1,
    'Previously ready candidate may safely remain discoverable.'
);


playerSnapshotRunnerAssert(
    (
        $repeatResult[
            'promoted'
        ]
        ?? null
    )
    ===
    0,
    'Repeated promotion does not report another insertion.'
);


playerSnapshotRunnerAssert(
    (
        $repeatResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    1,
    'Repeated promotion is safely reported as unchanged.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Promotion Runner Test Summary<br>";
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