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


$assessment =
    new PlayerAssessment();


echo "============================================<br>";
echo "Player Assessment Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * SCENARIO A
 * STRONG ESTABLISHED PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Strong Established Player<br>";
echo "============================================<br>";


$strongProfile = [

    'summary' => [

        'strength_rating' => 72.00,

        'value_rating' => 70.00,

        'fixture_rating' => 75.00,

        'availability_rating' => 100.00,

        'intelligence_score' => 72.50
    ],

    'performance' => [

        'sample_confidence' => 1.00
    ],

    'fixtures' => [

        'trend' => 'Stable'
    ]
];


$strongAssessment =
    $assessment->buildAssessment(
        $strongProfile
    );


testPass(
    'Strong profile returns an array',
    is_array(
        $strongAssessment
    )
);


testPass(
    'Strong player receives Strong FPL Option verdict',
    $strongAssessment['verdict']
    === 'Strong FPL Option'
);


testPass(
    'High underlying strength is recognised',
    in_array(
        'High underlying player strength.',
        $strongAssessment['strengths'],
        true
    )
);


testPass(
    'Full performance sample is recognised',
    in_array(
        'Backed by a full performance sample.',
        $strongAssessment['strengths'],
        true
    )
);


/*
 * ============================================================
 * SCENARIO B
 * SMALL SAMPLE UPSIDE PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Small Sample Upside<br>";
echo "============================================<br>";


$smallSampleProfile = [

    'summary' => [

        'strength_rating' => 60.00,

        'value_rating' => 75.00,

        'fixture_rating' => 75.00,

        'availability_rating' => 100.00,

        'intelligence_score' => 65.00
    ],

    'performance' => [

        'sample_confidence' => 0.17
    ],

    'fixtures' => [

        'trend' => 'Stable'
    ]
];


$smallSampleAssessment =
    $assessment->buildAssessment(
        $smallSampleProfile
    );


testPass(
    'Small sample upside player becomes High-Upside Watchlist',
    $smallSampleAssessment['verdict']
    === 'High-Upside Watchlist'
);


testPass(
    'Very small sample is identified as a concern',
    in_array(
        'Very small performance sample.',
        $smallSampleAssessment[
            'concerns'
        ],
        true
    )
);


/*
 * ============================================================
 * SCENARIO C
 * AVAILABILITY RISK
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Availability Risk<br>";
echo "============================================<br>";


$unavailableProfile = [

    'summary' => [

        'strength_rating' => 80.00,

        'value_rating' => 80.00,

        'fixture_rating' => 80.00,

        'availability_rating' => 20.00,

        'intelligence_score' => 75.00
    ],

    'performance' => [

        'sample_confidence' => 1.00
    ],

    'fixtures' => [

        'trend' => 'Stable'
    ]
];


$unavailableAssessment =
    $assessment->buildAssessment(
        $unavailableProfile
    );


testPass(
    'Major availability risk overrides strong intelligence',
    $unavailableAssessment['verdict']
    === 'Avoid for Now'
);


testPass(
    'Major availability concern is listed',
    in_array(
        'Major availability concern.',
        $unavailableAssessment[
            'concerns'
        ],
        true
    )
);


/*
 * ============================================================
 * SCENARIO D
 * DECLINING FIXTURES
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Declining Fixtures<br>";
echo "============================================<br>";


$decliningProfile = [

    'summary' => [

        'strength_rating' => 68.00,

        'value_rating' => 65.00,

        'fixture_rating' => 72.00,

        'availability_rating' => 100.00,

        'intelligence_score' => 69.00
    ],

    'performance' => [

        'sample_confidence' => 1.00
    ],

    'fixtures' => [

        'trend' => 'Declining'
    ]
];


$decliningAssessment =
    $assessment->buildAssessment(
        $decliningProfile
    );


testPass(
    'Declining fixtures are identified as a concern',
    in_array(
        'Fixture opportunity becomes less favourable over the longer run.',
        $decliningAssessment[
            'concerns'
        ],
        true
    )
);


/*
 * ============================================================
 * SCENARIO E
 * IMPROVING FIXTURES
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Improving Fixtures<br>";
echo "============================================<br>";


$improvingProfile =
    $decliningProfile;


$improvingProfile[
    'fixtures'
]['trend'] =
    'Improving';


$improvingAssessment =
    $assessment->buildAssessment(
        $improvingProfile
    );


testPass(
    'Improving fixtures are recognised as a strength',
    in_array(
        'Fixture opportunity improves across the upcoming run.',
        $improvingAssessment[
            'strengths'
        ],
        true
    )
);


/*
 * ============================================================
 * SCENARIO F
 * WEAK PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Weak Player<br>";
echo "============================================<br>";


$weakProfile = [

    'summary' => [

        'strength_rating' => 35.00,

        'value_rating' => 30.00,

        'fixture_rating' => 40.00,

        'availability_rating' => 100.00,

        'intelligence_score' => 39.00
    ],

    'performance' => [

        'sample_confidence' => 1.00
    ],

    'fixtures' => [

        'trend' => 'Stable'
    ]
];


$weakAssessment =
    $assessment->buildAssessment(
        $weakProfile
    );


testPass(
    'Weak player receives Avoid for Now verdict',
    $weakAssessment['verdict']
    === 'Avoid for Now'
);


testPass(
    'Weak player strength is identified',
    in_array(
        'Underlying player strength is currently weak.',
        $weakAssessment[
            'concerns'
        ],
        true
    )
);


testPass(
    'Poor value is identified',
    in_array(
        'Current price represents relatively poor value.',
        $weakAssessment[
            'concerns'
        ],
        true
    )
);


/*
 * ============================================================
 * SCENARIO G
 * MISSING DATA
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Missing Data<br>";
echo "============================================<br>";


$missingAssessment =
    $assessment->buildAssessment(
        []
    );


testPass(
    'Missing intelligence returns Insufficient Data verdict',
    $missingAssessment['verdict']
    === 'Insufficient Data'
);


testPass(
    'Missing component labels remain safe',
    $missingAssessment[
        'components'
    ]['strength']
    === 'Unknown'
);


/*
 * ============================================================
 * SCENARIO H
 * RATING LABELS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Rating Labels<br>";
echo "============================================<br>";


testPass(
    '80 rating is Excellent',
    $assessment->getRatingLabel(
        80
    )
    === 'Excellent'
);


testPass(
    '70 rating is Strong',
    $assessment->getRatingLabel(
        70
    )
    === 'Strong'
);


testPass(
    '60 rating is Average',
    $assessment->getRatingLabel(
        60
    )
    === 'Average'
);


testPass(
    '50 rating is Below Average',
    $assessment->getRatingLabel(
        50
    )
    === 'Below Average'
);


testPass(
    '30 rating is Weak',
    $assessment->getRatingLabel(
        30
    )
    === 'Weak'
);


/*
 * ============================================================
 * SCENARIO I
 * CONFIDENCE LABELS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Confidence Labels<br>";
echo "============================================<br>";


testPass(
    'Full confidence is labelled Full',
    $assessment->getConfidenceLabel(
        1.00
    )
    === 'Full'
);


testPass(
    'High confidence is labelled High',
    $assessment->getConfidenceLabel(
        0.80
    )
    === 'High'
);


testPass(
    'Moderate confidence is labelled Moderate',
    $assessment->getConfidenceLabel(
        0.60
    )
    === 'Moderate'
);


testPass(
    'Low confidence is labelled Low',
    $assessment->getConfidenceLabel(
        0.30
    )
    === 'Low'
);


testPass(
    'Very low confidence is labelled Very Low',
    $assessment->getConfidenceLabel(
        0.15
    )
    === 'Very Low'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Player Assessment Test Summary<br>";
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