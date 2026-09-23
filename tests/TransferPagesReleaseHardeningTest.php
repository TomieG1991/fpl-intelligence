<?php

echo "============================================<br>";
echo "Transfer Pages Release Hardening Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function transferPagesReleaseHardeningCheck(
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
 * PAGE DEFINITIONS
 * ============================================================
 */

$pages = [

    'Transfer Intelligence' => [
        'file' =>
            'transfers.php',

        'heading' =>
            'Transfer Intelligence',

        'action' =>
            'findPlayerReplacements',

        'error' =>
            'Transfer Intelligence could not be loaded.'
    ],

    'Transfer Planner' => [
        'file' =>
            'transfer-planner.php',

        'heading' =>
            'Transfer Planner',

        'action' =>
            'evaluateTransferCombination',

        'error' =>
            'Transfer Planner could not be loaded.'
    ],

    'Transfer Optimizer' => [
        'file' =>
            'transfer-optimizer.php',

        'heading' =>
            'Transfer Optimizer',

        'action' =>
            'optimizeTransferCombination',

        'error' =>
            'Transfer Optimizer could not be loaded.'
    ]
];


/*
 * ============================================================
 * PAGE TESTS
 * ============================================================
 */

foreach (
    $pages
    as $pageName => $definition
) {

    echo "============================================<br>";
    echo htmlspecialchars(
        $pageName,
        ENT_QUOTES,
        'UTF-8'
    );
    echo "<br>";
    echo "============================================<br>";


    $pagePath =
        __DIR__
        . '/../public/'
        . $definition['file'];


    $pageExists =
        is_file(
            $pagePath
        );


    $pageSource =
        $pageExists
            ? file_get_contents(
                $pagePath
            )
            : false;


    $pageReadable =
        is_string(
            $pageSource
        );


    $pageSource =
        $pageReadable
            ? $pageSource
            : '';


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' page exists',
        $pageExists
    );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' page source can be read',
        $pageReadable
    );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' retains the shared sidebar',
        strpos(
            $pageSource,
            '/includes/sidebar.php'
        ) !== false
    );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' retains the dashboard shell',
        strpos(
            $pageSource,
            '<main class="dashboard">'
        ) !== false
    );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' retains its page heading',
        strpos(
            $pageSource,
            $definition['heading']
        ) !== false
    );


    /*
     * --------------------------------------------------------
     * SAFE INITIALISATION
     * --------------------------------------------------------
     */

    transferPagesReleaseHardeningCheck(
        $pageName
        . ' does not terminate with die',
        strpos(
            $pageSource,
            'die('
        ) === false
    );


        $databaseSetupPosition =
        strpos(
            $pageSource,
            '$database ='
        );


    $playersInitialisationPosition =
        strpos(
            $pageSource,
            '$players ='
        );


    $serviceInitialisationPosition =
        strpos(
            $pageSource,
            '$service ='
        );


    $pageErrorInitialisationPosition =
        strpos(
            $pageSource,
            '$pageError ='
        );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' initialises an empty player collection before database access',
        $playersInitialisationPosition !== false
        &&
        $databaseSetupPosition !== false
        &&
        $playersInitialisationPosition
            <
            $databaseSetupPosition
        &&
        preg_match(
            '/\$players\s*=\s*\[\]\s*;/',
            $pageSource
        ) === 1
    );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' initialises the intelligence service before database access',
        $serviceInitialisationPosition !== false
        &&
        $databaseSetupPosition !== false
        &&
        $serviceInitialisationPosition
            <
            $databaseSetupPosition
        &&
        preg_match(
            '/\$service\s*=\s*null\s*;/',
            $pageSource
        ) === 1
    );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' initialises a page error before database access',
        $pageErrorInitialisationPosition !== false
        &&
        $databaseSetupPosition !== false
        &&
        $pageErrorInitialisationPosition
            <
            $databaseSetupPosition
        &&
        preg_match(
            '/\$pageError\s*=\s*null\s*;/',
            $pageSource
        ) === 1
    );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' provides an in-page setup failure message',
        strpos(
            $pageSource,
            $definition['error']
        ) !== false
    );


    /*
     * --------------------------------------------------------
     * EXISTING INTELLIGENCE
     * --------------------------------------------------------
     */

    transferPagesReleaseHardeningCheck(
        $pageName
        . ' retains PlayerIntelligenceService',
        strpos(
            $pageSource,
            'new PlayerIntelligenceService'
        ) !== false
    );


    transferPagesReleaseHardeningCheck(
        $pageName
        . ' retains player summaries',
        strpos(
            $pageSource,
            '->getAllPlayerSummaries()'
        ) !== false
    );


        transferPagesReleaseHardeningCheck(
            $pageName
            . ' retains its production intelligence operation',
            strpos(
                $pageSource,
                '->'
                . $definition['action']
                . '('
            ) !== false
        );


        /*
         * --------------------------------------------------------
         * CONFIDENCE TERMINOLOGY
         * --------------------------------------------------------
         *
         * Transfer Planner and Transfer Optimizer display movement
         * in sample_confidence. The user-facing label must identify
         * that specific confidence measure rather than presenting it
         * as generic Confidence.
         */

        if (
            in_array(
                $definition['file'],
                [
                    'transfer-planner.php',
                    'transfer-optimizer.php'
                ],
                true
            )
        ) {

            transferPagesReleaseHardeningCheck(
                $pageName
                . ' labels sample confidence explicitly',
                strpos(
                    $pageSource,
                    'Sample Confidence'
                ) !== false
            );
        }
        
                /*
         * --------------------------------------------------------
         * TRANSFER INTELLIGENCE ACCESSIBILITY
         * --------------------------------------------------------
         */

        if (
            $definition['file']
            ===
            'transfers.php'
        ) {

            transferPagesReleaseHardeningCheck(
                'Transfer Intelligence page-level error exposes alert semantics',
                preg_match(
                    '/<div\s+class="alert\s+alert-error"\s+role="alert">/i',
                    $pageSource
                )
                === 1
            );


            transferPagesReleaseHardeningCheck(
                'Transfer Intelligence search error exposes alert semantics',
                preg_match(
                    '/<div\s+class="transfer-error"\s+role="alert">/i',
                    $pageSource
                )
                === 1
            );
        }
        
        if (
            $definition['file']
            ===
            'transfer-planner.php'
        ) {

            transferPagesReleaseHardeningCheck(
                'Transfer Planner page-level error exposes alert semantics',
                preg_match(
                    '/<div\s+class="alert\s+alert-error"\s+role="alert">/i',
                    $pageSource
                )
                === 1
            );


            transferPagesReleaseHardeningCheck(
                'Transfer Planner evaluation error exposes alert semantics',
                preg_match(
                    '/<div\s+class="transfer-error"\s+role="alert">/i',
                    $pageSource
                )
                === 1
            );
        }
        
        if (
            $definition['file']
            ===
            'transfer-optimizer.php'
        ) {

            transferPagesReleaseHardeningCheck(
                'Transfer Optimizer page-level error exposes alert semantics',
                preg_match(
                    '/<div\s+class="alert\s+alert-error"\s+role="alert">/i',
                    $pageSource
                )
                === 1
            );


            transferPagesReleaseHardeningCheck(
                'Transfer Optimizer evaluation error exposes alert semantics',
                preg_match(
                    '/<div\s+class="transfer-error"\s+role="alert">/i',
                    $pageSource
                )
                === 1
            );
        }


        echo "<br>";
    }


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Transfer Pages Release Hardening Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}