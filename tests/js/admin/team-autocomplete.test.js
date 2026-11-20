/**
 * Tests for admin team form autocomplete: player details lookup and field population.
 */
import { getPlayerDetails, initTeamAutocomplete } from '../../../src/js/admin/features/forms/team-autocomplete.js';
import { safeAutocomplete } from '../../../src/js/features/autocomplete/autocomplete-helpers.js';

jest.mock('../../../src/js/features/autocomplete/autocomplete-helpers.js', () => ({
  safeAutocomplete: jest.fn(),
}));

describe('Admin feature: team-autocomplete', () => {
  let container;
  let ajax;

  const optionsFor = (selector) => {
    const call = safeAutocomplete.mock.calls.find(([sel]) => sel === selector);
    return call ? call[1] : undefined;
  };

  beforeEach(() => {
    window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'test-nonce-123' };
    ajax = jest.fn();
    window.jQuery = { ajax };
    window.alert = jest.fn();

    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.clearAllMocks();
  });

  describe('getPlayerDetails', () => {
    it('posts name, club, nonce and action synchronously', () => {
      getPlayerDetails('Smith', 4);

      expect(ajax).toHaveBeenCalledTimes(1);
      const opts = ajax.mock.calls[0][0];
      expect(opts.url).toBe('/wp-admin/admin-ajax.php');
      expect(opts.async).toBe(false);
      expect(opts.data).toEqual({
        type: 'name',
        name: 'Smith',
        club: 4,
        security: 'test-nonce-123',
        action: 'racketmanager_get_player_details',
      });
    });

    it('defaults club to null when not supplied', () => {
      getPlayerDetails('Smith');
      expect(ajax.mock.calls[0][0].data.club).toBeNull();
    });

    it('parses JSON string payloads into an array', () => {
      ajax.mockImplementation((opts) => opts.success({ data: '[{"value":"Jo Bloggs","playerId":7}]' }));
      expect(getPlayerDetails('Jo')).toEqual([{ value: 'Jo Bloggs', playerId: 7 }]);
    });

    it('returns object payloads unchanged', () => {
      ajax.mockImplementation((opts) => opts.success({ data: [{ value: 'Sam Smith', playerId: 8 }] }));
      expect(getPlayerDetails('Sam')).toEqual([{ value: 'Sam Smith', playerId: 8 }]);
    });

    it('returns the raw value when a JSON string cannot be parsed', () => {
      ajax.mockImplementation((opts) => opts.success({ data: 'not json' }));
      expect(getPlayerDetails('Bad')).toBe('not json');
    });

    it('returns null when jQuery is undefined', () => {
      delete window.jQuery;
      expect(getPlayerDetails('Smith')).toBeNull();
    });

    it('returns null when jQuery.ajax is not a function', () => {
      window.jQuery = { ajax: 'not-a-function' };
      expect(getPlayerDetails('Smith')).toBeNull();
    });

    it('handles success callback when data or data.data is undefined', () => {
      ajax.mockImplementation((opts) => opts.success(undefined));
      expect(getPlayerDetails('Smith')).toBeUndefined();

      ajax.mockImplementation((opts) => opts.success({}));
      expect(getPlayerDetails('Smith')).toBeUndefined();
    });

    it('alerts and returns null on request error', () => {
      ajax.mockImplementation((opts) => opts.error());
      expect(getPlayerDetails('Smith')).toBeNull();
      expect(window.alert).toHaveBeenCalledWith('Ajax error on getting player');
    });

    it('does not throw when alert is not a function on request error', () => {
      delete window.alert;
      ajax.mockImplementation((opts) => opts.error());
      expect(getPlayerDetails('Smith')).toBeNull();
    });
  });

  describe('initTeamAutocomplete', () => {
    it('does not initialise anything when the team form inputs are absent', () => {
      initTeamAutocomplete();
      expect(safeAutocomplete).not.toHaveBeenCalled();
    });

    it('initialises autocomplete on all four player and captain inputs when present', () => {
      container.innerHTML = `
        <input id="teamPlayer1"><input id="teamPlayer2"><input id="captain"><input id="match_secretary_name">
      `;
      initTeamAutocomplete();

      const selectors = safeAutocomplete.mock.calls.map(([sel]) => sel);
      expect(selectors).toEqual(['#teamPlayer1', '#teamPlayer2', '#captain', '#match_secretary_name']);
    });

    it('player 1 source sends a lookup without club restriction', () => {
      container.innerHTML = `<input id="teamPlayer1">`;
      initTeamAutocomplete();

      ajax.mockImplementation((opts) => opts.success({ data: [] }));
      const respond = jest.fn();
      optionsFor('#teamPlayer1').source({ term: 'Jo' }, respond);

      expect(ajax.mock.calls[0][0].data).toEqual(expect.objectContaining({ name: 'Jo', club: null }));
      expect(respond).toHaveBeenCalledWith([]);
    });

    it('populates player 1 hidden fields on select', () => {
      container.innerHTML = `
        <input id="teamPlayer1"><input id="teamPlayerId1"><input id="clubId">
        <input id="captain"><input id="captainId"><input id="contactno"><input id="contactemail">
      `;
      initTeamAutocomplete();

      optionsFor('#teamPlayer1').select({}, {
        item: { playerId: 7, club_id: 3, value: 'Jo Bloggs', contactno: '0123456', user_email: 'jo@example.com' },
      });

      expect(container.querySelector('#teamPlayerId1').value).toBe('7');
      expect(container.querySelector('#clubId').value).toBe('3');
      expect(container.querySelector('#captain').value).toBe('Jo Bloggs');
      expect(container.querySelector('#captainId').value).toBe('7');
      expect(container.querySelector('#contactno').value).toBe('0123456');
      expect(container.querySelector('#contactemail').value).toBe('jo@example.com');
    });

    it('builds the team name from both players on change', () => {
      container.innerHTML = `
        <input id="teamPlayer1" value="Jo Bloggs"><input id="teamPlayer2" value="Sam Smith">
        <input id="team"><input id="teamPlayerId1"><input id="captain"><input id="captainId">
        <input id="clubId"><input id="contactno"><input id="contactemail">
      `;
      initTeamAutocomplete();

      const input = container.querySelector('#teamPlayer1');
      optionsFor('#teamPlayer1').change({ target: input }, {
        item: { playerId: 7, club_id: 3, value: 'Jo Bloggs', contactno: '', user_email: '' },
      });

      expect(container.querySelector('#team').value).toBe('Jo Bloggs / Sam Smith');
      expect(container.querySelector('#teamPlayerId1').value).toBe('7');
    });

    it('uses only player 1 name for the team when player 2 is empty', () => {
      container.innerHTML = `
        <input id="teamPlayer1" value="Jo Bloggs"><input id="teamPlayer2" value="">
        <input id="team"><input id="teamPlayerId1"><input id="captain"><input id="captainId">
        <input id="clubId"><input id="contactno"><input id="contactemail">
      `;
      initTeamAutocomplete();

      optionsFor('#teamPlayer1').change({ target: container.querySelector('#teamPlayer1') }, {
        item: { playerId: 7, club_id: 3, value: 'Jo Bloggs', contactno: '', user_email: '' },
      });

      expect(container.querySelector('#team').value).toBe('Jo Bloggs');
    });

    it('clears dependent fields when the typed name no longer matches a player', () => {
      container.innerHTML = `
        <input id="teamPlayer1" value="Unknown"><input id="teamPlayerId1" value="7"><input id="clubId" value="3">
        <input id="captain" value="Jo"><input id="captainId" value="7"><input id="contactno" value="0123">
        <input id="contactemail" value="jo@example.com"><input id="team" value="Jo">
      `;
      initTeamAutocomplete();

      const input = container.querySelector('#teamPlayer1');
      optionsFor('#teamPlayer1').change({ target: input }, { item: null });

      expect(input.value).toBe('');
      for (const id of ['teamPlayerId1', 'clubId', 'captain', 'captainId', 'contactno', 'contactemail', 'team']) {
        expect(container.querySelector(`#${id}`).value).toBe('');
      }
    });

    it('captain lookup searches within the selected club from #clubId', () => {
      container.innerHTML = `<input id="captain"><input id="clubId" value="3">`;
      initTeamAutocomplete();

      ajax.mockImplementation((opts) => opts.success({ data: [] }));
      const respond = jest.fn();
      optionsFor('#captain').source({ term: 'Jo' }, respond);

      expect(ajax.mock.calls[0][0].data).toEqual(expect.objectContaining({
        name: 'Jo',
        club: '3',
        action: 'racketmanager_get_player_details',
      }));
      expect(respond).toHaveBeenCalledWith([]);
    });

    it('captain change with a matched player sets captain fields', () => {
      container.innerHTML = `
        <input id="captain"><input id="captainId"><input id="contactno"><input id="contactemail">
      `;
      initTeamAutocomplete();

      optionsFor('#captain').change({ target: container.querySelector('#captain') }, {
        item: { playerId: 9, value: 'Ann Lee', contactno: '0777', user_email: 'ann@example.com' },
      });

      expect(container.querySelector('#captain').value).toBe('Ann Lee');
      expect(container.querySelector('#captainId').value).toBe('9');
      expect(container.querySelector('#contactno').value).toBe('0777');
      expect(container.querySelector('#contactemail').value).toBe('ann@example.com');
    });

    it('match secretary lookup uses the club_id field and populates on select', () => {
      container.innerHTML = `
        <input id="match_secretary_name"><input id="club_id" value="9">
        <input id="match_secretary"><input id="match_secretary_contact_no"><input id="match_secretary_email">
      `;
      initTeamAutocomplete();

      ajax.mockImplementation((opts) => opts.success({ data: [{ value: 'Ann Lee', playerId: 5 }] }));
      const respond = jest.fn();
      optionsFor('#match_secretary_name').source({ term: 'Ann' }, respond);

      expect(ajax.mock.calls[0][0].data.club).toBe('9');
      expect(respond).toHaveBeenCalledWith([{ value: 'Ann Lee', playerId: 5 }]);

      optionsFor('#match_secretary_name').select({}, {
        item: { value: 'Ann Lee', playerId: 5, contactno: '0777', user_email: 'ann@example.com' },
      });
      expect(container.querySelector('#match_secretary_name').value).toBe('Ann Lee');
      expect(container.querySelector('#match_secretary').value).toBe('5');
      expect(container.querySelector('#match_secretary_contact_no').value).toBe('0777');
      expect(container.querySelector('#match_secretary_email').value).toBe('ann@example.com');
    });

    it('teamPlayer2 source queries player details with null club', () => {
      container.innerHTML = `<input id="teamPlayer2">`;
      initTeamAutocomplete();

      ajax.mockImplementation((opts) => opts.success({ data: [] }));
      const respond = jest.fn();
      optionsFor('#teamPlayer2').source({ term: 'Bob' }, respond);

      expect(ajax.mock.calls[0][0].data).toEqual(expect.objectContaining({ name: 'Bob', club: null }));
      expect(respond).toHaveBeenCalledWith([]);
    });

    it('teamPlayer2 select populates teamPlayerId2', () => {
      container.innerHTML = `<input id="teamPlayer2"><input id="teamPlayerId2">`;
      initTeamAutocomplete();

      optionsFor('#teamPlayer2').select({}, { item: { id: 14 } });
      expect(container.querySelector('#teamPlayerId2').value).toBe('14');
    });

    it('teamPlayer2 change updates team and teamPlayerId2 when item is present', () => {
      container.innerHTML = `
        <input id="teamPlayer1" value="Alice"><input id="teamPlayer2" value="Bob">
        <input id="teamPlayerId2"><input id="team">
      `;
      initTeamAutocomplete();

      optionsFor('#teamPlayer2').change({}, { item: { playerId: 22 } });
      expect(container.querySelector('#teamPlayerId2').value).toBe('22');
      expect(container.querySelector('#team').value).toBe('Alice / Bob');
    });

    it('teamPlayer2 change clears team and teamPlayerId2 when item is null', () => {
      container.innerHTML = `
        <input id="teamPlayer2" value="Bob"><input id="teamPlayerId2" value="22"><input id="team" value="Alice / Bob">
      `;
      initTeamAutocomplete();

      const input = container.querySelector('#teamPlayer2');
      optionsFor('#teamPlayer2').change({ target: input }, { item: null });
      expect(input.value).toBe('');
      expect(container.querySelector('#teamPlayerId2').value).toBe('');
      expect(container.querySelector('#team').value).toBe('');
    });

    it('captain select populates captain fields', () => {
      container.innerHTML = `
        <input id="captain"><input id="captainId"><input id="contactno"><input id="contactemail">
      `;
      initTeamAutocomplete();

      optionsFor('#captain').select({}, {
        item: { value: 'Cap Bob', playerId: 18, contactno: '0999', user_email: 'cap@example.com' },
      });
      expect(container.querySelector('#captain').value).toBe('Cap Bob');
      expect(container.querySelector('#captainId').value).toBe('18');
      expect(container.querySelector('#contactno').value).toBe('0999');
      expect(container.querySelector('#contactemail').value).toBe('cap@example.com');
    });

    it('captain source queries with club fallback when clubId is missing or empty', () => {
      container.innerHTML = `<input id="captain">`;
      initTeamAutocomplete();

      ajax.mockImplementation((opts) => opts.success({ data: [] }));
      const respond = jest.fn();
      optionsFor('#captain').source({ term: 'Cap' }, respond);

      expect(ajax.mock.calls[0][0].data.club).toBeNull();
      expect(respond).toHaveBeenCalledWith([]);
    });

    it('match_secretary_name source queries with club fallback when club_id is missing or empty', () => {
      container.innerHTML = `<input id="match_secretary_name">`;
      initTeamAutocomplete();

      ajax.mockImplementation((opts) => opts.success({ data: [] }));
      const respond = jest.fn();
      optionsFor('#match_secretary_name').source({ term: 'Sec' }, respond);

      expect(ajax.mock.calls[0][0].data.club).toBeNull();
      expect(respond).toHaveBeenCalledWith([]);
    });

    it('handles change events when event.target is undefined', () => {
      container.innerHTML = `
        <input id="teamPlayer1" value="Alice"><input id="teamPlayerId1" value="1">
        <input id="captain" value="Alice"><input id="captainId" value="1"><input id="clubId" value="2">
        <input id="contactno" value="123"><input id="contactemail" value="a@b.c"><input id="team" value="Alice">
      `;
      initTeamAutocomplete();

      expect(() => optionsFor('#teamPlayer1').change({}, { item: null })).not.toThrow();
      expect(container.querySelector('#teamPlayer1').value).toBe('Alice');
      expect(container.querySelector('#teamPlayerId1').value).toBe('');
    });

    it('captain change clears fields when item is null', () => {
      container.innerHTML = `
        <input id="captain" value="Cap"><input id="captainId" value="18"><input id="contactno" value="0999"><input id="contactemail" value="cap@example.com">
      `;
      initTeamAutocomplete();

      const input = container.querySelector('#captain');
      optionsFor('#captain').change({ target: input }, { item: null });
      expect(input.value).toBe('');
      expect(container.querySelector('#captain').value).toBe('');
      expect(container.querySelector('#captainId').value).toBe('');
      expect(container.querySelector('#contactno').value).toBe('');
      expect(container.querySelector('#contactemail').value).toBe('');
    });

    it('match_secretary_name change populates fields on match and clears on null item', () => {
      container.innerHTML = `
        <input id="match_secretary_name" value="Sec"><input id="match_secretary"><input id="match_secretary_contact_no"><input id="match_secretary_email">
      `;
      initTeamAutocomplete();

      const input = container.querySelector('#match_secretary_name');
      optionsFor('#match_secretary_name').change({ target: input }, {
        item: { value: 'Sec Mary', playerId: 31, contactno: '0444', user_email: 'sec@example.com' },
      });
      expect(container.querySelector('#match_secretary_name').value).toBe('Sec Mary');
      expect(container.querySelector('#match_secretary').value).toBe('31');
      expect(container.querySelector('#match_secretary_contact_no').value).toBe('0444');
      expect(container.querySelector('#match_secretary_email').value).toBe('sec@example.com');

      optionsFor('#match_secretary_name').change({ target: input }, { item: null });
      expect(input.value).toBe('');
      expect(container.querySelector('#match_secretary').value).toBe('');
      expect(container.querySelector('#match_secretary_contact_no').value).toBe('');
      expect(container.querySelector('#match_secretary_email').value).toBe('');
    });

    it('handles null and undefined values and missing DOM elements in setVal gracefully', () => {
      container.innerHTML = `
        <input id="teamPlayer1"><input id="captain"><input id="contactno" value="123"><input id="contactemail" value="a@b.c">
      `;
      initTeamAutocomplete();

      optionsFor('#teamPlayer1').select({}, {
        item: { playerId: 1, club_id: null, value: 'Jo', contactno: undefined, user_email: null },
      });
      expect(container.querySelector('#captain').value).toBe('Jo');
      expect(container.querySelector('#contactno').value).toBe('');
      expect(container.querySelector('#contactemail').value).toBe('');

      optionsFor('#teamPlayer1').change({ target: container.querySelector('#teamPlayer1') }, {
        item: { playerId: 1, club_id: null, value: 'Jo', contactno: null, user_email: undefined },
      });
      expect(container.querySelector('#captain').value).toBe('Jo');
      expect(container.querySelector('#contactno').value).toBe('');
      expect(container.querySelector('#contactemail').value).toBe('');
    });

    it('builds team name when teamPlayer1 or teamPlayer2 element is absent from DOM in teamPlayer1 change', () => {
      container.innerHTML = `<input id="teamPlayer1"><input id="team">`;
      initTeamAutocomplete();

      const input = container.querySelector('#teamPlayer1');
      input.value = 'Alice';
      optionsFor('#teamPlayer1').change({ target: input }, {
        item: { playerId: 1, value: 'Alice' },
      });
      expect(container.querySelector('#team').value).toBe('Alice');

      input.remove();
      optionsFor('#teamPlayer1').change({}, {
        item: { playerId: 1, value: 'Alice' },
      });
      expect(container.querySelector('#team').value).toBe('');
    });

    it('handles teamPlayer2 change without event.target when item is null', () => {
      container.innerHTML = `<input id="teamPlayer2" value="Bob"><input id="teamPlayerId2" value="22"><input id="team" value="Alice / Bob">`;
      initTeamAutocomplete();

      expect(() => optionsFor('#teamPlayer2').change({}, { item: null })).not.toThrow();
      expect(container.querySelector('#teamPlayerId2').value).toBe('');
      expect(container.querySelector('#team').value).toBe('');
    });

    it('handles teamPlayer2 change when teamPlayer1 or teamPlayer2 element is absent', () => {
      container.innerHTML = `<input id="teamPlayer2" value="Bob"><input id="teamPlayerId2"><input id="team">`;
      initTeamAutocomplete();

      optionsFor('#teamPlayer2').change({}, { item: { playerId: 22 } });
      expect(container.querySelector('#team').value).toBe(' / Bob');

      container.querySelector('#teamPlayer2').remove();
      optionsFor('#teamPlayer2').change({}, { item: { playerId: 22 } });
      expect(container.querySelector('#team').value).toBe(' / ');
    });

    it('captain source queries with null club when clubId input has empty value', () => {
      container.innerHTML = `<input id="captain"><input id="clubId" value="">`;
      initTeamAutocomplete();

      ajax.mockImplementation((opts) => opts.success({ data: [] }));
      const respond = jest.fn();
      optionsFor('#captain').source({ term: 'Cap' }, respond);

      expect(ajax.mock.calls[0][0].data.club).toBeNull();
      expect(respond).toHaveBeenCalledWith([]);
    });

    it('handles captain change without event.target when item is null', () => {
      container.innerHTML = `<input id="captain" value="Cap"><input id="captainId" value="18">`;
      initTeamAutocomplete();

      expect(() => optionsFor('#captain').change({}, { item: null })).not.toThrow();
      expect(container.querySelector('#captain').value).toBe('');
      expect(container.querySelector('#captainId').value).toBe('');
    });

    it('match_secretary_name source queries with null club when club_id input has empty value', () => {
      container.innerHTML = `<input id="match_secretary_name"><input id="club_id" value="">`;
      initTeamAutocomplete();

      ajax.mockImplementation((opts) => opts.success({ data: [] }));
      const respond = jest.fn();
      optionsFor('#match_secretary_name').source({ term: 'Sec' }, respond);

      expect(ajax.mock.calls[0][0].data.club).toBeNull();
      expect(respond).toHaveBeenCalledWith([]);
    });

    it('handles match_secretary_name change without event.target when item is null', () => {
      container.innerHTML = `<input id="match_secretary_name" value="Sec"><input id="match_secretary" value="31">`;
      initTeamAutocomplete();

      expect(() => optionsFor('#match_secretary_name').change({}, { item: null })).not.toThrow();
      expect(container.querySelector('#match_secretary_name').value).toBe('');
      expect(container.querySelector('#match_secretary').value).toBe('');
    });
  });
});
