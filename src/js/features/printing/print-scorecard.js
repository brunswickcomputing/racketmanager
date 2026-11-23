/**
 * Scorecard printing functionality (modularised legacy Racketmanager.printScoreCard)
 */
import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';

/**
 * Build <head> with current document stylesheets (parity with legacy)
 *
 * @returns {string} HTML head element string
 */
function buildHead() {
    let head = '<html lang=""><head><title>Match Card</title>';
    for (const item of document.styleSheets) {
        try {
            if (item.href) {
                head += '<link rel="stylesheet" type="text/css" href="' + item.href + '" media="all">';
            }
        } catch (_) {
            // Defensive: ignore any inaccessible stylesheet properties in restricted environments
        }
    }
    head += '</head>';
    return head;
}

/**
 * Display error message in feedback notification field
 *
 * @param {string} notifyField - Selector for notification container
 * @param {string} message - Error message to display
 */
function showNotifyError(notifyField, message) {
    jQuery(notifyField)
        .text(message)
        .show()
        .addClass('message-error');
}

/**
 * Fallback writing of HTML content to window when direct head/body assignment fails
 *
 * @param {Window} win - Popup window object
 * @param {string} fullHtml - Combined HTML content
 */
function fallbackPopulateWindow(win, fullHtml) {
    try {
        if (win.document.documentElement) {
            win.document.documentElement.innerHTML = fullHtml;
        } else if (win.document.body) {
            win.document.body.innerHTML = fullHtml;
        }
    } catch (e) {
        console.error(e);
    }
}

/**
 * Populate popup window with match card content and stylesheets
 *
 * @param {Window} win - Popup window object
 * @param {string} content - Match card body HTML
 * @param {string} head - Head element with stylesheets
 * @param {string} foot - Closing tags
 */
function writeMatchCardContent(win, content, head, foot) {
    try {
        win.document.head.innerHTML = head;
        win.document.body.innerHTML = content + foot;
    } catch {
        // Fallback: full document replacement
        fallbackPopulateWindow(win, head + content + foot);
    }
}

/**
 * Handle successful match card retrieval
 *
 * @param {Object} response - AJAX response object
 * @param {string} head - Prebuilt HTML head string
 * @param {string} foot - Prebuilt HTML foot string
 * @param {string} notifyField - Selector for feedback field
 */
function handlePrintSuccess(response, head, foot, notifyField) {
    const matchCardWindow = globalThis.open('about:blank', 'match_card', 'popup, width=800,height=775');
    if (!matchCardWindow) {
        showNotifyError(notifyField, 'Match Card not available - turn off pop blocker and retry');
        return;
    }

    const content = response?.data ? response.data : '';
    writeMatchCardContent(matchCardWindow, content, head, foot);
}

/**
 * Handle failed match card AJAX request
 *
 * @param {Object} response - AJAX failure response object
 * @param {string} notifyField - Selector for feedback field
 */
function handlePrintError(response, notifyField) {
    const message = response?.responseJSON?.data
        || response?.statusText
        || 'Request failed';

    showNotifyError(notifyField, message);
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

    const notifyField = '#feedback-' + matchId;
    jQuery(notifyField).hide().removeClass('message-success message-error');

    const head = buildHead();
    const foot = '</body></html>';

    // Fetch printable HTML via WP AJAX
    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: {
            matchId: matchId,
            action: 'racketmanager_match_card',
            security: getAjaxNonce(),
        },
        success: function (response) {
            handlePrintSuccess(response, head, foot, notifyField);
        },
        error: function (response) {
            handlePrintError(response, notifyField);
        },
    });
}
