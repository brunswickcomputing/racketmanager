/**
 * @jest-environment jsdom
 */

import { setMatchRubberStatus, initializeSetMatchRubberStatus } from '../../../../src/js/features/match/set-match-rubber-status.js';
import * as rubberStatusModal from '../../../../src/js/features/match/rubber-status-modal.js';

describe('features/match/set-match-rubber-status', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.jQuery = window.$ = require('jquery');
    });

    afterEach(() => {
        delete globalThis.Racketmanager;
        jest.restoreAllMocks();
    });

    test('delegates setMatchRubberStatus to setRubberStatus', () => {
        const spyModal = jest.spyOn(rubberStatusModal, 'setRubberStatus').mockReturnValue('result_123');
        const link = document.createElement('button');

        const result = setMatchRubberStatus(link);

        expect(spyModal).toHaveBeenCalledWith(link);
        expect(result).toBe('result_123');
    });

    test('initializeSetMatchRubberStatus attaches global and click listener', () => {
        const spyModal = jest.spyOn(rubberStatusModal, 'setRubberStatus').mockImplementation(() => {});

        document.body.innerHTML = `
            <button id="rubberBtn" data-action="set-match-rubber-status"></button>
        `;

        initializeSetMatchRubberStatus();

        expect(globalThis.Racketmanager.setMatchRubberStatus).toBe(setMatchRubberStatus);

        const btn = document.getElementById('rubberBtn');
        btn.click();

        expect(spyModal).toHaveBeenCalledWith(btn);
    });
});
