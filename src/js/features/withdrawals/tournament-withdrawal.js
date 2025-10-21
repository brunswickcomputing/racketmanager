/**
 * Tournament Withdrawal (Phase 6)
 * Modularizes legacy Racketmanager.withdrawTournament and Racketmanager.confirmTournamentWithdraw
 * - Uses centralized AJAX utilities and error handling
 * - Provides delegated handlers (no globals/back-compat)
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { updateTotalPrice } from '../pricing/pricing.js';
import { checkToggle } from '../modals/has-modal-checkboxes.js';

const LIST_CONTAINER = '#liEventDetails';
const WITHDRAW_MODAL = '#partnerModal'; // Reuse existing modal container per legacy
const ENTRY_ALERT = '#entryAlert';
const ENTRY_ALERT_TEXT = '#entryAlertResponse';
const WITHDRAW_ALERT = '#withdrawResponse';
const WITHDRAW_ALERT_TEXT = '#withdrawResponseText';

/**
 * Open the tournament withdrawal confirmation modal
 * @param {Event} event
 */
export function openWithdrawalModal(event) {
  if (event && typeof event.preventDefault === 'function') event.preventDefault();

  jQuery(LIST_CONTAINER).addClass('is-loading');

  const eventsEntered = jQuery('#eventsEntered').val();
  const tournamentId = jQuery('#tournamentId').val();
  const playerId = jQuery('#playerId').val();

  // Clear previous content/errors
  jQuery(WITHDRAW_MODAL).val('');
  jQuery(WITHDRAW_ALERT).hide();

  // Load confirmation UI into modal
  jQuery(WITHDRAW_MODAL).load(
    getAjaxUrl(),
    {
      tournamentId: tournamentId,
      playerId: playerId,
      eventsEntered: eventsEntered,
      modal: 'partnerModal',
      action: 'racketmanager_tournament_withdrawal',
      security: getAjaxNonce(),
    },
    function (_response, status, xhr) {
      jQuery(LIST_CONTAINER).removeClass('is-loading');
      if (status === 'error') {
        handleAjaxError(xhr, ENTRY_ALERT_TEXT, ENTRY_ALERT);
        jQuery(ENTRY_ALERT).show();
        return;
      }
      jQuery(WITHDRAW_MODAL).show();
      try { jQuery(WITHDRAW_MODAL).modal('show'); } catch (_) { /* no-op */ }
    }
  );
}

/**
 * Confirm tournament withdrawal. Mirrors legacy behavior.
 */
export function confirmTournamentWithdraw() {
  const modal = WITHDRAW_MODAL;
  const tournamentId = jQuery('#tournamentId').val();
  const playerId = jQuery('#playerId').val();

  // Reset alerts
  jQuery(WITHDRAW_ALERT).hide();
  jQuery(WITHDRAW_ALERT).removeClass('alert--success alert--warning alert--danger');
  jQuery(WITHDRAW_ALERT_TEXT).html('');
  jQuery(ENTRY_ALERT).hide();
  jQuery(ENTRY_ALERT).removeClass('alert--success alert--warning alert--danger');
  jQuery(ENTRY_ALERT_TEXT).html('');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: {
      tournamentId: tournamentId,
      playerId: playerId,
      action: 'racketmanager_confirm_tournament_withdrawal',
      security: getAjaxNonce(),
    },
    success: function (response) {
      // Uncheck each entered event and trigger UI updates
      const eventsEntered = jQuery('input:checked.form-check--event');
      for (let i = 0; i < eventsEntered.length; i++) {
        const ev = eventsEntered[i];
        ev.checked = false;
        try { checkToggle(ev, null); } catch (_) { /* no-op */ }
      }

      // Success alert
      jQuery(ENTRY_ALERT).addClass('alert--success');
      jQuery(ENTRY_ALERT_TEXT).html(response?.data);

      // Update totals via modular pricing
      try { updateTotalPrice(); } catch (_) { /* no-op */ }

      // Hide modal and show success
      try { jQuery(modal).modal('hide'); } catch (_) { /* no-op */ }
      jQuery(ENTRY_ALERT).show();
    },
    error: function (response) {
      handleAjaxError(response, WITHDRAW_ALERT_TEXT, WITHDRAW_ALERT);
      jQuery(WITHDRAW_ALERT).show();
    },
  });
}

/**
 * Initialize delegated handlers for tournament withdrawal
 */
export function initializeTournamentWithdrawal() {
  jQuery(document)
    .off('click.racketmanager.withdraw', '#tournamentWithdraw, [data-action="withdraw-tournament"]')
    .on('click.racketmanager.withdraw', '#tournamentWithdraw, [data-action="withdraw-tournament"]', function (e) {
      return openWithdrawalModal(e);
    })
    .off('click.racketmanager.withdrawConfirm', '[data-action="confirm-tournament-withdrawal"]')
    .on('click.racketmanager.withdrawConfirm', '[data-action="confirm-tournament-withdrawal"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return confirmTournamentWithdraw();
    });
}
