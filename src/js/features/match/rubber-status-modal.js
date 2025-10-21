/**
 * Rubber Score Status Modal - Modularized
 * Implements opening and saving rubber score status using delegated data-action hooks.
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { LOADING_MODAL } from '../../config/constants.js';

const SCORE_STATUS_MODAL = '#scoreStatusModal';
const ERROR_ALERT = '#scoreStatusResponse';
const ERROR_TEXT = '#scoreStatusResponseText';

/**
 * Open the rubber score status modal
 * @param {Event} event
 */
export function openRubberStatusModal(event) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    const trigger = event?.currentTarget || this;
    const rubberId = trigger?.getAttribute('data-rubber-id');
    const rubberNumber = trigger?.getAttribute('data-rubber-number');

    try { jQuery(LOADING_MODAL).modal('show'); } catch (_) { /* no-op */ }

    const errorField = '#matchAlert';
    const errorResponseField = errorField + 'Response';
    jQuery(errorField).hide();

    const scoreStatus = jQuery(`#match_status_${rubberNumber}`).val();

    jQuery(SCORE_STATUS_MODAL).val('');
    jQuery(SCORE_STATUS_MODAL).load(
        getAjaxUrl(),
        {
            rubber_id: rubberId,
            score_status: scoreStatus,
            modal: 'scoreStatusModal',
            action: 'racketmanager_match_rubber_status',
            security: getAjaxNonce(),
        },
        function (response, status) {
            try { jQuery(LOADING_MODAL).modal('hide'); } catch (_) { /* no-op */ }
            if (status === 'error') {
                try {
                    const data = JSON.parse(response);
                    jQuery(errorResponseField).html(data.message || 'An error occurred');
                } catch (_) {
                    jQuery(errorResponseField).html('An error occurred');
                }
                jQuery(errorField).show();
            } else {
                jQuery(SCORE_STATUS_MODAL).show();
                try { jQuery(SCORE_STATUS_MODAL).modal('show'); } catch (_) { /* no-op */ }
            }
        }
    );
}

/**
 * Save rubber status from modal form
 * @param {HTMLElement} link - the clicked button inside the modal footer
 */
export function setRubberStatus(link) {
    if (!link || !link.form || !link.form.id) return;

    const formId = `#${link.form.id}`;
    let $form = jQuery(formId).serialize();
    const splashBlock = '#splashBlockRubber';

    jQuery(splashBlock).addClass('is-loading');
    $form += '&action=racketmanager_set_match_rubber_status';

    jQuery(ERROR_ALERT).hide();
    jQuery(ERROR_TEXT).html('');
    jQuery('.is-invalid').removeClass('is-invalid');

    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: $form,
        success: function (response) {
            const data = response?.data || {};
            const rubberNumber = data.rubber_number;
            const scoreStatus = data.score_status;
            const statusMessages = Object.entries(data.status_message || {});
            const statusClasses = Object.entries(data.status_class || {});
            if (globalThis.Racketmanager?.setRubberStatusMessages) {
                globalThis.Racketmanager.setRubberStatusMessages(rubberNumber, statusMessages);
            }
            if (globalThis.Racketmanager?.setRubberStatusClasses) {
                globalThis.Racketmanager.setRubberStatusClasses(rubberNumber, statusClasses);
            }
            const modal = `#${data.modal}`;
            const matchStatusRef = `#match_status_${rubberNumber}`;
            jQuery(matchStatusRef).val(scoreStatus);
            try { jQuery(modal).modal('hide'); } catch (_) { /* no-op */ }
        },
        error: function () {
            // If we have centralized ajax error handler, we could call it, but keep minimal here
            jQuery(ERROR_TEXT).html('An error occurred');
            jQuery(ERROR_ALERT).show();
        },
        complete: function () {
            jQuery(splashBlock).removeClass('is-loading');
        }
    });
}

/**
 * Initialize delegated handlers for rubber status modal
 */
export function initializeRubberStatusModal() {
    jQuery(document)
        .off('click.racketmanager.rubberStatusOpen', '[data-action="open-rubber-status-modal"]')
        .on('click.racketmanager.rubberStatusOpen', '[data-action="open-rubber-status-modal"]', openRubberStatusModal);

    jQuery(document)
        .off('click.racketmanager.rubberStatusSave', '[data-action="set-rubber-status"]')
        .on('click.racketmanager.rubberStatusSave', '[data-action="set-rubber-status"]', function (e) {
            e.preventDefault();
            return setRubberStatus(this);
        });
}
