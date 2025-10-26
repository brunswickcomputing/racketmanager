// ------- UI helpers (moved to src/js/features/payments) -------

export function showMessage(messageText) {
    const messageContainer = document.querySelector("#payment-message");

    if (!messageContainer) {
        // Fallback: console log if container is missing
        try { console.warn('payment-message container not found'); } catch(_) {}
        return;
    }

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
