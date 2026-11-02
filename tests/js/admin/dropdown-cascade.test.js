/**
 * Tests for admin dropdown cascade requests: payloads, rendering, error and completion handling.
 */
import {
  getEventDropdown,
  getLeagueDropdown,
  getSeasonDropdown,
  getMatchDropdown,
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

      const el = document.querySelector('#seasons');
      expect(el.textContent).toBe('League not found');
      expect(el.classList.contains('message-error')).toBe(true);
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
  });
});
