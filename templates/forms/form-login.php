<?php
/**
 * Form for login and registration control
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\util\Util;

/** @var array $vars */
global $wp_query;
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$post_id = $wp_query->post->ID;
$tab     = 'login';
$action  = isset( $_GET[ ( 'action' ) ] ) ? sanitize_text_field( wp_unslash( $_GET[ ( 'action' ) ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $action ) && 'register' === $action ) {
    $tab = 'registration';
}
// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="row justify-content-center">
    <div id="tabs-login" class="col-12">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs frontend" id="loginTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="login-tab" data-bs-toggle="pill" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="true"><?php esc_html_e( 'Login', 'racketmanager' ); ?></button>
            </li>
            <?php
            if ( get_option( 'users_can_register' ) ) {
                ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="registration-tab" data-bs-toggle="pill" data-bs-target="#registration" type="button" role="tab" aria-controls="registration" aria-selected="true"><?php esc_html_e( 'Sign Up', 'racketmanager' ); ?></button>
                </li>
                <?php
            }
            ?>
        </ul>
        <?php
        $username_error    = false;
        $password_error    = false;
        $username_message  = '';
        $password_message  = '';
        $other_error       = false;
        $email_error       = false;
        $firstname_error   = false;
        $surname_error     = false;
        $recaptcha_error   = false;
        $email_message     = '';
        $firstname_message = '';
        $surname_message   = '';
        $recaptcha_message = '';
        $error_message = null;
        if ( count( $vars['errors'] ) > 0 ) {
            ?>
            <?php
            foreach ( $vars['error_codes'] as $error_code ) {
                switch ( $error_code ) {
                    case 'empty_username':
                    case 'invalid_username':
                        $username_error   = true;
                        $username_message = Util::get_error_message( $error_code );
                        break;
                    case 'incorrect_password':
                    case 'empty_password':
                        $password_error   = true;
                        $password_message = Util::get_error_message( $error_code );
                        break;
                    case 'email_exists':
                    case 'email':
                        $email_error   = true;
                        $email_message = Util::get_error_message( $error_code );
                        break;
                    case 'captcha':
                        $recaptcha_error   = true;
                        $recaptcha_message = Util::get_error_message( $error_code );
                        break;
                    case 'first_name':
                        $firstname_error   = true;
                        $firstname_message = Util::get_error_message( $error_code );
                        break;
                    case 'last_name':
                        $surname_error   = true;
                        $surname_message = Util::get_error_message( $error_code );
                        break;
                    case 'security':
                    case 'invalidkey':
                        $other_error   = true;
                        $error_message = Util::get_error_message( $error_code );
                        break;
                    default:
                        break;
                }
            }
            ?>
            <div class="alert_rm mt-3 alert--danger" id="loginAlert">
                <div class="alert__body">
                    <div class="alert__body-inner" id="loginAlertResponse">
                        <?php
                        if ( $other_error ) {
                            echo esc_html( $error_message );
                        } else {
                            /* translators: %s: tab */
                            printf( esc_html( __( 'Error in %s', 'racketmanager' ) ), esc_html( $tab ) );
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        <?php
        if ( isset( $vars['logged_out'] ) && $vars['logged_out'] ) {
            ?>
            <p class="login-info">
            <?php esc_html_e( 'You have signed out. Would you like to sign in again?', 'racketmanager' ); ?>
            </p>
            <?php
        }
        ?>
        <?php
        if ( isset( $vars['registered'] ) && $vars['registered'] ) {
            ?>
            <p class="login-info">
            <?php esc_html_e( 'You have successfully registered. We have emailed your password to the email address you entered.', 'racketmanager' ); ?>
            </p>
            <?php
        }
        ?>
        <?php
        if ( isset( $vars['password_updated'] ) && $vars['password_updated'] ) {
            ?>
            <p class="login-info">
            <?php esc_html_e( 'Your password has been changed. You can sign in now.', 'racketmanager' ); ?>
            </p>
            <?php
        }
        ?>
    </div>
    <!-- Tab panes -->
    <div class="tab-content">
        <div class="tab-pane fade" id="login" role="tabpanel" aria-labelledby="login-tab">
            <?php require 'login-page.php'; ?>
        </div>
        <?php
        if ( get_option( 'users_can_register' ) ) {
            ?>
            <div class="tab-pane fade" id="registration" role="tabpanel" aria-labelledby="registration-tab">
                <?php require 'register-page.php'; ?>
            </div>
            <?php
        }
        ?>
    </div>
</div>
