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

$playerRepository = new PlayerRepository(
    $database->getConnection()
);

$players = $playerRepository->getAll();

echo "<p>Total Players Loaded: " . count($players) . "</p>";

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