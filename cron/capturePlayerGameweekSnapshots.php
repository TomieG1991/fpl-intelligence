<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RETIRED
 * ============================================================
 *
 * RETIRED:
 * Completed-gameweek player snapshot reconstruction is no
 * longer part of the FPL Intelligence snapshot lifecycle.
 *
 * Historical player state must be captured before the FPL
 * deadline through:
 *
 * capturePlayerGameweekSnapshotCandidates.php
 *
 * The latest eligible candidate is then promoted at or after
 * its preserved deadline through:
 *
 * promotePlayerGameweekSnapshotCandidates.php
 *
 * This file deliberately remains as a safe legacy entry point
 * during migration so any old scheduler or manual URL cannot
 * silently reconstruct historical state from the current live
 * player pool.
 *
 * DO NOT restore retrospective completed-gameweek capture here.
 */


echo "============================================<br>";
echo "Player Gameweek Snapshot Capture — RETIRED<br>";
echo "============================================<br><br>";


echo "Retrospective completed-gameweek player snapshot capture "
    . "has been RETIRED.<br><br>";


echo "Player snapshot history is now preserved through the "
    . "pre-deadline candidate lifecycle:<br><br>";


echo "1. capturePlayerGameweekSnapshotCandidates.php<br>";

echo "2. promotePlayerGameweekSnapshotCandidates.php<br><br>";


echo "No historical snapshots have been written.<br><br>";


echo "RESULT: SNAPSHOT CAPTURE RETIRED";