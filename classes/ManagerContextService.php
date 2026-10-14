<?php

/**
 * ManagerContextService
 *
 * Provides the shared manager-context boundary for FPL
 * Intelligence.
 *
 * Its responsibility is deliberately small:
 *
 * - resolve an explicitly supplied FPL entry ID
 * - preserve a valid entry ID for later requests
 * - reuse preserved manager context
 * - build manager-aware internal URLs
 *
 * This service does not:
 *
 * - validate an entry against the FPL API
 * - import a squad
 * - calculate intelligence
 * - create authentication or user accounts
 *
 * FPLSquadImporter remains authoritative for determining
 * whether an FPL entry actually exists and whether its squad
 * is publicly available.
 */
class ManagerContextService
{
    private const SESSION_ENTRY_KEY =
        'fpl_entry_id';


    /*
     * ============================================================
     * RESOLVE ENTRY ID
     * ============================================================
     */

    /**
     * Resolve the active FPL manager entry.
     *
     * A valid explicit entry ID takes precedence and becomes the
     * preserved context for later requests.
     *
     * If explicit input is absent or invalid, an existing valid
     * preserved entry is reused.
     *
     * No synthetic/default entry is created when neither source
     * provides a valid value.
     */
    public function resolveEntryId(
        ?int $explicitEntryId,
        array &$session
    ): ?int {

        /*
         * --------------------------------------------------------
         * EXPLICIT ENTRY
         * --------------------------------------------------------
         */

        if (
            $explicitEntryId !== null
            &&
            $explicitEntryId > 0
        ) {

            $session[
                self::SESSION_ENTRY_KEY
            ] =
                $explicitEntryId;


            return
                $explicitEntryId;
        }


        /*
         * --------------------------------------------------------
         * PRESERVED ENTRY
         * --------------------------------------------------------
         */

        $preservedEntryId =
            $session[
                self::SESSION_ENTRY_KEY
            ]
            ?? null;


        if (
            is_numeric(
                $preservedEntryId
            )
            &&
            (int) $preservedEntryId > 0
        ) {

            return
                (int) $preservedEntryId;
        }


        /*
         * --------------------------------------------------------
         * NO MANAGER CONTEXT
         * --------------------------------------------------------
         */

        return null;
    }


    /*
     * ============================================================
     * BUILD CONTEXT-AWARE URL
     * ============================================================
     */

    /**
     * Build an internal application URL while preserving the
     * active manager entry.
     *
     * Existing query parameters are retained.
     *
     * An existing entry_id parameter is replaced so URLs never
     * contain duplicate manager context.
     */
    public function buildUrl(
        string $url,
        ?int $entryId
    ): string {

        /*
         * No manager context means the original URL should remain
         * untouched.
         */
        if (
            $entryId === null
            ||
            $entryId <= 0
        ) {

            return $url;
        }


        /*
         * --------------------------------------------------------
         * PRESERVE URL FRAGMENT
         * --------------------------------------------------------
         */

        $fragment =
            '';


        $fragmentPosition =
            strpos(
                $url,
                '#'
            );


        if (
            $fragmentPosition
            !==
            false
        ) {

            $fragment =
                substr(
                    $url,
                    $fragmentPosition
                );


            $url =
                substr(
                    $url,
                    0,
                    $fragmentPosition
                );
        }


        /*
         * --------------------------------------------------------
         * SPLIT PATH AND QUERY
         * --------------------------------------------------------
         */

        $queryPosition =
            strpos(
                $url,
                '?'
            );


        if (
            $queryPosition
            ===
            false
        ) {

            $path =
                $url;


            $query =
                '';

        } else {

            $path =
                substr(
                    $url,
                    0,
                    $queryPosition
                );


            $query =
                substr(
                    $url,
                    $queryPosition
                    +
                    1
                );
        }


        /*
         * --------------------------------------------------------
         * PARSE EXISTING QUERY
         * --------------------------------------------------------
         */

        $queryParameters =
            [];


        if (
            $query !==
            ''
        ) {

            parse_str(
                $query,
                $queryParameters
            );
        }


        /*
         * --------------------------------------------------------
         * APPLY MANAGER CONTEXT
         * --------------------------------------------------------
         */

        $queryParameters[
            'entry_id'
        ] =
            $entryId;


        $queryString =
            http_build_query(
                $queryParameters
            );


        /*
         * --------------------------------------------------------
         * BUILD URL
         * --------------------------------------------------------
         */

        $result =
            $path;


        if (
            $queryString !==
            ''
        ) {

            $result .=
                '?'
                .
                $queryString;
        }


        $result .=
            $fragment;


        return $result;
    }
}