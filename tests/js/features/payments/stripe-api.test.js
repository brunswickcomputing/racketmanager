/**
 * @jest-environment jsdom
 */

import { createPaymentRequest } from '../../../../src/js/features/payments/stripe-api.js';

describe('features/payments/stripe-api', () => {
    const originalFetch = globalThis.fetch;
    const originalAjaxVar = window.ajax_var;

    beforeEach(() => {
        jest.clearAllMocks();
        window.ajax_var = {
            url: '/wp-admin/admin-ajax.php',
            ajax_nonce: 'test-nonce'
        };
        window.jQuery = window.$ = require('jquery');
    });

    afterEach(() => {
        if (originalFetch !== undefined) {
            globalThis.fetch = originalFetch;
        } else {
            delete globalThis.fetch;
        }
        window.ajax_var = originalAjaxVar;
        jest.restoreAllMocks();
    });

    describe('callback mode (legacy compatibility)', () => {
        test('calls callback with client secret on jQuery AJAX success and passes security nonce', (done) => {
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({ data: 'pi_secret_cb_123' });
                opts.complete();
            });

            createPaymentRequest('entry-1', 'inv-1', 'tourn-1', 'player-1', (result) => {
                try {
                    expect(result).toBe('pi_secret_cb_123');
                    expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                        url: '/wp-admin/admin-ajax.php',
                        data: expect.objectContaining({
                            tournament_entry: 'entry-1',
                            invoiceId: 'inv-1',
                            tournamentId: 'tourn-1',
                            tournament_id: 'tourn-1',
                            playerId: 'player-1',
                            action: 'racketmanager_tournament_payment_create',
                            security: 'test-nonce'
                        })
                    }));
                    done();
                } catch (e) {
                    done(e);
                }
            });
        });

        test('calls callback with error message on jQuery AJAX failure with responseJSON', (done) => {
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.error({ responseJSON: { data: 'Payment creation failed' } });
                opts.complete();
            });

            createPaymentRequest('entry-1', 'inv-1', 'tourn-1', 'player-1', (result) => {
                try {
                    expect(result).toBe('Payment creation failed');
                    done();
                } catch (e) {
                    done(e);
                }
            });
        });

        test('calls callback with statusText or fallback default message when error has no responseJSON', (done) => {
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.error({ statusText: 'Bad Request' });
                opts.complete();
            });

            createPaymentRequest('entry-1', 'inv-1', null, null, (result) => {
                try {
                    expect(result).toBe('Bad Request');
                    done();
                } catch (e) {
                    done(e);
                }
            });
        });

        test('calls callback with default error message if statusText is missing', (done) => {
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.error({});
                opts.complete();
            });

            createPaymentRequest('entry-1', 'inv-1', null, null, (result) => {
                try {
                    expect(result).toBe('Network error creating payment.');
                    done();
                } catch (e) {
                    done(e);
                }
            });
        });

        test('invokes callback immediately with error if config/ajax url is missing', (done) => {
            delete window.ajax_var;
            const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

            // In our ajax-config.js, missing ajax_var returns default fallback '/wp-admin/admin-ajax.php'
            // To simulate missing ajax url, we can mock getAjaxUrl or set window.ajax_var = { url: '' }
            window.ajax_var = { url: '', ajax_nonce: '' };

            createPaymentRequest('entry-1', 'inv-1', null, null, (result) => {
                try {
                    expect(result).toBe('Ajax configuration is missing.');
                    done();
                } catch (e) {
                    done(e);
                }
            });
        });
    });

    describe('promise mode (modern modules)', () => {
        test('throws error if ajax URL is missing', async () => {
            window.ajax_var = { url: '', ajax_nonce: '' };

            await expect(createPaymentRequest('entry-x', 'inv-x', null, null))
                .rejects.toThrow('Ajax configuration is missing. Please reload the page.');
        });

        test('resolves with client_secret on fetch success and includes security nonce in form body', async () => {
            globalThis.fetch = jest.fn().mockResolvedValue({
                json: jest.fn().mockResolvedValue({
                    success: true,
                    data: 'pi_secret_modern_456'
                })
            });

            const result = await createPaymentRequest('entry-2', 'inv-2', 'tourn-2', 'player-2');
            expect(result).toBe('pi_secret_modern_456');
            expect(globalThis.fetch).toHaveBeenCalledWith('/wp-admin/admin-ajax.php', expect.objectContaining({
                method: 'POST',
                body: expect.stringContaining('security=test-nonce')
            }));
        });

        test('handles fetch returning {success: false, data: "string message"} and falls back to jQuery AJAX', async () => {
            globalThis.fetch = jest.fn().mockResolvedValue({
                json: jest.fn().mockResolvedValue({
                    success: false,
                    data: 'Server refused payment creation'
                })
            });

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({ success: true, data: 'pi_secret_from_jquery' });
            });

            const result = await createPaymentRequest('entry-fallback', 'inv-fallback', null, null);
            expect(result).toBe('pi_secret_from_jquery');
            expect(spyAjax).toHaveBeenCalled();
        });

        test('handles fetch returning {success: false, data: {message: "msg"}} and falls back to jQuery AJAX', async () => {
            globalThis.fetch = jest.fn().mockResolvedValue({
                json: jest.fn().mockResolvedValue({
                    success: false,
                    data: { message: 'Object error message' }
                })
            });

            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({ success: true, data: 'pi_secret_from_jquery_2' });
            });

            const result = await createPaymentRequest('entry-fallback', 'inv-fallback', null, null);
            expect(result).toBe('pi_secret_from_jquery_2');
            expect(spyAjax).toHaveBeenCalled();
        });

        test('falls back to jQuery AJAX when fetch rejects and jQuery succeeds', async () => {
            globalThis.fetch = jest.fn().mockRejectedValue(new Error('Fetch network error'));
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({ success: true, data: 'pi_secret_fallback_789' });
            });

            const result = await createPaymentRequest('entry-3', 'inv-3', 'tourn-3', 'player-3');
            expect(result).toBe('pi_secret_fallback_789');
            expect(spyAjax).toHaveBeenCalledWith(expect.objectContaining({
                data: expect.objectContaining({
                    security: 'test-nonce'
                })
            }));
        });

        test('jQuery fallback rejects when response has success: false', async () => {
            globalThis.fetch = jest.fn().mockRejectedValue(new Error('Fetch network error'));
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.success({ success: false, data: 'Invalid invoice reference' });
            });

            await expect(createPaymentRequest('entry-3', 'inv-3', null, null))
                .rejects.toThrow('Invalid invoice reference');
        });

        test('jQuery fallback rejects on error with responseJSON', async () => {
            globalThis.fetch = jest.fn().mockRejectedValue(new Error('Fetch network error'));
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.error({ responseJSON: { data: 'XHR error message' } });
            });

            await expect(createPaymentRequest('entry-3', 'inv-3', null, null))
                .rejects.toThrow('XHR error message');
        });

        test('jQuery fallback rejects on error with statusText or fallback message', async () => {
            globalThis.fetch = jest.fn().mockRejectedValue(new Error('Fetch network error'));
            const spyAjax = jest.spyOn(jQuery, 'ajax').mockImplementation((opts) => {
                opts.error({ statusText: 'Gateway Timeout' });
            });

            await expect(createPaymentRequest('entry-3', 'inv-3', null, null))
                .rejects.toThrow('Gateway Timeout');
        });

        test('rethrows original error when jQuery is unavailable', async () => {
            const originalJQuery = window.jQuery;
            delete window.jQuery;
            delete window.$;

            globalThis.fetch = jest.fn().mockRejectedValue(new Error('Fetch failed and no jQuery'));

            await expect(createPaymentRequest('entry-3', 'inv-3', null, null))
                .rejects.toThrow('Fetch failed and no jQuery');

            window.jQuery = window.$ = originalJQuery;
        });
    });
});
