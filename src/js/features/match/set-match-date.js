/**
 * Set Match Date - Modularized
 * Mirrors legacy Racketmanager.setMatchDate with centralized config and error handling.
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { matchHeader } from './match-header.js';

const ALERT_MATCH = '#matchAlert';
const ALERT_MATCH_TEXT = '#matchAlertResponse';
const ALERT_DATE = '#matchDateAlert';
const ALERT_DATE_TEXT = '#alertMatchDateResponse';
const UPDATE_STATUS_RESPONSE = '#updateStatusResponse';

/**
 * Submit match date form
 * @param {HTMLElement} link - Button inside the form
 * @param {boolean} isTournament - indicates tournament page variant
 */
export function setMatchDate(link, isTournament = false) {
  if (!link || !link.form || !link.form.id) return;

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_set_match_date';

  // Reset alerts
  const alert1 = isTournament ? ALERT_MATCH : '#matchOptionsAlert';
  const alert1Text = isTournament ? ALERT_MATCH_TEXT : '#alertMatchOptionsResponse';
  jQuery(alert1).hide().removeClass('alert--success alert--warning alert--danger');
  jQuery(ALERT_DATE).hide().removeClass('alert--success alert--warning alert--danger');
  jQuery('.is-invalid').removeClass('is-invalid');
  jQuery(UPDATE_STATUS_RESPONSE).val('').hide();

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: $form,
    success: function (response) {
      const data = response?.data || {};
      const message = data.msg;
      const modalId = `#${data.modal}`;
      const matchId = data.match_id;
      const matchDate = data.schedule_date;

      jQuery(alert1).show().addClass('alert--success');
      jQuery(alert1Text).html(message);
      try { jQuery(modalId).modal('hide'); } catch (_) { /* no-op */ }

      if (matchDate) {
        if (isTournament) {
          jQuery('#match-tournament-date-header').html(matchDate);
        } else {
          matchHeader(matchId);
        }
      }
    },
    error: function (response) {
      handleAjaxError(response, ALERT_DATE_TEXT, ALERT_DATE);
      jQuery(ALERT_DATE).show();
    },
  });
}

export function initializeSetMatchDate() {
  jQuery(document)
    .off('click.racketmanager.setMatchDate', '[data-action="set-match-date"]')
    .on('click.racketmanager.setMatchDate', '[data-action="set-match-date"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      const isTournament = this.getAttribute('data-is-tournament') === 'true' || jQuery(this).data('isTournament') === true;
      return setMatchDate(this, isTournament);
    });
}
