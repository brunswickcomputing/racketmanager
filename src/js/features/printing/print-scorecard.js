/**
 * Score card printing functionality
 */

/**
 * Initialize print score card handlers
 */
export function initializePrintScoreCard() {
    // This will be attached to elements dynamically
    // or can be called directly via Racketmanager.printScoreCard()
}

/**
 * Print score card for a match
 *
 * @param {Event} e - Click event
 * @param {number} matchId - Match ID to print
 */
export function printScoreCard(e, matchId) {
    e.preventDefault();

    let matchCardWindow;
    let notifyField = '#feedback-' + matchId;
    jQuery(notifyField).hide();

    // Build print URL
    const printUrl = buildPrintUrl(matchId);

    // Open print window
    matchCardWindow = window.open(
        printUrl,
        'matchCard',
        'width=800,height=600,scrollbars=yes,resizable=yes'
    );

    // Handle print window
    if (matchCardWindow) {
        matchCardWindow.focus();

        // Wait for content to load, then print
        matchCardWindow.onload = function() {
            matchCardWindow.print();
        };
    } else {
        // Popup blocked
        showError(notifyField, 'Please allow popups to print the score card.');
    }
}

/**
 * Build print URL for match card
 *
 * @param {number} matchId - Match ID
 * @returns {string} Print URL
 */
function buildPrintUrl(matchId) {
    const baseUrl = window.location.origin;
    return `${baseUrl}/match-card/${matchId}/print/`;
}

/**
 * Show error message
 *
 * @param {string} selector - Error message container selector
 * @param {string} message - Error message
 */
function showError(selector, message) {
    jQuery(selector).text(message);
    jQuery(selector).addClass('error');
    jQuery(selector).show();
}

/**
 * Export for global Racketmanager object (backward compatibility)
 */
export function attachToGlobal() {
    // Ensure Racketmanager object exists without overwriting
    window.Racketmanager = window.Racketmanager || {};

    // Add/update functions (this won't remove existing ones)
    Object.assign(window.Racketmanager, {
        printScoreCard: printScoreCard
    });
}
