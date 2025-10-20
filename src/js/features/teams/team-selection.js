/**
 * Team selection and AJAX operations (refactored)
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';

/**
 * Safely set a field value if the element exists
 * @param {string} selector
 * @param {any} value
 */
function setFieldValue(selector, value) {
    const $el = jQuery(selector);
    if ($el.length) {
        $el.val(value);
    }
}

/**
 * Show loading splash and hide the response area
 */
function showLoading(splashSel, responseSel) {
    jQuery(splashSel).removeClass('d-none').css('opacity', 1).show();
    jQuery(responseSel).hide();
}

/**
 * Hide loading splash and show the response area
 */
function hideLoading(splashSel, responseSel) {
    jQuery(splashSel).css('opacity', 0).hide();
    jQuery(responseSel).show();
}

export function initializeTeamSelection() {
    jQuery('select.cupteam').on('change', function () {
        const team = this.value;
        // name appears like team[EVENT]; extract EVENT
        const name = this.name || '';
        const event = name.substring(5, name.length - 1);

        const notifyField = `#team-${event}`;
        const responseField = `#team-dtls-${event}`;
        const splash = `#splash-${event}`;

        jQuery(notifyField).removeClass('is-invalid');
        showLoading(splash, responseField);

        // Build AJAX data
        const ajaxData = {
            team,
            event,
            action: 'racketmanager_get_team_info',
            security: getAjaxNonce(),
        };

        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: getAjaxUrl(),
            data: ajaxData,
            success: function (response) {
                const team_info = response?.data || {};
                const captainInput = `captain-${event}`;
                const ref = captainInput.substring(7);

                setFieldValue(`#${captainInput}`, team_info.captain);
                setFieldValue(`#captainId${ref}`, team_info.captainid);
                setFieldValue(`#contactno${ref}`, team_info.contactno);
                setFieldValue(`#contactemail${ref}`, team_info.user_email);
                setFieldValue(`#matchday${ref}`, team_info.match_day_num);
                setFieldValue(`#matchtime${ref}`, team_info.match_time);
            },
            error: function (response) {
                const feedback = `${notifyField}Feedback`;
                const message = response?.responseJSON?.data.msg || response?.statusText || 'An error occurred';
                jQuery(feedback).text(message);
                jQuery(notifyField).addClass('is-invalid').show();
            },
            complete: function () {
                hideLoading(splash, responseField);
            }
        });
    });
}
