<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Summary Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function marketSummaryCheck(
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


marketSummaryCheck(
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


marketSummaryCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


marketSummaryCheck(
    'Service exposes getPlayerMarketSummary()',
    method_exists(
        $service,
        'getPlayerMarketSummary'
    )
);


if (
    !method_exists(
        $service,
        'getPlayerMarketSummary'
    )
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "MarketIntelligenceService::getPlayerMarketSummary(int \$playerId)<br><br>";

    echo "Required summary responsibilities:<br>";
    echo "- expose public market classification<br>";
    echo "- expose component evidence states<br>";
    echo "- expose evidence count<br>";
    echo "- expose controlled insufficient-evidence state<br>";
    echo "- avoid exposing unnecessary internal implementation detail<br><br>";


    echo "============================================<br>";
    echo "Market Intelligence Summary Test Summary<br>";
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


$summary =
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


    $candidate =
        $service
            ->getPlayerMarketSummary(
                $playerId
            );


    if (
        (
            $candidate[
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


    $summary =
        $candidate;


    break;
}


marketSummaryCheck(
    'A real player market summary resolves',
    is_array(
        $testPlayer
    )
    &&
    is_array(
        $summary
    )
);


if (
    !is_array(
        $testPlayer
    )
    ||
    !is_array(
        $summary
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


echo "Player: "
    . htmlspecialchars(
        (string) (
            $testPlayer[
                'web_name'
            ]
            ?? 'Unknown'
        ),
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
 * SUMMARY STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Summary Structure<br>";
echo "============================================<br>";


$requiredFields =
    [
        'status',
        'player_id',
        'classification',
        'evidence_count',
        'evidence'
    ];


foreach (
    $requiredFields
    as $field
) {

    marketSummaryCheck(
        "Summary exposes field: {$field}",
        array_key_exists(
            $field,
            $summary
        )
    );
}


marketSummaryCheck(
    'Summary status is Available',
    (
        $summary[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


marketSummaryCheck(
    'Summary preserves requested player ID',
    (
        (int) (
            $summary[
                'player_id'
            ]
            ?? 0
        )
    )
    ===
    $playerId
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * CLASSIFICATION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Classification Contract<br>";
echo "============================================<br>";


$classification =
    $summary[
        'classification'
    ]
    ?? null;


marketSummaryCheck(
    'Summary exposes a recognised market classification',
    in_array(
        $classification,
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


echo "Classification: "
    . htmlspecialchars(
        (string) (
            $classification
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * EVIDENCE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Evidence Contract<br>";
echo "============================================<br>";


$evidence =
    $summary[
        'evidence'
    ]
    ?? null;


marketSummaryCheck(
    'Summary evidence is returned as an array',
    is_array(
        $evidence
    )
);


$requiredEvidence =
    [
        'price',
        'ownership',
        'transfers'
    ];


foreach (
    $requiredEvidence
    as $evidenceKey
) {

    marketSummaryCheck(
        "Summary exposes {$evidenceKey} evidence",
        is_array(
            $evidence
        )
        &&
        isset(
            $evidence[
                $evidenceKey
            ]
        )
        &&
        is_array(
            $evidence[
                $evidenceKey
            ]
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * COMPONENT EVIDENCE SHAPE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Component Evidence Shape<br>";
echo "============================================<br>";


$componentKeys =
    [
        'price',
        'ownership',
        'transfers'
    ];


foreach (
    $componentKeys
    as $componentKey
) {

    $component =
        $evidence[
            $componentKey
        ]
        ?? [];


    marketSummaryCheck(
        "{$componentKey} evidence exposes status",
        array_key_exists(
            'status',
            $component
        )
    );


    marketSummaryCheck(
        "{$componentKey} evidence exposes direction",
        array_key_exists(
            'direction',
            $component
        )
    );


    marketSummaryCheck(
        "{$componentKey} direction is recognised or unavailable",
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


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * EVIDENCE COUNT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Evidence Count<br>";
echo "============================================<br>";


$expectedEvidenceCount =
    0;


foreach (
    $componentKeys
    as $componentKey
) {

    $component =
        $evidence[
            $componentKey
        ]
        ?? [];


    if (
        (
            $component[
                'status'
            ]
            ?? null
        )
        ===
        'Available'
        &&
        in_array(
            $component[
                'direction'
            ]
            ?? null,
            [
                'Rising',
                'Falling',
                'Stable'
            ],
            true
        )
    ) {

        $expectedEvidenceCount++;
    }
}


marketSummaryCheck(
    'Summary evidence count matches available component evidence',
    (
        (int) (
            $summary[
                'evidence_count'
            ]
            ?? -1
        )
    )
    ===
    $expectedEvidenceCount
);


echo "Evidence Count: "
    . $expectedEvidenceCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * EARLY-SEASON REAL DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Early-Season Real Data<br>";
echo "============================================<br>";


$priceStatus =
    $evidence[
        'price'
    ][
        'status'
    ]
    ?? null;


$ownershipStatus =
    $evidence[
        'ownership'
    ][
        'status'
    ]
    ?? null;


$transferStatus =
    $evidence[
        'transfers'
    ][
        'status'
    ]
    ?? null;


echo "Price Evidence: "
    . htmlspecialchars(
        (string) (
            $priceStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Ownership Evidence: "
    . htmlspecialchars(
        (string) (
            $ownershipStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Transfer Evidence: "
    . htmlspecialchars(
        (string) (
            $transferStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Summary Classification: "
    . htmlspecialchars(
        (string) (
            $classification
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


if (
    $expectedEvidenceCount < 2
) {

    marketSummaryCheck(
        'Fewer than two available signals produce Insufficient Evidence',
        $classification
        ===
        'Insufficient Evidence'
    );

} else {

    marketSummaryCheck(
        'Sufficient component evidence produces directional market classification',
        $classification
        !==
        'Insufficient Evidence'
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * SUMMARY VS FULL INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Summary vs Full Intelligence<br>";
echo "============================================<br>";


$fullResult =
    $service
        ->getPlayerMarketIntelligence(
            $playerId
        );


$fullCombined =
    $fullResult[
        'combined_market_signal'
    ]
    ?? [];


marketSummaryCheck(
    'Summary classification matches full Market Intelligence',
    $classification
    ===
    (
        $fullCombined[
            'classification'
        ]
        ?? null
    )
);


marketSummaryCheck(
    'Summary price evidence matches full Price Movement',
    (
        $evidence[
            'price'
        ][
            'direction'
        ]
        ?? null
    )
    ===
    (
        $fullResult[
            'price_movement'
        ][
            'direction'
        ]
        ?? null
    )
);


marketSummaryCheck(
    'Summary ownership evidence matches full Ownership Movement',
    (
        $evidence[
            'ownership'
        ][
            'direction'
        ]
        ?? null
    )
    ===
    (
        $fullResult[
            'ownership_movement'
        ][
            'direction'
        ]
        ?? null
    )
);


marketSummaryCheck(
    'Summary transfer evidence matches full Transfer Momentum',
    (
        $evidence[
            'transfers'
        ][
            'direction'
        ]
        ?? null
    )
    ===
    (
        $fullResult[
            'transfer_momentum'
        ][
            'direction'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * SUMMARY DOES NOT LEAK FULL INTERNAL HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Summary Boundary<br>";
echo "============================================<br>";


marketSummaryCheck(
    'Summary does not expose full historical snapshot rows',
    !array_key_exists(
        'history',
        $summary
    )
);


marketSummaryCheck(
    'Summary does not expose full current player market state',
    !array_key_exists(
        'current',
        $summary
    )
);


marketSummaryCheck(
    'Summary does not expose full price movement internals',
    !array_key_exists(
        'price_movement',
        $summary
    )
);


marketSummaryCheck(
    'Summary does not expose full ownership movement internals',
    !array_key_exists(
        'ownership_movement',
        $summary
    )
);


marketSummaryCheck(
    'Summary does not expose full transfer momentum internals',
    !array_key_exists(
        'transfer_momentum',
        $summary
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * REPEATABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Repeatability<br>";
echo "============================================<br>";


$repeatSummary =
    $service
        ->getPlayerMarketSummary(
            $playerId
        );


marketSummaryCheck(
    'Repeated summary call returns an array',
    is_array(
        $repeatSummary
    )
);


marketSummaryCheck(
    'Repeated summary preserves classification',
    (
        $repeatSummary[
            'classification'
        ]
        ?? null
    )
    ===
    $classification
);


marketSummaryCheck(
    'Repeated summary preserves evidence count',
    (
        $repeatSummary[
            'evidence_count'
        ]
        ?? null
    )
    ===
    (
        $summary[
            'evidence_count'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * INVALID PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Invalid Player<br>";
echo "============================================<br>";


$invalidSummary =
    $service
        ->getPlayerMarketSummary(
            999999999
        );


marketSummaryCheck(
    'Invalid player returns controlled summary array',
    is_array(
        $invalidSummary
    )
);


marketSummaryCheck(
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


marketSummaryCheck(
    'Invalid player does not expose false market classification',
    in_array(
        $invalidSummary[
            'classification'
        ]
        ?? null,
        [
            null,
            'Unavailable',
            'Insufficient Evidence'
        ],
        true
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * SUMMARY DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Market Intelligence Summary Diagnostic<br>";
echo "============================================<br><br>";


echo "Player: "
    . htmlspecialchars(
        (string) (
            $testPlayer[
                'web_name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


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
            $summary[
                'evidence_count'
            ]
            ?? 0
        )
    )
    . "<br>";


echo "Price: "
    . htmlspecialchars(
        (string) (
            $evidence[
                'price'
            ][
                'direction'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Ownership: "
    . htmlspecialchars(
        (string) (
            $evidence[
                'ownership'
            ][
                'direction'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Transfers: "
    . htmlspecialchars(
        (string) (
            $evidence[
                'transfers'
            ][
                'direction'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Summary Test Summary<br>";
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