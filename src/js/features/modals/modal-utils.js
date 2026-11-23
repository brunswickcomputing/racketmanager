/**
 * Modal utility functions
 * Shared utilities for modal operations
 */

/**
 * Show Bootstrap modal
 *
 * @param {string} modalSelector - jQuery selector for modal
 */
export function showModal(modalSelector) {
    const $modal = jQuery(modalSelector);
    if ($modal.length) {
        const modal = new bootstrap.Modal($modal[0]);
        modal.show();
    }
}

/**
 * Hide Bootstrap modal
 *
 * @param {string} modalSelector - jQuery selector for modal
 */
export function hideModal(modalSelector) {
    const $modal = jQuery(modalSelector);
    if ($modal.length) {
        const modal = bootstrap.Modal.getInstance($modal[0]);
        if (modal) {
            modal.hide();
        }
    }
}
