/**
 * Autocomplete helpers for jQuery UI Autocomplete
 * Provides safe wrappers and guards for autocomplete initialization
 */

/**
 * Validate that selector is a non-empty string or valid DOM element / object
 *
 * @param {*} selector - Selector to validate
 * @returns {boolean} True if selector is valid
 */
function isValidSelector(selector) {
    if (selector === null || selector === undefined) {
        return false;
    }
    if (typeof selector === 'string') {
        return selector.trim().length > 0;
    }
    return true;
}

/**
 * Resolve the global jQuery instance if present without referencing the bare identifier
 *
 * @returns {Function|undefined}
 */
function getJQuery() {
    if (typeof globalThis !== 'undefined' && typeof globalThis['jQuery'] !== 'undefined') {
        return globalThis['jQuery'];
    }
    if (typeof window !== 'undefined' && typeof window['jQuery'] !== 'undefined') {
        return window['jQuery'];
    }
    return undefined;
}

/**
 * Check if jQuery UI is available
 *
 * @returns {boolean} True if available
 */
export function isJQueryUIAvailable() {
    const jq = getJQuery();
    return Boolean(jq?.ui);
}

/**
 * Check if jQuery UI Autocomplete is available
 *
 * @returns {boolean} True if available
 */
export function isAutocompleteAvailable() {
    const jq = getJQuery();
    return typeof jq !== 'undefined' && typeof jq.fn?.autocomplete === 'function';
}

/**
 * Wait for jQuery UI Autocomplete to be loaded
 *
 * @param {Function} callback - Function to call when ready
 * @param {number} timeout - Maximum wait time in ms
 * @param {number} checkInterval - How often to check (ms)
 */
export function whenAutocompleteReady(callback, timeout = 5000, checkInterval = 100) {
    if (typeof callback !== 'function') {
        return;
    }
    const startTime = Date.now();

    const checkAutocomplete = () => {
        if (isAutocompleteAvailable()) {
            callback();
        } else if (Date.now() - startTime < timeout) {
            setTimeout(checkAutocomplete, checkInterval);
        } else {
            console.error('jQuery UI Autocomplete not loaded within timeout period');
        }
    };

    checkAutocomplete();
}

/**
 * Safely initialize autocomplete
 *
 * @param {string|Element|object} selector - jQuery selector (can match multiple elements)
 * @param {Object} options - Autocomplete options
 * @returns {boolean} True if at least one element initialized successfully
 */
export function safeAutocomplete(selector, options = {}) {
    if (!isValidSelector(selector)) {
        return false;
    }
    if (!isAutocompleteAvailable()) {
        console.error('jQuery UI Autocomplete is not available');
        return false;
    }

    const jq = getJQuery();
    const elements = jq(selector);
    if (!elements || elements.length === 0) {
        // Element not found - this is OK, not all pages have all fields
        return false;
    }

    // Initialize autocomplete on all matching elements
    elements.each(function () {
        jq(this).autocomplete(options);
    });

    return true;
}

/**
 * Log jQuery/jQuery UI status
 * Useful for debugging
 *
 * @returns {Object}
 */
export function logJQueryStatus() {
    const jq = getJQuery();
    const isJQueryLoaded = typeof jq !== 'undefined';
    const isUIAvailable = isJQueryUIAvailable();
    const status = {
        jQueryLoaded: isJQueryLoaded,
        jQueryVersion: isJQueryLoaded && jq.fn ? jq.fn.jquery : null,
        jQueryUILoaded: isUIAvailable,
        jQueryUIVersion: isUIAvailable && jq.ui ? jq.ui.version : null,
        autocompleteAvailable: isAutocompleteAvailable()
    };

    console.log('jQuery Status:', status);
    return status;
}
