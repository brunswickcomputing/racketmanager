import { switchHomeAway } from '../../../../src/js/features/match/switch-home-away';
import { getAjaxUrl } from '../../../../src/js/config/ajax-config';
import { matchHeader } from '../../../../src/js/features/match/match-header';
import { handleAjaxError } from '../../../../src/js/features/ajax/handle-ajax-error';

jest.mock('../../../../src/js/config/ajax-config');
jest.mock('../../../../src/js/features/match/match-header');
jest.mock('../../../../src/js/features/ajax/handle-ajax-error');

describe('switchHomeAway', () => {
  let mockLink;
  let mockForm;

  beforeEach(() => {
    // Set up DOM
    document.body.innerHTML = `
      <div id="matchOptionsAlert" style="display:none">
        <div id="alertMatchOptionsResponse"></div>
      </div>
      <div id="switchFixtureAlert" style="display:none">
        <div id="alertSwitchFixtureResponse"></div>
      </div>
      <form id="testForm">
        <input type="hidden" name="match_id" value="123" />
      </form>
    `;

    mockForm = document.getElementById('testForm');
    mockLink = { form: mockForm };

    getAjaxUrl.mockReturnValue('/wp-admin/admin-ajax.php');

    // Mock jQuery.ajax
    jQuery.ajax = jest.fn();
    
    // Mock history.replaceState
    Object.defineProperty(window, 'history', {
      value: {
        replaceState: jest.fn()
      },
      writable: true
    });
  });

  test('should call jQuery.ajax with correct parameters', () => {
    switchHomeAway(mockLink);

    expect(jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
      url: '/wp-admin/admin-ajax.php',
      type: 'POST',
      data: expect.stringContaining('match_id=123'),
      success: expect.any(Function),
      error: expect.any(Function)
    }));
    
    expect(jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
      data: expect.stringContaining('action=racketmanager_switch_home_away')
    }));
  });

  test('should update URL and show success message on success', () => {
    switchHomeAway(mockLink);

    const successCallback = jQuery.ajax.mock.calls[0][0].success;
    const response = {
      data: {
        msg: 'Teams switched successfully',
        link: 'https://example.com/new-url',
        match_id: 123
      }
    };

    successCallback(response);

    // Verify alert
    const alert = jQuery('#matchOptionsAlert');
    const alertText = jQuery('#alertMatchOptionsResponse');
    expect(alert.css('display')).not.toBe('none');
    expect(alert.hasClass('alert--success')).toBe(true);
    expect(alertText.html()).toBe('Teams switched successfully');

    // Verify history update
    expect(window.history.replaceState).toHaveBeenCalledWith('', 'Test Title', 'https://example.com/new-url');

    // Verify matchHeader call
    expect(matchHeader).toHaveBeenCalledWith(123);
  });

  test('should handle error path', () => {
    switchHomeAway(mockLink);

    const errorCallback = jQuery.ajax.mock.calls[0][0].error;
    const response = { status: 500, responseJSON: { message: 'Error switching' } };

    errorCallback(response);

    expect(handleAjaxError).toHaveBeenCalledWith(response, '#alertSwitchFixtureResponse', '#switchFixtureAlert');
    
    const alert = jQuery('#switchFixtureAlert');
    expect(alert.css('display')).not.toBe('none');
  });
});
