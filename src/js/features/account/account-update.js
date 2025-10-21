import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

/**
 * Account Update Feature
 * Modularized version of Racketmanager.accountUpdate
 */

/**
 * Perform account update via AJAX.
 * Mirrors legacy behavior used by templates (form-member-account.php).
 *
 * @param {Event} e - The event from the click/submit handler.
 * @param {HTMLElement} link - The triggering element; expected to have a form property.
 */
export function accountUpdate(e, link) {
    e.preventDefault();

    const alertField = '#userAlert';
    const alertResponseField = '#userAlertResponse';
    const loadingField = '#accountUpdateModule';
    const notifyField = '#memberAccountForm';

    // Reset UI
    jQuery(alertField).hide();
    jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
    jQuery(alertResponseField).val('');
    jQuery('.is-invalid').removeClass('is-invalid');

    // Show loading
    jQuery(loadingField).addClass('is-loading');
    jQuery(notifyField).hide();

    // Build form payload
    const formId = '#' + link.form.id;
    let form = jQuery(formId).serialize();
    form += '&action=racketmanager_update_account';

    jQuery.ajax({
        type: 'POST',
        datatype: 'json',
        url: getAjaxUrl(),
        async: false,
        data: form,
        success: function (response) {
            const data = response.data;
            const msg = data.msg;
            jQuery(alertResponseField).html(msg);
            const alertClass = 'alert--' + data.class;
            jQuery(alertField).addClass(alertClass);
        },
        error: function (response) {
            handleAjaxError(response, alertResponseField, alertField);
        },
        complete: function () {
            jQuery(alertField).show();
            jQuery(notifyField).show();
            jQuery(loadingField).removeClass('is-loading');
        }
    });
}

/**
 * Initialize feature: expose accountUpdate on global Racketmanager for templates relying on it.
 */
export function initializeAccountUpdate() {
    // Bind click handler to the Member Account button (id=memberAccountButton)
    // Use a namespaced delegated event so we can safely re-initialize after AJAX loads without duplicates
    jQuery(document)
        .off('click.racketmanagerAccount', '#memberAccountButton')
        .on('click.racketmanagerAccount', '#memberAccountButton', function (e) {
            accountUpdate(e, this);
        });
}
