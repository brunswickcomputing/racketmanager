
/**
 * jQuery utility helpers
 * Provides safe wrappers and utilities for jQuery and jQuery UI functionality
 */

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
 * @param {string} selector - jQuery selector (can match multiple elements)
 * @param {Object} options - Autocomplete options
 * @returns {boolean} True if at least one element initialized successfully
 */
export function safeAutocomplete(selector, options) {
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
 * @param {string} selector - jQuery selector
 * @returns {boolean} True if destroyed successfully
 */
export function destroyAutocomplete(selector) {
    if (!isAutocompleteAvailable()) {
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
 * @param {string} selector - jQuery selector
 * @param {Object} options - Datepicker options
 * @returns {boolean} True if initialized successfully
 */
export function safeDatepicker(selector, options) {
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
 * @param {string} selector - jQuery selector
 * @param {Object} options - Sortable options
 * @returns {boolean} True if initialized successfully
 */
export function safeSortable(selector, options) {
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
 * @param {string} selector - jQuery selector
 * @param {Object} options - Tooltip options
 * @returns {boolean} True if initialized successfully
 */
export function safeTooltip(selector, options) {
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
export function safeAjax(options) {
    if (!isjQueryAvailable()) {
        return Promise.reject(new Error('jQuery is not available'));
    }

    return new Promise((resolve, reject) => {
        const defaultOptions = {
            type: 'POST',
            dataType: 'json',
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                reject({ xhr, status, error });
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
 * @param {string} selector - jQuery selector
 * @param {string} eventName - Event name
 * @param {*} data - Optional event data
 * @returns {boolean} True if triggered successfully
 */
export function safeTrigger(selector, eventName, data = null) {
    if (!isjQueryAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    if (data) {
        $elements.trigger(eventName, data);
    } else {
        $elements.trigger(eventName);
    }

    return true;
}

/**
 * Safely attach event handler
 *
 * @param {string} selector - jQuery selector
 * @param {string} eventName - Event name
 * @param {Function} handler - Event handler
 * @returns {boolean} True if attached successfully
 */
export function safeOn(selector, eventName, handler) {
    if (!isjQueryAvailable()) {
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
 * @param {string} selector - jQuery selector
 * @param {string} eventName - Event name (optional)
 * @returns {boolean} True if removed successfully
 */
export function safeOff(selector, eventName = null) {
    if (!isjQueryAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    if (eventName) {
        $elements.off(eventName);
    } else {
        $elements.off();
    }

    return true;
}

/**
 * Check if element exists in DOM
 *
 * @param {string} selector - jQuery selector
 * @returns {boolean} True if element exists
 */
export function elementExists(selector) {
    return isjQueryAvailable() && jQuery(selector).length > 0;
}

/**
 * Get element count matching selector
 *
 * @param {string} selector - jQuery selector
 * @returns {number} Number of matching elements
 */
export function getElementCount(selector) {
    return isjQueryAvailable() ? jQuery(selector).length : 0;
}

/**
 * Safely animate element
 *
 * @param {string} selector - jQuery selector
 * @param {Object} properties - Animation properties
 * @param {number} duration - Animation duration (ms)
 * @param {Function} callback - Completion callback
 * @returns {boolean} True if animation started
 */
export function safeAnimate(selector, properties, duration = 400, callback = null) {
    if (!isjQueryAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    if (callback) {
        $elements.animate(properties, duration, callback);
    } else {
        $elements.animate(properties, duration);
    }

    return true;
}

/**
 * Safely show element with animation
 *
 * @param {string} selector - jQuery selector
 * @param {number} duration - Animation duration (ms)
 * @param {Function} callback - Completion callback
 * @returns {boolean} True if shown
 */
export function safeShow(selector, duration = 400, callback = null) {
    if (!isjQueryAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    if (duration) {
        $elements.show(duration, callback);
    } else {
        $elements.show();
        if (callback) callback();
    }

    return true;
}

/**
 * Safely hide element with animation
 *
 * @param {string} selector - jQuery selector
 * @param {number} duration - Animation duration (ms)
 * @param {Function} callback - Completion callback
 * @returns {boolean} True if hidden
 */
export function safeHide(selector, duration = 400, callback = null) {
    if (!isjQueryAvailable()) {
        return false;
    }

    const $elements = jQuery(selector);
    if ($elements.length === 0) {
        return false;
    }

    if (duration) {
        $elements.hide(duration, callback);
    } else {
        $elements.hide();
        if (callback) callback();
    }

    return true;
}

/**
 * Delegate event handler (for dynamic content)
 *
 * @param {string} containerSelector - Container selector
 * @param {string} targetSelector - Target element selector
 * @param {string} eventName - Event name
 * @param {Function} handler - Event handler
 * @returns {boolean} True if delegated successfully
 */
export function safeDelegate(containerSelector, targetSelector, eventName, handler) {
    if (!isjQueryAvailable()) {
        return false;
    }

    const $container = jQuery(containerSelector);
    if ($container.length === 0) {
        return false;
    }

    $container.on(eventName, targetSelector, handler);
    return true;
}
