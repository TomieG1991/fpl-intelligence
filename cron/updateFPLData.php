<?php

require_once __DIR__ . '/../classes/autoload.php';


echo "Starting FPL Data Update...\n\n";


try {

    /*
     * ========================================================
     * DATABASE
     * ========================================================
     */

    $database =
        new Database();

    $db =
        $database->getConnection();


    echo "Database connection successful\n";


    /*
     * ========================================================
     * API
     * ========================================================
     */

    $fpl =
        new FPLApi();


    $data =
        $fpl->getBootstrapData();


    echo "FPL API connection successful\n\n";


    /*
     * ========================================================
     * VALIDATE BOOTSTRAP DATA
     * ========================================================
     */

    if (
        !isset($data['teams'])
        ||
        !is_array($data['teams'])
    ) {

        throw new RuntimeException(
            'FPL bootstrap data does not contain teams'
        );
    }


    if (
        !isset($data['elements'])
        ||
        !is_array($data['elements'])
    ) {

        throw new RuntimeException(
            'FPL bootstrap data does not contain players'
        );
    }


    /*
     * ========================================================
     * POSITION MAPPING
     * ========================================================
     *
     * FPL element_type values:
     *
     * 1 = Goalkeeper
     * 2 = Defender
     * 3 = Midfielder
     * 4 = Forward
     *
     * The player intelligence models use:
     *
     * GK
     * DEF
     * MID
     * FWD
     */

    $positionMap = [

        1 => 'GK',

        2 => 'DEF',

        3 => 'MID',

        4 => 'FWD'
    ];


    /*
     * ========================================================
     * PREPARE TEAM IMPORT
     * ========================================================
     */

    $teamSql = "
        INSERT INTO teams
        (
            fpl_team_id,
            name,
            short_name,
            strength_overall_home,
            strength_overall_away,
            strength_attack_home,
            strength_attack_away,
            strength_defence_home,
            strength_defence_away
        )

        VALUES
        (
            :fpl_team_id,
            :name,
            :short_name,
            :strength_overall_home,
            :strength_overall_away,
            :strength_attack_home,
            :strength_attack_away,
            :strength_defence_home,
            :strength_defence_away
        )

        ON DUPLICATE KEY UPDATE

            name = VALUES(name),

            short_name = VALUES(short_name),

            strength_overall_home =
                VALUES(strength_overall_home),

            strength_overall_away =
                VALUES(strength_overall_away),

            strength_attack_home =
                VALUES(strength_attack_home),

            strength_attack_away =
                VALUES(strength_attack_away),

            strength_defence_home =
                VALUES(strength_defence_home),

            strength_defence_away =
                VALUES(strength_defence_away),

            updated_at = CURRENT_TIMESTAMP
    ";


    $teamStatement =
        $db->prepare(
            $teamSql
        );


    /*
     * ========================================================
     * PREPARE PLAYER IMPORT
     * ========================================================
     */

    $playerSql = "
        INSERT INTO players
        (
            fpl_player_id,
            team_id,
            position,
            first_name,
            second_name,
            web_name,
            price,
            selected_by_percent,
            minutes,
            goals,
            assists,
            clean_sheets,
            bonus,
            bps,
            ict_index,
            expected_goals,
            expected_assists,
            expected_goal_involvements,
            chance_of_playing,
            status,
            news
        )

        VALUES
        (
            :fpl_player_id,
            :team_id,
            :position,
            :first_name,
            :second_name,
            :web_name,
            :price,
            :selected_by_percent,
            :minutes,
            :goals,
            :assists,
            :clean_sheets,
            :bonus,
            :bps,
            :ict_index,
            :expected_goals,
            :expected_assists,
            :expected_goal_involvements,
            :chance_of_playing,
            :status,
            :news
        )

        ON DUPLICATE KEY UPDATE

            team_id = VALUES(team_id),

            position = VALUES(position),

            first_name = VALUES(first_name),

            second_name = VALUES(second_name),

            web_name = VALUES(web_name),

            price = VALUES(price),

            selected_by_percent =
                VALUES(selected_by_percent),

            minutes = VALUES(minutes),

            goals = VALUES(goals),

            assists = VALUES(assists),

            clean_sheets = VALUES(clean_sheets),

            bonus = VALUES(bonus),

            bps = VALUES(bps),

            ict_index = VALUES(ict_index),

            expected_goals =
                VALUES(expected_goals),

            expected_assists =
                VALUES(expected_assists),

            expected_goal_involvements =
                VALUES(expected_goal_involvements),

            chance_of_playing =
                VALUES(chance_of_playing),

            status = VALUES(status),

            news = VALUES(news),

            updated_at = CURRENT_TIMESTAMP
    ";


    $playerStatement =
        $db->prepare(
            $playerSql
        );


    /*
     * ========================================================
     * START TRANSACTION
     * ========================================================
     */

    $db->beginTransaction();


    /*
     * ========================================================
     * IMPORT TEAMS
     * ========================================================
     */

    $teamsImported = 0;


    foreach (
        $data['teams']
        as $team
    ) {

        $teamStatement->execute([

            ':fpl_team_id' =>
                (int) $team['id'],

            ':name' =>
                $team['name'],

            ':short_name' =>
                $team['short_name'],

            ':strength_overall_home' =>
                $team['strength_overall_home'],

            ':strength_overall_away' =>
                $team['strength_overall_away'],

            ':strength_attack_home' =>
                $team['strength_attack_home'],

            ':strength_attack_away' =>
                $team['strength_attack_away'],

            ':strength_defence_home' =>
                $team['strength_defence_home'],

            ':strength_defence_away' =>
                $team['strength_defence_away']
        ]);


        $teamsImported++;
    }


    echo "Teams imported: "
        . $teamsImported
        . "\n";


    /*
     * ========================================================
     * REFRESH TEAM LOOKUPS
     * ========================================================
     *
     * Teams must exist before player foreign keys
     * can be resolved.
     */

    $teamRepository =
        new TeamRepository(
            $db
        );


    /*
     * ========================================================
     * IMPORT PLAYERS
     * ========================================================
     */

    $playersImported = 0;

    $playersSkipped = 0;


    foreach (
        $data['elements']
        as $player
    ) {

        /*
         * ----------------------------------------------------
         * Resolve local team ID.
         * ----------------------------------------------------
         */

        $teamId =
            $teamRepository->getTeamIdByFplId(
                (int) $player['team']
            );


        if ($teamId === null) {

            echo "Skipping player "
                . (
                    $player['web_name']
                    ?? $player['id']
                )
                . " - team not found\n";


            $playersSkipped++;


            continue;
        }


        /*
         * ----------------------------------------------------
         * Resolve intelligence-compatible position.
         * ----------------------------------------------------
         */

        $elementType =
            isset($player['element_type'])
                ? (int) $player['element_type']
                : 0;


        $position =
            $positionMap[$elementType]
            ?? null;


        if ($position === null) {

            echo "Skipping player "
                . (
                    $player['web_name']
                    ?? $player['id']
                )
                . " - invalid position\n";


            $playersSkipped++;


            continue;
        }


        /*
         * ----------------------------------------------------
         * Import player.
         * ----------------------------------------------------
         */

        $playerStatement->execute([

            ':fpl_player_id' =>
                (int) $player['id'],

            ':team_id' =>
                $teamId,

            ':position' =>
                $position,

            ':first_name' =>
                $player['first_name']
                ?? '',

            ':second_name' =>
                $player['second_name']
                ?? '',

            ':web_name' =>
                $player['web_name']
                ?? '',

            /*
             * FPL stores prices in tenths.
             *
             * Example:
             * 150 = £15.0m
             */
            ':price' =>
                isset($player['now_cost'])
                    ? ((float) $player['now_cost']) / 10
                    : null,

            ':selected_by_percent' =>
                isset($player['selected_by_percent'])
                    ? (float) $player['selected_by_percent']
                    : null,

            ':minutes' =>
                (int) (
                    $player['minutes']
                    ?? 0
                ),

            ':goals' =>
                (int) (
                    $player['goals_scored']
                    ?? 0
                ),

            ':assists' =>
                (int) (
                    $player['assists']
                    ?? 0
                ),

            ':clean_sheets' =>
                (int) (
                    $player['clean_sheets']
                    ?? 0
                ),

            ':bonus' =>
                (int) (
                    $player['bonus']
                    ?? 0
                ),

            ':bps' =>
                (int) (
                    $player['bps']
                    ?? 0
                ),

            ':ict_index' =>
                isset($player['ict_index'])
                    ? (float) $player['ict_index']
                    : null,

            ':expected_goals' =>
                isset($player['expected_goals'])
                    ? (float) $player['expected_goals']
                    : null,

            ':expected_assists' =>
                isset($player['expected_assists'])
                    ? (float) $player['expected_assists']
                    : null,

            ':expected_goal_involvements' =>
                isset(
                    $player[
                        'expected_goal_involvements'
                    ]
                )
                    ? (float)
                        $player[
                            'expected_goal_involvements'
                        ]
                    : null,

            ':chance_of_playing' =>
                isset(
                    $player[
                        'chance_of_playing_next_round'
                    ]
                )
                    ? (int)
                        $player[
                            'chance_of_playing_next_round'
                        ]
                    : null,

            ':status' =>
                $player['status']
                ?? null,

            ':news' =>
                $player['news']
                ?? null
        ]);


        $playersImported++;
    }


    /*
     * ========================================================
     * COMMIT
     * ========================================================
     */

    $db->commit();


    echo "Players imported: "
        . $playersImported
        . "\n";


    echo "Players skipped: "
        . $playersSkipped
        . "\n";


    echo "\nUpdate complete\n";


} catch (Throwable $exception) {

    /*
     * Roll back a partially completed import.
     */
    if (
        isset($db)
        &&
        $db instanceof PDO
        &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    echo "ERROR: "
        . $exception->getMessage()
        . "\n";


    exit(1);
}