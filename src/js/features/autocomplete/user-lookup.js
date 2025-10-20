/**
 * User lookup autocomplete functionality
 */

import { safeAutocomplete } from '../../utils/jquery-helpers.js';
import { getPlayerDetails, setPlayerDetails } from '../../utils/player-utils.js';

/**
 * Initialize user lookup autocomplete
 *
 * @param {jQuery|HTMLElement} [context=document] - Context to search within (for AJAX-loaded content)
 */
export function initializeUserLookup(context = document) {
    const $context = jQuery(context);

    // Find all user lookup inputs within the context
    $context.find('.user-lookup').each(function() {
        const fieldId = '#' + this.id;

        safeAutocomplete(fieldId, {
            minLength: 2,
            source: function (request, response) {
                const club = jQuery("#clubId").val();
                const notifyField = '#userFeedback';

                const results = getPlayerDetails('name', request.term, club, notifyField);
                response(results);
            },
            select: function (event, ui) {
                if (ui.item.value === 'null') {
                    ui.item.value = '';
                }

                jQuery('#userName').val(ui.item.value);
                jQuery('#userId').val(ui.item.playerId);
                jQuery('#userContactno').val(ui.item.contactno);
                jQuery('#userEmail').val(ui.item.user_email);
            },
            change: function (event, ui) {
                setPlayerDetails(
                    ui,
                    '#userName',
                    '#userId',
                    '#userContactno',
                    '#userEmail'
                );
            }
        });
    });
}
