/*
 * ============================================================
 * FPL INTELLIGENCE
 * APPLICATION JAVASCRIPT
 * ============================================================
 */


document.addEventListener(
    'DOMContentLoaded',
    function () {

        initialiseNavigation();

    }
);


/*
 * ============================================================
 * NAVIGATION
 * ============================================================
 */

function initialiseNavigation()
{
    const navigationLinks =
        document.querySelectorAll(
            '.main-navigation .nav-link'
        );


    navigationLinks.forEach(
        function (link) {

            link.addEventListener(
                'click',
                function (event) {

                    /*
                     * Navigation pages do not exist yet.
                     *
                     * Prevent placeholder links from
                     * jumping the page back to the top.
                     */
                    if (
                        link.getAttribute('href')
                        === '#'
                    ) {

                        event.preventDefault();
                    }

                }
            );

        }
    );
}