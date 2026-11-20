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
        jQuery.fn.modal = jest.fn();
        jQuery.fn.autocomplete = jest.fn();
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    describe('openClubRoleModal', () => {
        test('returns early if clubRoleId is falsy', () => {
            const spyLoad = jest.fn();
            jQuery.fn.load = spyLoad;
            openClubRoleModal(null, null);
            openClubRoleModal(null, '');
            openClubRoleModal(null, 0);
            expect(spyLoad).not.toHaveBeenCalled();
        });

        test('handles event without preventDefault or null event', () => {
            const spyLoad = jest.fn();
            jQuery.fn.load = spyLoad;
            openClubRoleModal({}, 'role-1');
            expect(spyLoad).toHaveBeenCalled();
        });

        test('handles modal exceptions in try/catch gracefully', () => {
            document.body.innerHTML = `
                <div id="rolesResponse"><div id="rolesResponseText"></div></div>
                <div id="clubRoleModal"></div>
                <div id="splashBlockModal"></div>
            `;

            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
            jQuery.fn.modal = jest.fn().mockImplementation(() => {
                throw new Error('Modal error');
            });

            let capturedCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                capturedCallback = callback;
                return this;
            });

            openClubRoleModal(null, 'role-1');
            expect(consoleErrorSpy).toHaveBeenCalled();

            // Success callback invokes modal hide and modal show which throw
            capturedCallback('<div>Modal content</div>', 'success', {});
            expect(consoleErrorSpy).toHaveBeenCalled();

            consoleErrorSpy.mockRestore();
        });

        test('loads modal UI, opens modal, and initializes user autocomplete', () => {
            document.body.innerHTML = `
                <div id="rolesResponse"><div id="rolesResponseText"></div></div>
                <div id="clubRoleModal">
                    <input id="userName" />
                </div>
                <div id="splashBlockModal"></div>
            `;

            let capturedCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                capturedCallback = callback;
                return this;
            });
            jQuery.fn.modal = jest.fn();

            let capturedAutocompleteOpts;
            jQuery.fn.autocomplete = jest.fn(function (opts) {
                capturedAutocompleteOpts = opts;
                return this;
            });

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
            expect(jQuery.fn.autocomplete).toHaveBeenCalled();
            expect(capturedAutocompleteOpts).toBeDefined();
            expect(capturedAutocompleteOpts.minLength).toBe(2);
        });

        test('handles error callback with valid JSON lacking message', () => {
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

            openClubRoleModal(null, 'role-5');
            capturedCallback('{}', 'error', {});

            expect(document.getElementById('rolesResponseText').textContent).toBe('An error occurred');
            expect(document.getElementById('rolesResponse').style.display).not.toBe('none');
        });

        test('handles error callback with non-JSON response falling back to handleAjaxError', () => {
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

            openClubRoleModal(null, 'role-5');

            const mockXhr = {
                status: 500,
                statusText: 'Server Error',
                responseJSON: { data: { message: 'Fallback server error' } },
            };

            capturedCallback('<html>Server error</html>', 'error', mockXhr);

            expect(document.getElementById('rolesResponseText').textContent).toBe('Fallback server error');
            expect(document.getElementById('rolesResponse').style.display).not.toBe('none');
            expect(document.getElementById('rolesResponse').className).toContain('alert--danger');
        });
    });

    describe('setClubRole', () => {
        test('exits early if link or form or form.id is invalid', () => {
            const spyAjax = jest.spyOn(jQuery, 'ajax');
            setClubRole(null);
            setClubRole({});
            setClubRole({ form: null });
            setClubRole({ form: { id: '' } });
            expect(spyAjax).not.toHaveBeenCalled();
        });

        test('serializes form, calls AJAX, and updates alert message on success', () => {
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
                        status: 'warning',
                        msg: 'Role saved with warnings'
                    }
                });
            });

            const btn = document.getElementById('saveBtn');
            setClubRole(btn);

            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.stringContaining('action=racketmanager_set_club_role')
            }));

            expect(document.getElementById('clubRoleResponse').className).toContain('alert--warning');
            expect(document.getElementById('clubRoleResponseText').textContent).toBe('Role saved with warnings');

            spyAjax.mockRestore();
        });

        test('handles success response with missing data or missing properties (defaults)', () => {
            document.body.innerHTML = `
                <div id="clubRoleResponse"><div id="clubRoleResponseText"></div></div>
                <form id="roleForm">
                    <button id="saveBtn" type="button"></button>
                </form>
            `;

            let successHandler;
            jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                successHandler = opts.success;
            });

            const btn = document.getElementById('saveBtn');
            setClubRole(btn);

            // Empty response
            successHandler({});
            expect(document.getElementById('clubRoleResponse').className).toContain('alert--success');
            expect(document.getElementById('clubRoleResponseText').textContent).toBe('');

            // Data with status and empty msg
            successHandler({ data: { status: 'danger' } });
            expect(document.getElementById('clubRoleResponse').className).toContain('alert--danger');
            expect(document.getElementById('clubRoleResponseText').textContent).toBe('');
        });

        test('handles AJAX error callback using handleAjaxError', () => {
            document.body.innerHTML = `
                <div id="clubRoleResponse" style="display: none;"><div id="clubRoleResponseText"></div></div>
                <form id="roleForm">
                    <button id="saveBtn" type="button"></button>
                </form>
            `;

            const mockErrorResponse = {
                status: 500,
                statusText: 'Internal Error',
                responseJSON: { data: { message: 'Database save failed' } },
            };

            jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.error(mockErrorResponse);
            });

            const btn = document.getElementById('saveBtn');
            setClubRole(btn);

            expect(document.getElementById('clubRoleResponse').style.display).not.toBe('none');
            expect(document.getElementById('clubRoleResponseText').textContent).toBe('Database save failed');
            expect(document.getElementById('clubRoleResponse').className).toContain('alert--danger');
        });
    });

    describe('user autocomplete lifecycle (source, select, change)', () => {
        test('destroys existing autocomplete if ui-autocomplete-input class is present', () => {
            document.body.innerHTML = `
                <div id="clubRoleModal">
                    <input id="userName" class="ui-autocomplete-input" />
                </div>
                <div id="splashBlockModal"></div>
            `;

            let loadCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                loadCallback = callback;
                return this;
            });

            openClubRoleModal(null, 'role-1');
            loadCallback('<div>content</div>', 'success', {});

            expect(jQuery.fn.autocomplete).toHaveBeenCalledWith('destroy');
        });

        test('catches autocomplete destroy errors gracefully', () => {
            document.body.innerHTML = `
                <div id="clubRoleModal">
                    <input id="userName" class="ui-autocomplete-input" />
                </div>
                <div id="splashBlockModal"></div>
            `;

            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
            jQuery.fn.autocomplete = jest.fn(function (arg) {
                if (arg === 'destroy') {
                    throw new Error('Destroy failed');
                }
                return this;
            });

            let loadCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                loadCallback = callback;
                return this;
            });

            openClubRoleModal(null, 'role-1');
            loadCallback('<div>content</div>', 'success', {});

            expect(consoleErrorSpy).toHaveBeenCalled();
            consoleErrorSpy.mockRestore();
        });

        test('exercises autocomplete source, select, and change handlers', () => {
            document.body.innerHTML = `
                <input id="clubId" value="44" />
                <div id="clubRoleModal">
                    <input id="userName" />
                    <input id="userId" />
                    <input id="contactno" />
                    <input id="contactemail" />
                    <div id="userFeedback"></div>
                </div>
                <div id="splashBlockModal"></div>
            `;

            let capturedOpts;
            jQuery.fn.autocomplete = jest.fn(function (opts) {
                if (typeof opts === 'object') {
                    capturedOpts = opts;
                }
                return this;
            });

            let loadCallback;
            jQuery.fn.load = jest.fn(function (url, data, callback) {
                loadCallback = callback;
                return this;
            });

            openClubRoleModal(null, 'role-1');
            loadCallback('<div>content</div>', 'success', {});

            expect(capturedOpts).toBeDefined();

            // 1. Test source callback
            const ajaxSpy = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({
                    success: true,
                    data: JSON.stringify([{ value: 'John Smith', playerId: '10' }])
                });
            });

            const responseSpy = jest.fn();
            capturedOpts.source({ term: 'John' }, responseSpy);

            expect(ajaxSpy).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.objectContaining({
                    action: 'racketmanager_get_player_details',
                    name: 'John',
                    club: '44',
                })
            }));
            expect(responseSpy).toHaveBeenCalledWith([
                { value: 'John Smith', playerId: '10' }
            ]);

            ajaxSpy.mockRestore();

            // 2. Test select callback with null ui or item
            capturedOpts.select({}, null);
            capturedOpts.select({}, {});

            // 3. Test select callback when ui.item.value is string 'null'
            capturedOpts.select({}, {
                item: {
                    value: 'null',
                    playerId: '12',
                    contactno: '07123456789',
                    user_email: 'john@example.com'
                }
            });
            expect(document.getElementById('userName').value).toBe('');
            expect(document.getElementById('userId').value).toBe('12');
            expect(document.getElementById('contactno').value).toBe('07123456789');
            expect(document.getElementById('contactemail').value).toBe('john@example.com');

            // 4. Test select callback with valid player details
            capturedOpts.select({}, {
                item: {
                    value: 'Alice Wonder',
                    playerId: '15',
                    contactno: '07987654321',
                    user_email: 'alice@example.com'
                }
            });
            expect(document.getElementById('userName').value).toBe('Alice Wonder');
            expect(document.getElementById('userId').value).toBe('15');
            expect(document.getElementById('contactno').value).toBe('07987654321');
            expect(document.getElementById('contactemail').value).toBe('alice@example.com');

            // 5. Test select callback with empty/missing properties (fallback branches)
            capturedOpts.select({}, {
                item: {
                    value: undefined,
                    playerId: undefined,
                    contactno: undefined,
                    user_email: undefined
                }
            });
            expect(document.getElementById('userName').value).toBe('');
            expect(document.getElementById('userId').value).toBe('');
            expect(document.getElementById('contactno').value).toBe('');
            expect(document.getElementById('contactemail').value).toBe('');

            // 6. Test change callback (calls setPlayerDetails)
            capturedOpts.change({}, { item: null });
            expect(document.getElementById('userName').value).toBe('');
            expect(document.getElementById('userId').value).toBe('');
        });
    });

    describe('initializeClubAdmin', () => {
        test('attaches click listeners to open role modal and submit updates', () => {
            document.body.innerHTML = `
                <button class="club-role" data-club-role-id="100"></button>
                <button id="roleLink2" class="club-role"></button>
                <form id="clubRoleForm">
                    <button id="clubRoleUpdateSubmit"></button>
                    <button id="setClubRoleAction" data-action="set-club-role"></button>
                </form>
            `;

            jQuery.fn.load = jest.fn();
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation(() => {});

            initializeClubAdmin();

            // Click with data-club-role-id
            document.querySelector('.club-role').click();
            expect(jQuery.fn.load).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({ clubRoleId: '100' }),
                expect.any(Function)
            );

            // Click with jQuery .data('clubRoleId') fallback
            jQuery('#roleLink2').data('clubRoleId', '200');
            document.getElementById('roleLink2').click();
            expect(jQuery.fn.load).toHaveBeenCalledWith(
                '/wp-admin/admin-ajax.php',
                expect.objectContaining({ clubRoleId: '200' }),
                expect.any(Function)
            );

            // Click #clubRoleUpdateSubmit
            document.getElementById('clubRoleUpdateSubmit').click();
            expect(spyAjax).toHaveBeenCalled();

            // Click data-action="set-club-role"
            document.getElementById('setClubRoleAction').click();
            expect(spyAjax).toHaveBeenCalledTimes(2);

            // Trigger click without preventDefault function to cover false branch of line 145
            jQuery('#clubRoleUpdateSubmit').trigger({ type: 'click', preventDefault: undefined });
            expect(spyAjax).toHaveBeenCalledTimes(3);

            spyAjax.mockRestore();
        });
    });
});
