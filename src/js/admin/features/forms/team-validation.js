/**
 * Form validation for admin team forms (#teamPlayerFrm).
 *
 * @module admin/features/forms/team-validation
 */

import { getAjaxUrl, getAjaxNonce } from '../../../config/ajax-config.js';

function requestTeamDuplicateCheck(teamVal) {
    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
    if (!jq || typeof jq.ajax !== 'function') {
        return '';
    }
    let ajaxMsg = '';
    jq.ajax({
        type: 'POST',
        datatype: 'json',
        url: getAjaxUrl(),
        async: false,
        data: {
            name: teamVal,
            action: 'racketmanager_check_team_exists',
            security: getAjaxNonce(),
        },
        success: function (response) {
            if (response?.data) {
                ajaxMsg = 'Team already exists\n';
            }
        },
        error: function () {
            ajaxMsg = 'Error with team name check\n';
        }
    });
    return ajaxMsg;
}

/**
 * Check if team name is set or duplicate via AJAX.
 *
 * @param {string} teamVal
 * @param {string} teamIdVal
 * @returns {string} Error message or empty string
 */
export function checkTeamNameExists(teamVal, teamIdVal) {
    if (teamVal === '') {
        return 'Team name not set\n';
    }
    if (teamIdVal === '') {
        return requestTeamDuplicateCheck(teamVal);
    }
    return '';
}

/**
 * Validate required and optional team players.
 *
 * @param {string|null|undefined} p1Val
 * @param {string|null|undefined} p2Val
 * @returns {string} Error message or empty string
 */
export function checkRequiredPlayers(p1Val, p2Val) {
    let msg = '';
    if (!p1Val) {
        msg += 'Player 1 not set\n';
    }
    if (p2Val === '') {
        msg += 'Player 2 not set\n';
    }
    return msg;
}

/**
 * Validate that a club is selected.
 *
 * @param {string|null|undefined} clubIdVal
 * @returns {string} Error message or empty string
 */
export function checkClubSelected(clubIdVal) {
    if (clubIdVal === '0' || clubIdVal === null || clubIdVal === '' || clubIdVal === undefined) {
        return 'Club not set\n';
    }
    return '';
}

/**
 * Display validation error message and prevent default event.
 *
 * @param {string} msg
 * @param {Event} [event]
 */
export function displayValidationErrorMessage(msg, event) {
    const errorMsgEl = document.getElementById('errorMsg');
    if (errorMsgEl) {
        errorMsgEl.style.display = '';
        errorMsgEl.textContent = msg;
    }
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }
}

/**
 * Validate the team player form before submission.
 *
 * @param {Event} [event]
 * @returns {boolean}
 */
export function validateTeamPlayerForm(event) {
    const teamEl = document.getElementById('team');
    const teamIdEl = document.getElementById('team_id');
    const teamVal = teamEl ? teamEl.value : '';
    const teamIdVal = teamIdEl ? teamIdEl.value : '';

    let msg = checkTeamNameExists(teamVal, teamIdVal);

    const teamPlayer1El = document.getElementById('teamPlayer1');
    const teamPlayerId2El = document.getElementById('teamPlayerId2');
    const p1Val = teamPlayer1El ? teamPlayer1El.value : '';
    const p2Val = teamPlayerId2El ? teamPlayerId2El.value : null;
    msg += checkRequiredPlayers(p1Val, p2Val);

    const clubIdEl = document.getElementById('clubId');
    const clubIdVal = clubIdEl ? clubIdEl.value : null;
    msg += checkClubSelected(clubIdVal);

    if (msg !== '') {
        displayValidationErrorMessage(msg, event);
        return false;
    }
    return true;
}

/**
 * Attach validation submit listener to #teamPlayerFrm.
 *
 * @param {Document|HTMLElement} [root=document]
 */
export function initTeamValidation(root = document) {
    if (!root || typeof root.querySelector !== 'function') {
        return;
    }
    const form = root.querySelector('#teamPlayerFrm');
    if (form) {
        form.addEventListener('submit', validateTeamPlayerForm);
    }
}
