/**
 * Teams - Team Ordering Feature (Phase 7)
 * Modularized implementation of legacy Racketmanager.showTeamOrderPlayers and Racketmanager.validateTeamOrder
 * using delegated event handlers and centralized utilities.
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { resetMatchScoresByFormId } from '../match/reset-match-scores.js';

const SELECT_CLUB = '#club_id';
const SELECT_EVENT = '#event_id';
const TEAM_ORDER_CONTAINER = '#team-order-details';
const TEAM_ORDER_RUBBERS = '#team-order-rubbers';
const ALERT_FIELD = '#teamOrderAlert';
const ALERT_TEXT = '#teamOrderAlertResponse';

/**
 * Load the team order players list after club/event selection
 */
export function showTeamOrderPlayers(event) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }

  jQuery(ALERT_FIELD).hide();

  const eventId = jQuery(SELECT_EVENT).val();
  const clubId = jQuery(SELECT_CLUB).val();

  if (!clubId || !eventId) {
    return; // need both to proceed
  }

  jQuery(TEAM_ORDER_RUBBERS).hide();
  jQuery(TEAM_ORDER_CONTAINER).addClass('is-loading');

  jQuery(TEAM_ORDER_RUBBERS).val('');
  jQuery(TEAM_ORDER_RUBBERS).load(
    getAjaxUrl(),
    {
      eventId: eventId,
      clubId: clubId,
      action: 'racketmanager_show_team_order_players',
      security: getAjaxNonce(),
    },
    function (_response, status, xhr) {
      jQuery(TEAM_ORDER_RUBBERS).show();
      jQuery(TEAM_ORDER_CONTAINER).removeClass('is-loading');
      if (status === 'error') {
        handleAjaxError(xhr, ALERT_TEXT, ALERT_FIELD);
        jQuery(ALERT_FIELD).show();
      }
      // else: HTML injected contains the form and buttons; delegated handlers will cover them
    }
  );
}

/**
 * Validate (and optionally set) team order
 * @param {HTMLElement} link - clicked button inside team order form
 * @param {string} setTeam - optional flag indicating set operation
 */
export function validateTeamOrder(link, setTeam = '') {
  if (!link || !link.form || !link.form.id) return;

  jQuery(TEAM_ORDER_CONTAINER).addClass('is-loading');
  jQuery(TEAM_ORDER_RUBBERS).hide();
  jQuery('.winner').removeClass('winner');
  jQuery('.loser').removeClass('loser');
  jQuery('.is-invalid').removeClass('is-invalid');

  jQuery(ALERT_FIELD).hide();
  jQuery(ALERT_FIELD).removeClass('alert--success alert--warning alert--danger');
  jQuery(ALERT_TEXT).html('');

  const formId = `#${link.form.id}`;
  let form = jQuery(formId).serialize();
  form += '&action=racketmanager_validate_team_order';
  form += '&security=' + getAjaxNonce();
  form += '&setTeam=' + (setTeam || '');

  jQuery.ajax({
    type: 'POST',
    datatype: 'json',
    url: getAjaxUrl(),
    async: false,
    data: form,
    success: function (response) {
      const data = response && response.data ? response.data : {};
      const updatedRubbers = data.rubbers || {};
      let rubberNo = 1;
      for (let r in updatedRubbers) {
        if (!Object.prototype.hasOwnProperty.call(updatedRubbers, r)) continue;
        const rubber = updatedRubbers[r];
        const status = rubber['status'];
        const statusClass = rubber['status_class'];
        let formField = '#match-status-' + rubberNo;
        jQuery(formField).addClass(statusClass);
        jQuery(formField).val(status);
        formField = '#wtn_' + rubberNo;
        jQuery(formField).addClass(statusClass);
        jQuery(formField).val(rubber['wtn']);
        rubberNo++;
      }
      const msg = data.msg || '';
      jQuery(ALERT_TEXT).html(msg);
      const valid = !!data.valid;
      const alertClass = valid ? 'alert--success' : 'alert--danger';
      jQuery(ALERT_FIELD).addClass(alertClass).show();
    },
    error: function (response) {
      handleAjaxError(response, ALERT_TEXT, ALERT_FIELD);
      jQuery(ALERT_FIELD).show();
    },
    complete: function () {
      jQuery(TEAM_ORDER_RUBBERS).show();
      jQuery(TEAM_ORDER_CONTAINER).removeClass('is-loading');
    }
  });
}

/**
 * Initialize the Team Order feature with delegated handlers
 */
export function initializeTeamOrder() {
  // Selections change triggers load
  jQuery(document)
    .off('change.racketmanager.teamOrder', `${SELECT_CLUB}, ${SELECT_EVENT}`)
    .on('change.racketmanager.teamOrder', `${SELECT_CLUB}, ${SELECT_EVENT}`, function (e) {
      return showTeamOrderPlayers(e);
    });

  // Delegated click handlers for actions inside loaded content
  jQuery(document)
    .off('click.racketmanager.teamOrder', '#resetMatchScore')
    .on('click.racketmanager.teamOrder', '#resetMatchScore', function (e) {
      e.preventDefault();
      // Prefer modular reset; fall back to legacy if needed
      if (typeof resetMatchScoresByFormId === 'function') {
        return resetMatchScoresByFormId('match');
      }
      if (globalThis.Racketmanager && typeof globalThis.Racketmanager.resetMatchScores === 'function') {
        return globalThis.Racketmanager.resetMatchScores(e, 'match');
      }
    });

  jQuery(document)
    .off('click.racketmanager.teamOrder', '#setTeamButton')
    .on('click.racketmanager.teamOrder', '#setTeamButton', function (e) {
      e.preventDefault();
      const setTeam = this.dataset.setTeam || '';
      return validateTeamOrder(this, setTeam);
    });

  jQuery(document)
    .off('click.racketmanager.teamOrder', '#validateTeamButton')
    .on('click.racketmanager.teamOrder', '#validateTeamButton', function (e) {
      e.preventDefault();
      return validateTeamOrder(this);
    });
}
