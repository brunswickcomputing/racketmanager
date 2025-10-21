/**
 * Auth: Reset Password (modular)
 * Replaces legacy Racketmanager.resetPassword
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

const LOGIN_ALERT = '#loginAlert';
const LOGIN_ALERT_TEXT = '#loginAlertResponse';
const RESET_ALERT = '#resetAlert';
const RESET_ALERT_TEXT = '#resetAlertResponse';
const RESET_MODAL = '#resetPasswordModal';

/**
 * Submit reset password using a form button inside the reset form
 * @param {HTMLElement|Event} btnOrEvent - button (with .form) or event
 */
export function submitResetPassword(btnOrEvent) {
    const event = btnOrEvent?.preventDefault ? btnOrEvent : null;
    if (event) event.preventDefault();

    // Derive form element
    let form;
    if (btnOrEvent && btnOrEvent.form) {
        form = btnOrEvent.form;
    } else {
        // Fallback: try to find a visible reset form within the modal
        form = document.querySelector(`${RESET_MODAL} form`) || document.querySelector('form#reset-password');
    }
    if (!form || !form.id) return;

    const formId = `#${form.id}`;
    let $form = jQuery(formId).serialize();
    $form += '&action=racketmanager_reset_password';

    jQuery(LOGIN_ALERT).hide().removeClass('alert--success alert--warning alert--danger');
    jQuery(RESET_ALERT).hide().removeClass('alert--success alert--warning alert--danger');
    jQuery('.is-invalid').removeClass('is-invalid');

    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: $form,
        success: function (response) {
            const message = response?.data?.[0] ?? '';
            jQuery(LOGIN_ALERT).show().addClass('alert--success');
            jQuery(LOGIN_ALERT_TEXT).html(message);
            try { jQuery(RESET_MODAL).modal('hide'); } catch (_) { /* no-op */ }
        },
        error: function (response) {
            handleAjaxError(response, RESET_ALERT_TEXT, RESET_ALERT);
            jQuery(RESET_ALERT).show();
        },
        complete: function () {
        }
    });
}

/**
 * Initialize the delegated handler for reset password submit
 * Usage: add data-action="reset-password" to the button inside the reset form/modal
 */
export function initializeResetPassword() {
    jQuery(document)
        .off('click.racketmanager.resetPassword', '[data-action="reset-password"]')
        .on('click.racketmanager.resetPassword', '[data-action="reset-password"]', function (e) {
            e.preventDefault();
            return submitResetPassword(this);
        });
}
