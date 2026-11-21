/**
 * @jest-environment jsdom
 */

import { executePlayerSearch, initializePlayerSearch } from '../../../../src/js/features/player/player-search.js';
import * as ajaxErrorModule from '../../../../src/js/features/ajax/handle-ajax-error.js';

describe('features/player/player-search', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <form id="playerSearch">
        <input type="text" id="search_string" value="" />
      </form>
      <div id="playerSearchContent"></div>
      <div id="searchResultsContainer" style="display: none;"></div>
      <div id="pageContentTab"><div>Initial Tab HTML</div></div>
      <div id="headerResponse" style="display: none;">
        <span id="headerResponseText"></span>
      </div>
    `;
    window.jQuery = window.$ = require('jquery');
    window.ajax_var = {
      url: '/wp-admin/admin-ajax.php',
      ajax_nonce: 'search-nonce-123'
    };
  });

  afterEach(() => {
    delete window.ajax_var;
    jest.restoreAllMocks();
  });

  test('does nothing when search string is empty', () => {
    const loadSpy = jest.spyOn(jQuery.fn, 'load').mockImplementation(() => {});

    const event = { preventDefault: jest.fn() };
    executePlayerSearch(event);

    expect(event.preventDefault).toHaveBeenCalled();
    expect(loadSpy).not.toHaveBeenCalled();
  });

  test('executes AJAX load, shows results, and pushes state to history on success', () => {
    let loadCallback;
    const loadSpy = jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, cb) {
      loadCallback = cb;
    });

    const pushStateSpy = jest.spyOn(globalThis.history, 'pushState').mockImplementation(() => {});

    jQuery('#search_string').val('Federer');
    executePlayerSearch();

    expect(loadSpy).toHaveBeenCalledWith(
      expect.stringContaining('search_string=Federer'),
      expect.any(Function)
    );
    expect(jQuery('#playerSearchContent').hasClass('is-loading')).toBe(true);

    // Trigger load success callback
    loadCallback('<div>Results</div>', 'success', {});

    expect(jQuery('#playerSearchContent').hasClass('is-loading')).toBe(false);
    expect(jQuery('#searchResultsContainer').css('display')).not.toBe('none');
    expect(pushStateSpy).toHaveBeenCalledWith(
      expect.stringContaining('Initial Tab HTML'),
      '',
      expect.stringContaining('q=Federer')
    );
  });

  test('suppresses error when history.pushState throws an exception on success', () => {
    let loadCallback;
    jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, cb) {
      loadCallback = cb;
    });

    jest.spyOn(globalThis.history, 'pushState').mockImplementation(() => {
      throw new DOMException('QuotaExceededError', 'QuotaExceededError');
    });

    jQuery('#search_string').val('Nadal');

    expect(() => {
      executePlayerSearch();
      loadCallback('<div>Results</div>', 'success', {});
    }).not.toThrow();

    expect(jQuery('#searchResultsContainer').css('display')).not.toBe('none');
    expect(jQuery('#playerSearchContent').hasClass('is-loading')).toBe(false);
  });

  test('falls back to location.pathname when URL parsing throws in pushQueryToHistory', () => {
    let loadCallback;
    jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, cb) {
      loadCallback = cb;
    });

    jest.spyOn(globalThis, 'URL').mockImplementation(() => {
      throw new TypeError('Invalid URL');
    });

    const pushStateSpy = jest.spyOn(globalThis.history, 'pushState').mockImplementation(() => {});

    jQuery('#search_string').val('Alcaraz');
    executePlayerSearch();
    loadCallback('<div>Results</div>', 'success', {});

    expect(pushStateSpy).toHaveBeenCalledWith(
      expect.any(String),
      '',
      expect.stringContaining(`${globalThis.location.pathname}?q=Alcaraz`)
    );
  });

  test('handles AJAX error by calling handleAjaxError and showing error container', () => {
    let loadCallback;
    jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, cb) {
      loadCallback = cb;
    });

    const handleAjaxErrorSpy = jest.spyOn(ajaxErrorModule, 'handleAjaxError').mockImplementation(() => {});

    jQuery('#search_string').val('Djokovic');
    executePlayerSearch();

    const mockXhr = { status: 500, responseText: 'Server Error' };
    loadCallback('', 'error', mockXhr);

    expect(jQuery('#playerSearchContent').hasClass('is-loading')).toBe(false);
    expect(handleAjaxErrorSpy).toHaveBeenCalledWith(mockXhr, '#headerResponseText', '#headerResponse');
    expect(jQuery('#headerResponse').css('display')).not.toBe('none');
  });

  test('initializePlayerSearch binds form submit to executePlayerSearch', () => {
    const loadSpy = jest.spyOn(jQuery.fn, 'load').mockImplementation(() => {});

    initializePlayerSearch();

    jQuery('#search_string').val('Murray');
    jQuery('#playerSearch').trigger('submit');

    expect(loadSpy).toHaveBeenCalled();
  });
});
