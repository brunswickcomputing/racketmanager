/**
 * Tests for RacketManager Main Entry Point (index.js)
 */

jest.mock('../../src/js/features/ui/tooltips.js', () => ({
    initializeTooltips: jest.fn()
}));
jest.mock('../../src/js/features/forms/acceptance-checkbox.js', () => ({
    initializeAcceptanceCheckbox: jest.fn()
}));
jest.mock('../../src/js/features/forms/password-toggle.js', () => ({
    initializePasswordToggle: jest.fn()
}));
jest.mock('../../src/js/features/forms/checkbox-conditionals.js', () => ({
    initializeCheckboxConditionals: jest.fn()
}));
jest.mock('../../src/js/features/forms/password-strength.js', () => ({
    initializePasswordStrength: jest.fn()
}));
jest.mock('../../src/js/features/navigation/index.js', () => ({
    initializeNavigation: jest.fn()
}));
jest.mock('../../src/js/features/autocomplete/index.js', () => ({
    initializeAutocomplete: jest.fn()
}));
jest.mock('../../src/js/features/teams/team-selection.js', () => ({
    initializeTeamSelection: jest.fn()
}));
jest.mock('../../src/js/features/teams/team-order.js', () => ({
    initializeTeamOrder: jest.fn()
}));
jest.mock('../../src/js/features/teams/team-admin.js', () => ({
    initializeTeamsAdmin: jest.fn()
}));
jest.mock('../../src/js/features/teams/team-update.js', () => ({
    initializeTeamUpdate: jest.fn()
}));
jest.mock('../../src/js/features/favourites/favourites.js', () => ({
    initializeFavourites: jest.fn()
}));
jest.mock('../../src/js/features/modals/index.js', () => ({
    initializeModals: jest.fn()
}));
jest.mock('../../src/js/features/tournaments/tournament-date.js', () => ({
    initializeTournamentDate: jest.fn()
}));
jest.mock('../../src/js/features/navigation/popstate-handler.js', () => ({
    initializePopstateHandler: jest.fn()
}));
jest.mock('../../src/js/features/printing/index.js', () => ({
    initializePrinting: jest.fn()
}));
jest.mock('../../src/js/features/tabdata/index.js', () => ({
    initializeTabDataFeature: jest.fn()
}));
jest.mock('../../src/js/features/navigation/switch-tab.js', () => ({
    initializeSwitchTab: jest.fn()
}));
jest.mock('../../src/js/features/navigation/activate-tab.js', () => ({
    initializeActivateTab: jest.fn()
}));
jest.mock('../../src/js/features/account/account-update.js', () => ({
    initializeAccountUpdate: jest.fn()
}));
jest.mock('../../src/js/features/club/club-update.js', () => ({
    initializeClubUpdate: jest.fn()
}));
jest.mock('../../src/js/features/ajax/handle-ajax-error.js', () => ({
    initializeAjaxError: jest.fn()
}));
jest.mock('../../src/js/features/player/player-update.js', () => ({
    initializePlayerUpdate: jest.fn()
}));
jest.mock('../../src/js/features/match/set-match-status.js', () => ({
    initializeSetMatchStatus: jest.fn()
}));
jest.mock('../../src/js/features/auth/login.js', () => ({
    initializeLogin: jest.fn()
}));
jest.mock('../../src/js/features/auth/reset-password.js', () => ({
    initializeResetPassword: jest.fn()
}));
jest.mock('../../src/js/features/messages/messages.js', () => ({
    initializeMessages: jest.fn()
}));
jest.mock('../../src/js/features/telemetry/index.js', () => ({
    initializeTelemetry: jest.fn()
}));
jest.mock('../../src/js/features/player/player-search.js', () => ({
    initializePlayerSearch: jest.fn()
}));
jest.mock('../../src/js/features/pricing/pricing.js', () => ({
    initializePricing: jest.fn()
}));
jest.mock('../../src/js/features/withdrawals/tournament-withdrawal.js', () => ({
    initializeTournamentWithdrawal: jest.fn()
}));
jest.mock('../../src/js/features/payments/payment-checkout.js', () => ({
    initializeTournamentCheckout: jest.fn()
}));
jest.mock('../../src/js/features/payments/payment-complete.js', () => ({
    initializeStripePaymentComplete: jest.fn()
}));
jest.mock('../../src/js/features/entry/entry-request.js', () => ({
    initializeEntryRequest: jest.fn()
}));
jest.mock('../../src/js/features/club/admin/club-roles.js', () => ({
    initializeClubAdmin: jest.fn()
}));
jest.mock('../../src/js/features/match/match-options.js', () => ({
    initializeMatchOptions: jest.fn()
}));
jest.mock('../../src/js/features/match/update-match-results.js', () => ({
    initializeUpdateMatchResults: jest.fn()
}));
jest.mock('../../src/js/features/match/set-match-date.js', () => ({
    initializeSetMatchDate: jest.fn()
}));
jest.mock('../../src/js/features/match/reset-match-result.js', () => ({
    initializeResetMatchResult: jest.fn()
}));
jest.mock('../../src/js/features/match/reset-match-scores.js', () => ({
    initializeResetMatchScores: jest.fn()
}));
jest.mock('../../src/js/features/match/switch-home-away.js', () => ({
    initializeSwitchHomeAway: jest.fn()
}));
jest.mock('../../src/js/features/match/update-team-result.js', () => ({
    initializeTeamMatchResult: jest.fn()
}));
jest.mock('../../src/js/features/club/club-player-request.js', () => ({
    initializeClubPlayerRequest: jest.fn()
}));
jest.mock('../../src/js/features/club/club-player-remove.js', () => ({
    initializeClubPlayerRemove: jest.fn()
}));
jest.mock('../../src/js/features/widgets/widget-carousel.js', () => ({
    initializeWidgetCarousel: jest.fn()
}));
jest.mock('../../src/js/features/match/set-calculator.js', () => ({
    initializeSetCalculator: jest.fn()
}));
jest.mock('../../src/js/features/autocomplete/autocomplete-helpers.js', () => ({
    isJQueryUIAvailable: jest.fn()
}));

import { initializeTooltips } from '../../src/js/features/ui/tooltips.js';
import { initializeAcceptanceCheckbox } from '../../src/js/features/forms/acceptance-checkbox.js';
import { initializePasswordToggle } from '../../src/js/features/forms/password-toggle.js';
import { initializeCheckboxConditionals } from '../../src/js/features/forms/checkbox-conditionals.js';
import { initializePasswordStrength } from '../../src/js/features/forms/password-strength.js';
import { initializeNavigation } from '../../src/js/features/navigation/index.js';
import { initializeAutocomplete } from '../../src/js/features/autocomplete/index.js';
import { initializeTeamSelection } from '../../src/js/features/teams/team-selection.js';
import { initializeTeamOrder } from '../../src/js/features/teams/team-order.js';
import { initializeTeamsAdmin } from '../../src/js/features/teams/team-admin.js';
import { initializeTeamUpdate } from '../../src/js/features/teams/team-update.js';
import { initializeFavourites } from '../../src/js/features/favourites/favourites.js';
import { initializeModals } from '../../src/js/features/modals/index.js';
import { initializeTournamentDate } from '../../src/js/features/tournaments/tournament-date.js';
import { initializePopstateHandler } from '../../src/js/features/navigation/popstate-handler.js';
import { initializePrinting } from '../../src/js/features/printing/index.js';
import { initializeTabDataFeature } from '../../src/js/features/tabdata/index.js';
import { initializeSwitchTab } from '../../src/js/features/navigation/switch-tab.js';
import { initializeActivateTab } from '../../src/js/features/navigation/activate-tab.js';
import { initializeAccountUpdate } from '../../src/js/features/account/account-update.js';
import { initializeClubUpdate } from '../../src/js/features/club/club-update.js';
import { initializeAjaxError } from '../../src/js/features/ajax/handle-ajax-error.js';
import { initializePlayerUpdate } from '../../src/js/features/player/player-update.js';
import { initializeSetMatchStatus } from '../../src/js/features/match/set-match-status.js';
import { initializeLogin } from '../../src/js/features/auth/login.js';
import { initializeResetPassword } from '../../src/js/features/auth/reset-password.js';
import { initializeMessages } from '../../src/js/features/messages/messages.js';
import { initializeTelemetry } from '../../src/js/features/telemetry/index.js';
import { initializePlayerSearch } from '../../src/js/features/player/player-search.js';
import { initializePricing } from '../../src/js/features/pricing/pricing.js';
import { initializeTournamentWithdrawal } from '../../src/js/features/withdrawals/tournament-withdrawal.js';
import { initializeTournamentCheckout } from '../../src/js/features/payments/payment-checkout.js';
import { initializeStripePaymentComplete } from '../../src/js/features/payments/payment-complete.js';
import { initializeEntryRequest } from '../../src/js/features/entry/entry-request.js';
import { initializeClubAdmin } from '../../src/js/features/club/admin/club-roles.js';
import { initializeMatchOptions } from '../../src/js/features/match/match-options.js';
import { initializeUpdateMatchResults } from '../../src/js/features/match/update-match-results.js';
import { initializeSetMatchDate } from '../../src/js/features/match/set-match-date.js';
import { initializeResetMatchResult } from '../../src/js/features/match/reset-match-result.js';
import { initializeResetMatchScores } from '../../src/js/features/match/reset-match-scores.js';
import { initializeSwitchHomeAway } from '../../src/js/features/match/switch-home-away.js';
import { initializeTeamMatchResult } from '../../src/js/features/match/update-team-result.js';
import { initializeClubPlayerRequest } from '../../src/js/features/club/club-player-request.js';
import { initializeClubPlayerRemove } from '../../src/js/features/club/club-player-remove.js';
import { initializeWidgetCarousel } from '../../src/js/features/widgets/widget-carousel.js';
import { initializeSetCalculator } from '../../src/js/features/match/set-calculator.js';
import { isJQueryUIAvailable } from '../../src/js/features/autocomplete/autocomplete-helpers.js';

describe('RacketManager Main Entry Point (index.js)', () => {
    let warnSpy;

    beforeEach(() => {
        jest.clearAllMocks();
        // Remove existing ajaxComplete event handlers on document so tests don't accumulate listeners
        jQuery(document).off('ajaxComplete');
        warnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {});
    });

    afterEach(() => {
        warnSpy.mockRestore();
        jQuery(document).off('ajaxComplete');
    });

    function loadAndExecuteIndex() {
        let readyCallback;
        const origReady = jQuery.fn.ready;
        jQuery.fn.ready = function (fn) {
            readyCallback = fn;
            return this;
        };

        try {
            jest.isolateModules(() => {
                require('../../src/js/index.js');
            });
        } finally {
            jQuery.fn.ready = origReady;
        }

        if (typeof readyCallback === 'function') {
            readyCallback(jQuery);
        }
    }

    test('initializes all submodules on document ready when jQuery UI is available', () => {
        isJQueryUIAvailable.mockReturnValue(true);

        loadAndExecuteIndex();

        expect(warnSpy).not.toHaveBeenCalled();

        // Check global AJAX error handler & telemetry
        expect(initializeAjaxError).toHaveBeenCalledTimes(1);
        expect(initializeTelemetry).toHaveBeenCalledTimes(1);

        // Check UI components
        expect(initializeTooltips).toHaveBeenCalledTimes(1);

        // Check Forms
        expect(initializeAcceptanceCheckbox).toHaveBeenCalledTimes(1);
        expect(initializePasswordToggle).toHaveBeenCalledTimes(1);
        expect(initializeCheckboxConditionals).toHaveBeenCalledTimes(1);
        expect(initializePasswordStrength).toHaveBeenCalledTimes(1);

        // Check Navigation & Tabs
        expect(initializeNavigation).toHaveBeenCalledTimes(1);
        expect(initializePopstateHandler).toHaveBeenCalledTimes(1);
        expect(initializeSwitchTab).toHaveBeenCalledTimes(1);
        expect(initializeActivateTab).toHaveBeenCalledTimes(1);
        expect(initializeTabDataFeature).toHaveBeenCalledTimes(1);

        // Check Autocomplete
        expect(initializeAutocomplete).toHaveBeenCalledTimes(1);

        // Check Teams
        expect(initializeTeamSelection).toHaveBeenCalledTimes(1);
        expect(initializeTeamOrder).toHaveBeenCalledTimes(1);
        expect(initializeTeamsAdmin).toHaveBeenCalledTimes(1);
        expect(initializeTeamUpdate).toHaveBeenCalledTimes(1);

        // Check Set Calculator
        expect(initializeSetCalculator).toHaveBeenCalledTimes(1);

        // Check Modals, Tournaments, Favourites, Printing
        expect(initializeModals).toHaveBeenCalledTimes(1);
        expect(initializeTournamentDate).toHaveBeenCalledTimes(1);
        expect(initializeFavourites).toHaveBeenCalledTimes(1);
        expect(initializePrinting).toHaveBeenCalledTimes(1);

        // Check Account, Club, Player
        expect(initializeAccountUpdate).toHaveBeenCalledTimes(1);
        expect(initializeClubUpdate).toHaveBeenCalledTimes(1);
        expect(initializePlayerUpdate).toHaveBeenCalledTimes(1);

        // Check Match Status, Auth, Messages, Player Search
        expect(initializeSetMatchStatus).toHaveBeenCalledTimes(1);
        expect(initializeLogin).toHaveBeenCalledTimes(1);
        expect(initializeResetPassword).toHaveBeenCalledTimes(1);
        expect(initializeMessages).toHaveBeenCalledTimes(1);
        expect(initializePlayerSearch).toHaveBeenCalledTimes(1);

        // Check Pricing, Payments, Withdrawals, Entry Requests
        expect(initializePricing).toHaveBeenCalledTimes(1);
        expect(initializeTournamentWithdrawal).toHaveBeenCalledTimes(1);
        expect(initializeTournamentCheckout).toHaveBeenCalledTimes(1);
        expect(initializeStripePaymentComplete).toHaveBeenCalledTimes(1);
        expect(initializeEntryRequest).toHaveBeenCalledTimes(1);

        // Check Club Admin, Club Players
        expect(initializeClubAdmin).toHaveBeenCalledTimes(1);
        expect(initializeClubPlayerRequest).toHaveBeenCalledTimes(1);
        expect(initializeClubPlayerRemove).toHaveBeenCalledTimes(1);

        // Check Match options, results, date, scores
        expect(initializeMatchOptions).toHaveBeenCalledTimes(1);
        expect(initializeUpdateMatchResults).toHaveBeenCalledTimes(1);
        expect(initializeSetMatchDate).toHaveBeenCalledTimes(1);
        expect(initializeResetMatchResult).toHaveBeenCalledTimes(1);
        expect(initializeResetMatchScores).toHaveBeenCalledTimes(1);
        expect(initializeSwitchHomeAway).toHaveBeenCalledTimes(1);
        expect(initializeTeamMatchResult).toHaveBeenCalledTimes(1);

        // Check Widgets
        expect(initializeWidgetCarousel).toHaveBeenCalledTimes(1);
    });

    test('logs a console warning when jQuery UI is not available', () => {
        isJQueryUIAvailable.mockReturnValue(false);

        loadAndExecuteIndex();

        expect(warnSpy).toHaveBeenCalledWith('jQuery UI not loaded - some features may not work');
    });

    test('re-initializes dynamic modules on jQuery ajaxComplete event', () => {
        isJQueryUIAvailable.mockReturnValue(true);

        loadAndExecuteIndex();

        // Clear mock calls from the document ready phase
        jest.clearAllMocks();

        // Trigger ajaxComplete on document
        jQuery(document).trigger('ajaxComplete');

        // Check functions registered in ajaxComplete callback
        expect(initializeAjaxError).toHaveBeenCalledTimes(1);
        expect(initializeAutocomplete).toHaveBeenCalledTimes(1);
        expect(initializeFavourites).toHaveBeenCalledTimes(1);
        expect(initializeNavigation).toHaveBeenCalledTimes(1);
        expect(initializePopstateHandler).toHaveBeenCalledTimes(1);
        expect(initializeTournamentDate).toHaveBeenCalledTimes(1);
        expect(initializeAccountUpdate).toHaveBeenCalledTimes(1);
        expect(initializeClubUpdate).toHaveBeenCalledTimes(1);
        expect(initializePlayerUpdate).toHaveBeenCalledTimes(1);
        expect(initializeModals).toHaveBeenCalledTimes(1);
        expect(initializeSwitchTab).toHaveBeenCalledTimes(1);
        expect(initializeActivateTab).toHaveBeenCalledTimes(1);
        expect(initializeTeamsAdmin).toHaveBeenCalledTimes(1);
        expect(initializeTeamUpdate).toHaveBeenCalledTimes(1);
        expect(initializeClubAdmin).toHaveBeenCalledTimes(1);
        expect(initializeClubPlayerRequest).toHaveBeenCalledTimes(1);
        expect(initializeClubPlayerRemove).toHaveBeenCalledTimes(1);
        expect(initializeWidgetCarousel).toHaveBeenCalledTimes(1);
        expect(initializeSetCalculator).toHaveBeenCalledTimes(1);

        // Check that one-time ready initializers are NOT called on ajaxComplete
        expect(initializeTooltips).not.toHaveBeenCalled();
        expect(initializeAcceptanceCheckbox).not.toHaveBeenCalled();
        expect(initializePasswordToggle).not.toHaveBeenCalled();
        expect(initializeCheckboxConditionals).not.toHaveBeenCalled();
        expect(initializePasswordStrength).not.toHaveBeenCalled();
        expect(initializeTabDataFeature).not.toHaveBeenCalled();
        expect(initializeTeamSelection).not.toHaveBeenCalled();
        expect(initializeTeamOrder).not.toHaveBeenCalled();
        expect(initializePrinting).not.toHaveBeenCalled();
        expect(initializeSetMatchStatus).not.toHaveBeenCalled();
        expect(initializeLogin).not.toHaveBeenCalled();
        expect(initializeResetPassword).not.toHaveBeenCalled();
        expect(initializeMessages).not.toHaveBeenCalled();
        expect(initializePlayerSearch).not.toHaveBeenCalled();
        expect(initializePricing).not.toHaveBeenCalled();
        expect(initializeTournamentWithdrawal).not.toHaveBeenCalled();
        expect(initializeTournamentCheckout).not.toHaveBeenCalled();
        expect(initializeStripePaymentComplete).not.toHaveBeenCalled();
        expect(initializeEntryRequest).not.toHaveBeenCalled();
        expect(initializeMatchOptions).not.toHaveBeenCalled();
        expect(initializeUpdateMatchResults).not.toHaveBeenCalled();
        expect(initializeSetMatchDate).not.toHaveBeenCalled();
        expect(initializeResetMatchResult).not.toHaveBeenCalled();
        expect(initializeResetMatchScores).not.toHaveBeenCalled();
        expect(initializeSwitchHomeAway).not.toHaveBeenCalled();
        expect(initializeTeamMatchResult).not.toHaveBeenCalled();
    });
});
