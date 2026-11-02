/**
 * Admin UI widgets and behaviors: tooltips, popovers, iris colorpicker,
 * sortable tables, and scroll navigation.
 *
 * @module admin/features/ui/admin-ui
 */

import { getAjaxUrl, getAjaxNonce } from '../../../config/ajax-config.js';

function getJQuery() {
    return typeof window !== 'undefined' ? window['jQuery'] : null;
}

/**
 * Fetch and format player clubs for popover display.
 *
 * @param {HTMLElement} link
 * @returns {string}
 */
export function showPlayerClubs(link) {
    const id = link?.id || '';
    const split_id = id.split('_');
    const player = split_id[1];
    let msg = '';
    const jq = getJQuery();

    if (jq && typeof jq.ajax === 'function') {
        jq.ajax({
            url: getAjaxUrl(),
            datatype: 'json',
            type: 'POST',
            async: false,
            data: {
                player: player,
                action: 'racketmanager_get_player_clubs',
                security: getAjaxNonce(),
            },
            success: function (response) {
                let message = 'Player not linked to clubs';
                if (response?.data && response.data.length > 0) {
                    message = '';
                    const clubs = response.data;
                    for (const club of clubs) {
                        message += (club.name || '') + '<br />';
                    }
                }
                msg = message;
            },
            error: function (response) {
                if (response?.responseJSON?.data && Array.isArray(response.responseJSON.data)) {
                    let message = response.responseJSON.data[0] || '';
                    if (Array.isArray(response.responseJSON.data[1])) {
                        for (const errorMsg of response.responseJSON.data[1]) {
                            message += '<br />' + errorMsg;
                        }
                    }
                    msg = message;
                } else {
                    msg = response?.statusText || '';
                }
            }
        });
    }
    return msg;
}

/**
 * Initialize Bootstrap / jQuery UI tooltips.
 */
export function initTooltips() {
    const jq = getJQuery();
    if (jq && typeof jq.fn?.tooltip === 'function') {
        jq('[data-bs-toggle="tooltip"]').tooltip();
    }
}

/**
 * Initialize Bootstrap / jQuery popovers.
 */
export function initPopovers() {
    const jq = getJQuery();
    if (jq && typeof jq.fn?.popover === 'function') {
        jq('[data-bs-toggle="popover"]').popover({
            placement: 'left',
            content: showPlayerClubs,
            html: true,
        });
    }
}

/**
 * Initialize WordPress Iris colorpicker on .racketmanager-colorpicker elements.
 */
export function initColorpicker() {
    const jq = getJQuery();
    if (jq && typeof jq.fn?.iris === 'function') {
        jq('.racketmanager-colorpicker').iris();
    }
}

/**
 * Initialize "go to top" button scroll trigger and click action.
 */
export function initGoTop() {
    if (typeof window === 'undefined' || typeof document === 'undefined') return;

    window.addEventListener('scroll', function () {
        const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
        const goTopElements = document.querySelectorAll('.go-top');
        for (const el of goTopElements) {
            if (scrollTop > 800) {
                el.classList.add('show');
            } else {
                el.classList.remove('show');
            }
        }
    });

    document.addEventListener('click', function (e) {
        const target = e.target;
        const btn = target && typeof target.closest === 'function' ? target.closest('.go-top') : null;
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const jq = getJQuery();
            if (jq && typeof jq === 'function' && typeof jq.fn?.animate === 'function') {
                jq('html, body').animate({ scrollTop: 0 }, 1000);
            } else if (typeof window.scrollTo === 'function') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    });
}

/**
 * Initialize jQuery UI sortable on standings tables.
 */
export function initStandingsSortable() {
    const jq = getJQuery();
    if (jq && typeof jq.fn?.sortable === 'function') {
        jq('.standings-table.sortable').sortable({
            axis: 'y',
        });
    }
    const sortableEls = document.querySelectorAll('.sortable');
    for (const el of sortableEls) {
        el.style.cursor = 'move';
    }
}

/**
 * Apply miscellaneous UI setup (hide top links, disable rank inputs, active filter value).
 */
export function initMiscellaneousUI() {
    if (typeof document === 'undefined') return;

    // Hide top-links in documentation
    const topLinks = document.querySelectorAll('.top-link');
    for (const link of topLinks) {
        link.style.display = 'none';
    }

    // Disable rank input fields
    const rankInputs = document.querySelectorAll('input.rank-input');
    for (const input of rankInputs) {
        input.disabled = true;
    }

    // Set js-active value to 1
    const activeInputs = document.querySelectorAll('#teams-filter > input.js-active');
    for (const input of activeInputs) {
        input.value = '1';
    }
}

/**
 * Initialize all admin UI enhancements.
 */
export function initAdminUI() {
    initTooltips();
    initPopovers();
    initColorpicker();
    initGoTop();
    initStandingsSortable();
    initMiscellaneousUI();
}
