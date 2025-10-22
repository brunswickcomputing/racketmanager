/**
 * Update Match Results - Modularized
 * Replaces legacy Racketmanager.updateMatchResults used on match input pages.
 */

import { getAjaxUrl } from '../../config/ajax-config.js';
import { handleAjaxError } from '../ajax/handle-ajax-error.js';

// Common selectors used across templates
const NOTIFY_FIELD_DEFAULT = '#updateResponse';
const ALERT_ID_DEFAULT = '#matchAlert';
const ALERT_TEXT_DEFAULT = '#matchAlertResponse';
const SPLASH_DEFAULT = '#splash';

/**
 * Perform the update match results request
 * Accepts the clicked element inside a form (button), where link.form is the form element
 * @param {HTMLElement} link
 */
export function updateMatchResults(link) {
  if (!link || !link.form || !link.form.id) return;

  const formId = `#${link.form.id}`;
  let $form = jQuery(formId).serialize();
  $form += '&action=racketmanager_update_match';

  // Resolve containers depending on template
  let notifyField = NOTIFY_FIELD_DEFAULT;
  let alertField = ALERT_ID_DEFAULT;
  let alertTextField = ALERT_TEXT_DEFAULT;
  let useAlert = false;

  const $alert = jQuery(ALERT_ID_DEFAULT);
  if ($alert.length) {
    useAlert = true;
    alertField = ALERT_ID_DEFAULT;
    alertTextField = ALERT_TEXT_DEFAULT;
  } else {
    notifyField = NOTIFY_FIELD_DEFAULT;
  }

  const splash = SPLASH_DEFAULT;

  if (useAlert) {
    jQuery(alertField).hide();
    jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
  } else {
    jQuery(notifyField).removeClass('message-success message-error');
    jQuery(notifyField).hide();
  }

  jQuery(splash).addClass('is-loading');

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: $form,
    success: function (response) {
      // Legacy behavior: if alert container exists, use it; else write into notifyField
      if (useAlert) {
        const data = response && response.data ? response.data : '';
        // Response may be HTML or plain string
        jQuery(alertField).addClass('alert--success');
        jQuery(alertTextField).html(data);
        jQuery(alertField).show();
      } else {
        jQuery(notifyField).html(response && response.data ? response.data : '');
        jQuery(notifyField).addClass('message-success');
        jQuery(notifyField).show();
      }
    },
    error: function (response) {
      if (useAlert) {
        handleAjaxError(response, alertTextField, alertField);
        jQuery(alertField).show();
      } else {
        // Fallback to simple message area
        let message = '';
        if (response && response.responseJSON) {
          message = response.responseJSON.data || 'Request failed';
        } else {
          message = response && response.statusText ? response.statusText : 'Request failed';
        }
        jQuery(notifyField).html(message);
        jQuery(notifyField).addClass('message-error');
        jQuery(notifyField).show();
      }
    },
    complete: function () {
      jQuery(splash).removeClass('is-loading');
    }
  });
}

/**
 * Initialize delegated handlers for Update Match Results buttons
 */
export function initializeUpdateMatchResults() {
  jQuery(document)
    .off('click.racketmanager.updateMatch', '[data-action="update-match-results"]')
    .on('click.racketmanager.updateMatch', '[data-action="update-match-results"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return updateMatchResults(this);
    })
    // Fallback in case data-action was missed but id is present
    .off('click.racketmanager.updateMatchFallback', '#updateMatchResults')
    .on('click.racketmanager.updateMatchFallback', '#updateMatchResults', function (e) {
      // Only trigger if no inline onclick attribute is present
      if (this.getAttribute('onclick')) return; // legacy template still active
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return updateMatchResults(this);
    });
}
