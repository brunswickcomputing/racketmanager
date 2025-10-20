/**
 * Password show/hide toggle
 */

export function initializePasswordToggle() {
    jQuery('.passwordShow').hover(
        function () {
            let input = jQuery(this).parent().find('.password');
            input.attr('type', 'text');
        },
        function () {
            let input = jQuery(this).parent().find('.password');
            input.attr('type', 'password');
        }
    );
}
