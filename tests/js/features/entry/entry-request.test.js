/**
 * @jest-environment jsdom
 */

import { initializeEntryRequest } from '../../../../src/js/features/entry/entry-request.js';

describe('features/entry/entry-request', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="entry-details"></div>
            <div id="entryAlert" style="display:none;"></div>
            <div id="entryAlertResponse"></div>
            <button id="entrySubmit">Submit</button>
            <form id="form-entry">
                <input type="hidden" name="player_id" value="123" />
            </form>
        `;
        window.jQuery = window.$ = require('jquery');
        window.ajax_var = { url: '/wp-admin/admin-ajax.php' };
        jest.clearAllMocks();
    });

    afterEach(() => {
        jQuery(document).off('click.racketmanager.entryRequest');
        jest.restoreAllMocks();
    });

    describe('initializeEntryRequest', () => {
        test('extracts type from dataset when clicked', () => {
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});
            document.body.innerHTML += `
                <button type="button" data-action="entry-submit" data-type="tournament">Enter</button>
            `;

            initializeEntryRequest();
            const btn = document.querySelector('[data-action="entry-submit"]');
            btn.click();

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_tournament_entry'),
            }));
            spyAjax.mockRestore();
        });

        test('falls back to jQuery.data("type") if dataset.type is absent', () => {
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});
            const btn = document.createElement('button');
            btn.setAttribute('data-action', 'entry-submit');
            jQuery(btn).data('type', 'league');
            document.body.appendChild(btn);

            initializeEntryRequest();
            btn.click();

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_league_entry'),
            }));
            spyAjax.mockRestore();
        });

        test('returns early if type is missing', () => {
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});
            const btn = document.createElement('button');
            btn.setAttribute('data-action', 'entry-submit');
            document.body.appendChild(btn);

            initializeEntryRequest();
            btn.click();

            expect(spyAjax).not.toHaveBeenCalled();
            spyAjax.mockRestore();
        });
    });
});
