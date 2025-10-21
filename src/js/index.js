/**
 * RacketManager - Main Entry Point
 * Orchestrates all feature modules
 */

import { isjQueryUIAvailable } from './utils/jquery-helpers.js';
import { initializeTooltips } from './features/ui/tooltips.js';
import { initializeAcceptanceCheckbox } from './features/forms/acceptance-checkbox.js';
import { initializePasswordToggle } from './features/forms/password-toggle.js';
import { initializeCheckboxConditionals } from './features/forms/checkbox-conditionals.js';
import { initializeNavigation } from './features/navigation/index.js';
import { initializeAutocomplete } from './features/autocomplete/index.js';
import { initializeTeamSelection } from './features/teams/team-selection.js';
import { initializeFavourites } from './features/favourites/favourites.js';
import { initializeModals } from './features/modals/index.js';
import { initializeTournamentDate } from './features/tournaments/tournament-date.js';
import { initializePopstateHandler } from './features/navigation/popstate-handler.js';
import { initializePrinting } from './features/printing/index.js';
import { initializeTabDataFeature } from './features/tabdata/index.js';
import { initializeAccountUpdate } from './features/account/account-update.js';
import { initializeClubUpdate } from './features/club/club-update.js';
import { initializeAjaxError } from './features/ajax/handle-ajax-error.js';
import { initializePlayerUpdate } from './features/player/player-update.js';
import { initializeSetMatchStatus } from './features/match/set-match-status.js';

// Initialize on document ready
jQuery(function () {
    // Check jQuery UI availability
    if (!isjQueryUIAvailable()) {
        console.warn('jQuery UI not loaded - some features may not work');
    }

    // Global AJAX error handler
    initializeAjaxError();

    // UI Components
    initializeTooltips();

    // Forms
    initializeAcceptanceCheckbox();
    initializePasswordToggle();
    initializeCheckboxConditionals();

    // Navigation
    initializeNavigation();
    initializePopstateHandler();

    // Tab Data (AJAX content loading)
    initializeTabDataFeature();

    // Autocomplete
    initializeAutocomplete();

    // Teams
    initializeTeamSelection();

    // Modals
    initializeModals();

    // Tournaments
    initializeTournamentDate();

    // Favourites
    initializeFavourites();

    // Printing
    initializePrinting();

    // Account Update
    initializeAccountUpdate();
    // Club Update
    initializeClubUpdate();
    // Player Update
    initializePlayerUpdate();

    // Match Status (delegated data-action handler)
    initializeSetMatchStatus();
});

// Re-initialize after AJAX
jQuery(document).ajaxComplete(function () {
    // Ensure global error handler remains available
    initializeAjaxError();

    initializeAutocomplete();
    initializeFavourites();
    initializeNavigation();
    initializePopstateHandler();
    initializeTournamentDate();
    // Ensure feature handlers are present after dynamic content loads
    initializeAccountUpdate();
    initializeClubUpdate();
    initializePlayerUpdate();
    // No need to re-initialize set-match-status; delegated handler on document covers dynamic content
});
