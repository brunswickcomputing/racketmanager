/**
 * Set Match Status - Modularized
 * Mirrors legacy Racketmanager.setMatchStatus behavior using architecture guidelines.
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { setRubberStatusMessages, setRubberStatusClasses, setTeamMessage } from './rubber-status-ui.js';

/**
 * Update status messages, classes, and values across rubbers
 * @param {number} numRubbers
 * @param {Array<[string, string]>} statusMessages
 * @param {Array<[string, string]>} statusClasses
 * @param {string} scoreStatus
 */
function updateRubbersStatusUI(numRubbers, statusMessages, statusClasses, scoreStatus) {
    for (let x = 1; x <= numRubbers; x++) {
        const rubberNumber = x;
        setRubberStatusMessages(rubberNumber, statusMessages);
        setRubberStatusClasses(rubberNumber, statusClasses);
        const matchStatusRef = `#match_status_${rubberNumber}`;
        jQuery(matchStatusRef).attr('value', scoreStatus);
    }
}

/**
 * Update singles or non-rubber team messages and status classes
 * @param {Array<[string, string]>} statusMessages
 * @param {Array<[string, string]>} statusClasses
 */
function updateSinglesStatusUI(statusMessages, statusClasses) {
    for (const [teamRef, teamMessage] of statusMessages) {
        const messageRef = `#match-message-${teamRef}`;
        setTeamMessage(messageRef, teamMessage);
    }
    for (const [teamRef, teamClass] of statusClasses) {
        const statusRef = `#match-status-${teamRef}`;
        jQuery(statusRef).removeClass('winner loser tie');
        if (teamClass) {
            jQuery(statusRef).addClass(teamClass);
        }
    }
}

function hideStatusModal(modalId) {
    if (!modalId) {
        return;
    }
    const modal = `#${modalId}`;
    try {
        jQuery(modal).modal('hide');
    } catch (e) {
        console.error(e);
    }
}

/**
 * Core implementation for setting match status.
 * Accepts the same "link" element passed from inline onclick, where link.form is the form.
 * @param {HTMLElement} link
 */
export function setMatchStatus(link) {
    if (!link?.form?.id) {
        return;
    }

    const formId = `#${link.form.id}`;
    let $form = jQuery(formId).serialize();
    const splashBlock = '#splashBlockMatch';

    jQuery(splashBlock).addClass('is-loading');

    $form += '&action=racketmanager_set_fixture_status';

    const notifyField = '#matchStatusResponse';
    const alertTextField = '#matchStatusResponseText';

    jQuery(notifyField).hide();
    jQuery(alertTextField).html('');
    jQuery('.is-invalid').removeClass('is-invalid');

    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: $form,
        success: function (response) {
            const data = response?.data || {};
            const scoreStatus = data.match_status;
            const statusMessages = Object.entries(data.status_message || {});
            const statusClasses = Object.entries(data.status_class || {});
            const numRubbers = data.num_rubbers;

            if (numRubbers) {
                updateRubbersStatusUI(numRubbers, statusMessages, statusClasses, scoreStatus);
            } else {
                updateSinglesStatusUI(statusMessages, statusClasses);
            }

            // Update base match status field
            jQuery('#match_status').attr('value', scoreStatus);

            // Hide modal provided in response
            hideStatusModal(data.modal);
        },
        error: function (response) {
            handleAjaxError(response, alertTextField, notifyField);
            jQuery(notifyField).show();
        },
        complete: function () {
            jQuery(splashBlock).removeClass('is-loading');
        }
    });
}

/**
 * Initialize feature: attach delegated handler.
 * Expose initializer on a neutral global namespace for templates to call directly.
 */
export function initializeSetMatchStatus() {
    // Delegated handler (future-friendly if templates adopt data-action)
    jQuery(document)
        .off('click.racketmanager.setMatchStatus', '[data-action="set-match-status"]')
        .on('click.racketmanager.setMatchStatus', '[data-action="set-match-status"]', function (e) {
            e.preventDefault();
            // Expect this button to be inside a form
            return setMatchStatus(this);
        });
}

