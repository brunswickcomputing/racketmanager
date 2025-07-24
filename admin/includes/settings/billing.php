<?php
/**
 * Billing settings
 */
namespace Racketmanager;

/** @var array $options */
?>
<div class="form-control">
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Invoice settings', 'racketmanager' ); ?></legend>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <select class="form-select" id="billingCurrency" name="billingCurrency">
                    <option value="GBP" <?php selected( 'GBP', empty( $options['billing']['billingCurrency'] ) ? null : $options['billing']['billingCurrency'] ); ?>><?php _e('GBP', 'racketmanager') ?></option>
                    <option value="EUR" <?php selected( 'EUR', empty( $options['billing']['billingCurrency'] ) ? null : $options['billing']['billingCurrency'] ); ?>><?php _e('EURO', 'racketmanager') ?></option>
                </select>
                <label for='billingCurrency'><?php _e( 'Billing Currency', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="number" class="form-control" name='invoiceNumber' id='invoiceNumber' value='<?php echo $options['billing']['invoiceNumber'] ?? '' ?>' />
                <label for='invoiceNumber'><?php _e( 'Invoice Number', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="number" class="form-control" name='paymentTerms' id='paymentTerms' value='<?php echo $options['billing']['paymentTerms'] ?? '' ?>' />
                <label for='paymentTerms'><?php _e( 'Payment Terms (days)', 'racketmanager' ) ?></label>
            </div>
        </div>
    </fieldset>
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Bank Account', 'racketmanager' ); ?></legend>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="text" class="form-control" name='bankName' id='bankName' value='<?php echo $options['billing']['bankName'] ?? '' ?>' />
                <label for='bankName'><?php _e( 'Bank Name', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="number" class="form-control" name='sortCode' id='sortCode' value='<?php echo $options['billing']['sortCode'] ?? '' ?>' />
                <label for='sortCode'><?php _e( 'Sort Code', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="number" class="form-control" name='accountNumber' id='accountNumber' value='<?php echo $options['billing']['accountNumber'] ?? '' ?>' />
                <label for='accountNumber'><?php _e( 'Account Number', 'racketmanager' ) ?></label>
            </div>
        </div>
    </fieldset>
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Billing Contact', 'racketmanager' ); ?></legend>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="email" class="form-control" name='billingEmail' id='billingEmail' value='<?php echo $options['billing']['billingEmail'] ?? '' ?>' />
                <label for='billingEmail'><?php _e( 'Billing Email Address', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="tel" class="form-control" name='billingTelephone' id='billingTelephone' value='<?php echo $options['billing']['billingTelephone'] ?? '' ?>' />
                <label for='billingTelephone'><?php _e( 'Billing Telephone', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="text" class="form-control" name='billingAddress' id='billingAddress' value='<?php echo $options['billing']['billingAddress'] ?? '' ?>' />
                <label for='billingAddress'><?php _e( 'Billing Address', 'racketmanager' ) ?></label>
            </div>
        </div>
    </fieldset>
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Stripe_Settings gateway', 'racketmanager' ); ?></legend>
        <div class="row">
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="billingIsLive" id="billingIsLive" <?php echo empty( $options['billing']['stripe_is_live']) ? null : 'checked'; ?>>
                    <label class="form-check-label" for="billingIsLive"><?php esc_html_e( 'Live mode', 'racketmanager' ); ?></label>
                </div>
            </div>
        </div>
        <fieldset class="row mb-3">
            <legend><?php esc_html_e( 'Test keys', 'racketmanager' ); ?></legend>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="form-floating">
                    <input type="text" class="form-control" name='api_publishable_key_test' id='api_publishable_key_test' value='<?php echo $options['billing']['api_publishable_key_test'] ?? '' ?>' />
                    <label for='api_publishable_key_test'><?php _e( 'API Publishable key', 'racketmanager' ) ?></label>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="form-floating">
                    <input type="text" class="form-control" name='api_secret_key_test' id='api_secret_key_test' value='<?php echo $options['billing']['api_secret_key_test'] ?? '' ?>' />
                    <label for='api_secret_key_test'><?php _e( 'API Secret key', 'racketmanager' ) ?></label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <input type="text" class="form-control" name='api_endpoint_key_test' id='api_endpoint_key_test' value='<?php echo $options['billing']['api_endpoint_key_test'] ?? '' ?>' />
                    <label for='api_endpoint_key_test'><?php _e( 'API Endpoint secret key', 'racketmanager' ) ?></label>
                </div>
            </div>
        </fieldset>
        <fieldset class="row mb-3">
            <legend><?php esc_html_e( 'Live keys', 'racketmanager' ); ?></legend>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="form-floating">
                    <input type="text" class="form-control" name='api_publishable_key_live' id='api_publishable_key_live' value='<?php echo $options['billing']['api_publishable_key_live'] ?? '' ?>' />
                    <label for='api_publishable_key_live'><?php _e( 'API Publishable key', 'racketmanager' ) ?></label>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="form-floating">
                    <input type="text" class="form-control" name='api_secret_key_live' id='api_secret_key_live' value='<?php echo $options['billing']['api_secret_key_live'] ?? '' ?>' />
                    <label for='api_secret_key_live'><?php _e( 'API Secret key', 'racketmanager' ) ?></label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <input type="text" class="form-control" name='api_endpoint_key_live' id='api_endpoint_key_live' value='<?php echo $options['billing']['api_endpoint_key_live'] ?? '' ?>' />
                    <label for='api_endpoint_key_live'><?php _e( 'API Endpoint secret key', 'racketmanager' ) ?></label>
                </div>
            </div>
        </fieldset>
    </fieldset>
</div>
