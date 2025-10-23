/**
 * Club Player Remove - Modularized
 * Replaces legacy Racketmanager.clubPlayerRemove
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

/**
 * Remove selected club players for the given gender
 * @param {string} formSelector - CSS selector for the gender-specific form
 * @param {string} gender - 'M' or 'F'
 */
export function clubPlayerRemove(formSelector, gender) {
  const $formEl = jQuery(formSelector);
  if ($formEl.length === 0) return;

  let $form = $formEl.serialize();
  $form += '&action=racketmanager_club_players_remove';

  const alertField = `#playerDel${gender}Response`;
  const alertText = `#playerDel${gender}ResponseText`;

  // Reset alerts and validation
  jQuery(alertField).hide().removeClass('alert--success alert--warning alert--danger');
  jQuery(alertText).html('');
  jQuery('.is-invalid').removeClass('is-invalid');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    async: false,
    data: $form,
    success: function (response) {
      const data = response && response.data ? response.data : {};
      const msg = typeof data === 'string' ? data : (data.msg || '');
      const status = data.status || 'success';
      jQuery(alertField).addClass(`alert--${status}`);
      jQuery(alertText).html(msg);

      // Remove each checked row from the table (parity with legacy)
      try {
        const checked = $formEl.find('input.checkbox:checked');
        checked.each(function () {
          const rosterId = this.value;
          const rowSel = `#club_player-${rosterId}`;
          jQuery(rowSel).remove();
        });
      } catch (_) { /* no-op */ }
    },
    error: function (response) {
      handleAjaxError(response, alertText, alertField);
    },
    complete: function () {
      jQuery(alertField).show();
    }
  });
}

export function initializeClubPlayerRemove() {
  jQuery(document)
    .off('click.racketmanager.clubPlayerRemove', '[data-action="club-player-remove"], #clubPlayerRemoveSubmit')
    .on('click.racketmanager.clubPlayerRemove', '[data-action="club-player-remove"], #clubPlayerRemoveSubmit', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      if (this.getAttribute && this.getAttribute('onclick')) return;
      const formId = this.getAttribute('data-form-id');
      const gender = this.getAttribute('data-gender');
      // If attributes not provided (legacy id case), attempt to infer from enclosing table heading
      const selector = formId ? `#${formId}` : (this.form ? `#${this.form.id}` : undefined);
      if (!selector || !gender) return;
      return clubPlayerRemove(selector, gender);
    });
}
