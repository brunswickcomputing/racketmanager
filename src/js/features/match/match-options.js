/**
 * Match Options (Phase 10 - Stage 2)
 * Modularized implementation of legacy Racketmanager.matchOptions
 * - Opens a modal with match options like schedule, switch home/away, reset result
 * - Uses centralized AJAX utilities and error handling
 * - Delegated handlers on document for dynamic content
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { LOADING_MODAL } from '../../config/constants.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

const MATCH_MODAL = '#matchModal';
const HEADER_ALERT = '#headerResponse';
const HEADER_ALERT_TEXT = '#headerResponseResponse';

/**
 * Open match options modal for a given match and option
 * @param {Event} event
 * @param {number|string} matchId
 * @param {string} optionKey
 */
export function openMatchOptions(event, matchId, optionKey) {
  if (event && typeof event.preventDefault === 'function') event.preventDefault();
  if (!matchId || !optionKey) return;

  // Show loading modal
  try { jQuery(LOADING_MODAL).modal('show'); } catch (_) { /* no-op */ }

  // Reset header error area
  jQuery(HEADER_ALERT).hide();
  jQuery(HEADER_ALERT_TEXT).html('');

  // Clear and load modal
  jQuery(MATCH_MODAL).val('');
  jQuery(MATCH_MODAL).load(
    getAjaxUrl(),
    {
      match_id: matchId,
      modal: 'matchModal',
      option: optionKey,
      action: 'racketmanager_match_option',
      security: getAjaxNonce(),
    },
    function (response, status, xhr) {
      try { jQuery(LOADING_MODAL).modal('hide'); } catch (_) { /* no-op */ }
      if (status === 'error') {
        // Prefer parsing JSON; fallback to centralized handler
        try {
          const data = JSON.parse(response);
          jQuery(HEADER_ALERT_TEXT).html(data.message || 'An error occurred');
        } catch (_) {
          handleAjaxError(xhr, HEADER_ALERT_TEXT, HEADER_ALERT);
        }
        jQuery(HEADER_ALERT).show();
      } else {
        jQuery(MATCH_MODAL).show();
        try { jQuery(MATCH_MODAL).modal('show'); } catch (_) { /* no-op */ }
      }
    }
  );
}

/**
 * Initialize delegated handlers for match options links in header menus
 */
export function initializeMatchOptions() {
  jQuery(document)
    .off('click.racketmanager.matchOptions', '.matchOptionLink')
    .on('click.racketmanager.matchOptions', '.matchOptionLink', function (e) {
      const matchId = this.getAttribute('data-match-id') || jQuery(this).data('matchId');
      const optionKey = this.getAttribute('data-match-option') || jQuery(this).data('matchOption');
      return openMatchOptions(e, matchId, optionKey);
    });
}
