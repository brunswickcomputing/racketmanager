/**
 * @jest-environment jsdom
 */

import { initializeStripePaymentComplete } from '../../../../src/js/features/payments/payment-complete.js';

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
}

describe('features/payments/payment-complete', () => {
    let mockStripe;

    beforeEach(() => {
        document.body.innerHTML = '';
        window.history.pushState({}, '', 'http://localhost/payment-complete/');
        jest.clearAllMocks();
        mockStripe = {
            retrievePaymentIntent: jest.fn().mockResolvedValue({})
        };
        window.Stripe = jest.fn().mockReturnValue(mockStripe);
    });

    afterEach(() => {
        jest.useRealTimers();
        delete window.Stripe;
        jest.restoreAllMocks();
    });

    test('exits early if paymentAlert or publishable key element missing or empty', () => {
        initializeStripePaymentComplete();
        expect(window.Stripe).not.toHaveBeenCalled();

        document.body.innerHTML = `
            <div id="paymentAlert"><div id="paymentAlertResponse"></div></div>
            <input id="api_publishable_key" value="" />
        `;
        initializeStripePaymentComplete();
        expect(window.Stripe).not.toHaveBeenCalled();
    });

    test('defers execution via setTimeout if Stripe is not a function', async () => {
        jest.useFakeTimers();
        delete window.Stripe;

        document.body.innerHTML = `
            <div id="paymentAlert"><div id="paymentAlertResponse"></div></div>
            <input id="api_publishable_key" value="pk_test_123" />
        `;

        initializeStripePaymentComplete();
        expect(jest.getTimerCount()).toBe(1);

        // Define Stripe and run timer
        window.Stripe = jest.fn().mockReturnValue(mockStripe);
        jest.runOnlyPendingTimers();
        await flushPromises();

        expect(window.Stripe).toHaveBeenCalledWith('pk_test_123');
    });

    test('handles Stripe initialization failure', () => {
        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        window.Stripe = jest.fn().mockImplementation(() => {
            throw new Error('Stripe init crash');
        });

        document.body.innerHTML = `
            <div id="paymentAlert" style="display: none;"><div id="paymentAlertResponse"></div></div>
            <input id="api_publishable_key" value="pk_test_123" />
        `;

        initializeStripePaymentComplete();

        expect(consoleErrorSpy).toHaveBeenCalled();
        expect(document.getElementById('paymentAlert').classList.contains('alert--danger')).toBe(true);
        expect(document.getElementById('paymentAlertResponse').textContent).toBe('Payment system failed to initialize.');
        consoleErrorSpy.mockRestore();
    });

    test('exits early if payment_intent_client_secret is absent from URL', () => {
        window.history.pushState({}, '', 'http://localhost/payment-complete/');
        document.body.innerHTML = `
            <div id="paymentAlert"><div id="paymentAlertResponse"></div></div>
            <input id="api_publishable_key" value="pk_test_123" />
        `;

        initializeStripePaymentComplete();

        expect(window.Stripe).toHaveBeenCalledWith('pk_test_123');
        expect(mockStripe.retrievePaymentIntent).not.toHaveBeenCalled();
    });

    test('handles payment retrieval error or missing paymentIntent', async () => {
        window.history.pushState({}, '', 'http://localhost/payment-complete/?payment_intent_client_secret=sec_fail');
        document.body.innerHTML = `
            <div id="paymentAlert" style="display: none;"><div id="paymentAlertResponse"></div></div>
            <input id="api_publishable_key" value="pk_test_123" />
        `;

        mockStripe.retrievePaymentIntent.mockResolvedValueOnce({
            error: { message: 'Failed to retrieve' }
        });

        initializeStripePaymentComplete();
        await flushPromises();

        expect(document.getElementById('paymentAlert').classList.contains('alert--danger')).toBe(true);
        expect(document.getElementById('paymentAlertResponse').textContent).toBe('Something went wrong with payment retrieval.');
    });

    test('handles paymentIntent statuses: succeeded, processing, requires_payment_method, default', async () => {
        const statuses = [
            { status: 'succeeded', alertClass: 'alert--success', msg: 'Payment succeeded' },
            { status: 'processing', alertClass: 'alert--warning', msg: 'Your payment is processing.' },
            { status: 'requires_payment_method', alertClass: 'alert--danger', msg: 'Your payment was not successful, please try again.' },
            { status: 'requires_action', alertClass: 'alert--danger', msg: 'Unknown status: requires_action' }
        ];

        for (const item of statuses) {
            window.history.pushState({}, '', 'http://localhost/payment-complete/?payment_intent_client_secret=sec_' + item.status);
            document.body.innerHTML = `
                <div id="paymentAlert" style="display: none;"><div id="paymentAlertResponse"></div></div>
                <input id="api_publishable_key" value="pk_test_123" />
            `;

            mockStripe.retrievePaymentIntent.mockResolvedValueOnce({
                paymentIntent: { status: item.status }
            });

            initializeStripePaymentComplete();
            await flushPromises();

            expect(document.getElementById('paymentAlert').classList.contains(item.alertClass)).toBe(true);
            expect(document.getElementById('paymentAlertResponse').textContent).toBe(item.msg);
        }
    });

    test('handles promise rejection from retrievePaymentIntent', async () => {
        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        window.history.pushState({}, '', 'http://localhost/payment-complete/?payment_intent_client_secret=sec_err');
        document.body.innerHTML = `
            <div id="paymentAlert" style="display: none;"><div id="paymentAlertResponse"></div></div>
            <input id="api_publishable_key" value="pk_test_123" />
        `;

        mockStripe.retrievePaymentIntent.mockRejectedValueOnce(new Error('Network crash'));

        initializeStripePaymentComplete();
        await flushPromises();

        expect(consoleErrorSpy).toHaveBeenCalled();
        expect(document.getElementById('paymentAlert').classList.contains('alert--danger')).toBe(true);
        expect(document.getElementById('paymentAlertResponse').textContent).toBe('An unexpected error occurred.');
        consoleErrorSpy.mockRestore();
    });
});
