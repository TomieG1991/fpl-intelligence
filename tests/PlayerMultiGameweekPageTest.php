<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Multi-Gameweek Page Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function playerMultiGameweekPageCheck(
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
 * DATABASE / REAL PLAYER
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


$playerRepository =
    new PlayerRepository(
        $connection
    );


$fixtureRepository =
    new FixtureRepository(
        $connection
    );


$players =
    $playerRepository
        ->getAll();


$selectedPlayer =
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


    $teamId =
        (int) (
            $player[
                'team_id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
        ||
        $teamId <= 0
    ) {

        continue;
    }


    $upcomingFixtures =
        $fixtureRepository
            ->getUpcomingForTeam(
                $teamId,
                6
            );


    if (
        count(
            $upcomingFixtures
        )
        <
        6
    ) {

        continue;
    }


    $selectedPlayer =
        $player;

    break;
}


/*
 * ============================================================
 * SCENARIO A
 * REAL PLAYER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Player Resolution<br>";
echo "============================================<br>";


playerMultiGameweekPageCheck(
    'A real player with six upcoming fixtures resolves',
    is_array(
        $selectedPlayer
    )
);


if (
    $selectedPlayer === null
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$playerId =
    (int) (
        $selectedPlayer[
            'id'
        ]
        ?? 0
    );


$playerName =
    trim(
        (string) (
            $selectedPlayer[
                'web_name'
            ]
            ??
            $selectedPlayer[
                'player_name'
            ]
            ??
            $selectedPlayer[
                'name'
            ]
            ??
            'Unknown'
        )
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
 * PAGE REQUEST HELPER
 * ============================================================
 */

function fetchPlayerMultiGameweekPage(
    string $url
): array {

    if (
        function_exists(
            'curl_init'
        )
    ) {

        $curl =
            curl_init();


        curl_setopt_array(
            $curl,
            [

                CURLOPT_URL =>
                    $url,

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_FOLLOWLOCATION =>
                    true,

                CURLOPT_CONNECTTIMEOUT =>
                    5,

                CURLOPT_TIMEOUT =>
                    30,

                CURLOPT_HEADER =>
                    false
            ]
        );


        $body =
            curl_exec(
                $curl
            );


        $error =
            curl_error(
                $curl
            );


        $statusCode =
            (int) curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );


        curl_close(
            $curl
        );


        return [

            'success' =>
                $body !== false
                &&
                $statusCode >= 200
                &&
                $statusCode < 400,

            'status_code' =>
                $statusCode,

            'body' =>
                $body !== false
                    ? (string) $body
                    : '',

            'error' =>
                $error
        ];
    }


    $context =
        stream_context_create(
            [

                'http' => [

                    'method' =>
                        'GET',

                    'timeout' =>
                        30,

                    'ignore_errors' =>
                        true
                ]
            ]
        );


    $body =
        @file_get_contents(
            $url,
            false,
            $context
        );


    $statusCode =
        0;


    if (
        isset(
            $http_response_header
        )
        &&
        is_array(
            $http_response_header
        )
        &&
        isset(
            $http_response_header[
                0
            ]
        )
    ) {

        if (
            preg_match(
                '/\s(\d{3})\s/',
                $http_response_header[
                    0
                ],
                $matches
            )
        ) {

            $statusCode =
                (int) (
                    $matches[
                        1
                    ]
                    ?? 0
                );
        }
    }


    return [

        'success' =>
            $body !== false
            &&
            (
                $statusCode === 0
                ||
                (
                    $statusCode >= 200
                    &&
                    $statusCode < 400
                )
            ),

        'status_code' =>
            $statusCode,

        'body' =>
            $body !== false
                ? (string) $body
                : '',

        'error' =>
            $body === false
                ? 'Unable to retrieve page'
                : ''
    ];
}


/*
 * ============================================================
 * DETERMINE APPLICATION URL
 * ============================================================
 */

$host =
    $_SERVER[
        'HTTP_HOST'
    ]
    ?? 'localhost:8008';


$isHttps =
    (
        isset(
            $_SERVER[
                'HTTPS'
            ]
        )
        &&
        $_SERVER[
            'HTTPS'
        ]
        !== ''
        &&
        strtolower(
            (string) $_SERVER[
                'HTTPS'
            ]
        )
        !== 'off'
    );


$scheme =
    $isHttps
        ? 'https'
        : 'http';


$documentRoot =
    realpath(
        (string) (
            $_SERVER[
                'DOCUMENT_ROOT'
            ]
            ?? ''
        )
    );


$projectRoot =
    realpath(
        __DIR__
        . '/..'
    );


$projectWebPath =
    '';


if (
    $documentRoot !== false
    &&
    $projectRoot !== false
) {

    $documentRoot =
        str_replace(
            '\\',
            '/',
            $documentRoot
        );


    $projectRoot =
        str_replace(
            '\\',
            '/',
            $projectRoot
        );


    if (
        str_starts_with(
            strtolower(
                $projectRoot
            ),
            strtolower(
                $documentRoot
            )
        )
    ) {

        $projectWebPath =
            substr(
                $projectRoot,
                strlen(
                    $documentRoot
                )
            );


        $projectWebPath =
            '/'
            . ltrim(
                $projectWebPath,
                '/'
            );
    }
}


if (
    $projectWebPath === ''
) {

    $projectWebPath =
        '/fpl-intelligence';
}


$pageUrl =
    $scheme
    . '://'
    . $host
    . $projectWebPath
    . '/public/player.php?id='
    . $playerId;


/*
 * ============================================================
 * FETCH REAL PLAYER PAGE
 * ============================================================
 */

$response =
    fetchPlayerMultiGameweekPage(
        $pageUrl
    );


$pageHtml =
    $response[
        'body'
    ]
    ?? '';


/*
 * ============================================================
 * SCENARIO B
 * PAGE RENDER CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Page Render Contract<br>";
echo "============================================<br>";


playerMultiGameweekPageCheck(
    'Player page request succeeds',
    (
        $response[
            'success'
        ]
        ?? false
    )
    === true
);

echo "HTTP Status: "
    . (
        $response[
            'status_code'
        ]
        ?? 0
    )
    . "<br>";

playerMultiGameweekPageCheck(
    'Player page renders HTML',
    is_string(
        $pageHtml
    )
    &&
    trim(
        $pageHtml
    )
    !== ''
);


playerMultiGameweekPageCheck(
    'Player page renders selected player name',
    str_contains(
        $pageHtml,
        htmlspecialchars(
            $playerName,
            ENT_QUOTES,
            'UTF-8'
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * MULTI-GAMEWEEK CARD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Multi-Gameweek Card<br>";
echo "============================================<br>";


playerMultiGameweekPageCheck(
    'Page renders Multi-Gameweek Planning heading',
    str_contains(
        $pageHtml,
        'Multi-Gameweek Planning'
    )
);


playerMultiGameweekPageCheck(
    'Page renders Planning Intelligence kicker',
    str_contains(
        $pageHtml,
        'Planning Intelligence'
    )
);


playerMultiGameweekPageCheck(
    'Page renders Upcoming Projections heading',
    str_contains(
        $pageHtml,
        'Upcoming Projections'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PLANNING HORIZONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Planning Horizons<br>";
echo "============================================<br>";


playerMultiGameweekPageCheck(
    'Page renders Next 3 horizon',
    str_contains(
        $pageHtml,
        'Next 3'
    )
);


playerMultiGameweekPageCheck(
    'Page renders Next 5 horizon',
    str_contains(
        $pageHtml,
        'Next 5'
    )
);


playerMultiGameweekPageCheck(
    'Page renders Next 6 horizon',
    str_contains(
        $pageHtml,
        'Next 6'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * REAL FIXTURE CONTENT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Real Fixture Content<br>";
echo "============================================<br>";


$service =
    new PlayerIntelligenceService(
        $connection
    );


$multiGameweek =
    $service
        ->getPlayerMultiGameweekExpectedPoints(
            $playerId,
            6
        );


$fixtureRows =
    $multiGameweek[
        'fixtures'
    ]
    ?? [];


playerMultiGameweekPageCheck(
    'Service provides six fixture rows for page validation',
    is_array(
        $fixtureRows
    )
    &&
    count(
        $fixtureRows
    )
    ===
    6
);


$allOpponentNamesRendered =
    true;


$allVenueMarkersRendered =
    true;


$allProjectedPointsRendered =
    true;


foreach (
    $fixtureRows
    as $fixtureRow
) {

    $opponentName =
        trim(
            (string) (
                $fixtureRow[
                    'opponent_name'
                ]
                ?? ''
            )
        );


    if (
        $opponentName === ''
        ||
        !str_contains(
            $pageHtml,
            htmlspecialchars(
                $opponentName,
                ENT_QUOTES,
                'UTF-8'
            )
        )
    ) {

        $allOpponentNamesRendered =
            false;
    }


    $venue =
        (
            $fixtureRow[
                'is_home'
            ]
            ?? null
        )
        ===
        true
            ? 'H'
            : 'A';


    if (
        preg_match(
            '/>\s*'
            . preg_quote(
                $venue,
                '/'
            )
            . '\s*</',
            $pageHtml
        )
        !==
        1
    ) {

        $allVenueMarkersRendered =
            false;
    }


    $projection =
        $fixtureRow[
            'projection'
        ]
        ?? [];


    $projectedPoints =
        $projection[
            'projected_points'
        ]
        ?? null;


    if (
        !is_numeric(
            $projectedPoints
        )
        ||
        !str_contains(
            $pageHtml,
            number_format(
                (float) $projectedPoints,
                2
            )
        )
    ) {

        $allProjectedPointsRendered =
            false;
    }
}


playerMultiGameweekPageCheck(
    'Page renders all six real opponent names',
    $allOpponentNamesRendered
);


playerMultiGameweekPageCheck(
    'Page renders home or away venue markers',
    $allVenueMarkersRendered
);


playerMultiGameweekPageCheck(
    'Page renders all six fixture projected-point values',
    $allProjectedPointsRendered
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * FIXTURE CONTEXT UI
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Fixture Context UI<br>";
echo "============================================<br>";


playerMultiGameweekPageCheck(
    'Page renders fixture opportunity context',
    str_contains(
        $pageHtml,
        'Opportunity'
    )
);


playerMultiGameweekPageCheck(
    'Page renders projected minutes context',
    str_contains(
        $pageHtml,
        'Minutes'
    )
);


playerMultiGameweekPageCheck(
    'Page renders projection confidence context',
    str_contains(
        $pageHtml,
        'Confidence'
    )
);


playerMultiGameweekPageCheck(
    'Page renders xP labels',
    str_contains(
        $pageHtml,
        'xP'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * NO DIAGNOSTIC LEAKAGE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Diagnostic Leakage<br>";
echo "============================================<br>";


playerMultiGameweekPageCheck(
    'Page does not expose raw print_r array output',
    !str_contains(
        $pageHtml,
        '[fixture_projection_count] =>'
    )
);


playerMultiGameweekPageCheck(
    'Page does not expose Team Name Lookup diagnostic',
    !str_contains(
        $pageHtml,
        'Team Name Lookup:'
    )
);


playerMultiGameweekPageCheck(
    'Page does not expose Fixture ID metadata diagnostic',
    !str_contains(
        $pageHtml,
        'Metadata:'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * MULTI-GAMEWEEK PAGE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Multi-Gameweek Page Diagnostic<br>";
echo "============================================<br>";


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Rendered Page Length: "
    . strlen(
        $pageHtml
    )
    . " characters<br>";


echo "Fixture Rows: "
    . count(
        $fixtureRows
    )
    . "<br>";


echo "Next 3: "
    . (
        is_numeric(
            $multiGameweek[
                'next_3'
            ]
            ?? null
        )
            ? number_format(
                (float) $multiGameweek[
                    'next_3'
                ],
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Next 5: "
    . (
        is_numeric(
            $multiGameweek[
                'next_5'
            ]
            ?? null
        )
            ? number_format(
                (float) $multiGameweek[
                    'next_5'
                ],
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Next 6: "
    . (
        is_numeric(
            $multiGameweek[
                'next_6'
            ]
            ?? null
        )
            ? number_format(
                (float) $multiGameweek[
                    'next_6'
                ],
                2
            )
            : 'Unavailable'
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Multi-Gameweek Page Test Summary<br>";
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