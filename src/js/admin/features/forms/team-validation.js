/**
 * Form validation for admin team forms (#teamPlayerFrm).
 *
 * @module admin/features/forms/team-validation
 */

import { getAjaxUrl, getAjaxNonce } from '../../../config/ajax-config.js';

/**
 * Validate the team player form before submission.
 *
 * @param {Event} [event]
 * @returns {boolean}
 */
export function validateTeamPlayerForm(event) {
    let error = false;
    let msg = '';
    const teamEl = document.getElementById('team');
    const teamIdEl = document.getElementById('team_id');
    const teamVal = teamEl ? teamEl.value : '';
    const teamIdVal = teamIdEl ? teamIdEl.value : '';

    if (teamVal === '') {
        error = true;
        msg += 'Team name not set\n';
    } else if (teamIdVal === '') {
        const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
        if (jq && typeof jq.ajax === 'function') {
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
                    if (response && response.data) {
                        error = true;
                        msg += 'Team already exists\n';
                    }
                },
                error: function () {
                    error = true;
                    msg += 'Error with team name check\n';
                }
            });
        }
    }

    const teamPlayer1El = document.getElementById('teamPlayer1');
    if (!teamPlayer1El || teamPlayer1El.value === '') {
        error = true;
        msg += 'Player 1 not set\n';
    }

    const teamPlayerId2El = document.getElementById('teamPlayerId2');
    if (teamPlayerId2El && teamPlayerId2El.value === '') {
        error = true;
        msg += 'Player 2 not set\n';
    }

    const clubIdEl = document.getElementById('clubId');
    const clubIdVal = clubIdEl ? clubIdEl.value : null;
    if (clubIdVal === '0' || clubIdVal === null || clubIdVal === '') {
        error = true;
        msg += 'Club not set\n';
    }

    if (error) {
        const errorMsgEl = document.getElementById('errorMsg');
        if (errorMsgEl) {
            errorMsgEl.style.display = '';
            errorMsgEl.textContent = msg;
        }
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
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
    if (!root || typeof root.querySelector !== 'function') return;
    const form = root.querySelector('#teamPlayerFrm');
    if (form) {
        form.addEventListener('submit', validateTeamPlayerForm);
    }
}
