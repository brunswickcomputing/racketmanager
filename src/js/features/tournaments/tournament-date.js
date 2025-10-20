/**
 * Tournament date change handler
 */

import { sanitizeUrlParam } from '../../utils/url-helpers.js';
import { tabDataLink } from '../tabdata/index.js';

export function initializeTournamentDate() {
    jQuery('#tournament-match-date-form #match_date').on('change', function (e) {
        let match_date = sanitizeUrlParam(jQuery('#match_date').val(), false);
        let tournament = sanitizeUrlParam(jQuery('#tournament_id').val());

        let tournamentLink = '/tournament/' + tournament.toLowerCase() +
            '/matches/' + match_date + '/';
        let linkId = match_date;
        let linkType = 'matches';
        let tournamentId = jQuery('#tournamentId').val();

        tabDataLink(e, 'tournament', tournamentId, null,
            tournamentLink, linkId, linkType);
        return false;
    });
}
