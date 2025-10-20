
/**
 * Autocomplete feature module index
 */

import { whenAutocompleteReady, logJQueryStatus } from '../../utils/jquery-helpers.js';
import { initializeMatchSecretaryLookup } from './match-secretary-lookup.js';
import { initializeCaptainLookup } from './captain-lookup.js';
import { initializeUserLookup } from './user-lookup.js';
import { initializePartnerLookup } from './partner-lookup.js';

/**
 * Initialize all autocomplete features
 *
 * @param {jQuery|HTMLElement} [context=document] - Context to search within (for AJAX-loaded content)
 */
export function initializeAutocomplete(context = document) {
    // Log status for debugging
    if (window.location.search.includes('debug')) {
        logJQueryStatus();
    }

    // Wait for jQuery UI Autocomplete to be ready
    whenAutocompleteReady(() => {
        initializeCaptainLookup(context);
        initializePartnerLookup(context);
        initializeUserLookup(context);
        initializeMatchSecretaryLookup(context);
    });
}

// Export individual initializers for targeted use
export {
    initializeCaptainLookup,
    initializePartnerLookup,
    initializeUserLookup,
    initializeMatchSecretaryLookup
};