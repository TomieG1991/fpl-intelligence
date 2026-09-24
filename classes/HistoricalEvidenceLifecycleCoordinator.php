<?php

/**
 * HistoricalEvidenceLifecycleCoordinator
 *
 * Coordinates the safe operational lifecycle required to
 * preserve genuine historical evidence.
 *
 * This lifecycle deliberately remains separate from the live
 * DataUpdateCoordinator.
 *
 * Order:
 *
 * 1. Promote eligible recommendation candidates.
 * 2. Promote eligible player snapshot candidates.
 * 3. Capture the latest player state for the next deadline.
 *
 * Recommendation candidate capture is deliberately excluded.
 * That operation requires genuine manager-specific production
 * recommendation evidence.
 */
class HistoricalEvidenceLifecycleCoordinator
{
    private $recommendationPromotionRunner;

    private $playerSnapshotPromotionRunner;

    private $playerSnapshotCaptureRunner;


    /*
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */

    public function __construct(
        callable $recommendationPromotionRunner,
        callable $playerSnapshotPromotionRunner,
        callable $playerSnapshotCaptureRunner
    ) {

        $this->recommendationPromotionRunner =
            $recommendationPromotionRunner;

        $this->playerSnapshotPromotionRunner =
            $playerSnapshotPromotionRunner;

        $this->playerSnapshotCaptureRunner =
            $playerSnapshotCaptureRunner;
    }


    /*
     * ============================================================
     * RUN
     * ============================================================
     */

    public function run(): array
    {

        $steps = [];


        /*
         * ========================================================
         * RECOMMENDATION CANDIDATE PROMOTION
         * ========================================================
         */

        $recommendationResult =
            $this->runStep(
                'recommendation_promotion',
                $this->recommendationPromotionRunner,
                $steps
            );


        if (!$recommendationResult) {

            return [
                'status' => 'Failed',
                'steps' => $steps
            ];
        }


        /*
         * ========================================================
         * PLAYER SNAPSHOT CANDIDATE PROMOTION
         * ========================================================
         *
         * Promotion occurs before the next candidate capture.
         *
         * This ensures any candidate whose preserved deadline has
         * now passed is given the opportunity to become immutable
         * historical evidence before current player state is
         * captured for the next future deadline.
         */

        $playerPromotionResult =
            $this->runStep(
                'player_snapshot_promotion',
                $this->playerSnapshotPromotionRunner,
                $steps
            );


        if (!$playerPromotionResult) {

            return [
                'status' => 'Failed',
                'steps' => $steps
            ];
        }


        /*
         * ========================================================
         * PLAYER SNAPSHOT CANDIDATE CAPTURE
         * ========================================================
         */

        $playerCaptureResult =
            $this->runStep(
                'player_snapshot_capture',
                $this->playerSnapshotCaptureRunner,
                $steps
            );


        if (!$playerCaptureResult) {

            return [
                'status' => 'Failed',
                'steps' => $steps
            ];
        }


        /*
         * ========================================================
         * SUCCESS
         * ========================================================
         */

        return [
            'status' => 'Success',
            'steps' => $steps
        ];
    }


    /*
     * ============================================================
     * RUN STEP
     * ============================================================
     *
     * Historical-evidence operations are deliberately
     * fail-closed.
     *
     * A later lifecycle operation must not continue after an
     * earlier operation has failed or returned an unexpected
     * status.
     */

    private function runStep(
        string $stepName,
        callable $runner,
        array &$steps
    ): bool {

        try {

            $result =
                call_user_func(
                    $runner
                );

        } catch (
            Throwable $exception
        ) {

            $steps[
                $stepName
            ] = [
                'status' => 'Failed',
                'error_message' =>
                    $exception->getMessage()
            ];


            return false;
        }


        if (!is_array($result)) {

            $steps[
                $stepName
            ] = [
                'status' => 'Failed',
                'error_message' =>
                    'Historical evidence lifecycle step did not return a valid result.'
            ];


            return false;
        }


        $steps[
            $stepName
        ] =
            $result;


        if (
            (
                $result[
                    'status'
                ]
                ?? null
            )
            !==
            'Success'
        ) {

            return false;
        }


        return true;
    }
}