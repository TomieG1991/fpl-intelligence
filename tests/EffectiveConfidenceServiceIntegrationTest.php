<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Effective Confidence Service Integration Test<br>";
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

function effectiveServiceCheck(
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
 * SCENARIO A
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


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $teamRepository =
        new TeamRepository(
            $db
        );


    $fixtureRepository =
        new FixtureRepository(
            $db
        );


    $service =
        new PlayerIntelligenceService(
            $db
        );


    effectiveServiceCheck(
        'Database connection is available',
        $db instanceof PDO
    );


    effectiveServiceCheck(
        'Player Repository is available',
        $playerRepository instanceof PlayerRepository
    );


    effectiveServiceCheck(
        'Team Repository is available',
        $teamRepository instanceof TeamRepository
    );


    effectiveServiceCheck(
        'Fixture Repository is available',
        $fixtureRepository instanceof FixtureRepository
    );


    effectiveServiceCheck(
        'Player Intelligence Service is available',
        $service instanceof PlayerIntelligenceService
    );

} catch (
    Throwable $exception
) {

    effectiveServiceCheck(
        'Database connection is available',
        false
    );


    echo "<br>Setup Error: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    exit;
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * CURRENT DATASET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Current Dataset<br>";
echo "============================================<br>";


$players =
    $playerRepository
        ->getAll();


$teams =
    $teamRepository
        ->getAll();


$fixtures =
    $fixtureRepository
        ->getAll();


effectiveServiceCheck(
    'Player repository contains current players',
    is_array(
        $players
    )
    &&
    !empty(
        $players
    )
);


effectiveServiceCheck(
    'Team repository contains exactly 20 Premier League teams',
    is_array(
        $teams
    )
    &&
    count(
        $teams
    )
    === 20
);


effectiveServiceCheck(
    'Fixture repository contains 380 Premier League fixtures',
    is_array(
        $fixtures
    )
    &&
    count(
        $fixtures
    )
    === 380
);


echo "Players: "
    . count(
        $players
    )
    . "<br>";


echo "Teams: "
    . count(
        $teams
    )
    . "<br>";


echo "Fixtures: "
    . count(
        $fixtures
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * FINISHED FIXTURE EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Finished Fixture Evidence<br>";
echo "============================================<br>";


$finishedFixtures =
    $fixtureRepository
        ->getFinishedFixtures();


effectiveServiceCheck(
    'Finished fixture query returns an array',
    is_array(
        $finishedFixtures
    )
);


$finishedFixtureCount =
    is_array(
        $finishedFixtures
    )
        ? count(
            $finishedFixtures
        )
        : 0;


effectiveServiceCheck(
    'Local fixture data contains completed Premier League fixtures',
    $finishedFixtureCount > 0
);


echo "Finished Fixtures: "
    . $finishedFixtureCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * TEAM AVAILABLE MINUTES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Team Available Minutes<br>";
echo "============================================<br>";


$teamAvailableMinutes =
    [];


$teamFinishedMatches =
    [];


foreach (
    $teams
    as $team
) {

    $teamId =
        (int) (
            $team[
                'id'
            ]
            ?? 0
        );


    if (
        $teamId <= 0
    ) {

        continue;
    }


    $finishedForTeam =
        $fixtureRepository
            ->getFinishedForTeam(
                $teamId
            );


    $finishedCount =
        is_array(
            $finishedForTeam
        )
            ? count(
                $finishedForTeam
            )
            : 0;


    $teamFinishedMatches[
        $teamId
    ] =
        $finishedCount;


    $teamAvailableMinutes[
        $teamId
    ] =
        $finishedCount
        *
        90;
}


effectiveServiceCheck(
    'Available-minute values are generated for all 20 teams',
    count(
        $teamAvailableMinutes
    )
    === 20
);


$allMinutesValid =
    true;


foreach (
    $teamAvailableMinutes
    as $availableMinutes
) {

    if (
        !is_int(
            $availableMinutes
        )
        ||
        $availableMinutes < 0
        ||
        $availableMinutes % 90 !== 0
    ) {

        $allMinutesValid =
            false;

        break;
    }
}


effectiveServiceCheck(
    'Every team available-minute value is a non-negative multiple of 90',
    $allMinutesValid
);


$teamsWithFinishedMatches =
    count(
        array_filter(
            $teamFinishedMatches,
            static function (
                int $matches
            ): bool {

                return $matches > 0;
            }
        )
    );


$teamsWithoutFinishedMatches =
    20
    -
    $teamsWithFinishedMatches;


echo "Teams With Finished Matches: "
    . $teamsWithFinishedMatches
    . "<br>";


echo "Teams Without Finished Matches: "
    . $teamsWithoutFinishedMatches
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * PLAYER INTELLIGENCE SERVICE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Player Intelligence Service Contract<br>";
echo "============================================<br>";


$startedAt =
    microtime(
        true
    );


$summaries =
    $service
        ->getAllPlayerSummaries();


$summaryRuntime =
    microtime(
        true
    )
    -
    $startedAt;


effectiveServiceCheck(
    'Player Intelligence summaries are returned',
    is_array(
        $summaries
    )
    &&
    !empty(
        $summaries
    )
);


$requiredFields = [

    'sample_confidence',

    'effective_confidence',

    'team_available_minutes',

    'participation_rate'
];


foreach (
    $requiredFields
    as $field
) {

    $fieldExists =
        false;


    foreach (
        $summaries
        as $summary
    ) {

        if (
            array_key_exists(
                $field,
                $summary
            )
        ) {

            $fieldExists =
                true;

            break;
        }
    }


    effectiveServiceCheck(
        'Player Intelligence summary exposes '
        . $field,
        $fieldExists
    );
}


echo "Player Summaries: "
    . count(
        $summaries
    )
    . "<br>";


echo "Summary Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * RAW PLAYER LOOKUP
 * ============================================================
 */

$rawPlayersById =
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


    if (
        $playerId <= 0
    ) {

        continue;
    }


    $rawPlayersById[
        $playerId
    ] =
        $player;
}


/*
 * ============================================================
 * SCENARIO F
 * SAMPLE CONFIDENCE PRESERVATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Sample Confidence Preservation<br>";
echo "============================================<br>";


$performance =
    new PlayerPerformance();


$sampleConfidenceMatches =
    true;


$samplePlayersChecked =
    0;


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


    $rawPlayer =
        $rawPlayersById[
            $playerId
        ]
        ?? null;


    if (
        !is_array(
            $rawPlayer
        )
    ) {

        continue;
    }


    $minutes =
        (int) (
            $rawPlayer[
                'minutes'
            ]
            ?? 0
        );


    $expectedSampleConfidence =
        $performance
            ->calculateSampleConfidence(
                $minutes
            );


    $actualSampleConfidence =
        $summary[
            'sample_confidence'
        ]
        ?? null;


    if (
        !is_numeric(
            $actualSampleConfidence
        )
        ||
        abs(
            (float) $actualSampleConfidence
            -
            $expectedSampleConfidence
        )
        >
        0.0001
    ) {

        $sampleConfidenceMatches =
            false;

        break;
    }


    $samplePlayersChecked++;


    if (
        $samplePlayersChecked >= 50
    ) {

        break;
    }
}


effectiveServiceCheck(
    'Existing sample confidence remains unchanged',
    $sampleConfidenceMatches
    &&
    $samplePlayersChecked > 0
);


echo "Sample Players Checked: "
    . $samplePlayersChecked
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * EFFECTIVE CONFIDENCE INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Effective Confidence Integrity<br>";
echo "============================================<br>";


$serviceFieldsAvailable =
    !empty(
        $summaries
    )
    &&
    array_key_exists(
        'effective_confidence',
        $summaries[
            0
        ]
        ?? []
    )
    &&
    array_key_exists(
        'team_available_minutes',
        $summaries[
            0
        ]
        ?? []
    )
    &&
    array_key_exists(
        'participation_rate',
        $summaries[
            0
        ]
        ?? []
    );


if (
    !$serviceFieldsAvailable
) {

    echo "Effective Confidence service fields have not been implemented yet.<br>";
    echo "Detailed service integration scenarios will become active after the fields are added.<br><br>";

} else {

    $allEffectiveConfidenceValid =
        true;


    $allAvailableMinutesValid =
        true;


    $allParticipationRatesValid =
        true;


    $numericEffectiveCount =
        0;


    $nullEffectiveCount =
        0;


    foreach (
        $summaries
        as $summary
    ) {

        $effectiveConfidence =
            $summary[
                'effective_confidence'
            ]
            ?? null;


        $availableMinutes =
            $summary[
                'team_available_minutes'
            ]
            ?? null;


        $participationRate =
            $summary[
                'participation_rate'
            ]
            ?? null;


        if (
            $effectiveConfidence === null
        ) {

            $nullEffectiveCount++;

        } elseif (
            !is_numeric(
                $effectiveConfidence
            )
            ||
            (float) $effectiveConfidence < 0
            ||
            (float) $effectiveConfidence > 1
        ) {

            $allEffectiveConfidenceValid =
                false;

        } else {

            $numericEffectiveCount++;
        }


        if (
            !is_numeric(
                $availableMinutes
            )
            ||
            (int) $availableMinutes < 0
            ||
            (int) $availableMinutes % 90 !== 0
        ) {

            $allAvailableMinutesValid =
                false;
        }


        if (
            $participationRate !== null
            &&
            (
                !is_numeric(
                    $participationRate
                )
                ||
                (float) $participationRate < 0
                ||
                (float) $participationRate > 1
            )
        ) {

            $allParticipationRatesValid =
                false;
        }
    }


    effectiveServiceCheck(
        'All non-null Effective Confidence values remain between 0 and 1',
        $allEffectiveConfidenceValid
    );


    effectiveServiceCheck(
        'All service team-available-minute values are valid',
        $allAvailableMinutesValid
    );


    effectiveServiceCheck(
        'All non-null participation rates remain between 0 and 1',
        $allParticipationRatesValid
    );


    echo "Numeric Effective Confidence: "
        . $numericEffectiveCount
        . "<br>";


    echo "Null Effective Confidence: "
        . $nullEffectiveCount
        . "<br><br>";
}


/*
 * ============================================================
 * SCENARIO J
 * PARTICIPATION RATE CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Participation Rate Consistency<br>";
echo "============================================<br>";


if (
    $serviceFieldsAvailable
) {

    $participationConsistent =
        true;


    $participationChecked =
        0;


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


        $rawPlayer =
            $rawPlayersById[
                $playerId
            ]
            ?? null;


        if (
            !is_array(
                $rawPlayer
            )
        ) {

            continue;
        }


        $availableMinutes =
            (int) (
                $summary[
                    'team_available_minutes'
                ]
                ?? 0
            );


        if (
            $availableMinutes <= 0
        ) {

            continue;
        }


        $minutes =
            max(
                0,
                (int) (
                    $rawPlayer[
                        'minutes'
                    ]
                    ?? 0
                )
            );


        $expectedParticipation =
            min(
                1.0,
                $minutes
                /
                $availableMinutes
            );


        $actualParticipation =
            $summary[
                'participation_rate'
            ]
            ?? null;


        if (
            !is_numeric(
                $actualParticipation
            )
            ||
            abs(
                (float) $actualParticipation
                -
                $expectedParticipation
            )
            >
            0.0001
        ) {

            $participationConsistent =
                false;

            break;
        }


        $participationChecked++;
    }


    effectiveServiceCheck(
        'Participation rate matches player minutes divided by team available minutes',
        $participationConsistent
        &&
        $participationChecked > 0
    );


    echo "Participation Records Checked: "
        . $participationChecked
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * SERVICE / MODEL CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Service / Model Consistency<br>";
echo "============================================<br>";


if (
    $serviceFieldsAvailable
) {

    $effectiveConsistent =
        true;


    $effectiveChecked =
        0;


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


        $rawPlayer =
            $rawPlayersById[
                $playerId
            ]
            ?? null;


        if (
            !is_array(
                $rawPlayer
            )
        ) {

            continue;
        }


        $minutes =
            (int) (
                $rawPlayer[
                    'minutes'
                ]
                ?? 0
            );


        $availableMinutes =
            (int) (
                $summary[
                    'team_available_minutes'
                ]
                ?? 0
            );


        $expectedEffective =
            $performance
                ->calculateEffectiveConfidence(
                    $minutes,
                    $availableMinutes
                );


        $actualEffective =
            $summary[
                'effective_confidence'
            ]
            ?? null;


        if (
            $expectedEffective === null
        ) {

            if (
                $actualEffective !== null
            ) {

                $effectiveConsistent =
                    false;

                break;
            }

        } else {

            if (
                !is_numeric(
                    $actualEffective
                )
                ||
                abs(
                    (float) $actualEffective
                    -
                    $expectedEffective
                )
                >
                0.0001
            ) {

                $effectiveConsistent =
                    false;

                break;
            }
        }


        $effectiveChecked++;
    }


    effectiveServiceCheck(
        'Service Effective Confidence matches PlayerPerformance calculation',
        $effectiveConsistent
        &&
        $effectiveChecked > 0
    );


    echo "Effective Confidence Records Checked: "
        . $effectiveChecked
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


effectiveServiceCheck(
    'Effective Confidence service integration completes within 10 seconds',
    $summaryRuntime <= 10.0
);


echo "Measured Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Effective Confidence Service Integration Test Summary<br>";
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