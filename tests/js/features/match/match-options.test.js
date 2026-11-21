/**
 * @jest-environment jsdom
 */

import { initializeMatchOptions } from '../../../../src/js/features/match/match-options.js';
import * as ajaxErrorModule from '../../../../src/js/features/ajax/handle-ajax-error.js';

describe('features/match/match-options', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="matchModal"></div>
            <div id="headerResponse" style="display:none;"></div>
            <div id="headerResponseResponse"></div>
        `;
        window.jQuery = window.$ = require('jquery');
        window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'nonce-123' };
        jest.clearAllMocks();
    });

    afterEach(() => {
        jQuery(document).off('click.racketmanager.matchOptions');
        jest.restoreAllMocks();
    });

    describe('initializeMatchOptions', () => {
        test('reads matchId and matchOption from dataset when clicked', () => {
            const spyLoad = jest.spyOn(jQuery.fn, 'load').mockImplementation(function () { return this; });
            document.body.innerHTML += `
                <a href="#" class="matchOptionLink" data-match-id="456" data-match-option="schedule">Schedule</a>
            `;

            initializeMatchOptions();
            const link = document.querySelector('.matchOptionLink');
            link.click();

            expect(spyLoad).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    match_id: '456',
                    option: 'schedule',
                    action: 'racketmanager_match_option',
                }),
                expect.any(Function)
            );
            spyLoad.mockRestore();
        });

        test('falls back to jQuery.data when dataset attributes are missing', () => {
            const spyLoad = jest.spyOn(jQuery.fn, 'load').mockImplementation(function () { return this; });
            const link = document.createElement('a');
            link.className = 'matchOptionLink';
            jQuery(link).data('matchId', '789');
            jQuery(link).data('matchOption', 'reset');
            document.body.appendChild(link);

            initializeMatchOptions();
            link.click();

            expect(spyLoad).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    match_id: '789',
                    option: 'reset',
                }),
                expect.any(Function)
            );
            spyLoad.mockRestore();
        });

        test('does not initiate request if matchId or matchOption is missing', () => {
            const spyLoad = jest.spyOn(jQuery.fn, 'load').mockImplementation(function () { return this; });
            const link = document.createElement('a');
            link.className = 'matchOptionLink';
            document.body.appendChild(link);

            initializeMatchOptions();
            link.click();

            expect(spyLoad).not.toHaveBeenCalled();
            spyLoad.mockRestore();
        });

        test('handles error status with valid JSON message', () => {
            let callback;
            jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, data, cb) {
                callback = cb;
                return this;
            });
            document.body.innerHTML += `
                <a href="#" class="matchOptionLink" data-match-id="100" data-match-option="schedule">Schedule</a>
            `;

            initializeMatchOptions();
            document.querySelector('.matchOptionLink').click();

            callback(JSON.stringify({ message: 'Match is already locked' }), 'error', {});

            expect(jQuery('#headerResponseResponse').html()).toBe('Match is already locked');
            expect(jQuery('#headerResponse').css('display')).not.toBe('none');
        });

        test('handles error status with invalid JSON by delegating to handleAjaxError', () => {
            let callback;
            jest.spyOn(jQuery.fn, 'load').mockImplementation(function (url, data, cb) {
                callback = cb;
                return this;
            });
            const handleAjaxErrorSpy = jest.spyOn(ajaxErrorModule, 'handleAjaxError').mockImplementation(() => {});

            document.body.innerHTML += `
                <a href="#" class="matchOptionLink" data-match-id="100" data-match-option="schedule">Schedule</a>
            `;

            initializeMatchOptions();
            document.querySelector('.matchOptionLink').click();

            const mockXhr = { status: 500, responseText: 'Fatal Server Error' };
            callback('<html>Not JSON</html>', 'error', mockXhr);

            expect(handleAjaxErrorSpy).toHaveBeenCalledWith(mockXhr, '#headerResponseResponse', '#headerResponse');
            expect(jQuery('#headerResponse').css('display')).not.toBe('none');
        });
    });
});
