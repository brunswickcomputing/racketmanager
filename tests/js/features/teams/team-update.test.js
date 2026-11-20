/**
 * @jest-environment jsdom
 */

import { updateTeam, initializeTeamUpdate } from '../../../../src/js/features/teams/team-update.js';

describe('features/teams/team-update', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = { url: '/wp-admin/admin-ajax.php', ajax_nonce: 'nonce-1' };
        window.jQuery = window.$ = require('jquery');
    });

    test('exits early if link or form is invalid', () => {
        const spyAjax = jest.spyOn(jQuery, 'ajax');
        updateTeam(null);
        updateTeam({});
        expect(spyAjax).not.toHaveBeenCalled();
    });

    test('serializes form, calls AJAX, and updates captain details on success', () => {
        document.body.innerHTML = `
            <div id="teamUpdateSubmit-1-2"></div>
            <div id="teamUpdateResponse-1-2" style="display: none;"></div>
            <div id="teamUpdateResponseText-1-2"></div>
            <input id="captain-1-2" value="Jane Doe" />
            <span id="captain-name-1-2"></span>
            <input id="contactno-1-2" value="07999888777" />
            <span id="captain-contact-no-1-2"></span>
            <input id="contactemail-1-2" value="jane@test.com" />
            <span id="captain-contact-email-1-2"></span>

            <form id="teamForm">
                <input id="event_id" value="1" />
                <input id="team_id" value="2" />
                <button id="updateBtn" type="button"></button>
            </form>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.success({ data: 'Team updated successfully' });
            opts.complete();
        });

        const btn = document.getElementById('updateBtn');
        updateTeam(btn);

        expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
            url: '/wp-admin/admin-ajax.php',
            data: expect.stringContaining('action=racketmanager_update_team')
        }));

        expect(document.getElementById('captain-name-1-2').textContent).toBe('Jane Doe');
        expect(document.getElementById('captain-contact-no-1-2').textContent).toBe('07999888777');
        expect(document.getElementById('captain-contact-email-1-2').textContent).toBe('jane@test.com');
        expect(document.getElementById('teamUpdateResponse-1-2').className).toContain('alert--success');
        expect(document.getElementById('teamUpdateResponseText-1-2').textContent).toBe('Team updated successfully');
        expect(document.getElementById('teamUpdateResponse-1-2').style.display).not.toBe('none');

        spyAjax.mockRestore();
    });

    test('handles AJAX error callback', () => {
        document.body.innerHTML = `
            <div id="teamUpdateSubmit-1-2"></div>
            <div id="teamUpdateResponse-1-2" style="display: none;"></div>
            <div id="teamUpdateResponseText-1-2"></div>
            <form id="teamForm">
                <input id="event_id" value="1" />
                <input id="team_id" value="2" />
                <button id="updateBtn" type="button"></button>
            </form>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.error({ responseJSON: { data: { message: 'Failed to update' } } });
            opts.complete();
        });

        const btn = document.getElementById('updateBtn');
        updateTeam(btn);

        expect(document.getElementById('teamUpdateResponseText-1-2').textContent).toContain('Failed to update');
        expect(document.getElementById('teamUpdateResponse-1-2').style.display).not.toBe('none');

        spyAjax.mockRestore();
    });

    test('initializeTeamUpdate delegates click on [data-action="update-team"]', () => {
        document.body.innerHTML = `
            <form id="myForm">
                <input id="event_id" value="1" />
                <input id="team_id" value="2" />
                <button id="clickBtn" data-action="update-team"></button>
            </form>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

        initializeTeamUpdate();
        document.getElementById('clickBtn').click();

        expect(spyAjax).toHaveBeenCalled();
        spyAjax.mockRestore();
    });
});
