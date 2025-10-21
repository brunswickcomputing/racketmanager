/**
 * Purchase Order Modal (POModal) feature
 * Modularized from legacy Racketmanager.POModal
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { showModal, hideModal } from './modal-utils.js';
import { LOADING_MODAL } from '../../config/constants.js';

const DEFAULT_MODAL_ID = '#POModal';
const ERROR_ALERT_ID = '#POUpdateResponse';
const ERROR_TEXT_ID = '#POUpdateResponseText';

/**
 * Open the Purchase Order modal via AJAX
 *
 * @param {Event} e - click event
 * @param {number|string} invoiceId - invoice identifier
 * @param {Object} options - optional overrides
 */
export function openPurchaseOrderModal(e, invoiceId, options = {}) {
    if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
    }

    const modalSelector = options.modalSelector || DEFAULT_MODAL_ID;
    const loadingSelector = options.loadingSelector || LOADING_MODAL;
    const url = getAjaxUrl();
    const security = getAjaxNonce();

    // Hide any prior errors
    jQuery(ERROR_ALERT_ID).hide().removeClass('alert--success alert--warning alert--danger');
    jQuery(ERROR_TEXT_ID).empty();

    // Show loading modal if present
    if (loadingSelector) {
        try { showModal(loadingSelector); } catch (_) { /* no-op */ }
    }

    // Use jQuery.load similar to legacy for minimal behavior change
    jQuery(modalSelector).val("");
    jQuery(modalSelector).load(
        url,
        {
            invoiceId: invoiceId,
            modal: modalSelector.replace('#', ''),
            action: 'racketmanager_purchase_order_modal',
            security: security,
        },
        function (response, status) {
            // Hide loading modal
            if (loadingSelector) {
                try { hideModal(loadingSelector); } catch (_) { /* no-op */ }
            }

            if (status === 'error') {
                // Response is a plain string; try parse as JSON (to match legacy)
                try {
                    const data = JSON.parse(response);
                    jQuery(ERROR_TEXT_ID).html(data.message || 'Error loading modal');
                } catch (err) {
                    jQuery(ERROR_TEXT_ID).html('Error loading modal');
                }
                jQuery(ERROR_ALERT_ID).show();
            } else {
                jQuery(modalSelector).show();
                // Bootstrap 5 modal show
                try { showModal(modalSelector); } catch (_) { jQuery(modalSelector).modal('show'); }
            }
        }
    );
}

/**
 * Wire up default click handler for the invoice page trigger button
 * Looks for an element with id "POModalLink" (as used in templates/club/invoice.php)
 */
export function initializePOModal() {
    const trigger = document.getElementById('POModalLink');
    if (trigger) {
        trigger.addEventListener('click', function (e) {
            const invoiceId = this.getAttribute('data-invoice-id');
            openPurchaseOrderModal(e, invoiceId);
        });
    }

    // Legacy shim: Some templates call Racketmanager.POModal(e, invoiceId)
    // Preserve compatibility by exposing the new function on the legacy namespace if available.
    if (typeof window !== 'undefined') {
        window.Racketmanager = window.Racketmanager || {};
        window.Racketmanager.POModal = function (e, invoiceId) {
            return openPurchaseOrderModal(e, invoiceId);
        };
    }
}
