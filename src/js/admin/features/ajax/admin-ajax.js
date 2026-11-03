/**
 * Admin AJAX actions and calculations for tournaments, leagues, and seasons.
 *
 * @module admin/features/ajax/admin-ajax
 */

import { getAjaxUrl, getAjaxNonce } from '../../../config/ajax-config.js';

function getJQueryAjax() {
    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
    return jq && typeof jq.ajax === 'function' ? jq.ajax : null;
}

/**
 * Update UI field visibility based on import option.
 *
 * @param {string} option
 */
export function getImportOption(option) {
    const showIds = [];
    const hideIds = [];

    if (option === 'table' || option === 'fixtures') {
        showIds.push('competitions', 'events', 'leagues', 'seasons');
        hideIds.push('clubs');
    } else if (option === 'clubplayers') {
        showIds.push('clubs');
        hideIds.push('competitions', 'events', 'leagues', 'seasons');
    } else if (option === 'players') {
        hideIds.push('clubs', 'competitions', 'events', 'leagues', 'seasons');
    }

    showIds.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = '';
    });
    hideIds.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

/**
 * Autopopulate event name from type and age limit if empty.
 */
export function setEventName() {
    const eventNameEl = document.getElementById('event_name');
    if (!eventNameEl || eventNameEl.value) {
        return;
    }
    const typeEl = document.getElementById('type');
    if (typeEl?.value) {
        const typeText = typeEl.options && typeEl.selectedIndex >= 0
            ? typeEl.options[typeEl.selectedIndex].text
            : '';
        if (typeText) {
            const ageLimitEl = document.getElementById('age_limit');
            const ageLimit = ageLimitEl ? ageLimitEl.value : '';
            if (ageLimit) {
                let eventName = '';
                const ageNum = Number.parseInt(ageLimit, 10);
                if (ageLimit === 'open') {
                    eventName = '';
                } else if (!Number.isNaN(ageNum) && ageNum <= 21) {
                    eventName = 'U' + ageLimit + ' ';
                } else if (!Number.isNaN(ageNum) && ageNum >= 30) {
                    eventName = 'O' + ageLimit + ' ';
                }
                eventName = eventName + typeText;
                eventNameEl.value = eventName;
            }
        }
    }
}

/**
 * Amend a date string by a given number of days.
 *
 * @param {string} date
 * @param {number} adjustment
 * @returns {string}
 */
export function amendDate(date, adjustment) {
    const newDate = new Date(date);
    newDate.setDate(newDate.getDate() + adjustment);
    const year = newDate.toLocaleString('default', { year: 'numeric' });
    const month = newDate.toLocaleString('default', { month: '2-digit' });
    const day = newDate.toLocaleString('default', { day: '2-digit' });
    return year + '-' + month + '-' + day;
}

/**
 * Calculate and set league end date based on start date and round settings.
 */
export function setEndDate() {
    const compTypeEl = document.getElementById('competitionType');
    if (compTypeEl?.value === 'league') {
        const dateStart = document.getElementById('dateStart')?.value;
        const dateEndEl = document.getElementById('dateEnd');
        const roundLength = Number.parseFloat(document.getElementById('round_length')?.value || '0');
        const roundLengthWeeks = roundLength / 7;
        const fillerWeeks = Number.parseFloat(document.getElementById('filler_weeks')?.value || '0') || 0;
        const homeAwayDiff = Number.parseFloat(document.getElementById('home_away_diff')?.value || '0') || 0;
        const numMatchDays = Number.parseFloat(document.getElementById('num_match_days')?.value || '0');

        if (dateStart && roundLengthWeeks && numMatchDays && dateEndEl) {
            const endDateGapWeeks = (roundLengthWeeks * numMatchDays) + fillerWeeks + homeAwayDiff;
            const endDateGap = (endDateGapWeeks * 7) - 1;
            dateEndEl.value = amendDate(dateStart, endDateGap);
        }
    }
}

/**
 * Calculate and set number of match days based on max teams and home/away option.
 */
export function setNumMatchDays() {
    const homeAwayEl = document.getElementById('homeAwayTrue');
    const maxTeamsEl = document.getElementById('max_teams');
    const numMatchDaysEl = document.getElementById('num_match_days');
    if (!maxTeamsEl || !numMatchDaysEl) return;

    let maxTeams = Number.parseInt(maxTeamsEl.value, 10);
    const homeAway = homeAwayEl ? Boolean(homeAwayEl.checked) : false;
    let numMatchDays = 0;
    if (maxTeams && !Number.isNaN(maxTeams)) {
        if (maxTeams % 2 !== 0) {
            maxTeams++;
        }
        numMatchDays = maxTeams - 1;
        if (homeAway) {
            numMatchDays = numMatchDays * 2;
        }
        numMatchDaysEl.value = numMatchDays;
    }
}

/**
 * Set tournament open, closing, and withdrawal dates via AJAX.
 */
export function setTournamentOpenDate() {
    const dateOpenEl = document.getElementById('dateOpen');
    const dateStart = document.getElementById('dateStart')?.value || '';
    const dateCloseEl = document.getElementById('dateClose');
    const dateWithdrawEl = document.getElementById('dateWithdraw');
    const grade = document.getElementById('grade')?.value || '';

    const notify1 = document.getElementById('alert-dates');
    if (notify1) {
        notify1.style.display = 'none';
        notify1.classList.remove('alert--success', 'alert--danger');
    }
    const notify2 = document.getElementById('alert-dates-response');
    if (notify2) {
        notify2.textContent = '';
    }

    const ajax = getJQueryAjax();
    if (ajax) {
        ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                dateStart: dateStart,
                grade: grade,
                action: 'racketmanager_set_tournament_dates',
                security: getAjaxNonce(),
            },
            success: function (response) {
                const message = response?.data?.msg || '';
                if (notify2) notify2.textContent = message;
                if (dateOpenEl && response?.data) dateOpenEl.value = response.data.date_open !== undefined && response.data.date_open !== null ? response.data.date_open : '';
                if (dateCloseEl && response?.data) dateCloseEl.value = response.data.date_closing !== undefined && response.data.date_closing !== null ? response.data.date_closing : '';
                if (dateWithdrawEl && response?.data) dateWithdrawEl.value = response.data.date_withdraw !== undefined && response.data.date_withdraw !== null ? response.data.date_withdraw : '';
                if (notify1) {
                    notify1.classList.add('alert--success');
                    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
                    const $notify1 = jq && typeof jq === 'function' ? jq(notify1) : null;
                    if ($notify1 && typeof $notify1.delay === 'function' && typeof $notify1.fadeOut === 'function') {
                        $notify1.delay(10000).fadeOut('slow');
                    } else {
                        setTimeout(() => { notify1.style.display = 'none'; }, 10000);
                    }
                }
            },
            error: function (response) {
                if (notify2) {
                    if (response.responseJSON) {
                        notify2.innerHTML = response.responseJSON.data;
                    } else {
                        notify2.textContent = response.statusText;
                    }
                }
                if (notify1) {
                    notify1.classList.add('alert--danger');
                }
            },
            complete: function () {
                if (notify1) notify1.style.display = '';
            }
        });
    }
}

/**
 * Send fixtures email notification via AJAX.
 *
 * @param {Event} e
 * @param {string|number} eventId
 */
export function sendFixtures(e, eventId) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const notify1 = document.getElementById('alert-season');
    if (notify1) {
        notify1.style.display = 'none';
        notify1.classList.remove('alert--success', 'alert--danger');
    }
    const notify2 = document.getElementById('alert-season-response');
    if (notify2) notify2.textContent = '';

    const ajax = getJQueryAjax();
    if (ajax) {
        ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                security: getAjaxNonce(),
                eventId: eventId,
                action: 'racketmanager_send_fixtures'
            },
            success: function (response) {
                const message = response?.data?.msg || '';
                if (notify2) notify2.textContent = message;
                if (notify1) notify1.classList.add('alert--success');
            },
            error: function (response) {
                if (notify2) {
                    if (response.responseJSON) {
                        notify2.innerHTML = response.responseJSON.data;
                    } else {
                        notify2.textContent = response.statusText;
                    }
                }
                if (notify1) notify1.classList.add('alert--danger');
            },
            complete: function () {
                if (notify1) notify1.style.display = '';
            }
        });
    }
}

/**
 * Notify tournament entries are open via AJAX.
 *
 * @param {Event} e
 * @param {string|number} tournamentId
 */
export function notifyTournamentEntryOpen(e, tournamentId) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const notify1 = document.getElementById('alert-tournaments');
    if (notify1) {
        notify1.style.display = 'none';
        notify1.classList.remove('alert--success', 'alert--danger');
    }
    const notify2 = document.getElementById('alert-tournaments-response');
    if (notify2) notify2.textContent = '';

    const ajax = getJQueryAjax();
    if (ajax) {
        ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                tournamentId: tournamentId,
                action: 'racketmanager_notify_tournament_entries_open',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (notify2) notify2.textContent = response.data;
                if (notify1) notify1.classList.add('alert--success');
            },
            error: function (response) {
                if (notify2) {
                    if (response.responseJSON) {
                        notify2.innerHTML = response.responseJSON.data;
                    } else {
                        notify2.textContent = response.statusText;
                    }
                }
                if (notify1) notify1.classList.add('alert--danger');
            },
            complete: function () {
                if (notify1) notify1.style.display = '';
            }
        });
    }
}

/**
 * Notify competition entries are open via AJAX.
 *
 * @param {Event} e
 * @param {string|number} competitionId
 * @param {string|number} season
 */
export function notify_open(e, competitionId, season) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const notify1 = document.getElementById('alert-season');
    if (notify1) {
        notify1.style.display = 'none';
        notify1.classList.remove('alert--success', 'alert--danger');
    }
    const notify2 = document.getElementById('alert-season-response');
    if (notify2) notify2.textContent = '';

    const ajax = getJQueryAjax();
    if (ajax) {
        ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                competitionId: competitionId,
                season: season,
                action: 'racketmanager_notify_competition_entries_open',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (notify2) notify2.textContent = response.data;
                if (notify1) notify1.classList.add('alert--success');
            },
            error: function (response) {
                if (notify2) {
                    if (response.responseJSON) {
                        notify2.innerHTML = response.responseJSON.data;
                    } else {
                        notify2.textContent = response.statusText;
                    }
                }
                if (notify1) notify1.classList.add('alert--danger');
            },
            complete: function () {
                if (notify1) notify1.style.display = '';
            }
        });
    }
}

/**
 * Email league constitution via AJAX.
 *
 * @param {Event} e
 * @param {string|number} eventId
 */
export function emailConstitution(e, eventId) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const notify = document.getElementById('notifyMessage-constitution');
    if (notify) {
        notify.style.display = 'none';
        notify.className = '';
    }

    const ajax = getJQueryAjax();
    if (ajax) {
        ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                eventId: eventId,
                action: 'racketmanager_email_constitution',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (notify) {
                    notify.textContent = response.data;
                    notify.style.display = '';
                    notify.className = 'message-success';
                    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
                    const $notify = jq && typeof jq === 'function' ? jq(notify) : null;
                    if ($notify && typeof $notify.delay === 'function' && typeof $notify.fadeOut === 'function') {
                        $notify.delay(10000).fadeOut('slow');
                    } else {
                        setTimeout(() => { notify.style.display = 'none'; }, 10000);
                    }
                }
            },
            error: function (response) {
                if (notify) {
                    if (response.responseJSON) {
                        notify.textContent = response.responseJSON.data;
                    } else {
                        notify.textContent = response.statusText;
                    }
                    notify.style.display = '';
                    notify.className = 'message-error';
                }
            }
        });
    }
}

/**
 * Notify match teams via AJAX.
 *
 * @param {string|number} matchId
 */
export function notifyTeams(matchId) {
    const notify = document.getElementById('notifyMessage-' + matchId);
    if (notify) {
        notify.style.display = 'none';
        notify.className = '';
    }

    const ajax = getJQueryAjax();
    if (ajax) {
        ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                matchId: matchId,
                action: 'racketmanager_notify_teams',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (notify) {
                    notify.textContent = response?.data?.message || '';
                    notify.style.display = '';
                    notify.className = 'message-success';
                    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
                    const $notify = jq && typeof jq === 'function' ? jq(notify) : null;
                    if ($notify && typeof $notify.delay === 'function' && typeof $notify.fadeOut === 'function') {
                        $notify.delay(10000).fadeOut('slow');
                    } else {
                        setTimeout(() => { notify.style.display = 'none'; }, 10000);
                    }
                }
            },
            error: function (response) {
                if (notify) {
                    if (response.responseJSON) {
                        notify.textContent = response.responseJSON.data;
                    } else {
                        notify.textContent = response.statusText;
                    }
                    notify.style.display = '';
                    notify.className = 'message-error';
                }
            }
        });
    }
}

/**
 * Insert home stadium into match location field via AJAX.
 *
 * @param {string|number} team_id
 * @param {number} i
 */
export function insertHomeStadium(team_id, i) {
    const feedback = document.getElementById('feedback');
    if (feedback) {
        feedback.classList.remove('message-error');
        feedback.textContent = '';
    }
    const locationInput = document.getElementById('location_' + i);

    const ajax = getJQueryAjax();
    if (ajax) {
        ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                team_id: team_id,
                action: 'racketmanager_insert_home_stadium',
                security: getAjaxNonce(),
            },
            success: function (response) {
                if (locationInput?.value === '') {
                    locationInput.value = response.data;
                }
            },
            error: function (response) {
                if (feedback) {
                    if (response.responseJSON) {
                        feedback.innerHTML = response.responseJSON.data;
                    } else {
                        feedback.textContent = response.statusText;
                    }
                    feedback.style.display = '';
                    feedback.classList.add('message-error');
                }
            }
        });
    }
}

/**
 * Autopopulate match dates across fixture rows in add mode.
 *
 * @param {string} match_date
 * @param {number} i
 * @param {number} max_matches
 * @param {string} mode
 */
export function setMatchDate(match_date, i, max_matches, mode) {
    if (i === 0 && mode === 'add') {
        for (let xx = 1; xx < max_matches; xx++) {
            const el = document.getElementById('myDatePicker[' + xx + ']');
            if (el) {
                el.value = match_date;
            }
        }
    }
}

/**
 * Autopopulate match day across fixture rows in add mode.
 *
 * @param {string|number} match_day
 * @param {number} i
 * @param {number} max_matches
 * @param {string} mode
 */
export function setMatchDayPopUp(match_day, i, max_matches, mode) {
    if (i === 0 && mode === 'add') {
        for (let xx = 1; xx < max_matches; xx++) {
            const el = document.getElementById('match_day_' + xx);
            if (el) {
                el.value = match_day;
            }
        }
    }
}

/**
 * Save additional points for a league table row via AJAX.
 *
 * @param {string|number} points
 * @param {string|number} table_id
 * @param {Function} [reloadPage] page reload collaborator, defaults to window.location.reload
 */
export function saveAddPoints(points, table_id, reloadPage = () => window.location.reload()) {
    const notify = document.getElementById('feedback_' + table_id);
    if (notify) {
        notify.classList.remove('message-error');
        notify.style.display = 'none';
        notify.textContent = '';
    }
    const loading = document.getElementById('loading_' + table_id);
    if (loading) {
        loading.style.display = 'inline';
        const pluginUrl = typeof RacketManagerAjaxL10n !== 'undefined' ? RacketManagerAjaxL10n.pluginUrl : '';
        loading.innerHTML = '<img alt="loading" src="' + pluginUrl + '/images/loading.gif" />';
    }

    const ajax = getJQueryAjax();
    if (ajax) {
        ajax({
            url: getAjaxUrl(),
            type: 'POST',
            data: {
                action: 'racketmanager_save_add_points',
                security: getAjaxNonce(),
                table_id: table_id,
                points: points,
            },
            success: function () {
                const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
                const $loading = jq && typeof jq === 'function' ? jq('#loading_' + table_id) : null;
                if ($loading && typeof $loading.fadeOut === 'function') {
                    $loading.fadeOut('fast');
                } else if (loading) {
                    loading.style.display = 'none';
                }
                reloadPage();
                return true;
            },
            error: function (response) {
                if (notify) {
                    if (response.responseJSON) {
                        notify.textContent = response.responseJSON.data;
                    } else {
                        notify.textContent = response.statusText;
                    }
                    notify.style.display = 'inline-block';
                    notify.classList.add('message-error');
                }
            },
            complete: function () {
                if (loading) loading.style.display = 'none';
            }
        });
    }
}
