<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Bootstrap Data Validator Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function bootstrapValidatorCheck(
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


function bootstrapValidatorSection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


function bootstrapValidatorSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Bootstrap Data Validator Test Summary<br>";
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
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario A: Class Contract'
);


bootstrapValidatorCheck(
    'BootstrapDataValidator exists.',
    class_exists(
        'BootstrapDataValidator'
    )
);


if (
    !class_exists(
        'BootstrapDataValidator'
    )
) {

    bootstrapValidatorSummary();

    exit;
}


/*
 * ============================================================
 * VALID BASELINE
 * ============================================================
 */

$validData = [

    'teams' => [
        [
            'id' => 1
        ]
    ],

    'elements' => [
        [
            'id' => 100
        ]
    ],

    'events' => [
        [
            'id' => 1
        ]
    ]
];


/*
 * ============================================================
 * SCENARIO B
 * VALID BOOTSTRAP DATA
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario B: Valid Bootstrap Data'
);


$validAccepted =
    true;


try {

    BootstrapDataValidator::validate(
        $validData
    );

} catch (
    Throwable $exception
) {

    $validAccepted =
        false;
}


bootstrapValidatorCheck(
    'Valid non-empty bootstrap data is accepted.',
    $validAccepted
);


/*
 * ============================================================
 * SCENARIO C
 * MISSING TEAMS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario C: Missing Teams'
);


$data =
    $validData;


unset(
    $data[
        'teams'
    ]
);


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Missing teams dataset is rejected.',
    $exceptionMessage !== null
);


bootstrapValidatorCheck(
    'Missing teams rejection preserves existing error meaning.',
    $exceptionMessage
    ===
    'FPL bootstrap data does not contain teams'
);


/*
 * ============================================================
 * SCENARIO D
 * NON-ARRAY TEAMS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario D: Non-Array Teams'
);


$data =
    $validData;


$data[
    'teams'
] =
    'invalid';


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Non-array teams dataset is rejected.',
    $exceptionMessage
    ===
    'FPL bootstrap data does not contain teams'
);


/*
 * ============================================================
 * SCENARIO E
 * EMPTY TEAMS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario E: Empty Teams'
);


$data =
    $validData;


$data[
    'teams'
] =
    [];


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Empty teams dataset is rejected.',
    $exceptionMessage !== null
);


bootstrapValidatorCheck(
    'Empty teams rejection explains that teams are empty.',
    $exceptionMessage
    ===
    'FPL bootstrap data contains no teams'
);


/*
 * ============================================================
 * SCENARIO F
 * MISSING PLAYERS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario F: Missing Players'
);


$data =
    $validData;


unset(
    $data[
        'elements'
    ]
);


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Missing players dataset is rejected.',
    $exceptionMessage
    ===
    'FPL bootstrap data does not contain players'
);


/*
 * ============================================================
 * SCENARIO G
 * NON-ARRAY PLAYERS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario G: Non-Array Players'
);


$data =
    $validData;


$data[
    'elements'
] =
    null;


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Non-array players dataset is rejected.',
    $exceptionMessage
    ===
    'FPL bootstrap data does not contain players'
);


/*
 * ============================================================
 * SCENARIO H
 * EMPTY PLAYERS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario H: Empty Players'
);


$data =
    $validData;


$data[
    'elements'
] =
    [];


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Empty players dataset is rejected.',
    $exceptionMessage !== null
);


bootstrapValidatorCheck(
    'Empty players rejection explains that players are empty.',
    $exceptionMessage
    ===
    'FPL bootstrap data contains no players'
);


/*
 * ============================================================
 * SCENARIO I
 * MISSING GAMEWEEKS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario I: Missing Gameweeks'
);


$data =
    $validData;


unset(
    $data[
        'events'
    ]
);


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Missing gameweeks dataset is rejected.',
    $exceptionMessage
    ===
    'FPL bootstrap data does not contain gameweeks'
);


/*
 * ============================================================
 * SCENARIO J
 * NON-ARRAY GAMEWEEKS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario J: Non-Array Gameweeks'
);


$data =
    $validData;


$data[
    'events'
] =
    false;


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Non-array gameweeks dataset is rejected.',
    $exceptionMessage
    ===
    'FPL bootstrap data does not contain gameweeks'
);


/*
 * ============================================================
 * SCENARIO K
 * EMPTY GAMEWEEKS
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario K: Empty Gameweeks'
);


$data =
    $validData;


$data[
    'events'
] =
    [];


$exceptionMessage =
    null;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    RuntimeException $exception
) {

    $exceptionMessage =
        $exception->getMessage();
}


bootstrapValidatorCheck(
    'Empty gameweeks dataset is rejected.',
    $exceptionMessage !== null
);


bootstrapValidatorCheck(
    'Empty gameweeks rejection explains that gameweeks are empty.',
    $exceptionMessage
    ===
    'FPL bootstrap data contains no gameweeks'
);


/*
 * ============================================================
 * SCENARIO L
 * UNRELATED BOOTSTRAP DATA
 * ============================================================
 */

bootstrapValidatorSection(
    'Scenario L: Unrelated Bootstrap Data'
);


$data =
    $validData;


$data[
    'element_types'
] = [];


$data[
    'phases'
] = [];


$unrelatedAccepted =
    true;


try {

    BootstrapDataValidator::validate(
        $data
    );

} catch (
    Throwable $exception
) {

    $unrelatedAccepted =
        false;
}


bootstrapValidatorCheck(
    'Validation is limited to required importer datasets.',
    $unrelatedAccepted
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

bootstrapValidatorSummary();