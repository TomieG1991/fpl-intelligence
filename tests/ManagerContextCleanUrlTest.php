<?php

echo "============================================<br>";
echo "Manager Context Clean URL Test<br>";
echo "============================================<br><br>";

$passed = 0;
$failed = 0;

function cleanUrlCheck(
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


$servicePath =
    __DIR__
    . '/../classes/ManagerContextService.php';

$integrationPath =
    __DIR__
    . '/../public/includes/manager-context.php';

$sidebarPath =
    __DIR__
    . '/../public/includes/sidebar.php';

$dashboardPath =
    __DIR__
    . '/../public/index.php';


$serviceSource =
    is_file($servicePath)
        ? file_get_contents($servicePath)
        : false;

$integrationSource =
    is_file($integrationPath)
        ? file_get_contents($integrationPath)
        : false;

$sidebarSource =
    is_file($sidebarPath)
        ? file_get_contents($sidebarPath)
        : false;

$dashboardSource =
    is_file($dashboardPath)
        ? file_get_contents($dashboardPath)
        : false;


$serviceSource =
    is_string($serviceSource)
        ? $serviceSource
        : '';

$integrationSource =
    is_string($integrationSource)
        ? $integrationSource
        : '';

$sidebarSource =
    is_string($sidebarSource)
        ? $sidebarSource
        : '';

$dashboardSource =
    is_string($dashboardSource)
        ? $dashboardSource
        : '';


echo "============================================<br>";
echo "A. Session Is Authoritative<br>";
echo "============================================<br>";

cleanUrlCheck(
    'Manager context integration starts or resumes the PHP session',
    strpos(
        $integrationSource,
        'session_start('
    ) !== false
);

cleanUrlCheck(
    'Manager context service retains the session entry key',
    strpos(
        $serviceSource,
        'fpl_entry_id'
    ) !== false
);

cleanUrlCheck(
    'Manager context integration resolves the active manager through the shared service',
    strpos(
        $integrationSource,
        '->resolveEntryId('
    ) !== false
);

echo "<br>";


echo "============================================<br>";
echo "B. Manager Selection Uses POST<br>";
echo "============================================<br>";

cleanUrlCheck(
    'Dashboard manager form submits with POST',
    preg_match(
        '/<form\b[^>]*method="post"[^>]*>/i',
        $dashboardSource
    ) === 1
);

cleanUrlCheck(
    'Dashboard manager form still submits entry_id',
    strpos(
        $dashboardSource,
        'name="entry_id"'
    ) !== false
);

cleanUrlCheck(
    'Manager context reads entry_id from POST',
    preg_match(
        '/\$_POST\s*\[\s*[\'"]entry_id[\'"]\s*\]/',
        $integrationSource
    ) === 1
);

cleanUrlCheck(
    'Manager context no longer reads entry_id from GET',
    preg_match(
        '/\$_GET\s*\[\s*[\'"]entry_id[\'"]\s*\]/',
        $integrationSource
    ) === 0
);

echo "<br>";


echo "============================================<br>";
echo "C. Clean Navigation URLs<br>";
echo "============================================<br>";

cleanUrlCheck(
    'Sidebar no longer builds navigation URLs with manager entry context',
    strpos(
        $sidebarSource,
        '->buildUrl('
    ) === false
);

cleanUrlCheck(
    'Dashboard no longer builds specialist URLs with manager entry context',
    strpos(
        $dashboardSource,
        '->buildUrl('
    ) === false
);

cleanUrlCheck(
    'Sidebar does not contain entry_id query parameters',
    strpos(
        $sidebarSource,
        'entry_id='
    ) === false
);

cleanUrlCheck(
    'Dashboard does not contain entry_id query parameters',
    strpos(
        $dashboardSource,
        'entry_id='
    ) === false
);

echo "<br>";


echo "============================================<br>";
echo "D. Clean Specialist Destinations<br>";
echo "============================================<br>";

$cleanDestinations = [
    'gameweek.php',
    'transfers.php',
    'squad.php',
    'chips.php',
    'health.php',
    'fixtures.php',
    'players.php'
];

foreach (
    $cleanDestinations
    as $cleanDestination
) {

    cleanUrlCheck(
        'Dashboard retains clean '
        . $cleanDestination
        . ' navigation',
        strpos(
            $dashboardSource,
            "'"
            . $cleanDestination
            . "'"
        ) !== false
        ||
        strpos(
            $dashboardSource,
            '"'
            . $cleanDestination
            . '"'
        ) !== false
    );
}

echo "<br>";


echo "============================================<br>";
echo "E. Session Persistence Without URL State<br>";
echo "============================================<br>";

cleanUrlCheck(
    'Manager context passes the submitted manager to the shared session resolver',
    preg_match(
        '/->resolveEntryId\s*\(\s*'
        . '\$requestedManagerEntryId\s*,\s*'
        . '\$_SESSION\s*\)/s',
        $integrationSource
    ) === 1
);

cleanUrlCheck(
    'Manager context does not require a Location redirect to remove query state',
    strpos(
        $integrationSource,
        'Location:'
    ) === false
);

cleanUrlCheck(
    'Manager context does not manipulate QUERY_STRING',
    strpos(
        $integrationSource,
        'QUERY_STRING'
    ) === false
);

echo "<br>";


echo "============================================<br>";
echo "F. No Permanent Test Manager<br>";
echo "============================================<br>";

cleanUrlCheck(
    'Dashboard does not hard-code the current local testing entry ID',
    strpos(
        $dashboardSource,
        '2702264'
    ) === false
);

cleanUrlCheck(
    'Manager context integration does not hard-code the current local testing entry ID',
    strpos(
        $integrationSource,
        '2702264'
    ) === false
);

cleanUrlCheck(
    'Manager context service does not hard-code the current local testing entry ID',
    strpos(
        $serviceSource,
        '2702264'
    ) === false
);

echo "<br>";


echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";

echo "Assertions passed: "
    . $passed
    . "<br>";

echo "Assertions failed: "
    . $failed
    . "<br><br>";

if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}
