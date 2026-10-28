import fs from 'fs';
import path from 'path';
import vm from 'vm';
import { registerAdminActions } from '../../../src/js/admin/admin-actions.js';

describe('Admin utils: registerAdminActions', () => {
  let container;

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);

    window.Racketmanager = window.Racketmanager || {};
    window.Racketmanager.checkAll = jest.fn();
    window.Racketmanager.notifyTeams = jest.fn();
    window.Racketmanager.setNumMatchDays = jest.fn();
    window.Racketmanager.setEndDate = jest.fn();
    window.Racketmanager.setEventName = jest.fn();
    window.Racketmanager.setShortcode = jest.fn();
    window.Racketmanager.setTournamentOpenDate = jest.fn();
    window.Racketmanager.getImportOption = jest.fn();
    window.Racketmanager.getEventDropdown = jest.fn();
    window.Racketmanager.setMatchDate = jest.fn();
    window.Racketmanager.setMatchDayPopUp = jest.fn();
    window.Racketmanager.insertHomeStadium = jest.fn();
    window.Racketmanager.saveAddPoints = jest.fn();
    window.setIframeHeight = jest.fn();

    registerAdminActions(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.clearAllMocks();
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

    it('invokes setNumMatchDays on change of number input', () => {
      const input = document.createElement('input');
      input.type = 'number';
      input.setAttribute('data-action', 'set-num-match-days');
      container.appendChild(input);

      input.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.setNumMatchDays).toHaveBeenCalled();
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

    it('invokes getEventDropdown on change with competition id', () => {
      const select = document.createElement('select');
      select.setAttribute('data-action', 'get-event-dropdown');
      const opt = document.createElement('option');
      opt.value = '10';
      select.appendChild(opt);
      select.value = '10';
      container.appendChild(select);

      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect(window.Racketmanager.getEventDropdown).toHaveBeenCalledWith('10');
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
    it('invokes setIframeHeight exactly once on iframe load', () => {
      const iframe = document.createElement('iframe');
      iframe.id = 'my-iframe';
      iframe.setAttribute('data-action', 'set-iframe-height');
      // jsdom automatically dispatches 'load' when iframe is appended to document
      container.appendChild(iframe);

      expect(window.setIframeHeight).toHaveBeenCalledTimes(1);
      expect(window.setIframeHeight).toHaveBeenCalledWith('my-iframe');
    });
  });

  describe('double-registration guard', () => {
    it('does not attach duplicate listeners or fire duplicate actions when registered twice', () => {
      registerAdminActions(container);

      const btn = document.createElement('button');
      btn.setAttribute('data-action', 'notify-teams');
      btn.setAttribute('data-match-id', '100');
      container.appendChild(btn);

      btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(window.Racketmanager.notifyTeams).toHaveBeenCalledTimes(1);
    });
  });
});
