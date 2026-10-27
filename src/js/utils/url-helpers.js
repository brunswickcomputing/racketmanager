
/**
 * URL manipulation and sanitization utilities
 */

/**
 * Sanitize URL parameter by removing unwanted characters
 * and converting to URL-friendly format
 *
 * @param {string} value - Value to sanitize
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

/**
 * Build a complete URL from components
 *
 * @param {string} protocol - Protocol (http, https)
 * @param {string} host - Hostname
 * @param {string} path - URL path
 * @param {Object} params - Query parameters
 * @returns {string} Complete URL
 */
export function buildUrl(protocol, host, path, params = {}) {
    let url = `${protocol}//${host}${path}`;

    const queryString = buildQueryString(params);
    if (queryString) {
        url += `?${queryString}`;
    }

    return url;
}

/**
 * Build query string from object
 *
 * @param {Object} params - Query parameters
 * @returns {string} Query string
 */
export function buildQueryString(params) {
    return Object.entries(params)
        .filter(([, value]) => value !== null && value !== undefined)
        .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
        .join('&');
}

/**
 * Get current location URL
 *
 * @returns {string} Current URL
 */
export function getCurrentUrl() {
    return globalThis.location.href;
}

/**
 * Get URL base (protocol and host)
 *
 * @returns {string} Base URL
 */
export function getUrlBase() {
    return `${globalThis.location.protocol}//${globalThis.location.host}`;
}

/**
 * Navigate to URL
 *
 * @param {string} url - URL to navigate to
 * @param {boolean} replace - Whether to replace history or push
 */
export function navigateTo(url, replace = false) {
    if (replace) {
        globalThis.location.replace(url);
    } else {
        globalThis.location.href = url;
    }
}

/**
 * Reload current page
 */
export function reloadPage() {
    globalThis.location.reload();
}

/**
 * Parse query string into object
 *
 * @param {string} queryString - Query string (with or without ?)
 * @returns {Object} Parsed parameters
 */
export function parseQueryString(queryString) {
    if (!queryString) return {};

    const params = {};
    const search = queryString.replace(/^\?/, '');

    for (const param of search.split('&')) {
        const [key, value] = param.split('=');
        if (key) {
            params[decodeURIComponent(key)] = value ? decodeURIComponent(value) : '';
        }
    }

    return params;
}

/**
 * Get query parameter value by name
 *
 * @param {string} name - Parameter name
 * @param {string} url - URL to parse (defaults to current URL)
 * @returns {string|null} Parameter value or null
 */
export function getQueryParam(name, url = globalThis.location.href) {
    const urlObj = new URL(url);
    return urlObj.searchParams.get(name);
}

/**
 * Add or update query parameter
 *
 * @param {string} name - Parameter name
 * @param {string} value - Parameter value
 * @param {string} url - Base URL (defaults to current)
 * @returns {string} Updated URL
 */
export function setQueryParam(name, value, url = globalThis.location.href) {
    const urlObj = new URL(url);
    urlObj.searchParams.set(name, value);
    return urlObj.toString();
}

/**
 * Remove query parameter
 *
 * @param {string} name - Parameter name
 * @param {string} url - Base URL (defaults to current)
 * @returns {string} Updated URL
 */
export function removeQueryParam(name, url = globalThis.location.href) {
    const urlObj = new URL(url);
    urlObj.searchParams.delete(name);
    return urlObj.toString();
}

/**
 * Convert string to SEO-friendly URL slug
 *
 * @param {string} str - String to convert
 * @returns {string} URL slug
 */
export function slugify(str) {
    if (!str) return '';

    return str
        .toString()
        .toLowerCase()
        .trim()
        .replaceAll(/\s+/g, '-')        // Replace spaces with '-'
        .replaceAll(/[^\w-]+/g, '')     // Remove all non-word chars
        .replaceAll(/-{2,}/g, '-')      // Replace multiple '-' with single '-'
        .replace(/^-+/, '')             // Trim '-' from the start
        .replace(/-$/, '');             // Trim single remaining '-' from the end (runs already collapsed above)
}

/**
 * Check if URL is absolute
 *
 * @param {string} url - URL to check
 * @returns {boolean} True if absolute
 */
export function isAbsoluteUrl(url) {
    return /^https?:\/\//i.test(url);
}

/**
 * Check if the URL is external
 *
 * @param {string} url - URL to check
 * @returns {boolean} True if external
 */
export function isExternalUrl(url) {
    if (!isAbsoluteUrl(url)) return false;

    try {
        return new URL(url).hostname !== globalThis.location.hostname;
    } catch {
        return false;
    }
}

/**
 * Encode URI component with special character handling
 *
 * @param {string} str - String to encode
 * @returns {string} Encoded string
 */
export function encodeUrlComponent(str) {
    return encodeURIComponent(str)
        .replaceAll(/[!'()*]/g, c => `%${c.charCodeAt(0).toString(16).toUpperCase()}`);
}

/**
 * Build friendly URL path
 *
 * @param {Array<string>} segments - Path segments
 * @returns {string} URL path
 */
export function buildPath(...segments) {
    const path = segments
        .filter(Boolean)
        .map(segment => segment.toString().toLowerCase())
        .join('/');
    return `/${path}/`;
}

/**
 * Extract filename from URL
 *
 * @param {string} url - URL
 * @returns {string} Filename
 */
export function getFilenameFromUrl(url) {
    if (!url) return '';

    const pathname = new URL(url).pathname;
    return pathname.substring(pathname.lastIndexOf('/') + 1);
}

/**
 * Update URL without page reload
 *
 * @param {string} url - New URL
 * @param {string} title - Page title
 * @param {Object} state - History state object
 */
export function updateUrlWithoutReload(url, title = '', state = {}) {
    globalThis.history.pushState(state, title, url);
}

/**
 * Replace URL without page reload
 *
 * @param {string} url - New URL
 * @param {string} title - Page title
 * @param {Object} state - History state object
 */
export function replaceUrlWithoutReload(url, title = '', state = {}) {
    globalThis.history.replaceState(state, title, url);
}
