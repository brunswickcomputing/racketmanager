import $ from 'jquery';
import {
    isAutocompleteAvailable,
    isJQueryUIAvailable,
    whenAutocompleteReady,
    safeAutocomplete,
    logJQueryStatus
} from '../../../../src/js/features/autocomplete/autocomplete-helpers';

describe('autocomplete-helpers', () => {
    let originalJQuery;

    beforeEach(() => {
        document.body.innerHTML = '';
        originalJQuery = global.jQuery;
        global.jQuery = $;
        window.jQuery = $;
        jest.clearAllMocks();
    });

    afterEach(() => {
        global.jQuery = originalJQuery;
        window.jQuery = originalJQuery;
    });

    describe('isAutocompleteAvailable', () => {
        test('returns false when autocomplete plugin is missing', () => {
            delete global.jQuery.fn.autocomplete;
            delete window.jQuery.fn.autocomplete;
            expect(isAutocompleteAvailable()).toBe(false);
        });

        test('returns true when autocomplete is a function', () => {
            global.jQuery.fn.autocomplete = jest.fn();
            expect(isAutocompleteAvailable()).toBe(true);
            delete global.jQuery.fn.autocomplete;
        });

        test('returns false when jQuery is unavailable', () => {
            delete global.jQuery;
            delete window.jQuery;
            expect(isAutocompleteAvailable()).toBe(false);
        });
    });

    describe('isJQueryUIAvailable', () => {
        test('returns true when jQuery UI is loaded', () => {
            global.jQuery.ui = {};
            expect(isJQueryUIAvailable()).toBe(true);
            delete global.jQuery.ui;
        });

        test('returns false when jQuery UI is unavailable', () => {
            delete global.jQuery.ui;
            expect(isJQueryUIAvailable()).toBe(false);

            global.jQuery.ui = null;
            expect(isJQueryUIAvailable()).toBe(false);
        });
    });

    describe('whenAutocompleteReady', () => {
        beforeEach(() => {
            jest.useFakeTimers();
        });

        afterEach(() => {
            jest.useRealTimers();
        });

        test('invokes callback immediately if autocomplete is available', () => {
            global.jQuery.fn.autocomplete = jest.fn();
            const callback = jest.fn();

            whenAutocompleteReady(callback);
            expect(callback).toHaveBeenCalledTimes(1);

            delete global.jQuery.fn.autocomplete;
        });

        test('polls until autocomplete is available', () => {
            const callback = jest.fn();
            whenAutocompleteReady(callback, 1000, 100);

            expect(callback).not.toHaveBeenCalled();

            jest.advanceTimersByTime(200);
            expect(callback).not.toHaveBeenCalled();

            global.jQuery.fn.autocomplete = jest.fn();
            jest.advanceTimersByTime(100);
            expect(callback).toHaveBeenCalledTimes(1);

            delete global.jQuery.fn.autocomplete;
        });

        test('logs error when timeout expires', () => {
            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
            const callback = jest.fn();

            whenAutocompleteReady(callback, 200, 100);
            jest.advanceTimersByTime(300);

            expect(callback).not.toHaveBeenCalled();
            expect(consoleErrorSpy).toHaveBeenCalledWith('jQuery UI Autocomplete not loaded within timeout period');
            consoleErrorSpy.mockRestore();
        });

        test('does nothing if callback is not a function', () => {
            whenAutocompleteReady(null);
            // No error thrown
        });
    });

    describe('safeAutocomplete', () => {
        beforeEach(() => {
            document.body.innerHTML = '<input id="field1" class="ac" /><input id="field2" class="ac" />';
        });

        test('handles invalid selectors and missing plugin', () => {
            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            expect(safeAutocomplete('')).toBe(false);
            expect(safeAutocomplete('   ')).toBe(false);
            expect(safeAutocomplete(null)).toBe(false);
            expect(safeAutocomplete(undefined)).toBe(false);

            // Autocomplete not loaded yet
            expect(safeAutocomplete('.ac')).toBe(false);
            expect(consoleErrorSpy).toHaveBeenCalledWith('jQuery UI Autocomplete is not available');
            consoleErrorSpy.mockRestore();
        });

        test('initializes elements when available', () => {
            const mockAutocomplete = jest.fn();
            global.jQuery.fn.autocomplete = mockAutocomplete;

            const res = safeAutocomplete('.ac', { source: ['Apple', 'Banana'] });
            expect(res).toBe(true);
            expect(mockAutocomplete).toHaveBeenCalledTimes(2);
            expect(mockAutocomplete).toHaveBeenNthCalledWith(1, { source: ['Apple', 'Banana'] });
            expect(mockAutocomplete).toHaveBeenNthCalledWith(2, { source: ['Apple', 'Banana'] });
            expect(mockAutocomplete.mock.contexts.map((context) => context[0])).toEqual([
                document.getElementById('field1'),
                document.getElementById('field2'),
            ]);

            // Returns false when no elements match
            expect(safeAutocomplete('#nonexistent')).toBe(false);

            delete global.jQuery.fn.autocomplete;
        });
    });

    describe('logJQueryStatus', () => {
        test('logs and returns complete status object', () => {
            const consoleLogSpy = jest.spyOn(console, 'log').mockImplementation(() => {});
            global.jQuery.ui = { version: '1.13.2' };
            global.jQuery.fn.autocomplete = jest.fn();

            const status = logJQueryStatus();

            expect(status).toEqual({
                jQueryLoaded: true,
                jQueryVersion: $.fn.jquery,
                jQueryUILoaded: true,
                jQueryUIVersion: '1.13.2',
                autocompleteAvailable: true
            });
            expect(consoleLogSpy).toHaveBeenCalledWith('jQuery Status:', status);

            delete global.jQuery.ui;
            delete global.jQuery.fn.autocomplete;
            consoleLogSpy.mockRestore();
        });

        test('handles missing jQuery safely', () => {
            const consoleLogSpy = jest.spyOn(console, 'log').mockImplementation(() => {});
            delete global.jQuery;
            delete window.jQuery;

            const status = logJQueryStatus();

            expect(status).toEqual({
                jQueryLoaded: false,
                jQueryVersion: null,
                jQueryUILoaded: false,
                jQueryUIVersion: null,
                autocompleteAvailable: false
            });

            consoleLogSpy.mockRestore();
        });
    });
});
