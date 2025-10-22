/**
 * Score card printing functionality (modularized legacy Racketmanager.printScoreCard)
 */

/**
 * Initialize print score card handlers (delegated bindings are handled in print-match-card.js)
 */
export function initializePrintScoreCard() {
    // No direct bindings here; handlers are wired in initializeMatchCardPrint
}

/**
 * Print score card for a match (AJAX-driven, mirrors legacy behavior)
 *
 * @param {Event} e - Click event
 * @param {number|string} matchId - Match ID to print
 */
export function printScoreCard(e, matchId) {
    if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
    }

    let matchCardWindow;
    const notifyField = '#feedback-' + matchId;
    jQuery(notifyField).hide();
    jQuery(notifyField).removeClass('message-success message-error');

    // Build <head> with current document stylesheets (parity with legacy)
    const styleSheetList = document.styleSheets;
    let head = '<html lang=""><head><title>Match Card</title>';
    for (let item of styleSheetList) {
        try {
            if (item.href) head += '<link rel="stylesheet" type="text/css" href="' + item.href + '" media="all">';
        } catch (_) { /* some stylesheets may be cross-origin */ }
    }
    head += '</head>';
    const foot = '</body></html>';

    // Fetch printable HTML via WP AJAX
    jQuery.ajax({
        url: ajax_var.url,
        type: 'POST',
        data: {
            matchId: matchId,
            action: 'racketmanager_match_card',
            security: ajax_var.ajax_nonce,
        },
        success: function (response) {
            // Open popup and write content
            matchCardWindow = globalThis.open('about:blank', 'match_card', 'popup, width=800,height=775');
            if (matchCardWindow) {
                try {
                    matchCardWindow.document.head.innerHTML = head;
                    matchCardWindow.document.body.innerHTML = (response && response.data ? response.data : '') + foot;
                } catch (_) {
                    // Fallback: full document write
                    try {
                        matchCardWindow.document.open();
                        matchCardWindow.document.write(head + (response && response.data ? response.data : '') + foot);
                        matchCardWindow.document.close();
                    } catch (_) { /* no-op */ }
                }
            } else {
                jQuery(notifyField).text('Match Card not available - turn off pop blocker and retry');
                jQuery(notifyField).show();
                jQuery(notifyField).addClass('message-error');
            }
        },
        error: function (response) {
            if (response && response.responseJSON) {
                jQuery(notifyField).text(response.responseJSON.data);
            } else {
                jQuery(notifyField).text(response && response.statusText ? response.statusText : 'Request failed');
            }
            jQuery(notifyField).show();
            jQuery(notifyField).addClass('message-error');
        }
    });
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
