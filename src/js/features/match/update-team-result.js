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

function setPlayerInputValues(teamName, rubberNo, team = []) {
  team.forEach((player, p) => {
    const id = p + 1;
    jQuery(`#${teamName}player${id}_${rubberNo}`).val(player);
    jQuery(`#players_${rubberNo}_${teamName}_${id}`).val(player);
  });
}

/**
 * Populate player fields for a single rubber
 * @param {Object} players
 * @param {number} rubberNo
 */
function populateRubberPlayers(players, rubberNo) {
  for (const [teamName, team] of Object.entries(players || {})) {
    if (Array.isArray(team)) {
      setPlayerInputValues(teamName, rubberNo, team);
    }
  }
}

/**
 * Populate set scores for a single rubber
 * @param {Object} sets
 * @param {number} rubberNo
 */
function populateRubberSets(sets, rubberNo) {
  for (const [setName, team] of Object.entries(sets || {})) {
    for (const [playerNo, score] of Object.entries(team || {})) {
      jQuery(`#set_${rubberNo}_${setName}_${playerNo}`).val(score);
    }
  }
}

/**
 * Populate rubber winners, players, and set scores
 * @param {Object} rubbers
 */
function populateRubberResults(rubbers) {
  let rubberNo = 1;
  for (const [, rubber] of Object.entries(rubbers || {})) {
    if (!rubber) {
      rubberNo++;
      continue;
    }
    const winner = rubber.winner;
    const winnerField = `#match-status-${rubberNo}-${winner}`;
    jQuery(winnerField).addClass('winner').val('W');

    populateRubberPlayers(rubber.players, rubberNo);
    populateRubberSets(rubber.sets, rubberNo);
    rubberNo++;
  }
}

/**
 * Apply field-level validation warnings
 * @param {Object|null} warnings
 */
function applyFieldWarnings(warnings) {
  if (!warnings) {
    return;
  }
  for (const [w, warningMsg] of Object.entries(warnings)) {
    const playerRef = `#${w}`;
    jQuery(playerRef).addClass('is-invalid');
    jQuery(`${playerRef}Feedback`).html(warningMsg);
  }
}

/**
 * Apply field-level validation errors
 * @param {Array<string>} [errFields=[]]
 * @param {Array<string>} [errMsgs=[]]
 */
function applyFieldErrors(errFields = [], errMsgs = []) {
  errFields.forEach((field, index) => {
    const $field = jQuery(`#${field}`);
    $field.addClass('is-invalid');
    const $feedback = jQuery(`#${field}Feedback`);
    if ($feedback.length && errMsgs[index]) {
      $feedback.html(errMsgs[index]);
    }
  });
}

/**
 * Update team (rubber) results
 * @param {HTMLElement} link - clicked button inside a form
 */
export function updateTeamResult(link) {
  if (!link?.form?.id) {
    return;
  }

  // Guard against double-trigger: ignore if already processing
  const $btn = jQuery(link);
  if ($btn.data('busy') === true) {
    return;
  }
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

      populateRubberResults(rubbers);
      applyFieldWarnings(warnings);
      applyFieldErrors(data.err_flds || [], data.err_msgs || []);

      // Refresh header
      try {
        matchHeader(matchId, match_edit);
      } catch (e) {
        console.error(e);
      }
    },
    error: function (response) {
      handleAjaxError(response, ALERT_TEXT, ALERT_FIELD);
    },
    complete: function () {
      jQuery(ALERT_FIELD).show();
      jQuery(SPLASH).css('opacity', 0).hide();
      jQuery(RUBBERS_CONTAINER).show();
      // Re-enable the clicked button and clear busy flag
      try {
        $btn.prop('disabled', false).removeClass('disabled').data('busy', false);
      } catch (e) {
        console.error(e);
      }
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
      if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
      }
      return updateTeamResult(this);
    });
}
