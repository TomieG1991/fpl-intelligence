CREATE TABLE teams (

    id INT AUTO_INCREMENT PRIMARY KEY,

    fpl_team_id INT NOT NULL,

    name VARCHAR(100) NOT NULL,

    short_name VARCHAR(10),

    strength_overall_home INT,

    strength_overall_away INT,

    strength_attack_home INT,

    strength_attack_away INT,

    strength_defence_home INT,

    strength_defence_away INT,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);



CREATE TABLE players (

    id INT AUTO_INCREMENT PRIMARY KEY,

    fpl_player_id INT NOT NULL,

    team_id INT NOT NULL,

    position VARCHAR(20),

    first_name VARCHAR(100),

    second_name VARCHAR(100),

    web_name VARCHAR(100),

    price DECIMAL(4,1),

    selected_by_percent DECIMAL(5,2),

    minutes INT DEFAULT 0,

    goals INT DEFAULT 0,

    assists INT DEFAULT 0,

    clean_sheets INT DEFAULT 0,

    bonus INT DEFAULT 0,

    bps INT DEFAULT 0,

    ict_index DECIMAL(6,2),

    expected_goals DECIMAL(6,2),

    expected_assists DECIMAL(6,2),

    expected_goal_involvements DECIMAL(6,2),

    chance_of_playing INT,

    status VARCHAR(10),

    news TEXT,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,


    FOREIGN KEY (team_id) REFERENCES teams(id)

);

ALTER TABLE teams
ADD UNIQUE KEY unique_fpl_team_id (fpl_team_id);


ALTER TABLE players
ADD UNIQUE KEY unique_fpl_player_id (fpl_player_id);

