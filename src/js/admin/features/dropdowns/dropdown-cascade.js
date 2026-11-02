/**
 * Dropdown cascade requests and delegated change event handlers for admin post-edit meta boxes and screens.
 *
 * @module admin/features/dropdowns/dropdown-cascade
 */

import { getAjaxUrl, getAjaxNonce } from '../../../config/ajax-config.js';

/**
 * Fetch and render event dropdown for a given competition.
 *
 * @param {string|number} competition_id
 */
export function getEventDropdown(competition_id) {
    const notifyField = '#events';
    const leaguesEl = document.querySelector('#leagues');
    if (leaguesEl) leaguesEl.style.display = 'none';
    const seasonsEl = document.querySelector('#seasons');
    if (seasonsEl) seasonsEl.style.display = 'none';
    const notifyEl = document.querySelector(notifyField);
    if (notifyEl) {
        notifyEl.classList.remove('message-error');
    }

    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
    if (jq && typeof jq.ajax === 'function') {
        jq.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                competition_id: competition_id,
                action: 'racketmanager_get_event_dropdown',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (notifyEl) {
                    notifyEl.innerHTML = response.data;
                }
            },
            error: function (response) {
                if (notifyEl) {
                    if (response.responseJSON) {
                        notifyEl.textContent = response.responseJSON.data;
                    } else {
                        notifyEl.textContent = response.statusText;
                    }
                    notifyEl.classList.add('message-error');
                }
            },
            complete: function () {
                if (notifyEl) {
                    notifyEl.style.display = '';
                }
            }
        });
    }
}

/**
 * Fetch and render league dropdown for a given event.
 *
 * @param {string|number} event_id
 */
export function getLeagueDropdown(event_id) {
    const notifyField = '#leagues';
    const seasonsEl = document.querySelector('#seasons');
    if (seasonsEl) seasonsEl.style.display = 'none';
    const notifyEl = document.querySelector(notifyField);
    if (notifyEl) {
        notifyEl.classList.remove('message-error');
    }

    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
    if (jq && typeof jq.ajax === 'function') {
        jq.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                event_id: event_id,
                action: 'racketmanager_get_league_dropdown',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (notifyEl) {
                    notifyEl.innerHTML = response.data;
                }
            },
            error: function (response) {
                if (notifyEl) {
                    if (response.responseJSON) {
                        notifyEl.textContent = response.responseJSON.data;
                    } else {
                        notifyEl.textContent = response.statusText;
                    }
                    notifyEl.classList.add('message-error');
                }
            },
            complete: function () {
                if (notifyEl) {
                    notifyEl.style.display = '';
                }
            }
        });
    }
}

/**
 * Fetch and render season dropdown for a given league.
 *
 * @param {string|number} league_id
 */
export function getSeasonDropdown(league_id) {
    const notifyField = '#seasons';
    const notifyEl = document.querySelector(notifyField);
    if (notifyEl) {
        notifyEl.classList.remove('message-error');
    }

    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
    if (jq && typeof jq.ajax === 'function') {
        jq.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                league_id: league_id,
                action: 'racketmanager_get_season_dropdown',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (notifyEl) {
                    notifyEl.innerHTML = response.data;
                }
            },
            error: function (response) {
                if (notifyEl) {
                    if (response.responseJSON) {
                        notifyEl.textContent = response.responseJSON.data;
                    } else {
                        notifyEl.textContent = response.statusText;
                    }
                    notifyEl.classList.add('message-error');
                }
            },
            complete: function () {
                if (notifyEl) {
                    notifyEl.style.display = '';
                }
            }
        });
    }
}

/**
 * Fetch and render match dropdown for a given league and season.
 *
 * @param {string|number} league_id
 * @param {string|number} season
 */
export function getMatchDropdown(league_id, season) {
    const notifyField = '#matches';
    const notifyEl = document.querySelector(notifyField);
    if (notifyEl) {
        notifyEl.classList.remove('message-error');
    }

    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
    if (jq && typeof jq.ajax === 'function') {
        jq.ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                league_id: league_id,
                season: season,
                action: 'racketmanager_get_match_dropdown',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (notifyEl) {
                    notifyEl.innerHTML = response.data;
                }
            },
            error: function (response) {
                if (notifyEl) {
                    if (response.responseJSON) {
                        notifyEl.textContent = response.responseJSON.data;
                    } else {
                        notifyEl.textContent = response.statusText;
                    }
                    notifyEl.style.display = '';
                    notifyEl.classList.add('message-error');
                }
            }
        });
    }
}

/**
 * Register delegated change events for dropdown cascade actions.
 *
 * @param {Document|HTMLElement} [root=document]
 */
export function registerDropdownCascades(root = document) {
    if (!root || typeof root.addEventListener !== 'function') return;
    if (root.__rmDropdownCascadesRegistered) return;
    root.__rmDropdownCascadesRegistered = true;

    root.addEventListener('change', function (e) {
        const target = e.target;
        const el = target && typeof target.closest === 'function' ? target.closest('[data-action]') : null;
        if (!el) return;

        const action = el.getAttribute('data-action') || (el.dataset ? el.dataset.action : null);
        if (!action) return;

        switch (action) {
            case 'get-event-dropdown': {
                if (window.Racketmanager && typeof window.Racketmanager.getEventDropdown === 'function') {
                    window.Racketmanager.getEventDropdown(el.value);
                }
                break;
            }
            case 'get-league-dropdown': {
                if (window.Racketmanager && typeof window.Racketmanager.getLeagueDropdown === 'function') {
                    window.Racketmanager.getLeagueDropdown(el.value);
                }
                break;
            }
            case 'get-season-dropdown': {
                if (window.Racketmanager && typeof window.Racketmanager.getSeasonDropdown === 'function') {
                    window.Racketmanager.getSeasonDropdown(el.value);
                }
                break;
            }
            case 'get-match-dropdown': {
                const leagueId = el.getAttribute('data-league-id') || (el.dataset ? el.dataset.leagueId : null);
                if (window.Racketmanager && typeof window.Racketmanager.getMatchDropdown === 'function') {
                    window.Racketmanager.getMatchDropdown(leagueId, el.value);
                }
                break;
            }
        }
    });
}
