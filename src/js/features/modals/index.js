/**
 * Modal feature module index
 */

import { initializeNoModalCheckboxes } from './no-modal-checkboxes.js';
import { initializeHasModalCheckboxes } from './has-modal-checkboxes.js';

export function initializeModals() {
    initializeNoModalCheckboxes();
    initializeHasModalCheckboxes();
}

// Re-export utilities for direct use
export * from './modal-utils.js';
export * from './no-modal-checkboxes.js';
export * from './has-modal-checkboxes.js';
