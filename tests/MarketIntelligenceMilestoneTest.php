<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence v0.31.0 Milestone Test<br>";
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

function milestoneCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo
            "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo
        "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


function milestoneHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $marketService =
        new MarketIntelligenceService(
            $db
        );


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $playerIntelligenceService =
        new PlayerIntelligenceService(
            $db
        );


} catch (
    Throwable $exception
) {

    echo "SETUP FAILED ❌<br>";

    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    exit(
        1
    );
}


/*
 * ============================================================
 * SCENARIO A
 * MILESTONE FOUNDATION
 * ============================================================
 */

milestoneHeading(
    'Scenario A: Milestone Foundation'
);


milestoneCheck(
    'Database connection is available',
    $db instanceof PDO
);


milestoneCheck(
    'Market Intelligence service can be created',
    $marketService
        instanceof
        MarketIntelligenceService
);


milestoneCheck(
    'Player Intelligence service can be created',
    $playerIntelligenceService
        instanceof
        PlayerIntelligenceService
);


milestoneCheck(
    'Market Intelligence exposes full player result',
    method_exists(
        $marketService,
        'getPlayerMarketIntelligence'
    )
);


milestoneCheck(
    'Market Intelligence exposes compact player summary',
    method_exists(
        $marketService,
        'getPlayerMarketSummary'
    )
);


milestoneCheck(
    'Market Intelligence exposes Value Trend builder',
    method_exists(
        $marketService,
        'buildValueTrend'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * HISTORICAL MARKET FOUNDATION
 * ============================================================
 */

milestoneHeading(
    'Scenario B: Historical Market Foundation'
);


$snapshotRows =
    (int) $db
        ->query(
            "
                SELECT COUNT(*)
                FROM player_gameweek_snapshots
            "
        )
        ->fetchColumn();


$snapshotGameweeks =
    (int) $db
        ->query(
            "
                SELECT COUNT(
                    DISTINCT gameweek_id
                )
                FROM player_gameweek_snapshots
            "
        )
        ->fetchColumn();


$snapshotPlayers =
    (int) $db
        ->query(
            "
                SELECT COUNT(
                    DISTINCT player_id
                )
                FROM player_gameweek_snapshots
            "
        )
        ->fetchColumn();


$fixtureHistoryRows =
    (int) $db
        ->query(
            "
                SELECT COUNT(*)
                FROM player_fixture_history
            "
        )
        ->fetchColumn();


$fixtureHistoryGameweeks =
    (int) $db
        ->query(
            "
                SELECT COUNT(
                    DISTINCT gameweek_id
                )
                FROM player_fixture_history
            "
        )
        ->fetchColumn();


$fixtureHistoryPlayers =
    (int) $db
        ->query(
            "
                SELECT COUNT(
                    DISTINCT player_id
                )
                FROM player_fixture_history
            "
        )
        ->fetchColumn();


milestoneCheck(
    'Historical player gameweek snapshots exist',
    $snapshotRows > 0
);


milestoneCheck(
    'Historical snapshots contain gameweek evidence',
    $snapshotGameweeks > 0
);


milestoneCheck(
    'Historical snapshots contain real players',
    $snapshotPlayers > 0
);


milestoneCheck(
    'Fixture-history market evidence exists',
    $fixtureHistoryRows > 0
);


milestoneCheck(
    'Fixture history contains gameweek evidence',
    $fixtureHistoryGameweeks > 0
);


milestoneCheck(
    'Fixture history contains real players',
    $fixtureHistoryPlayers > 0
);


echo "Snapshot Rows: "
    . number_format(
        $snapshotRows
    )
    . "<br>";


echo "Snapshot Gameweeks: "
    . number_format(
        $snapshotGameweeks
    )
    . "<br>";


echo "Snapshot Players: "
    . number_format(
        $snapshotPlayers
    )
    . "<br>";


echo "Fixture-History Rows: "
    . number_format(
        $fixtureHistoryRows
    )
    . "<br>";


echo "Fixture-History Gameweeks: "
    . number_format(
        $fixtureHistoryGameweeks
    )
    . "<br>";


echo "Fixture-History Players: "
    . number_format(
        $fixtureHistoryPlayers
    )
    . "<br>";


/*
 * ============================================================
 * SCENARIO C
 * REAL PLAYER RESOLUTION
 * ============================================================
 */

milestoneHeading(
    'Scenario C: Real Player Resolution'
);


$players =
    $playerRepository
        ->getAll();


$testPlayer =
    null;


$marketResult =
    null;


foreach (
    $players
    as $player
) {

    $candidatePlayerId =
        (int) (
            $player[
                'id'
            ]
            ?? 0
        );


    if (
        $candidatePlayerId <= 0
    ) {

        continue;
    }


    $candidateResult =
        $marketService
            ->getPlayerMarketIntelligence(
                $candidatePlayerId
            );


    if (
        (
            $candidateResult[
                'status'
            ]
            ?? null
        )
        !==
        'Available'
    ) {

        continue;
    }


    $testPlayer =
        $player;


    $marketResult =
        $candidateResult;


    break;
}


milestoneCheck(
    'A real player Market Intelligence result resolves',
    is_array(
        $testPlayer
    )
    &&
    is_array(
        $marketResult
    )
);


if (
    !is_array(
        $testPlayer
    )
    ||
    !is_array(
        $marketResult
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit(
        1
    );
}


$playerId =
    (int) (
        $testPlayer[
            'id'
        ]
        ?? 0
    );


$playerName =
    (string) (
        $testPlayer[
            'web_name'
        ]
        ?? 'Unknown'
    );


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Player ID: "
    . $playerId
    . "<br>";


/*
 * ============================================================
 * SCENARIO D
 * MARKET MOVEMENT COMPONENTS
 * ============================================================
 */

milestoneHeading(
    'Scenario D: Market Movement Components'
);


$priceMovement =
    $marketResult[
        'price_movement'
    ]
    ?? [];


$ownershipMovement =
    $marketResult[
        'ownership_movement'
    ]
    ?? [];


$transferMomentum =
    $marketResult[
        'transfer_momentum'
    ]
    ?? [];


milestoneCheck(
    'Price Movement is exposed',
    is_array(
        $priceMovement
    )
);


milestoneCheck(
    'Ownership Movement is exposed',
    is_array(
        $ownershipMovement
    )
);


milestoneCheck(
    'Transfer Momentum is exposed',
    is_array(
        $transferMomentum
    )
);


foreach (
    [
        'Price Movement' =>
            $priceMovement,

        'Ownership Movement' =>
            $ownershipMovement,

        'Transfer Momentum' =>
            $transferMomentum
    ]
    as $componentName => $component
) {

    milestoneCheck(
        "{$componentName} exposes status",
        array_key_exists(
            'status',
            $component
        )
    );


    milestoneCheck(
        "{$componentName} exposes direction",
        array_key_exists(
            'direction',
            $component
        )
    );


    milestoneCheck(
        "{$componentName} exposes a controlled status",
        in_array(
            $component[
                'status'
            ]
            ?? null,
            [
                'Available',
                'Insufficient Historical Data'
            ],
            true
        )
    );


    milestoneCheck(
        "{$componentName} does not fabricate direction",
        in_array(
            $component[
                'direction'
            ]
            ?? null,
            [
                'Rising',
                'Falling',
                'Stable',
                'Unavailable'
            ],
            true
        )
    );
}


/*
 * ============================================================
 * SCENARIO E
 * COMBINED MARKET SIGNAL
 * ============================================================
 */

milestoneHeading(
    'Scenario E: Combined Market Signal'
);


$combinedSignal =
    $marketResult[
        'combined_market_signal'
    ]
    ?? [];


milestoneCheck(
    'Combined Market Signal is exposed',
    is_array(
        $combinedSignal
    )
);


milestoneCheck(
    'Combined Market Signal exposes classification',
    array_key_exists(
        'classification',
        $combinedSignal
    )
);


milestoneCheck(
    'Combined Market Signal exposes available signal count',
    array_key_exists(
        'available_signals',
        $combinedSignal
    )
);


milestoneCheck(
    'Combined Market Signal exposes rising signal count',
    array_key_exists(
        'rising_signals',
        $combinedSignal
    )
);


milestoneCheck(
    'Combined Market Signal exposes falling signal count',
    array_key_exists(
        'falling_signals',
        $combinedSignal
    )
);


milestoneCheck(
    'Combined Market Signal exposes stable signal count',
    array_key_exists(
        'stable_signals',
        $combinedSignal
    )
);


$combinedClassification =
    $combinedSignal[
        'classification'
    ]
    ?? null;


$availableSignals =
    (int) (
        $combinedSignal[
            'available_signals'
        ]
        ?? 0
    );


$risingSignals =
    (int) (
        $combinedSignal[
            'rising_signals'
        ]
        ?? 0
    );


$fallingSignals =
    (int) (
        $combinedSignal[
            'falling_signals'
        ]
        ?? 0
    );


$stableSignals =
    (int) (
        $combinedSignal[
            'stable_signals'
        ]
        ?? 0
    );


milestoneCheck(
    'Combined Market Signal exposes recognised classification',
    in_array(
        $combinedClassification,
        [
            'Strong Rising',
            'Rising',
            'Stable',
            'Falling',
            'Strong Falling',
            'Mixed',
            'Insufficient Evidence'
        ],
        true
    )
);


milestoneCheck(
    'Combined signal accounting is internally consistent',
    $availableSignals
    ===
    (
        $risingSignals
        +
        $fallingSignals
        +
        $stableSignals
    )
);


echo "Combined Classification: "
    . htmlspecialchars(
        (string) (
            $combinedClassification
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Available Signals: "
    . $availableSignals
    . "<br>";


/*
 * ============================================================
 * SCENARIO F
 * VALUE INTELLIGENCE INTEGRATION
 * ============================================================
 */

milestoneHeading(
    'Scenario F: Value Intelligence Integration'
);


$playerProfile =
    $playerIntelligenceService
        ->getPlayerProfile(
            $playerId
        );


$valueRating =
    $playerProfile[
        'summary'
    ][
        'value_rating'
    ]
    ?? null;


$valueTrend =
    $marketResult[
        'value_trend'
    ]
    ?? [];


milestoneCheck(
    'Existing Player Intelligence value rating is available',
    $valueRating === null
    ||
    is_numeric(
        $valueRating
    )
);


milestoneCheck(
    'Market Intelligence exposes Value Trend',
    is_array(
        $valueTrend
    )
);


milestoneCheck(
    'Value Trend exposes status',
    array_key_exists(
        'status',
        $valueTrend
    )
);


milestoneCheck(
    'Value Trend exposes classification',
    array_key_exists(
        'classification',
        $valueTrend
    )
);


milestoneCheck(
    'Value Trend preserves Player Intelligence value rating',
    (
        $valueTrend[
            'value_rating'
        ]
        ?? null
    )
    ===
    (
        $valueRating !== null
            ? (float) $valueRating
            : null
    )
);


milestoneCheck(
    'Value Trend preserves combined market classification',
    (
        $valueTrend[
            'market_classification'
        ]
        ?? null
    )
    ===
    $combinedClassification
);


milestoneCheck(
    'Value Trend exposes recognised classification',
    in_array(
        $valueTrend[
            'classification'
        ]
        ?? null,
        [
            'Improving Value',
            'Stable Value',
            'Deteriorating Value',
            'Mixed Value Signal',
            'Insufficient Evidence'
        ],
        true
    )
);


echo "Value Rating: "
    . (
        $valueRating !== null
        &&
        is_numeric(
            $valueRating
        )
            ? number_format(
                (float) $valueRating,
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Value Trend: "
    . htmlspecialchars(
        (string) (
            $valueTrend[
                'classification'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


/*
 * ============================================================
 * SCENARIO G
 * PUBLIC MARKET SUMMARY
 * ============================================================
 */

milestoneHeading(
    'Scenario G: Public Market Summary'
);


$marketSummary =
    $marketService
        ->getPlayerMarketSummary(
            $playerId
        );


milestoneCheck(
    'Public Market Summary resolves',
    is_array(
        $marketSummary
    )
);


milestoneCheck(
    'Public Market Summary reports Available',
    (
        $marketSummary[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


milestoneCheck(
    'Public Market Summary classification matches full result',
    (
        $marketSummary[
            'classification'
        ]
        ?? null
    )
    ===
    $combinedClassification
);


milestoneCheck(
    'Public Market Summary evidence count matches full result',
    (
        (int) (
            $marketSummary[
                'evidence_count'
            ]
            ?? -1
        )
    )
    ===
    $availableSignals
);


$summaryEvidence =
    $marketSummary[
        'evidence'
    ]
    ?? [];


milestoneCheck(
    'Public Market Summary exposes compact component evidence',
    isset(
        $summaryEvidence[
            'price'
        ],
        $summaryEvidence[
            'ownership'
        ],
        $summaryEvidence[
            'transfers'
        ]
    )
);


$summaryValueTrend =
    $marketSummary[
        'value_trend'
    ]
    ?? [];


milestoneCheck(
    'Public Market Summary exposes Value Trend',
    is_array(
        $summaryValueTrend
    )
);


milestoneCheck(
    'Summary Value Trend classification matches full result',
    (
        $summaryValueTrend[
            'classification'
        ]
        ?? null
    )
    ===
    (
        $valueTrend[
            'classification'
        ]
        ?? null
    )
);


milestoneCheck(
    'Summary Value Trend status matches full result',
    (
        $summaryValueTrend[
            'status'
        ]
        ?? null
    )
    ===
    (
        $valueTrend[
            'status'
        ]
        ?? null
    )
);


/*
 * The public summary must remain intentionally compact.
 */

milestoneCheck(
    'Public summary does not expose raw snapshot history',
    !array_key_exists(
        'history',
        $marketSummary
    )
);


milestoneCheck(
    'Public summary does not expose raw price movement model',
    !array_key_exists(
        'price_movement',
        $marketSummary
    )
);


milestoneCheck(
    'Public summary does not expose raw ownership movement model',
    !array_key_exists(
        'ownership_movement',
        $marketSummary
    )
);


milestoneCheck(
    'Public summary does not expose raw transfer momentum model',
    !array_key_exists(
        'transfer_momentum',
        $marketSummary
    )
);


milestoneCheck(
    'Public Value Trend does not expose internal value rating',
    !array_key_exists(
        'value_rating',
        $summaryValueTrend
    )
);


milestoneCheck(
    'Public Value Trend does not expose internal market classification',
    !array_key_exists(
        'market_classification',
        $summaryValueTrend
    )
);


/*
 * ============================================================
 * SCENARIO H
 * CURRENT DATA READINESS
 * ============================================================
 */

milestoneHeading(
    'Scenario H: Current Data Readiness'
);


/*
 * The acceptance test adapts to the amount of real historical
 * evidence currently stored.
 *
 * At the present early-season stage there is only one
 * historical gameweek, so Market Intelligence must explicitly
 * refuse to manufacture movement trends.
 */

if (
    $snapshotGameweeks < 2
) {

    milestoneCheck(
        'Single-gameweek snapshot history keeps Price Movement insufficient',
        (
            $priceMovement[
                'status'
            ]
            ?? null
        )
        ===
        'Insufficient Historical Data'
    );


    milestoneCheck(
        'Single-gameweek snapshot history keeps Ownership Movement insufficient',
        (
            $ownershipMovement[
                'status'
            ]
            ?? null
        )
        ===
        'Insufficient Historical Data'
    );


    milestoneCheck(
        'Single-gameweek Price Movement has unavailable direction',
        (
            $priceMovement[
                'direction'
            ]
            ?? null
        )
        ===
        'Unavailable'
    );


    milestoneCheck(
        'Single-gameweek Ownership Movement has unavailable direction',
        (
            $ownershipMovement[
                'direction'
            ]
            ?? null
        )
        ===
        'Unavailable'
    );

} else {

    milestoneCheck(
        'Multi-gameweek snapshot history allows Price Movement evaluation',
        (
            $priceMovement[
                'status'
            ]
            ?? null
        )
        ===
        'Available'
    );


    milestoneCheck(
        'Multi-gameweek snapshot history allows Ownership Movement evaluation',
        (
            $ownershipMovement[
                'status'
            ]
            ?? null
        )
        ===
        'Available'
    );
}


if (
    $fixtureHistoryGameweeks < 2
) {

    milestoneCheck(
        'Single-gameweek fixture history keeps Transfer Momentum insufficient',
        (
            $transferMomentum[
                'status'
            ]
            ?? null
        )
        ===
        'Insufficient Historical Data'
    );


    milestoneCheck(
        'Single-gameweek Transfer Momentum has unavailable direction',
        (
            $transferMomentum[
                'direction'
            ]
            ?? null
        )
        ===
        'Unavailable'
    );

} else {

    milestoneCheck(
        'Multi-gameweek fixture history allows Transfer Momentum evaluation',
        (
            $transferMomentum[
                'status'
            ]
            ?? null
        )
        ===
        'Available'
    );
}


if (
    $availableSignals < 2
) {

    milestoneCheck(
        'Insufficient component evidence prevents combined directional classification',
        $combinedClassification
        ===
        'Insufficient Evidence'
    );


    milestoneCheck(
        'Insufficient combined market evidence prevents Value Trend fabrication',
        (
            $valueTrend[
                'classification'
            ]
            ?? null
        )
        ===
        'Insufficient Evidence'
    );


    milestoneCheck(
        'Insufficient Value Trend reports historical evidence limitation',
        (
            $valueTrend[
                'status'
            ]
            ?? null
        )
        ===
        'Insufficient Historical Data'
    );

} else {

    milestoneCheck(
        'Sufficient component evidence produces a market classification',
        $combinedClassification
        !==
        'Insufficient Evidence'
    );
}


/*
 * ============================================================
 * SCENARIO I
 * REPEATABILITY
 * ============================================================
 */

milestoneHeading(
    'Scenario I: Repeatability'
);


$repeatFullResult =
    $marketService
        ->getPlayerMarketIntelligence(
            $playerId
        );


$repeatSummary =
    $marketService
        ->getPlayerMarketSummary(
            $playerId
        );


milestoneCheck(
    'Repeated full Market Intelligence call is deterministic',
    $repeatFullResult
    ===
    $marketResult
);


milestoneCheck(
    'Repeated Market Summary call is deterministic',
    $repeatSummary
    ===
    $marketSummary
);


milestoneCheck(
    'Repeated combined classification is preserved',
    (
        $repeatFullResult[
            'combined_market_signal'
        ][
            'classification'
        ]
        ?? null
    )
    ===
    $combinedClassification
);


milestoneCheck(
    'Repeated Value Trend classification is preserved',
    (
        $repeatFullResult[
            'value_trend'
        ][
            'classification'
        ]
        ?? null
    )
    ===
    (
        $valueTrend[
            'classification'
        ]
        ?? null
    )
);


/*
 * ============================================================
 * SCENARIO J
 * INVALID PLAYER SAFETY
 * ============================================================
 */

milestoneHeading(
    'Scenario J: Invalid Player Safety'
);


$invalidFullResult =
    $marketService
        ->getPlayerMarketIntelligence(
            999999999
        );


$invalidSummary =
    $marketService
        ->getPlayerMarketSummary(
            999999999
        );


milestoneCheck(
    'Invalid player full result remains controlled',
    is_array(
        $invalidFullResult
    )
);


milestoneCheck(
    'Invalid player full result reports Unavailable',
    (
        $invalidFullResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


milestoneCheck(
    'Invalid player summary remains controlled',
    is_array(
        $invalidSummary
    )
);


milestoneCheck(
    'Invalid player summary reports Unavailable',
    (
        $invalidSummary[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


milestoneCheck(
    'Invalid player does not produce false market classification',
    (
        $invalidSummary[
            'classification'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * ============================================================
 * SCENARIO K
 * MILESTONE DIAGNOSTIC
 * ============================================================
 */

milestoneHeading(
    'Scenario K: v0.31.0 Milestone Diagnostic'
);


echo "<br>";


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Historical Snapshot Gameweeks: "
    . number_format(
        $snapshotGameweeks
    )
    . "<br>";


echo "Historical Transfer Gameweeks: "
    . number_format(
        $fixtureHistoryGameweeks
    )
    . "<br><br>";


echo "Price Movement: "
    . htmlspecialchars(
        (string) (
            $priceMovement[
                'status'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " / "
    . htmlspecialchars(
        (string) (
            $priceMovement[
                'direction'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Ownership Movement: "
    . htmlspecialchars(
        (string) (
            $ownershipMovement[
                'status'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " / "
    . htmlspecialchars(
        (string) (
            $ownershipMovement[
                'direction'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Transfer Momentum: "
    . htmlspecialchars(
        (string) (
            $transferMomentum[
                'status'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " / "
    . htmlspecialchars(
        (string) (
            $transferMomentum[
                'direction'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


echo "Combined Market Signal: "
    . htmlspecialchars(
        (string) (
            $combinedClassification
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Available Market Signals: "
    . number_format(
        $availableSignals
    )
    . " / 3<br>";


echo "Value Rating: "
    . (
        $valueRating !== null
        &&
        is_numeric(
            $valueRating
        )
            ? number_format(
                (float) $valueRating,
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Value Trend: "
    . htmlspecialchars(
        (string) (
            $valueTrend[
                'classification'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Value Trend Status: "
    . htmlspecialchars(
        (string) (
            $valueTrend[
                'status'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


echo "Public Summary: Available<br>";
echo "Invalid Player Handling: Controlled<br>";
echo "Repeatability: Confirmed<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Market Intelligence v0.31.0 Milestone Test Summary<br>";
echo "============================================<br>";


echo
    "Passed: "
    . $passed
    . "<br>";


echo
    "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

    exit(
        0
    );
}


echo "RESULT: TESTS FAILED ❌";


exit(
    1
);