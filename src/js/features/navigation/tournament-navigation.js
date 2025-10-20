/**
 * Tournament navigation
 */

import { sanitizeUrlParam } from '../../utils/url-helpers.js';

export function initializeTournamentNavigation() {
    jQuery('#racketmanager_tournament #tournament_id').on('change', function () {
        let tournament = sanitizeUrlParam(jQuery('#tournament_id').val());

        globalThis.location = encodeURI(globalThis.location.protocol) + '//' +
            encodeURIComponent(globalThis.location.host) + '/tournament/' +
            tournament.toLowerCase() + '/';

        return false;
    });
}
