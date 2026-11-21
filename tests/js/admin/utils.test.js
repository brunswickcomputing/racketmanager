import fs from 'fs';
import path from 'path';
import vm from 'vm';
import { registerAdminActions, setIframeHeight } from '../../../src/js/admin/admin-actions.js';
import '../../../src/js/admin/utils.js';

describe('Admin utils: Racketmanager.checkAll and setShortcode', () => {
  let form;

  beforeEach(() => {
    form = document.createElement('form');
    document.body.appendChild(form);
  });

  afterEach(() => {
    document.body.removeChild(form);
  });

  it('exposes checkAll and setShortcode on window.Racketmanager', () => {
    expect(typeof window.Racketmanager.checkAll).toBe('function');
    expect(typeof window.Racketmanager.setShortcode).toBe('function');
  });

  it('checkAll ignores non-form elements or null', () => {
    expect(() => window.Racketmanager.checkAll(null)).not.toThrow();
    expect(() => window.Racketmanager.checkAll(document.createElement('div'))).not.toThrow();
  });

  it('checkAll synchronizes row checkboxes with the thead master checkbox', () => {
    form.innerHTML = `
      <table>
        <thead>
          <tr><th><input type="checkbox" id="master"></th></tr>
        </thead>
        <tbody>
          <tr><td><input type="checkbox" class="row-cb"></td></tr>
          <tr><td><input type="checkbox" class="row-cb"></td></tr>
          <tr><td><input type="checkbox" class="row-cb" disabled></td></tr>
        </tbody>
      </table>
    `;
    const master = form.querySelector('#master');
    const [row1, row2, rowDisabled] = form.querySelectorAll('tbody input[type="checkbox"]');

    master.checked = true;
    window.Racketmanager.checkAll(form);

    expect(row1.checked).toBe(true);
    expect(row2.checked).toBe(true);
    expect(rowDisabled.checked).toBe(false);

    master.checked = false;
    window.Racketmanager.checkAll(form);

    expect(row1.checked).toBe(false);
    expect(row2.checked).toBe(false);
  });

  it('checkAll finds master checkbox by fallback ID when not in thead', () => {
    form.innerHTML = `
      <div><input type="checkbox" id="checkAllSeasons"></div>
      <div><input type="checkbox" class="row-cb"></div>
    `;
    const master = form.querySelector('#checkAllSeasons');
    const row = form.querySelector('.row-cb');

    master.checked = true;
    window.Racketmanager.checkAll(form);
    expect(row.checked).toBe(true);
  });

  it('checkAll infers desired state when no master checkbox exists', () => {
    form.innerHTML = `
      <table>
        <tbody>
          <tr><td><input type="checkbox" class="row-cb"></td></tr>
          <tr><td><input type="checkbox" class="row-cb" checked></td></tr>
        </tbody>
      </table>
    `;
    const [row1, row2] = form.querySelectorAll('.row-cb');

    // One is unchecked -> desiredState is true (check all)
    window.Racketmanager.checkAll(form);
    expect(row1.checked).toBe(true);
    expect(row2.checked).toBe(true);

    // All are checked -> desiredState is false (uncheck all)
    window.Racketmanager.checkAll(form);
    expect(row1.checked).toBe(false);
    expect(row2.checked).toBe(false);
  });

  it('setShortcode copies club value to shortcode if shortcode input is empty', () => {
    const club = document.createElement('input');
    club.id = 'club';
    club.value = 'Lawn Tennis Club';

    const shortcode = document.createElement('input');
    shortcode.id = 'shortcode';
    shortcode.value = '';

    document.body.appendChild(club);
    document.body.appendChild(shortcode);

    window.Racketmanager.setShortcode();
    expect(shortcode.value).toBe('Lawn Tennis Club');

    // Does not overwrite when already filled
    club.value = 'Different Club';
    window.Racketmanager.setShortcode();
    expect(shortcode.value).toBe('Lawn Tennis Club');

    document.body.removeChild(club);
    document.body.removeChild(shortcode);
  });
});

describe('Admin utils: registerAdminActions', () => {
  let container;
  let originalRacketmanager;

  beforeAll(() => {
    // utils.js already called registerAdminActions(document) on import.
    // Ensure document has listeners registered once.
    registerAdminActions(document);
  });

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);

    // Save
    originalRacketmanager = window.Racketmanager ? { ...window.Racketmanager } : undefined;
    
    // Set up mocks
    window.Racketmanager = window.Racketmanager || {};
    window.Racketmanager.checkAll = jest.fn();
    window.Racketmanager.notifyTeams = jest.fn();
    window.Racketmanager.setNumMatchDays = jest.fn();
    window.Racketmanager.setEndDate = jest.fn();
    window.Racketmanager.setEventName = jest.fn();
    window.Racketmanager.setShortcode = jest.fn();
    window.Racketmanager.setTournamentOpenDate = jest.fn();
    window.Racketmanager.getImportOption = jest.fn();
    window.Racketmanager.setMatchDate = jest.fn();
    window.Racketmanager.setMatchDayPopUp = jest.fn();
    window.Racketmanager.insertHomeStadium = jest.fn();
    window.Racketmanager.saveAddPoints = jest.fn();
  });

  afterEach(() => {
    document.body.removeChild(container);
    // Restore
    if (originalRacketmanager) {
        window.Racketmanager = originalRacketmanager;
    } else {
        delete window.Racketmanager;
    }
  });

  describe('check-all action', () => {
    it('dispatches checkAll with the target form when data-target is provided', () => {
      const form = document.createElement('form');
      form.id = 'my-form';
      document.body.appendChild(form);

      const btn = document.createElement('input');
      btn.type = 'checkbox';
      btn.setAttribute('data-action', 'check-all');
      btn.setAttribute('data-target', 'my-form');
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.checkAll).toHaveBeenCalledWith(form);
      document.body.removeChild(form);
    });

    it('dispatches checkAll resolving form via el.form when closest form is absent', () => {
      const form = document.createElement('form');
      document.body.appendChild(form);

      const btn = document.createElement('input');
      btn.type = 'checkbox';
      btn.setAttribute('data-action', 'check-all');
      Object.defineProperty(btn, 'form', { value: form });
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.checkAll).toHaveBeenCalledWith(form);
      document.body.removeChild(form);
    });

    it('dispatches checkAll with the closest enclosing form when data-target is absent', () => {
      const form = document.createElement('form');
      const btn = document.createElement('input');
      btn.type = 'checkbox';
      btn.setAttribute('data-action', 'check-all');
      form.appendChild(btn);
      container.appendChild(form);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.checkAll).toHaveBeenCalledWith(form);
    });

    it('handles clicks on elements nested inside data-action container', () => {
      const form = document.createElement('form');
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('data-action', 'check-all');
      const span = document.createElement('span');
      span.textContent = 'Select all';
      btn.appendChild(span);
      form.appendChild(btn);
      container.appendChild(form);

      span.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.checkAll).toHaveBeenCalledWith(form);
    });

    it('falls back to el.form when el.closest is not a function', () => {
      const form = document.createElement('form');
      const fakeEl = {
        getAttribute: (attr) => (attr === 'data-action' ? 'check-all' : null),
        dataset: { action: 'check-all' },
        form: form,
        closest: undefined,
      };
      const fakeTarget = {
        closest: () => fakeEl,
      };
      const ev = new MouseEvent('click', { bubbles: true });
      Object.defineProperty(ev, 'target', { value: fakeTarget });
      container.dispatchEvent(ev);

      expect(window.Racketmanager.checkAll).toHaveBeenCalledWith(form);
    });

    it('does nothing when element has no data-action, empty action, or unknown action', () => {
      const el = document.createElement('button');
      container.appendChild(el);
      el.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      el.setAttribute('data-action', '');
      el.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      el.setAttribute('data-action', 'unknown-action');
      el.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.checkAll).not.toHaveBeenCalled();
    });

    it('does not throw when window.Racketmanager or target function is missing', () => {
      const saved = window.Racketmanager.checkAll;
      delete window.Racketmanager.checkAll;

      const form = document.createElement('form');
      const btn = document.createElement('input');
      btn.type = 'checkbox';
      btn.setAttribute('data-action', 'check-all');
      form.appendChild(btn);
      container.appendChild(form);

      expect(() => btn.dispatchEvent(new MouseEvent('click', { bubbles: true }))).not.toThrow();
      window.Racketmanager.checkAll = saved;
    });
  });

  describe('notify-teams action', () => {
    it('invokes notifyTeams with data-match-id', () => {
      const btn = document.createElement('button');
      btn.setAttribute('data-action', 'notify-teams');
      btn.setAttribute('data-match-id', '42');
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.notifyTeams).toHaveBeenCalledWith('42');
    });
  });

  describe('set-num-match-days action', () => {
    it('invokes setNumMatchDays on click of radio', () => {
      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.setAttribute('data-action', 'set-num-match-days');
      container.appendChild(radio);

      radio.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.setNumMatchDays).toHaveBeenCalled();
    });

    it('does not invoke setNumMatchDays on click of non-radio or when Racketmanager is missing', () => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('data-action', 'set-num-match-days');
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(window.Racketmanager.setNumMatchDays).not.toHaveBeenCalled();

      // When Racketmanager function is missing
      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.setAttribute('data-action', 'set-num-match-days');
      container.appendChild(radio);
      delete window.Racketmanager.setNumMatchDays;
      expect(() => radio.dispatchEvent(new MouseEvent('click', { bubbles: true }))).not.toThrow();
    });

    it('invokes setNumMatchDays on change of number input', () => {
      const input = document.createElement('input');
      input.type = 'number';
      input.setAttribute('data-action', 'set-num-match-days');
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setNumMatchDays).toHaveBeenCalled();
    });

    it('does not invoke setNumMatchDays on change of radio input', () => {
      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.setAttribute('data-action', 'set-num-match-days');
      container.appendChild(radio);

      radio.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setNumMatchDays).not.toHaveBeenCalled();
    });

    it('invokes setNumMatchDays exactly once when a radio is clicked', () => {
      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'homeAway';
      radio.setAttribute('data-action', 'set-num-match-days');
      container.appendChild(radio);

      radio.click();

      expect(window.Racketmanager.setNumMatchDays).toHaveBeenCalledTimes(1);
    });
  });

  describe('set-end-date action', () => {
    it('invokes setEndDate on change', () => {
      const input = document.createElement('input');
      input.type = 'number';
      input.setAttribute('data-action', 'set-end-date');
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setEndDate).toHaveBeenCalled();
    });
  });

  describe('set-event-name action', () => {
    it('invokes setEventName on change', () => {
      const select = document.createElement('select');
      select.setAttribute('data-action', 'set-event-name');
      container.appendChild(select);

      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setEventName).toHaveBeenCalled();
    });
  });

  describe('set-shortcode action', () => {
    it('invokes setShortcode on change', () => {
      const input = document.createElement('input');
      input.setAttribute('data-action', 'set-shortcode');
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setShortcode).toHaveBeenCalled();
    });
  });

  describe('set-tournament-open-date action', () => {
    it('invokes setTournamentOpenDate on change', () => {
      const input = document.createElement('input');
      input.setAttribute('data-action', 'set-tournament-open-date');
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setTournamentOpenDate).toHaveBeenCalled();
    });
  });

  describe('import actions', () => {
    it('invokes getImportOption on change with select value', () => {
      const select = document.createElement('select');
      select.setAttribute('data-action', 'get-import-option');
      const opt = document.createElement('option');
      opt.value = 'fixtures';
      select.appendChild(opt);
      select.value = 'fixtures';
      container.appendChild(select);

      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.getImportOption).toHaveBeenCalledWith('fixtures');
    });
  });

  describe('match fixtures actions', () => {
    it('invokes setMatchDate with correct parsed arguments', () => {
      const input = document.createElement('input');
      input.type = 'date';
      input.value = '2026-10-30';
      input.setAttribute('data-action', 'set-match-date');
      input.setAttribute('data-index', '0');
      input.setAttribute('data-max-fixtures', '5');
      input.setAttribute('data-mode', 'add');
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setMatchDate).toHaveBeenCalledWith('2026-10-30', 0, 5, 'add');
    });

    it('invokes setMatchDayPopUp with correct parsed arguments', () => {
      const select = document.createElement('select');
      select.setAttribute('data-action', 'set-match-day-pop-up');
      select.setAttribute('data-index', '0');
      select.setAttribute('data-max-fixtures', '4');
      select.setAttribute('data-mode', 'add');
      const opt = document.createElement('option');
      opt.value = '2';
      select.appendChild(opt);
      select.value = '2';
      container.appendChild(select);

      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setMatchDayPopUp).toHaveBeenCalledWith('2', 0, 4, 'add');
    });

    it('invokes insertHomeStadium resolving home_team_i input value when available', () => {
      const homeTeamInput = document.createElement('input');
      homeTeamInput.id = 'home_team_1';
      homeTeamInput.value = '77';
      document.body.appendChild(homeTeamInput);

      const select = document.createElement('select');
      select.setAttribute('data-action', 'insert-home-stadium');
      select.setAttribute('data-index', '1');
      container.appendChild(select);

      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.insertHomeStadium).toHaveBeenCalledWith('77', 1);
      document.body.removeChild(homeTeamInput);
    });
  });

  describe('save-add-points action', () => {
    it('invokes saveAddPoints on focusout with tableId', () => {
      const input = document.createElement('input');
      input.value = '3';
      input.setAttribute('data-action', 'save-add-points');
      input.setAttribute('data-table-id', '99');
      container.appendChild(input);

      input.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));

      expect(window.Racketmanager.saveAddPoints).toHaveBeenCalledWith('3', '99');
    });

    it('invokes saveAddPoints exactly once on real focus change', () => {
      const inputA = document.createElement('input');
      inputA.value = '3';
      inputA.setAttribute('data-action', 'save-add-points');
      inputA.setAttribute('data-table-id', '7');
      container.appendChild(inputA);

      const inputB = document.createElement('input');
      container.appendChild(inputB);

      inputA.focus();
      inputB.focus();

      expect(window.Racketmanager.saveAddPoints).toHaveBeenCalledTimes(1);
      expect(window.Racketmanager.saveAddPoints).toHaveBeenCalledWith('3', '7');
    });
  });

  describe('iframe height action', () => {
    it('resizes iframe directly when load event fires for data-action="set-iframe-height"', () => {
      const iframe = document.createElement('iframe');
      iframe.id = 'my-iframe';
      iframe.setAttribute('data-action', 'set-iframe-height');
      // jsdom automatically dispatches 'load' when iframe is appended to document
      container.appendChild(iframe);

      expect(iframe.style.visibility).toBe('visible');
      expect(iframe.style.height).toMatch(/^\d+px$/);
    });

    it('handles load event when target has data-action attribute directly without closest match', () => {
      const iframe = document.createElement('iframe');
      iframe.setAttribute('data-action', 'set-iframe-height');
      container.appendChild(iframe);

      const fakeTarget = {
        closest: () => null,
        getAttribute: (attr) => (attr === 'data-action' ? 'set-iframe-height' : null),
        contentDocument: { body: { scrollHeight: 120, offsetHeight: 120 } },
        style: {},
      };
      const loadEvent = new Event('load', { bubbles: true });
      Object.defineProperty(loadEvent, 'target', { value: fakeTarget });

      document.dispatchEvent(loadEvent);

      expect(fakeTarget.style.visibility).toBe('visible');
      expect(fakeTarget.style.height).toBe('124px');
    });

    it('directly resizes iframe element via exported setIframeHeight helper', () => {
      const iframe = document.createElement('iframe');
      iframe.id = 'direct-iframe';
      container.appendChild(iframe);

      setIframeHeight(iframe);

      expect(iframe.style.visibility).toBe('visible');
      expect(iframe.style.height).toMatch(/^\d+px$/);
    });
  });

  describe('double-registration guard', () => {
    it('does not attach duplicate listeners or fire duplicate actions when registered twice', () => {
      // Re-registering on document should be a no-op due to root.__rmAdminActionsRegistered
      registerAdminActions(document);

      const btn = document.createElement('button');
      btn.setAttribute('data-action', 'notify-teams');
      btn.setAttribute('data-match-id', '100');
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.notifyTeams).toHaveBeenCalledTimes(1);
    });

    it('returns early when root is null or lacks addEventListener', () => {
      expect(() => registerAdminActions(null)).not.toThrow();
      expect(() => registerAdminActions({})).not.toThrow();
    });

    it('handles change and focusout events on elements without data-action or with missing Racketmanager functions', () => {
      const el = document.createElement('input');
      container.appendChild(el);

      el.dispatchEvent(new Event('change', { bubbles: true }));
      el.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));

      el.setAttribute('data-action', '');
      el.dispatchEvent(new Event('change', { bubbles: true }));
      el.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));

      el.setAttribute('data-action', 'unknown-change-action');
      el.dispatchEvent(new Event('change', { bubbles: true }));

      // Missing functions
      window.Racketmanager.notifyTeams = null;
      window.Racketmanager.setEndDate = null;
      window.Racketmanager.setEventName = null;
      window.Racketmanager.setShortcode = null;
      window.Racketmanager.setTournamentOpenDate = null;
      window.Racketmanager.getImportOption = null;
      window.Racketmanager.setMatchDate = null;
      window.Racketmanager.setMatchDayPopUp = null;
      window.Racketmanager.insertHomeStadium = null;
      window.Racketmanager.saveAddPoints = null;

      const actions = [
        'notify-teams',
        'set-end-date',
        'set-event-name',
        'set-shortcode',
        'set-tournament-open-date',
        'get-import-option',
        'set-match-date',
        'set-match-day-pop-up',
        'insert-home-stadium',
        'save-add-points',
      ];
      for (const act of actions) {
        el.setAttribute('data-action', act);
        expect(() => {
          el.dispatchEvent(new MouseEvent('click', { bubbles: true }));
          el.dispatchEvent(new Event('change', { bubbles: true }));
          el.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));
        }).not.toThrow();
        expect(window.Racketmanager.checkAll).not.toHaveBeenCalled();
        expect(window.Racketmanager.setNumMatchDays).not.toHaveBeenCalled();
      }
    });

    it('setIframeHeight returns early for invalid id or missing content document', () => {
      expect(() => setIframeHeight('non-existent-iframe-id')).not.toThrow();

      const fakeIframe = document.createElement('div');
      expect(() => setIframeHeight(fakeIframe)).not.toThrow();
    });

    it('handles click, change, focusout, and load when event target lacks closest', () => {
      const nonElementTarget = { closest: undefined };
      expect(() => {
        const clickEv = new MouseEvent('click', { bubbles: true });
        Object.defineProperty(clickEv, 'target', { value: nonElementTarget });
        document.dispatchEvent(clickEv);

        const changeEv = new Event('change', { bubbles: true });
        Object.defineProperty(changeEv, 'target', { value: nonElementTarget });
        document.dispatchEvent(changeEv);

        const focusEv = new FocusEvent('focusout', { bubbles: true });
        Object.defineProperty(focusEv, 'target', { value: nonElementTarget });
        document.dispatchEvent(focusEv);

        const loadEv = new Event('load', { bubbles: true });
        Object.defineProperty(loadEv, 'target', { value: nonElementTarget });
        document.dispatchEvent(loadEv);
      }).not.toThrow();
    });

    it('uses dataset fallback for action and target in check-all', () => {
      const form = document.createElement('form');
      form.id = 'form-via-dataset';
      document.body.appendChild(form);

      const btn = document.createElement('button');
      btn.setAttribute('data-action', '');
      btn.dataset.action = 'check-all';
      btn.dataset.target = 'form-via-dataset';
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(window.Racketmanager.checkAll).toHaveBeenCalledWith(form);
      document.body.removeChild(form);
    });

    it('handles check-all when no form can be found via closest or el.form', () => {
      const btn = document.createElement('button');
      btn.setAttribute('data-action', 'check-all');
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(window.Racketmanager.checkAll).not.toHaveBeenCalled();
    });

    it('uses dataset fallback for matchId in notify-teams', () => {
      const btn = document.createElement('button');
      btn.setAttribute('data-action', 'notify-teams');
      btn.dataset.matchId = '555';
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(window.Racketmanager.notifyTeams).toHaveBeenCalledWith('555');
    });

    it('uses dataset fallback for action in handleChange', () => {
      const input = document.createElement('input');
      input.setAttribute('data-action', '');
      input.dataset.action = 'set-end-date';
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.setEndDate).toHaveBeenCalled();
    });

    it('uses dataset fallback and defaults for set-match-date', () => {
      const input = document.createElement('input');
      input.value = '2025-05-10';
      input.setAttribute('data-action', 'set-match-date');
      input.dataset.index = '3';
      input.dataset.maxFixtures = '8';
      input.dataset.mode = 'add';
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.setMatchDate).toHaveBeenCalledWith('2025-05-10', 3, 8, 'add');

      const input2 = document.createElement('input');
      input2.value = '2025-06-01';
      input2.setAttribute('data-action', 'set-match-date');
      Object.defineProperty(input2, 'dataset', { value: undefined, configurable: true });
      container.appendChild(input2);

      input2.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.setMatchDate).toHaveBeenCalledWith('2025-06-01', 0, 0, '');
    });

    it('uses dataset fallback and defaults for set-match-day-pop-up', () => {
      const input = document.createElement('input');
      input.value = '2025-07-15';
      input.setAttribute('data-action', 'set-match-day-pop-up');
      input.dataset.index = '2';
      input.dataset.maxFixtures = '6';
      input.dataset.mode = 'edit';
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.setMatchDayPopUp).toHaveBeenCalledWith('2025-07-15', 2, 6, 'edit');

      const input2 = document.createElement('input');
      input2.value = '2025-08-01';
      input2.setAttribute('data-action', 'set-match-day-pop-up');
      Object.defineProperty(input2, 'dataset', { value: undefined, configurable: true });
      container.appendChild(input2);

      input2.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.setMatchDayPopUp).toHaveBeenCalledWith('2025-08-01', 0, 0, '');
    });

    it('uses dataset fallback and missing home_team input fallback for insert-home-stadium', () => {
      const input = document.createElement('input');
      input.value = '999';
      input.setAttribute('data-action', 'insert-home-stadium');
      input.dataset.index = '4';
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.insertHomeStadium).toHaveBeenCalledWith('999', 4);

      const input2 = document.createElement('input');
      input2.value = '888';
      input2.setAttribute('data-action', 'insert-home-stadium');
      Object.defineProperty(input2, 'dataset', { value: undefined, configurable: true });
      container.appendChild(input2);

      input2.dispatchEvent(new Event('change', { bubbles: true }));
      expect(window.Racketmanager.insertHomeStadium).toHaveBeenCalledWith('888', 0);
    });

    it('uses dataset fallback for action and tableId in handleFocusOut', () => {
      const input = document.createElement('input');
      input.value = '12';
      input.setAttribute('data-action', '');
      input.dataset.action = 'save-add-points';
      input.dataset.tableId = 'table-88';
      container.appendChild(input);

      input.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));
      expect(window.Racketmanager.saveAddPoints).toHaveBeenCalledWith('12', 'table-88');

      const input2 = document.createElement('input');
      input2.value = '15';
      input2.setAttribute('data-action', 'save-add-points');
      Object.defineProperty(input2, 'dataset', { value: undefined, configurable: true });
      container.appendChild(input2);

      input2.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));
      expect(window.Racketmanager.saveAddPoints).toHaveBeenCalledWith('15', null);
    });

    it('resizes iframe using contentWindow.document when contentDocument is null and body is null', () => {
      const fakeDoc = {
        body: null,
        documentElement: { clientHeight: 150, scrollHeight: 200, offsetHeight: 180 },
      };
      const iframe = {
        contentDocument: null,
        contentWindow: { document: fakeDoc },
        style: {},
      };
      setIframeHeight(iframe);
      expect(iframe.style.visibility).toBe('visible');
      expect(iframe.style.height).toBe('204px');
    });

    it('handles iframe without contentDocument and without contentWindow', () => {
      const iframe = {
        contentDocument: null,
        contentWindow: null,
        style: {},
      };
      expect(() => setIframeHeight(iframe)).not.toThrow();
    });

    it('registers on document by default when called without arguments', () => {
      const origFlag = document.__rmAdminActionsRegistered;
      delete document.__rmAdminActionsRegistered;
      try {
        expect(() => registerAdminActions()).not.toThrow();
        expect(document.__rmAdminActionsRegistered).toBe(true);
      } finally {
        document.__rmAdminActionsRegistered = origFlag;
      }
    });

    it('handles elements with undefined dataset in click, change, focusout, and actions', () => {
      const btn = document.createElement('button');
      btn.setAttribute('data-action', '');
      Object.defineProperty(btn, 'dataset', { value: undefined, configurable: true });
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      btn.dispatchEvent(new Event('change', { bubbles: true }));
      btn.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));

      // check-all with undefined dataset
      const checkAllBtn = document.createElement('button');
      checkAllBtn.type = 'button';
      checkAllBtn.setAttribute('data-action', 'check-all');
      Object.defineProperty(checkAllBtn, 'dataset', { value: undefined, configurable: true });
      const form = document.createElement('form');
      form.appendChild(checkAllBtn);
      container.appendChild(form);

      checkAllBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(window.Racketmanager.checkAll).toHaveBeenCalledWith(form);

      // notify-teams with undefined dataset
      const notifyBtn = document.createElement('button');
      notifyBtn.setAttribute('data-action', 'notify-teams');
      Object.defineProperty(notifyBtn, 'dataset', { value: undefined, configurable: true });
      container.appendChild(notifyBtn);

      notifyBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
      expect(window.Racketmanager.notifyTeams).toHaveBeenCalledWith(null);
    });
  });
});
