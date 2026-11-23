import {
    sanitizeUrlParam
} from '../../../src/js/utils/url-helpers';

describe('url-helpers', () => {
    describe('sanitizeUrlParam', () => {
        test('replaces hyphens with underscores and spaces with hyphens by default', () => {
            expect(sanitizeUrlParam('Mixed Doubles - Open')).toBe('Mixed-Doubles-_-Open');
        });

        test('keeps hyphens when hyphens flag is false', () => {
            expect(sanitizeUrlParam('Mixed Doubles - Open', false)).toBe('Mixed-Doubles---Open');
        });

        test('returns empty string for empty, null and undefined input', () => {
            expect(sanitizeUrlParam('')).toBe('');
            expect(sanitizeUrlParam(null)).toBe('');
            expect(sanitizeUrlParam(undefined)).toBe('');
        });

        test('converts non-string input to string', () => {
            expect(sanitizeUrlParam(123)).toBe('123');
        });

        test('strips special characters', () => {
            expect(sanitizeUrlParam('Men!@#s Singles')).toBe('Mens-Singles');
        });

        test('collapses multiple spaces into a single hyphen', () => {
            expect(sanitizeUrlParam('Ladies    Doubles')).toBe('Ladies-Doubles');
        });
    });
});
