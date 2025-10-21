/**
 * Checkboxes that trigger modals
 */

import { openPartnerModal } from './partner-modal.js';
import { setEventPrice, clearEventPrice } from '../pricing/pricing.js';

export function initializeHasModalCheckboxes() {
    jQuery(".hasModal:checkbox").click(function (event) {
        jQuery('#liEventDetails').addClass('is-loading');
        let target = event.target;
        checkToggle(target, event);
    });
}

function checkToggle($target, event) {
    let liEventDetails = jQuery('#liEventDetails');
    liEventDetails.addClass('is-loading');

    let isCheckbox = $target.getAttribute('type') === 'checkbox';
    let hasAriaControls = $target.getAttribute('aria-controls');
    let inputIsChecked = $target.checked;
    let eventId = $target.id.substring(6);

    if (isCheckbox && hasAriaControls) {
        let $target2 = jQuery('#' + hasAriaControls)[0];
        if ($target2.classList.contains('is-doubles')) {
            $target2.classList.toggle('form-checkboxes__conditional--hidden', !inputIsChecked);
            if (inputIsChecked) {
                // Open Partner modal using modular function (no globals)
                openPartnerModal(event, eventId);
            } else {
                jQuery("#partnerId-" + eventId).val('');
                jQuery("#partnerName-" + eventId).html('');
                // Update pricing using modular pricing
                clearEventPrice(eventId);
                liEventDetails.removeClass('is-loading');
            }
        } else {
            // Singles – update pricing using modular helpers
            if (inputIsChecked) {
                setEventPrice(eventId);
            } else {
                clearEventPrice(eventId);
            }
            liEventDetails.removeClass('is-loading');
        }
    } else {
        liEventDetails.removeClass('is-loading');
    }
}

// Export for use in partner-lookup
export { checkToggle };
