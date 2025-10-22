
/**
 * Printing feature module index
 */

import { initializePrintScoreCard } from './print-scorecard.js';
import { initializeMatchCardPrint } from './print-match-card.js';

export function initializePrinting() {
    initializePrintScoreCard();
    initializeMatchCardPrint();
}

// Re-export utilities for direct use if needed
export * from './print-utils.js';
