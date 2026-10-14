<?php

/*
 * ============================================================
 * SHARED MANAGER CONTEXT
 * ============================================================
 *
 * Provides one application-level boundary for resolving the
 * active FPL manager entry.
 *
 * Responsibilities:
 *
 * - initialise PHP session state when required
 * - read an explicitly submitted entry_id form value
 * - preserve valid manager context between page requests
 * - expose the resolved entry as $activeManagerEntryId
 *
 * ManagerContextService owns the resolution rules.
 *
 * FPLSquadImporter remains authoritative for determining
 * whether the resolved FPL entry actually exists and whether
 * its squad is publicly available.
 */


/*
 * ============================================================
 * SESSION
 * ============================================================
 */

if (
    session_status()
    !==
    PHP_SESSION_ACTIVE
) {

    session_start();
}


/*
 * ============================================================
 * EXPLICIT FORM CONTEXT
 * ============================================================
 */

$requestedManagerEntryId =
    null;


$entryIdInput =
    $_POST[
        'entry_id'
    ]
    ?? null;


if (
    $entryIdInput !== null
    &&
    filter_var(
        $entryIdInput,
        FILTER_VALIDATE_INT
    )
    !==
    false
) {

    $validatedEntryId =
        (int) $entryIdInput;


    if (
        $validatedEntryId > 0
    ) {

        $requestedManagerEntryId =
            $validatedEntryId;
    }
}


/*
 * ============================================================
 * RESOLVE ACTIVE MANAGER
 * ============================================================
 */

$managerContextService =
    new ManagerContextService();


$activeManagerEntryId =
    $managerContextService
        ->resolveEntryId(
            $requestedManagerEntryId,
            $_SESSION
        );
