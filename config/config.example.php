<?php

return [

    'environment' =>
        getenv('FPL_APP_ENV')
        ?: 'development',


    'database' => [

        'host' =>
            getenv('FPL_DB_HOST')
            ?: 'localhost',

        'name' =>
            getenv('FPL_DB_NAME')
            ?: 'fpl_intelligence',

        'username' =>
            getenv('FPL_DB_USERNAME')
            ?: 'your_database_username',

        'password' =>
            getenv('FPL_DB_PASSWORD')
            ?: 'your_database_password'
    ],


    'fpl_api' => [

        'base_url' =>
            getenv('FPL_API_BASE_URL')
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