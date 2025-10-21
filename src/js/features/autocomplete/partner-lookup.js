/**
 * Partner lookup autocomplete functionality
 */

import { safeAutocomplete } from '../../utils/jquery-helpers.js';
import { getPlayerDetails, setPlayerDetails } from '../../utils/player-utils.js';

/**
 * Initialize partner lookup autocomplete
 *
 * @param {jQuery|HTMLElement} [context=document] - Context to search within (for AJAX-loaded content)
 */
export function initializePartnerLookup(context = document) {
    const $context = jQuery(context);

    // Partner name autocomplete
    $context.find('.partner-name').each(function() {
        const fieldId = '#' + this.id;

        safeAutocomplete(fieldId, {
            minLength: 2,
            source: function (request, response) {
                const partnerGender = jQuery("#partnerGender").val();
                const notifyField = '#partnerFeedback';
                const results = getPlayerDetails('name', request.term, null, notifyField, partnerGender);
                response(results);
            },
            select: function (event, ui) {
                if (ui.item.value === 'null') {
                    ui.item.value = '';
                }

                jQuery('#partnerName').val(ui.item.value);
                jQuery('#partnerId').val(ui.item.playerId);
                jQuery('#partnerContactno').val(ui.item.contactno);
                jQuery('#partnerContactemail').val(ui.item.user_email);
                jQuery('#partnerBTM').val(ui.item.btm);
            },
            change: function (event, ui) {
                setPlayerDetails(
                    ui,
                    '#partnerName',
                    '#partnerId',
                    '#partnerContactno',
                    '#partnerContactemail',
                    '#partnerBTM'
                );
            }
        });
    });

    // Partner BTM number autocomplete
    $context.find('.partner-btm').each(function() {
        const fieldId = '#' + this.id;

        safeAutocomplete(fieldId, {
            minLength: 2,
            source: function (request, response) {
                const notifyField = '#partnerFeedback';
                const results = getPlayerDetails('btm', request.term, null, notifyField);
                response(results);
            },
            select: function (event, ui) {
                if (ui.item.value === 'null') {
                    ui.item.value = '';
                }

                jQuery('#partnerBTM').val(ui.item.value);
                jQuery('#partnerName').val(ui.item.name);
                jQuery('#partnerId').val(ui.item.playerId);
                jQuery('#partnerContactno').val(ui.item.contactno);
                jQuery('#partnerContactemail').val(ui.item.user_email);
            },
            change: function (event, ui) {
                if (ui.item === null) {
                    jQuery('#partnerBTM').val('');
                    jQuery('#partnerName').val('');
                    jQuery('#partnerId').val('');
                    jQuery('#partnerContactno').val('');
                    jQuery('#partnerContactemail').val('');
                }
            }
        });
    });
}
