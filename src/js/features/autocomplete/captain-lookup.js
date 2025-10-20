/**
 * Team captain autocomplete
 */

import { getPlayerDetails, setPlayerDetails } from '../../utils/player-utils.js';
import { safeAutocomplete } from '../../utils/jquery-helpers.js';

/**
 * Initialize captain lookup autocomplete
 *
 * @param {jQuery|HTMLElement} [context=document] - Context to search within (for AJAX-loaded content)
 */
export function initializeCaptainLookup(context = document) {
    const $context = jQuery(context);

    // Find all captain inputs within the context
    $context.find('.teamcaptain').each(function() {
        const fieldId = '#' + this.id;

        safeAutocomplete(fieldId, {
            minLength: 2,
            source: function (request, response) {
                const club = jQuery("#clubId").val();
                const fieldRef = this.element[0].id;
                const ref = fieldRef.substr(7);
                const notifyField = '#updateTeamResponse' + ref;

                const results = getPlayerDetails('name', request.term, club, notifyField);
                response(results);
            },
            select: function (event, ui) {
                if (ui.item.value === 'null') {
                    ui.item.value = '';
                }

                const captainInput = this.id;
                const ref = captainInput.substr(7);
                const player = "#" + captainInput;
                const playerId = "#captainId" + ref;
                const contactno = "#contactno" + ref;
                const contactemail = "#contactemail" + ref;

                jQuery(player).val(ui.item.value);
                jQuery(playerId).val(ui.item.playerId);
                jQuery(contactno).val(ui.item.contactno);
                jQuery(contactemail).val(ui.item.user_email);
            },
            change: function (event, ui) {
                const captainInput = this.id;
                const ref = captainInput.substr(7);
                const player = "#" + captainInput;
                const playerId = "#captainId" + ref;
                const contactno = "#contactno" + ref;
                const contactemail = "#contactemail" + ref;

                setPlayerDetails(ui, player, playerId, contactno, contactemail);
            }
        });
    });
}
