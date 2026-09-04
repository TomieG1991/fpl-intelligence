<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE PROMOTION RUNNER TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * The promotion runner sits between:
 *
 * RecommendationCandidateRepository
 *      -> getReadyForPromotion()
 *
 * and:
 *
 * RecommendationCandidatePromotionService
 *      -> promote()
 *
 * Its responsibility is deliberately small:
 *
 * - discover candidates whose preserved deadline has been reached
 * - process them in repository order
 * - pass only entry/gameweek identity to the promotion service
 * - report batch accounting
 *
 * It must not:
 *
 * - calculate recommendation intelligence
 * - reconstruct recommendation evidence
 * - modify recommendation candidates
 * - write recommendation snapshots directly
 * - decide whether an existing snapshot may be replaced
 */


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed =
    0;


$failed =
    0;


function promotionRunnerAssert(
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


function promotionRunnerSection(
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
 *
 * The production runner should only need the repository's
 * promotion-readiness query.
 */

class PromotionRunnerCandidateRepositoryDouble
{
    private array $readyCandidates;

    private array $calls =
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


    public function getCalls(): array
    {
        return $this->calls;
    }
}


/*
 * ============================================================
 * TEST DOUBLE — PROMOTION SERVICE
 * ============================================================
 *
 * Results are keyed by:
 *
 * entry_id|gameweek_id
 *
 * so each controlled candidate can independently simulate
 * successful promotion or an unchanged result.
 */

class PromotionRunnerPromotionServiceDouble
{
    private array $results;

    private array $calls =
        [];


    public function __construct(
        array $results = []
    ) {

        $this->results =
            $results;
    }


    public function promote(
        int $entryId,
        int $gameweekId
    ): bool {

        $this->calls[] = [
            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId
        ];


        $key =
            $entryId
            . '|'
            . $gameweekId;


        return
            $this->results[
                $key
            ]
            ?? false;
    }


    public function getCalls(): array
    {
        return $this->calls;
    }
}


/*
 * ============================================================
 * CONTROLLED CANDIDATES
 * ============================================================
 */

$candidateOne = [

    'id' =>
        101,

    'gameweek_id' =>
        1,

    'entry_id' =>
        935006001,

    'generated_at' =>
        '2026-08-20 18:00:00',

    'deadline_time' =>
        '2026-08-21 18:30:00',

    /*
     * Evidence deliberately exists here to prove the runner
     * does not need to interpret or rebuild it.
     */
    'player_projections' => [
        [
            'player_id' =>
                501,

            'projected_points' =>
                7.25
        ]
    ],

    'starting_xi' => [
        [
            'player_id' =>
                501
        ]
    ],

    'captain_recommendation' => [
        'captain' => [
            'player_id' =>
                501
        ]
    ],

    'transfer_recommendations' => [
        'recommendation' =>
            'Hold'
    ],

    'gameweek_decision' => [
        'overall_action' =>
            'Hold'
    ],

    'chip_recommendations' => [
        'wildcard' => [
            'recommendation' =>
                'Hold'
        ]
    ]
];


$candidateTwo = [

    'id' =>
        102,

    'gameweek_id' =>
        1,

    'entry_id' =>
        935006002,

    'generated_at' =>
        '2026-08-21 17:00:00',

    'deadline_time' =>
        '2026-08-21 18:30:00',

    'player_projections' => [
        [
            'player_id' =>
                601,

            'projected_points' =>
                9.5
        ]
    ],

    'starting_xi' => [],

    'captain_recommendation' => [],

    'transfer_recommendations' => [],

    'gameweek_decision' => [],

    'chip_recommendations' => []
];


$candidateThree = [

    'id' =>
        103,

    'gameweek_id' =>
        2,

    'entry_id' =>
        935006001,

    'generated_at' =>
        '2026-08-28 17:00:00',

    'deadline_time' =>
        '2026-08-28 18:30:00',

    'player_projections' => [],

    'starting_xi' => [],

    'captain_recommendation' => [],

    'transfer_recommendations' => [],

    'gameweek_decision' => [],

    'chip_recommendations' => []
];


/*
 * ============================================================
 * A. NO READY CANDIDATES
 * ============================================================
 */

promotionRunnerSection(
    'A. No Ready Candidates'
);


$emptyRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        []
    );


$emptyPromotionService =
    new PromotionRunnerPromotionServiceDouble();


$emptyRunner =
    new RecommendationCandidatePromotionRunner(
        $emptyRepository,
        $emptyPromotionService
    );


$emptyResult =
    $emptyRunner
        ->run(
            '2026-08-21 18:30:00'
        );


promotionRunnerAssert(
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


promotionRunnerAssert(
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


promotionRunnerAssert(
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


promotionRunnerAssert(
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


promotionRunnerAssert(
    $emptyPromotionService
        ->getCalls()
    ===
    [],
    'Promotion service is not called when no candidates are ready.'
);


promotionRunnerAssert(
    $emptyRepository
        ->getCalls()
    ===
    [
        '2026-08-21 18:30:00'
    ],
    'Runner asks repository for ready candidates exactly once.'
);


/*
 * ============================================================
 * B. SINGLE READY CANDIDATE
 * ============================================================
 */

promotionRunnerSection(
    'B. Single Ready Candidate'
);


$singleRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        [
            $candidateOne
        ]
    );


$singlePromotionService =
    new PromotionRunnerPromotionServiceDouble(
        [
            '935006001|1' =>
                true
        ]
    );


$singleRunner =
    new RecommendationCandidatePromotionRunner(
        $singleRepository,
        $singlePromotionService
    );


$singleResult =
    $singleRunner
        ->run(
            '2026-08-21 18:30:00'
        );


promotionRunnerAssert(
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


promotionRunnerAssert(
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


promotionRunnerAssert(
    (
        $singleResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    0,
    'Successful single promotion is not counted as unchanged.'
);


promotionRunnerAssert(
    $singlePromotionService
        ->getCalls()
    ===
    [
        [
            'entry_id' =>
                935006001,

            'gameweek_id' =>
                1
        ]
    ],
    'Runner passes exact entry and gameweek identity to promotion service.'
);


/*
 * ============================================================
 * C. MULTIPLE READY CANDIDATES
 * ============================================================
 */

promotionRunnerSection(
    'C. Multiple Ready Candidates'
);


$multipleRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        [
            $candidateOne,
            $candidateTwo,
            $candidateThree
        ]
    );


$multiplePromotionService =
    new PromotionRunnerPromotionServiceDouble(
        [
            '935006001|1' =>
                true,

            '935006002|1' =>
                true,

            '935006001|2' =>
                true
        ]
    );


$multipleRunner =
    new RecommendationCandidatePromotionRunner(
        $multipleRepository,
        $multiplePromotionService
    );


$multipleResult =
    $multipleRunner
        ->run(
            '2026-08-28 18:30:00'
        );


promotionRunnerAssert(
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


promotionRunnerAssert(
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


promotionRunnerAssert(
    (
        $multipleResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    0,
    'No unchanged candidates are reported when every promotion succeeds.'
);


promotionRunnerAssert(
    $multiplePromotionService
        ->getCalls()
    ===
    [
        [
            'entry_id' =>
                935006001,

            'gameweek_id' =>
                1
        ],
        [
            'entry_id' =>
                935006002,

            'gameweek_id' =>
                1
        ],
        [
            'entry_id' =>
                935006001,

            'gameweek_id' =>
                2
        ]
    ],
    'Runner preserves repository candidate processing order.'
);


/*
 * ============================================================
 * D. UNCHANGED PROMOTION
 * ============================================================
 *
 * PromotionService::promote() returning false is a valid,
 * idempotent outcome.
 *
 * For example, the immutable snapshot may already exist.
 */

promotionRunnerSection(
    'D. Unchanged Promotion'
);


$unchangedRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        [
            $candidateOne,
            $candidateTwo
        ]
    );


$unchangedPromotionService =
    new PromotionRunnerPromotionServiceDouble(
        [
            '935006001|1' =>
                true,

            '935006002|1' =>
                false
        ]
    );


$unchangedRunner =
    new RecommendationCandidatePromotionRunner(
        $unchangedRepository,
        $unchangedPromotionService
    );


$unchangedResult =
    $unchangedRunner
        ->run(
            '2026-08-21 18:30:00'
        );


promotionRunnerAssert(
    (
        $unchangedResult[
            'ready'
        ]
        ?? null
    )
    ===
    2,
    'Ready count includes candidates that are already unchanged.'
);


promotionRunnerAssert(
    (
        $unchangedResult[
            'promoted'
        ]
        ?? null
    )
    ===
    1,
    'Only successful promotion is counted as promoted.'
);


promotionRunnerAssert(
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


promotionRunnerAssert(
    count(
        $unchangedPromotionService
            ->getCalls()
    )
    ===
    2,
    'Runner continues after an unchanged promotion result.'
);


/*
 * ============================================================
 * E. RUNNER USES IDENTITY, NOT RECOMMENDATION EVIDENCE
 * ============================================================
 */

promotionRunnerSection(
    'E. Identity-Only Promotion Boundary'
);


$identityRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        [
            $candidateOne
        ]
    );


$identityPromotionService =
    new PromotionRunnerPromotionServiceDouble(
        [
            '935006001|1' =>
                true
        ]
    );


$identityRunner =
    new RecommendationCandidatePromotionRunner(
        $identityRepository,
        $identityPromotionService
    );


$identityRunner
    ->run(
        '2026-08-21 18:30:00'
    );


$identityCalls =
    $identityPromotionService
        ->getCalls();


promotionRunnerAssert(
    isset(
        $identityCalls[
            0
        ][
            'entry_id'
        ],
        $identityCalls[
            0
        ][
            'gameweek_id'
        ]
    ),
    'Promotion call contains candidate identity.'
);


promotionRunnerAssert(
    count(
        $identityCalls[
            0
        ]
        ?? []
    )
    ===
    2,
    'Promotion call contains only entry and gameweek identity.'
);


/*
 * ============================================================
 * F. INVALID CANDIDATE IDENTITY
 * ============================================================
 *
 * Repository rows are persistence evidence and should normally
 * be valid. The runner still protects the promotion boundary
 * from malformed identity data.
 */

promotionRunnerSection(
    'F. Invalid Candidate Identity'
);


$invalidEntryCandidate =
    $candidateOne;


$invalidEntryCandidate[
    'entry_id'
] =
    0;


$invalidEntryRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        [
            $invalidEntryCandidate
        ]
    );


$invalidEntryPromotionService =
    new PromotionRunnerPromotionServiceDouble();


$invalidEntryRunner =
    new RecommendationCandidatePromotionRunner(
        $invalidEntryRepository,
        $invalidEntryPromotionService
    );


$invalidEntryRejected =
    false;


try {

    $invalidEntryRunner
        ->run(
            '2026-08-21 18:30:00'
        );

} catch (
    RuntimeException $exception
) {

    $invalidEntryRejected =
        true;
}


promotionRunnerAssert(
    $invalidEntryRejected,
    'Runner rejects ready candidate with invalid entry ID.'
);


promotionRunnerAssert(
    $invalidEntryPromotionService
        ->getCalls()
    ===
    [],
    'Invalid entry candidate is not passed to promotion service.'
);


$invalidGameweekCandidate =
    $candidateOne;


$invalidGameweekCandidate[
    'gameweek_id'
] =
    0;


$invalidGameweekRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        [
            $invalidGameweekCandidate
        ]
    );


$invalidGameweekPromotionService =
    new PromotionRunnerPromotionServiceDouble();


$invalidGameweekRunner =
    new RecommendationCandidatePromotionRunner(
        $invalidGameweekRepository,
        $invalidGameweekPromotionService
    );


$invalidGameweekRejected =
    false;


try {

    $invalidGameweekRunner
        ->run(
            '2026-08-21 18:30:00'
        );

} catch (
    RuntimeException $exception
) {

    $invalidGameweekRejected =
        true;
}


promotionRunnerAssert(
    $invalidGameweekRejected,
    'Runner rejects ready candidate with invalid gameweek ID.'
);


promotionRunnerAssert(
    $invalidGameweekPromotionService
        ->getCalls()
    ===
    [],
    'Invalid gameweek candidate is not passed to promotion service.'
);


/*
 * ============================================================
 * G. INVALID READY CANDIDATE ROW
 * ============================================================
 */

promotionRunnerSection(
    'G. Invalid Ready Candidate Row'
);


$invalidRowRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        [
            'not-an-array'
        ]
    );


$invalidRowPromotionService =
    new PromotionRunnerPromotionServiceDouble();


$invalidRowRunner =
    new RecommendationCandidatePromotionRunner(
        $invalidRowRepository,
        $invalidRowPromotionService
    );


$invalidRowRejected =
    false;


try {

    $invalidRowRunner
        ->run(
            '2026-08-21 18:30:00'
        );

} catch (
    RuntimeException $exception
) {

    $invalidRowRejected =
        true;
}


promotionRunnerAssert(
    $invalidRowRejected,
    'Runner rejects malformed ready candidate row.'
);


promotionRunnerAssert(
    $invalidRowPromotionService
        ->getCalls()
    ===
    [],
    'Malformed candidate row is not passed to promotion service.'
);


/*
 * ============================================================
 * H. INVALID RUN TIMESTAMP
 * ============================================================
 */

promotionRunnerSection(
    'H. Invalid Run Timestamp'
);


$invalidTimestampRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        []
    );


$invalidTimestampPromotionService =
    new PromotionRunnerPromotionServiceDouble();


$invalidTimestampRunner =
    new RecommendationCandidatePromotionRunner(
        $invalidTimestampRepository,
        $invalidTimestampPromotionService
    );


$invalidTimestampRejected =
    false;


try {

    $invalidTimestampRunner
        ->run(
            'not-a-valid-timestamp'
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidTimestampRejected =
        true;
}


promotionRunnerAssert(
    $invalidTimestampRejected,
    'Runner rejects invalid run timestamp.'
);


promotionRunnerAssert(
    $invalidTimestampRepository
        ->getCalls()
    ===
    [],
    'Invalid timestamp is rejected before repository discovery.'
);


promotionRunnerAssert(
    $invalidTimestampPromotionService
        ->getCalls()
    ===
    [],
    'Invalid timestamp cannot trigger promotion.'
);


/*
 * ============================================================
 * I. TIMESTAMP NORMALISATION
 * ============================================================
 *
 * The runner owns its public timestamp boundary and passes a
 * stable MySQL-compatible representation to the repository.
 */

promotionRunnerSection(
    'I. Timestamp Normalisation'
);


$normalisationRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        []
    );


$normalisationPromotionService =
    new PromotionRunnerPromotionServiceDouble();


$normalisationRunner =
    new RecommendationCandidatePromotionRunner(
        $normalisationRepository,
        $normalisationPromotionService
    );


$normalisationRunner
    ->run(
        '2026-08-21T18:30:00+00:00'
    );


promotionRunnerAssert(
    $normalisationRepository
        ->getCalls()
    ===
    [
        '2026-08-21 18:30:00'
    ],
    'Runner normalises valid timestamp before repository discovery.'
);


/*
 * ============================================================
 * J. RESULT ACCOUNTING CONTRACT
 * ============================================================
 */

promotionRunnerSection(
    'J. Result Accounting Contract'
);


$accountingRepository =
    new PromotionRunnerCandidateRepositoryDouble(
        [
            $candidateOne,
            $candidateTwo,
            $candidateThree
        ]
    );


$accountingPromotionService =
    new PromotionRunnerPromotionServiceDouble(
        [
            '935006001|1' =>
                true,

            '935006002|1' =>
                false,

            '935006001|2' =>
                true
        ]
    );


$accountingRunner =
    new RecommendationCandidatePromotionRunner(
        $accountingRepository,
        $accountingPromotionService
    );


$accountingResult =
    $accountingRunner
        ->run(
            '2026-08-28 18:30:00'
        );


promotionRunnerAssert(
    $accountingResult
    ===
    [
        'status' =>
            'Complete',

        'ready' =>
            3,

        'promoted' =>
            2,

        'unchanged' =>
            1
    ],
    'Runner returns exact promotion accounting contract.'
);


promotionRunnerAssert(
    (
        $accountingResult[
            'ready'
        ]
        ?? 0
    )
    ===
    (
        (
            $accountingResult[
                'promoted'
            ]
            ?? 0
        )
        +
        (
            $accountingResult[
                'unchanged'
            ]
            ?? 0
        )
    ),
    'Promotion accounting balances ready candidates.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

promotionRunnerSection(
    'Recommendation Candidate Promotion Runner Test Summary'
);


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if (
    $failed === 0
) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}