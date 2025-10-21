/**
 * Set Purchase Order submission
 * Modularized from legacy Racketmanager.setPurchaseOrder
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

const INVOICE_ALERT_ID = '#invoiceAlert';
const INVOICE_RESPONSE_ID = '#invoiceResponse';
const PO_ALERT_ID = '#POUpdateResponse';
const PO_RESPONSE_TEXT_ID = '#POUpdateResponseText';

/**
 * Submit purchase order form via AJAX
 * Mirrors legacy behavior for minimal change risk
 *
 * @param {Event} e
 * @param {HTMLButtonElement|HTMLAnchorElement} link - element inside a form (uses link.form)
 */
export function setPurchaseOrder(e, link) {
    if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
    }

    if (!link || !link.form || !link.form.id) {
        console.error('setPurchaseOrder: invalid link/form');
        return false;
    }

    const formId = `#${link.form.id}`;
    let $form = jQuery(formId).serialize();
    $form += '&action=racketmanager_set_purchase_order';

    // Reset alerts
    jQuery(INVOICE_ALERT_ID).hide().removeClass('alert--success alert--warning alert--danger');
    jQuery(PO_ALERT_ID).hide().removeClass('alert--success alert--warning alert--danger');

    // Clear validation state
    jQuery('.is-invalid').removeClass('is-invalid');

    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: $form,
        success: function (response) {
            const data = response.data || {};
            const message = data.msg || '';
            const modal = `#${data.modal || ''}`;
            const invoice = data.invoice;

            jQuery(INVOICE_ALERT_ID).show();
            jQuery(INVOICE_ALERT_ID).addClass('alert--success');
            jQuery(INVOICE_RESPONSE_ID).html(message);

            if (data.modal) {
                try {
                    // Bootstrap 5 hide
                    jQuery(modal).modal('hide');
                } catch (_) { /* no-op */ }
            }
            if (invoice) {
                jQuery('#invoiceDetails').html(invoice);
            }
        },
        error: function (response) {
            handleAjaxError(response, PO_RESPONSE_TEXT_ID, PO_ALERT_ID);
            jQuery(PO_ALERT_ID).show();
        },
        complete: function () {}
    });

    return false;
}


/**
 * Initialize delegated handler for setting purchase order (no legacy globals)
 */
export function initializeSetPurchaseOrder() {
    // Delegate clicks on elements with data-action="set-purchase-order"
    // This enables progressive enhancement without relying on legacy globals.
    jQuery(document)
        .off('click.racketmanager.setPO', '[data-action="set-purchase-order"]')
        .on('click.racketmanager.setPO', '[data-action="set-purchase-order"]', function (e) {
            return setPurchaseOrder(e, this);
        });
}
