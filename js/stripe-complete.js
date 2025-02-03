function setPaymentDetails(intent) {
	let alertBox = '#paymentAlert';
	jQuery(alertBox).hide();
	jQuery(alertBox).removeClass('alert--success alert--info alert--warning alert--danger');
	let alertText = '#paymentAlertResponse';
	let statusText = 'Something went wrong, please try again.';
	let className = 'alert--danger';
	if (intent) {
		switch (intent.status) {
			case "succeeded":
				statusText = "Payment succeeded";
				className = 'alert--success';
				Racketmanager.setPaymentStatus(intent.id);
				break;
			case "processing":
				statusText = "Your payment is processing.";
				className = 'alert--warning';
				break;
			case "requires_payment_method":
				statusText = "Your payment was not successful, please try again.";
				className = 'alert--danger';
				break;
			default:
				break;
		}
	}
	jQuery(alertBox).addClass(className);
	jQuery(alertText).html(statusText);
	jQuery(alertBox).show();
}
// Stripe.js instance
api_publishable_key = jQuery('#api_publishable_key').val();
const stripe = Stripe(api_publishable_key);

checkStatus();

// Fetches the payment intent status after payment submission
async function checkStatus() {
	const clientSecret = new URLSearchParams(window.location.search).get("payment_intent_client_secret");
	if (!clientSecret) {
		setErrorState();
		return;
	}
	const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);
	setPaymentDetails(paymentIntent);
}

