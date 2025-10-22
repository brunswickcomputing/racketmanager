/**
 * Switch Home and Away (Modularized)
 * Mirrors legacy Racketmanager.switchHomeAway used in match options modal.
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { matchHeader } from './match-header.js';

const ALERT_MATCH_OPTIONS = '#matchOptionsAlert';
const ALERT_MATCH_OPTIONS_TEXT = '#alertMatchOptionsResponse';
const ALERT_MATCH_DATE = '#matchDateAlert';
const ALERT_MATCH_DATE_TEXT = '#alertMatchDateResponse';

/**
 * Perform switch home/away request
 * @param {HTMLElement} link - clicked button inside the form
 */
export function switchHomeAway(link) {
  if (!link || !link.form || !link.form.id) return;

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_switch_home_away';

  // Reset alert states
  jQuery(ALERT_MATCH_OPTIONS).hide();
  jQuery(ALERT_MATCH_OPTIONS).removeClass('alert--success alert--warning alert--danger');

  jQuery(ALERT_MATCH_DATE).hide();
  jQuery(ALERT_MATCH_DATE).removeClass('alert--success alert--warning alert--danger');

  jQuery('.is-invalid').removeClass('is-invalid');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: $form,
    success: function (response) {
      const data = response && response.data ? response.data : {};
      const message = data.msg || '';
      const modal = `#${data.modal}`;
      const matchId = data.match_id || data.matach_id; // tolerate legacy typo

      // Show success
      jQuery(ALERT_MATCH_OPTIONS).addClass('alert--success');
      jQuery(ALERT_MATCH_OPTIONS_TEXT).html(message);
      jQuery(ALERT_MATCH_OPTIONS).show();

      // Hide modal
      try { jQuery(modal).modal('hide'); } catch (_) { /* no-op */ }

      // Refresh header if possible
      try { matchHeader(matchId); } catch (_) { /* no-op */ }

      // Update URL if provided
      try {
        const newPath = data.link;
        if (newPath) {
          const url = new URL(globalThis.location.href);
          const newURL = url.protocol + '//' + url.hostname + newPath;
          if (history.replaceState) {
            history.replaceState('', document.title, newURL.toString());
          }
        }
      } catch (_) { /* no-op */ }
    },
    error: function (response) {
      handleAjaxError(response, ALERT_MATCH_DATE_TEXT, ALERT_MATCH_DATE);
      jQuery(ALERT_MATCH_DATE).show();
    },
  });
}

/**
 * Initialize delegated handler for switch home/away
 */
export function initializeSwitchHomeAway() {
  jQuery(document)
    .off('click.racketmanager.switchHomeAway', '[data-action="switch-home-away"]')
    .on('click.racketmanager.switchHomeAway', '[data-action="switch-home-away"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return switchHomeAway(this);
    });
}
