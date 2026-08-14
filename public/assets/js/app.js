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

        initialisePlayerExplorer();

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
                     * Pages that do not exist yet still use
                     * placeholder "#" links.
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


/*
 * ============================================================
 * PLAYER EXPLORER
 * ============================================================
 */

function initialisePlayerExplorer()
{
    const table =
        document.querySelector(
            '.player-table'
        );


    /*
     * Player Explorer does not exist on every page.
     */
    if (!table) {
        return;
    }


    /*
     * --------------------------------------------------------
     * ELEMENTS
     * --------------------------------------------------------
     */

    const searchInput =
        document.getElementById(
            'player-search'
        );


    const teamFilter =
        document.getElementById(
            'team-filter'
        );


    const priceFilter =
        document.getElementById(
            'price-filter'
        );
        
    const availabilityFilter =
        document.getElementById(
            'availability-filter'
        );


    const positionButtons =
        document.querySelectorAll(
            '.position-filter'
        );
        
    const intelligenceFilter =
        document.getElementById(
            'intelligence-filter'
        );
        
    const valueFilter =
        document.getElementById(
            'value-filter'
        );


    const poolButtons =
        document.querySelectorAll(
            '.player-pool-filter'
        );


    const clearButton =
        document.getElementById(
            'clear-player-filters'
        );


    const visibleCount =
        document.getElementById(
            'visible-player-count'
        );


    const tableBody =
        table.querySelector(
            'tbody'
        );


    const sortHeaders =
        table.querySelectorAll(
            '.sortable-column'
        );


    /*
     * --------------------------------------------------------
     * STATE
     * --------------------------------------------------------
     */

    let selectedPosition =
        '';


    let selectedPool =
        'ranked';


    let currentSort =
        'intelligence';


    let currentDirection =
        'desc';


    /*
     * --------------------------------------------------------
     * FILTERING
     * --------------------------------------------------------
     */

    function applyFilters()
    {
        const searchTerm =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';


        const selectedTeam =
            teamFilter
                ? teamFilter.value
                : '';


        const selectedPrice =
            (
                priceFilter
                &&
                priceFilter.value !== ''
            )
                ? parseFloat(
                    priceFilter.value
                )
                : null;
                
        const selectedAvailability =
            availabilityFilter
                ? availabilityFilter.value
                : '';
                
        const minimumIntelligence =
            (
                intelligenceFilter
                &&
                intelligenceFilter.value !== ''
            )
                ? parseFloat(
                    intelligenceFilter.value
                )
                : null;
                
        const minimumValue =
            (
                valueFilter
                &&
                valueFilter.value !== ''
            )
                ? parseFloat(
                    valueFilter.value
                )
                : null;


        const rows =
            tableBody.querySelectorAll(
                '.player-row'
            );


        let count =
            0;


        rows.forEach(
            function (row) {

                const playerName =
                    row.dataset.name
                    ?? '';


                const team =
                    row.dataset.team
                    ?? '';


                const position =
                    row.dataset.position
                    ?? '';


                const ranked =
                    row.dataset.ranked
                    === '1';


                const price =
                    row.dataset.price !== ''
                        ? parseFloat(
                            row.dataset.price
                        )
                        : null;
                        
                const availability =
                    row.dataset.availability
                    ?? 'unknown';
                    
                const intelligence =
                    row.dataset.intelligence !== ''
                        ? parseFloat(
                            row.dataset.intelligence
                        )
                        : null;
                        
                const valueRating =
                    row.dataset.value !== ''
                        ? parseFloat(
                            row.dataset.value
                        )
                        : null;
                    
                    
                /*
                 * Player pool.
                 */

                const matchesPool =
                    selectedPool === 'all'
                    ||
                    (
                        selectedPool === 'ranked'
                        &&
                        ranked
                    );


                /*
                 * Player search.
                 */

                const matchesSearch =
                    searchTerm === ''
                    ||
                    playerName.includes(
                        searchTerm
                    );


                /*
                 * Team filter.
                 */

                const matchesTeam =
                    selectedTeam === ''
                    ||
                    team === selectedTeam;


                /*
                 * Position filter.
                 */

                const matchesPosition =
                    selectedPosition === ''
                    ||
                    position === selectedPosition;


                /*
                 * Price filter.
                 */

                const matchesPrice =
                    selectedPrice === null
                    ||
                    (
                        price !== null
                        &&
                        price <= selectedPrice
                    );
                    
                const matchesAvailability =
                    selectedAvailability === ''
                    ||
                    availability === selectedAvailability;
                    
                const matchesIntelligence =
                    minimumIntelligence === null
                    ||
                    (
                        intelligence !== null
                        &&
                        intelligence >= minimumIntelligence
                    );
                    
                const matchesValue =
                    minimumValue === null
                    ||
                    (
                        valueRating !== null
                        &&
                        valueRating >= minimumValue
                    );


                const visible =
                    matchesPool
                    &&
                    matchesSearch
                    &&
                    matchesTeam
                    &&
                    matchesPosition
                    &&
                    matchesPrice
                    &&
                    matchesAvailability
                    &&
                    matchesIntelligence
                    &&
                    matchesValue;


                row.classList.toggle(
                    'hidden-player',
                    !visible
                );


                if (visible) {
                    count++;
                }

            }
        );


        if (visibleCount) {

            visibleCount.textContent =
                count;
        }
    }


    /*
     * --------------------------------------------------------
     * SEARCH
     * --------------------------------------------------------
     */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            applyFilters
        );
    }


    /*
     * --------------------------------------------------------
     * TEAM FILTER
     * --------------------------------------------------------
     */

    if (teamFilter) {

        teamFilter.addEventListener(
            'change',
            applyFilters
        );
    }


    /*
     * --------------------------------------------------------
     * PRICE FILTER
     * --------------------------------------------------------
     */

    if (priceFilter) {

        priceFilter.addEventListener(
            'change',
            applyFilters
        );
    }
    
    /*
     * --------------------------------------------------------
     * AVAILABILITY FILTER
     * --------------------------------------------------------
     */

    if (availabilityFilter) {

        availabilityFilter.addEventListener(
            'change',
            applyFilters
        );
    }
    
    if (intelligenceFilter) {

        intelligenceFilter.addEventListener(
            'change',
            applyFilters
        );
    }
    
    if (valueFilter) {
        
        valueFilter.addEventListener(
            'change',
            applyFilters
        );
    }


    /*
     * --------------------------------------------------------
     * POSITION FILTERS
     * --------------------------------------------------------
     */

    positionButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function () {

                    selectedPosition =
                        button.dataset.position
                        ?? '';


                    positionButtons.forEach(
                        function (item) {

                            item.classList.remove(
                                'active'
                            );
                        }
                    );


                    button.classList.add(
                        'active'
                    );


                    applyFilters();

                }
            );

        }
    );


    /*
     * --------------------------------------------------------
     * PLAYER POOL FILTERS
     * --------------------------------------------------------
     */

    poolButtons.forEach(
        function (button) {

            button.addEventListener(
                'click',
                function () {

                    selectedPool =
                        button.dataset.pool
                        ?? 'ranked';


                    poolButtons.forEach(
                        function (item) {

                            item.classList.remove(
                                'active'
                            );
                        }
                    );


                    button.classList.add(
                        'active'
                    );


                    applyFilters();

                }
            );

        }
    );


    /*
     * --------------------------------------------------------
     * CLEAR FILTERS
     * --------------------------------------------------------
     */

    if (clearButton) {

        clearButton.addEventListener(
            'click',
            function () {

                if (searchInput) {
                    searchInput.value = '';
                }


                if (teamFilter) {
                    teamFilter.value = '';
                }


                if (priceFilter) {
                    priceFilter.value = '';
                }
                
                if (availabilityFilter) {
                    availabilityFilter.value = '';
                }
                
                if (intelligenceFilter) {
                    intelligenceFilter.value = '';
                }
                
                if (valueFilter) {
                    valueFilter.value = '';
                }


                /*
                 * Reset position.
                 */

                selectedPosition =
                    '';


                positionButtons.forEach(
                    function (button) {

                        button.classList.toggle(
                            'active',
                            (
                                button.dataset.position
                                ?? ''
                            ) === ''
                        );

                    }
                );


                /*
                 * Reset player pool.
                 */

                selectedPool =
                    'ranked';


                poolButtons.forEach(
                    function (button) {

                        button.classList.toggle(
                            'active',
                            (
                                button.dataset.pool
                                ?? ''
                            ) === 'ranked'
                        );

                    }
                );


                applyFilters();

            }
        );
    }


    /*
     * --------------------------------------------------------
     * SORTING
     * --------------------------------------------------------
     */

    sortHeaders.forEach(
        function (header) {

            header.addEventListener(
                'click',
                function () {

                    const sortKey =
                        header.dataset.sort;


                    if (!sortKey) {
                        return;
                    }


                    /*
                     * Clicking the same column reverses
                     * its current direction.
                     */

                    if (
                        currentSort
                        === sortKey
                    ) {

                        currentDirection =
                            currentDirection
                            === 'asc'
                                ? 'desc'
                                : 'asc';

                    } else {

                        currentSort =
                            sortKey;


                        /*
                         * Text columns naturally begin
                         * alphabetically.
                         *
                         * Numeric columns begin highest-first.
                         */

                        currentDirection =
                            [
                                'name',
                                'team',
                                'position',
                                'rating'
                            ].includes(
                                sortKey
                            )
                                ? 'asc'
                                : 'desc';
                    }


                    sortTable(
                        sortKey,
                        currentDirection
                    );


                    /*
                     * Reset header indicators.
                     */

                    sortHeaders.forEach(
                        function (item) {

                            item.classList.remove(
                                'active-sort',
                                'sort-ascending'
                            );
                        }
                    );


                    header.classList.add(
                        'active-sort'
                    );


                    if (
                        currentDirection
                        === 'asc'
                    ) {

                        header.classList.add(
                            'sort-ascending'
                        );
                    }

                }
            );

        }
    );


    /*
     * --------------------------------------------------------
     * SORT TABLE
     * --------------------------------------------------------
     */

    function sortTable(
        sortKey,
        direction
    ) {

        const rows =
            Array.from(
                tableBody.querySelectorAll(
                    '.player-row'
                )
            );


        rows.sort(
            function (
                rowA,
                rowB
            ) {

                const valueA =
                    getSortValue(
                        rowA,
                        sortKey
                    );


                const valueB =
                    getSortValue(
                        rowB,
                        sortKey
                    );


                /*
                 * String comparison.
                 */

                if (
                    typeof valueA
                    === 'string'
                    &&
                    typeof valueB
                    === 'string'
                ) {

                    const result =
                        valueA.localeCompare(
                            valueB
                        );


                    return direction === 'asc'
                        ? result
                        : -result;
                }


                /*
                 * Numeric comparison.
                 *
                 * Null/unavailable ratings remain below
                 * real values when sorting descending.
                 */

                const numericA =
                    Number.isFinite(valueA)
                        ? valueA
                        : -Infinity;


                const numericB =
                    Number.isFinite(valueB)
                        ? valueB
                        : -Infinity;


                return direction === 'asc'
                    ? numericA - numericB
                    : numericB - numericA;

            }
        );


        /*
         * Re-append rows in the new order.
         */

        rows.forEach(
            function (row) {

                tableBody.appendChild(
                    row
                );

            }
        );
    }


    /*
     * --------------------------------------------------------
     * READ SORT VALUES
     * --------------------------------------------------------
     */

    function getSortValue(
        row,
        sortKey
    ) {

        const mapping = {

            rank:
                'rank',

            name:
                'name',

            team:
                'team',

            position:
                'position',

            price:
                'price',

            strength:
                'strength',

            value:
                'value',

            fixture:
                'fixture',

            intelligence:
                'intelligence',

            rating:
                'rating'
        };


        const dataKey =
            mapping[sortKey];


        if (!dataKey) {
            return '';
        }


        const rawValue =
            row.dataset[dataKey]
            ?? '';


        /*
         * Numeric columns.
         */

        if (
            [
                'rank',
                'price',
                'strength',
                'value',
                'fixture',
                'intelligence'
            ].includes(
                sortKey
            )
        ) {

            if (rawValue === '') {
                return NaN;
            }


            return parseFloat(
                rawValue
            );
        }


        /*
         * Text columns.
         */

        return rawValue
            .toLowerCase();
    }


    /*
     * --------------------------------------------------------
     * INITIAL STATE
     * --------------------------------------------------------
     */

    /*
     * Ensure the page starts in Ranked mode.
     */

    poolButtons.forEach(
        function (button) {

            button.classList.toggle(
                'active',
                (
                    button.dataset.pool
                    ?? ''
                ) === selectedPool
            );

        }
    );


    positionButtons.forEach(
        function (button) {

            button.classList.toggle(
                'active',
                (
                    button.dataset.position
                    ?? ''
                ) === selectedPosition
            );

        }
    );


    /*
     * Apply the default Player Intelligence ranking.
     *
     * This keeps the initial Explorer order consistent
     * with the dashboard Top Players ranking.
     */

    sortTable(
        currentSort,
        currentDirection
    );


    /*
     * Apply the default Ranked player pool.
     */

    applyFilters();
}