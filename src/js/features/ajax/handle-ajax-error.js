/**
 * AJAX Error Handler - Modularized
 * Provides a centralized error handling function compatible with legacy usage.
 */

import { logger, recordEvent } from '../../utils/logger.js';

/**
 * Safely resolve a jQuery target from a selector-like input.
 * For id selectors, uses getElementById to avoid jQuery parsing the input as HTML.
 *
 * @param {string} selector
 * @returns {JQuery}
 */
function getSafeJQueryTarget(selector) {
    if (typeof selector === 'string' && selector.startsWith('#')) {
        const id = selector.slice(1);
        const element = document.getElementById(id);
        return element ? jQuery(element) : jQuery();
    }
    return jQuery(selector);
}

/**
 * Safely extract a message from a jQuery AJAX error response.
 * Prefers server-provided JSON payloads, falls back to statusText.
 * Mirrors legacy getMessageFromResponse behaviour for 500s but works for any status.
 * @param {jqXHR} response
 * @returns {string}
 */
export function extractMessage(response) {
    if (response?.responseJSON?.data) {
        const data = response.responseJSON.data;
        // Try common fields first
        if (typeof data.msg === 'string') return data.msg;
        if (typeof data.message === 'string') return data.message;
        // Fallback to stringify data
        try {
            return typeof data === 'string' ? data : JSON.stringify(data);
        } catch (_) {
            return response.statusText || 'Request failed';
        }
    }
    return response?.statusText ? response.statusText : 'Request failed';
}

/**
 * Apply field-level validation error classes and feedback text,
 * returning any messages that could not be displayed adjacent to fields.
 *
 * @param {object} [data]
 * @returns {string[]}
 */
export function applyFieldValidation(data) {
    const extraMessages = [];
    if (!Array.isArray(data?.err_flds) || !Array.isArray(data?.err_msgs)) {
        return extraMessages;
    }

    for (let i = 0; i < data.err_flds.length; i++) {
        const formField = '#' + data.err_flds[i];
        jQuery(formField).addClass('is-invalid');
        const feedback = formField + 'Feedback';
        const $feedback = jQuery(feedback);
        if ($feedback.length) {
            $feedback.html(data.err_msgs[i]);
        } else if (data.err_msgs[i]) {
            extraMessages.push(data.err_msgs[i]);
        }
    }

    return extraMessages;
}

/**
 * Centralized AJAX error handler.
 * - Writes a message into alertTextField (HTML)
 * - Adds alert--danger to alertField
 * - Applies field-level validation feedback if provided by server
 *
 * @param {jqXHR} response
 * @param {string} alertTextField - selector for text container
 * @param {string} alertField - selector for alert container
 */
export function handleAjaxError(response, alertTextField, alertField) {
    let message = extractMessage(response);
    const extraMessages = applyFieldValidation(response?.responseJSON?.data);

    if (extraMessages.length > 0) {
        message += '<br>' + extraMessages.join('<br>');
    }

    // Log centrally (no PII)
    try {
        logger.error('AJAX error', {
            status: response?.status,
            statusText: response?.statusText,
            url: response?.responseURL,
        });
        recordEvent('ajax_error', {
            status: response?.status,
        });
    } catch (e) { console.error(e); }

    getSafeJQueryTarget(alertTextField).html(message);
    getSafeJQueryTarget(alertField).addClass('alert--danger');
}

/**
 * Attach the handler to the global namespace for legacy compatibility.
 */
export function initializeAjaxError() {
    globalThis.Racketmanager = globalThis.Racketmanager || {};
    globalThis.Racketmanager.handleAjaxError = handleAjaxError;
}