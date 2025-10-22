/**
 * Match Header Refresh - Modularized
 * Provides a helper to refresh the match header HTML via AJAX.
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

const MATCH_HEADER_CONTAINER = '#match-header';

/**
 * Refresh the match header markup from server
 * @param {number|string} matchId
 * @param {boolean} editMode
 */
export function matchHeader(matchId, editMode = false) {
  const notifyField = MATCH_HEADER_CONTAINER;
  jQuery(notifyField).val('');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: {
      match_id: matchId,
      edit_mode: editMode,
      action: 'racketmanager_update_match_header',
      security: getAjaxNonce(),
    },
    success: function (response) {
      jQuery(notifyField).empty();
      jQuery(notifyField).html(response?.data || '');
    },
    error: function (response) {
      // Render error directly in header container for parity
      const errorField = '#headerResponse';
      const errorText = '#headerResponseText';
      handleAjaxError(response, errorText, errorField);
      jQuery(errorField).show();
    },
    complete: function () {
      jQuery(notifyField).show();
    }
  });
}

export function initializeMatchHeader() {
  // No delegated bindings required at present.
}
