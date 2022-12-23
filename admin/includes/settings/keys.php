<div class="form-control">
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='recaptchaSiteKey' id='recaptchaSiteKey' value='<?php echo isset($options['keys']['recaptchaSiteKey']) ? $options['keys']['recaptchaSiteKey'] : '' ?>' />
    <label for='recaptchaSiteKey'><?php _e( 'Recaptcha Site Key', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='recaptchaSecretKey' id='recaptchaSecretKey' value='<?php echo isset($options['keys']['recaptchaSecretKey']) ? $options['keys']['recaptchaSecretKey'] : '' ?>' />
    <label for='recaptchaSecretKey'><?php _e( 'Recaptcha Secret Key', 'racketmanager' ) ?></label>
  </div>
</div>
