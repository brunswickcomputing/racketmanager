
/**
 * Browser back/forward button handler
 */

export function initializePopstateHandler() {
    globalThis.addEventListener("popstate", (event) => {
        if (event.state) {
            jQuery('#pageContentTab').html(event.state);
        }
    });
}
