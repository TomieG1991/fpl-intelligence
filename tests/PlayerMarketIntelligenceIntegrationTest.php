<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Market Intelligence Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerMarketIntegrationCheck(
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


playerMarketIntegrationCheck(
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


$marketService =
    new MarketIntelligenceService(
        $db
    );


playerMarketIntegrationCheck(
    'Market Intelligence service can be created',
    $marketService instanceof MarketIntelligenceService
);


playerMarketIntegrationCheck(
    'Market summary method is available',
    method_exists(
        $marketService,
        'getPlayerMarketSummary'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * REAL PLAYER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Real Player Resolution<br>";
echo "============================================<br>";


$playerRepository =
    new PlayerRepository(
        $db
    );


$players =
    $playerRepository
        ->getAll();


$testPlayer =
    null;


$marketSummary =
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


    $candidateSummary =
        $marketService
            ->getPlayerMarketSummary(
                $playerId
            );


    if (
        (
            $candidateSummary[
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


    $marketSummary =
        $candidateSummary;


    break;
}


playerMarketIntegrationCheck(
    'A real player with Market Intelligence resolves',
    is_array(
        $testPlayer
    )
    &&
    is_array(
        $marketSummary
    )
);


if (
    !is_array(
        $testPlayer
    )
    ||
    !is_array(
        $marketSummary
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
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
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * MARKET SUMMARY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Market Summary Contract<br>";
echo "============================================<br>";


playerMarketIntegrationCheck(
    'Market summary exposes classification',
    array_key_exists(
        'classification',
        $marketSummary
    )
);


playerMarketIntegrationCheck(
    'Market summary exposes evidence count',
    array_key_exists(
        'evidence_count',
        $marketSummary
    )
);


playerMarketIntegrationCheck(
    'Market summary exposes evidence collection',
    isset(
        $marketSummary[
            'evidence'
        ]
    )
    &&
    is_array(
        $marketSummary[
            'evidence'
        ]
    )
);


$classification =
    $marketSummary[
        'classification'
    ]
    ?? null;


$evidence =
    $marketSummary[
        'evidence'
    ]
    ?? [];


echo "Classification: "
    . htmlspecialchars(
        (string) (
            $classification
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Evidence Count: "
    . (
        (int) (
            $marketSummary[
                'evidence_count'
            ]
            ?? 0
        )
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * PLAYER PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Player Page Request<br>";
echo "============================================<br>";


$pageUrl =
    'http://localhost:8008/fpl-intelligence/public/player.php?id='
    . $playerId;


$context =
    stream_context_create(
        [
            'http' => [
                'ignore_errors' =>
                    true,

                'timeout' =>
                    15
            ]
        ]
    );


$pageStart =
    microtime(
        true
    );


$pageHtml =
    @file_get_contents(
        $pageUrl,
        false,
        $context
    );


$pageRuntime =
    microtime(
        true
    )
    -
    $pageStart;


$httpStatus =
    0;


if (
    isset(
        $http_response_header
    )
    &&
    is_array(
        $http_response_header
    )
) {

    foreach (
        $http_response_header
        as $header
    ) {

        if (
            preg_match(
                '/HTTP\/\S+\s+(\d{3})/',
                $header,
                $matches
            )
        ) {

            $httpStatus =
                (int) (
                    $matches[
                        1
                    ]
                    ?? 0
                );

            break;
        }
    }
}


playerMarketIntegrationCheck(
    'Player page request succeeds',
    $pageHtml !== false
    &&
    $httpStatus === 200
);


playerMarketIntegrationCheck(
    'Player page renders HTML',
    is_string(
        $pageHtml
    )
    &&
    strlen(
        $pageHtml
    )
    >
    1000
);


playerMarketIntegrationCheck(
    'Player page renders selected player name',
    is_string(
        $pageHtml
    )
    &&
    stripos(
        $pageHtml,
        $playerName
    )
    !==
    false
);


echo "HTTP Status: "
    . $httpStatus
    . "<br>";


echo "Page Runtime: "
    . number_format(
        $pageRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * MARKET INTELLIGENCE SECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Market Intelligence Section<br>";
echo "============================================<br>";


playerMarketIntegrationCheck(
    'Player page renders Market Intelligence heading',
    is_string(
        $pageHtml
    )
    &&
    stripos(
        $pageHtml,
        'Market Intelligence'
    )
    !==
    false
);


playerMarketIntegrationCheck(
    'Player page renders market classification',
    is_string(
        $pageHtml
    )
    &&
    is_string(
        $classification
    )
    &&
    stripos(
        $pageHtml,
        $classification
    )
    !==
    false
);


playerMarketIntegrationCheck(
    'Player page renders market evidence count',
    is_string(
        $pageHtml
    )
    &&
    (
        stripos(
            $pageHtml,
            'Evidence'
        )
        !==
        false
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * COMPONENT EVIDENCE PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Component Evidence Presentation<br>";
echo "============================================<br>";


playerMarketIntegrationCheck(
    'Player page renders Price Movement evidence',
    is_string(
        $pageHtml
    )
    &&
    stripos(
        $pageHtml,
        'Price Movement'
    )
    !==
    false
);


playerMarketIntegrationCheck(
    'Player page renders Ownership Movement evidence',
    is_string(
        $pageHtml
    )
    &&
    stripos(
        $pageHtml,
        'Ownership Movement'
    )
    !==
    false
);


playerMarketIntegrationCheck(
    'Player page renders Transfer Momentum evidence',
    is_string(
        $pageHtml
    )
    &&
    stripos(
        $pageHtml,
        'Transfer Momentum'
    )
    !==
    false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * EARLY-SEASON STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Early-Season State<br>";
echo "============================================<br>";


$priceEvidence =
    $evidence[
        'price'
    ]
    ?? [];


$ownershipEvidence =
    $evidence[
        'ownership'
    ]
    ?? [];


$transferEvidence =
    $evidence[
        'transfers'
    ]
    ?? [];


echo "Price Status: "
    . htmlspecialchars(
        (string) (
            $priceEvidence[
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
            $ownershipEvidence[
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
            $transferEvidence[
                'status'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


if (
    $classification
    ===
    'Insufficient Evidence'
) {

    playerMarketIntegrationCheck(
        'Player page renders Insufficient Evidence state',
        is_string(
            $pageHtml
        )
        &&
        stripos(
            $pageHtml,
            'Insufficient Evidence'
        )
        !==
        false
    );


    playerMarketIntegrationCheck(
        'Player page explains limited historical market evidence',
        is_string(
            $pageHtml
        )
        &&
        (
            stripos(
                $pageHtml,
                'historical'
            )
            !==
            false
            ||
            stripos(
                $pageHtml,
                'insufficient'
            )
            !==
            false
        )
    );

} else {

    playerMarketIntegrationCheck(
        'Player page renders directional market state',
        is_string(
            $pageHtml
        )
        &&
        stripos(
            $pageHtml,
            $classification
        )
        !==
        false
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * SUMMARY BOUNDARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Summary Boundary<br>";
echo "============================================<br>";


playerMarketIntegrationCheck(
    'Player page does not expose raw market snapshot array output',
    is_string(
        $pageHtml
    )
    &&
    stripos(
        $pageHtml,
        '[snapshot_id]'
    )
    ===
    false
);


playerMarketIntegrationCheck(
    'Player page does not expose raw selected-count diagnostic keys',
    is_string(
        $pageHtml
    )
    &&
    stripos(
        $pageHtml,
        '[start_selected]'
    )
    ===
    false
);


playerMarketIntegrationCheck(
    'Player page does not expose raw transfer-balance diagnostic keys',
    is_string(
        $pageHtml
    )
    &&
    stripos(
        $pageHtml,
        '[latest_balance]'
    )
    ===
    false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * PHP ERROR DETECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: PHP Error Detection<br>";
echo "============================================<br>";


$errorPatterns =
    [
        'Fatal error',
        'Parse error',
        'Uncaught',
        'Warning:',
        'Notice:',
        'Undefined variable'
    ];


foreach (
    $errorPatterns
    as $pattern
) {

    playerMarketIntegrationCheck(
        "Player page contains no {$pattern}",
        is_string(
            $pageHtml
        )
        &&
        stripos(
            $pageHtml,
            $pattern
        )
        ===
        false
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Performance<br>";
echo "============================================<br>";


playerMarketIntegrationCheck(
    'Player page loads within 10 seconds',
    $pageRuntime
    <
    10
);


echo "Measured Runtime: "
    . number_format(
        $pageRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO K
 * DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Player Market Integration Diagnostic<br>";
echo "============================================<br><br>";


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Market Classification: "
    . htmlspecialchars(
        (string) (
            $classification
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Evidence Count: "
    . (
        (int) (
            $marketSummary[
                'evidence_count'
            ]
            ?? 0
        )
    )
    . "<br>";


echo "Rendered Page Length: "
    . (
        is_string(
            $pageHtml
        )
            ? strlen(
                $pageHtml
            )
            : 0
    )
    . " characters<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Market Intelligence Integration Test Summary<br>";
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