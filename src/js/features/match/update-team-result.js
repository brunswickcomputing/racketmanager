/**
 * Match/Teams - Update Team Result (Rubbers)
 * Modular implementation that updates team rubber results.
 * Moved from src/js/features/teams/update-team-result.js to this match directory.
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { matchHeader } from './match-header.js';

const ALERT_FIELD = '#matchAlert';
const ALERT_TEXT = '#matchAlertResponse';
const SPLASH = '#splash';
const RUBBERS_CONTAINER = '#showMatchRubbers';
const UPDATE_BUTTON = '#updateRubberResults';

/**
 * Update team (rubber) results
 * @param {HTMLElement} link - clicked button inside a form
 */
export function updateTeamResult(link) {
  if (!link || !link.form || !link.form.id) return;

  // Guard against double-trigger: ignore if already processing
  const $btn = jQuery(link);
  if ($btn.data('busy') === true) return;
  $btn.data('busy', true).prop('disabled', true).addClass('disabled');

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_update_rubbers';

  // Context
  const matchId = jQuery('#current_match_id').val();
  const match_edit = jQuery('#matchStatusButton').length !== 0;

  // Prep UI
  jQuery(ALERT_FIELD).hide().removeClass('alert--success alert--warning alert--danger');
  jQuery('.is-invalid').removeClass('is-invalid');
  jQuery(SPLASH).removeClass('d-none').css('opacity', 1).show();
  jQuery(RUBBERS_CONTAINER).hide();

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: $form,
    success: function (response) {
      const data = response?.data || {};
      const message = data.msg || '';
      const status = data.status || 'success';
      const rubbers = data.rubbers || {};
      const warnings = data.warnings || null;

      // Apply alert
      jQuery(ALERT_FIELD).addClass(`alert--${status}`);
      jQuery(ALERT_TEXT).html(message);

      // Populate winners/players/sets
      let rubberNo = 1;
      for (const r in rubbers) {
        if (!Object.prototype.hasOwnProperty.call(rubbers, r)) continue;
        const rubber = rubbers[r];
        const winner = rubber['winner'];
        const winnerField = `#match-status-${rubberNo}-${winner}`;
        jQuery(winnerField).addClass('winner').val('W');

        // Players
        const players = rubber['players'] || {};
        for (const t in players) { // home or away
          if (!Object.prototype.hasOwnProperty.call(players, t)) continue;
          const team = players[t];
          for (let p = 0; p < team.length; p++) {
            const player = team[p];
            const id = p + 1;
            let field = `#${t}player${id}_${rubberNo}`;
            jQuery(field).val(player);
            field = `#players_${rubberNo}_${t}_${id}`;
            jQuery(field).val(player);
          }
        }
        // Sets
        const sets = rubber['sets'] || {};
        for (const s in sets) {
          if (!Object.prototype.hasOwnProperty.call(sets, s)) continue;
          const team = sets[s];
          for (const p in team) {
            if (!Object.prototype.hasOwnProperty.call(team, p)) continue;
            const score = team[p];
            const field = `#set_${rubberNo}_${s}_${p}`;
            jQuery(field).val(score);
          }
        }
        rubberNo++;
      }

      // Field-level warnings
      if (warnings) {
        for (const w in warnings) {
          if (!Object.prototype.hasOwnProperty.call(warnings, w)) continue;
          const playerRef = `#${w}`;
          jQuery(playerRef).addClass('is-invalid');
          const feedback = `${playerRef}Feedback`;
          jQuery(feedback).html(warnings[w]);
        }
      }

      // Refresh header
      try { matchHeader(matchId, match_edit); } catch (_) { /* no-op */ }
    },
    error: function (response) {
      handleAjaxError(response, ALERT_TEXT, ALERT_FIELD);
    },
    complete: function () {
      jQuery(ALERT_FIELD).show();
      jQuery(SPLASH).css('opacity', 0).hide();
      jQuery(RUBBERS_CONTAINER).show();
      // Re-enable the clicked button and clear busy flag
      try { $btn.prop('disabled', false).removeClass('disabled').data('busy', false); } catch (_) { /* no-op */ }
    }
  });
}

/**
 * Initialize delegated handlers for team result updates
 * Supports both the new data-action and the legacy #updateRubberResults id.
 */
export function initializeTeamMatchResult() {
  jQuery(document)
    .off('click.racketmanager.updateTeamResult', '[data-action="update-team-result"]')
    .on('click.racketmanager.updateTeamResult', '[data-action="update-team-result"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return updateTeamResult(this);
    })
}
