<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Recommendation Candidate Calibration Evidence Real Data Test<br>";
echo "============================================<br>";


$passed =
    0;


$failed =
    0;


function candidateCalibrationEvidenceResult(
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


/*
 * ============================================================
 * REAL ENTRY
 * ============================================================
 */

$entryId =
    2702264;


try {

    /*
     * ========================================================
     * DATABASE
     * ========================================================
     */

    $database =
        new Database();


    $db =
        $database->getConnection();


    candidateCalibrationEvidenceResult(
        $db instanceof PDO,
        'Database connection is available.'
    );


    /*
     * ========================================================
     * REPOSITORIES
     * ========================================================
     */

    $gameweekRepository =
        new GameweekRepository(
            $db
        );


    $candidateRepository =
        new RecommendationCandidateRepository(
            $db
        );


    /*
     * ========================================================
     * RESOLVE NEXT DEADLINE
     * ========================================================
     */

    $generatedAt =
        (new DateTimeImmutable())
            ->format(
                'Y-m-d H:i:s'
            );


    $targetGameweek =
        $gameweekRepository
            ->getNextDeadlineAfter(
                $generatedAt
            );


    candidateCalibrationEvidenceResult(
        is_array(
            $targetGameweek
        ),
        'A future recommendation target gameweek is available.'
    );


    if (
        !is_array(
            $targetGameweek
        )
    ) {

        throw new RuntimeException(
            'No future recommendation target gameweek is available.'
        );
    }


    $gameweekId =
        (int) (
            $targetGameweek[
                'id'
            ]
            ??
            0
        );


    $fplGameweekId =
        (int) (
            $targetGameweek[
                'fpl_gameweek_id'
            ]
            ??
            0
        );


    candidateCalibrationEvidenceResult(
        $gameweekId > 0,
        'Target gameweek has a valid local ID.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "TARGET GAMEWEEK<br>";
    echo "============================================<br>";

    echo "Local gameweek ID: "
        . $gameweekId
        . "<br>";

    echo "FPL gameweek ID: "
        . $fplGameweekId
        . "<br>";

    echo "Name: "
        . htmlspecialchars(
            (string) (
                $targetGameweek[
                    'name'
                ]
                ??
                'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "Deadline: "
        . htmlspecialchars(
            (string) (
                $targetGameweek[
                    'deadline_time'
                ]
                ??
                'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    /*
     * ========================================================
     * LOAD CURRENT RECOMMENDATION CANDIDATE
     * ========================================================
     */

    $candidate =
        $candidateRepository
            ->getByEntryAndGameweek(
                $entryId,
                $gameweekId
            );


    candidateCalibrationEvidenceResult(
        is_array(
            $candidate
        ),
        'Recommendation candidate exists for the next deadline.'
    );


    if (
        !is_array(
            $candidate
        )
    ) {

        echo "<br>";
        echo "No recommendation candidate currently exists for this entry/gameweek.<br>";

    } else {

        /*
         * ====================================================
         * PLAYER RANKING EVIDENCE
         * ====================================================
         */

        $playerRankings =
            is_array(
                $candidate[
                    'player_rankings'
                ]
                ??
                null
            )
                ? $candidate[
                    'player_rankings'
                ]
                : [];


        echo "<br>";
        echo "============================================<br>";
        echo "PLAYER RANKING CALIBRATION EVIDENCE<br>";
        echo "============================================<br>";

        echo "Ranking rows: "
            . count(
                $playerRankings
            )
            . "<br>";


        candidateCalibrationEvidenceResult(
            !empty(
                $playerRankings
            ),
            'Recommendation candidate contains player ranking evidence.'
        );


        $completeRows =
            0;


        $missingStrength =
            0;


        $missingFixture =
            0;


        $missingAvailability =
            0;


        foreach (
            $playerRankings
            as $ranking
        ) {

            if (!is_array($ranking)) {

                continue;
            }


            $hasStrength =
                array_key_exists(
                    'strength_rating',
                    $ranking
                )
                &&
                $ranking[
                    'strength_rating'
                ] !== null
                &&
                is_numeric(
                    $ranking[
                        'strength_rating'
                    ]
                );


            $hasFixture =
                array_key_exists(
                    'fixture_rating',
                    $ranking
                )
                &&
                $ranking[
                    'fixture_rating'
                ] !== null
                &&
                is_numeric(
                    $ranking[
                        'fixture_rating'
                    ]
                );


            $hasAvailability =
                array_key_exists(
                    'availability_multiplier',
                    $ranking
                )
                &&
                $ranking[
                    'availability_multiplier'
                ] !== null
                &&
                is_numeric(
                    $ranking[
                        'availability_multiplier'
                    ]
                );


            if (!$hasStrength) {

                $missingStrength++;
            }


            if (!$hasFixture) {

                $missingFixture++;
            }


            if (!$hasAvailability) {

                $missingAvailability++;
            }


            if (
                $hasStrength
                &&
                $hasFixture
                &&
                $hasAvailability
            ) {

                $completeRows++;
            }
        }


        echo "Complete calibration rows: "
            . $completeRows
            . "<br>";

        echo "Rows missing Strength: "
            . $missingStrength
            . "<br>";

        echo "Rows missing Fixture: "
            . $missingFixture
            . "<br>";

        echo "Rows missing Availability Multiplier: "
            . $missingAvailability
            . "<br>";


        candidateCalibrationEvidenceResult(
            $completeRows > 0,
            'Candidate contains at least one complete Strength/Fixture/Availability calibration row.'
        );


        /*
         * ====================================================
         * SAMPLE ROWS
         * ====================================================
         */

        echo "<br>";
        echo "============================================<br>";
        echo "SAMPLE CALIBRATION ROWS<br>";
        echo "============================================<br>";


        $shown =
            0;


        foreach (
            $playerRankings
            as $ranking
        ) {

            if (!is_array($ranking)) {

                continue;
            }


            echo htmlspecialchars(
                (string) (
                    $ranking[
                        'name'
                    ]
                    ??
                    'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            );


            echo " | Player ID: "
                . htmlspecialchars(
                    (string) (
                        $ranking[
                            'player_id'
                        ]
                        ??
                        'N/A'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                );


            echo " | Strength: "
                . (
                    is_numeric(
                        $ranking[
                            'strength_rating'
                        ]
                        ??
                        null
                    )
                        ? number_format(
                            (float) $ranking[
                                'strength_rating'
                            ],
                            2
                        )
                        : 'N/A'
                );


            echo " | Fixture: "
                . (
                    is_numeric(
                        $ranking[
                            'fixture_rating'
                        ]
                        ??
                        null
                    )
                        ? number_format(
                            (float) $ranking[
                                'fixture_rating'
                            ],
                            2
                        )
                        : 'N/A'
                );


            echo " | Availability: "
                . (
                    is_numeric(
                        $ranking[
                            'availability_multiplier'
                        ]
                        ??
                        null
                    )
                        ? number_format(
                            (float) $ranking[
                                'availability_multiplier'
                            ],
                            2
                        )
                        : 'N/A'
                );


            echo "<br>";


            $shown++;


            if ($shown >= 10) {

                break;
            }
        }
    }

} catch (
    Throwable $exception
) {

    candidateCalibrationEvidenceResult(
        false,
        'Real recommendation candidate inspection completed without exception.'
    );


    echo "<br>";
    echo "Exception: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "File: "
        . htmlspecialchars(
            $exception->getFile(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "Line: "
        . $exception->getLine()
        . "<br>";
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Recommendation Candidate Calibration Evidence Real Data Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}