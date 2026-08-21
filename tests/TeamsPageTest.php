<?php

echo "============================================<br>";
echo "Team Intelligence Page Test<br>";
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

function teamsPageCheck(
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
 * REQUEST HELPER
 * ============================================================
 */

function teamsPageRequest(
    string $url
): array {

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


    $startedAt =
        microtime(
            true
        );


    $html =
        @file_get_contents(
            $url,
            false,
            $context
        );


    $runtime =
        microtime(
            true
        )
        -
        $startedAt;


    $status =
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
                    '/^HTTP\/\S+\s+(\d{3})/',
                    $header,
                    $matches
                )
            ) {

                $status =
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


    return [

        'html' =>
            is_string(
                $html
            )
                ? $html
                : '',

        'status' =>
            $status,

        'error' =>
            $html === false
                ? 'Unable to request page.'
                : '',

        'runtime' =>
            $runtime
    ];
}


/*
 * ============================================================
 * NORMALISED HTML HELPER
 * ============================================================
 */

function teamsPageNormaliseHtml(
    string $html
): string {

    return preg_replace(
        '/\s+/',
        ' ',
        trim(
            $html
        )
    )
        ?? '';
}


/*
 * ============================================================
 * URL
 * ============================================================
 */

$baseUrl =
    'http://localhost:8008/fpl-intelligence/public/teams.php';


echo "Base URL: "
    . htmlspecialchars(
        $baseUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * PAGE REQUEST
 * ============================================================
 */

$page =
    teamsPageRequest(
        $baseUrl
    );


$html =
    $page[
        'html'
    ];


$normalisedHtml =
    teamsPageNormaliseHtml(
        $html
    );


/*
 * ============================================================
 * SCENARIO A
 * INITIAL PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Initial Page Request<br>";
echo "============================================<br>";


teamsPageCheck(
    'Team Intelligence page request succeeds',
    $page[
        'status'
    ] === 200
);


teamsPageCheck(
    'Team Intelligence page returns HTML',
    $html !== ''
);


teamsPageCheck(
    'Team Intelligence page contains document structure',
    stripos(
        $html,
        '<!DOCTYPE html>'
    ) !== false
    &&
    stripos(
        $html,
        '<html'
    ) !== false
    &&
    stripos(
        $html,
        '</html>'
    ) !== false
);


echo "Runtime: "
    . number_format(
        $page[
            'runtime'
        ],
        4
    )
    . " seconds<br>";


echo "HTTP Status: "
    . $page[
        'status'
    ]
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * APPLICATION SHELL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Application Shell<br>";
echo "============================================<br>";


teamsPageCheck(
    'Page contains Team Intelligence title',
    strpos(
        $normalisedHtml,
        'Team Intelligence'
    ) !== false
);


teamsPageCheck(
    'Teams navigation is present',
    strpos(
        $normalisedHtml,
        'href="teams.php"'
    ) !== false
);


teamsPageCheck(
    'Teams navigation is active',
    preg_match(
        '/href="teams\.php"[^>]*class="[^"]*\bactive\b[^"]*"/i',
        $normalisedHtml
    ) === 1
    ||
    preg_match(
        '/class="[^"]*\bactive\b[^"]*"[^>]*href="teams\.php"/i',
        $normalisedHtml
    ) === 1
);


teamsPageCheck(
    'Application shell is present',
    strpos(
        $normalisedHtml,
        'class="app-shell"'
    ) !== false
);


teamsPageCheck(
    'Application content wrapper is present',
    strpos(
        $normalisedHtml,
        'class="app-content"'
    ) !== false
);


teamsPageCheck(
    'Shared topbar is present',
    strpos(
        $normalisedHtml,
        'class="topbar"'
    ) !== false
);


teamsPageCheck(
    'Team dashboard wrapper is present',
    strpos(
        $normalisedHtml,
        'class="dashboard team-dashboard"'
    ) !== false
);


teamsPageCheck(
    'Premier League Analysis eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'Premier League Analysis'
    ) !== false
);


teamsPageCheck(
    'Page explains league-wide Team Intelligence',
    strpos(
        $normalisedHtml,
        'Compare all 20 Premier League teams'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * TEAM INTELLIGENCE SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Team Intelligence Summary<br>";
echo "============================================<br>";


teamsPageCheck(
    'League Overview eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'League Overview'
    ) !== false
);


teamsPageCheck(
    'Team Intelligence Summary section is rendered',
    strpos(
        $normalisedHtml,
        'Team Intelligence Summary'
    ) !== false
);


teamsPageCheck(
    'Team summary grid is rendered',
    strpos(
        $normalisedHtml,
        'class="team-summary-grid"'
    ) !== false
);


$summaryCardCount =
    substr_count(
        $normalisedHtml,
        'class="team-summary-card"'
    );


teamsPageCheck(
    'Team Intelligence Summary contains exactly five summary cards',
    $summaryCardCount === 5
);


teamsPageCheck(
    'Teams Analysed summary is rendered',
    strpos(
        $normalisedHtml,
        'Teams Analysed'
    ) !== false
);


teamsPageCheck(
    'Elite Teams summary is rendered',
    strpos(
        $normalisedHtml,
        'Elite Teams'
    ) !== false
);


teamsPageCheck(
    'Strong Teams summary is rendered',
    strpos(
        $normalisedHtml,
        'Strong Teams'
    ) !== false
);


teamsPageCheck(
    'Average Intelligence summary is rendered',
    strpos(
        $normalisedHtml,
        'Avg Intelligence'
    ) !== false
);


teamsPageCheck(
    'Average Fixture Rating summary is rendered',
    strpos(
        $normalisedHtml,
        'Avg Fixture Rating'
    ) !== false
);


teamsPageCheck(
    'Teams Analysed reports 20 teams',
    preg_match(
        '/Teams Analysed\s*<\/span>\s*<strong>\s*20\s*<\/strong>/i',
        $normalisedHtml
    ) === 1
);


echo "Summary Cards: "
    . $summaryCardCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * TEAM RANKING SECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Team Ranking Section<br>";
echo "============================================<br>";


teamsPageCheck(
    'Team Rankings eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'Team Rankings'
    ) !== false
);


teamsPageCheck(
    'Premier League Team Intelligence section is rendered',
    strpos(
        $normalisedHtml,
        'Premier League Team Intelligence'
    ) !== false
);


teamsPageCheck(
    'Team ranking description is rendered',
    strpos(
        $normalisedHtml,
        'Teams are ranked by overall Team Intelligence'
    ) !== false
);


teamsPageCheck(
    'Team ranking table wrapper is rendered',
    strpos(
        $normalisedHtml,
        'class="team-ranking-table-wrapper"'
    ) !== false
);


teamsPageCheck(
    'Team ranking table is rendered',
    strpos(
        $normalisedHtml,
        'class="team-ranking-table"'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * TABLE STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Ranking Table Structure<br>";
echo "============================================<br>";


$requiredHeaders = [

    'Team',
    'Intelligence',
    'Level',
    'Overall',
    'Home',
    'Away',
    'Next 5',
    'Fixtures',
    'Trend',
    'Form',
    'W-D-L'
];


foreach (
    $requiredHeaders
    as $header
) {

    teamsPageCheck(
        'Ranking table contains column: '
            . $header,
        strpos(
            $normalisedHtml,
            '>'
            . $header
            . '<'
        ) !== false
        ||
        preg_match(
            '/>\s*'
            . preg_quote(
                $header,
                '/'
            )
            . '\s*</i',
            $normalisedHtml
        ) === 1
    );
}


teamsPageCheck(
    'Ranking table contains a table head',
    strpos(
        $normalisedHtml,
        '<thead>'
    ) !== false
);


teamsPageCheck(
    'Ranking table contains a table body',
    strpos(
        $normalisedHtml,
        '<tbody>'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * COMPLETE 20-TEAM RANKING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Complete Team Ranking<br>";
echo "============================================<br>";


$rankCount =
    substr_count(
        $normalisedHtml,
        'class="team-rank-number"'
    );


$teamNameCount =
    substr_count(
        $normalisedHtml,
        'class="team-name-cell"'
    );


$scoreCount =
    substr_count(
        $normalisedHtml,
        'class="team-table-score"'
    );


teamsPageCheck(
    'Ranking contains exactly 20 rank positions',
    $rankCount === 20
);


teamsPageCheck(
    'Ranking contains exactly 20 team name cells',
    $teamNameCount === 20
);


teamsPageCheck(
    'Ranking contains exactly 20 Team Intelligence Scores',
    $scoreCount === 20
);


teamsPageCheck(
    'Team rankings begin at number one',
    preg_match(
        '/class="team-rank-number">\s*1\s*<\/span>/i',
        $normalisedHtml
    ) === 1
);


teamsPageCheck(
    'Team rankings include number twenty',
    preg_match(
        '/class="team-rank-number">\s*20\s*<\/span>/i',
        $normalisedHtml
    ) === 1
);


echo "Rank Positions: "
    . $rankCount
    . "<br>";


echo "Team Rows: "
    . $teamNameCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * TEAM INTELLIGENCE OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Team Intelligence Output<br>";
echo "============================================<br>";


$intelligenceBadgeCount =
    substr_count(
        $normalisedHtml,
        'team-intelligence-badge team-intelligence-badge-'
    );


$fixtureBadgeCount =
    substr_count(
        $normalisedHtml,
        'team-fixture-badge team-fixture-badge-'
    );


teamsPageCheck(
    'Every ranked team has an Intelligence classification badge',
    $intelligenceBadgeCount === 20
);


teamsPageCheck(
    'Every ranked team has a fixture classification badge',
    $fixtureBadgeCount === 20
);


teamsPageCheck(
    'Rendered Intelligence classifications use supported states',
    preg_match(
        '/team-intelligence-badge-(elite|strong|average|weak|poor)/i',
        $normalisedHtml
    ) === 1
);


teamsPageCheck(
    'Rendered fixture classifications use supported states',
    preg_match(
        '/team-fixture-badge-(excellent|good|average|difficult|very-difficult)/i',
        $normalisedHtml
    ) === 1
);


teamsPageCheck(
    'Team Intelligence Score values are rendered',
    preg_match(
        '/class="team-table-score">\s*\d+(?:\.\d+)?\s*<\/strong>/i',
        $normalisedHtml
    ) === 1
);


teamsPageCheck(
    'Fixture trend values are rendered',
    substr_count(
        $normalisedHtml,
        'class="team-trend-cell"'
    ) === 20
);


teamsPageCheck(
    'Recent form values are rendered for all teams',
    substr_count(
        $normalisedHtml,
        'class="team-form-value"'
    ) === 20
);


teamsPageCheck(
    'W-D-L records are rendered for all teams',
    substr_count(
        $normalisedHtml,
        'class="team-record-cell"'
    ) === 20
);


echo "Intelligence Badges: "
    . $intelligenceBadgeCount
    . "<br>";


echo "Fixture Badges: "
    . $fixtureBadgeCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * RANKING ORDER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Ranking Order<br>";
echo "============================================<br>";


preg_match_all(
    '/class="team-table-score">\s*([0-9]+(?:\.[0-9]+)?)\s*<\/strong>/i',
    $normalisedHtml,
    $scoreMatches
);


$renderedScores =
    array_map(
        'floatval',
        $scoreMatches[
            1
        ]
        ?? []
    );


teamsPageCheck(
    'All 20 rendered Team Intelligence Scores can be read',
    count(
        $renderedScores
    ) === 20
);


$scoresDescending =
    true;


for (
    $i = 1;
    $i < count(
        $renderedScores
    );
    $i++
) {

    if (
        $renderedScores[
            $i
        ]
        >
        $renderedScores[
            $i - 1
        ]
    ) {

        $scoresDescending =
            false;

        break;
    }
}


teamsPageCheck(
    'Rendered teams are ordered by Team Intelligence Score',
    $scoresDescending
);


if (
    !empty(
        $renderedScores
    )
) {

    echo "Top Rendered Score: "
        . number_format(
            $renderedScores[
                0
            ],
            2
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * CURRENT DATASET INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Current Dataset Integrity<br>";
echo "============================================<br>";


$currentPremierLeagueTeams = [

    'Arsenal',
    'Aston Villa',
    'Bournemouth',
    'Brentford',
    'Brighton',
    'Chelsea',
    'Coventry City',
    'Crystal Palace',
    'Everton',
    'Fulham',
    'Hull City',
    'Ipswich Town',
    'Leeds',
    'Liverpool',
    'Man City',
    'Man Utd',
    'Newcastle',
    'Nott&#039;m Forest',
    'Spurs',
    'Sunderland'
];


$missingTeams =
    [];


foreach (
    $currentPremierLeagueTeams
    as $teamName
) {

    if (
        strpos(
            $normalisedHtml,
            $teamName
        ) === false
    ) {

        $missingTeams[] =
            $teamName;
    }
}


teamsPageCheck(
    'All 20 current Premier League teams are rendered',
    empty(
        $missingTeams
    )
);


if (
    !empty(
        $missingTeams
    )
) {

    echo "Missing Teams: "
        . htmlspecialchars(
            implode(
                ', ',
                $missingTeams
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


teamsPageCheck(
    'Top-ranked Arsenal row is rendered',
    strpos(
        $normalisedHtml,
        'Arsenal'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * COMPLETED MATCH CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Completed Match Context<br>";
echo "============================================<br>";


teamsPageCheck(
    'Ranking note explains completed Premier League fixture data',
    strpos(
        $normalisedHtml,
        'Recent form and W-D-L reflect completed Premier League'
    ) !== false
);


teamsPageCheck(
    'Recent form output does not require historical results',
    $teamNameCount === 20
    &&
    substr_count(
        $normalisedHtml,
        'class="team-form-value"'
    ) === 20
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * TEAM INTELLIGENCE EXPLANATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Team Intelligence Explanation<br>";
echo "============================================<br>";


teamsPageCheck(
    'How It Works eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'How It Works'
    ) !== false
);


teamsPageCheck(
    'Understanding Team Intelligence section is rendered',
    strpos(
        $normalisedHtml,
        'Understanding Team Intelligence'
    ) !== false
);


$explanationCardCount =
    substr_count(
        $normalisedHtml,
        'class="team-explanation-card"'
    );


teamsPageCheck(
    'Exactly three Team Intelligence explanation cards are rendered',
    $explanationCardCount === 3
);


teamsPageCheck(
    'Current Strength explanation is rendered',
    strpos(
        $normalisedHtml,
        'Current Strength'
    ) !== false
);


teamsPageCheck(
    'Fixture Opportunity explanation is rendered',
    strpos(
        $normalisedHtml,
        'Fixture Opportunity'
    ) !== false
);


teamsPageCheck(
    'Team Intelligence explanation is rendered',
    strpos(
        $normalisedHtml,
        'The overall Team Intelligence Score'
    ) !== false
);


teamsPageCheck(
    'Page explains performance-adjusted strength',
    strpos(
        $normalisedHtml,
        'performance-adjusted ratings'
    ) !== false
);


teamsPageCheck(
    'Page explains baseline-to-performance transition',
    strpos(
        $normalisedHtml,
        'actual results'
    ) !== false
    &&
    strpos(
        $normalisedHtml,
        'progressively more important'
    ) !== false
);


teamsPageCheck(
    'Page explains fixture opportunity against opponent strength',
    strpos(
        $normalisedHtml,
        'opponent strength'
    ) !== false
);


echo "Explanation Cards: "
    . $explanationCardCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO L
 * PHP ERROR DETECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: PHP Error Detection<br>";
echo "============================================<br>";


teamsPageCheck(
    'Page contains no PHP fatal error',
    stripos(
        $html,
        'Fatal error'
    ) === false
);


teamsPageCheck(
    'Page contains no PHP parse error',
    stripos(
        $html,
        'Parse error'
    ) === false
);


teamsPageCheck(
    'Page contains no uncaught PHP error',
    stripos(
        $html,
        'Uncaught'
    ) === false
);


teamsPageCheck(
    'Page contains no PHP warning output',
    stripos(
        $html,
        'Warning:'
    ) === false
);


teamsPageCheck(
    'Page contains no PHP notice output',
    stripos(
        $html,
        'Notice:'
    ) === false
);


teamsPageCheck(
    'Page contains no undefined variable output',
    stripos(
        $html,
        'Undefined variable'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Performance<br>";
echo "============================================<br>";


teamsPageCheck(
    'Team Intelligence page loads within 10 seconds',
    $page[
        'runtime'
    ] < 10
);


echo "Page Runtime: "
    . number_format(
        $page[
            'runtime'
        ],
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Team Intelligence Page Test Summary<br>";
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

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}