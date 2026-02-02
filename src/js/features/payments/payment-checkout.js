// payment-checkout.js
// Stripe checkout initializer wired into the main app entry (src/js/index.js)

import { showMessage, setLoading } from './stripe-helpers.js';
import { createPaymentRequest } from './stripe-api.js';

let stripe = null;
let elements = null;
let initialized = false; // avoid double-binding on ajaxComplete

export function initializeTournamentCheckout() {
  // Only run on pages that contain the checkout button or payment form
  const checkoutBtn = document.getElementById('checkout-cc-button');
  const paymentFormEl = document.getElementById('payment-form');
  const publishableKeyEl = document.getElementById('api_publishable_key');

  if (!checkoutBtn && !paymentFormEl) {
    return; // nothing to do on this page
  }

  // Initialize Stripe once
  if (!stripe && publishableKeyEl && publishableKeyEl.value) {
    try {
      stripe = Stripe(publishableKeyEl.value);
    } catch (e) {
      console.error('Failed to initialize Stripe:', e);
      stripe = null;
    }
  }

  // Bind submit handler once
  if (paymentFormEl && !paymentFormEl.dataset.rmSubmitBound) {
    paymentFormEl.addEventListener('submit', handleSubmit);
    paymentFormEl.dataset.rmSubmitBound = '1';
  }

  // Bind checkout button click once
  if (checkoutBtn && !checkoutBtn.dataset.rmClickBound) {
    checkoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      if (!stripe) {
        showMessage('Payment system failed to initialize.');
        return;
      }
      // Hide the button to prevent duplicate inits
      checkoutBtn.style.display = 'none';
      await initialize(checkoutBtn);
    });
    checkoutBtn.dataset.rmClickBound = '1';
  }

  initialized = true;
}

async function initialize(checkoutBtn) {
  try {
    const tournamentEntry = jQuery('#tournamentEntryId').val();
    const tournamentId = jQuery('#tournamentId').val();
    const playerId = jQuery('#playerId').val();
    const invoiceId = jQuery('#invoiceId').val();
    const clientSecret = await createPaymentRequest(tournamentEntry, invoiceId, tournamentId, playerId);
    createPaymentBlock(clientSecret);
  } catch (err) {
    console.error(err);
    showMessage(typeof err?.message === 'string' ? err.message : 'Unable to start payment.');
    // Re-show the button so the user can retry
    if (checkoutBtn) checkoutBtn.style.display = '';
  }
}

function createPaymentBlock(clientSecret) {
  const paymentForm = jQuery('#payment-form');
  const playerName = jQuery('#playerName').val();
  const playerEmail = jQuery('#playerEmail').val();
  const playerContactNo = jQuery('#playerContactNo').val();
  elements = stripe.elements({ clientSecret });
  const paymentElementOptions = {
    defaultValues: {
      billingDetails: {
        email: playerEmail,
        name: playerName,
        phone: playerContactNo,
      },
    },
    layout: 'accordion',
  };
  const paymentElement = elements.create('payment', paymentElementOptions);
  paymentElement.mount('#payment-element');
  jQuery(paymentForm).show();
}

async function handleSubmit(e) {
  e.preventDefault();
  if (!stripe || !elements) {
    showMessage('Payment is not ready yet.');
    return;
  }
  setLoading(true);
  const paymentCompleteUrl = jQuery('#paymentCompleteUrl').val();
  const { error } = await stripe.confirmPayment({
    elements,
    confirmParams: {
      return_url: paymentCompleteUrl,
    },
  });

  if (error && (error.type === 'card_error' || error.type === 'validation_error')) {
    showMessage(error.message);
  } else if (error) {
    showMessage('An unexpected error occurred.');
  }
  setLoading(false);
}
