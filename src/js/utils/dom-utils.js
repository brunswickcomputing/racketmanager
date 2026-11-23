/**
 * DOM manipulation utilities
 */

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

/**
 * Safely read a data attribute from an element, preferring standard dataset with fallback
 * for SSR/testing environments where dataset may be absent or undefined.
 *
 * @param {HTMLElement|Element|object} el - Target element
 * @param {string} key - CamelCase dataset property name (e.g. 'action', 'leagueId')
 * @returns {string|null} The attribute value or null if not found
 */
export function getDataAttr(el, key) {
    if (!el) {
        return null;
    }
    const val = el.dataset?.[key];
    if (val !== undefined && val !== null) {
        return val;
    }

    const kebabKey = 'data-' + key.replace(/([A-Z])/g, '-$1').toLowerCase();
    if (el.attributes && typeof el.attributes.getNamedItem === 'function') {
        const attr = el.attributes.getNamedItem(kebabKey);
        if (attr) {
            return attr.value;
        }
    }
    if (typeof el.getAttribute === 'function') {
        return el.getAttribute(kebabKey);
    }
    return null;
}
