/**
 * Entry form acceptance checkbox handler
 */

export function initializeAcceptanceCheckbox() {
    jQuery("#acceptance").prop("checked", false);
    jQuery("#entrySubmit").hide();

    jQuery('#acceptance').on("change", function () {
        if (this.checked) {
            jQuery("#entrySubmit").show();
        } else {
            jQuery("#entrySubmit").hide();
        }
    });
}
