<?php

class ApplicationErrorPresenter
{
    private string $environment;


    public function __construct(
        string $environment
    ) {

        $this->environment =
            strtolower(
                trim(
                    $environment
                )
            );
    }


    public function present(
        Throwable $exception,
        string $safeMessage
    ): string {

        /*
         * Development environments retain the original
         * exception message so local diagnostics remain useful.
         */

        if (
            $this->environment
            !==
            'production'
        ) {

            return $exception
                ->getMessage();
        }


        /*
         * Production environments must not expose internal
         * exception details to the browser.
         *
         * Preserve the technical detail in the PHP error log
         * while returning only the explicitly supplied safe
         * user-facing message.
         */

        error_log(
            $exception
                ->getMessage()
        );


        return $safeMessage;
    }
}