
/**
 * Player-related utility functions
 */
import { getAjaxUrl, getAjaxNonce } from '../config/ajax-config.js';

/**
 * Get player details via AJAX
 *
 * @param {string} type - Lookup type ('name' or 'btm')
 * @param {string} name - Search term
 * @param {number|null} club - Club ID
 * @param {string|null} notifyField - Notification field selector
 * @param {string|null} partnerGender - Partner gender filter
 * @returns {Array} Player results
 */
export function getPlayerDetails(type, name, club = null, notifyField = null, partnerGender = null) {
    let results = [];

    // Build AJAX data
    const ajaxData = {
        action: 'racketmanager_get_player_details',
        security: getAjaxNonce(),
        type: type,
        name: name,
        club: club,
        partnerGender: partnerGender
    };

    jQuery.ajax({
        type: 'POST',
        url: getAjaxUrl(),
        data: ajaxData,
        async: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                try {
                    results = JSON.parse(response.data);
                } catch (e) {
                    console.error('Error parsing player data:', e);
                    results = [];
                }
            } else {
                if (notifyField) {
                    jQuery(notifyField).text(response.data || 'Error fetching player details');
                    jQuery(notifyField).addClass('error');
                    jQuery(notifyField).show();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Player lookup error:', error);
            if (notifyField) {
                jQuery(notifyField).text('Error connecting to server');
                jQuery(notifyField).addClass('error');
                jQuery(notifyField).show();
            }
        }
    });

    return results;
}

/**
 * Set player details in form fields
 *
 * @param {Object} ui - jQuery UI autocomplete item
 * @param {string} player - Player name field selector
 * @param {string} playerId - Player ID field selector
 * @param {string} contactno - Contact number field selector
 * @param {string} contactemail - Contact email field selector
 */
export function setPlayerDetails(ui, player, playerId, contactno, contactemail) {
    if (ui.item === null) {
        jQuery(player).val('');
        jQuery(playerId).val('');
        jQuery(contactno).val('');
        jQuery(contactemail).val('');
    } else {
        jQuery(player).val(ui.item.value);
        jQuery(playerId).val(ui.item.playerId);
        jQuery(contactno).val(ui.item.contactno);
        jQuery(contactemail).val(ui.item.user_email);
    }
}

/**
 * Check if element already has autocomplete initialized
 *
 * @param {jQuery} $element - jQuery element
 * @returns {boolean}
 */
export function hasAutocomplete($element) {
    return $element.hasClass('ui-autocomplete-input');
}
