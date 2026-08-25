<?php

echo "============================================<br>";
echo "Team Intelligence Profile Page Test<br>";
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

function teamProfilePageCheck(
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

function teamProfilePageRequest(
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

        'runtime' =>
            $runtime
    ];
}


/*
 * ============================================================
 * NORMALISE HTML
 * ============================================================
 */

function teamProfilePageNormalise(
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
 * URLS
 * ============================================================
 */

$baseUrl =
    'http://localhost:8008/fpl-intelligence/public/team.php?id=1';


$invalidUrl =
    'http://localhost:8008/fpl-intelligence/public/team.php?id=999999';


echo "Profile URL: "
    . htmlspecialchars(
        $baseUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Invalid URL: "
    . htmlspecialchars(
        $invalidUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * REQUESTS
 * ============================================================
 */

$page =
    teamProfilePageRequest(
        $baseUrl
    );


$invalidPage =
    teamProfilePageRequest(
        $invalidUrl
    );


$html =
    $page[
        'html'
    ];


$normalisedHtml =
    teamProfilePageNormalise(
        $html
    );


$invalidHtml =
    $invalidPage[
        'html'
    ];


$normalisedInvalidHtml =
    teamProfilePageNormalise(
        $invalidHtml
    );


/*
 * ============================================================
 * SCENARIO A
 * VALID PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Valid Team Profile Request<br>";
echo "============================================<br>";


teamProfilePageCheck(
    'Team profile page request succeeds',
    $page[
        'status'
    ] === 200
);


teamProfilePageCheck(
    'Team profile page returns HTML',
    $html !== ''
);


teamProfilePageCheck(
    'Team profile page contains document structure',
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


teamProfilePageCheck(
    'Application shell is present',
    strpos(
        $normalisedHtml,
        'class="app-shell"'
    ) !== false
);


teamProfilePageCheck(
    'Application content wrapper is present',
    strpos(
        $normalisedHtml,
        'class="app-content"'
    ) !== false
);


teamProfilePageCheck(
    'Shared topbar is present',
    strpos(
        $normalisedHtml,
        'class="topbar"'
    ) !== false
);


teamProfilePageCheck(
    'Team profile dashboard wrapper is present',
    strpos(
        $normalisedHtml,
        'class="dashboard team-profile-dashboard"'
    ) !== false
);


teamProfilePageCheck(
    'Teams navigation remains active',
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


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * TEAM IDENTITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Team Identity<br>";
echo "============================================<br>";


teamProfilePageCheck(
    'Team Intelligence Profile eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'Team Intelligence Profile'
    ) !== false
);


teamProfilePageCheck(
    'Arsenal team title is rendered',
    preg_match(
        '/<h1>\s*Arsenal\s*<\/h1>/i',
        $normalisedHtml
    ) === 1
);


teamProfilePageCheck(
    'Back to Team Intelligence link is rendered',
    strpos(
        $normalisedHtml,
        'href="teams.php"'
    ) !== false
    &&
    strpos(
        $normalisedHtml,
        'Back to Team Intelligence'
    ) !== false
);


teamProfilePageCheck(
    'League rank section is rendered',
    strpos(
        $normalisedHtml,
        'League Rank'
    ) !== false
);


teamProfilePageCheck(
    'Arsenal is ranked first in current dataset',
    preg_match(
        '/#\s*1\s*Arsenal/i',
        $normalisedHtml
    ) === 1
);


teamProfilePageCheck(
    'Arsenal short name is rendered',
    preg_match(
        '/class="team-profile-short-name">\s*ARS\s*<\/p>/i',
        $normalisedHtml
    ) === 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * TEAM INTELLIGENCE HERO
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Team Intelligence Hero<br>";
echo "============================================<br>";


teamProfilePageCheck(
    'Team Intelligence hero card is rendered',
    strpos(
        $normalisedHtml,
        'class="team-profile-hero"'
    ) !== false
);


$teamIntelligenceScore =
    null;


if (
    preg_match(
        '/Team Intelligence\s*<\/span>\s*<strong>\s*([0-9]+(?:\.[0-9]+)?)\s*<\/strong>/i',
        $normalisedHtml,
        $teamIntelligenceScoreMatches
    )
    === 1
) {

    $teamIntelligenceScore =
        (float) (
            $teamIntelligenceScoreMatches[
                1
            ]
            ?? 0
        );
}


teamProfilePageCheck(
    'Team Intelligence Score is rendered within 0-100',
    is_numeric(
        $teamIntelligenceScore
    )
    &&
    $teamIntelligenceScore >= 0
    &&
    $teamIntelligenceScore <= 100
);


echo "Rendered Team Intelligence Score: "
    . (
        is_numeric(
            $teamIntelligenceScore
        )
            ? number_format(
                $teamIntelligenceScore,
                2
            )
            : 'N/A'
    )
    . "<br>";


teamProfilePageCheck(
    'Elite Team Intelligence badge is rendered',
    strpos(
        $normalisedHtml,
        'team-intelligence-badge-elite'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * STRENGTH PROFILE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Team Strength Profile<br>";
echo "============================================<br>";


teamProfilePageCheck(
    'Team Strength eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'Team Strength'
    ) !== false
);


teamProfilePageCheck(
    'Current Strength Profile section is rendered',
    strpos(
        $normalisedHtml,
        'Current Strength Profile'
    ) !== false
);


$strengthCardCount =
    substr_count(
        $normalisedHtml,
        'class="team-profile-summary-card"'
    );


teamProfilePageCheck(
    'Strength profile contains exactly five summary cards',
    $strengthCardCount === 5
);


foreach (
    [
        'Overall',
        'Home',
        'Away',
        'Fixture Rating',
        'Fixture Trend'
    ]
    as $label
) {

    teamProfilePageCheck(
        'Strength summary contains: '
            . $label,
        strpos(
            $normalisedHtml,
            $label
        ) !== false
    );
}


teamProfilePageCheck(
    'Overall strength value is rendered',
    preg_match(
        '/Overall\s*<\/span>\s*<strong>\s*100\.0\s*<\/strong>/i',
        $normalisedHtml
    ) === 1
);


teamProfilePageCheck(
    'Home strength value is rendered',
    preg_match(
        '/Home\s*<\/span>\s*<strong>\s*100\.0\s*<\/strong>/i',
        $normalisedHtml
    ) === 1
);


teamProfilePageCheck(
    'Away strength value is rendered',
    preg_match(
        '/Away\s*<\/span>\s*<strong>\s*100\.0\s*<\/strong>/i',
        $normalisedHtml
    ) === 1
);


teamProfilePageCheck(
    'Fixture rating value is rendered',
    preg_match(
        '/Fixture Rating\s*<\/span>\s*<strong>\s*[0-9]+(?:\.[0-9]+)?\s*<\/strong>/i',
        $normalisedHtml
    ) === 1
);


teamProfilePageCheck(
    'Fixture trend value is rendered',
    strpos(
        $normalisedHtml,
        'Stable'
    ) !== false
);


echo "Strength Cards: "
    . $strengthCardCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * FIXTURE INTELLIGENCE SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Fixture Intelligence Summary<br>";
echo "============================================<br>";


teamProfilePageCheck(
    'Fixture Intelligence eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'Fixture Intelligence'
    ) !== false
);


teamProfilePageCheck(
    'Upcoming Fixture Opportunity section is rendered',
    strpos(
        $normalisedHtml,
        'Upcoming Fixture Opportunity'
    ) !== false
);


$fixtureClassification =
    null;


if (
    preg_match(
        '/class="team-fixture-badge team-fixture-badge-([a-z-]+)"[^>]*>\s*([^<]+)\s*<\/span>/i',
        $normalisedHtml,
        $fixtureClassificationMatches
    )
    === 1
) {

    $fixtureClassification = [

        'class' =>
            strtolower(
                trim(
                    (string) (
                        $fixtureClassificationMatches[
                            1
                        ]
                        ?? ''
                    )
                )
            ),

        'label' =>
            strtolower(
                trim(
                    (string) (
                        $fixtureClassificationMatches[
                            2
                        ]
                        ?? ''
                    )
                )
            )
    ];
}


$supportedFixtureClassifications = [

    'excellent' =>
        'excellent',

    'good' =>
        'good',

    'average' =>
        'average',

    'difficult' =>
        'difficult',

    'very-difficult' =>
        'very difficult'
];


teamProfilePageCheck(
    'Supported fixture classification is rendered',
    is_array(
        $fixtureClassification
    )
    &&
    isset(
        $supportedFixtureClassifications[
            $fixtureClassification[
                'class'
            ]
        ]
    )
    &&
    $supportedFixtureClassifications[
        $fixtureClassification[
            'class'
        ]
    ]
    ===
    $fixtureClassification[
        'label'
    ]
);


echo "Rendered Fixture Classification: "
    . (
        is_array(
            $fixtureClassification
        )
            ? htmlspecialchars(
                ucwords(
                    $fixtureClassification[
                        'label'
                    ]
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            : 'N/A'
    )
    . "<br>";


foreach (
    [
        'Next 5 Rating',
        'Next 6',
        'Next 8',
        'Next 10'
    ]
    as $ratingLabel
) {

    teamProfilePageCheck(
        $ratingLabel
        . ' rating is rendered',
        preg_match(
            '/'
            . preg_quote(
                $ratingLabel,
                '/'
            )
            . '\s*<\/span>\s*<strong>\s*[0-9]+(?:\.[0-9]+)?\s*<\/strong>/i',
            $normalisedHtml
        )
        === 1
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * UPCOMING FIXTURE CARDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Upcoming Fixture Cards<br>";
echo "============================================<br>";


$fixtureCardCount =
    substr_count(
        $normalisedHtml,
        'class="team-profile-fixture-card"'
    );


teamProfilePageCheck(
    'Exactly ten upcoming fixture cards are rendered',
    $fixtureCardCount === 10
);


teamProfilePageCheck(
    'Fixture cards run from one to ten',
    substr_count(
        $normalisedHtml,
        'class="team-profile-fixture-number"'
    ) === 10
);


$fixtureOpponentCount =
    substr_count(
        $normalisedHtml,
        'class="team-profile-fixture-opponent"'
    );


$fixtureOpponentMatches =
    [];


preg_match_all(
    '/class="team-profile-fixture-card"[^>]*>.*?<strong>\s*([^<]+?)\s*<\/strong>/i',
    $normalisedHtml,
    $fixtureOpponentMatches
);


$fixtureOpponents =
    $fixtureOpponentMatches[
        1
    ]
    ?? [];


$allFixtureOpponentsValid =
    count(
        $fixtureOpponents
    )
    ===
    $fixtureCardCount;


foreach (
    $fixtureOpponents
    as $opponent
) {

    $opponent =
        trim(
            html_entity_decode(
                $opponent,
                ENT_QUOTES,
                'UTF-8'
            )
        );


    if (
        $opponent === ''
        ||
        strcasecmp(
            $opponent,
            'Unknown'
        )
        === 0
    ) {

        $allFixtureOpponentsValid =
            false;

        break;
    }
}


teamProfilePageCheck(
    'All ten upcoming fixture cards expose an opponent',
    $fixtureCardCount === 10
    &&
    $allFixtureOpponentsValid
);


echo "Fixture Opponents Found: "
    . count(
        $fixtureOpponents
    )
    . "<br>";


teamProfilePageCheck(
    'Fixture cards expose Home and Away venues',
    preg_match(
        '/>\s*Home\s*<\/span>/i',
        $normalisedHtml
    ) === 1
    &&
    preg_match(
        '/>\s*Away\s*<\/span>/i',
        $normalisedHtml
    ) === 1
);


teamProfilePageCheck(
    'Fixture cards expose gameweek numbers',
    strpos(
        $normalisedHtml,
        'GW 1'
    ) !== false
    &&
    strpos(
        $normalisedHtml,
        'GW 10'
    ) !== false
);


echo "Fixture Cards: "
    . $fixtureCardCount
    . "<br>";


/*
 * ============================================================
 * SCENARIO H
 * CURRENT FORM
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Current Form<br>";
echo "============================================<br>";


teamProfilePageCheck(
    'Current Form eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'Current Form'
    ) !== false
);


teamProfilePageCheck(
    'Premier League Performance section is rendered',
    strpos(
        $normalisedHtml,
        'Premier League Performance'
    ) !== false
);

teamProfilePageCheck(
    'Attack and Defence Rating explanation is rendered',
    strpos(
        $normalisedHtml,
        'Attack and Defence Ratings use completed Premier League matches only'
    ) !== false
);


teamProfilePageCheck(
    'Performance explanation states ratings require completed fixtures',
    strpos(
        $normalisedHtml,
        'played at least one league fixture'
    ) !== false
);


$formCardCount =
    substr_count(
        $normalisedHtml,
        'team-profile-form-grid'
    );


teamProfilePageCheck(
    'Team form grid is rendered',
    $formCardCount === 1
);


foreach (
    [
        'Recent Form',
        'Played',
        'Wins',
        'Draws',
        'Losses',
        'Goal Difference',
        'Attack Rating',
        'Defence Rating'
    ]
    as $label
) {

    teamProfilePageCheck(
        'Current form contains: '
            . $label,
        strpos(
            $normalisedHtml,
            $label
        ) !== false
    );
}

teamProfilePageCheck(
    'Attack Rating explanation is rendered',
    strpos(
        $normalisedHtml,
        'Goals scored per game'
    ) !== false
);


teamProfilePageCheck(
    'Defence Rating explanation is rendered',
    strpos(
        $normalisedHtml,
        'Goals conceded per game'
    ) !== false
);

$attackRatingRendered =
    preg_match(
        '/Attack Rating\s*<\/span>\s*<strong>\s*([^<]+)\s*<\/strong>/i',
        $normalisedHtml,
        $attackRatingMatch
    )
    === 1;


$defenceRatingRendered =
    preg_match(
        '/Defence Rating\s*<\/span>\s*<strong>\s*([^<]+)\s*<\/strong>/i',
        $normalisedHtml,
        $defenceRatingMatch
    )
    === 1;


teamProfilePageCheck(
    'Attack Rating value is rendered',
    $attackRatingRendered
);


teamProfilePageCheck(
    'Defence Rating value is rendered',
    $defenceRatingRendered
);


$attackRatingValue =
    trim(
        (string) (
            $attackRatingMatch[
                1
            ]
            ?? ''
        )
    );


$defenceRatingValue =
    trim(
        (string) (
            $defenceRatingMatch[
                1
            ]
            ?? ''
        )
    );


$attackRatingValid =
    $attackRatingValue === '—'
    ||
    (
        is_numeric(
            $attackRatingValue
        )
        &&
        (float) $attackRatingValue >= 0
        &&
        (float) $attackRatingValue <= 100
    );


$defenceRatingValid =
    $defenceRatingValue === '—'
    ||
    (
        is_numeric(
            $defenceRatingValue
        )
        &&
        (float) $defenceRatingValue >= 0
        &&
        (float) $defenceRatingValue <= 100
    );


teamProfilePageCheck(
    'Rendered Attack Rating is numeric 0-100 or unavailable',
    $attackRatingValid
);


teamProfilePageCheck(
    'Rendered Defence Rating is numeric 0-100 or unavailable',
    $defenceRatingValid
);


echo "Rendered Attack Rating: "
    . htmlspecialchars(
        $attackRatingValue,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Rendered Defence Rating: "
    . htmlspecialchars(
        $defenceRatingValue,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * FPL PLAYER INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: FPL Player Intelligence<br>";
echo "============================================<br>";


teamProfilePageCheck(
    'FPL Players eyebrow is rendered',
    strpos(
        $normalisedHtml,
        'FPL Players'
    ) !== false
);


teamProfilePageCheck(
    'Current Player Intelligence section is rendered',
    strpos(
        $normalisedHtml,
        'Current Player Intelligence'
    ) !== false
);


teamProfilePageCheck(
    'Player Intelligence description is rendered',
    strpos(
        $normalisedHtml,
        'ranked by Player Intelligence'
    ) !== false
);


teamProfilePageCheck(
    'Player table wrapper is rendered',
    strpos(
        $normalisedHtml,
        'class="team-profile-player-table-wrapper"'
    ) !== false
);


teamProfilePageCheck(
    'Player table is rendered',
    strpos(
        $normalisedHtml,
        'class="team-profile-player-table"'
    ) !== false
);


$playerLinkCount =
    substr_count(
        $normalisedHtml,
        'class="team-profile-player-link"'
    );


teamProfilePageCheck(
    'Arsenal profile renders a substantial current player squad',
    $playerLinkCount >= 20
);


teamProfilePageCheck(
    'Player table links to Player Intelligence profiles',
    strpos(
        $normalisedHtml,
        'href="player.php?id='
    ) !== false
);


foreach (
    [
        'Raya',
        'Gabriel',
        'Calafiori',
        'Saka',
        'Gyökeres'
    ]
    as $playerName
) {

    teamProfilePageCheck(
        'Arsenal player is rendered: '
            . $playerName,
        strpos(
            $normalisedHtml,
            $playerName
        ) !== false
    );
}


echo "Player Links: "
    . $playerLinkCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO J
 * PLAYER TABLE STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Player Table Structure<br>";
echo "============================================<br>";


foreach (
    [
        'Player',
        'Pos',
        'Price',
        'Intelligence',
        'Strength',
        'Value',
        'Fixtures',
        'Availability'
    ]
    as $header
) {

    teamProfilePageCheck(
        'Player table contains column: '
            . $header,
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


teamProfilePageCheck(
    'Player table contains a table head',
    strpos(
        $normalisedHtml,
        '<thead>'
    ) !== false
);


teamProfilePageCheck(
    'Player table contains a table body',
    strpos(
        $normalisedHtml,
        '<tbody>'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * INVALID TEAM PROFILE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Invalid Team Profile<br>";
echo "============================================<br>";


teamProfilePageCheck(
    'Invalid team profile request returns HTTP 200',
    $invalidPage[
        'status'
    ] === 200
);


teamProfilePageCheck(
    'Invalid team profile renders controlled error state',
    strpos(
        $normalisedInvalidHtml,
        'The requested Team Intelligence team could not be found.'
    ) !== false
);


teamProfilePageCheck(
    'Invalid profile does not render Team Intelligence hero',
    strpos(
        $normalisedInvalidHtml,
        'class="team-profile-hero"'
    ) === false
);


teamProfilePageCheck(
    'Invalid profile retains navigation back to Team Intelligence',
    strpos(
        $normalisedInvalidHtml,
        'href="teams.php"'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * PHP ERROR DETECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: PHP Error Detection<br>";
echo "============================================<br>";


$errorPatterns = [

    'Fatal error',
    'Parse error',
    'Uncaught',
    'Warning:',
    'Notice:',
    'Undefined variable'
];


foreach (
    $errorPatterns
    as $errorPattern
) {

    teamProfilePageCheck(
        'Valid profile contains no '
            . $errorPattern,
        stripos(
            $html,
            $errorPattern
        ) === false
    );


    teamProfilePageCheck(
        'Invalid profile contains no '
            . $errorPattern,
        stripos(
            $invalidHtml,
            $errorPattern
        ) === false
    );
}


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


teamProfilePageCheck(
    'Valid Team Intelligence profile page loads within 10 seconds',
    $page[
        'runtime'
    ] < 10
);


teamProfilePageCheck(
    'Invalid Team Intelligence profile page loads within 10 seconds',
    $invalidPage[
        'runtime'
    ] < 10
);


echo "Valid Runtime: "
    . number_format(
        $page[
            'runtime'
        ],
        4
    )
    . " seconds<br>";


echo "Invalid Runtime: "
    . number_format(
        $invalidPage[
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
echo "Team Intelligence Profile Page Test Summary<br>";
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