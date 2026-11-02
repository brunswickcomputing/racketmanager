/**
 * Admin functions entrypoint: initializes UI components, form validation,
 * and exposes activaTab on window.Racketmanager namespace.
 *
 * @module admin/functions
 */

if (typeof jQuery === 'undefined') {
    throw new ReferenceError('functions.js requires jQuery');
}

import { initAdminUI } from './features/ui/index.js';
import { initTeamValidation, initTeamAutocomplete } from './features/forms/index.js';

function activaTab(tab) {
    const jq = typeof window !== 'undefined' ? window['jQuery'] : null;
    if (jq && typeof jq.fn?.tab === 'function') {
        jq('.navbar-nav button[data-bs-target="#' + tab + '"]').tab('show');
        jq('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
        jq('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
    }
}

window.Racketmanager = window.Racketmanager || {};
window.Racketmanager.activaTab = activaTab;

function onReady() {
    initAdminUI();
    if (typeof document !== 'undefined') {
        initTeamValidation(document);
    }
    initTeamAutocomplete();
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }
}
