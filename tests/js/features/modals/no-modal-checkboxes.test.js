/**
 * @jest-environment jsdom
 */

import {
    initializeNoModalCheckboxes
} from '../../../../src/js/features/modals';

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

});
