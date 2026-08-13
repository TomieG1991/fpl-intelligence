<?php

class FPLApi
{
    private string $baseUrl;

    private int $timeout = 15;


    public function __construct()
    {
        $config =
            require __DIR__ . '/../config/config.php';


        if (
            !isset($config['fpl_api']['base_url'])
            ||
            !is_string($config['fpl_api']['base_url'])
            ||
            trim($config['fpl_api']['base_url']) === ''
        ) {

            throw new RuntimeException(
                'FPL API base URL is not configured'
            );
        }


        $this->baseUrl =
            rtrim(
                $config['fpl_api']['base_url'],
                '/'
            )
            . '/';
    }


    /**
     * Return the main FPL bootstrap dataset.
     */
    public function getBootstrapData(): array
    {
        return $this->request(
            'bootstrap-static/'
        );
    }


    /**
     * Return the complete FPL fixture dataset.
     */
    public function getFixtures(): array
    {
        return $this->request(
            'fixtures/'
        );
    }


    /**
     * Perform a request against the FPL API
     * and return the decoded JSON response.
     */
    private function request(
        string $endpoint
    ): array {

        $url =
            $this->baseUrl
            . ltrim(
                $endpoint,
                '/'
            );


        $context =
            stream_context_create([

                'http' => [

                    'method' =>
                        'GET',

                    'timeout' =>
                        $this->timeout,

                    'header' =>
                        "Accept: application/json\r\n"
                        . "User-Agent: FPL-Intelligence/1.0\r\n"
                ]
            ]);


        $response =
            @file_get_contents(
                $url,
                false,
                $context
            );


        if ($response === false) {

            throw new RuntimeException(
                'Unable to retrieve data from FPL API endpoint: '
                . $endpoint
            );
        }


        try {

            $data =
                json_decode(
                    $response,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

        } catch (JsonException $exception) {

            throw new RuntimeException(
                'Invalid JSON received from FPL API endpoint: '
                . $endpoint,
                0,
                $exception
            );
        }


        if (!is_array($data)) {

            throw new RuntimeException(
                'Unexpected response received from FPL API endpoint: '
                . $endpoint
            );
        }


        return $data;
    }
}