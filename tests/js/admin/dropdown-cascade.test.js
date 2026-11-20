/**
 * Tests for admin dropdown cascade requests: payloads, rendering, error and completion handling.
 */
import {
  getEventDropdown,
  getLeagueDropdown,
  getSeasonDropdown,
  getMatchDropdown,
  registerDropdownCascades,
} from '../../../src/js/admin/features/dropdowns/dropdown-cascade.js';
import { createJQueryStub } from './support/jquery-stub.js';

describe('Admin feature: dropdown-cascade requests', () => {
  let container;
  let stub;
  const request = () => stub.jq.ajax.mock.calls[0][0];

  beforeEach(() => {
    window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'test-nonce-123' };
    stub = createJQueryStub();
    window.jQuery = stub.jq;

    container = document.createElement('div');
    container.innerHTML = `
      <div id="events"></div>
      <div id="leagues" style="display:block;"></div>
      <div id="seasons" style="display:block;"></div>
      <div id="matches"></div>
    `;
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.clearAllMocks();
  });

  describe('getEventDropdown', () => {
    it('hides leagues and seasons and posts the competition id with nonce', () => {
      getEventDropdown(3);

      expect(document.querySelector('#leagues').style.display).toBe('none');
      expect(document.querySelector('#seasons').style.display).toBe('none');
      expect(request().url).toBe('/wp-admin/admin-ajax.php');
      expect(request().data).toEqual({
        competition_id: 3,
        action: 'racketmanager_get_event_dropdown',
        security: 'test-nonce-123',
      });
    });

    it('renders returned markup into #events and shows the container on complete', () => {
      getEventDropdown(3);
      request().success({ data: '<option value="5">Event 5</option>' });
      request().complete();

      const el = document.querySelector('#events');
      expect(el.innerHTML).toBe('<option value="5">Event 5</option>');
      expect(el.style.display).toBe('');
    });

    it('renders responseJSON message in #events with message-error on failure', () => {
      getEventDropdown(3);
      request().error({ responseJSON: { data: 'Competition not found' } });
      request().complete();

      const el = document.querySelector('#events');
      expect(el.textContent).toBe('Competition not found');
      expect(el.classList.contains('message-error')).toBe(true);
      expect(el.style.display).toBe('');
    });

    it('falls back to statusText when responseJSON is absent', () => {
      getEventDropdown(3);
      request().error({ statusText: 'Bad Gateway' });

      expect(document.querySelector('#events').textContent).toBe('Bad Gateway');
    });

    it('still hides sections but sends no request when jQuery is unavailable', () => {
      window.jQuery = {};
      getEventDropdown(3);

      expect(document.querySelector('#leagues').style.display).toBe('none');
      expect(stub.jq.ajax).not.toHaveBeenCalled();
    });

    it('handles missing DOM elements in getEventDropdown gracefully', () => {
      container.innerHTML = '';
      expect(() => {
        getEventDropdown(3);
        request().success({ data: 'ok' });
        request().error({ statusText: 'err' });
        request().complete();
      }).not.toThrow();
    });
  });

  describe('getLeagueDropdown', () => {
    it('hides seasons only and posts the event id with nonce', () => {
      getLeagueDropdown(5);

      expect(document.querySelector('#seasons').style.display).toBe('none');
      expect(document.querySelector('#leagues').style.display).toBe('block');
      expect(request().data).toEqual({
        event_id: 5,
        action: 'racketmanager_get_league_dropdown',
        security: 'test-nonce-123',
      });
    });

    it('renders responseJSON message in #leagues with message-error on failure', () => {
      getLeagueDropdown(5);
      request().error({ responseJSON: { data: 'Event closed' } });
      request().complete();

      const el = document.querySelector('#leagues');
      expect(el.textContent).toBe('Event closed');
      expect(el.classList.contains('message-error')).toBe(true);
      expect(el.style.display).toBe('');
    });

    it('falls back to statusText on error when responseJSON is absent', () => {
      getLeagueDropdown(5);
      request().error({ statusText: 'Gateway Timeout' });

      const el = document.querySelector('#leagues');
      expect(el.textContent).toBe('Gateway Timeout');
      expect(el.classList.contains('message-error')).toBe(true);
    });

    it('renders returned markup in #leagues on success', () => {
      getLeagueDropdown(5);
      request().success({ data: '<option value="2">Division 2</option>' });

      const el = document.querySelector('#leagues');
      expect(el.innerHTML).toBe('<option value="2">Division 2</option>');
    });

    it('handles missing DOM elements in getLeagueDropdown gracefully', () => {
      container.innerHTML = '';
      expect(() => {
        getLeagueDropdown(5);
        request().success({ data: 'ok' });
        request().error({ statusText: 'err' });
        request().complete();
      }).not.toThrow();
    });

    it('still hides seasons but sends no request when jQuery is unavailable', () => {
      window.jQuery = {};
      getLeagueDropdown(5);

      expect(document.querySelector('#seasons').style.display).toBe('none');
      expect(stub.jq.ajax).not.toHaveBeenCalled();
    });
  });

  describe('getSeasonDropdown', () => {
    it('posts the league id with nonce and renders seasons on success', () => {
      getSeasonDropdown(12);
      expect(request().data).toEqual({
        league_id: 12,
        action: 'racketmanager_get_season_dropdown',
        security: 'test-nonce-123',
      });

      request().success({ data: '<option value="2026">2026</option>' });
      request().complete();
      expect(document.querySelector('#seasons').innerHTML).toBe('<option value="2026">2026</option>');
      expect(document.querySelector('#seasons').style.display).toBe('');
    });

    it('renders responseJSON message in #seasons with message-error on failure', () => {
      getSeasonDropdown(12);
      request().error({ responseJSON: { data: 'League not found' } });
      request().complete();

      const el = document.querySelector('#seasons');
      expect(el.textContent).toBe('League not found');
      expect(el.classList.contains('message-error')).toBe(true);
      expect(el.style.display).toBe('');
    });

    it('falls back to statusText on error when responseJSON is absent', () => {
      getSeasonDropdown(12);
      request().error({ statusText: 'Not Found' });

      const el = document.querySelector('#seasons');
      expect(el.textContent).toBe('Not Found');
      expect(el.classList.contains('message-error')).toBe(true);
    });

    it('handles missing DOM elements in getSeasonDropdown gracefully', () => {
      container.innerHTML = '';
      expect(() => {
        getSeasonDropdown(12);
        request().success({ data: 'ok' });
        request().error({ statusText: 'err' });
        request().complete();
      }).not.toThrow();
    });

    it('sends no request when jQuery is unavailable', () => {
      window.jQuery = {};
      getSeasonDropdown(12);

      expect(stub.jq.ajax).not.toHaveBeenCalled();
    });
  });

  describe('getMatchDropdown', () => {
    it('posts league id and season with nonce and renders matches on success', () => {
      getMatchDropdown(12, '2026');
      expect(request().data).toEqual({
        league_id: 12,
        season: '2026',
        action: 'racketmanager_get_match_dropdown',
        security: 'test-nonce-123',
      });

      request().success({ data: '<option value="101">Match 101</option>' });
      expect(document.querySelector('#matches').innerHTML).toBe('<option value="101">Match 101</option>');
    });

    it('shows #matches and renders error with message-error on failure', () => {
      getMatchDropdown(12, '2026');
      request().error({ statusText: 'Server Error' });

      const el = document.querySelector('#matches');
      expect(el.textContent).toBe('Server Error');
      expect(el.classList.contains('message-error')).toBe(true);
      expect(el.style.display).toBe('');
    });

    it('renders responseJSON error data in #matches on failure when present', () => {
      getMatchDropdown(12, '2026');
      request().error({ responseJSON: { data: 'No matches scheduled' } });

      const el = document.querySelector('#matches');
      expect(el.textContent).toBe('No matches scheduled');
      expect(el.classList.contains('message-error')).toBe(true);
      expect(el.style.display).toBe('');
    });

    it('handles missing DOM elements in getMatchDropdown gracefully', () => {
      container.innerHTML = '';
      expect(() => {
        getMatchDropdown(12, '2026');
        request().success({ data: 'ok' });
        request().error({ statusText: 'err' });
      }).not.toThrow();
    });

    it('sends no request when jQuery is unavailable', () => {
      window.jQuery = {};
      getMatchDropdown(12, '2026');

      expect(stub.jq.ajax).not.toHaveBeenCalled();
    });
  });

  describe('registerDropdownCascades', () => {
    let actionsContainer;

    beforeEach(() => {
      actionsContainer = document.createElement('div');
      document.body.appendChild(actionsContainer);

      window.Racketmanager = window.Racketmanager || {};
      window.Racketmanager.getEventDropdown = jest.fn();
      window.Racketmanager.getLeagueDropdown = jest.fn();
      window.Racketmanager.getSeasonDropdown = jest.fn();
      window.Racketmanager.getMatchDropdown = jest.fn();
    });

    afterEach(() => {
      document.body.removeChild(actionsContainer);
      delete window.Racketmanager.getEventDropdown;
      delete window.Racketmanager.getLeagueDropdown;
      delete window.Racketmanager.getSeasonDropdown;
      delete window.Racketmanager.getMatchDropdown;
    });

    it('returns early when root is null or lacks addEventListener', () => {
      expect(() => registerDropdownCascades(null)).not.toThrow();
      expect(() => registerDropdownCascades({})).not.toThrow();
    });

    it('does not re-register listeners when called twice on the same root', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);
      registerDropdownCascades(root);

      const select = document.createElement('select');
      select.setAttribute('data-action', 'get-event-dropdown');
      select.innerHTML = '<option value="42" selected>42</option>';
      root.appendChild(select);

      select.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.getEventDropdown).toHaveBeenCalledTimes(1);
    });

    it('dispatches get-event-dropdown, get-league-dropdown, and get-season-dropdown on change', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const selEvent = document.createElement('select');
      selEvent.setAttribute('data-action', 'get-event-dropdown');
      selEvent.innerHTML = '<option value="10" selected>10</option>';
      root.appendChild(selEvent);
      selEvent.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.getEventDropdown).toHaveBeenCalledWith('10');

      const selLeague = document.createElement('select');
      selLeague.setAttribute('data-action', 'get-league-dropdown');
      selLeague.innerHTML = '<option value="20" selected>20</option>';
      root.appendChild(selLeague);
      selLeague.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.getLeagueDropdown).toHaveBeenCalledWith('20');

      const selSeason = document.createElement('select');
      selSeason.setAttribute('data-action', 'get-season-dropdown');
      selSeason.innerHTML = '<option value="30" selected>30</option>';
      root.appendChild(selSeason);
      selSeason.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.getSeasonDropdown).toHaveBeenCalledWith('30');
    });

    it('dispatches get-match-dropdown reading data-league-id and dataset fallback', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const selMatch = document.createElement('select');
      selMatch.setAttribute('data-action', 'get-match-dropdown');
      selMatch.setAttribute('data-league-id', '99');
      selMatch.innerHTML = '<option value="2026" selected>2026</option>';
      root.appendChild(selMatch);

      selMatch.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.getMatchDropdown).toHaveBeenCalledWith('99', '2026');
    });

    it('does nothing when changed element has no action, empty action, or missing Racketmanager', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const plain = document.createElement('select');
      root.appendChild(plain);
      plain.dispatchEvent(new Event('change', { bubbles: true }));

      plain.setAttribute('data-action', '');
      plain.dispatchEvent(new Event('change', { bubbles: true }));

      plain.setAttribute('data-action', 'unknown-dropdown-action');
      plain.dispatchEvent(new Event('change', { bubbles: true }));

      // Missing Racketmanager functions
      delete window.Racketmanager.getEventDropdown;
      delete window.Racketmanager.getLeagueDropdown;
      delete window.Racketmanager.getSeasonDropdown;
      delete window.Racketmanager.getMatchDropdown;

      for (const act of ['get-event-dropdown', 'get-league-dropdown', 'get-season-dropdown', 'get-match-dropdown']) {
        plain.setAttribute('data-action', act);
        expect(() => plain.dispatchEvent(new Event('change', { bubbles: true }))).not.toThrow();
      }
    });

    it('registers on document by default when root parameter is omitted', () => {
      try {
        registerDropdownCascades();
        expect(document.__rmDropdownCascadesRegistered).toBe(true);
      } finally {
        delete document.__rmDropdownCascadesRegistered;
      }
    });

    it('handles change events when target lacks closest method', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const ev = new Event('change', { bubbles: true });
      Object.defineProperty(ev, 'target', { value: {} });

      expect(() => root.dispatchEvent(ev)).not.toThrow();
    });

    it('resolves action via dataset.action when getAttribute returns null', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const el = document.createElement('select');
      el.dataset.action = 'get-event-dropdown';
      el.innerHTML = '<option value="55" selected>55</option>';
      const origGetAttribute = el.getAttribute.bind(el);
      el.getAttribute = jest.fn((name) => (name === 'data-action' ? null : origGetAttribute(name)));
      root.appendChild(el);

      el.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.getEventDropdown).toHaveBeenCalledWith('55');
    });

    it('resolves leagueId via dataset.leagueId when getAttribute returns null', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const el = document.createElement('select');
      el.setAttribute('data-action', 'get-match-dropdown');
      el.dataset.leagueId = '88';
      el.innerHTML = '<option value="2027" selected>2027</option>';
      const origGetAttribute = el.getAttribute.bind(el);
      el.getAttribute = jest.fn((name) => (name === 'data-league-id' ? null : origGetAttribute(name)));
      root.appendChild(el);

      el.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.getMatchDropdown).toHaveBeenCalledWith('88', '2027');
    });

    it('handles element with null dataset when data-action attribute is empty', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const el = document.createElement('select');
      el.setAttribute('data-action', '');
      Object.defineProperty(el, 'dataset', { value: null, configurable: true });
      root.appendChild(el);

      expect(() => el.dispatchEvent(new Event('change', { bubbles: true }))).not.toThrow();
    });

    it('handles element where data-action attribute is empty and dataset.action is missing', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const el = document.createElement('select');
      el.setAttribute('data-action', '');
      root.appendChild(el);

      expect(() => el.dispatchEvent(new Event('change', { bubbles: true }))).not.toThrow();
    });

    it('handles get-match-dropdown when dataset is null and data-league-id attribute is null', () => {
      const root = document.createElement('div');
      registerDropdownCascades(root);

      const el = document.createElement('select');
      el.setAttribute('data-action', 'get-match-dropdown');
      const origGetAttribute = el.getAttribute.bind(el);
      el.getAttribute = jest.fn((name) => (name === 'data-league-id' ? null : origGetAttribute(name)));
      Object.defineProperty(el, 'dataset', { value: null, configurable: true });
      el.innerHTML = '<option value="2028" selected>2028</option>';
      root.appendChild(el);

      el.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.getMatchDropdown).toHaveBeenCalledWith(null, '2028');
    });
  });
});
