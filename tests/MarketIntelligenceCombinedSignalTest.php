<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Combined Signal Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function marketCombinedSignalCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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
 * SCENARIO A
 * SERVICE FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Service Foundation<br>";
echo "============================================<br>";


marketCombinedSignalCheck(
    'MarketIntelligenceService class exists',
    class_exists(
        'MarketIntelligenceService'
    )
);


$database =
    new Database();


$db =
    $database
        ->getConnection();


$service =
    new MarketIntelligenceService(
        $db
    );


marketCombinedSignalCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


marketCombinedSignalCheck(
    'Service exposes buildCombinedMarketSignal()',
    method_exists(
        $service,
        'buildCombinedMarketSignal'
    )
);


if (
    !method_exists(
        $service,
        'buildCombinedMarketSignal'
    )
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "MarketIntelligenceService::buildCombinedMarketSignal()<br><br>";

    echo "Required classifications:<br>";
    echo "- Strong Rising<br>";
    echo "- Rising<br>";
    echo "- Stable<br>";
    echo "- Falling<br>";
    echo "- Strong Falling<br>";
    echo "- Mixed<br>";
    echo "- Insufficient Evidence<br><br>";


    echo "============================================<br>";
    echo "Market Intelligence Combined Signal Test Summary<br>";
    echo "============================================<br>";


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    echo "RESULT: TESTS FAILED ❌";

    exit;
}


echo "<br>";


/*
 * ============================================================
 * REFLECTION
 * ============================================================
 */

$reflection =
    new ReflectionClass(
        MarketIntelligenceService::class
    );


$combinedSignalMethod =
    $reflection
        ->getMethod(
            'buildCombinedMarketSignal'
        );


$combinedSignalMethod
    ->setAccessible(
        true
    );


/*
 * ============================================================
 * SCENARIO B
 * STRONG RISING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Strong Rising<br>";
echo "============================================<br>";


$strongRising =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ]
        );


marketCombinedSignalCheck(
    'All three positive signals classify Strong Rising',
    (
        $strongRising[
            'classification'
        ]
        ?? null
    )
    ===
    'Strong Rising'
);


marketCombinedSignalCheck(
    'Strong Rising result reports three available signals',
    (
        (int) (
            $strongRising[
                'available_signals'
            ]
            ?? 0
        )
    )
    ===
    3
);


marketCombinedSignalCheck(
    'Strong Rising result reports three rising signals',
    (
        (int) (
            $strongRising[
                'rising_signals'
            ]
            ?? 0
        )
    )
    ===
    3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * RISING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Rising<br>";
echo "============================================<br>";


$rising =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Stable'
            ]
        );


marketCombinedSignalCheck(
    'Two rising and one stable signal classify Rising',
    (
        $rising[
            'classification'
        ]
        ?? null
    )
    ===
    'Rising'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * STRONG FALLING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Strong Falling<br>";
echo "============================================<br>";


$strongFalling =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Falling'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Falling'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Falling'
            ]
        );


marketCombinedSignalCheck(
    'All three negative signals classify Strong Falling',
    (
        $strongFalling[
            'classification'
        ]
        ?? null
    )
    ===
    'Strong Falling'
);


marketCombinedSignalCheck(
    'Strong Falling result reports three falling signals',
    (
        (int) (
            $strongFalling[
                'falling_signals'
            ]
            ?? 0
        )
    )
    ===
    3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * FALLING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Falling<br>";
echo "============================================<br>";


$falling =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Falling'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Stable'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Falling'
            ]
        );


marketCombinedSignalCheck(
    'Two falling and one stable signal classify Falling',
    (
        $falling[
            'classification'
        ]
        ?? null
    )
    ===
    'Falling'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * STABLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Stable<br>";
echo "============================================<br>";


$stable =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Stable'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Stable'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Stable'
            ]
        );


marketCombinedSignalCheck(
    'Three neutral signals classify Stable',
    (
        $stable[
            'classification'
        ]
        ?? null
    )
    ===
    'Stable'
);


marketCombinedSignalCheck(
    'Stable result reports three stable signals',
    (
        (int) (
            $stable[
                'stable_signals'
            ]
            ?? 0
        )
    )
    ===
    3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * MIXED SIGNALS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Mixed Signals<br>";
echo "============================================<br>";


$mixed =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Falling'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Stable'
            ]
        );


marketCombinedSignalCheck(
    'Material positive and negative evidence classifies Mixed',
    (
        $mixed[
            'classification'
        ]
        ?? null
    )
    ===
    'Mixed'
);


marketCombinedSignalCheck(
    'Mixed result preserves one rising signal',
    (
        (int) (
            $mixed[
                'rising_signals'
            ]
            ?? 0
        )
    )
    ===
    1
);


marketCombinedSignalCheck(
    'Mixed result preserves one falling signal',
    (
        (int) (
            $mixed[
                'falling_signals'
            ]
            ?? 0
        )
    )
    ===
    1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * INSUFFICIENT EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Insufficient Evidence<br>";
echo "============================================<br>";


$insufficient =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Insufficient Historical Data',

                'direction' =>
                    'Unavailable'
            ],

            [
                'status' =>
                    'Insufficient Historical Data',

                'direction' =>
                    'Unavailable'
            ],

            [
                'status' =>
                    'Insufficient Historical Data',

                'direction' =>
                    'Unavailable'
            ]
        );


marketCombinedSignalCheck(
    'No available component signals classify Insufficient Evidence',
    (
        $insufficient[
            'classification'
        ]
        ?? null
    )
    ===
    'Insufficient Evidence'
);


marketCombinedSignalCheck(
    'Insufficient result reports zero available signals',
    (
        (int) (
            $insufficient[
                'available_signals'
            ]
            ?? -1
        )
    )
    ===
    0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * PARTIAL EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Partial Evidence<br>";
echo "============================================<br>";


$partialPositive =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Insufficient Historical Data',

                'direction' =>
                    'Unavailable'
            ]
        );


marketCombinedSignalCheck(
    'Two available positive signals can classify Rising',
    (
        $partialPositive[
            'classification'
        ]
        ?? null
    )
    ===
    'Rising'
);


marketCombinedSignalCheck(
    'Two-signal evidence does not overstate Strong Rising',
    (
        $partialPositive[
            'classification'
        ]
        ?? null
    )
    !==
    'Strong Rising'
);


$singleAvailable =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Insufficient Historical Data',

                'direction' =>
                    'Unavailable'
            ],

            [
                'status' =>
                    'Insufficient Historical Data',

                'direction' =>
                    'Unavailable'
            ]
        );


marketCombinedSignalCheck(
    'One available signal is not enough for a market classification',
    (
        $singleAvailable[
            'classification'
        ]
        ?? null
    )
    ===
    'Insufficient Evidence'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * SIGNAL METADATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Signal Metadata<br>";
echo "============================================<br>";


$metadataResult =
    $combinedSignalMethod
        ->invoke(
            $service,

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Stable'
            ],

            [
                'status' =>
                    'Available',

                'direction' =>
                    'Rising'
            ]
        );


marketCombinedSignalCheck(
    'Combined signal exposes available signal count',
    array_key_exists(
        'available_signals',
        $metadataResult
    )
);


marketCombinedSignalCheck(
    'Combined signal exposes rising signal count',
    array_key_exists(
        'rising_signals',
        $metadataResult
    )
);


marketCombinedSignalCheck(
    'Combined signal exposes falling signal count',
    array_key_exists(
        'falling_signals',
        $metadataResult
    )
);


marketCombinedSignalCheck(
    'Combined signal exposes stable signal count',
    array_key_exists(
        'stable_signals',
        $metadataResult
    )
);


marketCombinedSignalCheck(
    'Combined signal exposes classification',
    array_key_exists(
        'classification',
        $metadataResult
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * REAL DATABASE BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Real Database Behaviour<br>";
echo "============================================<br>";


$playerRepository =
    new PlayerRepository(
        $db
    );


$players =
    $playerRepository
        ->getAll();


$realPlayer =
    null;


foreach (
    $players
    as $player
) {

    $playerId =
        (int) (
            $player[
                'id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
    ) {

        continue;
    }


    $result =
        $service
            ->getPlayerMarketIntelligence(
                $playerId
            );


    if (
        (
            $result[
                'status'
            ]
            ?? null
        )
        !==
        'Available'
    ) {

        continue;
    }


    $realPlayer =
        [

            'player' =>
                $player,

            'result' =>
                $result
        ];

    break;
}


marketCombinedSignalCheck(
    'A real player market intelligence result resolves',
    is_array(
        $realPlayer
    )
);


if (
    is_array(
        $realPlayer
    )
) {

    $realResult =
        $realPlayer[
            'result'
        ];


    $realCombined =
        $combinedSignalMethod
            ->invoke(
                $service,

                $realResult[
                    'price_movement'
                ]
                ?? [],

                $realResult[
                    'ownership_movement'
                ]
                ?? [],

                $realResult[
                    'transfer_momentum'
                ]
                ?? []
            );


    echo "Player: "
        . htmlspecialchars(
            (string) (
                $realPlayer[
                    'player'
                ][
                    'web_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Price Status: "
        . htmlspecialchars(
            (string) (
                $realResult[
                    'price_movement'
                ][
                    'status'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Ownership Status: "
        . htmlspecialchars(
            (string) (
                $realResult[
                    'ownership_movement'
                ][
                    'status'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Transfer Status: "
        . htmlspecialchars(
            (string) (
                $realResult[
                    'transfer_momentum'
                ][
                    'status'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Combined Classification: "
        . htmlspecialchars(
            (string) (
                $realCombined[
                    'classification'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    marketCombinedSignalCheck(
        'Current real early-season market state remains Insufficient Evidence',
        (
            $realCombined[
                'classification'
            ]
            ?? null
        )
        ===
        'Insufficient Evidence'
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * COMBINED SIGNAL DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Combined Signal Diagnostic<br>";
echo "============================================<br><br>";


echo "Rising + Rising + Rising → Strong Rising<br>";
echo "Rising + Rising + Stable → Rising<br>";
echo "Falling + Falling + Falling → Strong Falling<br>";
echo "Falling + Falling + Stable → Falling<br>";
echo "Stable + Stable + Stable → Stable<br>";
echo "Rising + Falling + Stable → Mixed<br>";
echo "No usable signals → Insufficient Evidence<br>";
echo "One usable signal → Insufficient Evidence<br>";
echo "Two positive usable signals → Rising<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Combined Signal Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}