
/**
 * Printing feature module index
 */

import { initializePrintScoreCard, attachToGlobal } from './print-scorecard.js';
import { initializeMatchCardPrint } from './print-match-card.js';

export function initializePrinting() {
    initializePrintScoreCard();
    initializeMatchCardPrint();
    attachToGlobal(); // For backward compatibility with existing code
}

// Re-export utilities for direct use if needed
export * from './print-utils.js';
