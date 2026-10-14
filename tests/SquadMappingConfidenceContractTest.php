<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Mapping Confidence Contract Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


function contractCheck(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;

    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


/*
 * Explicit public FPL entry fixture for this integration contract.
 * This test must not depend on the browser's PHP session: the
 * complete regression runner executes tests in isolated processes.
 * Manager selection and session behaviour have separate tests.
 */
$testManagerEntryId = 3158726;


$db =
    (new Database())
        ->getConnection();


$service =
    new PlayerIntelligenceService(
        $db
    );


$importedSquad =
    (new FPLSquadImporter())
        ->importSquad(
            $testManagerEntryId
        );


echo "============================================<br>";
echo "A. Import Preconditions<br>";
echo "============================================<br>";


contractCheck(
    is_array(
        $importedSquad
    )
    &&
    (
        $importedSquad[
            'status'
        ]
        ?? null
    )
    === 'success',
    'A successful public FPL squad is available.'
);


contractCheck(
    count(
        $importedSquad[
            'players'
        ]
        ?? []
    )
    === 15,
    'The imported squad contains exactly fifteen players.'
);


echo "<br>";
echo "============================================<br>";
echo "B. Authoritative Prepared Summaries<br>";
echo "============================================<br>";


$summaries =
    $service
        ->getAllPlayerSummaries();


$summaryByFplId =
    [];


foreach (
    $summaries
    as $summary
) {

    if (
        !is_array(
            $summary
        )
    ) {
        continue;
    }


    $fplPlayerId =
        (int) (
            $summary[
                'fpl_player_id'
            ]
            ?? 0
        );


    if ($fplPlayerId > 0) {

        $summaryByFplId[
            $fplPlayerId
        ] =
            $summary;
    }
}


$allImportedPlayersHaveSummary =
    true;

$allPreparedConfidenceFieldsPresent =
    true;


foreach (
    $importedSquad[
        'players'
    ]
    ?? []
    as $pick
) {

    $fplPlayerId =
        (int) (
            $pick[
                'fpl_player_id'
            ]
            ?? 0
        );


    $summary =
        $summaryByFplId[
            $fplPlayerId
        ]
        ?? null;


    if (
        !is_array(
            $summary
        )
    ) {

        $allImportedPlayersHaveSummary =
            false;

        continue;
    }


    foreach (
        [
            'effective_confidence',
            'team_available_minutes',
            'participation_rate'
        ]
        as $field
    ) {

        if (
            !array_key_exists(
                $field,
                $summary
            )
            ||
            $summary[
                $field
            ]
            === null
        ) {

            $allPreparedConfidenceFieldsPresent =
                false;
        }
    }
}


contractCheck(
    $allImportedPlayersHaveSummary,
    'Every imported player exists in the authoritative prepared summary collection.'
);


contractCheck(
    $allPreparedConfidenceFieldsPresent,
    'Prepared summaries expose effective confidence, team available minutes and participation rate for the imported squad.'
);


echo "<br>";
echo "============================================<br>";
echo "C. Imported Squad Mapping Contract<br>";
echo "============================================<br>";


$mappedSquad =
    $service
        ->buildSquadFromFPLImport(
            $importedSquad
        );


contractCheck(
    is_array(
        $mappedSquad
    )
    &&
    (
        $mappedSquad[
            'is_complete'
        ]
        ?? false
    )
    === true,
    'Imported squad mapping remains complete.'
);


$mappedByFplId =
    [];


foreach (
    $mappedSquad[
        'players'
    ]
    ?? []
    as $player
) {

    if (
        !is_array(
            $player
        )
    ) {
        continue;
    }


    $fplPlayerId =
        (int) (
            $player[
                'fpl_player_id'
            ]
            ?? 0
        );


    if ($fplPlayerId > 0) {

        $mappedByFplId[
            $fplPlayerId
        ] =
            $player;
    }
}


$confidenceFieldsMatch =
    true;

$metadataPreserved =
    true;

$identityPreserved =
    true;


foreach (
    $importedSquad[
        'players'
    ]
    ?? []
    as $pick
) {

    $fplPlayerId =
        (int) (
            $pick[
                'fpl_player_id'
            ]
            ?? 0
        );


    $summary =
        $summaryByFplId[
            $fplPlayerId
        ]
        ?? null;


    $mapped =
        $mappedByFplId[
            $fplPlayerId
        ]
        ?? null;


    if (
        !is_array(
            $summary
        )
        ||
        !is_array(
            $mapped
        )
    ) {

        $confidenceFieldsMatch =
            false;

        $metadataPreserved =
            false;

        $identityPreserved =
            false;

        continue;
    }


    foreach (
        [
            'effective_confidence',
            'team_available_minutes',
            'participation_rate'
        ]
        as $field
    ) {

        $expected =
            $summary[
                $field
            ]
            ?? null;


        $actual =
            $mapped[
                $field
            ]
            ?? null;


        $equivalent =
            $expected
            ===
            $actual;


        if (
            !$equivalent
            &&
            is_numeric(
                $expected
            )
            &&
            is_numeric(
                $actual
            )
        ) {

            $equivalent =
                abs(
                    (float) $expected
                    -
                    (float) $actual
                )
                <
                0.000001;
        }


        if (!$equivalent) {

            $confidenceFieldsMatch =
                false;
        }
    }


    if (
        (
            $mapped[
                'fpl_player_id'
            ]
            ?? null
        )
        !==
        $fplPlayerId
    ) {

        $identityPreserved =
            false;
    }


    $expectedSquadPosition =
        isset(
            $pick[
                'squad_position'
            ]
        )
            ? (int) $pick[
                'squad_position'
            ]
            : null;


    $expectedMultiplier =
        isset(
            $pick[
                'multiplier'
            ]
        )
            ? (int) $pick[
                'multiplier'
            ]
            : null;


    if (
        (
            $mapped[
                'squad_position'
            ]
            ?? null
        )
        !==
        $expectedSquadPosition
        ||
        (
            $mapped[
                'multiplier'
            ]
            ?? null
        )
        !==
        $expectedMultiplier
        ||
        (
            (bool) (
                $mapped[
                    'is_captain'
                ]
                ?? false
            )
        )
        !==
        (
            (bool) (
                $pick[
                    'is_captain'
                ]
                ?? false
            )
        )
        ||
        (
            (bool) (
                $mapped[
                    'is_vice_captain'
                ]
                ?? false
            )
        )
        !==
        (
            (bool) (
                $pick[
                    'is_vice_captain'
                ]
                ?? false
            )
        )
    ) {

        $metadataPreserved =
            false;
    }
}


contractCheck(
    $identityPreserved,
    'Mapped squad preserves imported FPL player identities.'
);


contractCheck(
    $metadataPreserved,
    'Mapped squad preserves current FPL squad position, multiplier, captain and vice-captain metadata.'
);


contractCheck(
    $confidenceFieldsMatch,
    'Mapped squad preserves authoritative prepared effective confidence, team available minutes and participation rate.'
);


echo "<br>";
echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";

echo "Assertions passed: "
    . $passed
    . "<br>";

echo "Assertions failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}
