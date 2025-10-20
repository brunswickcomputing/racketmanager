/**
 * Competition archive navigation
 */

export function initializeCompetitionNavigation() {
    jQuery('#racketmanager_competition_archive #season').on('change', function () {
        let pagename = jQuery('#pagename').val();
        let season = jQuery('#season').val();

        globalThis.location = encodeURI(globalThis.location.protocol) + '//' +
            encodeURIComponent(globalThis.location.host) + '/' +
            pagename.toLowerCase() + '/' + season + '/';

        return false;
    });
}
