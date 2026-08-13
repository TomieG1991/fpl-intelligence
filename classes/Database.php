<?php

class Database
{
    private PDO $connection;


    /**
     * Create the database connection.
     */
    public function __construct()
    {
        $config =
            require __DIR__ . '/../config/config.php';


        if (
            !isset(
                $config['database']['host'],
                $config['database']['name'],
                $config['database']['username'],
                $config['database']['password']
            )
        ) {

            throw new RuntimeException(
                'Database configuration is incomplete'
            );
        }


        $dsn =
            'mysql:host='
            . $config['database']['host']
            . ';dbname='
            . $config['database']['name']
            . ';charset=utf8mb4';


        $this->connection =
            new PDO(
                $dsn,
                $config['database']['username'],
                $config['database']['password'],
                [
                    PDO::ATTR_ERRMODE =>
                        PDO::ERRMODE_EXCEPTION,

                    PDO::ATTR_DEFAULT_FETCH_MODE =>
                        PDO::FETCH_ASSOC,

                    PDO::ATTR_EMULATE_PREPARES =>
                        false
                ]
            );
    }


    /**
     * Return the active PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}