<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Application Error Presenter Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function applicationErrorPresenterCheck(
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
 * A. CLASS CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "A. Class Contract<br>";
echo "============================================<br>";


applicationErrorPresenterCheck(
    'ApplicationErrorPresenter class exists.',
    class_exists(
        'ApplicationErrorPresenter'
    )
);


if (
    class_exists(
        'ApplicationErrorPresenter'
    )
) {

    applicationErrorPresenterCheck(
        'ApplicationErrorPresenter exposes present().',
        method_exists(
            'ApplicationErrorPresenter',
            'present'
        )
    );

} else {

    applicationErrorPresenterCheck(
        'ApplicationErrorPresenter exposes present().',
        false
    );
}


/*
 * ============================================================
 * B. DEVELOPMENT BEHAVIOUR
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Development Behaviour<br>";
echo "============================================<br>";


$developmentMessage =
    null;


if (
    class_exists(
        'ApplicationErrorPresenter'
    )
    &&
    method_exists(
        'ApplicationErrorPresenter',
        'present'
    )
) {

    $developmentPresenter =
        new ApplicationErrorPresenter(
            'development'
        );


    $developmentMessage =
        $developmentPresenter->present(
            new RuntimeException(
                'Sensitive diagnostic detail.'
            ),
            'Something went wrong.'
        );
}


applicationErrorPresenterCheck(
    'Development environment preserves the diagnostic exception message.',
    $developmentMessage
    ===
    'Sensitive diagnostic detail.'
);


/*
 * ============================================================
 * C. PRODUCTION BEHAVIOUR
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "C. Production Behaviour<br>";
echo "============================================<br>";


$productionMessage =
    null;


if (
    class_exists(
        'ApplicationErrorPresenter'
    )
    &&
    method_exists(
        'ApplicationErrorPresenter',
        'present'
    )
) {

    $productionPresenter =
        new ApplicationErrorPresenter(
            'production'
        );


    $productionMessage =
        $productionPresenter->present(
            new RuntimeException(
                'Sensitive diagnostic detail.'
            ),
            'Something went wrong.'
        );
}


applicationErrorPresenterCheck(
    'Production environment returns the safe user-facing message.',
    $productionMessage
    ===
    'Something went wrong.'
);


applicationErrorPresenterCheck(
    'Production environment does not expose the exception message.',
    $productionMessage
    !==
    'Sensitive diagnostic detail.'
);


/*
 * ============================================================
 * D. ENVIRONMENT NORMALISATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "D. Environment Normalisation<br>";
echo "============================================<br>";


$normalisedProductionMessage =
    null;


if (
    class_exists(
        'ApplicationErrorPresenter'
    )
    &&
    method_exists(
        'ApplicationErrorPresenter',
        'present'
    )
) {

    $normalisedPresenter =
        new ApplicationErrorPresenter(
            ' Production '
        );


    $normalisedProductionMessage =
        $normalisedPresenter->present(
            new RuntimeException(
                'Sensitive diagnostic detail.'
            ),
            'Something went wrong.'
        );
}


applicationErrorPresenterCheck(
    'Production environment matching is case-insensitive and whitespace-safe.',
    $normalisedProductionMessage
    ===
    'Something went wrong.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Application Error Presenter Test Summary<br>";
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