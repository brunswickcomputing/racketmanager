
/**
 * Navigation feature module index
 * Coordinates all navigation sub-modules
 */

import { initializeArchiveNavigation } from './archive-navigation.js';
import { initializeCompetitionNavigation } from './competition-navigation.js';
import { initializeTournamentNavigation } from './tournament-navigation.js';
import { initializeDailyMatches } from './daily-matches.js';
import { initializeMatchDay } from './match-day.js';
import { initializeMatchViewer } from './match-viewer.js';

// Export individual initializers
export { initializeMatchViewer, attachMatchViewEventListeners } from './match-viewer.js';

export function initializeNavigation() {
    initializeArchiveNavigation();
    initializeCompetitionNavigation();
    initializeTournamentNavigation();
    initializeDailyMatches();
    initializeMatchDay();
    initializeMatchViewer();
}
