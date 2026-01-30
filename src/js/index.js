/**
 * RacketManager - Main Entry Point
 * Orchestrates all feature modules
 */

import { isjQueryUIAvailable } from './utils/jquery-helpers.js';
import { initializeTooltips } from './features/ui/tooltips.js';
import { initializeAcceptanceCheckbox } from './features/forms/acceptance-checkbox.js';
import { initializePasswordToggle } from './features/forms/password-toggle.js';
import { initializeCheckboxConditionals } from './features/forms/checkbox-conditionals.js';
import { initializePasswordStrength } from './features/forms/password-strength.js';
import { initializeNavigation } from './features/navigation/index.js';
import { initializeAutocomplete } from './features/autocomplete/index.js';
import { initializeTeamSelection } from './features/teams/team-selection.js';
import { initializeTeamOrder } from './features/teams/team-order.js';
import { initializeTeamsAdmin } from './features/teams/team-admin.js';
import { initializeTeamUpdate } from './features/teams/team-update.js';
import { initializeFavourites } from './features/favourites/favourites.js';
import { initializeModals } from './features/modals/index.js';
import { initializeTournamentDate } from './features/tournaments/tournament-date.js';
import { initializePopstateHandler } from './features/navigation/popstate-handler.js';
import { initializePrinting } from './features/printing/index.js';
import { initializeTabDataFeature } from './features/tabdata/index.js';
import { initializeSwitchTab } from './features/navigation/switch-tab.js';
import { initializeActivateTab } from './features/navigation/activate-tab.js';
import { initializeAccountUpdate } from './features/account/account-update.js';
import { initializeClubUpdate } from './features/club/club-update.js';
import { initializeAjaxError } from './features/ajax/handle-ajax-error.js';
import { initializePlayerUpdate } from './features/player/player-update.js';
import { initializeSetMatchStatus } from './features/match/set-match-status.js';
import { initializeLogin } from './features/auth/login.js';
import { initializeResetPassword } from './features/auth/reset-password.js';
import { initializeMessages } from './features/messages/messages.js';
import { initializeTelemetry } from './features/telemetry/index.js';
import { initializePlayerSearch } from './features/player/player-search.js';
import { initializePricing } from './features/pricing/pricing.js';
import { initializeTournamentWithdrawal } from './features/withdrawals/tournament-withdrawal.js';
import { initializeTournamentCheckout } from './features/payments/payment-checkout.js';
import { initializeStripePaymentComplete } from './features/payments/payment-complete.js';
import { initializeEntryRequest } from './features/entry/entry-request.js';
import { initializeClubAdmin } from './features/club/admin/club-roles.js';
import { initializeMatchOptions } from './features/match/match-options.js';
import { initializeUpdateMatchResults } from './features/match/update-match-results.js';
import { initializeSetMatchDate } from './features/match/set-match-date.js';
import { initializeResetMatchResult } from './features/match/reset-match-result.js';
import { initializeResetMatchScores } from './features/match/reset-match-scores.js';
import { initializeSwitchHomeAway } from './features/match/switch-home-away.js';
import { initializeTeamMatchResult } from './features/match/update-team-result.js';
import { initializeClubPlayerRequest } from './features/club/club-player-request.js';
import { initializeClubPlayerRemove } from './features/club/club-player-remove.js';
import { initializeWidgetCarousel } from './features/widgets/widget-carousel.js';
import { initializeSetCalculator } from './features/match/set-calculator.js';

// Initialize on document ready
jQuery(function () {
    // Check jQuery UI availability
    if (!isjQueryUIAvailable()) {
        console.warn('jQuery UI not loaded - some features may not work');
    }

    // Global AJAX error handler
    initializeAjaxError();

    // Telemetry (opt-in, off in production by default)
    initializeTelemetry();

    // UI Components
    initializeTooltips();

    // Forms
    initializeAcceptanceCheckbox();
    initializePasswordToggle();
    initializeCheckboxConditionals();
    // Password strength meter (uses WP core password-strength-meter)
    initializePasswordStrength();

    // Navigation
    initializeNavigation();
    initializePopstateHandler();
    initializeSwitchTab();
    // Activate specific tab panels declared in markup
    initializeActivateTab();

    // Tab Data (AJAX content loading)
    initializeTabDataFeature();

    // Autocomplete
    initializeAutocomplete();

    // Teams
    initializeTeamSelection();
    initializeTeamOrder();
    initializeTeamsAdmin();
    initializeTeamUpdate();

    // Match Set Calculator (exposes globals for inline onblur handlers)
    initializeSetCalculator();

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

    // Auth
    initializeLogin();
    initializeResetPassword();

    // Messages feature (delegated handlers)
    initializeMessages();

    // Player Search (delegated form submit)
    initializePlayerSearch();

    // Pricing & Totals
    initializePricing();

    // Payments & Withdrawals (Phase 6)
    initializeTournamentWithdrawal();
    // Stripe Checkout (Payment Element)
    initializeTournamentCheckout();

    // Stripe Payment Complete (handle return status)
    initializeStripePaymentComplete();

    // Entry Requests (modularized legacy entryRequest)
    initializeEntryRequest();

    // Club Admin (Phase 8)
    initializeClubAdmin();

    // Club Players (Remove/Request)
    initializeClubPlayerRequest();
    initializeClubPlayerRemove();

    // Match Options (Phase 10 - Stage 2)
    initializeMatchOptions();

    // Update Match Results (Phase 10 - Stage 3)
    initializeUpdateMatchResults();

    // Match Date, Reset Result, Reset Scores (Phase 10)
    initializeSetMatchDate();
    initializeResetMatchResult();
    initializeResetMatchScores();

    // Switch Home/Away (from Match Options modal)
    initializeSwitchHomeAway();

    // Teams: Update Team Result (wrapper for rubbers)
    initializeTeamMatchResult();

    // Widgets
    initializeWidgetCarousel();
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
    // Modals (some bind direct events, e.g., has-modal checkboxes)
    initializeModals();
    // Navigation-related initializers that may depend on injected markup
    initializeSwitchTab();
    initializeActivateTab();
    // Teams (ensure delegated handlers are bound after dynamic injections)
    initializeTeamsAdmin();
    initializeTeamUpdate();
    // Club Admin delegated handlers
    initializeClubAdmin();
    // Club Players delegated handlers
    initializeClubPlayerRequest();
    initializeClubPlayerRemove();
    // No need to re-initialize set-match-status; delegated handler on document covers dynamic content

    // Widgets (in case a widget is injected via AJAX)
    initializeWidgetCarousel();

    // Ensure SetCalculator globals are available after dynamic injections
    initializeSetCalculator();
});
