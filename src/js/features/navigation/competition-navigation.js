/**
 * Competition archive navigation
 */

export function initializeCompetitionNavigation() {
    jQuery('#racketmanager_competition_archive #season').on('change', function () {
        const pagename = jQuery('#pagename').val();
        const season = jQuery('#season').val();
        const base = `${globalThis.location.protocol}//${globalThis.location.host}`;
        const fullPath = `${pagename.toLowerCase()}${season}/`;
        const finalUrl = new URL(fullPath, base).href;
        globalThis.location = finalUrl;
        return false;
    });
}
