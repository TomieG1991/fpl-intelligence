```php
<?php

require_once '../classes/autoload.php';


echo "<h1>FPL Intelligence v1.0</h1>";


// Database test

try {

    $database = new Database();

    echo "<p>Database: CONNECTED ✅</p>";

}
catch(Exception $e){

    echo "<p>Database: FAILED ❌</p>";

    echo $e->getMessage();

}


// API test

try {

    $fpl = new FPLApi();

    $data = $fpl->getBootstrapData();

    echo "<p>FPL API: CONNECTED ✅</p>";

    echo "<p>Players Found: "
        . count($data['elements'])
        . "</p>";

    echo "<p>Teams Found: "
        . count($data['teams'])
        . "</p>";

}
catch(Exception $e){

    echo "<p>FPL API: FAILED ❌</p>";

    echo $e->getMessage();

}


// Player data

$playerRepository = new PlayerRepository(
    $database->getConnection()
);

$players = $playerRepository->getAll();

echo "<p>Total Players Loaded: "
    . count($players)
    . "</p>";


$mostExpensive = $playerRepository->getMostExpensive();

echo "<h2>Top 10 Most Expensive Players</h2>";

echo "<table border='1' cellpadding='5'>";

echo "<tr>
        <th>Player</th>
        <th>Price</th>
        <th>Goals</th>
        <th>Assists</th>
      </tr>";


foreach ($mostExpensive as $player) {

    echo "<tr>";

    echo "<td>{$player['web_name']}</td>";

    echo "<td>£{$player['price']}m</td>";

    echo "<td>{$player['goals']}</td>";

    echo "<td>{$player['assists']}</td>";

    echo "</tr>";

}

echo "</table>";


// Team strength data

$teamRepository = new TeamRepository(
    $database->getConnection()
);

$teams = $teamRepository->getAll();

$teamStrength = new TeamStrength();

$teamStrengths = $teamStrength->calculateTeamStrengths(
    $teams
);


// Fixture intelligence

$fixtureRepository = new FixtureRepository(
    $database->getConnection()
);

$fixtures = $fixtureRepository->getAll();

$fixtureIntelligence = new FixtureIntelligence();


echo "<h2>Fixture Intelligence</h2>";

echo "<table border='1' cellpadding='5'>";

echo "<tr>
        <th>Gameweek</th>
        <th>Home Team</th>
        <th>Away Team</th>
        <th>Home Baseline</th>
        <th>Away Baseline</th>
        <th>Matchup</th>
      </tr>";


foreach (array_slice($fixtures, 0, 10) as $fixture) {

    $homeTeam = $teamStrengths[
        $fixture['home_team_id']
    ];

    $awayTeam = $teamStrengths[
        $fixture['away_team_id']
    ];


    $matchup = $fixtureIntelligence->calculateMatchup(
        $homeTeam['home'],
        $awayTeam['away']
    );


    echo "<tr>";

    echo "<td>{$fixture['gameweek']}</td>";

    echo "<td>{$homeTeam['name']}</td>";

    echo "<td>{$awayTeam['name']}</td>";

    echo "<td>" . round($homeTeam['home'], 2) . "</td>";

    echo "<td>" . round($awayTeam['away'], 2) . "</td>";

    echo "<td>" . round($matchup, 2) . "</td>";

    echo "</tr>";

}


echo "</table>"; 