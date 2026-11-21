/**
 * @jest-environment jsdom
 */

import { initializeActivateTab } from '../../../../src/js/features/navigation/activate-tab.js';
import { switchTab, initializeSwitchTab } from '../../../../src/js/features/navigation/switch-tab.js';

describe('features/navigation/tab-navigation', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        window.jQuery = window.$ = require('jquery');
        jest.clearAllMocks();
    });

    afterEach(() => {
        jQuery(document).off('click.racketmanager.switchTab');
        jest.restoreAllMocks();
    });

    describe('activate-tab', () => {
        test('activates tab from dataset.tabid', () => {
            document.body.innerHTML = `
                <div class="nav-tabs">
                    <button class="nav-link" data-bs-target="#login-pane">Login</button>
                    <button class="nav-link active" data-bs-target="#register-pane">Register</button>
                </div>
                <div class="tab-content">
                    <div id="login-pane" class="tab-pane fade">Login content</div>
                    <div id="register-pane" class="tab-pane active show">Register content</div>
                </div>
                <div data-action="activate-tab" data-tabid="login-pane"></div>
            `;

            initializeActivateTab();

            const loginBtn = document.querySelector('button[data-bs-target="#login-pane"]');
            const loginPane = document.getElementById('login-pane');
            expect(loginBtn.classList.contains('active')).toBe(true);
            expect(loginPane.classList.contains('active')).toBe(true);
        });

        test('falls back to jQuery.data("tabid") when dataset.tabid is missing', () => {
            document.body.innerHTML = `
                <div class="nav-tabs">
                    <button class="nav-link active" data-bs-target="#tab-a">Tab A</button>
                    <button class="nav-link" data-bs-target="#tab-b">Tab B</button>
                </div>
                <div class="tab-content">
                    <div id="tab-a" class="tab-pane active show">A</div>
                    <div id="tab-b" class="tab-pane fade">B</div>
                </div>
            `;

            const activator = document.createElement('div');
            activator.setAttribute('data-action', 'activate-tab');
            jQuery(activator).data('tabid', 'tab-b');
            document.body.appendChild(activator);

            initializeActivateTab();

            const btnB = document.querySelector('button[data-bs-target="#tab-b"]');
            expect(btnB.classList.contains('active')).toBe(true);
        });

        test('uses jQuery tab("show") plugin when available', () => {
            document.body.innerHTML = `
                <div class="nav-tabs">
                    <button class="nav-link" data-bs-target="#tab-plugin">Plugin Tab</button>
                </div>
            `;
            const tabSpy = jest.fn();
            const hadTabPlugin = Object.prototype.hasOwnProperty.call(jQuery.fn, 'tab');
            const originalTabPlugin = jQuery.fn.tab;
            jQuery.fn.tab = tabSpy;

            try {
                const activator = document.createElement('div');
                activator.setAttribute('data-action', 'activate-tab');
                activator.setAttribute('data-tabid', 'tab-plugin');
                document.body.appendChild(activator);

                initializeActivateTab();

                expect(tabSpy).toHaveBeenCalledWith('show');
            } finally {
                if (hadTabPlugin) {
                    jQuery.fn.tab = originalTabPlugin;
                } else {
                    delete jQuery.fn.tab;
                }
            }
        });
    });

    describe('switch-tab', () => {
        test('switches to tab-grid and tab-list via dataset.tabid', () => {
            document.body.innerHTML = `
                <div class="match-group"></div>
                <div class="match"></div>
                <button id="tab-list" class="active">List</button>
                <button id="tab-grid">Grid</button>
                <button id="btnGrid" data-action="switch-tab" data-tabid="tab-grid">Switch Grid</button>
                <button id="btnList" data-action="switch-tab" data-tabid="tab-list">Switch List</button>
            `;

            initializeSwitchTab();

            const btnGrid = document.getElementById('btnGrid');
            btnGrid.click();

            expect(document.querySelector('.match-group').classList.contains('match-group--grid')).toBe(true);
            expect(document.getElementById('tab-grid').classList.contains('active')).toBe(true);
            expect(document.getElementById('tab-list').classList.contains('active')).toBe(false);

            const btnList = document.getElementById('btnList');
            btnList.click();

            expect(document.querySelector('.match-group').classList.contains('match-group--grid')).toBe(false);
            expect(document.getElementById('tab-list').classList.contains('active')).toBe(true);
            expect(document.getElementById('tab-grid').classList.contains('active')).toBe(false);
        });

        test('handles element without dataset.tabid safely', () => {
            const btn = document.createElement('button');
            expect(() => switchTab(btn)).not.toThrow();
        });
    });
});
