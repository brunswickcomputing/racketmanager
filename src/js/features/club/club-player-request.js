/**
 * Club Player Request - Modularized
 * Replaces legacy Racketmanager.club_player_request
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

const FORM_ID = '#playerRequestFrm';
const ALERT_FIELD = '#playerAddResponse';
const ALERT_TEXT = '#playerAddResponseText';

export function clubPlayerRequest(link) {
  // link is the clicked button (optional); use static form id
  const formEl = document.querySelector(FORM_ID);
  if (!formEl) return;

  const formId = `#${formEl.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_club_player_request';

  // Prep UI
  jQuery(ALERT_FIELD).removeClass('alert--success alert--warning alert--danger').hide();
  jQuery(ALERT_TEXT).html('');
  jQuery('.is-invalid').removeClass('is-invalid');
  jQuery('.invalid-feedback').html('');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    async: false,
    data: $form,
    success: function (response) {
      const data = response && response.data ? response.data : {};
      // Server returns HTML via show_alert in many cases; support string as well
      const msg = typeof data === 'string' ? data : (data.msg || '');
      const status = data.status || 'success';
      jQuery(ALERT_FIELD).addClass(`alert--${status}`);
      jQuery(ALERT_TEXT).html(msg);
    },
    error: function (response) {
      handleAjaxError(response, ALERT_TEXT, ALERT_FIELD);
    },
    complete: function () {
      jQuery(ALERT_FIELD).show();
    }
  });
}

export function initializeClubPlayerRequest() {
  jQuery(document)
    .off('click.racketmanager.clubPlayerRequest', '[data-action="club-player-request"], #clubPlayerUpdateSubmit')
    .on('click.racketmanager.clubPlayerRequest', '[data-action="club-player-request"], #clubPlayerUpdateSubmit', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      // Ignore if legacy inline onclick exists (should be removed, but guard)
      if (this.getAttribute && this.getAttribute('onclick')) return;
      return clubPlayerRequest(this);
    });
}
