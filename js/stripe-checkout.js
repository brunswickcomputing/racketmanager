// This is your test publishable API key.
const api_publishable_key = jQuery('#api_publishable_key').val();
let stripe = null;
let elements;
let isInitializing = false;
let messageTimeoutId = null;

function getStripe() {
	if (stripe) {
		return stripe;
	}
	const publishableKey = jQuery('#api_publishable_key').val() || api_publishable_key;
	if (!publishableKey || typeof Stripe !== 'function') {
		return null;
	}
	try {
		stripe = Stripe(publishableKey);
		return stripe;
	} catch (e) {
		console.error('Failed to initialize Stripe:', e);
		stripe = null;
		return null;
	}
}

// Attempt initial Stripe setup if library and key are already loaded
getStripe();

const checkoutBtn = document.getElementById('checkout-cc-button');
const paymentForm = document.getElementById('payment-form');

if (paymentForm) {
	paymentForm.addEventListener('submit', handleSubmit);
}

if (checkoutBtn) {
	checkoutBtn.addEventListener('click', function (e) {
		e.preventDefault();
		if (isInitializing || (paymentForm && paymentForm.style.display !== 'none' && !jQuery(paymentForm).is(':hidden'))) {
			return;
		}
		isInitializing = true;
		checkoutBtn.style.display = 'none';

		if (messageTimeoutId) {
			clearTimeout(messageTimeoutId);
			messageTimeoutId = null;
		}
		const messageContainer = document.querySelector("#payment-message");
		if (messageContainer) {
			messageContainer.classList.add("hidden");
			messageContainer.classList.remove("message-error", "text-center");
			messageContainer.style.display = "none";
			messageContainer.textContent = "";
		}

		const stripeInstance = getStripe();
		if (!stripeInstance) {
			showMessage('Payment system failed to initialize.');
			checkoutBtn.style.display = '';
			isInitializing = false;
			return;
		}

		initialize(checkoutBtn);
	});
} else {
	// Fallback if button is not present
	initialize();
}

// Fetches a payment intent and captures the client secret
async function initialize(btn) {
	const stripeInstance = getStripe();
	if (!stripeInstance) {
		showMessage('Payment system failed to initialize.');
		if (btn) {
			btn.style.display = '';
		}
		isInitializing = false;
		return;
	}

	if (typeof createPaymentRequest !== 'function') {
		showMessage('Payment service is unavailable.');
		if (btn) {
			btn.style.display = '';
		}
		isInitializing = false;
		return;
	}

	let tournamentEntry = jQuery('#tournamentEntryId').val();
	let invoiceId = jQuery('#invoiceId').val();

	createPaymentRequest(tournamentEntry, invoiceId, function (output) {
		if (output && typeof output === 'string' && /^pi_[A-Za-z0-9_]+_secret_[A-Za-z0-9_]+$/.test(output)) {
			createPaymentBlock(output, btn);
		} else {
			let errorMsg = 'Unable to start payment.';
			if (typeof output === 'string' && output.trim()) {
				errorMsg = output;
			} else if (output && typeof output.error === 'string' && output.error.trim()) {
				errorMsg = output.error;
			} else if (output && typeof output.message === 'string' && output.message.trim()) {
				errorMsg = output.message;
			}
			showMessage(errorMsg);
			if (btn) {
				btn.style.display = '';
			}
			isInitializing = false;
		}
	});
}

function createPaymentBlock(clientSecret, btn) {
	let paymentFormEl = jQuery('#payment-form');
	let playerName = jQuery('#playerName').val();
	let playerEmail = jQuery('#playerEmail').val();
	let playerContactNo = jQuery('#playerContactNo').val();

	try {
		const stripeInstance = getStripe();
		if (!stripeInstance) {
			throw new Error('Payment system failed to initialize.');
		}

		if (elements && typeof elements.getElement === 'function') {
			try {
				const existingPaymentElement = elements.getElement('payment');
				if (existingPaymentElement) {
					existingPaymentElement.destroy();
				}
			} catch (_) {}
		}
		jQuery('#payment-element').empty();

		elements = stripeInstance.elements({ clientSecret });
		const paymentElementOptions = {
			defaultValues: {
				billingDetails: {
					email: playerEmail,
					name: playerName,
					phone: playerContactNo,
				},
			},
			layout: "accordion",
		};
		const paymentElement = elements.create("payment", paymentElementOptions);

		if (paymentElement && typeof paymentElement.on === 'function') {
			paymentElement.on('loaderror', function (event) {
				console.error('Payment element load error:', event);
				paymentFormEl.hide();
				const errMsg = (event && event.error && event.error.message) ? event.error.message : 'Unable to load payment form.';
				showMessage(errMsg);
				if (btn) {
					btn.style.display = '';
				}
				isInitializing = false;
			});
		}

		paymentElement.mount("#payment-element");

		const messageContainer = document.querySelector("#payment-message");
		const formDom = paymentFormEl[0];
		if (formDom && messageContainer && !formDom.contains(messageContainer)) {
			formDom.appendChild(messageContainer);
		}

		paymentFormEl.show();
	} catch (err) {
		console.error('Failed to mount payment element:', err);
		paymentFormEl.hide();
		showMessage(err && typeof err.message === 'string' ? err.message : 'Unable to load payment form.');
		if (btn) {
			btn.style.display = '';
		}
		isInitializing = false;
	}
}

async function handleSubmit(e) {
	e.preventDefault();
	const stripeInstance = getStripe();
	if (!stripeInstance || !elements) {
		showMessage('Payment is not ready yet.');
		return;
	}
	setLoading(true);
	let paymentCompleteUrl = jQuery('#paymentCompleteUrl').val();
	try {
		const { error } = await stripeInstance.confirmPayment({
			elements,
			confirmParams: {
				// Make sure to change this to your payment completion page
				return_url: paymentCompleteUrl,
			},
		});

		// This point will only be reached if there is an immediate error when
		// confirming the payment. Otherwise, your customer will be redirected to
		// your `return_url`. For some payment methods like iDEAL, your customer will
		// be redirected to an intermediate site first to authorize the payment, then
		// redirected to the `return_url`.
		if (error) {
			if (error.type === "card_error" || error.type === "validation_error") {
				showMessage(error.message);
			} else {
				showMessage("An unexpected error occurred.");
			}
		}
	} catch (err) {
		console.error('Confirm payment error:', err);
		showMessage("An unexpected error occurred.");
	}
	setLoading(false);
}

// ------- UI helpers -------

function showMessage(messageText) {
	const messageContainer = document.querySelector("#payment-message");
	if (!messageContainer) {
		return;
	}

	const paymentForm = document.querySelector("#payment-form");
	if (paymentForm && (paymentForm.style.display === 'none' || jQuery(paymentForm).is(':hidden'))) {
		if (paymentForm.contains(messageContainer)) {
			paymentForm.parentNode.insertBefore(messageContainer, paymentForm);
		}
	}

	if (messageTimeoutId) {
		clearTimeout(messageTimeoutId);
	}

	messageContainer.classList.remove("hidden");
	messageContainer.classList.add("message-error", "text-center");
	messageContainer.style.display = "block";
	messageContainer.textContent = messageText;

	messageTimeoutId = setTimeout(function () {
		messageContainer.classList.add("hidden");
		messageContainer.classList.remove("message-error", "text-center");
		messageContainer.style.display = "none";
		messageContainer.textContent = "";
		messageTimeoutId = null;
	}, 4000);
}

// Show a spinner on payment submission
function setLoading(isLoading) {
	let paymentBlock = jQuery('#payment-block');
	if (isLoading) {
		jQuery(paymentBlock).addClass("is-loading");
	} else {
		jQuery(paymentBlock).removeClass("is-loading");
	}
}
