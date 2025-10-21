/**
 * Club Admin - Club Roles (Phase 8)
 * Modularizes legacy Racketmanager.clubRoleModal and Racketmanager.setClubRole
 * using delegated handlers and centralized AJAX/error utilities.
 */

import { getAjaxUrl, getAjaxNonce } from '../../../config/ajax-config.js';
import { LOADING_MODAL } from '../../../config/constants.js';
import { handleAjaxError } from '../../ajax/handle-ajax-error.js';
import { safeAutocomplete } from '../../../utils/jquery-helpers.js';
import { getPlayerDetails, setPlayerDetails } from '../../../utils/player-utils.js';

const ROLES_ERROR_FIELD = '#rolesResponse';
const ROLES_ERROR_TEXT = '#rolesResponseText';
const CLUB_ROLE_MODAL = '#clubRoleModal';
const CLUB_ROLE_ALERT = '#clubRoleResponse';
const CLUB_ROLE_ALERT_TEXT = '#clubRoleResponseText';

/**
 * Open the Club Role modal
 * @param {Event} event
 * @param {number|string} clubRoleId
 */
export function openClubRoleModal(event, clubRoleId) {
  if (event && typeof event.preventDefault === 'function') event.preventDefault();
  if (!clubRoleId) return;

  try { jQuery(LOADING_MODAL).modal('show'); } catch (_) { /* no-op */ }

  // Reset page-level error field
  jQuery(ROLES_ERROR_FIELD).hide();

  jQuery(CLUB_ROLE_MODAL).val('');
  jQuery(CLUB_ROLE_MODAL).load(
    getAjaxUrl(),
    {
      clubRoleId: clubRoleId,
      modal: 'clubRoleModal',
      action: 'racketmanager_club_role_modal',
      security: getAjaxNonce(),
    },
    function (response, status, xhr) {
      try { jQuery(LOADING_MODAL).modal('hide'); } catch (_) { /* no-op */ }
      if (status === 'error') {
        // Prefer parsing JSON; fall back to centralized handler
        try {
          const data = JSON.parse(response);
          jQuery(ROLES_ERROR_TEXT).html(data.message || 'An error occurred');
        } catch (_) {
          handleAjaxError(xhr, ROLES_ERROR_TEXT, ROLES_ERROR_FIELD);
        }
        jQuery(ROLES_ERROR_FIELD).show();
      } else {
        jQuery(CLUB_ROLE_MODAL).show();
        try { jQuery(CLUB_ROLE_MODAL).modal('show'); } catch (_) { /* no-op */ }
        // Initialize username autocomplete inside the loaded modal
        try { initUserAutocomplete(CLUB_ROLE_MODAL); } catch (_) { /* no-op */ }
      }
    }
  );
}

/**
 * Save Club Role updates from within the modal
 * @param {HTMLElement} link - the clicked button inside the modal footer
 */
export function setClubRole(link) {
  if (!link || !link.form || !link.form.id) return;
  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_set_club_role';

  // Reset alerts/validation
  jQuery(CLUB_ROLE_ALERT).hide();
  jQuery(CLUB_ROLE_ALERT).removeClass('alert--success alert--warning alert--danger');
  jQuery('.is-invalid').removeClass('is-invalid');
  jQuery(CLUB_ROLE_ALERT_TEXT).html('');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: $form,
    success: function (response) {
      const data = response?.data || {};
      const message = data.msg || '';
      const status = data.status || 'success';
      const alertClass = `alert--${status}`;
      jQuery(CLUB_ROLE_ALERT).addClass(alertClass);
      jQuery(CLUB_ROLE_ALERT_TEXT).html(message);
      jQuery(CLUB_ROLE_ALERT).show();
    },
    error: function (response) {
      handleAjaxError(response, CLUB_ROLE_ALERT_TEXT, CLUB_ROLE_ALERT);
      jQuery(CLUB_ROLE_ALERT).show();
    },
  });
}

// Initialize user autocomplete for the Club Role modal
function initUserAutocomplete(context = document) {
  const selector = `${context} #userName`;
  // Avoid duplicate init by destroying existing instance if any
  try { jQuery(selector).filter('.ui-autocomplete-input').each(function(){ jQuery(this).autocomplete('destroy'); }); } catch (_) { /* no-op */ }

  safeAutocomplete(selector, {
    minLength: 2,
    source: function(request, response) {
      const club = jQuery('#clubId').val();
      const notifyField = '#userFeedback';
      const results = getPlayerDetails('name', request.term, club, notifyField);
      response(results);
    },
    select: function(_event, ui) {
      if (ui && ui.item) {
        if (ui.item.value === 'null') ui.item.value = '';
        jQuery('#userName').val(ui.item.value || '');
        jQuery('#userId').val(ui.item.playerId || '');
        jQuery('#contactno').val(ui.item.contactno || '');
        jQuery('#contactemail').val(ui.item.user_email || '');
      }
    },
    change: function(_event, ui) {
      // If cleared or invalid, clear all related fields
      setPlayerDetails(ui, '#userName', '#userId', '#contactno', '#contactemail');
    }
  });
}

/**
 * Initialize Club Admin (Roles) delegated handlers
 */
export function initializeClubAdmin() {
  // Open role modal when clicking username cell/link
  jQuery(document)
    .off('click.racketmanager.clubAdmin', '.club-role, [data-action="open-club-role-modal"]')
    .on('click.racketmanager.clubAdmin', '.club-role, [data-action="open-club-role-modal"]', function (e) {
      const clubRoleId = this.getAttribute('data-club-role-id') || jQuery(this).data('clubRoleId');
      return openClubRoleModal(e, clubRoleId);
    });

  // Save button inside modal
  jQuery(document)
    .off('click.racketmanager.clubAdminSave', '[data-action="set-club-role"], #clubRoleUpdateSubmit')
    .on('click.racketmanager.clubAdminSave', '[data-action="set-club-role"], #clubRoleUpdateSubmit', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return setClubRole(this);
    });
}
