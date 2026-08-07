<?php

class FPLApi
{
    private string $baseUrl;


    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';

        $this->baseUrl = $config['fpl_api']['base_url'];
    }


    public function getBootstrapData(): array
    {
        $url = $this->baseUrl . 'bootstrap-static/';


        $response = file_get_contents($url);


        if ($response === false) {

            throw new Exception(
                "Unable to connect to FPL API"
            );

        }


        return json_decode(
            $response,
            true
        );

    }
}