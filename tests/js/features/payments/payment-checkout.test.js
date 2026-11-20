/**
 * @jest-environment jsdom
 */

describe('features/payments/payment-checkout', () => {
    let mockStripe;
    let mockElements;
    let mockPaymentElement;
    let paymentCheckoutModule;
    let stripeApiModule;

    beforeEach(() => {
        document.body.innerHTML = '';
        jest.clearAllMocks();
        window.jQuery = window.$ = require('jquery');

        mockPaymentElement = {
            mount: jest.fn()
        };
        mockElements = {
            create: jest.fn().mockReturnValue(mockPaymentElement)
        };
        mockStripe = {
            elements: jest.fn().mockReturnValue(mockElements),
            confirmPayment: jest.fn().mockResolvedValue({})
        };
        window.Stripe = jest.fn().mockReturnValue(mockStripe);

        jest.resetModules();
        stripeApiModule = require('../../../../src/js/features/payments/stripe-api.js');
        paymentCheckoutModule = require('../../../../src/js/features/payments/payment-checkout.js');
    });

    afterEach(() => {
        delete window.Stripe;
        jest.restoreAllMocks();
    });

    test('exits early if neither checkout button nor payment form exist', () => {
        paymentCheckoutModule.initializeTournamentCheckout();
        expect(window.Stripe).not.toHaveBeenCalled();
    });

    test('handles Stripe initialization throwing an error', () => {
        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        document.body.innerHTML = `
            <input id="api_publishable_key" value="pk_invalid" />
            <button id="checkout-cc-button">Pay Now</button>
            <div id="payment-message" class="hidden"></div>
        `;
        window.Stripe.mockImplementationOnce(() => {
            throw new Error('Stripe init error');
        });

        paymentCheckoutModule.initializeTournamentCheckout();

        expect(consoleErrorSpy).toHaveBeenCalledWith('Failed to initialize Stripe:', expect.any(Error));

        const checkoutBtn = document.getElementById('checkout-cc-button');
        checkoutBtn.click();

        expect(document.getElementById('payment-message').textContent).toBe('Payment system failed to initialize.');
    });

    test('handles checkout click when stripe is not initialized ("Payment system failed to initialize.")', () => {
        document.body.innerHTML = `
            <button id="checkout-cc-button">Pay Now</button>
            <div id="payment-message" class="hidden"></div>
        `;

        paymentCheckoutModule.initializeTournamentCheckout();

        const checkoutBtn = document.getElementById('checkout-cc-button');
        checkoutBtn.click();

        expect(document.getElementById('payment-message').textContent).toBe('Payment system failed to initialize.');
    });

    test('initializes Stripe, mounts payment element, and handles form submit', async () => {
        document.body.innerHTML = `
            <input id="api_publishable_key" value="pk_test_123" />
            <button id="checkout-cc-button">Pay Now</button>
            <form id="payment-form" style="display: none;">
                <div id="payment-element"></div>
                <input id="tournamentEntryId" value="entry-1" />
                <input id="tournamentId" value="tourn-10" />
                <input id="playerId" value="player-9" />
                <input id="invoiceId" value="inv-5" />
                <input id="playerName" value="Alice" />
                <input id="playerEmail" value="alice@example.com" />
                <input id="playerContactNo" value="0712345678" />
                <input id="paymentCompleteUrl" value="http://localhost/complete" />
                <button type="submit" id="submit-btn">Confirm</button>
            </form>
            <div id="payment-message" class="hidden"></div>
        `;

        jest.spyOn(stripeApiModule, 'createPaymentRequest').mockResolvedValue('pi_secret_123');

        paymentCheckoutModule.initializeTournamentCheckout();

        expect(window.Stripe).toHaveBeenCalledWith('pk_test_123');

        const checkoutBtn = document.getElementById('checkout-cc-button');
        checkoutBtn.click();

        await new Promise(resolve => setTimeout(resolve, 20));

        expect(stripeApiModule.createPaymentRequest).toHaveBeenCalledWith('entry-1', 'inv-5', 'tourn-10', 'player-9');
        expect(mockStripe.elements).toHaveBeenCalledWith({ clientSecret: 'pi_secret_123' });
        expect(mockElements.create).toHaveBeenCalledWith('payment', expect.objectContaining({
            layout: 'accordion',
            defaultValues: {
                billingDetails: {
                    name: 'Alice',
                    email: 'alice@example.com',
                    phone: '0712345678'
                }
            }
        }));
        expect(mockPaymentElement.mount).toHaveBeenCalledWith('#payment-element');
        expect(document.getElementById('payment-form').style.display).not.toBe('none');

        // Form submit - validation error case
        mockStripe.confirmPayment.mockResolvedValueOnce({
            error: {
                type: 'validation_error',
                message: 'Invalid card details'
            }
        });

        const paymentForm = document.getElementById('payment-form');
        paymentForm.dispatchEvent(new Event('submit', { cancelable: true }));

        await new Promise(resolve => setTimeout(resolve, 20));

        expect(mockStripe.confirmPayment).toHaveBeenCalledWith(expect.objectContaining({
            elements: mockElements,
            confirmParams: {
                return_url: 'http://localhost/complete'
            }
        }));
        expect(document.getElementById('payment-message').textContent).toBe('Invalid card details');
    });

    test('handles card_error on payment confirmation', async () => {
        document.body.innerHTML = `
            <input id="api_publishable_key" value="pk_test_123" />
            <button id="checkout-cc-button">Pay Now</button>
            <form id="payment-form" style="display: none;">
                <div id="payment-element"></div>
                <input id="paymentCompleteUrl" value="http://localhost/complete" />
                <button type="submit" id="submit-btn">Confirm</button>
            </form>
            <div id="payment-message" class="hidden"></div>
        `;

        jest.spyOn(stripeApiModule, 'createPaymentRequest').mockResolvedValue('pi_secret_123');

        paymentCheckoutModule.initializeTournamentCheckout();
        document.getElementById('checkout-cc-button').click();
        await new Promise(resolve => setTimeout(resolve, 20));

        mockStripe.confirmPayment.mockResolvedValueOnce({
            error: {
                type: 'card_error',
                message: 'Your card was declined.'
            }
        });

        const paymentForm = document.getElementById('payment-form');
        paymentForm.dispatchEvent(new Event('submit', { cancelable: true }));
        await new Promise(resolve => setTimeout(resolve, 20));

        expect(document.getElementById('payment-message').textContent).toBe('Your card was declined.');
    });

    test('handles non-card/validation error (unexpected error) on payment confirmation', async () => {
        document.body.innerHTML = `
            <input id="api_publishable_key" value="pk_test_123" />
            <button id="checkout-cc-button">Pay Now</button>
            <form id="payment-form" style="display: none;">
                <div id="payment-element"></div>
                <input id="paymentCompleteUrl" value="http://localhost/complete" />
                <button type="submit" id="submit-btn">Confirm</button>
            </form>
            <div id="payment-message" class="hidden"></div>
        `;

        jest.spyOn(stripeApiModule, 'createPaymentRequest').mockResolvedValue('pi_secret_123');

        paymentCheckoutModule.initializeTournamentCheckout();
        document.getElementById('checkout-cc-button').click();
        await new Promise(resolve => setTimeout(resolve, 20));

        mockStripe.confirmPayment.mockResolvedValueOnce({
            error: {
                type: 'api_connection_error',
                message: 'Connection failed'
            }
        });

        const paymentForm = document.getElementById('payment-form');
        paymentForm.dispatchEvent(new Event('submit', { cancelable: true }));
        await new Promise(resolve => setTimeout(resolve, 20));

        expect(document.getElementById('payment-message').textContent).toBe('An unexpected error occurred.');
    });

    test('submitting form before elements exist displays "Payment is not ready yet."', async () => {
        document.body.innerHTML = `
            <input id="api_publishable_key" value="pk_test_123" />
            <form id="payment-form">
                <button type="submit" id="submit-btn">Confirm</button>
            </form>
            <div id="payment-message" class="hidden"></div>
        `;

        paymentCheckoutModule.initializeTournamentCheckout();

        const paymentForm = document.getElementById('payment-form');
        paymentForm.dispatchEvent(new Event('submit', { cancelable: true }));

        expect(document.getElementById('payment-message').textContent).toBe('Payment is not ready yet.');
    });

    test('handles successful confirmPayment with no error and toggles loading spinner', async () => {
        document.body.innerHTML = `
            <input id="api_publishable_key" value="pk_test_123" />
            <button id="checkout-cc-button">Pay Now</button>
            <form id="payment-form" style="display: none;">
                <div id="payment-element"></div>
                <input id="paymentCompleteUrl" value="http://localhost/complete" />
                <button type="submit" id="submit-btn">
                    <span id="button-text">Pay now</span>
                    <span id="spinner" class="spinner hidden"></span>
                </button>
            </form>
            <div id="payment-message" class="hidden"></div>
        `;

        jest.spyOn(stripeApiModule, 'createPaymentRequest').mockResolvedValue('pi_secret_123');
        mockStripe.confirmPayment.mockResolvedValueOnce({}); // no error

        paymentCheckoutModule.initializeTournamentCheckout();
        document.getElementById('checkout-cc-button').click();
        await new Promise(resolve => setTimeout(resolve, 20));

        const paymentForm = document.getElementById('payment-form');
        paymentForm.dispatchEvent(new Event('submit', { cancelable: true }));
        await new Promise(resolve => setTimeout(resolve, 20));

        // Loading should have been toggled on and off
        expect(mockStripe.confirmPayment).toHaveBeenCalled();
        expect(document.getElementById('spinner').classList.contains('hidden')).toBe(true);
        expect(document.getElementById('submit-btn').disabled).toBe(false);
    });

    test('handles checkout start failure gracefully and re-shows checkout button', async () => {
        const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        document.body.innerHTML = `
            <input id="api_publishable_key" value="pk_test_123" />
            <button id="checkout-cc-button">Pay Now</button>
            <form id="payment-form"></form>
            <div id="payment-message" class="hidden"></div>
        `;

        jest.spyOn(stripeApiModule, 'createPaymentRequest').mockRejectedValue(new Error('Gateway error'));

        paymentCheckoutModule.initializeTournamentCheckout();

        const checkoutBtn = document.getElementById('checkout-cc-button');
        checkoutBtn.click();

        await new Promise(resolve => setTimeout(resolve, 20));

        expect(consoleErrorSpy).toHaveBeenCalled();
        expect(document.getElementById('payment-message').textContent).toBe('Gateway error');
        expect(checkoutBtn.style.display).not.toBe('none');
    });
});
