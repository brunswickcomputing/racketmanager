/**
 * @jest-environment jsdom
 */

import { initializeMatchStatusModal } from '../../../../src/js/features/modals/match-status-modal.js';

describe('features/modals/match-status-modal', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="scoreStatusModal"></div>
            <div id="headerResponse" style="display:none;"></div>
            <div id="headerResponseResponse"></div>
            <input id="match_status" value="in_progress" />
        `;
        window.jQuery = window.$ = require('jquery');
        window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'nonce-123' };
        jest.clearAllMocks();
    });

    afterEach(() => {
        jQuery(document).off('click.racketmanager.matchStatus');
        jest.restoreAllMocks();
    });

    describe('initializeMatchStatusModal', () => {
        test('reads matchId from dataset.matchId when clicked', () => {
            const spyLoad = jest.spyOn(jQuery.fn, 'load').mockImplementation(function () { return this; });
            document.body.innerHTML += `
                <button type="button" data-action="open-match-status-modal" data-match-id="555">Status</button>
            `;

            initializeMatchStatusModal();
            const btn = document.querySelector('[data-action="open-match-status-modal"]');
            btn.click();

            expect(spyLoad).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    match_id: '555',
                    match_status: 'in_progress',
                    modal: 'scoreStatusModal',
                    action: 'racketmanager_match_status',
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
            document.body.innerHTML += `
                <button type="button" data-action="open-match-status-modal" data-match-id="555">Status</button>
            `;

            initializeMatchStatusModal();
            document.querySelector('[data-action="open-match-status-modal"]').click();

            callback({ data: JSON.stringify({ message: 'Invalid match status' }) }, 'error');

            expect(jQuery('#headerResponseResponse').html()).toBe('Invalid match status');
            expect(jQuery('#headerResponse').css('display')).not.toBe('none');
        });

        test('handles error status with invalid JSON by falling back to default message', () => {
            let callback;
            jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, data, cb) {
                callback = cb;
                return this;
            });
            document.body.innerHTML += `
                <button type="button" data-action="open-match-status-modal" data-match-id="555">Status</button>
            `;

            initializeMatchStatusModal();
            document.querySelector('[data-action="open-match-status-modal"]').click();

            callback({ data: '<html>Server Error</html>' }, 'error');

            expect(jQuery('#headerResponseResponse').html()).toBe('An error occurred');
            expect(jQuery('#headerResponse').css('display')).not.toBe('none');
        });
    });
});
