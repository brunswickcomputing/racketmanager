/**
 * Match viewer navigation functionality
 * Handles viewing match details and rubbers
 */

/**
 * Initialize match viewer
 */
export function initializeMatchViewer() {
    attachMatchViewEventListeners();
    setupMatchElementObserver();
}

/**
 * Attach event listeners for match viewing elements
 */
export function attachMatchViewEventListeners() {
    const matchElements = document.querySelectorAll('.score-row__wrapper');

    matchElements.forEach(element => {
        // Remove existing listener to avoid duplicates
        element.removeEventListener('click', handleMatchViewClick);
        // Add new listener
        element.addEventListener('click', handleMatchViewClick);
    });
}

/**
 * Set up observer to watch for dynamically added match elements
 */
function setupMatchElementObserver() {
    // Watch for new match elements being added to the page
    const observer = new MutationObserver((mutations) => {
        let hasNewMatches = false;

        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    if (node.classList && node.classList.contains('score-row__wrapper')) {
                        hasNewMatches = true;
                    } else if (node.querySelector && node.querySelector('.score-row__wrapper')) {
                        hasNewMatches = true;
                    }
                }
            });
        });

        if (hasNewMatches) {
            attachMatchViewEventListeners();
        }
    });

    // Start observing the document body for changes
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

/**
 * Handle match view click event
 *
 * @param {Event} e - Click event
 */
function handleMatchViewClick(e) {
    viewMatch(e);
}

/**
 * View match function - navigates to match details page
 *
 * @param {Event} e - Event object
 */
export function viewMatch(e) {
    const link = jQuery(e.currentTarget).find('a.score-row__anchor').attr('href');
    if (link) {
        e.preventDefault();
        window.location = link;
    }
}
