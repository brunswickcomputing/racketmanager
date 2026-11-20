/**
 * @jest-environment jsdom
 */

import { setMatchStatus, initializeSetMatchStatus } from '../../../../src/js/features/match/set-match-status.js';
import * as rubberStatusUi from '../../../../src/js/features/match/rubber-status-ui.js';

describe('features/match/set-match-status', () => {
    const originalAjaxVar = window.ajax_var;

    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'nonce-123' };
        window.jQuery = window.$ = require('jquery');
    });

    afterEach(() => {
        window.ajax_var = originalAjaxVar;
        jest.restoreAllMocks();
    });

    test('exits early if link or link.form is missing', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        setMatchStatus(null);
        setMatchStatus({});
        setMatchStatus({ form: {} });
        expect(spyAjax).not.toHaveBeenCalled();
    });

    test('serializes form, passes security nonce, and updates status for rubbers when num_rubbers provided', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        const spyMsg = jest.spyOn(rubberStatusUi, 'setRubberStatusMessages').mockImplementation(() => {});
        const spyClasses = jest.spyOn(rubberStatusUi, 'setRubberStatusClasses').mockImplementation(() => {});

        document.body.innerHTML = `
            <div id="splashBlockMatch"></div>
            <div id="matchStatusResponse"></div>
            <div id="matchStatusResponseText"></div>
            <input id="match_status" />
            <input id="match_status_1" />
            <input id="match_status_2" />
            <div id="statusModal"></div>
            <form id="statusForm">
                <input name="security" value="nonce-123" />
                <input name="test" value="abc" />
                <button id="btnStatus" type="button"></button>
            </form>
        `;

        // Mock modal method on jQuery
        jQuery.fn.modal = jest.fn();

        const btn = document.getElementById('btnStatus');
        setMatchStatus(btn);

        expect(jQuery('#splashBlockMatch').hasClass('is-loading')).toBe(true);
        expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
            data: expect.stringContaining('action=racketmanager_set_fixture_status')
        }));
        expect(spyAjax.mock.calls[0][0].data).toContain('security=nonce-123');

        const ajaxOptions = spyAjax.mock.calls[0][0];
        ajaxOptions.success({
            data: {
                match_status: 'completed',
                status_message: { '1': 'Rubber 1 finished' },
                status_class: { '1': 'winner' },
                num_rubbers: 2,
                modal: 'statusModal'
            }
        });

        expect(spyMsg).toHaveBeenCalledTimes(2);
        expect(spyClasses).toHaveBeenCalledTimes(2);
        expect(jQuery('#match_status_1').attr('value')).toBe('completed');
        expect(jQuery('#match_status_2').attr('value')).toBe('completed');
        expect(jQuery('#match_status').attr('value')).toBe('completed');
        expect(jQuery.fn.modal).toHaveBeenCalledWith('hide');

        ajaxOptions.complete();
        expect(jQuery('#splashBlockMatch').hasClass('is-loading')).toBe(false);

        spyMsg.mockRestore();
        spyClasses.mockRestore();
    });

    test('handles singles / non-rubber match status updates', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        const spyTeamMsg = jest.spyOn(rubberStatusUi, 'setTeamMessage').mockImplementation(() => {});

        document.body.innerHTML = `
            <div id="splashBlockMatch"></div>
            <div id="matchStatusResponse"></div>
            <div id="matchStatusResponseText"></div>
            <input id="match_status" />
            <div id="match-status-home" class="winner"></div>
            <div id="match-status-away"></div>
            <form id="statusForm">
                <input name="security" value="nonce-123" />
                <button id="btnStatus" type="button"></button>
            </form>
        `;

        const btn = document.getElementById('btnStatus');
        setMatchStatus(btn);

        const ajaxOptions = spyAjax.mock.calls[0][0];
        ajaxOptions.success({
            data: {
                match_status: 'walkover',
                status_message: { home: 'Walkover Win' },
                status_class: { home: 'winner', away: 'loser' }
            }
        });

        expect(spyTeamMsg).toHaveBeenCalledWith('#match-message-home', 'Walkover Win');
        expect(jQuery('#match-status-home').hasClass('winner')).toBe(true);
        expect(jQuery('#match-status-away').hasClass('loser')).toBe(true);
        expect(jQuery('#match_status').attr('value')).toBe('walkover');

        spyTeamMsg.mockRestore();
    });

    test('handles AJAX error response and silences console output', () => {
        const spyError = jest.spyOn(console, 'error').mockImplementation(() => {});
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        document.body.innerHTML = `
            <div id="splashBlockMatch"></div>
            <div id="matchStatusResponse" style="display: none;"></div>
            <div id="matchStatusResponseText"></div>
            <form id="statusForm">
                <button id="btnStatus" type="button"></button>
            </form>
        `;

        const btn = document.getElementById('btnStatus');
        setMatchStatus(btn);

        const ajaxOptions = spyAjax.mock.calls[0][0];
        ajaxOptions.error({
            responseJSON: { data: { message: 'Invalid fixture status transition' } }
        });

        expect(jQuery('#matchStatusResponseText').text()).toContain('Invalid fixture status transition');
        expect(jQuery('#matchStatusResponse').css('display')).not.toBe('none');
        spyError.mockRestore();
    });

    test('initializeSetMatchStatus attaches click handler to [data-action="set-match-status"]', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        document.body.innerHTML = `
            <form id="formAction">
                <input name="security" value="nonce-123" />
                <button id="actionBtn" data-action="set-match-status"></button>
            </form>
        `;

        initializeSetMatchStatus();
        document.getElementById('actionBtn').click();

        expect(spyAjax).toHaveBeenCalledTimes(1);
        expect(spyAjax.mock.calls[0][0].data).toContain('security=nonce-123');
    });
});
