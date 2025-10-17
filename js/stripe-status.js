// stripe-status.js
import { showAlert } from './alerts.js';

// Initialize Stripe
const api_publishable_key = jQuery('#api_publishable_key').val();
const stripe = Stripe(api_publishable_key);

// Public function to run the status check
export async function checkStripePaymentStatus() {
    const clientSecret = new URLSearchParams(globalThis.location.search).get("payment_intent_client_secret");

    if (!clientSecret) {
        showAlert("Could not retrieve payment status. Please contact support.", "danger");
        return;
    }

    try {
        const { paymentIntent, error } = await stripe.retrievePaymentIntent(clientSecret);

        if (error || !paymentIntent) {
            showAlert("Something went wrong with payment retrieval.", "danger");
            return;
        }

        handlePaymentStatus(paymentIntent);
    } catch (err) {
        console.error("Stripe error:", err);
        showAlert("An unexpected error occurred.", "danger");
    }
}

function handlePaymentStatus(intent) {
    switch (intent.status) {
        case "succeeded":
            showAlert("Payment succeeded", "success");
            break;
        case "processing":
            showAlert("Your payment is processing.", "warning");
            break;
        case "requires_payment_method":
            showAlert("Your payment was not successful, please try again.", "danger");
            break;
        default:
            showAlert("Unknown status: " + intent.status, "danger");
            break;
    }
}
