<?php

class PhpCliExecutableLocator
{
    /*
     * ========================================================
     * LOCATE
     * ========================================================
     */

    public function locate(
        string $phpRoot,
        string $preferredVersion
    ): string {

        $phpRoot =
            rtrim(
                trim(
                    $phpRoot
                ),
                '\\/'
            );


        $preferredVersion =
            trim(
                $preferredVersion
            );


        /*
         * ----------------------------------------------------
         * EXPLICIT PHP EXECUTABLE
         * ----------------------------------------------------
         *
         * Production environments commonly expose PHP as a
         * directly executable binary rather than through the
         * WAMP php<version>/php.exe directory structure.
         *
         * Preserve the existing WAMP discovery behaviour while
         * allowing callers to provide an explicit executable.
         */

        if (
            $phpRoot !== ''
            &&
            is_file(
                $phpRoot
            )
        ) {

            return $phpRoot;
        }


        if (
            $phpRoot === ''
            ||
            !is_dir(
                $phpRoot
            )
        ) {

            throw new InvalidArgumentException(
                'PHP installation root or executable could not be found.'
            );
        }


        /*
         * ----------------------------------------------------
         * PREFERRED VERSION
         * ----------------------------------------------------
         */

        $preferredExecutable =
            $phpRoot
            . DIRECTORY_SEPARATOR
            . 'php'
            . $preferredVersion
            . DIRECTORY_SEPARATOR
            . 'php.exe';


        if (
            is_file(
                $preferredExecutable
            )
        ) {

            return $preferredExecutable;
        }


        /*
         * ----------------------------------------------------
         * FALLBACK TO INSTALLED VERSIONS
         * ----------------------------------------------------
         */

        $phpCandidates =
            glob(
                $phpRoot
                . DIRECTORY_SEPARATOR
                . 'php*'
                . DIRECTORY_SEPARATOR
                . 'php.exe'
            );


        if (
            $phpCandidates === false
            ||
            empty(
                $phpCandidates
            )
        ) {

            throw new RuntimeException(
                'No PHP CLI executable could be found.'
            );
        }


        /*
         * Natural sorting means:
         *
         * php8.1.0
         * php8.2.3
         * php8.3.1
         *
         * are ordered by version rather than plain
         * string comparison.
         */
        natsort(
            $phpCandidates
        );


        $phpCandidates =
            array_values(
                $phpCandidates
            );


        $phpExecutable =
            end(
                $phpCandidates
            );


        if (
            $phpExecutable === false
            ||
            !is_file(
                $phpExecutable
            )
        ) {

            throw new RuntimeException(
                'No PHP CLI executable could be found.'
            );
        }


        return $phpExecutable;
    }
}