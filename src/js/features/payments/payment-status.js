/**
 * Payments - Payment Status (Phase 6)
 * Modularizes legacy Racketmanager.setPaymentStatus
 * - Minimal parity: POST update to server; UI updates (if any) can be extended later
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';

/**
 * Update payment status for a given reference
 * @param {string} paymentReference
 */
export function setPaymentStatus(paymentReference) {
  if (!paymentReference) return;
  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: {
      paymentReference: paymentReference,
      action: 'racketmanager_update_payment',
      security: getAjaxNonce(),
    }
  });
}

/**
 * Initialize delegated handlers for payment status updates
 * Expects elements to provide a data-payment-ref attribute.
 */
export function initializePaymentStatus() {
  // Generic data-action hook (e.g., buttons/links)
  jQuery(document)
    .off('click.racketmanager.paymentStatus', '[data-action="set-payment-status"]')
    .on('click.racketmanager.paymentStatus', '[data-action="set-payment-status"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      const ref = this.getAttribute('data-payment-ref');
      return setPaymentStatus(ref);
    });

  // Inputs/selects with class .payment-status and data-payment-ref
  jQuery(document)
    .off('change.racketmanager.paymentStatus', '.payment-status')
    .on('change.racketmanager.paymentStatus', '.payment-status', function () {
      const ref = this.getAttribute('data-payment-ref');
      return setPaymentStatus(ref);
    });
}
