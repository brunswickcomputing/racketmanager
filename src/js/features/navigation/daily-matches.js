/**
 * Daily matches navigation
 */

export function initializeDailyMatches() {
    jQuery('#racketmanager_daily_matches #match_date').on('change', function () {
        let matchDate = jQuery('#match_date').val();

        globalThis.location = encodeURI(globalThis.location.protocol) + '//' +
            encodeURIComponent(globalThis.location.host) + '/leagues/daily-matches/' +
            encodeURIComponent(matchDate) + '/';

        return false;
    });
}
