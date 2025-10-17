// ------- UI helpers -------

export function showMessage(messageText) {
    const messageContainer = document.querySelector("#payment-message");

    messageContainer.classList.remove("hidden");
    messageContainer.textContent = messageText;
    setTimeout(function () {
        messageContainer.classList.add("hidden");
        messageContainer.textContent = "";
    }, 4000);
}

// Show a spinner on payment submission
export function setLoading(isLoading) {
    let paymentBlock = jQuery('#payment-block');
    if (isLoading) {
        jQuery(paymentBlock).addClass("is-loading");
    } else {
        jQuery(paymentBlock).removeClass("is-loading");
    }
}
