/**
 * Set Match Rubber Status - Modularized bridge
 * Provides a modular implementation and a backward-compatible global hook
 * for templates calling Racketmanager.setMatchRubberStatus(this).
 */

import { setRubberStatus } from './rubber-status-modal.js';

/**
 * Modular function that mirrors legacy Racketmanager.setMatchRubberStatus
 * @param {HTMLElement} link - clicked button inside the rubber status modal
 */
export function setMatchRubberStatus(link) {
    return setRubberStatus(link);
}

/**
 * Initialize and expose backward-compatible global API
 * Also wires a delegated data-action handler for future usage.
 */
export function initializeSetMatchRubberStatus() {
    // Expose legacy API on global Racketmanager, preserving other properties if present
    globalThis.Racketmanager = globalThis.Racketmanager || {};
    // Only define if not already defined or overwrite to ensure it uses modular code
    globalThis.Racketmanager.setMatchRubberStatus = setMatchRubberStatus;

    // Optional delegated handler for data-action usage
    jQuery(document)
        .off('click.racketmanager.setMatchRubberStatus', '[data-action="set-match-rubber-status"]')
        .on('click.racketmanager.setMatchRubberStatus', '[data-action="set-match-rubber-status"]', function (e) {
            e.preventDefault();
            return setMatchRubberStatus(this);
        });
}
