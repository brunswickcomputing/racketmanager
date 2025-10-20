/**
 * Shared printing utilities
 */

/**
 * Window configuration for print dialogs
 */
export const PRINT_WINDOW_CONFIG = {
    width: 800,
    height: 600,
    scrollbars: 'yes',
    resizable: 'yes',
    toolbar: 'no',
    menubar: 'no',
    location: 'no',
    status: 'no'
};

/**
 * Open a print window with standard configuration
 *
 * @param {string} url - URL to open
 * @param {string} windowName - Name for the window
 * @param {Object} config - Optional window configuration override
 * @returns {Window|null} The opened window or null if blocked
 */
export function openPrintWindow(url, windowName = 'printWindow', config = {}) {
    const windowConfig = { ...PRINT_WINDOW_CONFIG, ...config };
    const configString = Object.entries(windowConfig)
        .map(([key, value]) => `${key}=${value}`)
        .join(',');

    const printWindow = window.open(url, windowName, configString);

    if (printWindow) {
        printWindow.focus();
        return printWindow;
    }

    return null;
}

/**
 * Auto-print when window loads
 *
 * @param {Window} printWindow - The print window
 * @param {Function} callback - Optional callback after print dialog closes
 */
export function autoPrint(printWindow, callback = null) {
    if (!printWindow) return;

    printWindow.onload = function() {
        printWindow.print();

        if (callback && typeof callback === 'function') {
            callback();
        }
    };
}

/**
 * Build a print URL
 *
 * @param {string} baseUrl - Base URL
 * @param {string} path - Path to append
 * @param {Object} params - Optional query parameters
 * @returns {string} Complete URL
 */
export function buildPrintUrl(baseUrl, path, params = {}) {
    let url = `${baseUrl}${path}`;

    const queryString = Object.entries(params)
        .filter(([_, value]) => value !== null && value !== undefined)
        .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
        .join('&');

    if (queryString) {
        url += `?${queryString}`;
    }

    return url;
}

/**
 * Show error message for print failures
 *
 * @param {string} selector - jQuery selector for error container
 * @param {string} message - Error message to display
 */
export function showPrintError(selector, message) {
    const $container = jQuery(selector);

    if ($container.length) {
        $container
            .text(message)
            .addClass('alert alert-danger')
            .removeClass('d-none')
            .show();

        // Auto-hide after 5 seconds
        setTimeout(() => {
            $container.fadeOut();
        }, 5000);
    }
}

/**
 * Handle popup blocker detection
 *
 * @param {Window|null} printWindow - The window that was attempted to open
 * @param {string} errorSelector - Selector for error message container
 * @returns {boolean} True if popup was blocked
 */
export function handlePopupBlocker(printWindow, errorSelector) {
    if (!printWindow) {
        showPrintError(
            errorSelector,
            'Please allow popups for this site to print documents.'
        );
        return true;
    }
    return false;
}

/**
 * Prepare print window with loading state
 *
 * @param {string} selector - Loading indicator selector
 */
export function showPrintLoading(selector) {
    jQuery(selector).show();
}

/**
 * Hide print loading state
 *
 * @param {string} selector - Loading indicator selector
 */
export function hidePrintLoading(selector) {
    jQuery(selector).hide();
}

/**
 * Generate print-friendly timestamp
 *
 * @returns {string} Formatted timestamp
 */
export function getPrintTimestamp() {
    const now = new Date();
    return now.toLocaleString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Add print metadata to document
 *
 * @param {Document} doc - Document to add metadata to
 * @param {Object} metadata - Metadata object
 */
export function addPrintMetadata(doc, metadata = {}) {
    const metaTimestamp = doc.createElement('meta');
    metaTimestamp.name = 'print-timestamp';
    metaTimestamp.content = getPrintTimestamp();
    doc.head.appendChild(metaTimestamp);

    Object.entries(metadata).forEach(([key, value]) => {
        const meta = doc.createElement('meta');
        meta.name = `print-${key}`;
        meta.content = value;
        doc.head.appendChild(meta);
    });
}

/**
 * Print multiple items in sequence
 *
 * @param {Array<string>} urls - Array of URLs to print
 * @param {number} delay - Delay between prints in ms
 */
export function printSequence(urls, delay = 1000) {
    urls.forEach((url, index) => {
        setTimeout(() => {
            const printWindow = openPrintWindow(url, `printWindow${index}`);
            if (printWindow) {
                autoPrint(printWindow);
            }
        }, index * delay);
    });
}

/**
 * Create print preview without opening new window
 *
 * @param {string} content - HTML content to preview
 * @param {string} containerId - Container element ID
 */
export function createPrintPreview(content, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = content;
    container.classList.add('print-preview');
}

/**
 * Convert element to print-friendly format
 *
 * @param {string} selector - Element selector
 * @returns {string} Print-friendly HTML
 */
export function elementToPrintHtml(selector) {
    const element = document.querySelector(selector);
    if (!element) return '';

    const clone = element.cloneNode(true);

    // Remove non-printable elements
    clone.querySelectorAll('.no-print, .print-hide').forEach(el => el.remove());

    // Add print-specific classes
    clone.classList.add('print-content');

    return clone.outerHTML;
}

/**
 * Check if browser supports printing
 *
 * @returns {boolean} True if printing is supported
 */
export function isPrintSupported() {
    return typeof window.print === 'function';
}

/**
 * Get print stylesheet
 *
 * @returns {string} URL to print stylesheet
 */
export function getPrintStylesheet() {
    return `${window.location.origin}/wp-content/plugins/racketmanager/css/print.css`;
}

/**
 * Add print stylesheet to window
 *
 * @param {Window} printWindow - Window to add stylesheet to
 */
export function addPrintStylesheet(printWindow) {
    if (!printWindow || !printWindow.document) return;

    const link = printWindow.document.createElement('link');
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = getPrintStylesheet();

    printWindow.document.head.appendChild(link);
}
