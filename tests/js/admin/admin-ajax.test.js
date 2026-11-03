/**
 * Tests for admin AJAX and utility calculation methods.
 */
import {
  amendDate,
  setNumMatchDays,
  setEndDate,
  getImportOption,
  setMatchDate,
  setMatchDayPopUp,
  setEventName,
  setTournamentOpenDate,
  sendFixtures,
  notifyTournamentEntryOpen,
  notify_open,
  emailConstitution,
  notifyTeams,
  insertHomeStadium,
  saveAddPoints,
} from '../../../src/js/admin/features/ajax/admin-ajax.js';
import { createJQueryStub } from './support/jquery-stub.js';

describe('Admin feature: admin-ajax functions', () => {
  let container;
  let stub;
  const lastRequest = () => stub.jq.ajax.mock.calls[stub.jq.ajax.mock.calls.length - 1][0];

  beforeEach(() => {
    window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'test-nonce-123' };

    stub = createJQueryStub();
    window.jQuery = stub.jq;

    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.useRealTimers();
    jest.clearAllMocks();
  });

  describe('amendDate', () => {
    it('adjusts dates forward and backward correctly', () => {
      expect(amendDate('2026-05-10', 7)).toBe('2026-05-17');
      expect(amendDate('2026-05-10', -5)).toBe('2026-05-05');
    });
  });

  describe('setNumMatchDays', () => {
    it('calculates match days for single round robin with even number of teams', () => {
      container.innerHTML = `
        <input type="number" id="max_teams" value="6">
        <input type="checkbox" id="homeAwayTrue">
        <input type="number" id="num_match_days" value="0">
      `;
      setNumMatchDays();
      expect(container.querySelector('#num_match_days').value).toBe('5');
    });

    it('calculates match days for home and away with odd number of teams', () => {
      container.innerHTML = `
        <input type="number" id="max_teams" value="5">
        <input type="checkbox" id="homeAwayTrue" checked>
        <input type="number" id="num_match_days" value="0">
      `;
      setNumMatchDays();
      // 5 teams odd -> rounded up to 6 -> (6-1)*2 = 10
      expect(container.querySelector('#num_match_days').value).toBe('10');
    });
  });

  describe('setEndDate', () => {
    it('calculates end date for a league competition', () => {
      container.innerHTML = `
        <input type="text" id="competitionType" value="league">
        <input type="date" id="dateStart" value="2026-06-01">
        <input type="date" id="dateEnd" value="">
        <input type="number" id="round_length" value="7">
        <input type="number" id="filler_weeks" value="1">
        <input type="number" id="home_away_diff" value="0">
        <input type="number" id="num_match_days" value="4">
      `;
      setEndDate();
      // roundLengthWeeks = 7/7 = 1
      // endDateGapWeeks = (1 * 4) + 1 + 0 = 5 weeks = 35 days
      // endDateGap = 35 - 1 = 34 days
      // 2026-06-01 + 34 days = 2026-07-05
      expect(container.querySelector('#dateEnd').value).toBe('2026-07-05');
    });
  });

  describe('getImportOption', () => {
    it('shows and hides relevant sections based on selection', () => {
      container.innerHTML = `
        <div id="competitions" style="display:none;"></div>
        <div id="events" style="display:none;"></div>
        <div id="leagues" style="display:none;"></div>
        <div id="seasons" style="display:none;"></div>
        <div id="clubs" style="display:none;"></div>
      `;

      getImportOption('table');
      expect(container.querySelector('#competitions').style.display).toBe('');
      expect(container.querySelector('#clubs').style.display).toBe('none');

      getImportOption('clubplayers');
      expect(container.querySelector('#clubs').style.display).toBe('');
      expect(container.querySelector('#competitions').style.display).toBe('none');
    });
  });

  describe('setMatchDate and setMatchDayPopUp', () => {
    it('populates remaining rows in add mode for first row index', () => {
      container.innerHTML = `
        <input id="myDatePicker[1]" value="">
        <input id="myDatePicker[2]" value="">
        <input id="match_day_1" value="">
        <input id="match_day_2" value="">
      `;

      setMatchDate('2026-08-15', 0, 3, 'add');
      expect(container.querySelector('input[id="myDatePicker[1]"]').value).toBe('2026-08-15');
      expect(container.querySelector('input[id="myDatePicker[2]"]').value).toBe('2026-08-15');

      setMatchDayPopUp('Round 1', 0, 3, 'add');
      expect(container.querySelector('#match_day_1').value).toBe('Round 1');
      expect(container.querySelector('#match_day_2').value).toBe('Round 1');
    });
  });

  describe('setEventName', () => {
    it('generates event name from type and age limit if event name is empty', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
        <input type="text" id="age_limit" value="18">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('U18 Singles');
    });

    it('does not overwrite existing event name', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="Custom Name">
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
        <input type="text" id="age_limit" value="18">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('Custom Name');
    });

    it('formats event name without prefix when age limit is open', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type">
          <option value="doubles" selected>Doubles</option>
        </select>
        <input type="text" id="age_limit" value="open">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('Doubles');
    });

    it('formats junior event names for boundary age limits (<= 21)', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
        <input type="text" id="age_limit" value="21">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('U21 Singles');
    });

    it('formats senior event names for boundary and senior age limits (>= 30)', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
        <input type="text" id="age_limit" value="30">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('O30 Singles');

      container.querySelector('#event_name').value = '';
      container.querySelector('#age_limit').value = '45';
      setEventName();
      expect(container.querySelector('#event_name').value).toBe('O45 Singles');
    });

    it('formats event name without prefix for intermediate age limits between 22 and 29', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
        <input type="text" id="age_limit" value="25">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('Singles');
    });

    it('formats event name without prefix when age limit is non-numeric', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
        <input type="text" id="age_limit" value="invalid">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('Singles');
    });

    it('does not modify event name if type element is missing or has no selected option', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <input type="text" id="age_limit" value="18">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('');

      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type"></select>
        <input type="text" id="age_limit" value="18">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('');
    });

    it('does not modify event name if age limit element is missing or empty', () => {
      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('');

      container.innerHTML = `
        <input type="text" id="event_name" value="">
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
        <input type="text" id="age_limit" value="">
      `;

      setEventName();
      expect(container.querySelector('#event_name').value).toBe('');
    });

    it('does not throw when event_name element does not exist in DOM', () => {
      container.innerHTML = `
        <select id="type">
          <option value="singles" selected>Singles</option>
        </select>
        <input type="text" id="age_limit" value="18">
      `;

      expect(() => setEventName()).not.toThrow();
    });
  });

  describe('setTournamentOpenDate', () => {
    const renderDom = (overrides = {}) => {
      container.innerHTML = `
        <input id="dateOpen" value="${overrides.dateOpen ?? ''}">
        <input id="dateStart" value="2026-07-01">
        <input id="dateClose" value="${overrides.dateClose ?? ''}">
        <input id="dateWithdraw" value="${overrides.dateWithdraw ?? ''}">
        <input id="grade" value="4">
        <div id="alert-dates" style="display:none;"></div>
        <div id="alert-dates-response"></div>
      `;
    };

    it('sends correct payload and sets dates and success alert on success', () => {
      renderDom();

      setTournamentOpenDate();
      expect(stub.jq.ajax).toHaveBeenCalledTimes(1);
      expect(lastRequest().url).toBe('/wp-admin/admin-ajax.php');
      expect(lastRequest().data).toEqual({
        dateStart: '2026-07-01',
        grade: '4',
        action: 'racketmanager_set_tournament_dates',
        security: 'test-nonce-123',
      });

      lastRequest().success({
        data: {
          msg: 'Dates calculated',
          date_open: '2026-05-01',
          date_closing: '2026-06-20',
          date_withdraw: '2026-06-25',
        },
      });
      lastRequest().complete();

      expect(container.querySelector('#dateOpen').value).toBe('2026-05-01');
      expect(container.querySelector('#dateClose').value).toBe('2026-06-20');
      expect(container.querySelector('#dateWithdraw').value).toBe('2026-06-25');
      expect(container.querySelector('#alert-dates-response').textContent).toBe('Dates calculated');
      expect(container.querySelector('#alert-dates').classList.contains('alert--success')).toBe(true);
      expect(container.querySelector('#alert-dates').style.display).not.toBe('none');
    });

    it('fades the success alert with delay(10000).fadeOut(slow) when jQuery FX is available', () => {
      renderDom();

      setTournamentOpenDate();
      lastRequest().success({ data: { msg: 'ok', date_open: '', date_closing: '', date_withdraw: '' } });

      expect(stub.delay).toHaveBeenCalledWith(10000);
      expect(stub.fadeOut).toHaveBeenCalledWith('slow');
    });

    it('falls back to a 10 second timer when jQuery FX is unavailable', () => {
      jest.useFakeTimers();
      renderDom();
      stub.jq.mockReturnValueOnce({});

      setTournamentOpenDate();
      lastRequest().success({ data: { msg: 'ok', date_open: '', date_closing: '', date_withdraw: '' } });
      lastRequest().complete();
      expect(container.querySelector('#alert-dates').style.display).toBe('');

      jest.advanceTimersByTime(10000);
      expect(container.querySelector('#alert-dates').style.display).toBe('none');
    });

    it('clears date fields when returned dates are empty string or null', () => {
      renderDom({ dateOpen: '2026-01-01', dateClose: '2026-02-01', dateWithdraw: '2026-03-01' });

      setTournamentOpenDate();
      lastRequest().success({
        data: {
          msg: 'Dates reset',
          date_open: '',
          date_closing: '',
          date_withdraw: null,
        },
      });

      expect(container.querySelector('#dateOpen').value).toBe('');
      expect(container.querySelector('#dateClose').value).toBe('');
      expect(container.querySelector('#dateWithdraw').value).toBe('');
    });

    it('renders error message and adds alert--danger on request failure', () => {
      renderDom();

      setTournamentOpenDate();
      lastRequest().error({
        responseJSON: { data: 'Invalid tournament grade' },
      });
      lastRequest().complete();

      expect(container.querySelector('#alert-dates-response').innerHTML).toBe('Invalid tournament grade');
      expect(container.querySelector('#alert-dates').classList.contains('alert--danger')).toBe(true);
      expect(container.querySelector('#alert-dates').style.display).toBe('');
    });

    it('uses statusText when the failed request has no responseJSON', () => {
      renderDom();

      setTournamentOpenDate();
      lastRequest().error({ statusText: 'Service Unavailable' });

      expect(container.querySelector('#alert-dates-response').textContent).toBe('Service Unavailable');
      expect(container.querySelector('#alert-dates').classList.contains('alert--danger')).toBe(true);
      expect(stub.delay).not.toHaveBeenCalled();
    });
  });

  describe('sendFixtures', () => {
    const renderDom = () => {
      container.innerHTML = `
        <div id="alert-season" style="display:none;"></div>
        <div id="alert-season-response"></div>
      `;
    };

    it('sends correct payload and sets success message', () => {
      renderDom();
      const e = { preventDefault: jest.fn() };

      sendFixtures(e, 42);
      expect(e.preventDefault).toHaveBeenCalled();
      expect(stub.jq.ajax).toHaveBeenCalledTimes(1);
      expect(lastRequest().data).toEqual({
        security: 'test-nonce-123',
        eventId: 42,
        action: 'racketmanager_send_fixtures',
      });

      lastRequest().success({ data: { msg: 'Fixtures sent successfully' } });
      lastRequest().complete();

      expect(container.querySelector('#alert-season-response').textContent).toBe('Fixtures sent successfully');
      expect(container.querySelector('#alert-season').classList.contains('alert--success')).toBe(true);
      expect(container.querySelector('#alert-season').style.display).toBe('');
    });

    it('renders responseJSON message and adds alert--danger on error', () => {
      renderDom();

      sendFixtures({ preventDefault: jest.fn() }, 42);
      lastRequest().error({ responseJSON: { data: 'No teams in event' } });
      lastRequest().complete();

      expect(container.querySelector('#alert-season-response').innerHTML).toBe('No teams in event');
      expect(container.querySelector('#alert-season').classList.contains('alert--danger')).toBe(true);
    });
  });

  describe('notifyTournamentEntryOpen and notify_open', () => {
    it('posts racketmanager_notify_tournament_entries_open with tournament ID', () => {
      container.innerHTML = `
        <div id="alert-tournaments" style="display:none;"></div>
        <div id="alert-tournaments-response"></div>
      `;
      const e = { preventDefault: jest.fn() };

      notifyTournamentEntryOpen(e, 88);
      expect(e.preventDefault).toHaveBeenCalled();
      expect(lastRequest().data).toEqual({
        tournamentId: 88,
        action: 'racketmanager_notify_tournament_entries_open',
        security: 'test-nonce-123',
      });

      lastRequest().success({ data: 'Entries open email queued' });
      expect(container.querySelector('#alert-tournaments-response').textContent).toBe('Entries open email queued');
      expect(container.querySelector('#alert-tournaments').classList.contains('alert--success')).toBe(true);
    });

    it('renders error on tournament entries failure', () => {
      container.innerHTML = `
        <div id="alert-tournaments" style="display:none;"></div>
        <div id="alert-tournaments-response"></div>
      `;

      notifyTournamentEntryOpen({ preventDefault: jest.fn() }, 88);
      lastRequest().error({ statusText: 'Forbidden' });

      expect(container.querySelector('#alert-tournaments-response').textContent).toBe('Forbidden');
      expect(container.querySelector('#alert-tournaments').classList.contains('alert--danger')).toBe(true);
    });

    it('posts racketmanager_notify_competition_entries_open with competition ID and season', () => {
      container.innerHTML = `
        <div id="alert-season" style="display:none;"></div>
        <div id="alert-season-response"></div>
      `;
      const e = { preventDefault: jest.fn() };

      notify_open(e, 14, '2026');
      expect(e.preventDefault).toHaveBeenCalled();
      expect(lastRequest().data).toEqual({
        competitionId: 14,
        season: '2026',
        action: 'racketmanager_notify_competition_entries_open',
        security: 'test-nonce-123',
      });

      lastRequest().success({ data: 'Competition entries open' });
      expect(container.querySelector('#alert-season-response').textContent).toBe('Competition entries open');
      expect(container.querySelector('#alert-season').classList.contains('alert--success')).toBe(true);
    });

    it('renders error on competition entries failure', () => {
      container.innerHTML = `
        <div id="alert-season" style="display:none;"></div>
        <div id="alert-season-response"></div>
      `;

      notify_open({ preventDefault: jest.fn() }, 14, '2026');
      lastRequest().error({ responseJSON: { data: 'Season closed' } });

      expect(container.querySelector('#alert-season-response').innerHTML).toBe('Season closed');
      expect(container.querySelector('#alert-season').classList.contains('alert--danger')).toBe(true);
    });
  });

  describe('emailConstitution and notifyTeams', () => {
    it('emailConstitution posts racketmanager_email_constitution and displays success message with timed fade', () => {
      container.innerHTML = `<div id="notifyMessage-constitution" style="display:none;"></div>`;
      const e = { preventDefault: jest.fn() };
      emailConstitution(e, 19);

      expect(e.preventDefault).toHaveBeenCalled();
      expect(lastRequest().data).toEqual({
        eventId: 19,
        action: 'racketmanager_email_constitution',
        security: 'test-nonce-123',
      });

      lastRequest().success({ data: 'Constitution emailed' });
      const el = container.querySelector('#notifyMessage-constitution');
      expect(el.textContent).toBe('Constitution emailed');
      expect(el.className).toBe('message-success');
      expect(el.style.display).toBe('');
      expect(stub.delay).toHaveBeenCalledWith(10000);
      expect(stub.fadeOut).toHaveBeenCalledWith('slow');
    });

    it('emailConstitution renders responseJSON message as message-error', () => {
      container.innerHTML = `<div id="notifyMessage-constitution" style="display:none;"></div>`;
      emailConstitution({ preventDefault: jest.fn() }, 19);

      lastRequest().error({ responseJSON: { data: 'No constitution found' } });
      const el = container.querySelector('#notifyMessage-constitution');
      expect(el.textContent).toBe('No constitution found');
      expect(el.className).toBe('message-error');
      expect(el.style.display).toBe('');
      expect(stub.fadeOut).not.toHaveBeenCalled();
    });

    it('notifyTeams posts racketmanager_notify_teams and updates match notify message with timed fade', () => {
      container.innerHTML = `<div id="notifyMessage-55" style="display:none;"></div>`;
      notifyTeams(55);

      expect(lastRequest().data).toEqual({
        matchId: 55,
        action: 'racketmanager_notify_teams',
        security: 'test-nonce-123',
      });

      lastRequest().success({ data: { message: 'Teams notified for match 55' } });
      const el = container.querySelector('#notifyMessage-55');
      expect(el.textContent).toBe('Teams notified for match 55');
      expect(el.className).toBe('message-success');
      expect(stub.delay).toHaveBeenCalledWith(10000);
      expect(stub.fadeOut).toHaveBeenCalledWith('slow');
    });

    it('notifyTeams renders statusText as message-error when no responseJSON is present', () => {
      container.innerHTML = `<div id="notifyMessage-56" style="display:none;"></div>`;
      notifyTeams(56);

      lastRequest().error({ statusText: 'Gateway Timeout' });
      const el = container.querySelector('#notifyMessage-56');
      expect(el.textContent).toBe('Gateway Timeout');
      expect(el.className).toBe('message-error');
      expect(stub.fadeOut).not.toHaveBeenCalled();
    });
  });

  describe('insertHomeStadium', () => {
    it('sets stadium value into empty location input but preserves existing value', () => {
      container.innerHTML = `
        <input id="location_1" value="">
        <input id="location_2" value="Existing Stadium">
        <div id="feedback"></div>
      `;

      insertHomeStadium(101, 1);
      expect(stub.jq.ajax).toHaveBeenCalledTimes(1);
      expect(lastRequest().data).toEqual({
        team_id: 101,
        action: 'racketmanager_insert_home_stadium',
        security: 'test-nonce-123',
      });

      lastRequest().success({ data: 'Centre Court' });
      expect(container.querySelector('#location_1').value).toBe('Centre Court');

      insertHomeStadium(102, 2);
      stub.jq.ajax.mock.calls[1][0].success({ data: 'Court 2' });
      expect(container.querySelector('#location_2').value).toBe('Existing Stadium');
    });

    it('renders responseJSON message in feedback as message-error on failure', () => {
      container.innerHTML = `
        <input id="location_1" value="">
        <div id="feedback"></div>
      `;

      insertHomeStadium(101, 1);
      lastRequest().error({ responseJSON: { data: 'No home stadium set' } });

      const feedback = container.querySelector('#feedback');
      expect(feedback.innerHTML).toBe('No home stadium set');
      expect(feedback.classList.contains('message-error')).toBe(true);
      expect(container.querySelector('#location_1').value).toBe('');
    });
  });

  describe('saveAddPoints', () => {
    const renderDom = (id) => {
      container.innerHTML = `
        <span id="loading_${id}" style="display:none;"></span>
        <div id="feedback_${id}" style="display:none;"></div>
      `;
    };

    it('posts racketmanager_save_add_points, fades loading with fast timing, and reloads page on success', () => {
      renderDom(7);
      const reloadPage = jest.fn();

      saveAddPoints('3', 7, reloadPage);
      expect(lastRequest().data).toEqual({
        action: 'racketmanager_save_add_points',
        security: 'test-nonce-123',
        table_id: 7,
        points: '3',
      });
      expect(container.querySelector('#loading_7').style.display).toBe('inline');

      lastRequest().success();
      lastRequest().complete();

      expect(stub.jq).toHaveBeenCalledWith('#loading_7');
      expect(stub.fadeOut).toHaveBeenCalledWith('fast');
      expect(reloadPage).toHaveBeenCalledTimes(1);
      expect(container.querySelector('#loading_7').style.display).toBe('none');
    });

    it('does not reload the page when the request fails', () => {
      renderDom(8);
      const reloadPage = jest.fn();

      saveAddPoints('-2', 8, reloadPage);
      lastRequest().error({
        responseJSON: { data: 'Points cannot be negative' },
      });
      lastRequest().complete();

      expect(reloadPage).not.toHaveBeenCalled();
      expect(container.querySelector('#loading_8').style.display).toBe('none');
    });

    it('renders error message in feedback container on failure', () => {
      renderDom(8);

      saveAddPoints('-2', 8, jest.fn());
      lastRequest().error({
        responseJSON: { data: 'Points cannot be negative' },
      });

      const notify = container.querySelector('#feedback_8');
      expect(notify.textContent).toBe('Points cannot be negative');
      expect(notify.classList.contains('message-error')).toBe(true);
      expect(notify.style.display).toBe('inline-block');
    });
  });
});
