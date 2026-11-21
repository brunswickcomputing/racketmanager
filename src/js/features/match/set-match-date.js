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
 * Render success alerts for match date update
 * @param {Object} data
 * @param {string} alert1
 * @param {string} alert1Text
 */
function renderMatchDateAlerts(data, alert1, alert1Text) {
  const message = data.msg;

  jQuery(ALERT_DATE).show().addClass('alert--success');
  const alertDateText = document.querySelector(ALERT_DATE_TEXT);
  if (alertDateText) {
    alertDateText.innerHTML = message;
  }
  jQuery(alert1).show().addClass('alert--success');
  jQuery(alert1Text).html(message);
}

function updateTournamentDateUI(matchDate) {
  const tournamentDateHeader = document.getElementById('match-tournament-date-header');
  if (tournamentDateHeader) {
    tournamentDateHeader.innerHTML = matchDate;
  }
}

function updateLeagueMatchDateUI(data, link, matchId) {
  const modalEl = link?.closest('.modal') || (data.modal ? document.getElementById(data.modal) : null) || document.getElementById('matchModal');
  if (modalEl) {
    jQuery(modalEl).off('hidden.bs.modal.setMatchDate').one('hidden.bs.modal.setMatchDate', function () {
      matchHeader(matchId);
    });
    return;
  }
  matchHeader(matchId);
}

/**
 * Update match date in UI headers or defer match header update on modal hide
 * @param {Object} data
 * @param {boolean} isTournament
 * @param {HTMLElement} link
 * @param {string|number} matchId
 */
function updateMatchDateUI(data, isTournament, link, matchId) {
  const matchDate = data.schedule_date;
  if (!matchDate) {
    return;
  }

  if (isTournament) {
    updateTournamentDateUI(matchDate);
    return;
  }

  updateLeagueMatchDateUI(data, link, matchId);
}

/**
 * Submit match date form
 * @param {HTMLElement} link - Button inside the form
 * @param {boolean} isTournament - indicates tournament page variant
 */
export function setMatchDate(link, isTournament = false) {
  if (!link?.form?.id) {
    return;
  }

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_set_fixture_date';

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
      const matchId = data.match_id;

      renderMatchDateAlerts(data, alert1, alert1Text);
      updateMatchDateUI(data, isTournament, link, matchId);
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
      if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
      }
      const isTournament = this.dataset?.isTournament === 'true';
      return setMatchDate(this, isTournament);
    });
}
