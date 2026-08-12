<?php

require_once '../classes/autoload.php';

echo "<h1>FPL Intelligence v1.0</h1>";


// Database health check

try {

    $database = new Database();

    echo "<p>Database: CONNECTED ✅</p>";

} catch (Exception $e) {

    echo "<p>Database: FAILED ❌</p>";

    echo $e->getMessage();

    exit;
}


// FPL API health check

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

} catch (Exception $e) {

    echo "<p>FPL API: FAILED ❌</p>";

}