/**
 * Tournament navigation
 */

import { sanitizeUrlParam } from '../../utils/url-helpers.js';

export function initializeTournamentNavigation() {
    jQuery('#racketmanager_tournament #tournament_id').on('change', function () {
        const tournament = sanitizeUrlParam(jQuery('#tournament_id').val());

        const base = `${globalThis.location.protocol}//${globalThis.location.host}`;
        const fullPath = `/tournament/${tournament.toLowerCase()}/`;
        const finalUrl = new URL(fullPath, base).href;
        globalThis.location = finalUrl;

        return false;
    });
}
