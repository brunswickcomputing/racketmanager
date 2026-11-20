/**
 * @jest-environment jsdom
 */

import {
    initializeNoModalCheckboxes,
    initializeCheckbox,
    reinitializeNoModalCheckboxes,
    toggleCheckboxConditional,
    getConditionalState
} from '../../../../src/js/features/modals/no-modal-checkboxes.js';

describe('features/modals/no-modal-checkboxes', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.jQuery = window.$ = require('jquery');
    });

    test('initializes noModal checkboxes and toggles conditional element visibility', () => {
        document.body.innerHTML = `
            <div>
                <div>
                    <input type="checkbox" class="noModal" id="chk1" aria-controls="cond1" />
                    <div id="cond1" class="form-checkboxes__conditional form-checkboxes__conditional--hidden" style="display: none;">
                        Conditional Content
                    </div>
                </div>
            </div>
        `;

        initializeNoModalCheckboxes();

        const chk = document.getElementById('chk1');
        const cond = document.getElementById('cond1');

        // Clicking a native unchecked checkbox toggles checked to true
        chk.click();

        expect(cond.getAttribute('aria-expanded')).toBe('true');
        expect(cond.classList.contains('form-checkboxes__conditional--hidden')).toBe(false);
        expect(cond.style.display).toBe('');

        // Clicking again toggles checked to false
        chk.click();

        expect(cond.getAttribute('aria-expanded')).toBe('false');
        expect(cond.classList.contains('form-checkboxes__conditional--hidden')).toBe(true);
        expect(cond.style.display).toBe('none');
    });

    test('initializeCheckbox attaches handler to specific selector', () => {
        document.body.innerHTML = `
            <input type="checkbox" id="dynamicChk" aria-controls="dynamicCond" />
            <div id="dynamicCond" class="form-checkboxes__conditional"></div>
        `;

        initializeCheckbox('#dynamicChk');
        const chk = document.getElementById('dynamicChk');
        const cond = document.getElementById('dynamicCond');

        chk.click();

        expect(cond.getAttribute('aria-expanded')).toBe('true');
    });

    test('reinitializeNoModalCheckboxes unbinds and rebinds click handler', () => {
        document.body.innerHTML = `
            <input type="checkbox" class="noModal" id="reinitChk" aria-controls="reinitCond" />
            <div id="reinitCond" class="form-checkboxes__conditional"></div>
        `;

        reinitializeNoModalCheckboxes();
        const chk = document.getElementById('reinitChk');
        const cond = document.getElementById('reinitCond');

        chk.click();

        expect(cond.getAttribute('aria-expanded')).toBe('true');
    });

    test('toggleCheckboxConditional and getConditionalState query and modify state', () => {
        document.body.innerHTML = `
            <input type="checkbox" id="apiChk" aria-controls="apiCond" />
            <div id="apiCond" class="form-checkboxes__conditional form-checkboxes__conditional--hidden"></div>
        `;

        expect(getConditionalState('#unknown')).toBeNull();

        let state = getConditionalState('#apiChk');
        expect(state.checked).toBe(false);
        expect(state.visible).toBe(false);

        toggleCheckboxConditional('#apiChk', true);
        state = getConditionalState('#apiChk');
        expect(state.checked).toBe(true);
        expect(state.visible).toBe(true);

        // Warning when toggle element not found
        const spyWarn = jest.spyOn(console, 'warn').mockImplementation(() => {});
        toggleCheckboxConditional('#nonexistent', true);
        expect(spyWarn).toHaveBeenCalledWith('Checkbox not found: #nonexistent');
        spyWarn.mockRestore();
    });
});
