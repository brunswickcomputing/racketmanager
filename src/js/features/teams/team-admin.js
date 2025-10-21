    /**
 * Teams - Admin helpers for Team Order workflow (Phase 7)
 * Modularized implementation of legacy get_event_team_match_dropdown and teamEditModal.
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { LOADING_MODAL } from '../../config/constants.js';

const TEAM_SELECT = '#teamId';
const MATCHES_CONTAINER = '#matches';
const SET_TEAM_BUTTON = '#setTeamButton';

export function getEventTeamMatchDropdown(teamId) {
  const eventId = jQuery('#event_id').val();
  if (!eventId) return;

  jQuery(MATCHES_CONTAINER).hide().html('');
  jQuery(SET_TEAM_BUTTON).hide();

  jQuery(MATCHES_CONTAINER).load(
    getAjaxUrl(),
    {
      eventId: eventId,
      teamId: teamId,
      action: 'racketmanager_get_event_team_match_dropdown',
      security: getAjaxNonce(),
    },
    function (_response, status, xhr) {
      if (status === 'error') {
        // Render error at generic header if available
        handleAjaxError(xhr, '#headerResponseText', '#headerResponse');
        jQuery('#headerResponse').show();
        return;
      }
      jQuery(MATCHES_CONTAINER).show();
    }
  );
}

export function teamEditModal(event, teamId, eventId) {
  if (event && typeof event.preventDefault === 'function') event.preventDefault();
  const modal = '#teamModal';
  const errorField = '#rolesResponse';
  const errorResponseField = errorField + 'Text';

  try { jQuery(LOADING_MODAL).modal('show'); } catch (_) { /* no-op */ }
  jQuery(errorField).hide();
  jQuery(modal).val('');

  jQuery(modal).load(
    getAjaxUrl(),
    {
      teamId: teamId,
      eventId: eventId,
      modal: 'teamModal',
      action: 'racketmanager_team_edit_modal',
      security: getAjaxNonce(),
    },
    function (response, status, xhr) {
      try { jQuery(LOADING_MODAL).modal('hide'); } catch (_) { /* no-op */ }
      if (status === 'error') {
        try {
          const data = JSON.parse(response);
          jQuery(errorResponseField).html(data.message || 'An error occurred');
        } catch (_) {
          handleAjaxError(xhr, errorResponseField, errorField);
        }
        jQuery(errorField).show();
      } else {
        jQuery(modal).show();
        try { jQuery(modal).modal('show'); } catch (_) { /* no-op */ }
      }
    }
  );
}

export function initializeTeamsAdmin() {
  // Change of team select triggers matches dropdown load
  jQuery(document)
    .off('change.racketmanager.teamAdmin', TEAM_SELECT)
    .on('change.racketmanager.teamAdmin', TEAM_SELECT, function () {
      return getEventTeamMatchDropdown(this.value);
    });

  // Optional: delegated handler for opening team edit modal
  jQuery(document)
    .off('click.racketmanager.teamAdmin', '[data-action="open-team-edit-modal"]')
    .on('click.racketmanager.teamAdmin', '[data-action="open-team-edit-modal"]', function (e) {
      const teamId = this.getAttribute('data-team-id');
      const eventId = this.getAttribute('data-event-id');
      return teamEditModal(e, teamId, eventId);
    });

  // When server indicates set team is possible, show button
  // Provide a simple API if PHP templates call Racketmanager.show_set_team_button()
  globalThis.Racketmanager = globalThis.Racketmanager || {};
  globalThis.Racketmanager.show_set_team_button = function () {
    jQuery(SET_TEAM_BUTTON).show();
  };
}
