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
});
