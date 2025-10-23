/**
 * Update Match Results - Modularized (tidied)
 * Replaces legacy Racketmanager.updateMatchResults used on match input pages.
 * - Maintains legacy-parity UI updates
 * - Adds re-entrancy guard (busy flag) to prevent duplicate submissions
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

// Common selectors used across templates
const ALERT_ID_DEFAULT = '#matchAlert';
const ALERT_TEXT_DEFAULT = '#matchAlertResponse';
const INLINE_NOTIFY = '#updateResponse';
const SPLASH_DEFAULT = '#splash';

function parsePayload(payload) {
  // Supports legacy array and object responses
  let message = '';
  let homePoints = null;
  let awayPoints = null;
  let winner = null;
  let sets = null;

  if (Array.isArray(payload)) {
    message = payload[0];
    homePoints = payload[1];
    awayPoints = payload[2];
    winner = payload[3];
    sets = payload[4];
  } else if (payload && typeof payload === 'object') {
    message = payload.msg || payload.message || '';
    homePoints = payload.home_points ?? payload.home ?? null;
    awayPoints = payload.away_points ?? payload.away ?? null;
    winner = payload.winner ?? null;
    sets = payload.sets ?? null;
  } else {
    message = payload || '';
  }
  return { message, homePoints, awayPoints, winner, sets };
}

function renderSuccessUI({ message, homePoints, awayPoints, winner, sets }) {
  const $alert = jQuery(ALERT_ID_DEFAULT);
  const $alertText = jQuery(ALERT_TEXT_DEFAULT);

  // Use alert container if present (tournament/match pages)
  $alert.attr('role', $alert.attr('role') || 'alert');
  $alert.removeClass('alert--warning alert--danger').addClass('alert--success').show();
  $alertText.html(message);

  // Update points fields if present
  if (homePoints !== null && homePoints !== undefined) {
    jQuery('#home_points').val(homePoints);
  }
  if (awayPoints !== null && awayPoints !== undefined) {
    jQuery('#away_points').val(awayPoints);
  }

  // Mark match winner (singles context)
  if (winner) {
    const winnerField = `#match-status-${winner}`;
    jQuery(winnerField).addClass('winner');
  }

  // Update sets (supports [setNo, {player1,player2}] entries or object maps)
  if (sets) {
    // If sets is an array of entries [[setNo, teamScores], ...]
    if (Array.isArray(sets)) {
      for (const entry of sets) {
        const setNo = entry[0];
        const teamScores = entry[1] || {};
        Object.entries(teamScores).forEach(([teamKey, val]) => {
          const field = `#set_${setNo}_${teamKey}`;
          jQuery(field).val(val);
        });
      }
    } else if (typeof sets === 'object') {
      // If sets is an object map {setNo: {player1, player2}}
      Object.entries(sets).forEach(([setNo, teamScores]) => {
        Object.entries(teamScores || {}).forEach(([teamKey, val]) => {
          const field = `#set_${setNo}_${teamKey}`;
          jQuery(field).val(val);
        });
      });
    }
  }
}

/**
 * Perform the update match results request
 * Accepts the clicked element inside a form (button), where link.form is the form element
 * @param {HTMLElement} link
 */
export function updateMatchResults(link) {
  if (!link || !link.form || !link.form.id) return;

  const $btn = jQuery(link);
  if ($btn.data('busy') === true) return; // re-entrancy guard
  $btn.data('busy', true).prop('disabled', true).addClass('disabled').attr('aria-busy', 'true');

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_update_match';

  const alertField = ALERT_ID_DEFAULT;
  const alertTextField = ALERT_TEXT_DEFAULT;
  const splash = SPLASH_DEFAULT;

  // Pre-request cleanup (parity with legacy)
  jQuery(alertField).hide().removeClass('alert--success alert--warning alert--danger');
  jQuery('.is-invalid').removeClass('is-invalid');
  // Clear any existing winner indicators
  jQuery('.winner').val('').removeClass('winner');

  // Splash/body visibility (mirror legacy)
  jQuery(splash).removeClass('d-none').css('opacity', 1).show();
  jQuery('.match__body').hide();

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: $form,
    success: function (response) {
      const payload = (response && response.data !== undefined) ? response.data : null;
      const parsed = parsePayload(payload);
      renderSuccessUI(parsed);
    },
    error: function (response) {
      handleAjaxError(response, alertTextField, alertField);
      jQuery(alertField).show();
    },
    complete: function () {
      // Restore UI state (legacy parity)
      jQuery(splash).css('opacity', 0).hide();
      jQuery('.match__body').show();
      // Re-enable button
      try { $btn.prop('disabled', false).removeClass('disabled').data('busy', false).removeAttr('aria-busy'); } catch (_) { /* no-op */ }
    }
  });
}

/**
 * Initialize delegated handlers for Update Match Results buttons
 */
export function initializeUpdateMatchResults() {
  jQuery(document)
    .off('click.racketmanager.updateMatch', '[data-action="update-match-results"]')
    .on('click.racketmanager.updateMatch', '[data-action="update-match-results"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return updateMatchResults(this);
    })
    // Fallback in case data-action was missed but id is present
    .off('click.racketmanager.updateMatchFallback', '#updateMatchResults')
    .on('click.racketmanager.updateMatchFallback', '#updateMatchResults', function (e) {
      // Only trigger if no inline onclick attribute is present
      if (this.getAttribute('onclick')) return; // legacy template still active
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return updateMatchResults(this);
    });
}
