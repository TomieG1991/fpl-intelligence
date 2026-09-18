<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Multi-Gameweek Prepared Team Context Test<br>";
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

function preparedTeamContextCheck(
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
 * SETUP
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $players =
        $playerRepository
            ->getAll();


    $representativePlayerIds =
        [];


    foreach (
        $players
        as $player
    ) {

        $playerId =
            (int) (
                $player[
                    'id'
                ]
                ?? 0
            );


        $teamId =
            (int) (
                $player[
                    'team_id'
                ]
                ?? 0
            );


        $position =
            strtoupper(
                trim(
                    (string) (
                        $player[
                            'position'
                        ]
                        ?? ''
                    )
                )
            );


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
        ) {

            continue;
        }


        $representativePlayerIds[] =
            $playerId;


        if (
            count(
                $representativePlayerIds
            )
            >= 3
        ) {

            break;
        }
    }


    if (
        count(
            $representativePlayerIds
        )
        < 2
    ) {

        throw new RuntimeException(
            'At least two representative players are required.'
        );
    }


    $service =
        new PlayerIntelligenceService(
            $db
        );

} catch (
    Throwable $exception
) {

    echo "SETUP FAILED ❌<br><br>";

    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    exit;
}


/*
 * ============================================================
 * A. AUTHORITATIVE PROJECTION RESULTS
 * ============================================================
 *
 * Build the existing projection results before introducing any
 * prepared-context behaviour.
 *
 * These results are the behavioural contract that must remain
 * unchanged.
 * ============================================================
 */

echo "============================================<br>";
echo "A. Projection Contract<br>";
echo "============================================<br>";


$referenceResults =
    [];


foreach (
    $representativePlayerIds
    as $playerId
) {

    $referenceService =
        new PlayerIntelligenceService(
            $db
        );


    $referenceResults[
        $playerId
    ] =
        $referenceService
            ->getPlayerMultiGameweekExpectedPoints(
                $playerId,
                6
            );
}


preparedTeamContextCheck(
    'Representative multi-gameweek projections are available.',
    count(
        $referenceResults
    )
    ===
    count(
        $representativePlayerIds
    )
);


echo "<br>";


/*
 * ============================================================
 * B. PREPARED TEAM CONTEXT CONTRACT
 * ============================================================
 *
 * The optimized service should expose one internal prepared
 * team-context boundary.
 *
 * The context contains only team-wide evidence that is
 * independent of player identity and fixture limit.
 *
 * This method intentionally does not exist yet.
 * The test must therefore RED before production changes.
 * ============================================================
 */

echo "============================================<br>";
echo "B. Prepared Team Context Contract<br>";
echo "============================================<br>";


try {

    $reflection =
        new ReflectionClass(
            PlayerIntelligenceService::class
        );


    if (
        !$reflection->hasMethod(
            'prepareMultiGameweekTeamContext'
        )
    ) {

        throw new RuntimeException(
            'PlayerIntelligenceService::prepareMultiGameweekTeamContext() does not exist yet.'
        );
    }


    $prepareMethod =
        $reflection->getMethod(
            'prepareMultiGameweekTeamContext'
        );


    $prepareMethod->setAccessible(
        true
    );


    $firstContext =
        $prepareMethod->invoke(
            $service
        );


    $secondContext =
        $prepareMethod->invoke(
            $service
        );


    preparedTeamContextCheck(
        'Prepared context contains team-name lookup.',
        isset(
            $firstContext[
                'team_name_lookup'
            ]
        )
        &&
        is_array(
            $firstContext[
                'team_name_lookup'
            ]
        )
    );


    preparedTeamContextCheck(
        'Prepared context contains complete team models.',
        isset(
            $firstContext[
                'complete_team_models'
            ]
        )
        &&
        is_array(
            $firstContext[
                'complete_team_models'
            ]
        )
    );


    preparedTeamContextCheck(
        'Prepared context contains team attack and defence lookup.',
        isset(
            $firstContext[
                'team_attack_defence_lookup'
            ]
        )
        &&
        is_array(
            $firstContext[
                'team_attack_defence_lookup'
            ]
        )
    );


    preparedTeamContextCheck(
        'Repeated prepared-context requests reuse identical team evidence.',
        $secondContext
        ===
        $firstContext
    );

} catch (
    Throwable $exception
) {

    echo "EXPECTED RED: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


echo "<br>";


/*
 * ============================================================
 * C. PROJECTION EQUIVALENCE
 * ============================================================
 *
 * Once the prepared context exists, repeated player projection
 * calls through one service instance must remain byte-for-byte
 * equivalent to the authoritative existing results above.
 * ============================================================
 */

echo "============================================<br>";
echo "C. Projection Equivalence<br>";
echo "============================================<br>";


$projectionEquivalence =
    true;


foreach (
    $representativePlayerIds
    as $playerId
) {

    $actual =
        $service
            ->getPlayerMultiGameweekExpectedPoints(
                $playerId,
                6
            );


    if (
        $actual
        !==
        $referenceResults[
            $playerId
        ]
    ) {

        $projectionEquivalence =
            false;

        break;
    }
}


preparedTeamContextCheck(
    'Prepared team context preserves exact multi-gameweek projection results.',
    $projectionEquivalence
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}