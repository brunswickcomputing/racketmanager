/**
 * @jest-environment jsdom
 */

import { openClubRoleModal, setClubRole, initializeClubAdmin } from '../../../../src/js/features/club/admin/club-roles.js';

describe('features/club/admin/club-roles', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: 'club-nonce-123'
        };
        window.jQuery = window.$ = require('jquery');
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    describe('openClubRoleModal', () => {
        test('returns early if clubRoleId is falsy', () => {
            const spyLoad = jest.fn();
            jQuery.fn.load = spyLoad;
            openClubRoleModal(null, null);
            expect(spyLoad).not.toHaveBeenCalled();
        });

        test('loads modal UI, opens modal, and initializes user autocomplete', () => {
            document.body.innerHTML = `
                <div id="rolesResponse"><div id="rolesResponseText"></div></div>
                <div id="clubRoleModal"></div>
                <div id="splashBlockModal"></div>
            `;

            let capturedCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                capturedCallback = callback;
                return this;
            });
            jQuery.fn.modal = jest.fn();

            const fakeEvt = { preventDefault: jest.fn() };
            openClubRoleModal(fakeEvt, 'role-5');

            expect(fakeEvt.preventDefault).toHaveBeenCalled();
            expect(jQuery.fn.load).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({
                    action: 'racketmanager_club_role_modal',
                    clubRoleId: 'role-5',
                    security: 'club-nonce-123'
                }),
                expect.any(Function)
            );

            // Success callback
            capturedCallback('<div>Club Role Modal Body</div>', 'success', {});
            expect(jQuery.fn.modal).toHaveBeenCalledWith('show');

            // Error callback JSON parse
            const spyError = jest.spyOn(console, 'error').mockImplementation(() => {});
            capturedCallback('{"message":"Role not found"}', 'error', {});
            expect(document.getElementById('rolesResponseText').textContent).toBe('Role not found');
            expect(document.getElementById('rolesResponse').style.display).not.toBe('none');
            spyError.mockRestore();
        });
    });

    describe('setClubRole', () => {
        test('exits early if link or form is invalid', () => {
            const spyAjax = jest.spyOn(jQuery, 'ajax');
            setClubRole(null);
            setClubRole({});
            expect(spyAjax).not.toHaveBeenCalled();
        });

        test('serializes form, calls AJAX, and updates alert message', () => {
            document.body.innerHTML = `
                <div id="clubRoleResponse"><div id="clubRoleResponseText"></div></div>
                <form id="roleForm">
                    <input name="role" value="captain" />
                    <button id="saveBtn" type="button"></button>
                </form>
            `;

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    data: {
                        status: 'success',
                        msg: 'Role updated successfully'
                    }
                });
            });

            const btn = document.getElementById('saveBtn');
            setClubRole(btn);

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_set_club_role')
            }));

            expect(document.getElementById('clubRoleResponse').className).toContain('alert--success');
            expect(document.getElementById('clubRoleResponseText').textContent).toBe('Role updated successfully');

            spyAjax.mockRestore();
        });
    });

    describe('initializeClubAdmin', () => {
        test('attaches click listeners to open role modal and submit updates', () => {
            document.body.innerHTML = `
                <button class="club-role" data-club-role-id="100"></button>
                <form id="clubRoleForm">
                    <button id="clubRoleUpdateSubmit"></button>
                </form>
            `;

            jQuery.fn.load = jest.fn();
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeClubAdmin();

            document.querySelector('.club-role').click();
            expect(jQuery.fn.load).toHaveBeenCalled();

            document.getElementById('clubRoleUpdateSubmit').click();
            expect(spyAjax).toHaveBeenCalled();

            spyAjax.mockRestore();
        });
    });
});
