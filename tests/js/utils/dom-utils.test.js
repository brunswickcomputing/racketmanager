import { toggleElement, handleCheckboxConditional } from '../../../src/js/utils/dom-utils.js';

describe('dom-utils', () => {
    describe('toggleElement', () => {
        beforeEach(() => {
            document.body.innerHTML = '<div id="target" style="display: none;">Content</div>';
        });

        test('shows element when show is true', () => {
            toggleElement('#target', true);
            expect(jQuery('#target').css('display')).not.toBe('none');
        });

        test('hides element when show is false', () => {
            jQuery('#target').show();
            toggleElement('#target', false);
            expect(jQuery('#target').css('display')).toBe('none');
        });
    });

    describe('handleCheckboxConditional', () => {
        let container;

        beforeEach(() => {
            document.body.innerHTML = `
                <div class="form-group">
                    <div>
                        <input type="checkbox" id="my-checkbox" aria-controls="conditional-section" />
                    </div>
                    <div id="conditional-section" class="form-checkboxes__conditional form-checkboxes__conditional--hidden">
                        Conditional Content
                    </div>
                </div>
            `;
            container = document.body;
        });

        test('toggles conditional element when checkbox is checked', () => {
            const checkbox = document.getElementById('my-checkbox');
            const conditional = document.getElementById('conditional-section');

            checkbox.checked = true;
            handleCheckboxConditional(checkbox);

            expect(conditional.getAttribute('aria-expanded')).toBe('true');
            expect(conditional.classList.contains('form-checkboxes__conditional--hidden')).toBe(false);

            checkbox.checked = false;
            handleCheckboxConditional(checkbox);

            expect(conditional.getAttribute('aria-expanded')).toBe('false');
            expect(conditional.classList.contains('form-checkboxes__conditional--hidden')).toBe(true);
        });

        test('does nothing if element is not a checkbox', () => {
            const input = document.createElement('input');
            input.setAttribute('type', 'text');
            input.setAttribute('aria-controls', 'conditional-section');
            container.appendChild(input);

            handleCheckboxConditional(input);

            const conditional = document.getElementById('conditional-section');
            expect(conditional.getAttribute('aria-expanded')).toBeNull();
        });

        test('does nothing if checkbox lacks aria-controls', () => {
            const checkbox = document.getElementById('my-checkbox');
            checkbox.removeAttribute('aria-controls');

            handleCheckboxConditional(checkbox);

            const conditional = document.getElementById('conditional-section');
            expect(conditional.getAttribute('aria-expanded')).toBeNull();
        });

        test('does nothing if controlled element does not have form-checkboxes__conditional class', () => {
            const checkbox = document.getElementById('my-checkbox');
            const conditional = document.getElementById('conditional-section');
            conditional.className = 'some-other-class';

            checkbox.checked = true;
            handleCheckboxConditional(checkbox);

            expect(conditional.getAttribute('aria-expanded')).toBeNull();
        });

        test('does nothing if controlled element cannot be found', () => {
            const checkbox = document.getElementById('my-checkbox');
            checkbox.setAttribute('aria-controls', 'non-existent-section');

            expect(() => handleCheckboxConditional(checkbox)).not.toThrow();
        });
    });
});
