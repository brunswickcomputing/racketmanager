import { matchHeader } from '../../../../src/js/features/match/match-header';

describe('matchHeader', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div id="match-header">Old Header Content</div>
      <div id="headerResponse" style="display:none;">
        <div id="headerResponseText"></div>
      </div>
    `;
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('calls AJAX and updates header on success', () => {
    const response = {
      data: 'New Header HTML'
    };

    jQuery.ajax.mockImplementation((settings) => {
      settings.success(response);
      settings.complete();
    });

    matchHeader(456, true);

    expect(jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
      type: 'POST',
      data: expect.objectContaining({
        match_id: 456,
        edit_mode: true,
        action: 'racketmanager_update_match_header'
      })
    }));

    expect(jQuery('#match-header').html()).toBe('New Header HTML');
    expect(jQuery('#match-header').css('display')).not.toBe('none');
  });

  test('handles error and shows alert', () => {
    const errorResponse = {
      responseJSON: {
        data: {
          msg: 'Header refresh failed'
        }
      }
    };

    jQuery.ajax.mockImplementation((settings) => {
      settings.error(errorResponse);
      settings.complete();
    });

    matchHeader(456);

    expect(jQuery('#headerResponse').css('display')).not.toBe('none');
    expect(jQuery('#headerResponseText').html()).toBe('Header refresh failed');
  });
});
