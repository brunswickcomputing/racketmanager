/**
 * @jest-environment jsdom
 */

import { initializePOModal } from '../../../../src/js/features/modals/po-modal.js';

describe('features/modals/po-modal', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="POModal"></div>
            <div id="POUpdateResponse" style="display:none;"></div>
            <div id="POUpdateResponseText"></div>
            <button id="POModalLink" data-invoice-id="INV-999">View PO</button>
        `;
        window.jQuery = window.$ = require('jquery');
        window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'nonce-rm' };
        jest.clearAllMocks();
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    describe('initializePOModal', () => {
        test('reads invoiceId from this.dataset.invoiceId when trigger is clicked', () => {
            const spyLoad = jest.spyOn(jQuery.fn, 'load').mockImplementation(function () { return this; });

            initializePOModal();
            const trigger = document.getElementById('POModalLink');
            trigger.click();

            expect(spyLoad).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    invoiceId: 'INV-999',
                    modal: 'POModal',
                    action: 'racketmanager_purchase_order_modal',
                }),
                expect.any(Function)
            );
            spyLoad.mockRestore();
        });

        test('handles error status with valid JSON message', () => {
            let callback;
            jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, data, cb) {
                callback = cb;
                return this;
            });

            initializePOModal();
            document.getElementById('POModalLink').click();

            callback(JSON.stringify({ message: 'Purchase order not found' }), 'error');

            expect(jQuery('#POUpdateResponseText').html()).toBe('Purchase order not found');
            expect(jQuery('#POUpdateResponse').css('display')).not.toBe('none');
        });

        test('handles error status with invalid JSON by falling back to default message', () => {
            let callback;
            jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, data, cb) {
                callback = cb;
                return this;
            });

            initializePOModal();
            document.getElementById('POModalLink').click();

            callback('<html>Server Error</html>', 'error');

            expect(jQuery('#POUpdateResponseText').html()).toBe('Error loading modal');
            expect(jQuery('#POUpdateResponse').css('display')).not.toBe('none');
        });
    });
});
