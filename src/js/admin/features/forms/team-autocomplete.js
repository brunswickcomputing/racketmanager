/**
 * Player and captain autocomplete handlers for admin team forms.
 *
 * @module admin/features/forms/team-autocomplete
 */

import { safeAutocomplete } from '../../../features/autocomplete/autocomplete-helpers.js';
import { getAjaxUrl, getAjaxNonce } from '../../../config/ajax-config.js';

/**
 * Fetch player details via synchronous AJAX request for autocomplete dropdowns.
 *
 * @param {string} name
 * @param {string|number|null} [club=null]
 * @returns {Array|null}
 */
export function getPlayerDetails(name, club = null) {
    let response = null;
    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
    if (jq && typeof jq.ajax === 'function') {
        jq.ajax({
            type: 'POST',
            datatype: 'json',
            url: getAjaxUrl(),
            async: false,
            data: {
                type: 'name',
                name: name,
                club: club,
                security: getAjaxNonce(),
                action: 'racketmanager_get_player_details',
            },
            success: function (data) {
                try {
                    response = typeof data?.data === 'string' ? JSON.parse(data.data) : data?.data;
                } catch {
                    response = data?.data;
                }
            },
            error: function () {
                if (typeof alert === 'function') {
                    alert('Ajax error on getting player');
                }
            }
        });
    }
    return response;
}

function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) {
        el.value = val !== undefined && val !== null ? val : '';
    }
}

/**
 * Initialize autocompletes on team players, captain, and match secretary inputs if present.
 */
export function initTeamAutocomplete() {
    if (typeof document === 'undefined') return;

    if (document.querySelector('#teamPlayer1')) {
        safeAutocomplete('#teamPlayer1', {
            minLength: 2,
            source: function (request, response) {
                response(getPlayerDetails(request.term));
            },
            select: function (event, ui) {
                setVal('teamPlayerId1', ui.item.playerId);
                setVal('clubId', ui.item.club_id);
                setVal('captain', ui.item.value);
                setVal('captainId', ui.item.playerId);
                setVal('contactno', ui.item.contactno);
                setVal('contactemail', ui.item.user_email);
            },
            change: function (event, ui) {
                if (ui.item === null) {
                    if (event.target) event.target.value = '';
                    setVal('teamPlayerId1', '');
                    setVal('clubId', '');
                    setVal('captain', '');
                    setVal('captainId', '');
                    setVal('contactno', '');
                    setVal('contactemail', '');
                    setVal('team', '');
                } else {
                    setVal('teamPlayerId1', ui.item.playerId);
                    setVal('captain', ui.item.value);
                    setVal('captainId', ui.item.playerId);
                    setVal('clubId', ui.item.club_id);
                    setVal('contactno', ui.item.contactno);
                    setVal('contactemail', ui.item.user_email);
                    const team1 = document.getElementById('teamPlayer1')?.value || '';
                    const teamPlayer2Val = document.getElementById('teamPlayer2')?.value || '';
                    const team = teamPlayer2Val ? (team1 + ' / ' + teamPlayer2Val) : team1;
                    setVal('team', team);
                }
            }
        });
    }

    if (document.querySelector('#teamPlayer2')) {
        safeAutocomplete('#teamPlayer2', {
            minLength: 2,
            source: function (request, response) {
                response(getPlayerDetails(request.term));
            },
            select: function (event, ui) {
                setVal('teamPlayerId2', ui.item.id);
            },
            change: function (event, ui) {
                if (ui.item === null) {
                    if (event.target) event.target.value = '';
                    setVal('teamPlayerId2', '');
                    setVal('team', '');
                } else {
                    setVal('teamPlayerId2', ui.item.playerId);
                    const team1 = document.getElementById('teamPlayer1')?.value || '';
                    const team2 = document.getElementById('teamPlayer2')?.value || '';
                    setVal('team', team1 + ' / ' + team2);
                }
            }
        });
    }

    if (document.querySelector('#captain')) {
        safeAutocomplete('#captain', {
            minLength: 2,
            source: function (request, response) {
                const club = document.getElementById('clubId')?.value || null;
                response(getPlayerDetails(request.term, club));
            },
            select: function (event, ui) {
                setVal('captain', ui.item.value);
                setVal('captainId', ui.item.playerId);
                setVal('contactno', ui.item.contactno);
                setVal('contactemail', ui.item.user_email);
            },
            change: function (event, ui) {
                if (ui.item === null) {
                    if (event.target) event.target.value = '';
                    setVal('captain', '');
                    setVal('captainId', '');
                    setVal('contactno', '');
                    setVal('contactemail', '');
                } else {
                    setVal('captain', ui.item.value);
                    setVal('captainId', ui.item.playerId);
                    setVal('contactno', ui.item.contactno);
                    setVal('contactemail', ui.item.user_email);
                }
            }
        });
    }

    if (document.querySelector('#match_secretary_name')) {
        safeAutocomplete('#match_secretary_name', {
            minLength: 2,
            source: function (request, response) {
                const club = document.getElementById('club_id')?.value || null;
                response(getPlayerDetails(request.term, club));
            },
            select: function (event, ui) {
                setVal('match_secretary_name', ui.item.value);
                setVal('match_secretary', ui.item.playerId);
                setVal('match_secretary_contact_no', ui.item.contactno);
                setVal('match_secretary_email', ui.item.user_email);
            },
            change: function (event, ui) {
                if (ui.item === null) {
                    if (event.target) event.target.value = '';
                    setVal('match_secretary_name', '');
                    setVal('match_secretary', '');
                    setVal('match_secretary_contact_no', '');
                    setVal('match_secretary_email', '');
                } else {
                    setVal('match_secretary_name', ui.item.value);
                    setVal('match_secretary', ui.item.playerId);
                    setVal('match_secretary_contact_no', ui.item.contactno);
                    setVal('match_secretary_email', ui.item.user_email);
                }
            }
        });
    }
}
