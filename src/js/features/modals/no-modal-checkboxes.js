/**
 * Checkboxes that control conditional visibility without modals
 * Handles aria-controls attribute for accessibility
 */

import { handleCheckboxConditional } from '../../utils/dom-utils.js';

/**
 * Initialize checkboxes that show/hide content without modals
 */
export function initializeNoModalCheckboxes() {
    jQuery(".noModal:checkbox").click(function (event) {
        const target = event.target;
        handleConditionalVisibility(target);
    });
}

/**
 * Handle conditional visibility for a checkbox
 *
 * @param {HTMLElement} target - The checkbox element
 */
function handleConditionalVisibility(target) {
    const isCheckbox = target.getAttribute('type') === 'checkbox';
    const ariaControls = target.getAttribute('aria-controls');

    if (!isCheckbox || !ariaControls) {
        return;
    }

    const controlledElement = findControlledElement(target, ariaControls);

    if (controlledElement && isConditionalElement(controlledElement)) {
        toggleConditionalElement(controlledElement, target.checked);
    }
}

/**
 * Find the element controlled by aria-controls
 *
 * @param {HTMLElement} checkbox - The checkbox element
 * @param {string} controlsId - The ID of the controlled element
 * @returns {HTMLElement|null} The controlled element or null
 */
function findControlledElement(checkbox, controlsId) {
    // First try direct sibling lookup (most common pattern)
    let controlled = checkbox.parentNode.parentNode.querySelector(`#${controlsId}`);

    // If not found, try document-wide search
    if (!controlled) {
        controlled = document.getElementById(controlsId);
    }

    return controlled;
}

/**
 * Check if element is a conditional form element
 *
 * @param {HTMLElement} element - Element to check
 * @returns {boolean} True if it's a conditional element
 */
function isConditionalElement(element) {
    return element.classList.contains('form-checkboxes__conditional');
}

/**
 * Toggle visibility and accessibility of conditional element
 *
 * @param {HTMLElement} element - Element to toggle
 * @param {boolean} show - Whether to show or hide
 */
function toggleConditionalElement(element, show) {
    // Update aria-expanded for accessibility
    element.setAttribute('aria-expanded', show);

    // Toggle visibility class
    element.classList.toggle('form-checkboxes__conditional--hidden', !show);

    // Also handle inline display style if needed
    if (show) {
        element.style.display = '';
    } else {
        element.style.display = 'none';
    }
}

/**
 * Initialize a specific checkbox by selector
 * Useful for dynamically added checkboxes
 *
 * @param {string} selector - jQuery selector for the checkbox
 */
export function initializeCheckbox(selector) {
    jQuery(selector).off('click.noModal').on('click.noModal', function(event) {
        handleConditionalVisibility(event.target);
    });
}

/**
 * Reinitialize all no-modal checkboxes
 * Useful after AJAX content load
 */
export function reinitializeNoModalCheckboxes() {
    // Remove existing handlers to avoid duplicates
    jQuery(".noModal:checkbox").off('click.noModal');

    // Reinitialize
    initializeNoModalCheckboxes();
}

/**
 * Programmatically toggle a checkbox and its conditional content
 *
 * @param {string} checkboxSelector - Selector for the checkbox
 * @param {boolean} checked - Whether checkbox should be checked
 */
export function toggleCheckboxConditional(checkboxSelector, checked) {
    const checkbox = document.querySelector(checkboxSelector);

    if (!checkbox) {
        console.warn(`Checkbox not found: ${checkboxSelector}`);
        return;
    }

    checkbox.checked = checked;
    handleConditionalVisibility(checkbox);
}

/**
 * Get state of conditional element
 *
 * @param {string} checkboxSelector - Selector for the checkbox
 * @returns {Object} State object with checkbox and conditional element info
 */
export function getConditionalState(checkboxSelector) {
    const checkbox = document.querySelector(checkboxSelector);

    if (!checkbox) {
        return null;
    }

    const ariaControls = checkbox.getAttribute('aria-controls');
    const controlledElement = ariaControls ?
        findControlledElement(checkbox, ariaControls) : null;

    return {
        checked: checkbox.checked,
        controlsId: ariaControls,
        controlled: controlledElement,
        visible: controlledElement ?
            !controlledElement.classList.contains('form-checkboxes__conditional--hidden') :
            false
    };
}
