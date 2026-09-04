<?php

/**
 * RecommendationCandidateProductionService
 *
 * Orchestrates existing production recommendation intelligence
 * into the latest mutable Recommendation Candidate.
 *
 * This service does not:
 *
 * - calculate Expected Points itself
 * - calculate Player Intelligence itself
 * - calculate Chip Intelligence itself
 * - rank chips against each other
 * - create a separate Gameweek scoring model
 *
 * It reuses existing production intelligence and delegates the
 * historical evidence transformation to the v0.35 adapters.
 */
class RecommendationCandidateProductionService
{
    private object $playerIntelligenceService;


    private PlayerProjectionEvidence $playerProjectionEvidence;


    private ChipRecommendationEvidence $chipRecommendationEvidence;


    private object $captureService;


    public function __construct(
        object $playerIntelligenceService,
        PlayerProjectionEvidence $playerProjectionEvidence,
        ChipRecommendationEvidence $chipRecommendationEvidence,
        object $captureService
    ) {

        $this->playerIntelligenceService =
            $playerIntelligenceService;


        $this->playerProjectionEvidence =
            $playerProjectionEvidence;


        $this->chipRecommendationEvidence =
            $chipRecommendationEvidence;


        $this->captureService =
            $captureService;
    }


    /**
     * Capture the latest recommendation evidence produced by
     * the existing production intelligence stack.
     */
    public function capture(
        int $gameweekId,
        int $entryId,
        string $generatedAt,
        string $deadlineTime,
        array $importedSquad,
        array $wildcardResult,
        array $freeHitResult,
        array $benchBoostResult,
        array $tripleCaptainResult
    ): bool {

        /*
         * ========================================================
         * VALIDATE HISTORICAL IDENTITY
         * ========================================================
         */

        if ($gameweekId <= 0) {

            throw new InvalidArgumentException(
                'A positive local gameweek ID is required.'
            );
        }


        /*
         * Entry zero is deliberately used by development preview
         * and integration fixtures.
         *
         * Recommendation history must only contain genuine FPL
         * entries.
         */
        if ($entryId <= 0) {

            throw new InvalidArgumentException(
                'A positive FPL entry ID is required.'
            );
        }


        /*
         * ========================================================
         * VALIDATE PRE-DEADLINE TIMING
         * ========================================================
         */

        try {

            $generated =
                new DateTimeImmutable(
                    $generatedAt
                );


            $deadline =
                new DateTimeImmutable(
                    $deadlineTime
                );

        } catch (
            Exception $exception
        ) {

            throw new InvalidArgumentException(
                'Valid recommendation generation and deadline timestamps are required.',
                0,
                $exception
            );
        }


        if (
            $generated
            >=
            $deadline
        ) {

            throw new InvalidArgumentException(
                'Recommendation candidate must be generated before the gameweek deadline.'
            );
        }


        /*
         * ========================================================
         * VALIDATE IMPORTED FPL SQUAD
         * ========================================================
         */

        if (
            (
                $importedSquad[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            throw new InvalidArgumentException(
                'A successful imported FPL squad is required.'
            );
        }


        $importedPlayers =
            $importedSquad[
                'players'
            ]
            ??
            null;


        if (
            !is_array(
                $importedPlayers
            )
            ||
            empty(
                $importedPlayers
            )
        ) {

            throw new InvalidArgumentException(
                'Imported FPL squad players are required.'
            );
        }


        /*
         * ========================================================
         * MAP THROUGH EXISTING PLAYER INTELLIGENCE
         * ========================================================
         *
         * Do not create another FPL-player-ID to local-player-ID
         * mapping path here.
         *
         * PlayerIntelligenceService already owns the production
         * mapping used by Gameweek Intelligence.
         */

        $mappedSquad =
            $this
                ->playerIntelligenceService
                ->buildSquadFromFPLImport(
                    $importedSquad
                );


        if (
            !is_array(
                $mappedSquad
            )
            ||
            !(
                $mappedSquad[
                    'is_complete'
                ]
                ??
                false
            )
        ) {

            throw new RuntimeException(
                'The imported FPL squad could not be mapped completely.'
            );
        }


        $mappedPlayers =
            $mappedSquad[
                'players'
            ]
            ??
            null;


        if (
            !is_array(
                $mappedPlayers
            )
            ||
            empty(
                $mappedPlayers
            )
        ) {

            throw new RuntimeException(
                'The mapped FPL squad does not contain usable players.'
            );
        }


        /*
         * ========================================================
         * EXISTING GAMEWEEK DECISION
         * ========================================================
         */

        $bank =
            is_numeric(
                $mappedSquad[
                    'bank'
                ]
                ??
                null
            )
                ? (float) $mappedSquad[
                    'bank'
                ]
                : (
                    is_numeric(
                        $importedSquad[
                            'bank'
                        ]
                        ??
                        null
                    )
                        ? (float) $importedSquad[
                            'bank'
                        ]
                        : 0.0
                );


        $gameweekDecisionResult =
            $this
                ->playerIntelligenceService
                ->getGameweekDecision(
                    $mappedPlayers,
                    $bank
                );


        if (
            !is_array(
                $gameweekDecisionResult
            )
            ||
            (
                $gameweekDecisionResult[
                    'status'
                ]
                ??
                null
            )
            !==
            'success'
        ) {

            throw new RuntimeException(
                'Existing Gameweek Decision Intelligence could not be generated.'
            );
        }


        /*
         * ========================================================
         * EXISTING PLAYER PROJECTION EVIDENCE
         * ========================================================
         */

        $playerSummaries =
            $this
                ->playerIntelligenceService
                ->getAllPlayerSummaries();


        if (
            !is_array(
                $playerSummaries
            )
            ||
            empty(
                $playerSummaries
            )
        ) {

            throw new RuntimeException(
                'Existing Player Intelligence summaries are unavailable.'
            );
        }


        $playerProjections =
            $this
                ->playerProjectionEvidence
                ->build(
                    $mappedPlayers,
                    $playerSummaries
                );


        /*
         * ========================================================
         * EXISTING CHIP INTELLIGENCE EVIDENCE
         * ========================================================
         *
         * All four results are supplied by the caller because the
         * production Chip Intelligence pipelines have already run.
         *
         * This service must not run or recalculate them again.
         */

        $chipRecommendations =
            $this
                ->chipRecommendationEvidence
                ->build(
                    $wildcardResult,
                    $freeHitResult,
                    $benchBoostResult,
                    $tripleCaptainResult
                );


        /*
         * ========================================================
         * CAPTURE LATEST MUTABLE CANDIDATE
         * ========================================================
         *
         * RecommendationCandidateCaptureService remains the owner
         * of candidate construction and persistence semantics.
         */

        return
            $this
                ->captureService
                ->capture(
                    $gameweekId,
                    $entryId,
                    $generatedAt,
                    $deadlineTime,
                    $playerProjections,
                    $gameweekDecisionResult,
                    $chipRecommendations
                );
    }
}