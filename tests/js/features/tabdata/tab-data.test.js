/**
 * @jest-environment jsdom
 */

import { initializeTabData, tabData, tabDataLink, attachTabDataToGlobal } from '../../../../src/js/features/tabdata/tab-data.js';

describe('features/tabdata/tab-data', () => {
    const originalAjaxVar = window.ajax_var;
    const originalJQuery = window.jQuery;
    const originalDollar = window.$;

    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.history.pushState({}, '', 'http://localhost/current-page/');
        window.ajax_var = {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: 'test-nonce-123'
        };
        // Mock jQuery.ajax
        window.jQuery = window.$ = {
            ajax: jest.fn()
        };
    });

    afterEach(() => {
        delete window.Racketmanager;
        window.ajax_var = originalAjaxVar;
        window.jQuery = originalJQuery;
        window.$ = originalDollar;
        jest.restoreAllMocks();
    });

    describe('attachTabDataToGlobal', () => {
        test('attaches tabData and tabDataLink to window.Racketmanager', () => {
            attachTabDataToGlobal();
            expect(window.Racketmanager).toBeDefined();
            expect(window.Racketmanager.tabData).toBe(tabData);
            expect(window.Racketmanager.tabDataLink).toBe(tabDataLink);
        });
    });

    describe('initializeTabData', () => {
        test('attaches click listeners to .tabData and .tabDataLink elements and passes security nonce', () => {
            document.body.innerHTML = `
                <button class="tabData" data-type="league" data-type-id="10" data-season="2024" data-name="summer" data-competition-type="mens" data-bs-target="#overview"></button>
                <a class="tabDataLink" data-type="league" data-type-id="10" data-season="2024" data-link="/league/summer/2024/fixtures" data-link-id="fixtures-1" data-link-type="fixtures"></a>
                <div id="leagueTabContent">
                    <div id="overview" class="tab-pane active"></div>
                    <div id="fixtures" class="tab-pane"></div>
                </div>
            `;

            initializeTabData();

            const tabBtn = document.querySelector('.tabData');
            const linkBtn = document.querySelector('.tabDataLink');

            // Trigger tabData click
            tabBtn.click();
            expect(window.jQuery.ajax).toHaveBeenCalledTimes(1);
            expect(window.jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
                type: 'POST',
                data: expect.objectContaining({
                    action: 'racketmanager_get_tab_data',
                    security: 'test-nonce-123',
                    target: 'league',
                    targetId: '10',
                    season: '2024',
                    name: 'summer',
                    competitionType: 'mens',
                    tab: 'overview'
                })
            }));

            // Trigger tabDataLink click
            linkBtn.click();
            expect(window.jQuery.ajax).toHaveBeenCalledTimes(2);
            expect(window.jQuery.ajax).toHaveBeenLastCalledWith(expect.objectContaining({
                type: 'POST',
                data: expect.objectContaining({
                    action: 'racketmanager_get_tab_data',
                    security: 'test-nonce-123',
                    target: 'league',
                    targetId: '10',
                    season: '2024',
                    tab: 'fixtures',
                    link_id: 'fixtures-1'
                })
            }));
        });
    });

    describe('tabData', () => {
        test('logs error and returns if no target attribute found', () => {
            const spyError = jest.spyOn(console, 'error').mockImplementation(() => {});
            const button = document.createElement('button');
            const event = {
                preventDefault: jest.fn(),
                currentTarget: button
            };

            tabData(event, 'league', 1, '2024', 'summer', 'mens');

            expect(event.preventDefault).toHaveBeenCalled();
            expect(spyError).toHaveBeenCalledWith('No tab target found');
            expect(window.jQuery.ajax).not.toHaveBeenCalled();
            spyError.mockRestore();
        });

        test('skips AJAX call if URL would not change', () => {
            const spyLog = jest.spyOn(console, 'log').mockImplementation(() => {});
            const button = document.createElement('button');
            button.setAttribute('data-bs-target', '#fixtures');
            const event = {
                preventDefault: jest.fn(),
                currentTarget: button
            };

            // Expected URL: http://localhost/mens/summer/2024/fixtures/
            const expectedUrl = 'http://localhost/mens/summer/2024/fixtures/';
            window.history.pushState({}, '', expectedUrl);

            tabData(event, 'league', 1, '2024', 'summer', 'mens');

            expect(spyLog).toHaveBeenCalledWith(expect.stringContaining('Tab already loaded'));
            expect(window.jQuery.ajax).not.toHaveBeenCalled();
            spyLog.mockRestore();
        });

        test('builds URLs correctly across different types (tournament, event, competition, fallback)', () => {
            const pushStateSpy = jest.spyOn(window.history, 'pushState');
            const cases = [
                { type: 'tournament', comp: 'singles', expected: 'http://localhost/tournament/open/results/' },
                { type: 'event', comp: 'mixed', expected: 'http://localhost/mixeds/open/2024/results/' },
                { type: 'competition', comp: 'mens', expected: 'http://localhost/open/2024/results/' },
                { type: 'other', comp: 'none', expected: 'http://localhost/other/open/results/' }
            ];

            cases.forEach(({ type, comp, expected }) => {
                window.history.pushState({}, '', 'http://localhost/current-page/');
                document.body.innerHTML = `
                    <button id="btn-${type}" data-bs-target="#results"></button>
                    <div id="${type}TabContent">
                        <div id="results"></div>
                    </div>
                `;
                const btn = document.querySelector(`#btn-${type}`);
                const event = { preventDefault: jest.fn(), currentTarget: btn };

                tabData(event, type, 5, '2024', 'open', comp);

                expect(window.jQuery.ajax).toHaveBeenLastCalledWith(expect.objectContaining({
                    data: expect.objectContaining({
                        action: 'racketmanager_get_tab_data',
                        security: 'test-nonce-123',
                        target: type,
                        tab: 'results'
                    })
                }));

                const ajaxOptions = window.jQuery.ajax.mock.calls[window.jQuery.ajax.mock.calls.length - 1][0];
                ajaxOptions.success('<div>Content for ' + type + '</div>');

                expect(pushStateSpy).toHaveBeenCalledWith(
                    { html: '<div>Content for ' + type + '</div>' },
                    '',
                    expected
                );
            });

            pushStateSpy.mockRestore();
        });

        test('handles successful AJAX response and updates DOM and history', () => {
            window.history.pushState({}, '', 'http://localhost/current-page/');
            document.body.innerHTML = `
                <button id="btn" data-bs-target="#matches"></button>
                <div id="leagueTabContent">
                    <div id="matches">Old Content</div>
                </div>
            `;
            const pushStateSpy = jest.spyOn(window.history, 'pushState');
            const btn = document.querySelector('#btn');
            const event = { preventDefault: jest.fn(), currentTarget: btn };

            tabData(event, 'league', 10, '2024', 'summer', 'mens');

            // Find the ajax options passed
            const ajaxOptions = window.jQuery.ajax.mock.calls[0][0];
            expect(document.querySelector('#leagueTabContent').classList.contains('is-loading')).toBe(true);
            expect(ajaxOptions.data.security).toBe('test-nonce-123');

            // Execute success
            ajaxOptions.success('<div>Loaded Matches Content</div>');
            expect(document.querySelector('#matches').innerHTML).toBe('<div>Loaded Matches Content</div>');
            expect(pushStateSpy).toHaveBeenCalledWith(
                { html: '<div>Loaded Matches Content</div>' },
                '',
                'http://localhost/mens/summer/2024/matches/'
            );

            // Complete hook
            ajaxOptions.complete();
            expect(document.querySelector('#leagueTabContent').classList.contains('is-loading')).toBe(false);

            pushStateSpy.mockRestore();
        });

        test('handles AJAX error response and displays error message in tabContent', () => {
            const spyError = jest.spyOn(console, 'error').mockImplementation(() => {});
            window.history.pushState({}, '', 'http://localhost/current-page/');
            document.body.innerHTML = `
                <button id="btn" data-bs-target="#tab1"></button>
                <div id="leagueTabContent">
                    <div id="tab1"></div>
                </div>
            `;
            const btn = document.querySelector('#btn');
            const event = { preventDefault: jest.fn(), currentTarget: btn };

            tabData(event, 'league', 10, '2024', 'summer', 'mens');
            const ajaxOptions = window.jQuery.ajax.mock.calls[0][0];

            ajaxOptions.error({}, 'error', 'Network error');
            expect(spyError).toHaveBeenCalledWith('Tab data load error:', 'Network error');
            expect(document.querySelector('#leagueTabContent .alert-danger').textContent).toBe('Failed to load content');

            ajaxOptions.complete();
            expect(document.querySelector('#leagueTabContent').classList.contains('is-loading')).toBe(false);
            spyError.mockRestore();
        });
    });

    describe('tabDataLink', () => {
        test('switches active tab, clears content, sends AJAX and pushes history on success', () => {
            window.history.pushState({}, '', 'http://localhost/league/summer/2024/overview');
            const pushStateSpy = jest.spyOn(window.history, 'pushState');
            document.body.innerHTML = `
                <ul id="myTab">
                    <li><button id="overview-tab" class="active">Overview</button></li>
                    <li><button id="fixtures-tab">Fixtures</button></li>
                </ul>
                <div id="pageContentTab"><span>Page Content</span></div>
                <div id="leagueTabContent">
                    <div id="overview" class="tab-pane active show">Overview Content</div>
                    <div id="fixtures" class="tab-pane fade"></div>
                </div>
            `;

            const linkElem = document.createElement('a');
            const event = { preventDefault: jest.fn(), currentTarget: linkElem };

            tabDataLink(event, 'league', 12, '2024', '/league/summer/2024/fixtures/', 'fix-99', 'fixtures');

            // Button should now be active
            expect(document.querySelector('#overview-tab').classList.contains('active')).toBe(false);
            expect(document.querySelector('#fixtures-tab').classList.contains('active')).toBe(true);

            // Tab panes swapped
            expect(document.querySelector('#overview').classList.contains('active')).toBe(false);
            expect(document.querySelector('#fixtures').classList.contains('active')).toBe(true);

            // Check AJAX call
            expect(window.jQuery.ajax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.objectContaining({
                    action: 'racketmanager_get_tab_data',
                    security: 'test-nonce-123',
                    target: 'league',
                    targetId: 12,
                    season: '2024',
                    tab: 'fixtures',
                    link_id: 'fix-99'
                })
            }));

            const ajaxOptions = window.jQuery.ajax.mock.calls[0][0];
            ajaxOptions.success('<div>Fixtures list</div>');

            expect(document.querySelector('#fixtures').innerHTML).toBe('<div>Fixtures list</div>');
            expect(pushStateSpy).toHaveBeenCalledWith(
                '<span>Page Content</span>',
                '',
                'http://localhost/league/summer/2024/fixtures/'
            );

            ajaxOptions.complete();
            expect(document.querySelector('#leagueTabContent').classList.contains('is-loading')).toBe(false);

            pushStateSpy.mockRestore();
        });

        test('handles tabDataLink error callback', () => {
            const spyError = jest.spyOn(console, 'error').mockImplementation(() => {});
            document.body.innerHTML = `
                <ul id="myTab"><li><button id="fixtures-tab">Fixtures</button></li></ul>
                <div id="pageContentTab"></div>
                <div id="leagueTabContent">
                    <div id="fixtures"></div>
                </div>
            `;

            const linkElem = document.createElement('a');
            const event = { preventDefault: jest.fn(), currentTarget: linkElem };

            tabDataLink(event, 'league', 12, '2024', '/fixtures/', 'fix-99', 'fixtures');
            const ajaxOptions = window.jQuery.ajax.mock.calls[0][0];

            ajaxOptions.error({}, 'error', 'Could not fetch tab link');
            expect(spyError).toHaveBeenCalledWith('Tab data link load error:', 'Could not fetch tab link');
            expect(document.querySelector('#fixtures .alert-danger').textContent).toBe('Failed to load content');

            ajaxOptions.complete();
            expect(document.querySelector('#leagueTabContent').classList.contains('is-loading')).toBe(false);
            spyError.mockRestore();
        });
    });
});
