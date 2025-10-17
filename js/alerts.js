export function showAlert(message, type = "info") {
    const alertBox = '#paymentAlert';
    const alertText = '#paymentAlertResponse';

    const classMap = {
        success: 'alert--success',
        warning: 'alert--warning',
        danger: 'alert--danger',
        info: 'alert--info',
    };

    const className = classMap[type] || 'alert--info';

    jQuery(alertBox).hide();
    jQuery(alertBox).removeClass('alert--success alert--info alert--warning alert--danger');
    jQuery(alertBox).addClass(className);
    jQuery(alertText).html(message);
    jQuery(alertBox).show();
}
