/**
 * Entry Request - Modularized
 * Replaces legacy Racketmanager.entryRequest and removes dependency on js/entry-link.js
 */

import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { getAjaxUrl } from '../../config/ajax-config.js';

const FORM_ID = '#form-entry';
const ENTRY_DETAILS = '#entry-details';
const ALERT_FIELD = '#entryAlert';
const ALERT_TEXT = '#entryAlertResponse';
const SUBMIT_BUTTON = '#entrySubmit';
const ACCEPTANCE = '#acceptance';

/**
 * Perform the entry request
 * @param {Event} event
 * @param {string} type - e.g. 'tournament' | 'league' | 'cup'
 */
export function entryRequest(event, type) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }
  if (!type) return;

  // UI state prep
  jQuery(ENTRY_DETAILS).addClass('is-loading');
  jQuery(ALERT_FIELD).removeClass('alert--success alert--warning alert--info alert--danger').hide();
  jQuery(ALERT_TEXT).html('');
  jQuery('.is-invalid').removeClass('is-invalid');
  jQuery('.invalid-feedback').val('');
  jQuery('.invalid-tooltip').val('');
  jQuery(SUBMIT_BUTTON).hide();

  // Build form payload
  let $form = jQuery(FORM_ID).serialize();
  $form += `&action=racketmanager_${type}_entry`;

  jQuery.ajax({
    url: getAjaxUrl(),
    async: false,
    type: 'POST',
    data: $form,
    success: function (response) {
      let msg;
      let msgType;
      if (Array.isArray(response?.data)) {
        msg = response.data[0];
        msgType = response.data[1];
        // Optional redirect link
        if (response.data[2]) {
          const link = response.data[3];
          if (link) {
            try { globalThis.location = link; } catch (_) { /* no-op */ }
          }
        }
      } else {
        msg = response?.data;
        msgType = 'success';
      }
      const msgClass = `alert--${msgType}`;
      jQuery(ALERT_FIELD).addClass(msgClass);
      jQuery(ALERT_TEXT).html(msg);
    },
    error: function (response) {
      handleAjaxError(response, ALERT_TEXT, ALERT_FIELD);
    },
    complete: function () {
      jQuery(ALERT_FIELD).show();
      try { jQuery(ACCEPTANCE).prop('checked', false); } catch (_) { /* no-op */ }
      jQuery(ENTRY_DETAILS).removeClass('is-loading');
      // Keep submit hidden on completion per legacy; UX can revisit later
    }
  });
}

/**
 * Initialize delegated handler for entry submit
 */
export function initializeEntryRequest() {
  jQuery(document)
    .off('click.racketmanager.entryRequest', '[data-action="entry-submit"]')
    .on('click.racketmanager.entryRequest', '[data-action="entry-submit"]', function (e) {
      const type = this.getAttribute('data-type') || jQuery(this).data('type');
      return entryRequest(e, type);
    });

  // Fallback: handle #entrySubmit if present with data-type (in case data-action attr missing in some templates)
  jQuery(document)
    .off('click.racketmanager.entryRequestFallback', '#entrySubmit')
    .on('click.racketmanager.entryRequestFallback', '#entrySubmit', function (e) {
      const type = this.getAttribute('data-type') || jQuery(this).data('type');
      // Only trigger if type is available and element is not explicitly opted-out of delegated flow
      if (type) {
        return entryRequest(e, type);
      }
    });
}
