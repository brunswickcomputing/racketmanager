/**
 * AJAX Error Handler - Modularized
 * Provides a centralized error handling function compatible with legacy usage.
 */

import { logger, recordEvent } from '../../utils/logger.js';

/**
 * Safely extract a message from a jQuery AJAX error response.
 * Prefers server-provided JSON payloads, falls back to statusText.
 * Mirrors legacy getMessageFromResponse behavior for 500s but works for any status.
 * @param {jqXHR} response
 * @returns {string}
 */
function extractMessage(response) {
    if (response && response.responseJSON && response.responseJSON.data) {
        const data = response.responseJSON.data;
        // Try common fields first
        if (typeof data.msg === 'string') return data.msg;
        if (typeof data.message === 'string') return data.message;
        // Fallback to stringifying data
        try {
            return typeof data === 'string' ? data : JSON.stringify(data);
        } catch (_) {
            return response.statusText || 'Request failed';
        }
    }
    return response && response.statusText ? response.statusText : 'Request failed';
}

/**
 * Centralized AJAX error handler.
 * - Writes message into alertTextField (HTML)
 * - Adds alert--danger to alertField
 * - Applies field-level validation feedback if provided by server
 *
 * @param {jqXHR} response
 * @param {string} alertTextField - selector for text container
 * @param {string} alertField - selector for alert container
 */
export function handleAjaxError(response, alertTextField, alertField) {
    const message = extractMessage(response);

    // Field-level validation support (err_flds/err_msgs)
    if (response && response.responseJSON && response.responseJSON.data) {
        const data = response.responseJSON.data;
        if (Array.isArray(data.err_flds) && Array.isArray(data.err_msgs)) {
            for (let i = 0; i < data.err_flds.length; i++) {
                let formField = '#' + data.err_flds[i];
                jQuery(formField).addClass('is-invalid');
                const feedback = formField + 'Feedback';
                jQuery(feedback).html(data.err_msgs[i]);
            }
        }
    }

    // Log centrally (no PII)
    try {
        logger.error('AJAX error', {
            status: response && response.status,
            statusText: response && response.statusText,
            url: response && response.responseURL,
        });
        recordEvent('ajax_error', {
            status: response && response.status,
        });
    } catch (_e) { /* no-op */ }

    jQuery(alertTextField).html(message);
    jQuery(alertField).addClass('alert--danger');
}

/**
 * Attach the handler to the global namespace for legacy compatibility.
 */
export function initializeAjaxError() {
    globalThis.Racketmanager = globalThis.Racketmanager || {};
    globalThis.Racketmanager.handleAjaxError = handleAjaxError;
}