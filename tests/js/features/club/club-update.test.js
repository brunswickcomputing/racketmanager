/**
 * @jest-environment jsdom
 */

import { updateClub, initializeClubUpdate } from '../../../../src/js/features/club/club-update.js';

describe('features/club/club-update', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'nonce-1' };
        window.jQuery = window.$ = require('jquery');
    });

    test('exits early if link or form is invalid', () => {
        const spyError = jest.spyOn(console, 'error').mockImplementation(() => {});
        const spyAjax = jest.spyOn(jQuery, 'ajax');

        const result1 = updateClub(null, null);
        const result2 = updateClub(null, {});
        expect(result1).toBe(false);
        expect(result2).toBe(false);
        expect(spyAjax).not.toHaveBeenCalled();

        spyError.mockRestore();
    });

    test('serializes form, calls AJAX, and updates status alert', () => {
        document.body.innerHTML = `
            <button id="updateClubSubmit"></button>
            <div id="clubUpdateResponse" style="display: none;"></div>
            <div id="clubUpdateResponseText"></div>
            <form id="clubForm">
                <input name="club_name" value="Leigh LTC" />
                <button id="submitBtn" type="button"></button>
            </form>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.success({
                data: {
                    status: 'success',
                    msg: 'Club updated'
                }
            });
            opts.complete();
        });

        const btn = document.getElementById('submitBtn');
        const fakeEvt = { preventDefault: jest.fn() };
        updateClub(fakeEvt, btn);

        expect(fakeEvt.preventDefault).toHaveBeenCalled();
        expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
            url: '/wp-admin/admin-ajax.php',
            data: expect.stringContaining('action=racketmanager_update_club')
        }));

        expect(document.getElementById('clubUpdateResponse').className).toContain('alert--success');
        expect(document.getElementById('clubUpdateResponseText').textContent).toBe('Club updated');
        expect(document.getElementById('clubUpdateResponse').style.display).not.toBe('none');

        spyAjax.mockRestore();
    });

    test('handles AJAX error callback', () => {
        document.body.innerHTML = `
            <button id="updateClubSubmit"></button>
            <div id="clubUpdateResponse" style="display: none;"></div>
            <div id="clubUpdateResponseText"></div>
            <form id="clubForm">
                <button id="submitBtn" type="button"></button>
            </form>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.error({ responseJSON: { data: { message: 'Failed to update club' } } });
            opts.complete();
        });

        const btn = document.getElementById('submitBtn');
        updateClub(null, btn);

        expect(document.getElementById('clubUpdateResponseText').textContent).toContain('Failed to update club');
        expect(document.getElementById('clubUpdateResponse').style.display).not.toBe('none');

        spyAjax.mockRestore();
    });

    test('initializeClubUpdate binds click on #updateClubSubmit', () => {
        document.body.innerHTML = `
            <form id="myClubForm">
                <button id="updateClubSubmit"></button>
            </form>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

        initializeClubUpdate();
        document.getElementById('updateClubSubmit').click();

        expect(spyAjax).toHaveBeenCalled();
        spyAjax.mockRestore();
    });
});
