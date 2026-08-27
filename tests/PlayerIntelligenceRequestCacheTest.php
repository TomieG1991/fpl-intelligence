<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Intelligence Request Cache Test<br>";
echo "============================================<br><br>";


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

$database =
    new Database();


$db =
    $database
        ->getConnection();


/*
 * ============================================================
 * SERVICE
 * ============================================================
 *
 * Both calls deliberately use the SAME service instance.
 *
 * Request-scoped memoization belongs to the service instance,
 * so this is the behaviour we need to verify.
 */

$service =
    new PlayerIntelligenceService(
        $db
    );


/*
 * ============================================================
 * FIRST CALL
 * ============================================================
 */

$firstStart =
    microtime(
        true
    );


$firstResult =
    $service
        ->getAllPlayerSummaries();


$firstDuration =
    microtime(
        true
    )
    -
    $firstStart;


/*
 * ============================================================
 * SECOND CALL
 * ============================================================
 */

$secondStart =
    microtime(
        true
    );


$secondResult =
    $service
        ->getAllPlayerSummaries();


$secondDuration =
    microtime(
        true
    )
    -
    $secondStart;


/*
 * ============================================================
 * ASSERTIONS
 * ============================================================
 */

$failures =
    [];


/*
 * The production collection must still be returned.
 */
if (
    !is_array(
        $firstResult
    )
    ||
    empty(
        $firstResult
    )
) {

    $failures[] =
        'First call did not return a populated player summary collection.';
}


/*
 * Reusing the service must not change the number of players.
 */
if (
    count(
        $firstResult
    )
    !==
    count(
        $secondResult
    )
) {

    $failures[] =
        'Repeated calls returned different player counts.';
}


/*
 * The cached collection must be exactly equivalent to the
 * collection produced by the initial calculation.
 */
if (
    $firstResult
    !==
    $secondResult
) {

    $failures[] =
        'Repeated calls returned different Player Intelligence summaries.';
}


/*
 * ============================================================
 * RESULT
 * ============================================================
 */

echo
    'First Call: '
    . number_format(
        $firstDuration,
        6
    )
    . " seconds<br>";


echo
    'Second Call: '
    . number_format(
        $secondDuration,
        6
    )
    . " seconds<br>";


echo
    'Players: '
    . count(
        $firstResult
    )
    . "<br><br>";


if (
    !empty(
        $failures
    )
) {

    echo "============================================<br>";
    echo "Player Intelligence Request Cache Test Summary<br>";
    echo "============================================<br><br>";


    foreach (
        $failures
        as $failure
    ) {

        echo
            "FAIL: "
            . htmlspecialchars(
                $failure,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    echo "<br>RESULT: TESTS FAILED ❌<br>";


    exit(
        1
    );
}


echo "============================================<br>";
echo "Player Intelligence Request Cache Test Summary<br>";
echo "============================================<br><br>";

echo "PASS: repeated calls preserve identical Player Intelligence summaries<br>";
echo "PASS: repeated calls preserve player count<br>";

echo "<br>RESULT: TESTS PASSED ✅<br>";


exit(
    0
);