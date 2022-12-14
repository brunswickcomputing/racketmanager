<div class="form-control">
  <div class="form-floating mb-3">
    <select class="form-select" id="billingCurrency" name="billingCurrency">
      <option value="GBP" <?php if (isset($options['billing']['billingCurrency']) && $options['billing']['billingCurrency'] == "GBP") echo 'selected="selected"'?>><?php _e('GBP', 'racketmanager') ?></option>
      <option value="EUR" <?php if (isset($options['billing']['billingCurrency']) && $options['billing']['billingCurrency'] == "EUR") echo 'selected="selected"'?>><?php _e('EURO', 'racketmanager') ?></option>
    </select>
    <label for='billingCurrency'><?php _e( 'Billing Currency', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="email" class="form-control" name='billingEmail' id='billingEmail' value='<?php echo isset($options['billing']['billingEmail']) ? $options['billing']['billingEmail'] : '' ?>' />
    <label for='billingEmail'><?php _e( 'Billing Email Address', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='billingAddress' id='billingAddress' value='<?php echo isset($options['billing']['billingAddress']) ? $options['billing']['billingAddress'] : '' ?>' />
    <label for='billingAddress'><?php _e( 'Billing Address', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="tel" class="form-control" name='billingTelephone' id='billingTelephone' value='<?php echo isset($options['billing']['billingTelephone']) ? $options['billing']['billingTelephone'] : '' ?>' />
    <label for='billingTelephone'><?php _e( 'Billing Telephone', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="text" class="form-control" name='bankName' id='bankName' value='<?php echo isset($options['billing']['bankName']) ? $options['billing']['bankName'] : '' ?>' />
    <label for='bankName'><?php _e( 'Bank Name', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="number" class="form-control" name='sortCode' id='sortCode' value='<?php echo isset($options['billing']['sortCode']) ? $options['billing']['sortCode'] : '' ?>' />
    <label for='sortCode'><?php _e( 'Sort Code', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="number" class="form-control" name='accountNumber' id='accountNumber' value='<?php echo isset($options['billing']['accountNumber']) ? $options['billing']['accountNumber'] : '' ?>' />
    <label for='accountNumber'><?php _e( 'Account Number', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type="number" class="form-control" name='invoiceNumber' id='invoiceNumber' value='<?php echo isset($options['billing']['invoiceNumber']) ? $options['billing']['invoiceNumber'] : '' ?>' />
    <label for='invoiceNumber'><?php _e( 'Invoice Number', 'racketmanager' ) ?></label>
  </div>
</div>
