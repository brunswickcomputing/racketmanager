/**
 * Tests for admin team form validation.
 */
import {
  validateTeamPlayerForm,
  initTeamValidation,
} from '../../../src/js/admin/features/forms/team-validation.js';

describe('Admin feature: team-validation', () => {
  let container;

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.clearAllMocks();
  });

  it('fails validation when team name is empty and sets error message', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="">
        <input type="hidden" id="team_id" value="">
        <input type="text" id="teamPlayer1" value="Player One">
        <input type="hidden" id="clubId" value="10">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;

    const preventDefault = jest.fn();
    const result = validateTeamPlayerForm({ preventDefault });

    expect(result).toBe(false);
    expect(preventDefault).toHaveBeenCalled();
    const errorMsg = container.querySelector('#errorMsg');
    expect(errorMsg.textContent).toContain('Team name not set');
  });

  it('fails validation when player 1 is empty', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="Leigh 1">
        <input type="hidden" id="team_id" value="5">
        <input type="text" id="teamPlayer1" value="">
        <input type="hidden" id="clubId" value="10">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;

    const preventDefault = jest.fn();
    const result = validateTeamPlayerForm({ preventDefault });

    expect(result).toBe(false);
    expect(preventDefault).toHaveBeenCalled();
    expect(container.querySelector('#errorMsg').textContent).toContain('Player 1 not set');
  });

  it('fails validation when club is not selected (0 or empty)', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="Leigh 1">
        <input type="hidden" id="team_id" value="5">
        <input type="text" id="teamPlayer1" value="Player One">
        <input type="hidden" id="clubId" value="0">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;

    const preventDefault = jest.fn();
    const result = validateTeamPlayerForm({ preventDefault });

    expect(result).toBe(false);
    expect(preventDefault).toHaveBeenCalled();
    expect(container.querySelector('#errorMsg').textContent).toContain('Club not set');
  });

  it('passes validation when all required fields are present', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="Leigh 1">
        <input type="hidden" id="team_id" value="5">
        <input type="text" id="teamPlayer1" value="Player One">
        <input type="hidden" id="clubId" value="12">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;

    const preventDefault = jest.fn();
    const result = validateTeamPlayerForm({ preventDefault });

    expect(result).toBe(true);
    expect(preventDefault).not.toHaveBeenCalled();
  });

  it('initTeamValidation registers submit handler on the form', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="">
        <input type="hidden" id="team_id" value="">
        <input type="text" id="teamPlayer1" value="">
        <input type="hidden" id="clubId" value="0">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;

    initTeamValidation(container);

    const form = container.querySelector('#teamPlayerFrm');
    const submitEvent = new Event('submit', { cancelable: true });
    form.dispatchEvent(submitEvent);

    expect(submitEvent.defaultPrevented).toBe(true);
    expect(container.querySelector('#errorMsg').textContent).toContain('Team name not set');
  });

  it('fails validation when team element is missing from the DOM', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="teamPlayer1" value="Player One">
        <input type="hidden" id="clubId" value="10">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;
    const preventDefault = jest.fn();
    expect(validateTeamPlayerForm({ preventDefault })).toBe(false);
    expect(container.querySelector('#errorMsg').textContent).toContain('Team name not set');
  });

  it('fails validation when teamPlayer1 element is missing from the DOM', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="Leigh 1">
        <input type="hidden" id="team_id" value="5">
        <input type="hidden" id="clubId" value="10">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;
    const preventDefault = jest.fn();
    expect(validateTeamPlayerForm({ preventDefault })).toBe(false);
    expect(container.querySelector('#errorMsg').textContent).toContain('Player 1 not set');
  });

  it('passes validation when player 2 is set with a valid non-empty value', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="Leigh 1">
        <input type="hidden" id="team_id" value="5">
        <input type="text" id="teamPlayer1" value="Player One">
        <input type="hidden" id="teamPlayerId2" value="42">
        <input type="hidden" id="clubId" value="10">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;
    const preventDefault = jest.fn();
    expect(validateTeamPlayerForm({ preventDefault })).toBe(true);
    expect(preventDefault).not.toHaveBeenCalled();
  });

  it('fails validation when clubId element is missing from DOM', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="Leigh 1">
        <input type="hidden" id="team_id" value="5">
        <input type="text" id="teamPlayer1" value="Player One">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;
    const preventDefault = jest.fn();
    expect(validateTeamPlayerForm({ preventDefault })).toBe(false);
    expect(container.querySelector('#errorMsg').textContent).toContain('Club not set');
  });

  it('fails validation when clubId value is an empty string', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="Leigh 1">
        <input type="hidden" id="team_id" value="5">
        <input type="text" id="teamPlayer1" value="Player One">
        <input type="hidden" id="clubId" value="">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;
    const preventDefault = jest.fn();
    expect(validateTeamPlayerForm({ preventDefault })).toBe(false);
    expect(container.querySelector('#errorMsg').textContent).toContain('Club not set');
  });

  it('fails validation without throwing when #errorMsg element is absent', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="">
        <input type="hidden" id="team_id" value="">
      </form>
    `;
    const preventDefault = jest.fn();
    expect(validateTeamPlayerForm({ preventDefault })).toBe(false);
    expect(preventDefault).toHaveBeenCalled();
  });

  it('handles invocation without event argument or with event missing preventDefault', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="">
      </form>
    `;
    expect(() => validateTeamPlayerForm()).not.toThrow();
    expect(validateTeamPlayerForm()).toBe(false);

    expect(() => validateTeamPlayerForm({})).not.toThrow();
    expect(validateTeamPlayerForm({})).toBe(false);

    expect(() => validateTeamPlayerForm({ preventDefault: null })).not.toThrow();
  });

  it('handles null root or root without querySelector in initTeamValidation', () => {
    expect(() => initTeamValidation(null)).not.toThrow();
    expect(() => initTeamValidation({})).not.toThrow();
  });

  it('does not throw when #teamPlayerFrm is not found in root', () => {
    const emptyContainer = document.createElement('div');
    expect(() => initTeamValidation(emptyContainer)).not.toThrow();
  });

  it('attaches listener using default root = document when called without arguments', () => {
    container.innerHTML = `
      <form id="teamPlayerFrm">
        <input type="text" id="team" value="">
        <input type="hidden" id="clubId" value="0">
        <div id="errorMsg" style="display:none;"></div>
      </form>
    `;
    initTeamValidation();

    const form = document.querySelector('#teamPlayerFrm');
    const submitEvent = new Event('submit', { cancelable: true });
    form.dispatchEvent(submitEvent);

    expect(submitEvent.defaultPrevented).toBe(true);
  });

  describe('duplicate team name check via AJAX', () => {
    const originalJQuery = window.jQuery;
    const originalAjax = window.jQuery.ajax;

    const renderForm = ({ teamName = 'New Team', teamId = '' } = {}) => {
      container.innerHTML = `
        <form id="teamPlayerFrm">
          <input type="text" id="team" value="${teamName}">
          <input type="hidden" id="team_id" value="${teamId}">
          <input type="text" id="teamPlayer1" value="Player One">
          <input type="hidden" id="clubId" value="10">
          <div id="errorMsg" style="display:none;"></div>
        </form>
      `;
    };

    beforeEach(() => {
      window.jQuery = originalJQuery;
      window.jQuery.ajax = jest.fn();
    });

    afterEach(() => {
      window.jQuery = originalJQuery;
      window.jQuery.ajax = originalAjax;
    });

    it('posts the team name, action, and nonce synchronously when team id is empty', () => {
      renderForm({ teamName: 'New Team' });
      validateTeamPlayerForm({ preventDefault: jest.fn() });

      expect(window.jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
        type: 'POST',
        async: false,
        url: '/wp-admin/admin-ajax.php',
        data: {
          name: 'New Team',
          action: 'racketmanager_check_team_exists',
          security: 'test-nonce',
        },
      }));
    });

    it('does not check for duplicates when an existing team id is set', () => {
      renderForm({ teamName: 'Existing Team', teamId: '5' });
      expect(validateTeamPlayerForm({ preventDefault: jest.fn() })).toBe(true);
      expect(window.jQuery.ajax).not.toHaveBeenCalled();
    });

    it('fails validation with "Team already exists" when the server reports a duplicate', () => {
      renderForm({ teamName: 'Leigh 1' });
      window.jQuery.ajax.mockImplementation((opts) => opts.success({ data: true }));

      const preventDefault = jest.fn();
      expect(validateTeamPlayerForm({ preventDefault })).toBe(false);
      expect(preventDefault).toHaveBeenCalled();
      expect(container.querySelector('#errorMsg').textContent).toContain('Team already exists');
    });

    it('passes validation when the server reports the name is available', () => {
      renderForm({ teamName: 'Fresh Team' });
      window.jQuery.ajax.mockImplementation((opts) => opts.success({ data: false }));

      const preventDefault = jest.fn();
      expect(validateTeamPlayerForm({ preventDefault })).toBe(true);
      expect(preventDefault).not.toHaveBeenCalled();
    });

    it('fails validation with an error message when the duplicate check request fails', () => {
      renderForm({ teamName: 'Leigh 1' });
      window.jQuery.ajax.mockImplementation((opts) => opts.error());

      expect(validateTeamPlayerForm({ preventDefault: jest.fn() })).toBe(false);
      expect(container.querySelector('#errorMsg').textContent).toContain('Error with team name check');
    });

    it('reports Player 2 not set when the optional second player field is empty', () => {
      container.innerHTML = `
        <form id="teamPlayerFrm">
          <input type="text" id="team" value="Leigh 1">
          <input type="hidden" id="team_id" value="5">
          <input type="text" id="teamPlayer1" value="Player One">
          <input type="hidden" id="teamPlayerId2" value="">
          <input type="hidden" id="clubId" value="10">
          <div id="errorMsg" style="display:none;"></div>
        </form>
      `;

      expect(validateTeamPlayerForm({ preventDefault: jest.fn() })).toBe(false);
      expect(container.querySelector('#errorMsg').textContent).toContain('Player 2 not set');
    });

    it('triggers duplicate check when team_id element is missing from the form', () => {
      container.innerHTML = `
        <form id="teamPlayerFrm">
          <input type="text" id="team" value="New Team">
          <input type="text" id="teamPlayer1" value="Player One">
          <input type="hidden" id="clubId" value="10">
          <div id="errorMsg" style="display:none;"></div>
        </form>
      `;
      validateTeamPlayerForm({ preventDefault: jest.fn() });
      expect(window.jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
        data: expect.objectContaining({ name: 'New Team', action: 'racketmanager_check_team_exists' })
      }));
    });

    it('does not crash and continues validation when window.jQuery is undefined or ajax is not a function', () => {
      renderForm({ teamName: 'New Team' });
      const origJq = window.jQuery;
      try {
        delete window.jQuery;
        expect(validateTeamPlayerForm({ preventDefault: jest.fn() })).toBe(true);

        window.jQuery = { ajax: 'not-a-fn' };
        expect(validateTeamPlayerForm({ preventDefault: jest.fn() })).toBe(true);
      } finally {
        window.jQuery = origJq;
      }
    });

    it('passes validation when duplicate check AJAX response is null or missing data', () => {
      renderForm({ teamName: 'New Team' });
      window.jQuery.ajax.mockImplementation((opts) => opts.success(null));
      expect(validateTeamPlayerForm({ preventDefault: jest.fn() })).toBe(true);

      window.jQuery.ajax.mockImplementation((opts) => opts.success({ data: null }));
      expect(validateTeamPlayerForm({ preventDefault: jest.fn() })).toBe(true);
    });
  });
});
