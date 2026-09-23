<?php

/*
 * ============================================================
 * SHARED DATA HEALTH EVALUATION
 * ============================================================
 *
 * Provides a common boundary for evaluating the health of the
 * update feeds required by an intelligence surface.
 *
 * The caller remains responsible for deciding which update
 * types the current page depends on.
 *
 * UpdateHealthService remains the authoritative source of
 * health semantics.
 */


/*
 * ============================================================
 * EVALUATE DATA HEALTH
 * ============================================================
 */

function evaluateDataHealth(
    PDO $db,
    array $updateTypes,
    string $now,
    int $freshnessSeconds
): array {

    /*
     * No dependencies means there is nothing to evaluate.
     *
     * Returning early also avoids constructing repository and
     * service objects unnecessarily.
     */

    if (empty($updateTypes)) {

        return [];
    }


    /*
     * ========================================================
     * NORMALISE REQUESTED UPDATE TYPES
     * ========================================================
     */

    $normalisedTypes =
        [];


    foreach (
        $updateTypes
        as $updateType
    ) {

        if (!is_string($updateType)) {

            throw new InvalidArgumentException(
                'Update type must be a string.'
            );
        }


        $updateType =
            trim(
                $updateType
            );


        if ($updateType === '') {

            throw new InvalidArgumentException(
                'Update type cannot be empty.'
            );
        }


        /*
         * Associative keys provide deterministic de-duplication
         * while preserving first-requested order.
         */

        $normalisedTypes[
            $updateType
        ] =
            true;
    }


    /*
     * ========================================================
     * HEALTH SERVICE
     * ========================================================
     */

    $repository =
        new UpdateRunRepository(
            $db
        );


    $service =
        new UpdateHealthService(
            $repository
        );


    /*
     * ========================================================
     * EVALUATE REQUESTED FEEDS
     * ========================================================
     */

    $health =
        [];


    foreach (
        array_keys(
            $normalisedTypes
        )
        as $updateType
    ) {

        $health[
            $updateType
        ] =
            $service->evaluate(
                $updateType,
                $now,
                $freshnessSeconds
            );
    }


    return $health;
}