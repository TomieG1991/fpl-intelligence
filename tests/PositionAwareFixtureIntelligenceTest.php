<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Position-Aware Fixture Intelligence Test<br>";
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

function positionAwareFixtureCheck(
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

echo "============================================<br>";
echo "Scenario A: Setup<br>";
echo "============================================<br>";


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


    $fixtureIntelligence =
        new FixtureIntelligence();


    positionAwareFixtureCheck(
        'Database connection is available',
        $db instanceof PDO
    );


    positionAwareFixtureCheck(
        'Player Intelligence Service is available',
        $service instanceof PlayerIntelligenceService
    );


    positionAwareFixtureCheck(
        'Fixture Intelligence model is available',
        $fixtureIntelligence instanceof FixtureIntelligence
    );

} catch (
    Throwable $exception
) {

    positionAwareFixtureCheck(
        'Database connection is available',
        false
    );


    positionAwareFixtureCheck(
        'Player Intelligence Service is available',
        false
    );


    positionAwareFixtureCheck(
        'Fixture Intelligence model is available',
        false
    );


    echo "<br>";
    echo "Setup Error: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br><br>";


    echo "============================================<br>";
    echo "Position-Aware Fixture Intelligence Test Summary<br>";
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


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * SERVICE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Position-Aware Fixture Contract<br>";
echo "============================================<br>";


$methodAvailable =
    method_exists(
        $fixtureIntelligence,
        'calculatePositionAwareOpportunity'
    );


positionAwareFixtureCheck(
    'Fixture Intelligence exposes calculatePositionAwareOpportunity()',
    $methodAvailable
);


if (
    !$methodAvailable
) {

    echo "<br>";
    echo "Position-aware Fixture Intelligence has not been implemented yet.<br>";
    echo "The remaining scenarios will run after the new method is added.<br><br>";


    echo "============================================<br>";
    echo "Position-Aware Fixture Intelligence Test Summary<br>";
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


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * VALID POSITIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Valid Positions<br>";
echo "============================================<br>";


$positions = [

    'GK',
    'DEF',
    'MID',
    'FWD'
];


foreach (
    $positions
    as $position
) {

    $result =
        $fixtureIntelligence
            ->calculatePositionAwareOpportunity(
                50.0,
                $position,
                50.0,
                50.0
            );


    positionAwareFixtureCheck(
        $position
        . ' returns numeric position-aware fixture opportunity',
        is_numeric(
            $result
        )
    );


    positionAwareFixtureCheck(
        $position
        . ' position-aware fixture opportunity remains between 0 and 100',
        is_numeric(
            $result
        )
        &&
        (float) $result >= 0
        &&
        (float) $result <= 100
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * DEFENSIVE POSITION BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Defensive Position Behaviour<br>";
echo "============================================<br>";


$defVsWeakAttack =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'DEF',
            20.0,
            50.0
        );


$defVsNeutralAttack =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'DEF',
            50.0,
            50.0
        );


$defVsStrongAttack =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'DEF',
            80.0,
            50.0
        );


positionAwareFixtureCheck(
    'Defender fixture opportunity improves against weak opponent attack',
    $defVsWeakAttack
    >
    $defVsNeutralAttack
);


positionAwareFixtureCheck(
    'Defender fixture opportunity worsens against strong opponent attack',
    $defVsStrongAttack
    <
    $defVsNeutralAttack
);


positionAwareFixtureCheck(
    'Defender position-aware ratings remain ordered by opponent attack strength',
    $defVsWeakAttack
    >
    $defVsNeutralAttack
    &&
    $defVsNeutralAttack
    >
    $defVsStrongAttack
);


$gkVsWeakAttack =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'GK',
            20.0,
            50.0
        );


$gkVsStrongAttack =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'GK',
            80.0,
            50.0
        );


positionAwareFixtureCheck(
    'Goalkeeper opportunity is higher against weak opponent attack',
    $gkVsWeakAttack
    >
    $gkVsStrongAttack
);


echo "DEF vs Weak Attack: "
    . number_format(
        (float) $defVsWeakAttack,
        2
    )
    . "<br>";


echo "DEF vs Neutral Attack: "
    . number_format(
        (float) $defVsNeutralAttack,
        2
    )
    . "<br>";


echo "DEF vs Strong Attack: "
    . number_format(
        (float) $defVsStrongAttack,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * ATTACKING POSITION BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Attacking Position Behaviour<br>";
echo "============================================<br>";


$midVsWeakDefence =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'MID',
            50.0,
            20.0
        );


$midVsNeutralDefence =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'MID',
            50.0,
            50.0
        );


$midVsStrongDefence =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'MID',
            50.0,
            80.0
        );


positionAwareFixtureCheck(
    'Midfielder fixture opportunity improves against weak opponent defence',
    $midVsWeakDefence
    >
    $midVsNeutralDefence
);


positionAwareFixtureCheck(
    'Midfielder fixture opportunity worsens against strong opponent defence',
    $midVsStrongDefence
    <
    $midVsNeutralDefence
);


positionAwareFixtureCheck(
    'Midfielder position-aware ratings remain ordered by opponent defence strength',
    $midVsWeakDefence
    >
    $midVsNeutralDefence
    &&
    $midVsNeutralDefence
    >
    $midVsStrongDefence
);


$fwdVsWeakDefence =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'FWD',
            50.0,
            20.0
        );


$fwdVsStrongDefence =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            50.0,
            'FWD',
            50.0,
            80.0
        );


positionAwareFixtureCheck(
    'Forward opportunity is higher against weak opponent defence',
    $fwdVsWeakDefence
    >
    $fwdVsStrongDefence
);


echo "MID vs Weak Defence: "
    . number_format(
        (float) $midVsWeakDefence,
        2
    )
    . "<br>";


echo "MID vs Neutral Defence: "
    . number_format(
        (float) $midVsNeutralDefence,
        2
    )
    . "<br>";


echo "MID vs Strong Defence: "
    . number_format(
        (float) $midVsStrongDefence,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * FALLBACK BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: No-Evidence Fallback Behaviour<br>";
echo "============================================<br>";


$baseOpportunity =
    63.5;


$gkFallback =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            $baseOpportunity,
            'GK',
            null,
            null
        );


$defFallback =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            $baseOpportunity,
            'DEF',
            null,
            null
        );


$midFallback =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            $baseOpportunity,
            'MID',
            null,
            null
        );


$fwdFallback =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            $baseOpportunity,
            'FWD',
            null,
            null
        );


foreach (
    [
        'GK' =>
            $gkFallback,

        'DEF' =>
            $defFallback,

        'MID' =>
            $midFallback,

        'FWD' =>
            $fwdFallback
    ]
    as $position => $fallbackValue
) {

    positionAwareFixtureCheck(
        $position
        . ' falls back to base fixture opportunity when no attack/defence evidence exists',
        is_numeric(
            $fallbackValue
        )
        &&
        abs(
            (float) $fallbackValue
            -
            $baseOpportunity
        )
        < 0.01
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * SCORE BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Score Bounds<br>";
echo "============================================<br>";


$extremeDefensiveOpportunity =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            100.0,
            'DEF',
            0.0,
            100.0
        );


$extremeAttackingOpportunity =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            100.0,
            'FWD',
            100.0,
            0.0
        );


$extremeDefensivePenalty =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            0.0,
            'DEF',
            100.0,
            0.0
        );


$extremeAttackingPenalty =
    $fixtureIntelligence
        ->calculatePositionAwareOpportunity(
            0.0,
            'FWD',
            0.0,
            100.0
        );


foreach (
    [
        $extremeDefensiveOpportunity,
        $extremeAttackingOpportunity,
        $extremeDefensivePenalty,
        $extremeAttackingPenalty
    ]
    as $result
) {

    positionAwareFixtureCheck(
        'Position-aware fixture opportunity remains within 0-100 bounds',
        is_numeric(
            $result
        )
        &&
        (float) $result >= 0
        &&
        (float) $result <= 100
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * INVALID POSITION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Invalid Position<br>";
echo "============================================<br>";


$invalidPositionHandled =
    false;


try {

    $invalidResult =
        $fixtureIntelligence
            ->calculatePositionAwareOpportunity(
                50.0,
                'UNKNOWN',
                50.0,
                50.0
            );


    /*
     * A safe fallback is acceptable if the method chooses
     * not to throw. Returning the base opportunity is the
     * expected fallback contract.
     */

    $invalidPositionHandled =
        is_numeric(
            $invalidResult
        )
        &&
        abs(
            (float) $invalidResult
            -
            50.0
        )
        < 0.01;

} catch (
    InvalidArgumentException $exception
) {

    $invalidPositionHandled =
        true;
}


positionAwareFixtureCheck(
    'Invalid position is rejected or safely falls back to base fixture opportunity',
    $invalidPositionHandled
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * PLAYER INTELLIGENCE SUMMARY INTEGRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Player Intelligence Summary Integration<br>";
echo "============================================<br>";


$playerSummaries =
    $service
        ->getAllPlayerSummaries();


positionAwareFixtureCheck(
    'Player Intelligence summaries are returned',
    is_array(
        $playerSummaries
    )
    &&
    !empty(
        $playerSummaries
    )
);


$firstPlayerSummary =
    $playerSummaries[
        0
    ]
    ?? [];


$hasPositionAwareFixture =
    array_key_exists(
        'position_aware_fixture_rating',
        $firstPlayerSummary
    );


positionAwareFixtureCheck(
    'Player Intelligence summary exposes position_aware_fixture_rating',
    $hasPositionAwareFixture
);


if (
    $hasPositionAwareFixture
) {

    $allPositionAwareRatingsValid =
        true;


    foreach (
        $playerSummaries
        as $summary
    ) {

        $rating =
            $summary[
                'position_aware_fixture_rating'
            ]
            ?? null;


        if (
            $rating === null
        ) {

            continue;
        }


        if (
            !is_numeric(
                $rating
            )
            ||
            (float) $rating < 0
            ||
            (float) $rating > 100
        ) {

            $allPositionAwareRatingsValid =
                false;

            break;
        }
    }


    positionAwareFixtureCheck(
        'All available position-aware fixture ratings remain between 0 and 100',
        $allPositionAwareRatingsValid
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * NEXT FIXTURE INTEGRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Immediate Fixture Integration<br>";
echo "============================================<br>";


$hasNextFixtureRating =
    array_key_exists(
        'next_fixture_rating',
        $firstPlayerSummary
    );


positionAwareFixtureCheck(
    'Player Intelligence summary exposes next_fixture_rating',
    $hasNextFixtureRating
);


if (
    $hasPositionAwareFixture
    &&
    $hasNextFixtureRating
) {

    $fallbackConsistency =
        true;


    foreach (
        $playerSummaries
        as $summary
    ) {

        $positionAware =
            $summary[
                'position_aware_fixture_rating'
            ]
            ?? null;


        $nextFixture =
            $summary[
                'next_fixture_rating'
            ]
            ?? null;


        if (
            $positionAware === null
            ||
            $nextFixture === null
        ) {

            continue;
        }


        if (
            !is_numeric(
                $positionAware
            )
            ||
            !is_numeric(
                $nextFixture
            )
        ) {

            $fallbackConsistency =
                false;

            break;
        }
    }


    positionAwareFixtureCheck(
        'Immediate fixture and position-aware fixture outputs remain numerically valid together',
        $fallbackConsistency
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * CURRENT NO-EVIDENCE REGRESSION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Current No-Evidence Regression<br>";
echo "============================================<br>";


if (
    $hasPositionAwareFixture
) {

    $numericCount =
        0;


    $nullCount =
        0;


    foreach (
        $playerSummaries
        as $summary
    ) {

        $rating =
            $summary[
                'position_aware_fixture_rating'
            ]
            ?? null;


        if (
            $rating === null
        ) {

            $nullCount++;

        } elseif (
            is_numeric(
                $rating
            )
        ) {

            $numericCount++;
        }
    }


    positionAwareFixtureCheck(
        'Every player exposes either numeric or null position-aware fixture output',
        (
            $numericCount
            +
            $nullCount
        )
        ===
        count(
            $playerSummaries
        )
    );


    echo "Numeric Position-Aware Ratings: "
        . $numericCount
        . "<br>";


    echo "Null Position-Aware Ratings: "
        . $nullCount
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Performance<br>";
echo "============================================<br>";


$startedAt =
    microtime(
        true
    );


$performanceSummaries =
    $service
        ->getAllPlayerSummaries();


$runtime =
    microtime(
        true
    )
    -
    $startedAt;


positionAwareFixtureCheck(
    'Position-aware fixture integration completes within 10 seconds',
    $runtime <= 10.0
);


positionAwareFixtureCheck(
    'Performance run still returns Player Intelligence summaries',
    !empty(
        $performanceSummaries
    )
);


echo "Measured Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Position-Aware Fixture Intelligence Test Summary<br>";
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