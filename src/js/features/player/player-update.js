import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

/**
 * Player Update Feature
 * Modularized version of legacy Racketmanager.updatePlayer
 */

/**
 * Perform player update via AJAX.
 * @param {Event} e - click event
 * @param {HTMLElement} link - button inside a form; expects link.form.id
 */
export function updatePlayer(e, link) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();

    if (!link || !link.form || !link.form.id) {
        console.error('updatePlayer: invalid link/form');
        return false;
    }

    const formId = `#${link.form.id}`;
    let $form = jQuery(formId).serialize();
    $form += '&action=racketmanager_update_player';

    const submitButton = '#updatePlayerSubmit';
    const alertField = '#playerUpdateResponse';
    const alertTextField = '#playerUpdateResponseText';

    // Reset UI
    jQuery(submitButton).hide();
    jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
    jQuery(alertField).hide();
    jQuery(alertTextField).html('');
    jQuery('.is-invalid').removeClass('is-invalid');
    jQuery('.invalid-feedback').val('');

    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: $form,
        async: false,
        success: function (response) {
            const data = response.data;
            const alertClass = data.state ? `alert--${data.state}` : 'alert--success';
            jQuery(alertField).addClass(alertClass);
            jQuery(alertTextField).html(data.msg);
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
 * Initialize feature: bind click to #updatePlayerSubmit and provide a global fallback for templates.
 */
export function initializePlayerUpdate() {
    // Delegated binding (prevents duplicate bindings and works with dynamic content)
    jQuery(document)
        .off('click.racketmanager.player', '#updatePlayerSubmit')
        .on('click.racketmanager.player', '#updatePlayerSubmit', function (e) {
            return updatePlayer(e, this);
        });
}
