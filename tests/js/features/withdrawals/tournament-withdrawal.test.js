/**
 * @jest-environment jsdom
 */

import { openWithdrawalModal, confirmTournamentWithdraw, initializeTournamentWithdrawal } from '../../../../src/js/features/withdrawals/tournament-withdrawal.js';
import * as pricingModule from '../../../../src/js/features/pricing/pricing.js';
import * as modalCheckboxes from '../../../../src/js/features/modals/has-modal-checkboxes.js';

describe('features/withdrawals/tournament-withdrawal', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: 'nonce-withdraw-123'
        };
        window.jQuery = window.$ = require('jquery');
    });

    describe('openWithdrawalModal', () => {
        test('loads withdrawal modal UI and opens modal', () => {
            document.body.innerHTML = `
                <div id="liEventDetails"></div>
                <input id="eventsEntered" value="1,2" />
                <input id="tournamentId" value="44" />
                <input id="playerId" value="101" />
                <div id="partnerModal"></div>
                <div id="withdrawResponse"><div id="withdrawResponseText"></div></div>
                <div id="entryAlert"><div id="entryAlertResponse"></div></div>
            `;

            let capturedCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                capturedCallback = callback;
                return this;
            });
            jQuery.fn.modal = jest.fn();

            const fakeEvt = { preventDefault: jest.fn() };
            openWithdrawalModal(fakeEvt);

            expect(fakeEvt.preventDefault).toHaveBeenCalled();
            expect(jQuery.fn.load).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    action: 'racketmanager_tournament_withdrawal',
                    tournamentId: '44',
                    playerId: '101',
                    eventsEntered: '1,2',
                    security: 'nonce-withdraw-123'
                }),
                expect.any(Function)
            );

            capturedCallback('<div>Withdrawal confirmation</div>', 'success', {});
            expect(jQuery.fn.modal).toHaveBeenCalledWith('show');

            // Error branch
            capturedCallback('', 'error', { status: 400, statusText: 'Bad Request' });
            expect(document.getElementById('entryAlert').style.display).not.toBe('none');
        });
    });

    describe('confirmTournamentWithdraw', () => {
        test('submits withdrawal, unchecks events, updates total price, and shows alert', () => {
            const spyPrice = jest.spyOn(pricingModule, 'updateTotalPrice').mockImplementation(() => {});
            const spyCheckToggle = jest.spyOn(modalCheckboxes, 'checkToggle').mockImplementation(() => {});

            document.body.innerHTML = `
                <input id="tournamentId" value="44" />
                <input id="playerId" value="101" />
                <input type="checkbox" class="form-check--event" checked />
                <div id="withdrawResponse"><div id="withdrawResponseText"></div></div>
                <div id="entryAlert"><div id="entryAlertResponse"></div></div>
                <div id="partnerModal"></div>
            `;

            jQuery.fn.modal = jest.fn();
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({ data: 'Successfully withdrawn' });
            });

            confirmTournamentWithdraw();

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.objectContaining({
                    action: 'racketmanager_confirm_tournament_withdrawal',
                    tournamentId: '44',
                    playerId: '101',
                    security: 'nonce-withdraw-123'
                })
            }));

            expect(document.querySelector('.form-check--event').checked).toBe(false);
            expect(spyCheckToggle).toHaveBeenCalled();
            expect(spyPrice).toHaveBeenCalled();
            expect(document.getElementById('entryAlert').className).toContain('alert--success');
            expect(document.getElementById('entryAlertResponse').textContent).toBe('Successfully withdrawn');
            expect(jQuery.fn.modal).toHaveBeenCalledWith('hide');

            spyPrice.mockRestore();
            spyCheckToggle.mockRestore();
            spyAjax.mockRestore();
        });
    });

    describe('initializeTournamentWithdrawal', () => {
        test('delegates clicks to open and confirm withdrawal modal', () => {
            document.body.innerHTML = `
                <button id="tournamentWithdraw"></button>
                <button data-action="confirm-tournament-withdrawal"></button>
            `;

            jQuery.fn.load = jest.fn();
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeTournamentWithdrawal();

            document.getElementById('tournamentWithdraw').click();
            expect(jQuery.fn.load).toHaveBeenCalled();

            document.querySelector('[data-action="confirm-tournament-withdrawal"]').click();
            expect(spyAjax).toHaveBeenCalled();

            spyAjax.mockRestore();
        });
    });
});
