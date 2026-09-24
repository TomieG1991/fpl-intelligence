<?php

$environment =
    strtolower(
        trim(
            (string) (
                getenv(
                    'FPL_APP_ENV'
                )
                ?: 'development'
            )
        )
    );


$databaseHost =
    getenv(
        'FPL_DB_HOST'
    );


$databaseName =
    getenv(
        'FPL_DB_NAME'
    );


$databaseUsername =
    getenv(
        'FPL_DB_USERNAME'
    );


$databasePassword =
    getenv(
        'FPL_DB_PASSWORD'
    );


if ($environment === 'production') {

    $requiredDatabaseEnvironmentVariables = [

        'FPL_DB_HOST' =>
            $databaseHost,

        'FPL_DB_NAME' =>
            $databaseName,

        'FPL_DB_USERNAME' =>
            $databaseUsername,

        'FPL_DB_PASSWORD' =>
            $databasePassword
    ];


    foreach (
        $requiredDatabaseEnvironmentVariables
        as $variableName =>
            $variableValue
    ) {

        if (
            $variableValue === false
            ||
            trim(
                (string) $variableValue
            ) === ''
        ) {

            throw new RuntimeException(
                'Production database environment variable is missing: '
                . $variableName
            );
        }
    }
}


return [

    'environment' =>
        $environment,


    'database' => [

        'host' =>
            $databaseHost
            ?: 'localhost',

        'name' =>
            $databaseName
            ?: 'fpl_intelligence',

        'username' =>
            $databaseUsername
            ?: 'root',

        'password' =>
            $databasePassword !== false
                ? $databasePassword
                : ''
    ],


    'fpl_api' => [

        'base_url' =>
            getenv(
                'FPL_API_BASE_URL'
            )
            ?: 'https://fantasy.premierleague.com/api/'
    ],


    /*
     * ========================================================
     * DATA HEALTH
     * ========================================================
     *
     * A successful application data update remains fresh for
     * 24 hours.
     *
     * UpdateHealthService receives this threshold explicitly so
     * the health policy remains separate from health evaluation.
     */

    'data_health' => [

        'freshness_seconds' =>
            86400
    ]

];