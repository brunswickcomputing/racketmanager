/**
 * League archive navigation
 */

export function initializeArchiveNavigation() {
    jQuery('#racketmanager_archive').on('change', function () {
        const league = jQuery('#league_id').val();
        const season = jQuery('#season').val();

        const base = `${globalThis.location.protocol}//${globalThis.location.host}`;
        const fullPath = `league/${league.toLowerCase()}/${season}/`;
        const finalUrl = new URL(fullPath, base).href;
        globalThis.location = finalUrl;

        return false;
    });
}
