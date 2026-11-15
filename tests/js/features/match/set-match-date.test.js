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
        <input type="date" id="schedule-date" name="schedule-date" value="2026-05-01" />
        <div id="schedule-dateFeedback"></div>
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

  test('calls AJAX with correct data and handles success for league when no modal exists', () => {
    const response = {
      data: {
        msg: 'Date updated',
        modal: 'nonExistentModal',
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
      data: expect.stringContaining('action=racketmanager_set_fixture_date')
    }));

    expect(jQuery('#matchOptionsAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#alertMatchOptionsResponse').html()).toBe('Date updated');
    expect(jQuery('#matchDateAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#alertMatchDateResponse').html()).toBe('Date updated');
    expect(jQuery.fn.modal).not.toHaveBeenCalled();
    expect(matchHeader).toHaveBeenCalledWith(123);
  });

  test('keeps modal open on league success when button is inside modal and defers matchHeader', () => {
    const modalDiv = document.createElement('div');
    modalDiv.id = 'containerModal';
    modalDiv.className = 'modal';
    const formEl = document.getElementById('testForm');
    modalDiv.appendChild(formEl);
    document.body.appendChild(modalDiv);

    const response = {
      data: {
        msg: 'Date updated',
        modal: 'containerModal',
        match_id: 123,
        schedule_date: '2026-05-01'
      }
    };

    jQuery.ajax.mockImplementation((settings) => {
      settings.success(response);
    });

    setMatchDate(link, false);

    expect(jQuery('#matchOptionsAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#matchDateAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#alertMatchDateResponse').html()).toBe('Date updated');
    expect(jQuery.fn.modal).not.toHaveBeenCalled();
    expect(matchHeader).not.toHaveBeenCalled();

    // Trigger modal hidden event
    jQuery('#containerModal').trigger('hidden.bs.modal');
    expect(matchHeader).toHaveBeenCalledWith(123);
  });

  test('keeps modal open on league success and defers matchHeader until modal is hidden', () => {
    const modalDiv = document.createElement('div');
    modalDiv.id = 'testModal';
    modalDiv.className = 'modal show';
    document.body.appendChild(modalDiv);

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

    expect(jQuery('#matchOptionsAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#matchDateAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#alertMatchDateResponse').html()).toBe('Date updated');
    expect(jQuery.fn.modal).not.toHaveBeenCalled();
    // matchHeader should NOT be called immediately while modal is open
    expect(matchHeader).not.toHaveBeenCalled();

    // Trigger modal hidden event
    jQuery('#testModal').trigger('hidden.bs.modal');
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
    expect(jQuery('#matchDateAlert').hasClass('alert--success')).toBe(true);
    expect(jQuery('#alertMatchDateResponse').html()).toBe('Tournament date updated');
    expect(jQuery('#match-tournament-date-header').html()).toBe('2026-06-01');
    expect(jQuery.fn.modal).not.toHaveBeenCalled();
    expect(matchHeader).not.toHaveBeenCalled();
  });

  test('handles error response when date is not changed', () => {
    const errorResponse = {
      status: 400,
      responseJSON: {
        data: {
          msg: 'Unable to set fixture date',
          err_flds: ['schedule-date'],
          err_msgs: ['Date not changed']
        }
      }
    };

    jQuery.ajax.mockImplementation((settings) => {
      settings.error(errorResponse);
    });

    setMatchDate(link, false);

    expect(jQuery('#matchDateAlert').hasClass('alert--danger')).toBe(true);
    expect(jQuery('#alertMatchDateResponse').html()).toBe('Unable to set fixture date');
    expect(jQuery('#schedule-dateFeedback').html()).toBe('Date not changed');
    expect(jQuery('#schedule-date').hasClass('is-invalid')).toBe(true);
    expect(jQuery.fn.modal).not.toHaveBeenCalled();
  });
});
