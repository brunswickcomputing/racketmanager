/**
 * Teams - Team Update (Phase 10 cleanup)
 * Modularized replacement for legacy Racketmanager.updateTeam
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

/**
 * Update a team using the form values inside the clicked button's form
 * @param {HTMLElement} link - the clicked button inside the form
 */
export function updateTeam(link) {
  if (!link || !link.form || !link.form.id) return;

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();

  // Discover event and team ids from hidden inputs (keeps parity with legacy UI selectors)
  const event = link.form.querySelector('#event_id')?.value || link.form[3]?.value; // legacy index fallback
  const team = link.form.querySelector('#team_id')?.value || link.form[2]?.value;

  // Submit button selector (hide during request, then show)
  const submitButton = `#teamUpdateSubmit-${event}-${team}`;

  // Alert containers per legacy
  const alertField = `#teamUpdateResponse-${event}-${team}`;
  const alertTextField = `#teamUpdateResponseText-${event}-${team}`;

  $form += '&action=racketmanager_update_team';

  // Prep UI
  jQuery(submitButton).hide();
  jQuery(alertField).removeClass('alert--success alert--warning alert--danger').hide();
  jQuery(alertTextField).html('');
  jQuery('.is-invalid').removeClass('is-invalid');
  jQuery('.invalid-feedback').val('');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    async: false,
    data: $form,
    success: function (response) {
      // Mirror legacy: update captain/contact summary fields if present
      const captainNameField = `#captain-${event}-${team}`;
      const captainName = jQuery(captainNameField).val();
      if (captainName) {
        const teamCaptainNameField = `#captain-name-${event}-${team}`;
        jQuery(teamCaptainNameField).text(captainName);
      }
      const captainContactNoField = `#contactno-${event}-${team}`;
      const captainContactNo = jQuery(captainContactNoField).val();
      if (captainContactNo) {
        const teamContactNoField = `#captain-contact-no-${event}-${team}`;
        jQuery(teamContactNoField).text(captainContactNo);
      }
      const captainContactEmailField = `#contactemail-${event}-${team}`;
      const captainContactEmail = jQuery(captainContactEmailField).val();
      if (captainContactEmail) {
        const teamContactEmailField = `#captain-contact-email-${event}-${team}`;
        jQuery(teamContactEmailField).text(captainContactEmail);
      }

      jQuery(alertField).addClass('alert--success');
      jQuery(alertTextField).html(response?.data);
    },
    error: function (response) {
      handleAjaxError(response, alertTextField, alertField);
    },
    complete: function () {
      jQuery(alertField).show();
      jQuery(submitButton).show();
    }
  });
}

/**
 * Initialize delegated handler for updating team
 */
export function initializeTeamUpdate() {
  jQuery(document)
    .off('click.racketmanager.teamUpdate', '[data-action="update-team"]')
    .on('click.racketmanager.teamUpdate', '[data-action="update-team"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return updateTeam(this);
    })
}
