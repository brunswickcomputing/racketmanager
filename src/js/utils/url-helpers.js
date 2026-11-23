
/**
 * URL manipulation and sanitisation utilities
 */

/**
 * Sanitise URL parameter by removing unwanted characters
 * and converting to URL-friendly format
 *
 * @param {string} value - Value to sanitise
 * @param {boolean} hyphens - Whether to replace hyphens in the value
 * @returns {string} Sanitized value
 */
export function sanitizeUrlParam(value, hyphens = true) {
    if (!value) return '';

    let sanitized = value.toString();

    // Remove unwanted characters, only accept alphanumeric, spaces, and hyphens
    sanitized = sanitized.replaceAll(/[^A-Za-z0-9 -]/g, '');

    // Replace multiple spaces with a single space
    sanitized = sanitized.replaceAll(/\s{2,}/g, ' ');

    // Replace hyphens with underscores
    if (hyphens) {
        sanitized = sanitized.replaceAll('-', '_');
    }

    // Replace spaces with hyphens
    sanitized = sanitized.replaceAll(/\s/g, '-');

    return sanitized;
}
