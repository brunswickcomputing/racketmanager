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

/**
 * Set loading state for modal
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @param {boolean} loading - Whether to show loading state
 */
export function setModalLoading(modalSelector, loading) {
    const $modal = jQuery(modalSelector);

    if (loading) {
        $modal.addClass('is-loading');
        $modal.find('.modal-content').css('opacity', '0.5');
    } else {
        $modal.removeClass('is-loading');
        $modal.find('.modal-content').css('opacity', '1');
    }
}

/**
 * Set modal title
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @param {string} title - New title text
 */
export function setModalTitle(modalSelector, title) {
    jQuery(modalSelector).find('.modal-title').text(title);
}

/**
 * Set modal body content
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @param {string} content - HTML content
 */
export function setModalContent(modalSelector, content) {
    jQuery(modalSelector).find('.modal-body').html(content);
}

/**
 * Clear modal form
 *
 * @param {string} modalSelector - jQuery selector for modal
 */
export function clearModalForm(modalSelector) {
    const $modal = jQuery(modalSelector);
    $modal.find('input[type="text"], input[type="number"], textarea').val('');
    $modal.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
    $modal.find('select').prop('selectedIndex', 0);
}

/**
 * Show modal error message
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @param {string} message - Error message
 */
export function showModalError(modalSelector, message) {
    const $modal = jQuery(modalSelector);
    const $errorContainer = $modal.find('.modal-error');

    if ($errorContainer.length === 0) {
        $modal.find('.modal-body').prepend(
            '<div class="alert alert-danger modal-error">' + message + '</div>'
        );
    } else {
        $errorContainer.html(message).show();
    }
}

/**
 * Hide modal error message
 *
 * @param {string} modalSelector - jQuery selector for modal
 */
export function hideModalError(modalSelector) {
    jQuery(modalSelector).find('.modal-error').hide();
}

/**
 * Attach modal event handlers
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @param {Object} handlers - Event handlers object
 */
export function attachModalHandlers(modalSelector, handlers = {}) {
    const $modal = jQuery(modalSelector);

    if (handlers.onShow) {
        $modal.on('show.bs.modal', handlers.onShow);
    }

    if (handlers.onShown) {
        $modal.on('shown.bs.modal', handlers.onShown);
    }

    if (handlers.onHide) {
        $modal.on('hide.bs.modal', handlers.onHide);
    }

    if (handlers.onHidden) {
        $modal.on('hidden.bs.modal', handlers.onHidden);
    }
}

/**
 * Detach modal event handlers
 *
 * @param {string} modalSelector - jQuery selector for modal
 */
export function detachModalHandlers(modalSelector) {
    const $modal = jQuery(modalSelector);
    $modal.off('show.bs.modal shown.bs.modal hide.bs.modal hidden.bs.modal');
}

/**
 * Check if modal is currently visible
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @returns {boolean} True if modal is visible
 */
export function isModalVisible(modalSelector) {
    const $modal = jQuery(modalSelector);
    return $modal.hasClass('show');
}

/**
 * Get modal data attribute
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @param {string} key - Data attribute key
 * @returns {string|null} Data attribute value
 */
export function getModalData(modalSelector, key) {
    return jQuery(modalSelector).data(key);
}

/**
 * Set modal data attribute
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @param {string} key - Data attribute key
 * @param {*} value - Value to set
 */
export function setModalData(modalSelector, key, value) {
    jQuery(modalSelector).data(key, value);
}

/**
 * Enable/disable modal submit button
 *
 * @param {string} modalSelector - jQuery selector for modal
 * @param {boolean} enabled - Whether button should be enabled
 */
export function setModalSubmitEnabled(modalSelector, enabled) {
    const $modal = jQuery(modalSelector);
    $modal.find('.btn-primary, [type="submit"]').prop('disabled', !enabled);
}

/**
 * Create a confirmation modal
 *
 * @param {Object} options - Modal configuration
 * @returns {Promise} Promise that resolves on confirm, rejects on cancel
 */
export function confirmModal(options = {}) {
    const defaults = {
        title: 'Confirm',
        message: 'Are you sure?',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        confirmClass: 'btn-primary',
        cancelClass: 'btn-secondary'
    };

    const config = { ...defaults, ...options };

    return new Promise((resolve, reject) => {
        const modalHtml = `
            <div class="modal fade" id="confirmModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${config.title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${config.message}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn ${config.cancelClass}" data-bs-dismiss="modal">
                                ${config.cancelText}
                            </button>
                            <button type="button" class="btn ${config.confirmClass}" id="confirmBtn">
                                ${config.confirmText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing confirm modal if any
        jQuery('#confirmModal').remove();

        // Add to body
        jQuery('body').append(modalHtml);

        const $modal = jQuery('#confirmModal');
        const modal = new bootstrap.Modal($modal[0]);

        // Handle confirm
        jQuery('#confirmBtn').on('click', function() {
            modal.hide();
            resolve(true);
        });

        // Handle cancel/close
        $modal.on('hidden.bs.modal', function() {
            $modal.remove();
            reject(false);
        });

        modal.show();
    });
}
