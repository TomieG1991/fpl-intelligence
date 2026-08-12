<?php

require_once '../classes/autoload.php';

echo "Starting FPL Data Update...\n\n";


try {


    // Database

    $database = new Database();

    $db = $database->getConnection();


    echo "Database connection successful\n";


    // API

    $fpl = new FPLApi();

    $data = $fpl->getBootstrapData();


    echo "FPL API connection successful\n\n";



    /*
    Import Teams
    */


    foreach ($data['teams'] as $team) {

        $sql = "
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
                strength_overall_home = VALUES(strength_overall_home),
                strength_overall_away = VALUES(strength_overall_away),

                strength_attack_home = VALUES(strength_attack_home),
                strength_attack_away = VALUES(strength_attack_away),

                strength_defence_home = VALUES(strength_defence_home),
                strength_defence_away = VALUES(strength_defence_away),
                updated_at = CURRENT_TIMESTAMP

        ";


        $stmt = $db->prepare($sql);


        $stmt->execute([

            ':fpl_team_id' => $team['id'],
            ':name' => $team['name'],
            ':short_name' => $team['short_name'],

            ':strength_overall_home' => $team['strength_overall_home'],
            ':strength_overall_away' => $team['strength_overall_away'],

            ':strength_attack_home' => $team['strength_attack_home'],
            ':strength_attack_away' => $team['strength_attack_away'],

            ':strength_defence_home' => $team['strength_defence_home'],
            ':strength_defence_away' => $team['strength_defence_away']

        ]);

    }



    echo "Teams imported: "
        . count($data['teams'])
        . "\n";
        
    /*
    Import Players
    */


    foreach ($data['elements'] as $player) {


        $teamId = $database->getTeamIdByFplId(
            $player['team']
        );


        if (!$teamId) {

            echo "Skipping player "
                . $player['web_name']
                . " - team not found\n";

            continue;

        }



        $sql = "

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
                price = VALUES(price),
                selected_by_percent = VALUES(selected_by_percent),
                minutes = VALUES(minutes),
                goals = VALUES(goals),
                assists = VALUES(assists),
                bonus = VALUES(bonus),
                bps = VALUES(bps),
                ict_index = VALUES(ict_index),
                expected_goals = VALUES(expected_goals),
                expected_assists = VALUES(expected_assists),
                expected_goal_involvements = VALUES(expected_goal_involvements),
                chance_of_playing = VALUES(chance_of_playing),
                status = VALUES(status),
                news = VALUES(news),
                updated_at = CURRENT_TIMESTAMP

        ";



        $stmt = $db->prepare($sql);



        $stmt->execute([

            ':fpl_player_id' => $player['id'],

            ':team_id' => $teamId,

            ':position' => $player['element_type'],

            ':first_name' => $player['first_name'],

            ':second_name' => $player['second_name'],

            ':web_name' => $player['web_name'],

            // FPL stores prices as 150 = £15.0m
            ':price' => $player['now_cost'] / 10,

            ':selected_by_percent' => $player['selected_by_percent'],

            ':minutes' => $player['minutes'],

            ':goals' => $player['goals_scored'],

            ':assists' => $player['assists'],

            ':clean_sheets' => $player['clean_sheets'],

            ':bonus' => $player['bonus'],

            ':bps' => $player['bps'],

            ':ict_index' => $player['ict_index'],

            ':expected_goals' => $player['expected_goals'],

            ':expected_assists' => $player['expected_assists'],

            ':expected_goal_involvements' => $player['expected_goal_involvements'],

            ':chance_of_playing' => $player['chance_of_playing_next_round'],

            ':status' => $player['status'],

            ':news' => $player['news']

        ]);

    }


    echo "Players imported: "
        . count($data['elements'])
        . "\n";


    echo "\nUpdate complete";


}
catch(Exception $e)
{

    echo "ERROR: "
        . $e->getMessage();

}