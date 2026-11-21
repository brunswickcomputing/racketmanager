import { showElement, hideElement, handleCheckboxConditional, getDataAttr } from '../../../src/js/utils/dom-utils.js';

describe('dom-utils', () => {
    describe('showElement', () => {
        beforeEach(() => {
            document.body.innerHTML = '<div id="target" style="display: none;">Content</div>';
        });

        test('shows element', () => {
            showElement('#target');
            expect(jQuery('#target').css('display')).not.toBe('none');
        });
    });

    describe('hideElement', () => {
        beforeEach(() => {
            document.body.innerHTML = '<div id="target">Content</div>';
        });

        test('hides element', () => {
            hideElement('#target');
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

    describe('getDataAttr', () => {
        test('returns null when element is null or undefined', () => {
            expect(getDataAttr(null, 'action')).toBeNull();
            expect(getDataAttr(undefined, 'action')).toBeNull();
        });

        test('reads property from element.dataset when available', () => {
            const el = document.createElement('div');
            el.dataset.action = 'submit-form';
            el.dataset.leagueId = '42';

            expect(getDataAttr(el, 'action')).toBe('submit-form');
            expect(getDataAttr(el, 'leagueId')).toBe('42');
        });

        test('preserves empty string values in dataset', () => {
            const el = document.createElement('div');
            el.dataset.action = '';

            expect(getDataAttr(el, 'action')).toBe('');
        });

        test('falls back to attributes.getNamedItem when dataset is undefined', () => {
            const el = document.createElement('button');
            el.setAttribute('data-action', 'delete-user');
            el.setAttribute('data-league-id', '101');
            Object.defineProperty(el, 'dataset', { value: undefined, configurable: true });

            expect(getDataAttr(el, 'action')).toBe('delete-user');
            expect(getDataAttr(el, 'leagueId')).toBe('101');
        });

        test('falls back to getAttribute when attributes.getNamedItem is unavailable', () => {
            const stub = {
                getAttribute: jest.fn((attr) => (attr === 'data-action' ? 'stub-action' : null)),
            };

            expect(getDataAttr(stub, 'action')).toBe('stub-action');
            expect(stub.getAttribute).toHaveBeenCalledWith('data-action');
        });

        test('returns null when element has neither dataset nor matching attribute', () => {
            const el = document.createElement('div');
            expect(getDataAttr(el, 'action')).toBeNull();
        });
    });
});
