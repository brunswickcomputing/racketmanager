import { currencyFormat } from '../../../src/js/utils/currency.js';

describe('currencyFormat', () => {
    const originalCurrencyFormat = globalThis.currencyFormat;
    const originalIntl = globalThis.Intl;

    afterEach(() => {
        if (originalCurrencyFormat !== undefined) {
            globalThis.currencyFormat = originalCurrencyFormat;
        } else {
            delete globalThis.currencyFormat;
        }
        globalThis.Intl = originalIntl;
        jest.restoreAllMocks();
    });

    test('formats valid positive numbers using Intl.NumberFormat by default', () => {
        delete globalThis.currencyFormat;
        const result = currencyFormat(12.5);
        expect(result).toBe('£12.50');
    });

    test('formats string representations of numbers and falsy values', () => {
        delete globalThis.currencyFormat;
        expect(currencyFormat('25.99')).toBe('£25.99');
        expect(currencyFormat('')).toBe('£0.00');
        expect(currencyFormat(null)).toBe('£0.00');
        expect(currencyFormat(undefined)).toBe('£0.00');
        expect(currencyFormat(0)).toBe('£0.00');
    });

    test('delegates to globalThis.currencyFormat when defined', () => {
        globalThis.currencyFormat = jest.fn((num) => `GBP ${num.toFixed(2)}`);
        const result = currencyFormat(42);
        expect(globalThis.currencyFormat).toHaveBeenCalledWith(42);
        expect(result).toBe('GBP 42.00');
    });

    test('logs error and falls back when globalThis.currencyFormat throws', () => {
        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        globalThis.currencyFormat = jest.fn(() => {
            throw new Error('legacy failure');
        });

        const result = currencyFormat(15);
        expect(consoleErrorSpy).toHaveBeenCalled();
        expect(result).toBe('£15.00');
    });

    test('falls back to string formatting when Intl.NumberFormat throws', () => {
        delete globalThis.currencyFormat;
        // Break Intl to trigger catch block
        globalThis.Intl = {
            NumberFormat: jest.fn().mockImplementation(() => {
                throw new Error('Intl unsupported');
            })
        };

        const result = currencyFormat(19.994);
        expect(result).toBe('£19.99');

        const zeroResult = currencyFormat(0);
        expect(zeroResult).toBe('£0.00');
    });
});
