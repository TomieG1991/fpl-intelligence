<?php

return [

    'database' => [

        'host' =>
            getenv('FPL_DB_HOST')
            ?: 'localhost',

        'name' =>
            getenv('FPL_DB_NAME')
            ?: 'fpl_intelligence',

        'username' =>
            getenv('FPL_DB_USERNAME')
            ?: 'root',

        'password' =>
            getenv('FPL_DB_PASSWORD')
            ?: ''
    ],


    'fpl_api' => [

        'base_url' =>
            getenv('FPL_API_BASE_URL')
            ?: 'https://fantasy.premierleague.com/api/'
    ]

];