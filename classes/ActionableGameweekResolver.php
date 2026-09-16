<?php

/**
 * ActionableGameweekResolver
 *
 * Resolves the FPL gameweek that is currently actionable from
 * an explicit timestamp.
 *
 * The authoritative boundary is the first gameweek deadline
 * strictly after that timestamp.
 *
 * GameweekRepository retains ownership of deadline lookup.
 * This resolver translates the repository result into the
 * FPL gameweek identity required by projection and chip
 * intelligence services.
 */
class ActionableGameweekResolver
{
    private object
        $gameweekRepository;


    public function __construct(
        object $gameweekRepository
    ) {

        $this->gameweekRepository =
            $gameweekRepository;
    }


    /**
     * Resolve the first FPL gameweek whose deadline is strictly
     * after the supplied timestamp.
     *
     * Returns null when no future deadline exists.
     */
    public function resolve(
        string $timestamp
    ): ?int {

        $gameweek =
            $this
                ->gameweekRepository
                ->getNextDeadlineAfter(
                    $timestamp
                );


        /*
         * End-of-season, or otherwise no future deadline.
         *
         * This is a valid state rather than an application error.
         */
        if (
            $gameweek === null
        ) {

            return
                null;
        }


        /*
         * Projection and chip intelligence use the official FPL
         * gameweek number rather than the local gameweeks.id
         * primary key.
         */
        $fplGameweekId =
            is_numeric(
                $gameweek[
                    'fpl_gameweek_id'
                ]
                ?? null
            )
                ? (int) $gameweek[
                    'fpl_gameweek_id'
                ]
                : 0;


        if (
            $fplGameweekId <= 0
        ) {

            throw new RuntimeException(
                'Resolved actionable gameweek does not contain a valid FPL gameweek ID.'
            );
        }


        return
            $fplGameweekId;
    }
}