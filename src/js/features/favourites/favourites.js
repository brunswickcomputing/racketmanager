/**
 * Favourites management
 */
import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';

export function initializeFavourites() {
    jQuery('[data-js=add-favourite]').click(function (e) {
        e.preventDefault();

        let favouriteId = jQuery(this).data('favourite');
        let favouriteType = jQuery(this).data('type');
        let favouriteStatus = jQuery(this).data('status');
        let favourite_field = "#" + e.currentTarget.id;
        let notifyField = "#fav-msg-" + favouriteId;

        // Toggle UI state
        if (favouriteStatus === 1) {
            jQuery(favourite_field).attr("data-status", 0);
            jQuery(favourite_field).attr("data-bs-original-title", "Add favourite");
            jQuery(favourite_field).removeClass('is-favourite');
            jQuery(favourite_field).find('i').removeClass('fav-icon-svg-selected');
        } else {
            jQuery(favourite_field).attr("data-status", 1);
            jQuery(favourite_field).attr("data-bs-original-title", "Remove favourite");
            jQuery(favourite_field).addClass('is-favourite');
            jQuery(favourite_field).find('i').addClass('fav-icon-svg-selected');
        }

        // Build AJAX data
        const ajaxData = {
            type: favouriteType,
            id: favouriteId,
            action: "racketmanager_add_favourite",
            security: getAjaxNonce(),
       };

        // Send AJAX request
        jQuery.ajax({
            url: getAjaxUrl(),
            type: "POST",
            data: ajaxData,
            error: function (response) {
                if (response.responseJSON) {
                    jQuery(notifyField).text(response.responseJSON.data);
                } else {
                    jQuery(notifyField).text(response.statusText);
                }
                jQuery(notifyField).show();
                jQuery(notifyField).addClass('message-error');
            }
        });
    });
}
