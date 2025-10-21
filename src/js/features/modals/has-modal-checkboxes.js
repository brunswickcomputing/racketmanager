/**
 * Checkboxes that trigger modals
 */

import { openPartnerModal } from './partner-modal.js';

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
                // Pricing remains legacy for Phase 5; keep BC for now
                if (globalThis.Racketmanager && typeof globalThis.Racketmanager.clearPrice === 'function') {
                    Racketmanager.clearPrice(eventId);
                }
                liEventDetails.removeClass('is-loading');
            }
        } else {
            // Singles – update pricing if legacy function available (Phase 5 will modularize)
            if (inputIsChecked) {
                if (globalThis.Racketmanager && typeof globalThis.Racketmanager.setEventPrice === 'function') {
                    Racketmanager.setEventPrice(eventId);
                }
            } else {
                if (globalThis.Racketmanager && typeof globalThis.Racketmanager.clearPrice === 'function') {
                    Racketmanager.clearPrice(eventId);
                }
            }
            liEventDetails.removeClass('is-loading');
        }
    } else {
        liEventDetails.removeClass('is-loading');
    }
}

// Export for use in partner-lookup
export { checkToggle };
