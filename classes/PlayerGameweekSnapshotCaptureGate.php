<?php

class PlayerGameweekSnapshotCaptureGate
{
    public function shouldCapture(
        bool $fullImport,
        int $playersSelected,
        int $playersProcessed,
        int $playersFailed
    ): bool {

        if (
            !$fullImport
        ) {

            return false;
        }


        if (
            $playersSelected <= 0
        ) {

            return false;
        }


        if (
            $playersFailed !== 0
        ) {

            return false;
        }


        return
            $playersProcessed
            ===
            $playersSelected;
    }
}