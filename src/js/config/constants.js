/**
 * Global constants and configuration
 */

// Ensure Racketmanager global exists and provides loadingModal only (no legacy placeholders)
if (!window.Racketmanager) {
    window.Racketmanager = { loadingModal: '#loadingModal' };
} else if (!window.Racketmanager.loadingModal) {
    window.Racketmanager.loadingModal = '#loadingModal';
}

export const LOADING_MODAL = '#loadingModal';
export const AJAX_TIMEOUT = 30000; // 30 seconds

// Export global object reference
export const Racketmanager = window.Racketmanager;