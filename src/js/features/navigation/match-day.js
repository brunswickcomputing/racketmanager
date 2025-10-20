/**
 * Match day navigation
 */

import { sanitizeUrlParam } from '../../utils/url-helpers.js';
import { tabDataLink } from '../tabdata/index.js';

export function initializeMatchDay() {
    jQuery('#racketmanager_match_day_selection').on('change', function (e) {
        let league = sanitizeUrlParam(jQuery('#league_id').val());
        let season = jQuery('#season').val();
        let matchday = jQuery('#match_day').val();

        if (matchday === -1) matchday = 0;

        let leagueLink = '/league/' + league.toLowerCase() + '/' + season +
            '/matches/day' + matchday + '/';
        let leagueId = jQuery('#leagueId').val();

        tabDataLink(e, 'league', leagueId, season, leagueLink, matchday, 'matches');
        return false;
    });
}
