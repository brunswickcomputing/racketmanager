// This is your test publishable API key.
api_publishable_key = jQuery('#api_publishable_key').val();
const stripe = Stripe(api_publishable_key);
let elements;
initialize();

document
  .querySelector("#payment-form")
  .addEventListener("submit", handleSubmit);

// Fetches a payment intent and captures the client secret
async function initialize() {
	let tournamentEntry = jQuery('#tournamentEntryId').val();
	let invoiceId = jQuery('#invoiceId').val();
	createPaymentRequest(tournamentEntry, invoiceId, createPaymentBlock);
}
function createPaymentBlock(clientSecret) {
	let paymentForm = jQuery('#payment-form');
	let playerName = jQuery('#playerName').val();
	let playerEmail = jQuery('#playerEmail').val();
	let playerContactNo = jQuery('#playerContactNo').val();
	elements = stripe.elements({ clientSecret });
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
	paymentElement.mount("#payment-element");
	jQuery(paymentForm).show();
}

async function handleSubmit(e) {
	e.preventDefault();
	setLoading(true);
	let paymentCompleteUrl = jQuery('#paymentCompleteUrl').val();
	const { error } = await stripe.confirmPayment({
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
	if (error.type === "card_error" || error.type === "validation_error") {
		showMessage(error.message);
	} else {
		showMessage("An unexpected error occurred.");
	}
	setLoading(false);
}

// ------- UI helpers -------

function showMessage(messageText) {
	const messageContainer = document.querySelector("#payment-message");

	messageContainer.classList.remove("hidden");
	messageContainer.textContent = messageText;
	setTimeout(function () {
		messageContainer.classList.add("hidden");
		messageContainer.textContent = "";
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
