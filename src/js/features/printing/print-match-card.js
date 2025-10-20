/**
 * Match card printing functionality
 */

import {
    openPrintWindow,
    autoPrint,
    buildPrintUrl,
    handlePopupBlocker,
    showPrintLoading,
    hidePrintLoading
} from './print-utils.js';

/**
 * Print a match card
 *
 * @param {Event} e - Click event
 * @param {number} matchId - Match ID
 */
export function printMatchCard(e, matchId) {
    e.preventDefault();

    const loadingSelector = '#printing-loading';
    const errorSelector = '#print-error-' + matchId;

    // Show loading state
    showPrintLoading(loadingSelector);

    // Build print URL
    const printUrl = buildPrintUrl(
        window.location.origin,
        `/match/${matchId}/card/print/`
    );

    // Open print window
    const printWindow = openPrintWindow(printUrl, 'matchCard');

    // Handle popup blocker
    if (handlePopupBlocker(printWindow, errorSelector)) {
        hidePrintLoading(loadingSelector);
        return;
    }

    // Auto-print when loaded
    autoPrint(printWindow, () => {
        hidePrintLoading(loadingSelector);
    });
}

/**
 * Print multiple match cards
 *
 * @param {Array<number>} matchIds - Array of match IDs
 */
export function printMultipleMatchCards(matchIds) {
    const urls = matchIds.map(id =>
        buildPrintUrl(window.location.origin, `/match/${id}/card/print/`)
    );

    printSequence(urls, 2000); // 2 second delay between prints
}

/**
 * Initialize match card print buttons
 */
export function initializeMatchCardPrint() {
    jQuery(document).on('click', '[data-print-match-card]', function(e) {
        const matchId = jQuery(this).data('print-match-card');
        printMatchCard(e, matchId);
    });
}
