import { getAjaxConfig, getAjaxUrl, getAjaxNonce } from '../../../src/js/config/ajax-config.js';

describe('ajax-config', () => {
    const originalAjaxVar = window.ajax_var;

    afterEach(() => {
        window.ajax_var = originalAjaxVar;
        jest.restoreAllMocks();
    });

    test('returns window.ajax_var when defined', () => {
        window.ajax_var = {
            url: 'https://example.com/wp-admin/admin-ajax.php',
            ajax_nonce: 'custom-nonce-123'
        };

        const config = getAjaxConfig();
        expect(config).toEqual({
            url: 'https://example.com/wp-admin/admin-ajax.php',
            ajax_nonce: 'custom-nonce-123'
        });
        expect(getAjaxUrl()).toBe('https://example.com/wp-admin/admin-ajax.php');
        expect(getAjaxNonce()).toBe('custom-nonce-123');
    });

    test('logs error and returns default fallback when window.ajax_var is undefined', () => {
        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        delete window.ajax_var;

        const config = getAjaxConfig();
        expect(consoleErrorSpy).toHaveBeenCalledWith(
            'ajax_var is not defined. Check wp_add_inline_script in PHP.'
        );
        expect(config).toEqual({
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: ''
        });
        expect(getAjaxUrl()).toBe('/wp-admin/admin-ajax.php');
        expect(getAjaxNonce()).toBe('');
    });
});
