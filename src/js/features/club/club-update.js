import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

/**
 * Club Update Feature
 * Modularized version of legacy Racketmanager.updateClub
 */

/**
 * Perform club update via AJAX.
 * @param {Event} e - click event
 * @param {HTMLElement} link - button inside a form; expects link.form.id
 */
export function updateClub(e, link) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();

    if (!link || !link.form || !link.form.id) {
        console.error('updateClub: invalid link/form');
        return false;
    }

    const formId = `#${link.form.id}`;
    let $form = jQuery(formId).serialize();
    const submitButton = '#updateClubSubmit';
    $form += '&action=racketmanager_update_club';

    const alertField = '#clubUpdateResponse';
    const alertTextField = '#clubUpdateResponseText';

    // Reset UI
    jQuery(submitButton).hide();
    jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
    jQuery(alertField).hide();
    jQuery(alertTextField).html('');
    jQuery('.is-invalid').removeClass('is-invalid');
    jQuery('.invalid-feedback').val('');
    jQuery('.invalid-tooltip').val('');

    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: $form,
        async: false,
        success: function (response) {
            const alertClass = "alert--" + response.data.status;
            jQuery(alertField).addClass(alertClass);
            jQuery(alertTextField).html(response.data.msg);
        },
        error: function (response) {
            handleAjaxError(response, alertTextField, alertField);
        },
        complete: function () {
            jQuery(alertField).show();
            jQuery(submitButton).show();
        }
    });

    return false;
}

/**
 * Initialize feature: bind click to #updateClubSubmit using delegated event.
 */
export function initializeClubUpdate() {
    // Delegated binding (survives dynamic content changes)
    jQuery(document)
        .off('click.racketmanager.club', '#updateClubSubmit')
        .on('click.racketmanager.club', '#updateClubSubmit', function (e) {
            return updateClub(e, this);
        });
}
