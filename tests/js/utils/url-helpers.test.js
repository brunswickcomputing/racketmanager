import {
    sanitizeUrlParam,
    buildUrl,
    buildQueryString,
    getCurrentUrl,
    getUrlBase,
    navigateTo,
    reloadPage,
    parseQueryString,
    getQueryParam,
    setQueryParam,
    removeQueryParam,
    slugify,
    isAbsoluteUrl,
    isExternalUrl,
    encodeUrlComponent,
    buildPath,
    getFilenameFromUrl,
    updateUrlWithoutReload,
    replaceUrlWithoutReload
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

    describe('buildQueryString', () => {
        test('drops null and undefined values', () => {
            expect(buildQueryString({ a: 1, b: null, c: undefined, d: 'x y' })).toBe('a=1&d=x%20y');
        });

        test('keeps falsy values that are not null or undefined', () => {
            expect(buildQueryString({ zero: 0, empty: '', flag: false })).toBe('zero=0&empty=&flag=false');
        });

        test('encodes keys and values', () => {
            expect(buildQueryString({ 'a b': 'c&d' })).toBe('a%20b=c%26d');
        });

        test('returns empty string for empty object', () => {
            expect(buildQueryString({})).toBe('');
        });
    });

    describe('buildUrl', () => {
        test('builds URL without query string when there are no params', () => {
            expect(buildUrl('https:', 'example.com', '/path')).toBe('https://example.com/path');
            expect(buildUrl('https:', 'example.com', '/path', { a: null })).toBe('https://example.com/path');
        });

        test('appends query string when params are present', () => {
            expect(buildUrl('https:', 'example.com', '/path', { a: 1, b: 'two' })).toBe('https://example.com/path?a=1&b=two');
        });
    });

    describe('getCurrentUrl and getUrlBase', () => {
        test('returns jsdom location values', () => {
            expect(getCurrentUrl()).toBe(globalThis.location.href);
            expect(getUrlBase()).toBe('http://localhost');
        });
    });

    describe('navigateTo and reloadPage', () => {
        // jsdom's `location` is unforgeable (non-configurable) so it cannot be mocked.
        // jsdom reports every navigation attempt through console.error as
        // "Not implemented: navigation", which proves the call reached `location`.
        let errorSpy;

        beforeEach(() => {
            errorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        });

        afterEach(() => {
            errorSpy.mockRestore();
        });

        const expectNavigationAttempt = () => {
            expect(errorSpy).toHaveBeenCalledTimes(1);
            expect(String(errorSpy.mock.calls[0][0])).toContain('Not implemented: navigation');
        };

        test('navigateTo assigns href by default', () => {
            expect(() => navigateTo('http://localhost/new')).not.toThrow();
            expectNavigationAttempt();
        });

        test('navigateTo uses replace when requested', () => {
            expect(() => navigateTo('http://localhost/replaced', true)).not.toThrow();
            expectNavigationAttempt();
        });

        test('reloadPage calls location.reload', () => {
            expect(() => reloadPage()).not.toThrow();
            expectNavigationAttempt();
        });
    });

    describe('parseQueryString', () => {
        test('parses query string with and without leading ?', () => {
            expect(parseQueryString('?a=1&b=hello%20world&c')).toEqual({ a: '1', b: 'hello world', c: '' });
            expect(parseQueryString('a=1&b=2')).toEqual({ a: '1', b: '2' });
        });

        test('returns empty object for empty or undefined input', () => {
            expect(parseQueryString('')).toEqual({});
            expect(parseQueryString(undefined)).toEqual({});
            expect(parseQueryString(null)).toEqual({});
        });

        test('skips empty segments from trailing or doubled ampersands', () => {
            expect(parseQueryString('a=1&&b=2&')).toEqual({ a: '1', b: '2' });
        });
    });

    describe('getQueryParam, setQueryParam and removeQueryParam', () => {
        const url = 'http://example.com/page?a=1&b=two';

        test('getQueryParam reads from explicit URL', () => {
            expect(getQueryParam('a', url)).toBe('1');
            expect(getQueryParam('b', url)).toBe('two');
            expect(getQueryParam('missing', url)).toBeNull();
        });

        test('getQueryParam defaults to current location', () => {
            expect(getQueryParam('nothing')).toBeNull();
        });

        test('setQueryParam adds or updates a parameter', () => {
            expect(setQueryParam('c', '3', url)).toBe('http://example.com/page?a=1&b=two&c=3');
            expect(setQueryParam('a', 'changed', url)).toBe('http://example.com/page?a=changed&b=two');
        });

        test('setQueryParam defaults to current location', () => {
            expect(setQueryParam('x', 'y')).toBe('http://localhost/?x=y');
        });

        test('removeQueryParam removes a parameter', () => {
            expect(removeQueryParam('a', url)).toBe('http://example.com/page?b=two');
            expect(removeQueryParam('missing', url)).toBe(url);
        });

        test('removeQueryParam defaults to current location', () => {
            expect(removeQueryParam('x')).toBe('http://localhost/');
        });
    });

    describe('slugify', () => {
        test('produces a lower-case hyphenated slug', () => {
            expect(slugify('  Hello   World!! --Test-- ')).toBe('hello-world-test');
        });

        test('collapses runs of hyphens and trims leading/trailing hyphens', () => {
            expect(slugify('--a---b--')).toBe('a-b');
        });

        test('returns empty string for falsy input', () => {
            expect(slugify('')).toBe('');
            expect(slugify(null)).toBe('');
            expect(slugify(undefined)).toBe('');
        });

        test('converts non-string input', () => {
            expect(slugify(42)).toBe('42');
        });
    });

    describe('isAbsoluteUrl', () => {
        test('detects http and https URLs case-insensitively', () => {
            expect(isAbsoluteUrl('http://example.com')).toBe(true);
            expect(isAbsoluteUrl('HTTPS://example.com')).toBe(true);
        });

        test('rejects relative and other-scheme URLs', () => {
            expect(isAbsoluteUrl('/relative/path')).toBe(false);
            expect(isAbsoluteUrl('ftp://example.com')).toBe(false);
            expect(isAbsoluteUrl('')).toBe(false);
        });
    });

    describe('isExternalUrl', () => {
        test('returns true for a different host', () => {
            expect(isExternalUrl('https://example.com/x')).toBe(true);
        });

        test('returns false for the current host', () => {
            expect(isExternalUrl('http://localhost/some/path')).toBe(false);
        });

        test('returns false for relative URLs', () => {
            expect(isExternalUrl('/relative')).toBe(false);
        });

        test('returns false for malformed absolute URLs without throwing', () => {
            expect(isExternalUrl('http://[bad')).toBe(false);
        });
    });

    describe('encodeUrlComponent', () => {
        test('encodes characters not covered by encodeURIComponent', () => {
            expect(encodeUrlComponent("a!b'c(d)e*f")).toBe('a%21b%27c%28d%29e%2Af');
        });

        test('encodes standard characters', () => {
            expect(encodeUrlComponent('a b&c')).toBe('a%20b%26c');
        });
    });

    describe('buildPath', () => {
        test('joins truthy segments in lower case with surrounding slashes', () => {
            expect(buildPath('Leagues', '', 'Summer-2024', null)).toBe('/leagues/summer-2024/');
        });

        test('handles numeric segments', () => {
            expect(buildPath('Season', 2024)).toBe('/season/2024/');
        });

        test('returns double slash for no segments', () => {
            expect(buildPath()).toBe('//');
        });
    });

    describe('getFilenameFromUrl', () => {
        test('extracts the last path segment', () => {
            expect(getFilenameFromUrl('https://h/a/b/file.pdf')).toBe('file.pdf');
        });

        test('returns empty string for falsy input or trailing slash', () => {
            expect(getFilenameFromUrl('')).toBe('');
            expect(getFilenameFromUrl(null)).toBe('');
            expect(getFilenameFromUrl('https://h/a/b/')).toBe('');
        });
    });

    describe('updateUrlWithoutReload and replaceUrlWithoutReload', () => {
        afterEach(() => {
            jest.restoreAllMocks();
        });

        test('updateUrlWithoutReload calls history.pushState', () => {
            const spy = jest.spyOn(globalThis.history, 'pushState').mockImplementation(() => {});
            const state = { page: 2 };

            updateUrlWithoutReload('/new-url', 'New Title', state);

            expect(spy).toHaveBeenCalledWith(state, 'New Title', '/new-url');
        });

        test('updateUrlWithoutReload uses default title and state', () => {
            const spy = jest.spyOn(globalThis.history, 'pushState').mockImplementation(() => {});

            updateUrlWithoutReload('/new-url');

            expect(spy).toHaveBeenCalledWith({}, '', '/new-url');
        });

        test('replaceUrlWithoutReload calls history.replaceState', () => {
            const spy = jest.spyOn(globalThis.history, 'replaceState').mockImplementation(() => {});
            const state = { page: 3 };

            replaceUrlWithoutReload('/replaced-url', 'Replaced', state);

            expect(spy).toHaveBeenCalledWith(state, 'Replaced', '/replaced-url');
        });

        test('replaceUrlWithoutReload uses default title and state', () => {
            const spy = jest.spyOn(globalThis.history, 'replaceState').mockImplementation(() => {});

            replaceUrlWithoutReload('/replaced-url');

            expect(spy).toHaveBeenCalledWith({}, '', '/replaced-url');
        });
    });
});
