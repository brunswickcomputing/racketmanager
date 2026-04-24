import { resetMatchResult } from '../../../../src/js/features/match/reset-match-result';
import { getAjaxUrl } from '../../../../src/js/config/ajax-config';
import { matchHeader } from '../../../../src/js/features/match/match-header';
import { handleAjaxError } from '../../../../src/js/features/ajax/handle-ajax-error';
import { resetMatchScoresByFormId } from '../../../../src/js/features/match/reset-match-scores';

jest.mock('../../../../src/js/config/ajax-config');
jest.mock('../../../../src/js/features/match/match-header');
jest.mock('../../../../src/js/features/ajax/handle-ajax-error');
jest.mock('../../../../src/js/features/match/reset-match-scores');

describe('resetMatchResult', () => {
  let mockLink;
  let mockForm;

  beforeEach(() => {
    document.body.innerHTML = `
      <div id="matchAlert" style="display:none"><div id="matchAlertResponse"></div></div>
      <div id="matchOptionsAlert" style="display:none"><div id="alertMatchOptionsResponse"></div></div>
      <div id="matchResetAlert" style="display:none"><div id="alertMatchResetResponse"></div></div>
      <form id="resetForm">
        <input type="hidden" name="match_id" value="456" />
      </form>
      <div id="match-view"></div>
    `;

    mockForm = document.getElementById('resetForm');
    mockLink = { form: mockForm };

    getAjaxUrl.mockReturnValue('/wp-admin/admin-ajax.php');
    jQuery.ajax = jest.fn();
    
    Object.defineProperty(window, 'history', {
      value: { replaceState: jest.fn() },
      writable: true
    });
  });

  test('should call jQuery.ajax with reset action', () => {
    resetMatchResult(mockLink);

    expect(jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
      data: expect.stringContaining('action=racketmanager_reset_match_result'),
    }));
    expect(jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
      data: expect.stringContaining('match_id=456')
    }));
  });

  test('should handle success and update UI', () => {
    resetMatchResult(mockLink, false);

    const successCallback = jQuery.ajax.mock.calls[0][0].success;
    successCallback({
      data: {
        msg: 'Reset successful',
        link: 'https://example.com/reset-link',
        match_id: 456,
        modal: 'testModal'
      }
    });

    const alert = jQuery('#matchOptionsAlert');
    expect(alert.css('display')).not.toBe('none');
    expect(jQuery('#alertMatchOptionsResponse').html()).toBe('Reset successful');
    
    expect(window.history.replaceState).toHaveBeenCalledWith('', 'Test Title', 'https://example.com/reset-link');
    expect(matchHeader).toHaveBeenCalledWith(456);
    expect(resetMatchScoresByFormId).toHaveBeenCalledWith('match-view');
  });

  test('should use tournament alerts when isTournament is true', () => {
    resetMatchResult(mockLink, true);

    const successCallback = jQuery.ajax.mock.calls[0][0].success;
    successCallback({ data: { msg: 'Tournament Reset', match_id: 456 } });

    expect(jQuery('#matchAlert').css('display')).not.toBe('none');
    expect(jQuery('#matchAlertResponse').html()).toBe('Tournament Reset');
  });
});
