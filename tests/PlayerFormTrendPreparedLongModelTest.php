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
 * TEST DOUBLE
 * ============================================================
 *
 * Record every PlayerForm build request so we can prove that
 * PlayerFormTrend does not rebuild an explicitly supplied long
 * model.
 */

class PreparedLongModelPlayerForm extends PlayerForm
{
    public array $calls = [];


    public function __construct()
    {
        /*
         * Deliberately do not call the parent constructor.
         *
         * buildModel() is overridden completely, so no
         * PlayerFormHistory dependency is required.
         */
    }


    public function buildModel(
        int $playerId,
        ?string $position = null,
        int $fixtureLimit = 5,
        int $appearanceLimit = 5
    ): array {

        $this->calls[] = [

            'player_id' =>
                $playerId,

            'position' =>
                $position,

            'fixture_limit' =>
                $fixtureLimit,

            'appearance_limit' =>
                $appearanceLimit
        ];


        return [

            'player_id' =>
                $playerId,

            'position' =>
                $position ?? 'MID',

            'form_rating' =>
                60.0,

            'performance_rating' =>
                60.0,

            'participation_rate' =>
                100.0,

            'fixture_sample_size' =>
                $fixtureLimit,

            'appearance_sample_size' =>
                $appearanceLimit,

            'weighted_metrics' => [

                'minutes_per_fixture' =>
                    90.0
            ]
        ];
    }
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Player Form Trend Prepared Long Model Test<br>";
echo "============================================<br><br>";


$playerForm =
    new PreparedLongModelPlayerForm();


$trend =
    new PlayerFormTrend(
        $playerForm
    );


$preparedLongModel = [

    'player_id' =>
        123,

    'position' =>
        'MID',

    'form_rating' =>
        50.0,

    'performance_rating' =>
        50.0,

    'participation_rate' =>
        80.0,

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'weighted_metrics' => [

        'minutes_per_fixture' =>
            70.0
    ]
];


/*
 * ============================================================
 * PREPARED LONG MODEL
 * ============================================================
 */

try {

    $result =
        $trend
            ->buildModel(
                123,
                'MID',
                $preparedLongModel
            );


    testPass(
        'Prepared long model is accepted by Player Form Trend',
        is_array(
            $result
        )
    );


    testPass(
        'Only the short Player Form model is calculated',
        count(
            $playerForm->calls
        )
        ===
        1
    );


    $onlyCall =
        $playerForm->calls[
            0
        ]
        ?? [];


    testPass(
        'Remaining Player Form calculation uses the 3-fixture window',
        (
            (int) (
                $onlyCall[
                    'fixture_limit'
                ]
                ?? 0
            )
        )
        ===
        3
    );


    testPass(
        'Remaining Player Form calculation uses the 3-appearance window',
        (
            (int) (
                $onlyCall[
                    'appearance_limit'
                ]
                ?? 0
            )
        )
        ===
        3
    );


    testPass(
        'Prepared long model supplies the long Form rating',
        (
            (float) (
                $result[
                    'long_form_rating'
                ]
                ?? -1
            )
        )
        ===
        50.0
    );


    testPass(
        'Prepared long model supplies the long Performance rating',
        (
            (float) (
                $result[
                    'long_performance_rating'
                ]
                ?? -1
            )
        )
        ===
        50.0
    );


    testPass(
        'Prepared long model supplies the long Participation Rate',
        (
            (float) (
                $result[
                    'long_participation_rate'
                ]
                ?? -1
            )
        )
        ===
        80.0
    );


    testPass(
        'Prepared long model supplies the long Minutes per Fixture',
        (
            (float) (
                $result[
                    'long_minutes_per_fixture'
                ]
                ?? -1
            )
        )
        ===
        70.0
    );


    testPass(
        'Prepared long model is preserved in trend diagnostics',
        (
            $result[
                'long_model'
            ]
            ?? null
        )
        ===
        $preparedLongModel
    );

} catch (
    Throwable $exception
) {

    echo "EXPECTED RED: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Summary<br>";
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