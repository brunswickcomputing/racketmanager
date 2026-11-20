/**
 * @jest-environment jsdom
 */

import { updateMatchResults, initializeUpdateMatchResults } from '../../../../src/js/features/match/update-match-results.js';

describe('features/match/update-match-results', () => {
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

    test('exits early if link or form is invalid or button is busy', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        updateMatchResults(null);
        updateMatchResults({});
        updateMatchResults({ form: {} });
        expect(spyAjax).not.toHaveBeenCalled();

        const btn = document.createElement('button');
        const form = document.createElement('form');
        form.id = 'form-1';
        form.appendChild(btn);
        document.body.appendChild(form);

        jQuery(btn).data('busy', true);
        updateMatchResults(btn);
        expect(spyAjax).not.toHaveBeenCalled();
    });

    test('renders success UI with object payload (home, away, winner, sets map) and sends nonce', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        document.body.innerHTML = `
            <div id="matchAlert" style="display: none;">
                <div id="matchAlertResponse"></div>
            </div>
            <div id="splash" class="d-none"></div>
            <div class="match__body"></div>
            <input id="home_points" />
            <input id="away_points" />
            <div id="match-status-home" class="team-status"></div>
            <input id="set_1_home" />
            <input id="set_1_away" />
            <form id="matchForm">
                <input name="security" value="nonce-123" />
                <input name="test" value="123" />
                <button id="btnSubmit" type="button"></button>
            </form>
        `;

        const btn = document.getElementById('btnSubmit');
        updateMatchResults(btn);

        expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
            url: '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: expect.stringContaining('action=racketmanager_update_match')
        }));
        expect(spyAjax.mock.calls[0][0].data).toContain('security=nonce-123');

        const ajaxOptions = spyAjax.mock.calls[0][0];

        // Trigger success with object format
        ajaxOptions.success({
            data: {
                msg: 'Match successfully saved',
                home_points: 8,
                away_points: 2,
                winner: 'home',
                sets: {
                    '1': { home: 6, away: 4 }
                }
            }
        });

        expect(jQuery('#matchAlert').hasClass('alert--success')).toBe(true);
        expect(jQuery('#matchAlertResponse').html()).toBe('Match successfully saved');
        expect(jQuery('#home_points').val()).toBe('8');
        expect(jQuery('#away_points').val()).toBe('2');
        expect(jQuery('#match-status-home').hasClass('winner')).toBe(true);
        expect(jQuery('#set_1_home').val()).toBe('6');
        expect(jQuery('#set_1_away').val()).toBe('4');

        // Trigger complete callback
        ajaxOptions.complete();
        expect(jQuery(btn).data('busy')).toBe(false);
        expect(jQuery(btn).prop('disabled')).toBe(false);
        expect(jQuery('.match__body').css('display')).not.toBe('none');
    });

    test('renders success UI with array payload', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        document.body.innerHTML = `
            <div id="matchAlert">
                <div id="matchAlertResponse"></div>
            </div>
            <input id="home_points" />
            <input id="away_points" />
            <div id="match-status-away"></div>
            <input id="set_2_p1" />
            <form id="matchForm">
                <input name="security" value="nonce-123" />
                <button id="btnSubmit" type="button"></button>
            </form>
        `;

        const btn = document.getElementById('btnSubmit');
        updateMatchResults(btn);

        const ajaxOptions = spyAjax.mock.calls[0][0];

        // Trigger success with array format: [message, homePoints, awayPoints, winner, sets]
        ajaxOptions.success({
            data: [
                'Array format success',
                10,
                5,
                'away',
                [[2, { p1: 7 }]]
            ]
        });

        expect(jQuery('#matchAlertResponse').html()).toBe('Array format success');
        expect(jQuery('#home_points').val()).toBe('10');
        expect(jQuery('#away_points').val()).toBe('5');
        expect(jQuery('#match-status-away').hasClass('winner')).toBe(true);
        expect(jQuery('#set_2_p1').val()).toBe('7');
    });

    test('handles error response from server and silences console output', () => {
        const spyError = jest.spyOn(console, 'error').mockImplementation(() => {});
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        document.body.innerHTML = `
            <div id="matchAlert"><div id="matchAlertResponse"></div></div>
            <form id="matchForm">
                <button id="btnSubmit" type="button"></button>
            </form>
        `;
        const btn = document.getElementById('btnSubmit');
        updateMatchResults(btn);

        const ajaxOptions = spyAjax.mock.calls[0][0];
        ajaxOptions.error({
            responseJSON: { data: { message: 'Invalid set score detected' } }
        });

        expect(jQuery('#matchAlertResponse').text()).toContain('Invalid set score detected');
        spyError.mockRestore();
    });

    test('initializeUpdateMatchResults handles data-action and fallback id click', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        document.body.innerHTML = `
            <form id="formA">
                <input name="security" value="nonce-123" />
                <button id="btnA" data-action="update-match-results"></button>
            </form>
            <form id="formB">
                <input name="security" value="nonce-123" />
                <button id="updateMatchResults"></button>
            </form>
        `;

        initializeUpdateMatchResults();

        document.getElementById('btnA').click();
        expect(spyAjax).toHaveBeenCalledTimes(1);
        expect(spyAjax.mock.calls[0][0].data).toContain('security=nonce-123');

        document.getElementById('updateMatchResults').click();
        expect(spyAjax).toHaveBeenCalledTimes(2);
        expect(spyAjax.mock.calls[1][0].data).toContain('security=nonce-123');
    });
});
