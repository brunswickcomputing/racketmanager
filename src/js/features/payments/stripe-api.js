// stripe-api.js (moved to src/js/features/payments)
// Modular Stripe-related API requests for Racketmanager
// Exports createPaymentRequest with backward-compatible signature.
import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';

/**
 * Create a PaymentIntent for the given tournament entry and invoice.
 *
 * Backward compatibility with legacy racketmanager.legacy.js:
 * - When a callback function is provided as the third argument, this mirrors the
 *   legacy behavior and invokes the callback with the output string on completion.
 *   It does not throw; it passes either the client secret (success) or an error message (failure).
 * - When no callback is provided, it returns a Promise that resolves to the client secret
 *   or rejects with an Error on failure (modern usage in modules).
 *
 * @param {string|number} tournamentEntry
 * @param {string|number} invoiceId
 * @param {string|number} tournamentId
 * @param {string|number} playerId
 * @param {(output:string)=>void} [callback]
 * @returns {Promise<string>|void}
 */
export async function createPaymentRequest(tournamentEntry, invoiceId, tournamentId, playerId, callback) {
  const hasCallback = typeof callback === 'function';

  // If using legacy-style callback, mirror racketmanager.legacy.js behavior closely.
  if (hasCallback) {
    // Ensure minimum globals exist; if not, fail fast through the callback.
    if (typeof jQuery === 'undefined' || !jQuery.ajax || !getAjaxUrl()) {
      try { callback('Ajax configuration is missing.'); } catch (_) {}
      return;
    }
    let output;
    jQuery.ajax({
      url: getAjaxUrl(),
      type: 'POST',
      data: {
        tournament_entry: tournamentEntry,
        invoiceId: invoiceId,
        playerId: playerId,
        tournament_id: tournamentId,
        action: 'racketmanager_tournament_payment_create',
        security: getAjaxNonce(),
      },
      success: function (response) {
        // Legacy placed response.data into output regardless of type
        output = response?.data;
      },
      error: function (xhr) {
        if (xhr?.responseJSON) {
          output = xhr.responseJSON.data;
        } else {
          output = xhr?.statusText || 'Network error creating payment.';
        }
      },
      complete: function () {
        try { callback(output); } catch (_) {}
      },
    });
    return;
  }

  // Modern Promise-based usage (module consumers)
  // Use centralized config getters instead of touching ajax_var directly
  const ajaxUrl = getAjaxUrl();
  const ajaxNonce = getAjaxNonce();
  if (!ajaxUrl) {
    throw new Error('Ajax configuration is missing. Please reload the page.');
  }

  const formData = new URLSearchParams();
  formData.set('tournament_entry', tournamentEntry);
  formData.set('invoiceId', invoiceId);
  formData.set('action', 'racketmanager_tournament_payment_create');
  formData.set('security', ajaxNonce || '');

  try {
    const res = await fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: formData.toString(),
      credentials: 'same-origin',
    });

    // WordPress admin-ajax returns 200 with a JSON envelope { success, data }
    const json = await res.json();

    if (json?.success) {
      return json.data; // expected to be client_secret
    }

    // If not success, attempt to extract message
    const message = typeof json?.data === 'string' ? json.data : (json?.data?.message || 'Unable to create payment.');
    throw new Error(message);
  } catch (err) {
    // Fallback: try jQuery Ajax if available
    if (typeof jQuery !== 'undefined' && jQuery?.ajax) {
      const jqResult = await new Promise((resolve, reject) => {
        jQuery.ajax({
          url: getAjaxUrl(),
          type: 'POST',
          data: {
            tournament_entry: tournamentEntry,
            invoiceId: invoiceId,
            action: 'racketmanager_tournament_payment_create',
            security: getAjaxNonce(),
          },
          success: function (response) {
            if (response && response.success) {
              resolve(response.data);
            } else {
              reject(new Error(response?.data || 'Unable to create payment.'));
            }
          },
          error: function (xhr) {
            const msg = xhr?.responseJSON?.data || xhr?.statusText || 'Network error creating payment.';
            reject(new Error(msg));
          },
        });
      });
      return jqResult;
    }
    throw err;
  }
}
