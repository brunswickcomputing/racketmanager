/**
 * @jest-environment jsdom
 */

import { showTeamOrderPlayers, validateTeamOrder, initializeTeamOrder } from '../../../../src/js/features/teams/team-order.js';

describe('features/teams/team-order', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: 'test-nonce-team'
        };
        window.jQuery = window.$ = require('jquery');
    });

    describe('showTeamOrderPlayers', () => {
        test('does nothing if clubId or eventId is missing', () => {
            document.body.innerHTML = `
                <select id="club_id"><option value="">Select Club</option></select>
                <select id="event_id"><option value="5">Event 5</option></select>
                <div id="team-order-rubbers"></div>
            `;
            const spyLoad = jest.fn();
            jQuery.fn.load = spyLoad;

            showTeamOrderPlayers();
            expect(spyLoad).not.toHaveBeenCalled();
        });

        test('loads team order rubbers when both club and event are selected', () => {
            document.body.innerHTML = `
                <select id="club_id"><option value="12" selected>Club 12</option></select>
                <select id="event_id"><option value="5" selected>Event 5</option></select>
                <div id="team-order-details"></div>
                <div id="team-order-rubbers" style="display: none;"></div>
                <div id="teamOrderAlert"><div id="teamOrderAlertResponse"></div></div>
            `;

            let capturedCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                capturedCallback = callback;
                return this;
            });

            showTeamOrderPlayers();

            expect(jQuery.fn.load).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    action: 'racketmanager_show_team_order_players',
                    clubId: '12',
                    eventId: '5',
                    security: 'test-nonce-team'
                }),
                expect.any(Function)
            );

            // Execute completion with error
            capturedCallback('', 'error', { status: 500, statusText: 'Internal Error' });
            expect(document.getElementById('teamOrderAlert').style.display).not.toBe('none');
        });
    });

    describe('validateTeamOrder', () => {
        test('returns early if link or form is invalid', () => {
            const spyAjax = jest.spyOn(jQuery, 'ajax');
            validateTeamOrder(null);
            validateTeamOrder({});
            expect(spyAjax).not.toHaveBeenCalled();
        });

        test('serializes form, calls AJAX and renders rubbers with success alert', () => {
            document.body.innerHTML = `
                <div id="team-order-details"></div>
                <div id="team-order-rubbers"></div>
                <div id="teamOrderAlert"><div id="teamOrderAlertResponse"></div></div>
                <input id="match-status-1" />
                <input id="wtn_1" />
                <form id="teamOrderForm">
                    <input name="test" value="1" />
                    <button id="valBtn" type="button"></button>
                </form>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    data: {
                        valid: true,
                        msg: 'Team order is valid',
                        rubbers: {
                            '1': {
                                status: 'Valid Order',
                                status_class: 'order-valid',
                                wtn: '12.5'
                            }
                        }
                    }
                });
                opts.complete();
            });

            const btn = document.getElementById('valBtn');
            validateTeamOrder(btn, '1');

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_validate_team_order')
            }));

            expect(document.getElementById('match-status-1').value).toBe('Valid Order');
            expect(document.getElementById('match-status-1').className).toContain('order-valid');
            expect(document.getElementById('wtn_1').value).toBe('12.5');
            expect(document.getElementById('teamOrderAlertResponse').textContent).toBe('Team order is valid');
            expect(document.getElementById('teamOrderAlert').className).toContain('alert--success');

            spyAjax.mockRestore();
        });
    });

    describe('initializeTeamOrder', () => {
        test('attaches event listeners to club/event select and buttons', () => {
            document.body.innerHTML = `
                <select id="club_id"><option value="1" selected>Club 1</option></select>
                <select id="event_id"><option value="2" selected>Event 2</option></select>
                <div id="team-order-details"></div>
                <div id="team-order-rubbers"></div>
                <button id="validateTeamButton" type="button" form="teamForm"></button>
                <form id="teamForm"></form>
            `;

            const spyLoad = jest.fn();
            jQuery.fn.load = spyLoad;
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeTeamOrder();

            // Trigger change
            jQuery('#club_id').trigger('change');
            expect(spyLoad).toHaveBeenCalled();

            // Trigger click
            document.getElementById('validateTeamButton').click();
            expect(spyAjax).toHaveBeenCalled();

            spyAjax.mockRestore();
        });
    });
});
