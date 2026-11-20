import { capitalize, escapeHtml, reverse, isEmpty } from '../../../src/js/utils/string-utils.js';

describe('string-utils', () => {
    describe('capitalize', () => {
        test('capitalizes first letter and lowercases remaining characters', () => {
            expect(capitalize('hello')).toBe('Hello');
            expect(capitalize('WORLD')).toBe('World');
            expect(capitalize('rACKET')).toBe('Racket');
            expect(capitalize('a')).toBe('A');
        });

        test('returns empty string for falsy/empty values', () => {
            expect(capitalize('')).toBe('');
            expect(capitalize(null)).toBe('');
            expect(capitalize(undefined)).toBe('');
        });
    });

    describe('escapeHtml', () => {
        test('escapes HTML special characters', () => {
            const raw = '<script>alert("xss" & \'test\')</script>';
            const escaped = escapeHtml(raw);
            expect(escaped).toContain('&lt;script&gt;');
            expect(escaped).toContain('&amp;');
            expect(escaped).toContain('&lt;/script&gt;');
        });

        test('returns empty string for falsy/empty values', () => {
            expect(escapeHtml('')).toBe('');
            expect(escapeHtml(null)).toBe('');
            expect(escapeHtml(undefined)).toBe('');
        });

        test('returns unchanged string if no html chars are present', () => {
            expect(escapeHtml('plain text 123')).toBe('plain text 123');
        });
    });

    describe('reverse', () => {
        test('reverses strings correctly', () => {
            expect(reverse('tennis')).toBe('sinnet');
            expect(reverse('12345')).toBe('54321');
            expect(reverse('a')).toBe('a');
        });

        test('returns empty string for falsy/empty values', () => {
            expect(reverse('')).toBe('');
            expect(reverse(null)).toBe('');
            expect(reverse(undefined)).toBe('');
        });
    });

    describe('isEmpty', () => {
        test('returns true for null, undefined, empty string, or whitespace-only strings', () => {
            expect(isEmpty('')).toBe(true);
            expect(isEmpty('   ')).toBe(true);
            expect(isEmpty('\t\n ')).toBe(true);
            expect(isEmpty(null)).toBe(true);
            expect(isEmpty(undefined)).toBe(true);
        });

        test('returns false for non-empty strings', () => {
            expect(isEmpty('a')).toBe(false);
            expect(isEmpty(' word ')).toBe(false);
            expect(isEmpty('0')).toBe(false);
        });
    });
});
