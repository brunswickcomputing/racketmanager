/**
 * Partner Modal (Phase 4)
 * Modularizes legacy Racketmanager.partnerModal and Racketmanager.partnerSave
 * - Uses centralized AJAX utilities and error handling
 * - Provides delegated handlers (no globals/back-compat)
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { LOADING_MODAL } from '../../config/constants.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

const PARTNER_MODAL = '#partnerModal';
const LIST_CONTAINER = '#liEventDetails';
const ALERT_ID = '#partnerResponse';
const ALERT_TEXT = '#partnerResponseText';

/**
 * Open the Partner selection modal for a given event ID.
 * Mirrors legacy behavior, but uses modular utilities.
 * @param {Event} event
 * @param {number|string} eventId
 */
export function openPartnerModal(event, eventId) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }

  // Guard
  if (!eventId) return;

  // UI state
  jQuery(LIST_CONTAINER).addClass('is-loading');

  // Ensure the corresponding event checkbox is checked
  const eventRef = `#event-${eventId}`;
  try { jQuery(eventRef).prop('checked', true); } catch (_) { /* no-op */ }

  // Read ancillary fields (gender/season/dateEnd) if present on page
  const gender = jQuery('#playerGender').val();
  const season = jQuery('#season').val();
  const dateEnd = jQuery('#tournamentDateEnd').val();

  // Clear prior content/errors
  jQuery(ALERT_ID).hide();
  jQuery(PARTNER_MODAL).val('');

  // Optional loading modal
  try { jQuery(LOADING_MODAL).modal('show'); } catch (_) { /* no-op */ }

  // Load modal contents
  jQuery(PARTNER_MODAL).load(
    getAjaxUrl(),
    {
      eventId: eventId,
      modal: 'partnerModal',
      gender: gender,
      season: season,
      partnerId: jQuery(`#partnerId-${eventId}`).val(),
      dateEnd: dateEnd,
      action: 'racketmanager_team_partner',
      security: getAjaxNonce(),
    },
    function (response, status, xhr) {
      try { jQuery(LOADING_MODAL).modal('hide'); } catch (_) { /* no-op */ }
      jQuery(LIST_CONTAINER).removeClass('is-loading');
      if (status === 'error') {
        // Render error inside modal area using legacy-like formatting
        handleAjaxError(xhr, ALERT_TEXT, ALERT_ID);
        jQuery(ALERT_ID).show();
      } else {
        jQuery(PARTNER_MODAL).show();
        try { jQuery(PARTNER_MODAL).modal('show'); } catch (_) { /* no-op */ }
        // Attach event id to modal for convenience
        jQuery(PARTNER_MODAL).attr('data-event', eventId);
      }
    }
  );
}

/**
 * Save the selected partner from the modal.
 * Expects to be called from a button inside the partner modal form.
 * @param {HTMLElement} link - the clicked button element inside the form
 */
export function savePartner(link) {
  if (!link || !link.form || !link.form.id) return;

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_validate_partner';

  // Reset alerts and validation
  jQuery(ALERT_ID).hide();
  jQuery(ALERT_ID).removeClass('alert--success alert--warning alert--danger');
  jQuery('.is-invalid').removeClass('is-invalid');
  jQuery(ALERT_TEXT).html('');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: $form,
    success: function (response) {
      const data = response && response.data ? response.data : [];
      const modal = `#${data[0]}`; // modal id
      const partnerId = data[1];
      const partnerName = data[2];
      const eventId = data[3];

      // Update partner id/name fields on the page
      const partnerIdLink = `#partnerId-${eventId}`;
      jQuery(partnerIdLink).val(partnerId);
      const partnerNameLink = `#partnerName-${eventId}`;
      jQuery(partnerNameLink).html(partnerName);

      // Hide modal
      try { jQuery(modal).modal('hide'); } catch (_) { /* no-op */ }

      // Update pricing for the event if pricing module is present
      if (globalThis.Racketmanager && typeof globalThis.Racketmanager.setEventPrice === 'function') {
        try { globalThis.Racketmanager.setEventPrice(eventId); } catch (_) { /* no-op */ }
      }
    },
    error: function (response) {
      // Server may return [message, errorMsgs[], errorFlds[]]
      if (response && response.responseJSON) {
        const data = response.responseJSON.data;
        const message = Array.isArray(data) ? data[0] : (data && (data.msg || data.message));
        // Field errors
        if (Array.isArray(data?.[1]) && Array.isArray(data?.[2])) {
          const errorMsg = data[1];
          const errorField = data[2];
          for (let i = 0; i < errorField.length; i++) {
            let formField = `#${errorField[i]}`;
            jQuery(formField).addClass('is-invalid');
            const feedback = `${formField}Feedback`;
            jQuery(feedback).html(errorMsg[i]);
          }
        }
        jQuery(ALERT_TEXT).html(message || 'An error occurred');
      } else {
        jQuery(ALERT_TEXT).text(response?.statusText || 'Request failed');
      }
      jQuery(ALERT_ID).addClass('alert--danger').show();
    },
  });
}

/**
 * Initialize delegated handlers for the Partner modal workflow
 */
export function initializePartnerModal() {
  // Delegated handler to open the modal via explicit trigger
  jQuery(document)
    .off('click.racketmanager.partnerModal', '[data-action="open-partner-modal"]')
    .on('click.racketmanager.partnerModal', '[data-action="open-partner-modal"]', function (e) {
      const eventId = this.getAttribute('data-event-id');
      return openPartnerModal(e, eventId);
    });

  // Delegated handler to save partner from within modal footer
  jQuery(document)
    .off('click.racketmanager.partnerSave', '[data-action="set-partner"]')
    .on('click.racketmanager.partnerSave', '[data-action="set-partner"]', function (e) {
      e.preventDefault();
      return savePartner(this);
    });
}
