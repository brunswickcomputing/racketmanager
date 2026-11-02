/**
 * Tests for admin/ajax.js dropdown cascade event delegation.
 * Verifies that dropdown cascades function on post-edit screens where only ajax.js is loaded.
 */
describe('Admin ajax: dropdown cascades delegation', () => {
  let container;

  beforeAll(async () => {
    window.ajaxurl = '/wp-admin/admin-ajax.php';
    window.ajax_var = { ajax_nonce: 'test-nonce' };
    await import('../../../src/js/admin/ajax.js');
  });

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);

    window.Racketmanager = window.Racketmanager || {};
    window.Racketmanager.getEventDropdown = jest.fn();
    window.Racketmanager.getLeagueDropdown = jest.fn();
    window.Racketmanager.getSeasonDropdown = jest.fn();
    window.Racketmanager.getMatchDropdown = jest.fn();
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.clearAllMocks();
  });

  it('invokes getEventDropdown on change of [data-action="get-event-dropdown"]', () => {
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

  it('invokes getLeagueDropdown on change of [data-action="get-league-dropdown"]', () => {
    const select = document.createElement('select');
    select.setAttribute('data-action', 'get-league-dropdown');
    const opt = document.createElement('option');
    opt.value = '25';
    select.appendChild(opt);
    select.value = '25';
    container.appendChild(select);

    select.dispatchEvent(new Event('change', { bubbles: true }));

    expect(window.Racketmanager.getLeagueDropdown).toHaveBeenCalledWith('25');
  });

  it('invokes getSeasonDropdown on change of [data-action="get-season-dropdown"]', () => {
    const select = document.createElement('select');
    select.setAttribute('data-action', 'get-season-dropdown');
    const opt = document.createElement('option');
    opt.value = '33';
    select.appendChild(opt);
    select.value = '33';
    container.appendChild(select);

    select.dispatchEvent(new Event('change', { bubbles: true }));

    expect(window.Racketmanager.getSeasonDropdown).toHaveBeenCalledWith('33');
  });

  it('invokes getMatchDropdown on change of [data-action="get-match-dropdown"] with league id and season', () => {
    const select = document.createElement('select');
    select.setAttribute('data-action', 'get-match-dropdown');
    select.setAttribute('data-league-id', '12');
    const opt = document.createElement('option');
    opt.value = 'Summer 2026';
    select.appendChild(opt);
    select.value = 'Summer 2026';
    container.appendChild(select);

    select.dispatchEvent(new Event('change', { bubbles: true }));

    expect(window.Racketmanager.getMatchDropdown).toHaveBeenCalledWith('12', 'Summer 2026');
  });

  it('handles cascades on dynamically inserted dropdown elements (post-edit metabox injection)', () => {
    // Simulate AJAX injection into #seasons container
    const seasonsContainer = document.createElement('div');
    seasonsContainer.id = 'seasons';
    container.appendChild(seasonsContainer);

    // Dynamic injection of season dropdown
    seasonsContainer.innerHTML = `
      <select name="season" id="season" class="form-select" data-action="get-match-dropdown" data-league-id="45">
        <option value="0">Choose Season</option>
        <option value="2026" selected>2026</option>
      </select>
    `;

    const seasonSelect = seasonsContainer.querySelector('#season');
    seasonSelect.dispatchEvent(new Event('change', { bubbles: true }));

    expect(window.Racketmanager.getMatchDropdown).toHaveBeenCalledWith('45', '2026');
  });
});
