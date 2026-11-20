/**
 * @jest-environment jsdom
 */

import { updateTeamResult, initializeTeamMatchResult } from '../../../../src/js/features/match/update-team-result.js';
import * as matchHeaderModule from '../../../../src/js/features/match/match-header.js';

describe('features/match/update-team-result', () => {
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

    test('exits early if link or link.form is missing or link is busy', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        updateTeamResult(null);
        updateTeamResult({});
        updateTeamResult({ form: {} });
        expect(spyAjax).not.toHaveBeenCalled();

        // Busy guard
        const link = document.createElement('button');
        const form = document.createElement('form');
        form.id = 'test-form';
        form.appendChild(link);
        document.body.appendChild(form);

        jQuery(link).data('busy', true);
        updateTeamResult(link);
        expect(spyAjax).not.toHaveBeenCalled();
    });

    test('serializes form, passes security nonce, calls AJAX and renders rubbers, players, sets, warnings and errors', () => {
        const spyHeader = jest.spyOn(matchHeaderModule, 'matchHeader').mockImplementation(() => {});
        const spyAjax = jest.spyOn(jQuery, 'ajax');

        document.body.innerHTML = `
            <div id="matchAlert">
                <div id="matchAlertResponse"></div>
            </div>
            <div id="splash" class="d-none"></div>
            <div id="showMatchRubbers"></div>
            <input id="current_match_id" value="456" />
            <div id="matchStatusButton"></div>

            <!-- Rubbers fields -->
            <input id="match-status-1-home" />
            <input id="homeplayer1_1" />
            <input id="players_1_home_1" />
            <input id="set_1_home_1" />

            <!-- Away winner rubber fields -->
            <input id="match-status-2-away" />
            <input id="awayplayer1_2" />
            <input id="players_2_away_1" />
            <input id="set_2_away_1" />

            <!-- Warnings and feedback -->
            <input id="warnPlayer1" />
            <div id="warnPlayer1Feedback"></div>

            <!-- Field errors and feedback -->
            <input id="errField1" />
            <div id="errField1Feedback"></div>

            <form id="rubberForm">
                <input name="security" value="nonce-123" />
                <input name="test_field" value="test_val" />
                <button id="submitBtn" type="button"></button>
            </form>
        `;

        const btn = document.getElementById('submitBtn');
        updateTeamResult(btn);

        expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
            url: '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: expect.stringContaining('action=racketmanager_update_rubbers')
        }));
        expect(spyAjax.mock.calls[0][0].data).toContain('security=nonce-123');

        const ajaxOptions = spyAjax.mock.calls[0][0];

        // Trigger success callback with both home and away winner paths
        const mockResponse = {
            data: {
                msg: 'Rubbers updated successfully',
                status: 'success',
                rubbers: {
                    '1': {
                        winner: 'home',
                        players: {
                            home: ['John Doe']
                        },
                        sets: {
                            home: { '1': 6 }
                        }
                    },
                    '2': {
                        winner: 'away',
                        players: {
                            away: ['Jane Smith']
                        },
                        sets: {
                            away: { '1': 6 }
                        }
                    }
                },
                warnings: {
                    warnPlayer1: 'Check player eligibility'
                },
                err_flds: ['errField1'],
                err_msgs: ['Invalid score']
            }
        };

        ajaxOptions.success(mockResponse);

        expect(jQuery('#matchAlert').hasClass('alert--success')).toBe(true);
        expect(jQuery('#matchAlertResponse').html()).toBe('Rubbers updated successfully');
        expect(jQuery('#match-status-1-home').hasClass('winner')).toBe(true);
        expect(jQuery('#match-status-1-home').val()).toBe('W');
        expect(jQuery('#homeplayer1_1').val()).toBe('John Doe');
        expect(jQuery('#players_1_home_1').val()).toBe('John Doe');
        expect(jQuery('#set_1_home_1').val()).toBe('6');

        expect(jQuery('#match-status-2-away').hasClass('winner')).toBe(true);
        expect(jQuery('#match-status-2-away').val()).toBe('W');
        expect(jQuery('#awayplayer1_2').val()).toBe('Jane Smith');
        expect(jQuery('#players_2_away_1').val()).toBe('Jane Smith');
        expect(jQuery('#set_2_away_1').val()).toBe('6');

        // Warnings
        expect(jQuery('#warnPlayer1').hasClass('is-invalid')).toBe(true);
        expect(jQuery('#warnPlayer1Feedback').html()).toBe('Check player eligibility');

        // Field errors
        expect(jQuery('#errField1').hasClass('is-invalid')).toBe(true);
        expect(jQuery('#errField1Feedback').html()).toBe('Invalid score');

        // Match header called
        expect(spyHeader).toHaveBeenCalledWith('456', true);

        // Complete callback
        ajaxOptions.complete();
        expect(jQuery('#matchAlert').css('display')).not.toBe('none');
        expect(jQuery('#showMatchRubbers').css('display')).not.toBe('none');
        expect(jQuery(btn).data('busy')).toBe(false);
        expect(jQuery(btn).prop('disabled')).toBe(false);

        spyHeader.mockRestore();
    });

    test('handles AJAX error callback and silences console output', () => {
        const spyError = jest.spyOn(console, 'error').mockImplementation(() => {});
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        document.body.innerHTML = `
            <div id="matchAlert"><div id="matchAlertResponse"></div></div>
            <form id="rubberForm">
                <input name="security" value="nonce-123" />
                <button id="submitBtn" type="button"></button>
            </form>
        `;
        const btn = document.getElementById('submitBtn');
        updateTeamResult(btn);

        const ajaxOptions = spyAjax.mock.calls[0][0];
        ajaxOptions.error({
            responseJSON: { data: { message: 'Server failed to update rubber' } }
        });

        expect(jQuery('#matchAlertResponse').text()).toContain('Server failed to update rubber');
        spyError.mockRestore();
    });

    test('initializeTeamMatchResult binds click on [data-action="update-team-result"]', () => {
        document.body.innerHTML = `
            <form id="actionForm">
                <input name="security" value="nonce-123" />
                <button id="actionBtn" data-action="update-team-result"></button>
            </form>
        `;
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        initializeTeamMatchResult();

        const btn = document.getElementById('actionBtn');
        btn.click();

        expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
            data: expect.stringContaining('action=racketmanager_update_rubbers')
        }));
        expect(spyAjax.mock.calls[0][0].data).toContain('security=nonce-123');
    });
});
