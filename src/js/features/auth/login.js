/**
 * Auth: Login (modular)
 * Replaces the legacy "Racketmanager.login" function
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

/**
 * Submit login using current form fields
 * Expects inputs with IDs: #user_login, #user_pass, optional #redirect_to
 */
export function submitLogin(eventOrButton) {
    const event = eventOrButton?.preventDefault ? eventOrButton : null;
    if (event) event.preventDefault();

    const notifyField = '#login';
    const alertTextField = '#loginAlertResponse';
    const alertField = '#loginAlert';

    try { jQuery(notifyField).css('opacity', 0.25); } catch (_) { /* no-op */ }
    jQuery(alertField).hide();
    jQuery(alertTextField).html('');
    jQuery('.is-invalid').removeClass('is-invalid');

    const userLogin = jQuery('#user_login').val();
    const userPass = jQuery('#user_pass').val();
    const redirectURL = jQuery('#redirect_to').val();

    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: {
            action: 'racketmanager_login',
            security: getAjaxNonce(),
            log: userLogin,
            pwd: userPass,
            redirect_to: redirectURL,
        },
        success: function (response) {
            if (response && response.data) {
                document.location.href = response.data;
            }
        },
        error: function (response) {
            handleAjaxError(response, alertTextField, alertField);
            jQuery(alertField).show();
        },
        complete: function () {
            try { jQuery(notifyField).css('opacity', 1); } catch (_) { /* no-op */ }
        }
    });
}

/**
 * Initialize the delegated handler for login submit
 * Usage: add data-action="login-submit" to the "submit" button
 */
export function initializeLogin() {
    jQuery(document)
        .off('click.racketmanager.login', '[data-action="login-submit"]')
        .on('click.racketmanager.login', '[data-action="login-submit"]', function (e) {
            e.preventDefault();
            return submitLogin(e);
        });
}
