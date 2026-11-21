/**
 * @jest-environment jsdom
 */

import { initializeRubberStatusModal } from '../../../../src/js/features/match/rubber-status-modal.js';

describe('features/match/rubber-status-modal', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="scoreStatusModal"></div>
            <div id="matchAlert" style="display:none;"></div>
            <div id="matchAlertResponse"></div>
            <input id="match_status_2" value="completed" />
        `;
        window.jQuery = window.$ = require('jquery');
        window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'nonce-rm' };
        jest.clearAllMocks();
    });

    afterEach(() => {
        jQuery(document).off('click.racketmanager.rubberStatusOpen');
        jest.restoreAllMocks();
    });

    describe('initializeRubberStatusModal', () => {
        test('reads rubberId and rubberNumber from trigger.dataset when clicked', () => {
            const spyLoad = jest.spyOn(jQuery.fn, 'load').mockImplementation(function () { return this; });
            document.body.innerHTML += `
                <button type="button" data-action="open-rubber-status-modal" data-rubber-id="101" data-rubber-number="2">Open</button>
            `;

            initializeRubberStatusModal();
            const btn = document.querySelector('[data-action="open-rubber-status-modal"]');
            btn.click();

            expect(spyLoad).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    rubber_id: '101',
                    score_status: 'completed',
                    modal: 'scoreStatusModal',
                    action: 'racketmanager_match_rubber_status',
                }),
                expect.any(Function)
            );
            spyLoad.mockRestore();
        });

        test('handles trigger when dataset properties are absent', () => {
            const spyLoad = jest.spyOn(jQuery.fn, 'load').mockImplementation(function () { return this; });
            const btn = document.createElement('button');
            btn.setAttribute('data-action', 'open-rubber-status-modal');
            document.body.appendChild(btn);

            initializeRubberStatusModal();
            btn.click();

            expect(spyLoad).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    rubber_id: undefined,
                    modal: 'scoreStatusModal',
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
                <button type="button" data-action="open-rubber-status-modal" data-rubber-id="101" data-rubber-number="2">Open</button>
            `;

            initializeRubberStatusModal();
            document.querySelector('[data-action="open-rubber-status-modal"]').click();

            callback(JSON.stringify({ message: 'Rubber status locked' }), 'error');

            expect(jQuery('#matchAlertResponse').html()).toBe('Rubber status locked');
            expect(jQuery('#matchAlert').css('display')).not.toBe('none');
        });

        test('handles error status with invalid JSON by falling back to default message', () => {
            let callback;
            jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, data, cb) {
                callback = cb;
                return this;
            });
            document.body.innerHTML += `
                <button type="button" data-action="open-rubber-status-modal" data-rubber-id="101" data-rubber-number="2">Open</button>
            `;

            initializeRubberStatusModal();
            document.querySelector('[data-action="open-rubber-status-modal"]').click();

            callback('<html>Server Error</html>', 'error');

            expect(jQuery('#matchAlertResponse').html()).toBe('An error occurred');
            expect(jQuery('#matchAlert').css('display')).not.toBe('none');
        });
    });
});
