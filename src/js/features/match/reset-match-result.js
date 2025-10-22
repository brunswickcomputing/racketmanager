/**
 * Reset Match Result - Modularized
 * Mirrors legacy Racketmanager.resetMatchResult
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { matchHeader } from './match-header.js';
import { resetMatchScoresByFormId } from './reset-match-scores.js';

const ALERT_MATCH = '#matchAlert';
const ALERT_MATCH_TEXT = '#matchAlertResponse';
const ALERT_OPTIONS = '#matchOptionsAlert';
const ALERT_OPTIONS_TEXT = '#alertMatchOptionsResponse';
const ALERT_RESET = '#matchResetAlert';
const ALERT_RESET_TEXT = '#alertMatchResetResponse';

/**
 * Submit reset match result
 * @param {HTMLElement} link - Button inside the form
 * @param {boolean} isTournament - indicates tournament page variant
 */
export function resetMatchResult(link, isTournament = false) {
  if (!link || !link.form || !link.form.id) return;

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_reset_match_result';

  // Primary alert container varies by page
  const alert1 = isTournament ? ALERT_MATCH : ALERT_OPTIONS;
  const alert1Text = isTournament ? ALERT_MATCH_TEXT : ALERT_OPTIONS_TEXT;
  // Secondary alert container in modal
  const alert2 = ALERT_RESET;
  const alert2Text = ALERT_RESET_TEXT;

  jQuery(alert1).hide().removeClass('alert--success alert--warning alert--danger');
  jQuery(alert2).hide().removeClass('alert--success alert--warning alert--danger');
  jQuery('.is-invalid').removeClass('is-invalid');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: $form,
    success: function (response) {
      const data = response?.data || {};
      const message = data.msg;
      const modalId = `#${data.modal}`;
      const matchId = data.match_id;

      jQuery(alert1).show().addClass('alert--success');
      jQuery(alert1Text).html(message);
      try { jQuery(modalId).modal('hide'); } catch (_) { /* no-op */ }

      // Refresh header
      matchHeader(matchId);

      // Also reset any visible match score input forms, if present
      const candidates = [`form-match-${matchId}`, 'match-view', 'match'];
      candidates.forEach(id => {
        if (jQuery(`#${id}`).length) {
          resetMatchScoresByFormId(id);
        }
      });

      // Optional URL update
      const newPath = data.link || '';
      if (newPath) {
        try {
          const url = new URL(globalThis.location.href);
          const newURL = url.protocol + '//' + url.hostname + newPath;
          if (history.replaceState) {
            history.replaceState('', document.title, newURL.toString());
          }
        } catch (_) { /* no-op */ }
      }
    },
    error: function (response) {
      handleAjaxError(response, alert2Text, alert2);
      jQuery(alert2).show();
    },
  });
}

export function initializeResetMatchResult() {
  jQuery(document)
    .off('click.racketmanager.resetMatchResult', '[data-action="reset-match-result"]')
    .on('click.racketmanager.resetMatchResult', '[data-action="reset-match-result"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      const isTournament = this.getAttribute('data-is-tournament') === 'true' || jQuery(this).data('isTournament') === true;
      return resetMatchResult(this, isTournament);
    });
}
