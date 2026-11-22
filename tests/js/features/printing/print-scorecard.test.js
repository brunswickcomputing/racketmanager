/**
 * @jest-environment jsdom
 */

import { printScoreCard, attachToGlobal, initializePrintScoreCard } from '../../../../src/js/features/printing/print-scorecard.js';

describe('features/printing/print-scorecard', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.ajax_var = {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: 'nonce-print-test'
        };
        window.jQuery = window.$ = require('jquery');
    });

    afterEach(() => {
        jest.restoreAllMocks();
        delete window.Racketmanager;
    });

    test('attachToGlobal attaches printScoreCard to window.Racketmanager', () => {
        attachToGlobal();
        expect(window.Racketmanager.printScoreCard).toBe(printScoreCard);
    });

    test('initializePrintScoreCard runs without error', () => {
        expect(() => initializePrintScoreCard()).not.toThrow();
    });

    test('opens popup window and writes content on success', () => {
        document.body.innerHTML = `
            <div id="feedback-50"></div>
        `;

        const mockWindow = {
            document: {
                head: { innerHTML: '' },
                body: { innerHTML: '' }
            }
        };
        const spyOpen = jest.spyOn(globalThis, 'open').mockReturnValue(mockWindow);
        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.success({ data: '<div>Scorecard Content</div>' });
        });

        const fakeEvt = { preventDefault: jest.fn() };
        printScoreCard(fakeEvt, 50);

        expect(fakeEvt.preventDefault).toHaveBeenCalled();
        expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
            url: '/wp-admin/admin-ajax.php',
            data: expect.objectContaining({
                matchId: 50,
                action: 'racketmanager_match_card',
                security: 'nonce-print-test'
            })
        }));

        expect(spyOpen).toHaveBeenCalledWith('about:blank', 'match_card', 'popup, width=800,height=775');
        expect(mockWindow.document.body.innerHTML).toContain('Scorecard Content');

        spyOpen.mockRestore();
        spyAjax.mockRestore();
    });

    test('displays popup blocker error when window.open returns null', () => {
        document.body.innerHTML = `
            <div id="feedback-50"></div>
        `;

        const spyOpen = jest.spyOn(globalThis, 'open').mockReturnValue(null);
        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.success({ data: '<div>Scorecard Content</div>' });
        });

        printScoreCard(null, 50);

        expect(document.getElementById('feedback-50').textContent).toContain('turn off pop blocker');
        expect(document.getElementById('feedback-50').className).toContain('message-error');

        spyOpen.mockRestore();
        spyAjax.mockRestore();
    });

    test('displays error message on AJAX failure', () => {
        document.body.innerHTML = `
            <div id="feedback-50"></div>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.error({ responseJSON: { data: 'Scorecard not generated' } });
        });

        printScoreCard(null, 50);

        expect(document.getElementById('feedback-50').textContent).toBe('Scorecard not generated');
        expect(document.getElementById('feedback-50').className).toContain('message-error');

        spyAjax.mockRestore();
    });

    test('falls back to statusText on AJAX failure when responseJSON is absent', () => {
        document.body.innerHTML = `
            <div id="feedback-50"></div>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.error({ statusText: 'Not Found' });
        });

        printScoreCard(null, 50);

        expect(document.getElementById('feedback-50').textContent).toBe('Not Found');
        expect(document.getElementById('feedback-50').className).toContain('message-error');

        spyAjax.mockRestore();
    });

    test('falls back to default error message on AJAX failure when both responseJSON and statusText are absent', () => {
        document.body.innerHTML = `
            <div id="feedback-50"></div>
        `;

        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.error({});
        });

        printScoreCard(null, 50);

        expect(document.getElementById('feedback-50').textContent).toBe('Request failed');
        expect(document.getElementById('feedback-50').className).toContain('message-error');

        spyAjax.mockRestore();
    });

    test('defensively ignores restricted stylesheet property access when building head', () => {
        document.body.innerHTML = `
            <div id="feedback-50"></div>
        `;

        const mockWindow = {
            document: {
                head: { innerHTML: '' },
                body: { innerHTML: '' }
            }
        };
        const spyOpen = jest.spyOn(globalThis, 'open').mockReturnValue(mockWindow);
        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.success({ data: '<div>Scorecard Content</div>' });
        });

        const originalStyleSheets = Object.getOwnPropertyDescriptor(document, 'styleSheets');
        const restrictedSheet = {};
        Object.defineProperty(restrictedSheet, 'href', {
            get: () => {
                throw new DOMException('Inaccessible stylesheet property', 'SecurityError');
            }
        });
        const normalSheet = { href: 'https://example.com/style.css' };

        Object.defineProperty(document, 'styleSheets', {
            value: [restrictedSheet, normalSheet],
            configurable: true
        });

        try {
            expect(() => printScoreCard(null, 50)).not.toThrow();
            expect(mockWindow.document.head.innerHTML).toContain('https://example.com/style.css');
            expect(mockWindow.document.body.innerHTML).toContain('Scorecard Content');
        } finally {
            if (originalStyleSheets) {
                Object.defineProperty(document, 'styleSheets', originalStyleSheets);
            } else {
                delete document.styleSheets;
            }
            spyOpen.mockRestore();
            spyAjax.mockRestore();
        }
    });

    test('falls back to documentElement.innerHTML when setting head innerHTML throws', () => {
        document.body.innerHTML = `
            <div id="feedback-50"></div>
        `;

        const mockWindow = {
            document: {
                head: {
                    set innerHTML(_) {
                        throw new Error('Disallowed innerHTML');
                    }
                },
                body: { innerHTML: '' },
                documentElement: { innerHTML: '' }
            }
        };
        const spyOpen = jest.spyOn(globalThis, 'open').mockReturnValue(mockWindow);
        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.success({ data: '<div>Fallback Content</div>' });
        });

        printScoreCard(null, 50);

        expect(mockWindow.document.documentElement.innerHTML).toContain('Fallback Content');

        spyOpen.mockRestore();
        spyAjax.mockRestore();
    });

    test('falls back to body.innerHTML when documentElement is missing and head innerHTML throws', () => {
        document.body.innerHTML = `
            <div id="feedback-50"></div>
        `;

        const mockWindow = {
            document: {
                head: {
                    set innerHTML(_) {
                        throw new Error('Disallowed innerHTML');
                    }
                },
                body: { innerHTML: '' }
            }
        };
        const spyOpen = jest.spyOn(globalThis, 'open').mockReturnValue(mockWindow);
        const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
            opts.success({ data: '<div>Fallback Body Content</div>' });
        });

        printScoreCard(null, 50);

        expect(mockWindow.document.body.innerHTML).toContain('Fallback Body Content');

        spyOpen.mockRestore();
        spyAjax.mockRestore();
    });
});
