// payment-complete.js
// Handle Stripe return (payment completion status) within main src/js modules

// Lightweight local alert helper tailored to the payment-complete template
function showAlert(message, type = 'info') {
  const alertBox = document.getElementById('paymentAlert');
  const alertText = document.getElementById('paymentAlertResponse');
  if (!alertBox || !alertText) return;

  // Remove previous state classes
  alertBox.classList.remove('alert--success', 'alert--info', 'alert--warning', 'alert--danger');

  const classMap = {
    success: 'alert--success',
    warning: 'alert--warning',
    danger: 'alert--danger',
    info: 'alert--info',
  };
  alertBox.classList.add(classMap[type] || 'alert--info');

  alertText.innerHTML = message;
  alertBox.style.display = '';
}

export function initializeStripePaymentComplete() {
  // Only run on the payment complete page (has #paymentAlert and Stripe publishable key input)
  const alertBox = document.getElementById('paymentAlert');
  const publishableKeyEl = document.getElementById('api_publishable_key');
  if (!alertBox || !publishableKeyEl || !publishableKeyEl.value) return;

  // Ensure Stripe.js is available
  if (typeof Stripe !== 'function') {
    // Defer a bit in case the script is late
    setTimeout(initializeStripePaymentComplete, 50);
    return;
  }

  let stripe;
  try {
    stripe = Stripe(publishableKeyEl.value);
  } catch (e) {
    console.error('Failed to initialize Stripe:', e);
    showAlert('Payment system failed to initialize.', 'danger');
    return;
  }

  const params = new URLSearchParams(globalThis.location.search);
  const clientSecret = params.get('payment_intent_client_secret');
  if (!clientSecret) {
    // Not a return from Stripe; nothing to do
    return;
  }

  // Retrieve PaymentIntent status and show appropriate message
  stripe
    .retrievePaymentIntent(clientSecret)
    .then(({ paymentIntent, error }) => {
      if (error || !paymentIntent) {
        showAlert('Something went wrong with payment retrieval.', 'danger');
        return;
      }
      switch (paymentIntent.status) {
        case 'succeeded':
          showAlert('Payment succeeded', 'success');
          break;
        case 'processing':
          showAlert('Your payment is processing.', 'warning');
          break;
        case 'requires_payment_method':
          showAlert('Your payment was not successful, please try again.', 'danger');
          break;
        default:
          showAlert('Unknown status: ' + paymentIntent.status, 'danger');
          break;
      }
    })
    .catch((err) => {
      console.error('Stripe error:', err);
      showAlert('An unexpected error occurred.', 'danger');
    });
}
