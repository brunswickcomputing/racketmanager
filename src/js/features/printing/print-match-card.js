/**
 * Match card printing functionality (delegated handlers)
 */

import { printScoreCard } from './print-scorecard.js';

/**
 * Initialize match card print buttons
 */
export function initializeMatchCardPrint() {
    // Delegated handlers with namespace to avoid duplicates
    jQuery(document)
        .off('click.racketmanager.print', '[data-print-match-card]')
        .on('click.racketmanager.print', '[data-print-match-card]', function(e) {
            const matchId = jQuery(this).data('print-match-card');
            return printScoreCard(e, matchId);
        });
}
