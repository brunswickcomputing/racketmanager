/**
 * Delegated event actions for RacketManager admin templates.
 *
 * @module admin-actions
 */

/**
 * Handle delegated click events.
 *
 * @param {Event} e
 */
function handleClick(e) {
  var target = e.target;
  var el = target && typeof target.closest === 'function' ? target.closest('[data-action]') : null;
  if (!el) return;

  var action = el.getAttribute('data-action') || (el.dataset ? el.dataset.action : null);
  if (!action) return;

  switch (action) {
    case 'check-all': {
      var form = null;
      var targetId = el.getAttribute('data-target') || (el.dataset ? el.dataset.target : null);
      if (targetId) {
        form = document.getElementById(targetId);
      }
      if (!form) {
        form = (typeof el.closest === 'function' ? el.closest('form') : null) || el.form;
      }
      if (form && window.Racketmanager && typeof window.Racketmanager.checkAll === 'function') {
        window.Racketmanager.checkAll(form);
      }
      break;
    }
    case 'notify-teams': {
      var matchId = el.getAttribute('data-match-id') || (el.dataset ? el.dataset.matchId : null);
      if (window.Racketmanager && typeof window.Racketmanager.notifyTeams === 'function') {
        window.Racketmanager.notifyTeams(matchId);
      }
      break;
    }
    case 'set-num-match-days': {
      // Only handle click for radio buttons, matching the original onclick attribute in season-edit.php
      if (el.type === 'radio' && window.Racketmanager && typeof window.Racketmanager.setNumMatchDays === 'function') {
        window.Racketmanager.setNumMatchDays();
      }
      break;
    }
  }
}

/**
 * Handle delegated change events.
 *
 * @param {Event} e
 */
function handleChange(e) {
  var target = e.target;
  var el = target && typeof target.closest === 'function' ? target.closest('[data-action]') : null;
  if (!el) return;

  var action = el.getAttribute('data-action') || (el.dataset ? el.dataset.action : null);
  if (!action) return;

  switch (action) {
    case 'set-end-date': {
      if (window.Racketmanager && typeof window.Racketmanager.setEndDate === 'function') {
        window.Racketmanager.setEndDate();
      }
      break;
    }
    case 'set-num-match-days': {
      // Only handle change for non-radio inputs (e.g. #max_teams number input),
      // preventing duplicate calls when a radio button is clicked (firing both click and change).
      if (el.type !== 'radio' && window.Racketmanager && typeof window.Racketmanager.setNumMatchDays === 'function') {
        window.Racketmanager.setNumMatchDays();
      }
      break;
    }
    case 'set-event-name': {
      if (window.Racketmanager && typeof window.Racketmanager.setEventName === 'function') {
        window.Racketmanager.setEventName();
      }
      break;
    }
    case 'set-shortcode': {
      if (window.Racketmanager && typeof window.Racketmanager.setShortcode === 'function') {
        window.Racketmanager.setShortcode();
      }
      break;
    }
    case 'set-tournament-open-date': {
      if (window.Racketmanager && typeof window.Racketmanager.setTournamentOpenDate === 'function') {
        window.Racketmanager.setTournamentOpenDate();
      }
      break;
    }
    case 'get-import-option': {
      if (window.Racketmanager && typeof window.Racketmanager.getImportOption === 'function') {
        window.Racketmanager.getImportOption(el.value);
      }
      break;
    }
    case 'get-event-dropdown': {
      if (window.Racketmanager && typeof window.Racketmanager.getEventDropdown === 'function') {
        window.Racketmanager.getEventDropdown(el.value);
      }
      break;
    }
    case 'set-match-date': {
      var idx = Number(el.getAttribute('data-index') || (el.dataset ? el.dataset.index : 0));
      var maxFixtures = Number(el.getAttribute('data-max-fixtures') || (el.dataset ? el.dataset.maxFixtures : 0));
      var mode = el.getAttribute('data-mode') || (el.dataset ? el.dataset.mode : '');
      if (window.Racketmanager && typeof window.Racketmanager.setMatchDate === 'function') {
        window.Racketmanager.setMatchDate(el.value, idx, maxFixtures, mode);
      }
      break;
    }
    case 'set-match-day-pop-up': {
      var popIdx = Number(el.getAttribute('data-index') || (el.dataset ? el.dataset.index : 0));
      var popMaxFixtures = Number(el.getAttribute('data-max-fixtures') || (el.dataset ? el.dataset.maxFixtures : 0));
      var popMode = el.getAttribute('data-mode') || (el.dataset ? el.dataset.mode : '');
      if (window.Racketmanager && typeof window.Racketmanager.setMatchDayPopUp === 'function') {
        window.Racketmanager.setMatchDayPopUp(el.value, popIdx, popMaxFixtures, popMode);
      }
      break;
    }
    case 'insert-home-stadium': {
      var stadiumIdx = Number(el.getAttribute('data-index') || (el.dataset ? el.dataset.index : 0));
      var homeTeamInput = document.getElementById('home_team_' + stadiumIdx);
      var teamId = homeTeamInput ? homeTeamInput.value : el.value;
      if (window.Racketmanager && typeof window.Racketmanager.insertHomeStadium === 'function') {
        window.Racketmanager.insertHomeStadium(teamId, stadiumIdx);
      }
      break;
    }
  }
}

/**
 * Handle delegated focusout events.
 *
 * @param {Event} e
 */
function handleFocusOut(e) {
  var target = e.target;
  var el = target && typeof target.closest === 'function' ? target.closest('[data-action]') : null;
  if (!el) return;

  var action = el.getAttribute('data-action') || (el.dataset ? el.dataset.action : null);
  if (action === 'save-add-points') {
    var tableId = el.getAttribute('data-table-id') || (el.dataset ? el.dataset.tableId : null);
    if (window.Racketmanager && typeof window.Racketmanager.saveAddPoints === 'function') {
      window.Racketmanager.saveAddPoints(el.value, tableId);
    }
  }
}

/**
 * Handle delegated load events (capturing phase).
 *
 * @param {Event} e
 */
function handleLoad(e) {
  var target = e.target;
  var el = (target && typeof target.closest === 'function') ? target.closest('[data-action="set-iframe-height"]') : null;
  if (!el && target && target.getAttribute && target.getAttribute('data-action') === 'set-iframe-height') {
    el = target;
  }
  if (el && el.id && typeof window.setIframeHeight === 'function') {
    window.setIframeHeight(el.id);
  }
}

/**
 * Register delegated event handlers for admin actions.
 *
 * @param {Document|HTMLElement} [root=document]
 */
export function registerAdminActions(root = document) {
  if (!root || typeof root.addEventListener !== 'function') return;
  if (root.__rmAdminActionsRegistered) return;
  root.__rmAdminActionsRegistered = true;

  root.addEventListener('click', handleClick);
  root.addEventListener('change', handleChange);
  root.addEventListener('focusout', handleFocusOut);
  root.addEventListener('load', handleLoad, true);
}
