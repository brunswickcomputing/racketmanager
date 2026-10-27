
/**
 * jQuery utility helpers
 * Provides safe wrappers and utilities for jQuery and jQuery UI functionality
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
 * Check if jQuery is loaded
 *
 * @returns {boolean} True if available
 */
export function isjQueryAvailable() {
    return typeof jQuery !== 'undefined';
}

/**
 * Get jQuery version
 *
 * @returns {string|null} jQuery version or null
 */
export function getjQueryVersion() {
    return isjQueryAvailable() ? jQuery.fn.jquery : null;
}

/**
 * Check if jQuery UI is loaded
 *
 * @returns {boolean} True if available
 */
export function isjQueryUIAvailable() {
    return isjQueryAvailable() && typeof jQuery.ui !== 'undefined';
}

/**
 * Get jQuery UI version
 *
 * @returns {string|null} jQuery UI version or null
 */
export function getjQueryUIVersion() {
    return isjQueryUIAvailable() ? jQuery.ui.version : null;
}

/**
 * Check if jQuery UI Autocomplete is available
 *
 * @returns {boolean} True if available
 */
export function isAutocompleteAvailable() {
    return isjQueryAvailable() && typeof jQuery.fn.autocomplete === 'function';
}

/**
 * Wait for jQuery UI to be loaded
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
 * @param {string|Element|jQuery} selector - jQuery selector (can match multiple elements)
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

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        // Element not found - this is OK, not all pages have all fields
        return false;
    }

    // Initialize autocomplete on all matching elements
    $elements.each(function() {
        jQuery(this).autocomplete(options);
    });

    return true;
}

/**
 * Destroy autocomplete instance
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @returns {boolean} True if destroyed successfully
 */
export function destroyAutocomplete(selector) {
    if (!isValidSelector(selector) || !isAutocompleteAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    $elements.each(function() {
        const $element = jQuery(this);
        if ($element.autocomplete('instance')) {
            $element.autocomplete('destroy');
        }
    });

    return true;
}

/**
 * Safely initialize datepicker
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {Object} options - Datepicker options
 * @returns {boolean} True if initialized successfully
 */
export function safeDatepicker(selector, options = {}) {
    if (!isValidSelector(selector)) {
        return false;
    }
    if (!isjQueryUIAvailable() || typeof jQuery.fn.datepicker !== 'function') {
        console.error('jQuery UI Datepicker is not available');
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    $elements.datepicker(options);
    return true;
}

/**
 * Safely initialize sortable
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {Object} options - Sortable options
 * @returns {boolean} True if initialized successfully
 */
export function safeSortable(selector, options = {}) {
    if (!isValidSelector(selector)) {
        return false;
    }
    if (!isjQueryUIAvailable() || typeof jQuery.fn.sortable !== 'function') {
        console.error('jQuery UI Sortable is not available');
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    $elements.sortable(options);
    return true;
}

/**
 * Safely initialize tooltip
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {Object} options - Tooltip options
 * @returns {boolean} True if initialized successfully
 */
export function safeTooltip(selector, options = {}) {
    if (!isValidSelector(selector)) {
        return false;
    }
    if (!isjQueryUIAvailable() || typeof jQuery.fn.tooltip !== 'function') {
        console.error('jQuery UI Tooltip is not available');
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    $elements.tooltip(options);
    return true;
}

/**
 * Log jQuery/jQuery UI status
 * Useful for debugging
 */
export function logJQueryStatus() {
    const status = {
        jQueryLoaded: isjQueryAvailable(),
        jQueryVersion: getjQueryVersion(),
        jQueryUILoaded: isjQueryUIAvailable(),
        jQueryUIVersion: getjQueryUIVersion(),
        autocompleteAvailable: isAutocompleteAvailable()
    };

    console.log('jQuery Status:', status);
    return status;
}

/**
 * Wait for jQuery to be ready
 *
 * @param {Function} callback - Function to call when ready
 */
export function whenjQueryReady(callback) {
    if (typeof callback !== 'function') {
        return;
    }
    if (isjQueryAvailable()) {
        jQuery(callback);
    } else {
        document.addEventListener('DOMContentLoaded', callback);
    }
}

/**
 * Safe AJAX call with error handling
 *
 * @param {Object} options - jQuery AJAX options
 * @returns {Promise} Promise that resolves with response
 */
export function safeAjax(options = {}) {
    if (!isjQueryAvailable()) {
        return Promise.reject(new Error('jQuery is not available'));
    }

    return new Promise((resolve, reject) => {
        const defaultOptions = {
            type: 'POST',
            dataType: 'json',
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                const errorMessage = error || (xhr && xhr.statusText) || 'AJAX request failed';
                const ajaxError = new Error(`AJAX Error: ${status || 'unknown'} - ${errorMessage}`);
                ajaxError.xhr = xhr;
                ajaxError.status = status;
                ajaxError.error = error;
                reject(ajaxError);
            },
            success: function(response) {
                resolve(response);
            }
        };

        jQuery.ajax({ ...defaultOptions, ...options });
    });
}

/**
 * Safely trigger jQuery event
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {string} eventName - Event name
 * @param {*} data - Optional event data
 * @returns {boolean} True if triggered successfully
 */
export function safeTrigger(selector, eventName, data = null) {
    if (!isValidSelector(selector) || !isjQueryAvailable() || typeof eventName !== 'string') {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    if (data !== null && data !== undefined) {
        $elements.trigger(eventName, data);
    } else {
        $elements.trigger(eventName);
    }

    return true;
}

/**
 * Safely attach event handler
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {string} eventName - Event name
 * @param {Function} handler - Event handler
 * @returns {boolean} True if attached successfully
 */
export function safeOn(selector, eventName, handler) {
    if (!isValidSelector(selector) || !isjQueryAvailable() || typeof eventName !== 'string' || typeof handler !== 'function') {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    $elements.on(eventName, handler);
    return true;
}

/**
 * Safely remove event handler
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {string} eventName - Event name (optional)
 * @returns {boolean} True if removed successfully
 */
export function safeOff(selector, eventName = null) {
    if (!isValidSelector(selector) || !isjQueryAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    if (typeof eventName === 'string') {
        $elements.off(eventName);
    } else {
        $elements.off();
    }

    return true;
}

/**
 * Check if element exists in DOM
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @returns {boolean} True if element exists
 */
export function elementExists(selector) {
    return isValidSelector(selector) && isjQueryAvailable() && jQuery(selector).length > 0;
}

/**
 * Get element count matching selector
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @returns {number} Number of matching elements
 */
export function getElementCount(selector) {
    return (isValidSelector(selector) && isjQueryAvailable()) ? jQuery(selector).length : 0;
}

/**
 * Safely animate element
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {Object} properties - Animation properties
 * @param {number} duration - Animation duration (ms)
 * @param {Function} callback - Completion callback
 * @returns {boolean} True if animation started
 */
export function safeAnimate(selector, properties, duration = 400, callback = null) {
    if (!isValidSelector(selector) || !isjQueryAvailable() || !properties || typeof properties !== 'object') {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    if (typeof callback === 'function') {
        $elements.animate(properties, duration, callback);
    } else {
        $elements.animate(properties, duration);
    }

    return true;
}

/**
 * Safely show element with animation
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {number|string} duration - Animation duration (ms)
 * @param {Function} callback - Completion callback
 * @returns {boolean} True if shown
 */
export function safeShow(selector, duration = 400, callback = null) {
    if (!isValidSelector(selector) || !isjQueryAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    const isFnCallback = typeof callback === 'function';

    if (duration !== null && duration !== undefined && duration !== 0) {
        if (isFnCallback) {
            $elements.show(duration, callback);
        } else {
            $elements.show(duration);
        }
    } else {
        $elements.show();
        if (isFnCallback) {
            callback();
        }
    }

    return true;
}

/**
 * Safely hide element with animation
 *
 * @param {string|Element|jQuery} selector - jQuery selector
 * @param {number|string} duration - Animation duration (ms)
 * @param {Function} callback - Completion callback
 * @returns {boolean} True if hidden
 */
export function safeHide(selector, duration = 400, callback = null) {
    if (!isValidSelector(selector) || !isjQueryAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    const isFnCallback = typeof callback === 'function';

    if (duration !== null && duration !== undefined && duration !== 0) {
        if (isFnCallback) {
            $elements.hide(duration, callback);
        } else {
            $elements.hide(duration);
        }
    } else {
        $elements.hide();
        if (isFnCallback) {
            callback();
        }
    }

    return true;
}

/**
 * Delegate event handler (for dynamic content)
 *
 * @param {string|Element|jQuery} containerSelector - Container selector
 * @param {string} targetSelector - Target element selector
 * @param {string} eventName - Event name
 * @param {Function} handler - Event handler
 * @returns {boolean} True if delegated successfully
 */
export function safeDelegate(containerSelector, targetSelector, eventName, handler) {
    if (!isValidSelector(containerSelector) || !isjQueryAvailable() || typeof targetSelector !== 'string' || typeof eventName !== 'string' || typeof handler !== 'function') {
        return false;
    }

    const $container = jQuery(containerSelector);
    if ($container.length === 0) {
        return false;
    }

    $container.on(eventName, targetSelector, handler);
    return true;
}
