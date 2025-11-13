/**
 * League archive navigation
 */

export function initializeArchiveNavigation() {
    jQuery('#racketmanager_archive').on('change', function () {
        let league = jQuery('#league_id').val();
        let season = jQuery('#season').val();

        globalThis.location = encodeURI(globalThis.location.protocol) + '//' +
            encodeURIComponent(globalThis.location.host) + '/league/' +
            encodeURIComponent(league.toLowerCase()) + '/' + encodeURIComponent(season) + '/';

        return false;
    });
}
