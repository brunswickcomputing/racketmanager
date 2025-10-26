/**
 * Player Search - Modularized
 * Implements delegated submit handler for the Players page search form.
 */

import { handleAjaxError } from '../ajax/handle-ajax-error.js';
import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';

const FORM_SELECTOR = '#playerSearch';
const INPUT_SELECTOR = '#search_string';
const RESULTS_CONTAINER = '#searchResultsContainer';
const CONTENT_CONTAINER = '#playerSearchContent';
const PAGE_TAB_CONTAINER = '#pageContentTab';
const ERROR_FIELD = '#headerResponse';
const ERROR_RESPONSE = '#headerResponseText';

function getBaseUrl() {
  try {
    const url = new URL(globalThis.location.href);
    return url.protocol + '//' + url.hostname + url.pathname;
  } catch (_) {
    return globalThis.location?.pathname || '/';
  }
}

function pushQueryToHistory(q) {
  try {
    const base = getBaseUrl();
    const newUri = `${base}?q=${q}`;
    const state = jQuery(PAGE_TAB_CONTAINER).html();
    if (globalThis.history && typeof globalThis.history.pushState === 'function') {
      globalThis.history.pushState(state, '', newUri.toString());
    }
  } catch (_) {
    // no-op
  }
}

export function executePlayerSearch(event) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }

  // Clear previous results and errors
  jQuery(RESULTS_CONTAINER).empty();
  jQuery(ERROR_FIELD).hide();

  const searchVal = jQuery(INPUT_SELECTOR).val();
  if (!searchVal) {
    return; // nothing to search
  }

  const encoded = encodeURI(searchVal);
  const baseUrl = getAjaxUrl();
  const ajaxURL = `${baseUrl}?search_string=${encoded}&action=racketmanager_search_players&security=${getAjaxNonce()}`;

  jQuery(CONTENT_CONTAINER).addClass('is-loading');

  jQuery(RESULTS_CONTAINER).load(
    ajaxURL,
    function (response, status, xhr) {
      jQuery(CONTENT_CONTAINER).removeClass('is-loading');
      if (status === 'error') {
        // Use centralized error handler
        handleAjaxError(xhr, ERROR_RESPONSE, ERROR_FIELD);
        jQuery(ERROR_FIELD).show();
        return;
      }
      // Show results
      jQuery(RESULTS_CONTAINER).show();
      // Update URL
      pushQueryToHistory(encoded);
    }
  );
}

export function initializePlayerSearch() {
  // Delegated submit handler
  jQuery(document)
    .off('submit.racketmanager.playerSearch', FORM_SELECTOR)
    .on('submit.racketmanager.playerSearch', FORM_SELECTOR, function (e) {
      return executePlayerSearch(e);
    });
}
