<?php

echo "============================================<br>";
echo "Player Page Production Error Handling Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function playerPageProductionErrorCheck(
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


$pagePath =
    __DIR__
    . '/../public/player.php';


$pageSource =
    file_get_contents(
        $pagePath
    );


playerPageProductionErrorCheck(
    'Player page source can be read.',
    $pageSource !== false
);


if ($pageSource === false) {

    echo "<br>";
    echo "============================================<br>";
    echo "Player Page Production Error Handling Test Summary<br>";
    echo "============================================<br>";
    echo "Passed: {$passed}<br>";
    echo "Failed: {$failed}<br><br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


/*
 * ============================================================
 * SHARED ERROR PRESENTER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Shared Error Presenter<br>";
echo "============================================<br>";


playerPageProductionErrorCheck(
    'Player page creates the shared ApplicationErrorPresenter.',
    str_contains(
        $pageSource,
        'new ApplicationErrorPresenter'
    )
);


playerPageProductionErrorCheck(
    'Player page supplies the configured application environment.',
    str_contains(
        $pageSource,
        "\$config['environment']"
    )
    ||
    preg_match(
        "/\\\$config\\s*\\[\\s*'environment'\\s*\\]/s",
        $pageSource
    ) === 1
);


/*
 * ============================================================
 * PROFILE FAILURE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Profile Failure<br>";
echo "============================================<br>";


playerPageProductionErrorCheck(
    'Player profile failure is passed through the shared error presenter.',
    str_contains(
        $pageSource,
        '$errorPresenter->present('
    )
);


playerPageProductionErrorCheck(
    'Player profile failure has a safe user-facing fallback message.',
    str_contains(
        $pageSource,
        'Unable to load this Player Intelligence profile at the moment.'
    )
);


playerPageProductionErrorCheck(
    'Player profile catch does not assign the raw exception message directly.',
    !preg_match(
        '/\\$pageError\\s*=\\s*\\$exception\\s*->\\s*getMessage\\s*\\(\\s*\\)\\s*;/s',
        $pageSource
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Page Production Error Handling Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}