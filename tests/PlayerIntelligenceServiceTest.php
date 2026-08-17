<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function testPass(
    string $message,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . $message
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . $message
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Player Intelligence Service Test<br>";
echo "============================================<br><br>";


try {

    $database =
        new Database();


    $service =
        new PlayerIntelligenceService(
            $database->getConnection()
        );


    $players =
        $service
            ->getAllPlayerSummaries();


} catch (Throwable $exception) {

    echo "SETUP FAILED ❌<br>";

    echo htmlspecialchars(
        $exception->getMessage()
    );

    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * PLAYER LIST
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Player Summaries<br>";
echo "============================================<br>";


testPass(
    'Player summaries return an array',
    is_array(
        $players
    )
);


testPass(
    'Player summaries are not empty',
    !empty(
        $players
    )
);


/*
 * ============================================================
 * FIND A VALID PLAYER
 * ============================================================
 */

$testPlayer =
    null;


foreach ($players as $player) {

    if (
        isset(
            $player['player_id']
        )
        &&
        (int) $player['player_id'] > 0
    ) {

        $testPlayer =
            $player;

        break;
    }
}


testPass(
    'A valid test player was found',
    $testPlayer !== null
);


if ($testPlayer === null) {

    echo "<br>Unable to continue without a valid player.<br>";

    exit;
}


$playerId =
    (int) $testPlayer['player_id'];


/*
 * ============================================================
 * SCENARIO B
 * COMPLETE PLAYER PROFILE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Complete Player Profile<br>";
echo "============================================<br>";


$profile =
    $service->getPlayerProfile(
        $playerId
    );


testPass(
    'Player profile returns an array',
    is_array(
        $profile
    )
);


testPass(
    'Player section exists',
    isset(
        $profile['player']
    )
);


testPass(
    'Team section exists',
    isset(
        $profile['team']
    )
);


testPass(
    'Performance section exists',
    isset(
        $profile['performance']
    )
);


testPass(
    'Strength section exists',
    isset(
        $profile['strength']
    )
);


testPass(
    'Value section exists',
    isset(
        $profile['value']
    )
);


testPass(
    'Availability section exists',
    isset(
        $profile['availability']
    )
);


testPass(
    'Intelligence section exists',
    isset(
        $profile['intelligence']
    )
);


testPass(
    'Summary section exists',
    isset(
        $profile['summary']
    )
);


testPass(
    'Fixtures section exists',
    isset(
        $profile['fixtures']
    )
);

testPass(
    'Assessment section exists',
    isset(
        $profile['assessment']
    )
);


/*
 * ============================================================
 * SCENARIO C
 * PLAYER IDENTITY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Player Identity<br>";
echo "============================================<br>";


testPass(
    'Player ID matches requested player',
    (
        (int) (
            $profile[
                'player'
            ]['player_id']
            ?? 0
        )
    )
    ===
    $playerId
);


testPass(
    'Player name exists',
    !empty(
        $profile[
            'player'
        ]['name']
        ?? null
    )
);


testPass(
    'Player position exists',
    !empty(
        $profile[
            'player'
        ]['position']
        ?? null
    )
);


/*
 * ============================================================
 * SCENARIO D
 * TEAM INFORMATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Team Information<br>";
echo "============================================<br>";


testPass(
    'Team ID exists',
    (
        (int) (
            $profile[
                'team'
            ]['team_id']
            ?? 0
        )
    )
    > 0
);


testPass(
    'Team name exists',
    !empty(
        $profile[
            'team'
        ]['name']
        ?? null
    )
);


/*
 * ============================================================
 * SCENARIO E
 * INTELLIGENCE INFORMATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Intelligence Information<br>";
echo "============================================<br>";


testPass(
    'Summary strength rating exists when available',
    array_key_exists(
        'strength_rating',
        $profile['summary']
    )
);


testPass(
    'Summary fixture rating exists',
    array_key_exists(
        'fixture_rating',
        $profile['summary']
    )
);


testPass(
    'Summary intelligence score exists',
    array_key_exists(
        'intelligence_score',
        $profile['summary']
    )
);


testPass(
    'Summary intelligence label exists',
    array_key_exists(
        'intelligence_label',
        $profile['summary']
    )
);


/*
 * ============================================================
 * SCENARIO F
 * FIXTURE PROFILE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Fixture Profile<br>";
echo "============================================<br>";


testPass(
    'Fixture rating field exists',
    array_key_exists(
        'rating',
        $profile['fixtures']
    )
);


testPass(
    'Rolling averages exist',
    isset(
        $profile[
            'fixtures'
        ]['rolling_averages']
    )
);


testPass(
    'Upcoming fixtures exist',
    isset(
        $profile[
            'fixtures'
        ]['upcoming']
    )
    &&
    is_array(
        $profile[
            'fixtures'
        ]['upcoming']
    )
);


testPass(
    'Fixture count matches upcoming fixture array',
    (
        (int) (
            $profile[
                'fixtures'
            ]['fixture_count']
            ?? -1
        )
    )
    ===
    count(
        $profile[
            'fixtures'
        ]['upcoming']
    )
);

/*
 * ============================================================
 * SCENARIO G
 * PLAYER ASSESSMENT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Player Assessment<br>";
echo "============================================<br>";


testPass(
    'Assessment verdict exists',
    array_key_exists(
        'verdict',
        $profile['assessment']
    )
);


testPass(
    'Assessment verdict key exists',
    array_key_exists(
        'verdict_key',
        $profile['assessment']
    )
);


testPass(
    'Assessment summary exists',
    array_key_exists(
        'summary',
        $profile['assessment']
    )
);


testPass(
    'Assessment strengths exist',
    isset(
        $profile[
            'assessment'
        ]['strengths']
    )
    &&
    is_array(
        $profile[
            'assessment'
        ]['strengths']
    )
);


testPass(
    'Assessment concerns exist',
    isset(
        $profile[
            'assessment'
        ]['concerns']
    )
    &&
    is_array(
        $profile[
            'assessment'
        ]['concerns']
    )
);


testPass(
    'Assessment components exist',
    isset(
        $profile[
            'assessment'
        ]['components']
    )
    &&
    is_array(
        $profile[
            'assessment'
        ]['components']
    )
);


/*
 * ============================================================
 * SCENARIO H
 * INVALID PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Invalid Player Handling<br>";
echo "============================================<br>";


testPass(
    'Zero player ID returns null',
    $service->getPlayerProfile(
        0
    )
    === null
);


testPass(
    'Negative player ID returns null',
    $service->getPlayerProfile(
        -1
    )
    === null
);


testPass(
    'Unknown player ID returns null',
    $service->getPlayerProfile(
        999999999
    )
    === null
);




/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Player Intelligence Service Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}