/**
 * Delegated event actions for RacketManager admin templates.
 *
 * @module admin-actions
 */

import { getDataAttr } from '../utils/dom-utils.js';

/**
 * Invoke a method on window.Racketmanager if available.
 *
 * @param {string} method
 * @param {...*} args
 */
export function invokeRacketmanagerMethod(method, ...args) {
  if (typeof window !== 'undefined' && window.Racketmanager && typeof window.Racketmanager[method] === 'function') {
    window.Racketmanager[method](...args);
  }
}

export function handleCheckAllAction(el) {
  var form = null;
  var targetId = el.dataset?.target || null;
  if (targetId) {
    form = document.getElementById(targetId);
  }
  if (!form) {
    form = (typeof el.closest === 'function' ? el.closest('form') : null) || el.form;
  }
  if (form) {
    invokeRacketmanagerMethod('checkAll', form);
  }
}

export function handleNotifyTeamsAction(el) {
  var matchId = el.dataset?.matchId ?? null;
  invokeRacketmanagerMethod('notifyTeams', matchId);
}

export function handleSetNumMatchDaysClickAction(el) {
  // Only handle click for radio buttons, matching the original onclick attribute in season-edit.php
  if (el.type === 'radio') {
    invokeRacketmanagerMethod('setNumMatchDays');
  }
}

const CLICK_ACTION_MAP = Object.freeze(Object.assign(Object.create(null), {
  'check-all': handleCheckAllAction,
  'notify-teams': handleNotifyTeamsAction,
  'set-num-match-days': handleSetNumMatchDaysClickAction,
}));

/**
 * Handle delegated click events.
 *
 * @param {Event} e
 */
function handleClick(e) {
  var target = e.target;
  var el = target && typeof target.closest === 'function' ? target.closest('[data-action]') : null;
  if (!el) {
    return;
  }

  var action = getDataAttr(el, 'action');
  if (!action) {
    return;
  }

  var handler = CLICK_ACTION_MAP[action];
  if (handler) {
    handler(el);
  }
}

export function handleSetMatchDateAction(el) {
  var idx = Number(el.dataset?.index ?? 0);
  var maxFixtures = Number(el.dataset?.maxFixtures ?? 0);
  var mode = el.dataset?.mode || '';
  invokeRacketmanagerMethod('setMatchDate', el.value, idx, maxFixtures, mode);
}

export function handleSetMatchDayPopUpAction(el) {
  var popIdx = Number(el.dataset?.index ?? 0);
  var popMaxFixtures = Number(el.dataset?.maxFixtures ?? 0);
  var popMode = el.dataset?.mode || '';
  invokeRacketmanagerMethod('setMatchDayPopUp', el.value, popIdx, popMaxFixtures, popMode);
}

export function handleInsertHomeStadiumAction(el) {
  var stadiumIdx = Number(el.dataset?.index ?? 0);
  var homeTeamInput = document.getElementById('home_team_' + stadiumIdx);
  var teamId = homeTeamInput ? homeTeamInput.value : el.value;
  invokeRacketmanagerMethod('insertHomeStadium', teamId, stadiumIdx);
}

export function handleSetNumMatchDaysChangeAction(el) {
  // Only handle change for non-radio inputs (e.g. #max_teams number input),
  // preventing duplicate calls when a radio button is clicked (firing both click and change).
  if (el.type !== 'radio') {
    invokeRacketmanagerMethod('setNumMatchDays');
  }
}

const CHANGE_ZERO_ARG_ACTIONS = Object.freeze(Object.assign(Object.create(null), {
  'set-end-date': 'setEndDate',
  'set-event-name': 'setEventName',
  'set-shortcode': 'setShortcode',
  'set-tournament-open-date': 'setTournamentOpenDate',
}));

const CHANGE_ACTION_HANDLERS = Object.freeze(Object.assign(Object.create(null), {
  'set-num-match-days': handleSetNumMatchDaysChangeAction,
  'get-import-option': function (el) {
    invokeRacketmanagerMethod('getImportOption', el.value);
  },
  'set-match-date': handleSetMatchDateAction,
  'set-match-day-pop-up': handleSetMatchDayPopUpAction,
  'insert-home-stadium': handleInsertHomeStadiumAction,
}));

/**
 * Handle delegated change events.
 *
 * @param {Event} e
 */
function handleChange(e) {
  var target = e.target;
  var el = target && typeof target.closest === 'function' ? target.closest('[data-action]') : null;
  if (!el) {
    return;
  }

  var action = getDataAttr(el, 'action');
  if (!action) {
    return;
  }

  var zeroArgMethod = CHANGE_ZERO_ARG_ACTIONS[action];
  if (zeroArgMethod) {
    invokeRacketmanagerMethod(zeroArgMethod);
    return;
  }

  var handler = CHANGE_ACTION_HANDLERS[action];
  if (handler) {
    handler(el);
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

  var action = getDataAttr(el, 'action');
  if (action === 'save-add-points') {
    var tableId = el.dataset?.tableId ?? null;
    if (window.Racketmanager && typeof window.Racketmanager.saveAddPoints === 'function') {
      window.Racketmanager.saveAddPoints(el.value, tableId);
    }
  }
}

function getDocHeight(doc) {
  doc = doc || document;
  var body = doc.body;
  var html = doc.documentElement;
  return Math.max(
    body ? body.scrollHeight : 0,
    body ? body.offsetHeight : 0,
    html ? html.clientHeight : 0,
    html ? html.scrollHeight : 0,
    html ? html.offsetHeight : 0
  );
}

/**
 * Adjust iframe height to fit its content.
 *
 * @param {string|HTMLIFrameElement} id
 */
export function setIframeHeight(id) {
  var ifrm = typeof id === 'string' ? document.getElementById(id) : id;
  if (!ifrm) return;
  const doc = ifrm.contentDocument || ifrm.contentWindow?.document || null;
  if (!doc) return;
  ifrm.style.visibility = 'hidden';
  ifrm.style.height = '10px';
  ifrm.style.height = getDocHeight(doc) + 4 + 'px';
  ifrm.style.visibility = 'visible';
}

/**
 * Handle delegated load events (capturing phase).
 *
 * @param {Event} e
 */
function handleLoad(e) {
  var target = e.target;
  var el = (target && typeof target.closest === 'function') ? target.closest('[data-action="set-iframe-height"]') : null;
  if (!el && getDataAttr(target, 'action') === 'set-iframe-height') {
    el = target;
  }
  if (el) {
    setIframeHeight(el);
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
