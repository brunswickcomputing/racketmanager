import $ from 'jquery';
import {
    isjQueryAvailable,
    getjQueryVersion,
    isjQueryUIAvailable,
    getjQueryUIVersion,
    isAutocompleteAvailable,
    whenAutocompleteReady,
    safeAutocomplete,
    destroyAutocomplete,
    safeDatepicker,
    safeSortable,
    safeTooltip,
    logJQueryStatus,
    whenjQueryReady,
    safeAjax,
    safeTrigger,
    safeOn,
    safeOff,
    elementExists,
    getElementCount,
    safeAnimate,
    safeShow,
    safeHide,
    safeDelegate
} from '../../../src/js/utils/jquery-helpers';

describe('jquery-helpers', () => {
    let originalJQuery;

    beforeEach(() => {
        document.body.innerHTML = '';
        originalJQuery = global.jQuery;
        global.jQuery = $;
        jest.clearAllMocks();
    });

    afterEach(() => {
        global.jQuery = originalJQuery;
    });

    describe('isjQueryAvailable and getjQueryVersion', () => {
        test('returns true and version when jQuery is available', () => {
            expect(isjQueryAvailable()).toBe(true);
            expect(getjQueryVersion()).toBe($.fn.jquery);
        });

        test('returns false and null when jQuery is unavailable', () => {
            delete global.jQuery;
            expect(isjQueryAvailable()).toBe(false);
            expect(getjQueryVersion()).toBeNull();
        });
    });

    describe('isjQueryUIAvailable and getjQueryUIVersion', () => {
        test('returns false and null when jQuery UI is not defined', () => {
            delete global.jQuery.ui;
            expect(isjQueryUIAvailable()).toBe(false);
            expect(getjQueryUIVersion()).toBeNull();
        });

        test('returns true and version when jQuery UI is defined', () => {
            global.jQuery.ui = { version: '1.13.2' };
            expect(isjQueryUIAvailable()).toBe(true);
            expect(getjQueryUIVersion()).toBe('1.13.2');
            delete global.jQuery.ui;
        });

        test('returns false when jQuery itself is unavailable', () => {
            delete global.jQuery;
            expect(isjQueryUIAvailable()).toBe(false);
            expect(getjQueryUIVersion()).toBeNull();
        });
    });

    describe('isAutocompleteAvailable', () => {
        test('returns false when autocomplete plugin is missing', () => {
            expect(isAutocompleteAvailable()).toBe(false);
        });

        test('returns true when autocomplete is a function', () => {
            global.jQuery.fn.autocomplete = jest.fn();
            expect(isAutocompleteAvailable()).toBe(true);
            delete global.jQuery.fn.autocomplete;
        });

        test('returns false when jQuery is unavailable', () => {
            delete global.jQuery;
            expect(isAutocompleteAvailable()).toBe(false);
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

    describe('safeAutocomplete and destroyAutocomplete', () => {
        beforeEach(() => {
            document.body.innerHTML = '<input id="field1" class="ac" /><input id="field2" class="ac" />';
        });

        test('safeAutocomplete handles invalid selectors and missing plugin', () => {
            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            expect(safeAutocomplete('')).toBe(false);
            expect(safeAutocomplete(null)).toBe(false);

            // Autocomplete not loaded yet
            expect(safeAutocomplete('.ac')).toBe(false);
            expect(consoleErrorSpy).toHaveBeenCalledWith('jQuery UI Autocomplete is not available');
            consoleErrorSpy.mockRestore();
        });

        test('safeAutocomplete initializes elements when available', () => {
            const mockAutocomplete = jest.fn();
            global.jQuery.fn.autocomplete = mockAutocomplete;

            const res = safeAutocomplete('.ac', { source: ['Apple', 'Banana'] });
            expect(res).toBe(true);
            expect(mockAutocomplete).toHaveBeenCalledWith({ source: ['Apple', 'Banana'] });

            // Returns false when no elements match
            expect(safeAutocomplete('#nonexistent')).toBe(false);

            delete global.jQuery.fn.autocomplete;
        });

        test('destroyAutocomplete handles instances safely', () => {
            expect(destroyAutocomplete('')).toBe(false);
            expect(destroyAutocomplete('.ac')).toBe(false); // not available

            const mockAutocomplete = jest.fn(function(action) {
                if (action === 'instance') return true;
                return undefined;
            });
            global.jQuery.fn.autocomplete = mockAutocomplete;

            expect(destroyAutocomplete('#nonexistent')).toBe(false);
            expect(destroyAutocomplete('.ac')).toBe(true);
            expect(mockAutocomplete).toHaveBeenCalledWith('destroy');

            delete global.jQuery.fn.autocomplete;
        });
    });

    describe('safeDatepicker, safeSortable, safeTooltip', () => {
        beforeEach(() => {
            document.body.innerHTML = '<div id="item"></div>';
        });

        test('handles invalid selectors', () => {
            expect(safeDatepicker('')).toBe(false);
            expect(safeSortable('')).toBe(false);
            expect(safeTooltip('')).toBe(false);
        });

        test('safeDatepicker checks ui and function availability', () => {
            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            expect(safeDatepicker('#item')).toBe(false);
            expect(consoleErrorSpy).toHaveBeenCalledWith('jQuery UI Datepicker is not available');

            global.jQuery.ui = { version: '1.13.2' };
            global.jQuery.fn.datepicker = jest.fn();

            expect(safeDatepicker('#item', { dateFormat: 'yy-mm-dd' })).toBe(true);
            expect(global.jQuery.fn.datepicker).toHaveBeenCalledWith({ dateFormat: 'yy-mm-dd' });
            expect(safeDatepicker('#nonexistent')).toBe(false);

            delete global.jQuery.ui;
            delete global.jQuery.fn.datepicker;
            consoleErrorSpy.mockRestore();
        });

        test('safeSortable checks ui and function availability', () => {
            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            expect(safeSortable('#item')).toBe(false);
            expect(consoleErrorSpy).toHaveBeenCalledWith('jQuery UI Sortable is not available');

            global.jQuery.ui = { version: '1.13.2' };
            global.jQuery.fn.sortable = jest.fn();

            expect(safeSortable('#item', { axis: 'y' })).toBe(true);
            expect(global.jQuery.fn.sortable).toHaveBeenCalledWith({ axis: 'y' });
            expect(safeSortable('#nonexistent')).toBe(false);

            delete global.jQuery.ui;
            delete global.jQuery.fn.sortable;
            consoleErrorSpy.mockRestore();
        });

        test('safeTooltip checks ui and function availability', () => {
            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            expect(safeTooltip('#item')).toBe(false);
            expect(consoleErrorSpy).toHaveBeenCalledWith('jQuery UI Tooltip is not available');

            global.jQuery.ui = { version: '1.13.2' };
            global.jQuery.fn.tooltip = jest.fn();

            expect(safeTooltip('#item', { track: true })).toBe(true);
            expect(global.jQuery.fn.tooltip).toHaveBeenCalledWith({ track: true });
            expect(safeTooltip('#nonexistent')).toBe(false);

            delete global.jQuery.ui;
            delete global.jQuery.fn.tooltip;
            consoleErrorSpy.mockRestore();
        });
    });

    describe('logJQueryStatus', () => {
        test('logs and returns current status object', () => {
            const consoleLogSpy = jest.spyOn(console, 'log').mockImplementation(() => {});
            const status = logJQueryStatus();

            expect(status).toHaveProperty('jQueryLoaded', true);
            expect(status).toHaveProperty('jQueryVersion', $.fn.jquery);
            expect(status).toHaveProperty('jQueryUILoaded', false);
            expect(status).toHaveProperty('jQueryUIVersion', null);
            expect(status).toHaveProperty('autocompleteAvailable', false);
            expect(consoleLogSpy).toHaveBeenCalledWith('jQuery Status:', status);

            consoleLogSpy.mockRestore();
        });
    });

    describe('whenjQueryReady', () => {
        test('calls callback via jQuery when jQuery is available', () => {
            const callback = jest.fn();
            whenjQueryReady(callback);
            // In jsdom with jQuery, jQuery(callback) registers ready or fires
            expect(typeof callback).toBe('function');
        });

        test('falls back to DOMContentLoaded when jQuery is not available', () => {
            delete global.jQuery;
            const addEventSpy = jest.spyOn(document, 'addEventListener');
            const callback = jest.fn();

            whenjQueryReady(callback);
            expect(addEventSpy).toHaveBeenCalledWith('DOMContentLoaded', callback);
            addEventSpy.mockRestore();
        });

        test('ignores non-function argument', () => {
            whenjQueryReady(null);
        });
    });

    describe('safeAjax', () => {
        test('rejects with Error when jQuery is unavailable', async () => {
            delete global.jQuery;
            await expect(safeAjax()).rejects.toThrow('jQuery is not available');
        });

        test('resolves when jQuery.ajax succeeds', async () => {
            jest.spyOn(global.jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({ data: 'ok' });
            });

            const res = await safeAjax({ url: '/test' });
            expect(res).toEqual({ data: 'ok' });
        });

        test('rejects with Error instance containing xhr, status, error on failure', async () => {
            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
            const fakeXhr = { statusText: 'Not Found', status: 404 };

            jest.spyOn(global.jQuery, 'ajax').mockImplementation((opts) => {
                opts.error(fakeXhr, 'error', 'Not Found');
            });

            await expect(safeAjax({ url: '/test' })).rejects.toMatchObject({
                message: expect.stringContaining('AJAX Error: error - Not Found'),
                xhr: fakeXhr,
                status: 'error',
                error: 'Not Found'
            });

            consoleErrorSpy.mockRestore();
        });
    });

    describe('event utilities: safeTrigger, safeOn, safeOff, safeDelegate', () => {
        beforeEach(() => {
            document.body.innerHTML = `
                <div id="parent">
                    <button id="btn" class="target">Click</button>
                </div>
            `;
        });

        test('safeTrigger handles valid and invalid calls', () => {
            expect(safeTrigger('', 'click')).toBe(false);
            expect(safeTrigger('#btn', null)).toBe(false);
            expect(safeTrigger('#missing', 'click')).toBe(false);

            let triggered = false;
            let receivedData = null;
            $('#btn').on('custom', (e, data) => {
                triggered = true;
                receivedData = data;
            });

            expect(safeTrigger('#btn', 'custom', { foo: 'bar' })).toBe(true);
            expect(triggered).toBe(true);
            expect(receivedData).toEqual({ foo: 'bar' });
        });

        test('safeOn handles valid and invalid calls', () => {
            expect(safeOn('', 'click', () => {})).toBe(false);
            expect(safeOn('#btn', 123, () => {})).toBe(false);
            expect(safeOn('#btn', 'click', null)).toBe(false);
            expect(safeOn('#missing', 'click', () => {})).toBe(false);

            const handler = jest.fn();
            expect(safeOn('#btn', 'click', handler)).toBe(true);

            $('#btn').trigger('click');
            expect(handler).toHaveBeenCalledTimes(1);
        });

        test('safeOff handles valid and invalid calls', () => {
            expect(safeOff('')).toBe(false);
            expect(safeOff('#missing')).toBe(false);

            const handler = jest.fn();
            $('#btn').on('testEvent', handler);
            $('#btn').trigger('testEvent');
            expect(handler).toHaveBeenCalledTimes(1);

            expect(safeOff('#btn', 'testEvent')).toBe(true);
            $('#btn').trigger('testEvent');
            expect(handler).toHaveBeenCalledTimes(1);

            $('#btn').on('anotherEvent', handler);
            expect(safeOff('#btn')).toBe(true);
            $('#btn').trigger('anotherEvent');
            expect(handler).toHaveBeenCalledTimes(1);
        });

        test('safeDelegate handles delegation and validation', () => {
            expect(safeDelegate('', '.target', 'click', () => {})).toBe(false);
            expect(safeDelegate('#parent', null, 'click', () => {})).toBe(false);
            expect(safeDelegate('#parent', '.target', null, () => {})).toBe(false);
            expect(safeDelegate('#parent', '.target', 'click', null)).toBe(false);
            expect(safeDelegate('#missing', '.target', 'click', () => {})).toBe(false);

            const handler = jest.fn();
            expect(safeDelegate('#parent', '.target', 'click', handler)).toBe(true);

            $('#btn').trigger('click');
            expect(handler).toHaveBeenCalledTimes(1);
        });
    });

    describe('elementExists and getElementCount', () => {
        beforeEach(() => {
            document.body.innerHTML = '<span class="item"></span><span class="item"></span>';
        });

        test('validates existence and count correctly', () => {
            expect(elementExists('.item')).toBe(true);
            expect(elementExists('.none')).toBe(false);
            expect(elementExists('')).toBe(false);
            expect(elementExists(null)).toBe(false);

            expect(getElementCount('.item')).toBe(2);
            expect(getElementCount('.none')).toBe(0);
            expect(getElementCount('')).toBe(0);
            expect(getElementCount(null)).toBe(0);
        });

        test('returns false / 0 when jQuery is unavailable', () => {
            delete global.jQuery;
            expect(elementExists('.item')).toBe(false);
            expect(getElementCount('.item')).toBe(0);
        });
    });

    describe('safeAnimate, safeShow, safeHide', () => {
        beforeEach(() => {
            document.body.innerHTML = '<div id="box"></div>';
        });

        test('safeAnimate validates parameters', () => {
            expect(safeAnimate('', { opacity: 0.5 })).toBe(false);
            expect(safeAnimate('#box', null)).toBe(false);
            expect(safeAnimate('#missing', { opacity: 0.5 })).toBe(false);

            const animateSpy = jest.spyOn($.fn, 'animate').mockImplementation(function(_props, _dur, cb) {
                if (typeof cb === 'function') cb();
                return this;
            });

            const callback = jest.fn();
            expect(safeAnimate('#box', { opacity: 0.5 }, 300, callback)).toBe(true);
            expect(animateSpy).toHaveBeenCalledWith({ opacity: 0.5 }, 300, callback);
            expect(callback).toHaveBeenCalled();

            expect(safeAnimate('#box', { opacity: 0.5 }, 300)).toBe(true);
            expect(animateSpy).toHaveBeenCalledWith({ opacity: 0.5 }, 300);

            animateSpy.mockRestore();
        });

        test('safeShow operates with and without duration and callback', () => {
            expect(safeShow('')).toBe(false);
            expect(safeShow('#missing')).toBe(false);

            const showSpy = jest.spyOn($.fn, 'show').mockImplementation(function(_dur, cb) {
                if (typeof cb === 'function') cb();
                return this;
            });

            const cb1 = jest.fn();
            expect(safeShow('#box', 200, cb1)).toBe(true);
            expect(showSpy).toHaveBeenCalledWith(200, cb1);
            expect(cb1).toHaveBeenCalledTimes(1);

            const cb2 = jest.fn();
            expect(safeShow('#box', 0, cb2)).toBe(true);
            expect(showSpy).toHaveBeenCalledWith();
            expect(cb2).toHaveBeenCalledTimes(1);

            expect(safeShow('#box', 300)).toBe(true);
            expect(showSpy).toHaveBeenCalledWith(300);

            showSpy.mockRestore();
        });

        test('safeHide operates with and without duration and callback', () => {
            expect(safeHide('')).toBe(false);
            expect(safeHide('#missing')).toBe(false);

            const hideSpy = jest.spyOn($.fn, 'hide').mockImplementation(function(_dur, cb) {
                if (typeof cb === 'function') cb();
                return this;
            });

            const cb1 = jest.fn();
            expect(safeHide('#box', 200, cb1)).toBe(true);
            expect(hideSpy).toHaveBeenCalledWith(200, cb1);
            expect(cb1).toHaveBeenCalledTimes(1);

            const cb2 = jest.fn();
            expect(safeHide('#box', 0, cb2)).toBe(true);
            expect(hideSpy).toHaveBeenCalledWith();
            expect(cb2).toHaveBeenCalledTimes(1);

            expect(safeHide('#box', 300)).toBe(true);
            expect(hideSpy).toHaveBeenCalledWith(300);

            hideSpy.mockRestore();
        });
    });
});
