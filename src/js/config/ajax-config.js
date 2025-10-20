/**
 * AJAX configuration
 * Provides access to WordPress AJAX settings
 */

/**
 * Get AJAX configuration from window
 * @returns {Object} AJAX configuration
 */
export function getAjaxConfig() {
    if (typeof window.ajax_var === 'undefined') {
        console.error('ajax_var is not defined. Check wp_add_inline_script in PHP.');
        return {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: ''
        };
    }
    return window.ajax_var;
}

/**
 * Get AJAX URL
 * @returns {string} AJAX URL
 */
export function getAjaxUrl() {
    return getAjaxConfig().url;
}

/**
 * Get AJAX nonce
 * @returns {string} AJAX nonce
 */
export function getAjaxNonce() {
    return getAjaxConfig().ajax_nonce;
}
