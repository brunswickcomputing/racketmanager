/**
 * String manipulation utilities
 */

/**
 * Capitalize first letter of string
 *
 * @param {string} str - String to capitalize
 * @returns {string} Capitalized string
 */
export function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

/**
 * Escape HTML special characters
 *
 * @param {string} str - String to escape
 * @returns {string} Escaped string
 */
export function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Reverse string
 *
 * @param {string} str - String to reverse
 * @returns {string} Reversed string
 */
export function reverse(str) {
    if (!str) return '';
    return str.split('').reverse().join('');
}

/**
 * Check if a string is empty or whitespace
 *
 * @param {string} str - String to check
 * @returns {boolean} True if empty
 */
export function isEmpty(str) {
    return !str || str.trim().length === 0;
}
