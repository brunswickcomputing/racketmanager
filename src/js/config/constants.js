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

// Global flag to disable legacy JS by default (can be overridden via inline script before legacy loads)
if (typeof window.RACKETMANAGER_DISABLE_LEGACY === 'undefined') {
    window.RACKETMANAGER_DISABLE_LEGACY = true;
}

// Export global object reference
export const Racketmanager = window.Racketmanager;