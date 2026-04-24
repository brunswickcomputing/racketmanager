import { setMatchDate } from '../../../../src/js/features/match/set-match-date';
import { matchHeader } from '../../../../src/js/features/match/match-header';

jest.mock('../../../../src/js/features/match/match-header', () => ({
  matchHeader: jest.fn(),
}));

describe('setMatchDate', () => {
  let link;
  let form;

  beforeEach(() => {
    document.body.innerHTML = `
      <div id="matchOptionsAlert" style="display:none;">
        <div id="alertMatchOptionsResponse"></div>
      </div>
      <div id="matchAlert" style="display:none;">
        <div id="matchAlertResponse"></div>
      </div>
      <div id="matchDateAlert" style="display:none;">
        <div id="alertMatchDateResponse"></div>
      </div>
      <div id="match-tournament-date-header"></div>
      <form id="testForm">
        <input type="hidden" name="match_id" value="123" />
        <button type="button" id="submitBtn">Submit</button>
      </form>
    `;

    link = document.getElementById('submitBtn');
    form = document.getElementById('testForm');
    // link.form = form; // This is a getter in JSDOM
    
    // Mock jQuery modal
    jQuery.fn.modal = jest.fn();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('calls AJAX with correct data and handles success for league', () => {
    const response = {
      data: {
        msg: 'Date updated',
        modal: 'testModal',
        match_id: 123,
        schedule_date: '2026-05-01'
      }
    };

    jQuery.ajax.mockImplementation((settings) => {
      settings.success(response);
    });

    setMatchDate(link, false);

    expect(jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
      type: 'POST',
      data: expect.stringContaining('action=racketmanager_set_match_date')
    }));

    expect(jQuery('#matchOptionsAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#alertMatchOptionsResponse').html()).toBe('Date updated');
    expect(jQuery.fn.modal).toHaveBeenCalledWith('hide');
    expect(matchHeader).toHaveBeenCalledWith(123);
  });

  test('handles success for tournament', () => {
    const response = {
      data: {
        msg: 'Tournament date updated',
        modal: 'tournamentModal',
        schedule_date: '2026-06-01'
      }
    };

    jQuery.ajax.mockImplementation((settings) => {
      settings.success(response);
    });

    setMatchDate(link, true);

    expect(jQuery('#matchAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#matchAlertResponse').html()).toBe('Tournament date updated');
    expect(jQuery('#match-tournament-date-header').html()).toBe('2026-06-01');
    expect(matchHeader).not.toHaveBeenCalled();
  });
});
