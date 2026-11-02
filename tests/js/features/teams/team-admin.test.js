import { initializeTeamsAdmin } from '../../../../src/js/features/teams/team-admin';

describe('Team Admin feature', () => {
  let container;

  beforeAll(() => {
    initializeTeamsAdmin();
  });

  beforeEach(() => {
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    document.body.removeChild(container);
    jest.clearAllMocks();
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
});
