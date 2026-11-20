/**
 * @jest-environment jsdom
 */

import { openPartnerModal, savePartner, initializePartnerModal } from '../../../../src/js/features/modals/partner-modal.js';
import * as pricingModule from '../../../../src/js/features/pricing/pricing.js';

describe('features/modals/partner-modal', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: 'nonce-modal-test'
        };
        window.jQuery = window.$ = require('jquery');
    });

    describe('openPartnerModal', () => {
        test('does nothing if eventId is not provided', () => {
            const spyLoad = jest.fn();
            jQuery.fn.load = spyLoad;
            openPartnerModal(null, null);
            expect(spyLoad).not.toHaveBeenCalled();
        });

        test('loads modal HTML via jQuery.fn.load and calls modal show', () => {
            document.body.innerHTML = `
                <div id="liEventDetails"></div>
                <input type="checkbox" id="event-10" />
                <input id="playerId" value="player-1" />
                <input id="playerGender" value="M" />
                <input id="season" value="2024" />
                <input id="tournamentDateEnd" value="2024-08-31" />
                <input id="tournamentId" value="tourn-99" />
                <input id="partnerId-10" value="partner-4" />
                <div id="partnerModal"></div>
                <div id="partnerResponse"><div id="partnerResponseText"></div></div>
            `;

            let capturedCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                capturedCallback = callback;
                return this;
            });
            jQuery.fn.modal = jest.fn();

            const fakeEvt = { preventDefault: jest.fn() };
            openPartnerModal(fakeEvt, 10);

            expect(fakeEvt.preventDefault).toHaveBeenCalled();
            expect(document.getElementById('event-10').checked).toBe(true);
            expect(jQuery.fn.load).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    action: 'racketmanager_team_partner',
                    eventId: 10,
                    playerId: 'player-1',
                    security: 'nonce-modal-test'
                }),
                expect.any(Function)
            );

            // Execute success
            capturedCallback('<div>Modal Content</div>', 'success', {});
            expect(jQuery.fn.modal).toHaveBeenCalledWith('show');
            expect(document.getElementById('partnerModal').getAttribute('data-event')).toBe('10');

            // Test error callback
            capturedCallback('', 'error', { status: 500, statusText: 'Server Error' });
            expect(document.getElementById('partnerResponse').style.display).not.toBe('none');
        });
    });

    describe('savePartner', () => {
        test('exits early if link or form is invalid', () => {
            const spyAjax = jest.spyOn(jQuery, 'ajax');
            savePartner(null);
            savePartner({});
            expect(spyAjax).not.toHaveBeenCalled();
        });

        test('serializes form, calls AJAX and updates partner ID and Name and event price', () => {
            const spyPrice = jest.spyOn(pricingModule, 'setEventPrice').mockImplementation(() => {});
            document.body.innerHTML = `
                <div id="partnerModal"></div>
                <div id="partnerResponse"><div id="partnerResponseText"></div></div>
                <input id="partnerId-10" value="" />
                <div id="partnerName-10"></div>
                <input type="checkbox" id="event-10" />
                <form id="partnerForm">
                    <input name="test_partner" value="val" />
                    <button id="saveBtn" type="button"></button>
                </form>
            `;

            jQuery.fn.modal = jest.fn();
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    data: ['partnerModal', 'player-88', 'Jane Smith', 10]
                });
            });

            const btn = document.getElementById('saveBtn');
            savePartner(btn);

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_validate_partner')
            }));

            expect(document.getElementById('partnerId-10').value).toBe('player-88');
            expect(document.getElementById('partnerName-10').textContent).toBe('Jane Smith');
            expect(document.getElementById('event-10').checked).toBe(true);
            expect(jQuery.fn.modal).toHaveBeenCalledWith('hide');
            expect(spyPrice).toHaveBeenCalledWith(10);

            spyPrice.mockRestore();
            spyAjax.mockRestore();
        });
    });

    describe('initializePartnerModal', () => {
        test('attaches event listeners to open modal and set partner buttons', () => {
            document.body.innerHTML = `
                <button id="openBtn" data-action="open-partner-modal" data-event-id="15"></button>
                <form id="savePartnerForm">
                    <button id="setBtn" data-action="set-partner"></button>
                </form>
            `;

            jQuery.fn.load = jest.fn();
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializePartnerModal();

            document.getElementById('openBtn').click();
            expect(jQuery.fn.load).toHaveBeenCalled();

            document.getElementById('setBtn').click();
            expect(spyAjax).toHaveBeenCalled();

            spyAjax.mockRestore();
        });
    });
});
