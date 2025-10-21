/**
 * Match Status Modal
 * Modularized from legacy Racketmanager.statusModal
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { LOADING_MODAL } from '../../config/constants.js';

const SCORE_STATUS_MODAL = '#scoreStatusModal';
const HEADER_ALERT_ID = '#headerResponse';
const HEADER_RESPONSE_TEXT_ID = '#headerResponseResponse';

/**
 * Open the match status modal.
 * Mirrors legacy behavior while using modular utilities.
 *
 * @param {Event} event
 * @param {number|string} matchId
 */
export function openMatchStatusModal(event, matchId) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    // Show loading modal
    try {
        jQuery(LOADING_MODAL).modal('show');
    } catch (_) { /* no-op */ }

    // Reset header error
    jQuery(HEADER_ALERT_ID).hide();

    // Build parameters
    const matchStatus = jQuery('#match_status').val();
    const data = {
        match_id: matchId,
        modal: 'scoreStatusModal',
        match_status: matchStatus,
        action: 'racketmanager_match_status',
        security: getAjaxNonce(),
    };

    // Clear and load modal content
    jQuery(SCORE_STATUS_MODAL).val('');
    jQuery(SCORE_STATUS_MODAL).load(
        getAjaxUrl(),
        data,
        function (response, status) {
            // Hide loading modal
            try {
                jQuery(LOADING_MODAL).modal('hide');
            } catch (_) { /* no-op */ }

            if (status === 'error') {
                // Try to parse response for message consistency
                try {
                    const parsed = JSON.parse(response);
                    jQuery(HEADER_RESPONSE_TEXT_ID).html(parsed.message || 'An error occurred');
                } catch (_) {
                    jQuery(HEADER_RESPONSE_TEXT_ID).html('An error occurred');
                }
                jQuery(HEADER_ALERT_ID).show();
            } else {
                jQuery(SCORE_STATUS_MODAL).show();
                try {
                    jQuery(SCORE_STATUS_MODAL).modal('show');
                } catch (_) { /* no-op */ }
            }
        }
    );
}

/**
 * Initialize the match status modal feature.
 * - Attaches legacy-compatible global for templates that call Racketmanager.statusModal
 */
export function initializeMatchStatusModal() {
    // Optional: delegated handler if we ever add data-action hooks
    jQuery(document)
        .off('click.racketmanager.matchStatus', '[data-action="open-match-status-modal"]')
        .on('click.racketmanager.matchStatus', '[data-action="open-match-status-modal"]', function (e) {
            const matchId = this.getAttribute('data-match-id');
            return openMatchStatusModal(e, matchId);
        });
}
