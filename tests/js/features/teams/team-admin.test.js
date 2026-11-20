import {
  getEventTeamMatchDropdown,
  teamEditModal,
  initializeTeamsAdmin,
} from '../../../../src/js/features/teams/team-admin';

describe('Team Admin feature', () => {
  let container;

  beforeAll(() => {
    window.ajax_var = {
      url: '/wp-admin/admin-ajax.php',
      ajax_nonce: 'team-nonce-123',
    };
    window.jQuery = window.$ = require('jquery');
    initializeTeamsAdmin();
  });

  beforeEach(() => {
    document.body.innerHTML = '';
    container = document.createElement('div');
    document.body.appendChild(container);
    jest.clearAllMocks();
    jQuery.fn.modal = jest.fn();
    jQuery.fn.load = jest.fn();
  });

  afterEach(() => {
    if (container && container.parentNode) {
      container.parentNode.removeChild(container);
    }
    jest.restoreAllMocks();
  });

  describe('getEventTeamMatchDropdown', () => {
    it('returns early when #event_id is not in DOM or has empty value', () => {
      getEventTeamMatchDropdown('10');
      expect(jQuery.fn.load).not.toHaveBeenCalled();

      container.innerHTML = '<input id="event_id" value="" />';
      getEventTeamMatchDropdown('10');
      expect(jQuery.fn.load).not.toHaveBeenCalled();
    });

    it('hides matches & set-team button and loads matches dropdown on valid eventId', () => {
      container.innerHTML = `
        <input id="event_id" value="42" />
        <div id="matches" style="display: block;">Old matches</div>
        <button id="setTeamButton" style="display: block;">Set Team</button>
        <div id="headerResponse" style="display: none;">
          <div id="headerResponseText"></div>
        </div>
      `;

      let loadCallback;
      jQuery.fn.load = jest.fn(function (url, data, callback) {
        loadCallback = callback;
        return this;
      });

      getEventTeamMatchDropdown('99');

      expect(document.getElementById('matches').style.display).toBe('none');
      expect(document.getElementById('matches').innerHTML).toBe('');
      expect(document.getElementById('setTeamButton').style.display).toBe('none');

      expect(jQuery.fn.load).toHaveBeenCalledWith(
        '/wp-admin/admin-ajax.php',
        {
          eventId: '42',
          teamId: '99',
          action: 'racketmanager_get_event_team_match_dropdown',
          security: 'team-nonce-123',
        },
        expect.any(Function)
      );

      // Verify success status shows #matches
      loadCallback('<div>Match Options</div>', 'success', {});
      expect(document.getElementById('matches').style.display).not.toBe('none');
    });

    it('handles error status in .load callback by invoking handleAjaxError and showing headerResponse', () => {
      container.innerHTML = `
        <input id="event_id" value="42" />
        <div id="matches" style="display: block;"></div>
        <button id="setTeamButton"></button>
        <div id="headerResponse" style="display: none;">
          <div id="headerResponseText"></div>
        </div>
      `;

      let loadCallback;
      jQuery.fn.load = jest.fn(function (url, data, callback) {
        loadCallback = callback;
        return this;
      });

      getEventTeamMatchDropdown('99');

      const mockXhr = {
        status: 500,
        statusText: 'Internal Server Error',
        responseJSON: { data: { message: 'Failed to load matches' } },
      };

      loadCallback('', 'error', mockXhr);

      expect(document.getElementById('headerResponse').style.display).not.toBe('none');
      expect(document.getElementById('headerResponseText').innerHTML).toBe('Failed to load matches');
      expect(document.getElementById('matches').style.display).toBe('none');
    });
  });

  describe('teamEditModal', () => {
    it('prevents default event action if present, shows loading modal, and loads modal content', () => {
      container.innerHTML = `
        <div id="splashBlockModal"></div>
        <div id="teamModal"></div>
        <div id="rolesResponse" style="display: block;">
          <div id="rolesResponseText"></div>
        </div>
      `;

      let loadCallback;
      jQuery.fn.load = jest.fn(function (url, data, callback) {
        loadCallback = callback;
        return this;
      });

      const fakeEvt = { preventDefault: jest.fn() };
      teamEditModal(fakeEvt, 'team-1', 'event-2');

      expect(fakeEvt.preventDefault).toHaveBeenCalled();
      expect(jQuery.fn.modal).toHaveBeenCalledWith('show');
      expect(document.getElementById('rolesResponse').style.display).toBe('none');
      expect(document.getElementById('teamModal').value).toBe('');

      expect(jQuery.fn.load).toHaveBeenCalledWith(
        '/wp-admin/admin-ajax.php',
        {
          teamId: 'team-1',
          eventId: 'event-2',
          modal: 'teamModal',
          action: 'racketmanager_team_edit_modal',
          security: 'team-nonce-123',
        },
        expect.any(Function)
      );

      // Verify success status in callback
      loadCallback('<div>Team Edit Content</div>', 'success', {});
      expect(jQuery.fn.modal).toHaveBeenCalledWith('hide');
      expect(document.getElementById('teamModal').style.display).not.toBe('none');
    });

    it('works when event is null or does not have preventDefault', () => {
      container.innerHTML = `
        <div id="splashBlockModal"></div>
        <div id="teamModal"></div>
        <div id="rolesResponse">
          <div id="rolesResponseText"></div>
        </div>
      `;

      jQuery.fn.load = jest.fn();
      expect(() => teamEditModal(null, 'team-1', 'event-2')).not.toThrow();
      expect(() => teamEditModal({}, 'team-1', 'event-2')).not.toThrow();
    });

    it('handles modal exceptions gracefully in try/catch blocks', () => {
      container.innerHTML = `
        <div id="splashBlockModal"></div>
        <div id="teamModal"></div>
        <div id="rolesResponse">
          <div id="rolesResponseText"></div>
        </div>
      `;

      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
      jQuery.fn.modal = jest.fn().mockImplementation(() => {
        throw new Error('Bootstrap modal error');
      });

      let loadCallback;
      jQuery.fn.load = jest.fn(function (url, data, callback) {
        loadCallback = callback;
        return this;
      });

      teamEditModal(null, 'team-1', 'event-2');
      expect(consoleErrorSpy).toHaveBeenCalled();

      // Callback triggers modal hide and show which also throw
      loadCallback('<div>Content</div>', 'success', {});
      expect(consoleErrorSpy).toHaveBeenCalledTimes(3);

      consoleErrorSpy.mockRestore();
    });

    it('handles error status with valid JSON containing message', () => {
      container.innerHTML = `
        <div id="splashBlockModal"></div>
        <div id="teamModal"></div>
        <div id="rolesResponse" style="display: none;">
          <div id="rolesResponseText"></div>
        </div>
      `;

      let loadCallback;
      jQuery.fn.load = jest.fn(function (url, data, callback) {
        loadCallback = callback;
        return this;
      });

      teamEditModal(null, 'team-1', 'event-2');

      loadCallback('{"message":"Team editing not allowed"}', 'error', {});

      expect(document.getElementById('rolesResponseText').innerHTML).toBe('Team editing not allowed');
      expect(document.getElementById('rolesResponse').style.display).not.toBe('none');
    });

    it('handles error status with valid JSON lacking message (fallback text)', () => {
      container.innerHTML = `
        <div id="splashBlockModal"></div>
        <div id="teamModal"></div>
        <div id="rolesResponse" style="display: none;">
          <div id="rolesResponseText"></div>
        </div>
      `;

      let loadCallback;
      jQuery.fn.load = jest.fn(function (url, data, callback) {
        loadCallback = callback;
        return this;
      });

      teamEditModal(null, 'team-1', 'event-2');

      loadCallback('{}', 'error', {});

      expect(document.getElementById('rolesResponseText').innerHTML).toBe('An error occurred');
      expect(document.getElementById('rolesResponse').style.display).not.toBe('none');
    });

    it('handles error status with invalid JSON by delegating to handleAjaxError', () => {
      container.innerHTML = `
        <div id="splashBlockModal"></div>
        <div id="teamModal"></div>
        <div id="rolesResponse" style="display: none;">
          <div id="rolesResponseText"></div>
        </div>
      `;

      let loadCallback;
      jQuery.fn.load = jest.fn(function (url, data, callback) {
        loadCallback = callback;
        return this;
      });

      teamEditModal(null, 'team-1', 'event-2');

      const mockXhr = {
        status: 500,
        statusText: 'Server Error',
        responseJSON: { data: { message: 'Centralized error message' } },
      };

      loadCallback('<html>Not JSON</html>', 'error', mockXhr);

      expect(document.getElementById('rolesResponseText').innerHTML).toBe('Centralized error message');
      expect(document.getElementById('rolesResponse').style.display).not.toBe('none');
      expect(document.getElementById('rolesResponse').className).toContain('alert--danger');
    });
  });

  describe('initializeTeamsAdmin', () => {
    it('triggers getEventTeamMatchDropdown on #teamId change', () => {
      container.innerHTML = `
        <input id="event_id" value="101" />
        <select id="teamId">
          <option value="1">Team 1</option>
          <option value="2">Team 2</option>
        </select>
        <div id="matches"></div>
        <button id="setTeamButton"></button>
      `;

      jQuery.fn.load = jest.fn();

      const teamSelect = document.getElementById('teamId');
      teamSelect.value = '2';
      jQuery(teamSelect).trigger('change');

      expect(jQuery.fn.load).toHaveBeenCalledWith(
        '/wp-admin/admin-ajax.php',
        expect.objectContaining({
          eventId: '101',
          teamId: '2',
          action: 'racketmanager_get_event_team_match_dropdown',
        }),
        expect.any(Function)
      );
    });

    it('triggers teamEditModal on [data-action="open-team-edit-modal"] click', () => {
      container.innerHTML = `
        <div id="splashBlockModal"></div>
        <div id="teamModal"></div>
        <div id="rolesResponse"><div id="rolesResponseText"></div></div>
        <button data-action="open-team-edit-modal" data-team-id="55" data-event-id="77">Edit</button>
      `;

      jQuery.fn.load = jest.fn();

      const btn = document.querySelector('[data-action="open-team-edit-modal"]');
      jQuery(btn).trigger('click');

      expect(jQuery.fn.load).toHaveBeenCalledWith(
        '/wp-admin/admin-ajax.php',
        expect.objectContaining({
          teamId: '55',
          eventId: '77',
          action: 'racketmanager_team_edit_modal',
        }),
        expect.any(Function)
      );
    });

    it('reveals #setTeamButton on change of [data-action="show-set-team-button"] without submitting parent form', () => {
      const form = document.createElement('form');
      form.id = 'team-order-validate';
      form.submit = jest.fn();

      const select = document.createElement('select');
      select.id = 'matchId';
      select.setAttribute('data-action', 'show-set-team-button');
      const opt = document.createElement('option');
      opt.value = '101';
      select.appendChild(opt);
      select.value = '101';

      const setTeamButton = document.createElement('button');
      setTeamButton.id = 'setTeamButton';
      setTeamButton.style.display = 'none';

      form.appendChild(select);
      form.appendChild(setTeamButton);
      container.appendChild(form);

      expect(setTeamButton.style.display).toBe('none');

      select.dispatchEvent(new Event('change', { bubbles: true }));

      // #setTeamButton is revealed
      expect(setTeamButton.style.display).not.toBe('none');
      // Parent form submit is NOT called
      expect(form.submit).not.toHaveBeenCalled();
    });

    it('falls back to document.querySelector(#setTeamButton) when Racketmanager.show_set_team_button is not a function', () => {
      const originalShow = globalThis.Racketmanager?.show_set_team_button;
      delete globalThis.Racketmanager.show_set_team_button;

      container.innerHTML = `
        <select data-action="show-set-team-button">
          <option value="1">Match 1</option>
        </select>
        <button id="setTeamButton" style="display: none;">Set Team</button>
      `;

      const select = container.querySelector('[data-action="show-set-team-button"]');
      const btn = document.getElementById('setTeamButton');
      expect(btn.style.display).toBe('none');

      select.dispatchEvent(new Event('change', { bubbles: true }));
      expect(btn.style.display).toBe('');

      // When #setTeamButton does not exist in DOM, does not throw
      btn.remove();
      expect(() => {
        select.dispatchEvent(new Event('change', { bubbles: true }));
      }).not.toThrow();

      if (originalShow) {
        globalThis.Racketmanager.show_set_team_button = originalShow;
      }
    });

    it('ignores change events on elements not matching data-action="show-set-team-button"', () => {
      container.innerHTML = `
        <input id="otherInput" value="abc" />
        <button id="setTeamButton" style="display: none;">Set Team</button>
      `;

      const input = document.getElementById('otherInput');
      input.dispatchEvent(new Event('change', { bubbles: true }));

      expect(document.getElementById('setTeamButton').style.display).toBe('none');

      // Also when target has no closest method
      const fakeEvt = { target: {} };
      expect(() => {
        document.dispatchEvent(new Event('change'));
      }).not.toThrow();
    });

    it('provides globalThis.Racketmanager.show_set_team_button that shows #setTeamButton', () => {
      container.innerHTML = `<button id="setTeamButton" style="display: none;"></button>`;
      expect(typeof globalThis.Racketmanager?.show_set_team_button).toBe('function');

      globalThis.Racketmanager.show_set_team_button();
      expect(document.getElementById('setTeamButton').style.display).not.toBe('none');
    });

    it('handles subsequent initialization and initializes when globalThis.Racketmanager is undefined', () => {
      delete globalThis.Racketmanager;
      initializeTeamsAdmin();
      expect(typeof globalThis.Racketmanager?.show_set_team_button).toBe('function');
    });
  });
});
