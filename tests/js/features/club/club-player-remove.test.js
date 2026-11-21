/**
 * @jest-environment jsdom
 */

import { clubPlayerRemove, initializeClubPlayerRemove } from '../../../../src/js/features/club/club-player-remove.js';

describe('features/club/club-player-remove', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'nonce-rm' };
        window.jQuery = window.$ = require('jquery');
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    describe('clubPlayerRemove', () => {
        test('returns early if form selector matches no elements', () => {
            const spyAjax = jest.spyOn(jQuery, 'ajax');
            clubPlayerRemove('#nonExistentForm', 'M');
            expect(spyAjax).not.toHaveBeenCalled();
            spyAjax.mockRestore();
        });

        test('submits AJAX request, updates alerts and removes checked player rows on success', () => {
            document.body.innerHTML = `
                <div id="playerDelMResponse" style="display: none;"></div>
                <div id="playerDelMResponseText"></div>
                <form id="playerDelMForm">
                    <input type="checkbox" class="checkbox" value="101" checked />
                    <input type="checkbox" class="checkbox" value="102" />
                    <input type="checkbox" class="checkbox" value="103" checked />
                </form>
                <div id="club_player-101">Player 101</div>
                <div id="club_player-102">Player 102</div>
                <div id="club_player-103">Player 103</div>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    data: {
                        status: 'success',
                        msg: 'Players removed successfully'
                    }
                });
                opts.complete();
            });

            clubPlayerRemove('#playerDelMForm', 'M');

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                url: '/wp-admin/admin-ajax.php',
                type: 'POST',
                async: false,
                data: expect.stringContaining('action=racketmanager_club_players_remove')
            }));

            const alertField = document.getElementById('playerDelMResponse');
            const alertText = document.getElementById('playerDelMResponseText');

            expect(alertField.className).toContain('alert--success');
            expect(alertText.textContent).toBe('Players removed successfully');
            expect(alertField.style.display).not.toBe('none');

            // 101 and 103 should be removed, 102 should remain
            expect(document.getElementById('club_player-101')).toBeNull();
            expect(document.getElementById('club_player-102')).not.toBeNull();
            expect(document.getElementById('club_player-103')).toBeNull();

            spyAjax.mockRestore();
        });

        test('handles string response message', () => {
            document.body.innerHTML = `
                <div id="playerDelFResponse" style="display: none;"></div>
                <div id="playerDelFResponseText"></div>
                <form id="playerDelFForm"></form>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    data: 'All selected players removed'
                });
                opts.complete();
            });

            clubPlayerRemove('#playerDelFForm', 'F');

            expect(document.getElementById('playerDelFResponseText').textContent).toBe('All selected players removed');
            expect(document.getElementById('playerDelFResponse').className).toContain('alert--success');
            spyAjax.mockRestore();
        });

        test('handles AJAX error response', () => {
            document.body.innerHTML = `
                <div id="playerDelMResponse" style="display: none;"></div>
                <div id="playerDelMResponseText"></div>
                <form id="playerDelMForm"></form>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.error({ responseJSON: { data: { message: 'Failed to remove players' } } });
                opts.complete();
            });

            clubPlayerRemove('#playerDelMForm', 'M');

            expect(document.getElementById('playerDelMResponseText').textContent).toContain('Failed to remove players');
            expect(document.getElementById('playerDelMResponse').style.display).not.toBe('none');
            spyAjax.mockRestore();
        });
    });

    describe('initializeClubPlayerRemove', () => {
        test('triggers removal with data-form-id when clicked', () => {
            document.body.innerHTML = `
                <form id="playerDelMForm"></form>
                <button type="button"
                    data-action="club-player-remove"
                    data-form-id="playerDelMForm"
                    data-gender="M">Remove Players</button>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeClubPlayerRemove();
            const btn = document.querySelector('[data-action="club-player-remove"]');
            btn.click();

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_club_players_remove')
            }));

            spyAjax.mockRestore();
        });

        test('infers form selector from this.form.id when data-form-id is omitted', () => {
            document.body.innerHTML = `
                <form id="inferredForm">
                    <button type="button" id="clubPlayerRemoveSubmit" data-gender="F">Remove</button>
                </form>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeClubPlayerRemove();
            const btn = document.getElementById('clubPlayerRemoveSubmit');
            btn.click();

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_club_players_remove')
            }));

            spyAjax.mockRestore();
        });

        test('returns early if button has onclick attribute', () => {
            document.body.innerHTML = `
                <button type="button"
                    data-action="club-player-remove"
                    data-form-id="playerDelMForm"
                    data-gender="M"
                    onclick="return false;">Remove</button>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeClubPlayerRemove();
            const btn = document.querySelector('[data-action="club-player-remove"]');
            btn.click();

            expect(spyAjax).not.toHaveBeenCalled();
            spyAjax.mockRestore();
        });

        test('returns early if form selector cannot be resolved', () => {
            document.body.innerHTML = `
                <button type="button"
                    data-action="club-player-remove"
                    data-gender="M">Remove</button>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeClubPlayerRemove();
            const btn = document.querySelector('[data-action="club-player-remove"]');
            btn.click();

            expect(spyAjax).not.toHaveBeenCalled();
            spyAjax.mockRestore();
        });

        test('returns early if gender is missing', () => {
            document.body.innerHTML = `
                <button type="button"
                    data-action="club-player-remove"
                    data-form-id="playerDelMForm">Remove</button>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeClubPlayerRemove();
            const btn = document.querySelector('[data-action="club-player-remove"]');
            btn.click();

            expect(spyAjax).not.toHaveBeenCalled();
            spyAjax.mockRestore();
        });
    });
});
