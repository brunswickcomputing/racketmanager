/**
 * Checkboxes that control conditional visibility without modals
 * Handles aria-controls attribute for accessibility
 */

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

