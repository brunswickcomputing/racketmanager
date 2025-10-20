/**
 * DOM manipulation utilities
 */

/**
 * Toggle visibility of an element
 *
 * @param {string} selector - jQuery selector
 * @param {boolean} show - Whether to show or hide
 */
export function toggleElement(selector, show) {
    if (show) {
        jQuery(selector).show();
    } else {
        jQuery(selector).hide();
    }
}

/**
 * Handle checkbox conditional visibility
 *
 * @param {HTMLElement} target - Checkbox element
 */
export function handleCheckboxConditional(target) {
    const isCheckbox = target.getAttribute('type') === 'checkbox';
    const hasAriaControls = target.getAttribute('aria-controls');

    if (isCheckbox && hasAriaControls) {
        const controlledElement = target.parentNode.parentNode.querySelector(
            '#' + target.getAttribute('aria-controls')
        );

        if (controlledElement?.classList.contains('form-checkboxes__conditional')) {
            const inputIsChecked = target.checked;
            controlledElement.setAttribute('aria-expanded', inputIsChecked);
            controlledElement.classList.toggle(
                'form-checkboxes__conditional--hidden',
                !inputIsChecked
            );
        }
    }
}
