/**
 * Modal feature module index
 */

import { initializeNoModalCheckboxes } from './no-modal-checkboxes.js';
import { initializeHasModalCheckboxes } from './has-modal-checkboxes.js';

import { initializePOModal } from './po-modal.js';
import { initializeSetPurchaseOrder } from './po-set-purchase-order.js';
import { initializeMatchStatusModal } from './match-status-modal.js';
import { initializeRubberStatusModal } from '../match/rubber-status-modal.js';

export function initializeModals() {
    initializeNoModalCheckboxes();
    initializeHasModalCheckboxes();
    initializePOModal();
    initializeSetPurchaseOrder();
    initializeMatchStatusModal();
    initializeRubberStatusModal();
}

// Re-export utilities for direct use
export * from './modal-utils.js';
export * from './no-modal-checkboxes.js';
export * from './has-modal-checkboxes.js';
