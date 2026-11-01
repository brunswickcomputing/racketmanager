<?php
declare( strict_types=1 );
/**
 * Keys administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Options_Page_View_Model;

/** @var Options_Page_View_Model|\stdClass $vm */
$options = $options ?? ( $vm->options ?? array() );
?>
<div class="form-control">
    <div class="form-floating mb-3">
        <input type="text" class="form-control" name='googleMapsKey' id='googleMapsKey' value="<?php echo $options['keys']['googleMapsKey'] ?? '' ?>" />
        <label for='googleMapsKey'><?php _e( 'Google Maps Key', 'racketmanager' ) ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="text" class="form-control" name='recaptchaSiteKey' id='recaptchaSiteKey' value="<?php echo $options['keys']['recaptchaSiteKey'] ?? '' ?>" />
        <label for='recaptchaSiteKey'><?php _e( 'Recaptcha Site Key', 'racketmanager' ) ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="text" class="form-control" name='recaptchaSecretKey' id='recaptchaSecretKey' value="<?php echo $options['keys']['recaptchaSecretKey'] ?? '' ?>" />
        <label for='recaptchaSecretKey'><?php _e( 'Recaptcha Secret Key', 'racketmanager' ) ?></label>
    </div>
</div>
