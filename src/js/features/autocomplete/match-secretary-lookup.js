/**
 * Match secretary autocomplete functionality
 */

import { safeAutocomplete } from '../../utils/jquery-helpers.js';
import { getPlayerDetails, setPlayerDetails } from '../../utils/player-utils.js';

/**
 * Initialize match secretary autocomplete
 *
 * @param {jQuery|HTMLElement} [context=document] - Context to search within (for AJAX-loaded content)
 */
export function initializeMatchSecretaryLookup(context = document) {
    const $context = jQuery(context);

    // Find match secretary input within the context
    const selector = '#matchSecretaryName';

    // Check if element exists in context
    if ($context.find(selector).length === 0) {
        return;
    }

    $context.find(selector).each(function() {
        const fieldId = '#' + this.id;

        safeAutocomplete(fieldId, {
            minLength: 2,
            source: function (request, response) {
                const club = jQuery("#club_id").val();
                const notifyField = '#secretaryFeedback';

                const results = getPlayerDetails('name', request.term, club, notifyField);
                response(results);
            },
            select: function (event, ui) {
                if (ui.item.value === 'null') {
                    ui.item.value = '';
                }

                jQuery('#matchSecretary').val(ui.item.value);
                jQuery('#matchSecretaryId').val(ui.item.playerId);
                jQuery('#matchSecretaryContactNo').val(ui.item.contactno);
                jQuery('#matchSecretaryEmail').val(ui.item.user_email);
            },
            change: function (event, ui) {
                setPlayerDetails(
                    ui,
                    '#matchSecretary',
                    '#matchSecretaryId',
                    '#matchSecretaryContactNo',
                    '#matchSecretaryEmail'
                );
            }
        });
    });
}
