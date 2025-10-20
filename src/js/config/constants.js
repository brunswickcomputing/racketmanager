/**
 * Global constants and configuration
 */

// Initialize Racketmanager global object if it doesn't exist
if (!window.Racketmanager) {
    window.Racketmanager = {
        loadingModal: '#loadingModal',
        // Placeholder functions that will be replaced by modules
        tabData: function() {
            console.warn('Racketmanager.tabData not yet initialized');
        },
        tabDataLink: function() {
            console.warn('Racketmanager.tabDataLink not yet initialized');
        },
        partnerModal: function() {
            console.warn('Racketmanager.partnerModal not yet initialized');
        },
        setEventPrice: function() {
            console.warn('Racketmanager.setEventPrice not yet initialized');
        },
        clearPrice: function() {
            console.warn('Racketmanager.clearPrice not yet initialized');
        },
        printScoreCard: function() {
            console.warn('Racketmanager.printScoreCard not yet initialized');
        }
    };
}

export const LOADING_MODAL = '#loadingModal';
export const AJAX_TIMEOUT = 30000; // 30 seconds

// Export global object reference
export const Racketmanager = window.Racketmanager;