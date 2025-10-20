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
 * Capitalize first letter of each word
 *
 * @param {string} str - String to capitalize
 * @returns {string} Title case string
 */
export function titleCase(str) {
    if (!str) return '';
    return str
        .toLowerCase()
        .split(' ')
        .map(word => capitalize(word))
        .join(' ');
}

/**
 * Convert string to camelCase
 *
 * @param {string} str - String to convert
 * @returns {string} camelCase string
 */
export function toCamelCase(str) {
    if (!str) return '';
    return str
        .replace(/(?:^\w|[A-Z]|\b\w)/g, (letter, index) => {
            return index === 0 ? letter.toLowerCase() : letter.toUpperCase();
        })
        .replace(/\s+/g, '');
}

/**
 * Convert string to snake_case
 *
 * @param {string} str - String to convert
 * @returns {string} snake_case string
 */
export function toSnakeCase(str) {
    if (!str) return '';
    return str
        .replace(/\W+/g, ' ')
        .split(/ |\B(?=[A-Z])/)
        .map(word => word.toLowerCase())
        .join('_');
}

/**
 * Convert string to kebab-case
 *
 * @param {string} str - String to convert
 * @returns {string} kebab-case string
 */
export function toKebabCase(str) {
    if (!str) return '';
    return str
        .replace(/\W+/g, ' ')
        .split(/ |\B(?=[A-Z])/)
        .map(word => word.toLowerCase())
        .join('-');
}

/**
 * Truncate string to specified length
 *
 * @param {string} str - String to truncate
 * @param {number} length - Maximum length
 * @param {string} suffix - Suffix to add (default: '...')
 * @returns {string} Truncated string
 */
export function truncate(str, length, suffix = '...') {
    if (!str || str.length <= length) return str;
    return str.substring(0, length - suffix.length) + suffix;
}

/**
 * Remove HTML tags from string
 *
 * @param {string} str - String with HTML
 * @returns {string} Plain text
 */
export function stripHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.innerHTML = str;
    return div.textContent || div.innerText || '';
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
 * Unescape HTML entities
 *
 * @param {string} str - String with HTML entities
 * @returns {string} Unescaped string
 */
export function unescapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.innerHTML = str;
    return div.textContent || div.innerText || '';
}

/**
 * Count words in string
 *
 * @param {string} str - String to count
 * @returns {number} Word count
 */
export function wordCount(str) {
    if (!str) return 0;
    return str.trim().split(/\s+/).length;
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
 * Check if string is empty or whitespace
 *
 * @param {string} str - String to check
 * @returns {boolean} True if empty
 */
export function isEmpty(str) {
    return !str || str.trim().length === 0;
}

/**
 * Pad string to length with character
 *
 * @param {string} str - String to pad
 * @param {number} length - Target length
 * @param {string} char - Character to pad with
 * @param {string} side - 'left' or 'right'
 * @returns {string} Padded string
 */
export function pad(str, length, char = ' ', side = 'left') {
    if (!str) str = '';
    const padding = char.repeat(Math.max(0, length - str.length));
    return side === 'left' ? padding + str : str + padding;
}

/**
 * Replace multiple occurrences
 *
 * @param {string} str - String to process
 * @param {Object} replacements - Object with search: replace pairs
 * @returns {string} Processed string
 */
export function replaceMultiple(str, replacements) {
    if (!str) return '';
    let result = str;
    Object.entries(replacements).forEach(([search, replace]) => {
        result = result.replace(new RegExp(search, 'g'), replace);
    });
    return result;
}

/**
 * Extract numbers from string
 *
 * @param {string} str - String to process
 * @returns {Array<number>} Array of numbers
 */
export function extractNumbers(str) {
    if (!str) return [];
    const matches = str.match(/\d+/g);
    return matches ? matches.map(Number) : [];
}

/**
 * Generate random string
 *
 * @param {number} length - Length of string
 * @param {string} chars - Characters to use
 * @returns {string} Random string
 */
export function randomString(length = 8, chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

/**
 * Check if string contains substring (case-insensitive)
 *
 * @param {string} str - String to search
 * @param {string} search - Substring to find
 * @returns {boolean} True if found
 */
export function containsIgnoreCase(str, search) {
    if (!str || !search) return false;
    return str.toLowerCase().includes(search.toLowerCase());
}

/**
 * Format number with thousand separators
 *
 * @param {number} num - Number to format
 * @param {string} separator - Separator character
 * @returns {string} Formatted number
 */
export function formatNumber(num, separator = ',') {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, separator);
}
