<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Promotion Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerSnapshotPromotionAssert(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


function playerSnapshotPromotionSection(
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

class PlayerSnapshotPromotionCandidateRepository
{
    public int $getCalls =
        0;


    public ?int $lastPlayerId =
        null;


    public ?int $lastGameweekId =
        null;


    public ?array $candidate;


    public function __construct(
        ?array $candidate
    ) {

        $this->candidate =
            $candidate;
    }


    public function getByPlayerAndGameweek(
        int $playerId,
        int $gameweekId
    ): ?array {

        $this->getCalls++;


        $this->lastPlayerId =
            $playerId;


        $this->lastGameweekId =
            $gameweekId;


        return
            $this->candidate;
    }
}


/*
 * ============================================================
 * TEST DOUBLE — IMMUTABLE SNAPSHOT REPOSITORY
 * ============================================================
 */

class PlayerSnapshotPromotionSnapshotRepository
{
    public int $getCalls =
        0;


    public int $insertCalls =
        0;


    public ?int $lastGetPlayerId =
        null;


    public ?int $lastGetGameweekId =
        null;


    public ?array $existingSnapshot;


    public ?array $insertedSnapshot =
        null;


    public bool $insertResult;


    public function __construct(
        ?array $existingSnapshot = null,
        bool $insertResult = true
    ) {

        $this->existingSnapshot =
            $existingSnapshot;


        $this->insertResult =
            $insertResult;
    }


    public function getByPlayerAndGameweek(
        int $playerId,
        int $gameweekId
    ): ?array {

        $this->getCalls++;


        $this->lastGetPlayerId =
            $playerId;


        $this->lastGetGameweekId =
            $gameweekId;


        return
            $this->existingSnapshot;
    }


    public function insertIfAbsent(
        array $snapshot
    ): bool {

        $this->insertCalls++;


        $this->insertedSnapshot =
            $snapshot;


        return
            $this->insertResult;
    }
}


/*
 * ============================================================
 * CONTROLLED CANDIDATE EVIDENCE
 * ============================================================
 */

$gameweekId =
    8;


$playerId =
    101;


$generatedAt =
    '2026-09-18 17:30:00';


$deadlineTime =
    '2026-09-18 18:30:00';


$playerState = [

    'player_id' =>
        $playerId,

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
        'Pre-deadline player news',

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


$candidate = [

    'id' =>
        9001,

    'gameweek_id' =>
        $gameweekId,

    'player_id' =>
        $playerId,

    'fpl_player_id' =>
        501,

    'team_id' =>
        11,

    'generated_at' =>
        $generatedAt,

    'deadline_time' =>
        $deadlineTime,

    'player_state' =>
        $playerState,

    'created_at' =>
        '2026-09-18 17:30:01',

    'updated_at' =>
        '2026-09-18 17:30:01'
];


$expectedSnapshot =
    $playerState;


$expectedSnapshot[
    'gameweek_id'
] =
    $gameweekId;


/*
 * ============================================================
 * A. CLASS CONTRACT
 * ============================================================
 */

playerSnapshotPromotionSection(
    'A. Class Contract'
);


$classExists =
    class_exists(
        'PlayerGameweekSnapshotCandidatePromotionService'
    );


playerSnapshotPromotionAssert(
    $classExists,
    'PlayerGameweekSnapshotCandidatePromotionService exists.'
);


if (
    !$classExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Player Gameweek Snapshot Candidate Promotion Service Test Summary<br>";
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
 * B. PROMOTE EXISTING CANDIDATE
 * ============================================================
 */

playerSnapshotPromotionSection(
    'B. Promote Existing Candidate'
);


$candidateRepository =
    new PlayerSnapshotPromotionCandidateRepository(
        $candidate
    );


$snapshotRepository =
    new PlayerSnapshotPromotionSnapshotRepository();


$service =
    new PlayerGameweekSnapshotCandidatePromotionService(
        $candidateRepository,
        $snapshotRepository
    );


$result =
    $service->promote(
        $playerId,
        $gameweekId
    );


playerSnapshotPromotionAssert(
    $result === true,
    'Existing player candidate is promoted.'
);


playerSnapshotPromotionAssert(
    $snapshotRepository->insertCalls
    ===
    1,
    'Immutable snapshot repository is called exactly once.'
);


/*
 * ============================================================
 * C. IMMUTABLE SNAPSHOT CHECK OCCURS FIRST
 * ============================================================
 */

playerSnapshotPromotionSection(
    'C. Immutable Snapshot Check'
);


playerSnapshotPromotionAssert(
    $snapshotRepository->getCalls
    ===
    1,
    'Existing immutable snapshot is checked exactly once.'
);


playerSnapshotPromotionAssert(
    $snapshotRepository->lastGetPlayerId
    ===
    $playerId,
    'Immutable lookup uses supplied player identity.'
);


playerSnapshotPromotionAssert(
    $snapshotRepository->lastGetGameweekId
    ===
    $gameweekId,
    'Immutable lookup uses supplied gameweek identity.'
);


/*
 * ============================================================
 * D. CANDIDATE LOOKUP
 * ============================================================
 */

playerSnapshotPromotionSection(
    'D. Candidate Lookup'
);


playerSnapshotPromotionAssert(
    $candidateRepository->getCalls
    ===
    1,
    'Candidate is retrieved exactly once.'
);


playerSnapshotPromotionAssert(
    $candidateRepository->lastPlayerId
    ===
    $playerId,
    'Candidate lookup uses supplied player identity.'
);


playerSnapshotPromotionAssert(
    $candidateRepository->lastGameweekId
    ===
    $gameweekId,
    'Candidate lookup uses supplied gameweek identity.'
);


/*
 * ============================================================
 * E. SNAPSHOT IDENTITY
 * ============================================================
 */

playerSnapshotPromotionSection(
    'E. Snapshot Identity'
);


$inserted =
    $snapshotRepository->insertedSnapshot;


playerSnapshotPromotionAssert(
    (
        $inserted[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekId,
    'Promoted snapshot preserves local gameweek ID.'
);


playerSnapshotPromotionAssert(
    (
        $inserted[
            'player_id'
        ]
        ?? null
    )
    ===
    $playerId,
    'Promoted snapshot preserves local player ID.'
);


playerSnapshotPromotionAssert(
    (
        $inserted[
            'fpl_player_id'
        ]
        ?? null
    )
    ===
    501,
    'Promoted snapshot preserves FPL player ID.'
);


playerSnapshotPromotionAssert(
    (
        $inserted[
            'team_id'
        ]
        ?? null
    )
    ===
    11,
    'Promoted snapshot preserves team ID.'
);


/*
 * ============================================================
 * F. PRE-DEADLINE PLAYER STATE IS PRESERVED
 * ============================================================
 */

playerSnapshotPromotionSection(
    'F. Pre-Deadline Player State'
);


playerSnapshotPromotionAssert(
    $inserted
    ===
    $expectedSnapshot,
    'Candidate player state is promoted without recalculation.'
);


playerSnapshotPromotionAssert(
    array_key_exists(
        'selected',
        $inserted
    )
    &&
    $inserted[
        'selected'
    ]
    ===
    null,
    'Unavailable raw selected count remains null after promotion.'
);


playerSnapshotPromotionAssert(
    (
        $inserted[
            'selected_by_percent'
        ]
        ?? null
    )
    ===
    12.5,
    'Pre-deadline ownership percentage is preserved.'
);


playerSnapshotPromotionAssert(
    (
        $inserted[
            'news'
        ]
        ?? null
    )
    ===
    'Pre-deadline player news',
    'Pre-deadline player news is preserved.'
);


playerSnapshotPromotionAssert(
    (
        $inserted[
            'minutes'
        ]
        ?? null
    )
    ===
    180,
    'Pre-deadline cumulative minutes are preserved.'
);


playerSnapshotPromotionAssert(
    (
        $inserted[
            'expected_goal_involvements'
        ]
        ?? null
    )
    ===
    1.37,
    'Pre-deadline cumulative expected goal involvements are preserved.'
);


/*
 * ============================================================
 * G. CANDIDATE METADATA IS NOT WRITTEN TO SNAPSHOT
 * ============================================================
 */

playerSnapshotPromotionSection(
    'G. Candidate Metadata Boundary'
);


playerSnapshotPromotionAssert(
    !array_key_exists(
        'generated_at',
        $inserted
    ),
    'Candidate generation timestamp is not invented as snapshot data.'
);


playerSnapshotPromotionAssert(
    !array_key_exists(
        'deadline_time',
        $inserted
    ),
    'Candidate deadline is not written into unsupported snapshot columns.'
);


playerSnapshotPromotionAssert(
    !array_key_exists(
        'created_at',
        $inserted
    ),
    'Candidate staging created timestamp is not copied into snapshot evidence.'
);


playerSnapshotPromotionAssert(
    !array_key_exists(
        'updated_at',
        $inserted
    ),
    'Candidate staging updated timestamp is not copied into snapshot evidence.'
);


/*
 * ============================================================
 * H. NO FIXTURE-HISTORY DEPENDENCY
 * ============================================================
 */

playerSnapshotPromotionSection(
    'H. Blank-Gameweek Compatibility'
);


playerSnapshotPromotionAssert(
    !array_key_exists(
        'fixture_id',
        $inserted
    ),
    'Promotion does not require fixture identity.'
);


playerSnapshotPromotionAssert(
    !array_key_exists(
        'fixture_history',
        $inserted
    ),
    'Promotion does not require fixture-history evidence.'
);


/*
 * ============================================================
 * I. EXISTING SNAPSHOT CANNOT BE REWRITTEN
 * ============================================================
 */

playerSnapshotPromotionSection(
    'I. Existing Snapshot Immutability'
);


$existingSnapshot = [

    'gameweek_id' =>
        $gameweekId,

    'player_id' =>
        $playerId,

    'price' =>
        '7.4',

    'news' =>
        'Original immutable evidence'
];


$existingCandidateRepository =
    new PlayerSnapshotPromotionCandidateRepository(
        $candidate
    );


$existingSnapshotRepository =
    new PlayerSnapshotPromotionSnapshotRepository(
        $existingSnapshot
    );


$existingService =
    new PlayerGameweekSnapshotCandidatePromotionService(
        $existingCandidateRepository,
        $existingSnapshotRepository
    );


$existingResult =
    $existingService->promote(
        $playerId,
        $gameweekId
    );


playerSnapshotPromotionAssert(
    $existingResult === false,
    'Existing immutable snapshot prevents promotion.'
);


playerSnapshotPromotionAssert(
    $existingCandidateRepository->getCalls
    ===
    0,
    'Existing immutable snapshot prevents unnecessary candidate lookup.'
);


playerSnapshotPromotionAssert(
    $existingSnapshotRepository->insertCalls
    ===
    0,
    'Existing immutable snapshot is never overwritten.'
);


/*
 * ============================================================
 * J. MISSING CANDIDATE
 * ============================================================
 */

playerSnapshotPromotionSection(
    'J. Missing Candidate'
);


$missingCandidateRepository =
    new PlayerSnapshotPromotionCandidateRepository(
        null
    );


$missingSnapshotRepository =
    new PlayerSnapshotPromotionSnapshotRepository();


$missingService =
    new PlayerGameweekSnapshotCandidatePromotionService(
        $missingCandidateRepository,
        $missingSnapshotRepository
    );


$missingResult =
    $missingService->promote(
        $playerId,
        $gameweekId
    );


playerSnapshotPromotionAssert(
    $missingResult === false,
    'Missing candidate is not promoted.'
);


playerSnapshotPromotionAssert(
    $missingSnapshotRepository->insertCalls
    ===
    0,
    'Missing candidate creates no immutable snapshot.'
);


/*
 * ============================================================
 * K. FINAL INSERT SAFEGUARD
 * ============================================================
 */

playerSnapshotPromotionSection(
    'K. Final Insert Safeguard'
);


$raceCandidateRepository =
    new PlayerSnapshotPromotionCandidateRepository(
        $candidate
    );


$raceSnapshotRepository =
    new PlayerSnapshotPromotionSnapshotRepository(
        null,
        false
    );


$raceService =
    new PlayerGameweekSnapshotCandidatePromotionService(
        $raceCandidateRepository,
        $raceSnapshotRepository
    );


$raceResult =
    $raceService->promote(
        $playerId,
        $gameweekId
    );


playerSnapshotPromotionAssert(
    $raceResult === false,
    'Immutable repository false result is preserved.'
);


playerSnapshotPromotionAssert(
    $raceSnapshotRepository->insertCalls
    ===
    1,
    'insertIfAbsent remains final immutability safeguard.'
);


/*
 * ============================================================
 * L. INVALID PLAYER ID
 * ============================================================
 */

playerSnapshotPromotionSection(
    'L. Invalid Player Identity'
);


$invalidPlayerCandidateRepository =
    new PlayerSnapshotPromotionCandidateRepository(
        $candidate
    );


$invalidPlayerSnapshotRepository =
    new PlayerSnapshotPromotionSnapshotRepository();


$invalidPlayerService =
    new PlayerGameweekSnapshotCandidatePromotionService(
        $invalidPlayerCandidateRepository,
        $invalidPlayerSnapshotRepository
    );


$invalidPlayerRejected =
    false;


try {

    $invalidPlayerService->promote(
        0,
        $gameweekId
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidPlayerRejected =
        true;
}


playerSnapshotPromotionAssert(
    $invalidPlayerRejected,
    'Non-positive player ID is rejected.'
);


playerSnapshotPromotionAssert(
    $invalidPlayerSnapshotRepository->getCalls
    ===
    0,
    'Invalid player ID is rejected before repository access.'
);


/*
 * ============================================================
 * M. INVALID GAMEWEEK ID
 * ============================================================
 */

playerSnapshotPromotionSection(
    'M. Invalid Gameweek Identity'
);


$invalidGameweekCandidateRepository =
    new PlayerSnapshotPromotionCandidateRepository(
        $candidate
    );


$invalidGameweekSnapshotRepository =
    new PlayerSnapshotPromotionSnapshotRepository();


$invalidGameweekService =
    new PlayerGameweekSnapshotCandidatePromotionService(
        $invalidGameweekCandidateRepository,
        $invalidGameweekSnapshotRepository
    );


$invalidGameweekRejected =
    false;


try {

    $invalidGameweekService->promote(
        $playerId,
        0
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekRejected =
        true;
}


playerSnapshotPromotionAssert(
    $invalidGameweekRejected,
    'Non-positive gameweek ID is rejected.'
);


playerSnapshotPromotionAssert(
    $invalidGameweekSnapshotRepository->getCalls
    ===
    0,
    'Invalid gameweek ID is rejected before repository access.'
);


/*
 * ============================================================
 * N. CANDIDATE IDENTITY MUST MATCH REQUEST
 * ============================================================
 */

playerSnapshotPromotionSection(
    'N. Candidate Identity Protection'
);


$mismatchedCandidate =
    $candidate;


$mismatchedCandidate[
    'player_id'
] =
    999;


$mismatchedCandidate[
    'player_state'
][
    'player_id'
] =
    999;


$mismatchCandidateRepository =
    new PlayerSnapshotPromotionCandidateRepository(
        $mismatchedCandidate
    );


$mismatchSnapshotRepository =
    new PlayerSnapshotPromotionSnapshotRepository();


$mismatchService =
    new PlayerGameweekSnapshotCandidatePromotionService(
        $mismatchCandidateRepository,
        $mismatchSnapshotRepository
    );


$mismatchRejected =
    false;


try {

    $mismatchService->promote(
        $playerId,
        $gameweekId
    );

} catch (
    RuntimeException $exception
) {

    $mismatchRejected =
        true;
}


playerSnapshotPromotionAssert(
    $mismatchRejected,
    'Candidate player identity must match requested player.'
);


playerSnapshotPromotionAssert(
    $mismatchSnapshotRepository->insertCalls
    ===
    0,
    'Mismatched candidate identity cannot create historical evidence.'
);


/*
 * ============================================================
 * O. CANDIDATE GAMEWEEK MUST MATCH REQUEST
 * ============================================================
 */

playerSnapshotPromotionSection(
    'O. Candidate Gameweek Protection'
);


$mismatchedGameweekCandidate =
    $candidate;


$mismatchedGameweekCandidate[
    'gameweek_id'
] =
    999;


$mismatchGameweekCandidateRepository =
    new PlayerSnapshotPromotionCandidateRepository(
        $mismatchedGameweekCandidate
    );


$mismatchGameweekSnapshotRepository =
    new PlayerSnapshotPromotionSnapshotRepository();


$mismatchGameweekService =
    new PlayerGameweekSnapshotCandidatePromotionService(
        $mismatchGameweekCandidateRepository,
        $mismatchGameweekSnapshotRepository
    );


$mismatchGameweekRejected =
    false;


try {

    $mismatchGameweekService->promote(
        $playerId,
        $gameweekId
    );

} catch (
    RuntimeException $exception
) {

    $mismatchGameweekRejected =
        true;
}


playerSnapshotPromotionAssert(
    $mismatchGameweekRejected,
    'Candidate gameweek identity must match requested gameweek.'
);


playerSnapshotPromotionAssert(
    $mismatchGameweekSnapshotRepository->insertCalls
    ===
    0,
    'Mismatched candidate gameweek cannot create historical evidence.'
);


/*
 * ============================================================
 * P. CANDIDATE REMAINS STAGING EVIDENCE
 * ============================================================
 */

playerSnapshotPromotionSection(
    'P. Candidate Retention'
);


playerSnapshotPromotionAssert(
    $candidateRepository->getCalls
    ===
    1,
    'Promotion reads candidate staging evidence without deleting it.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Promotion Service Test Summary<br>";
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