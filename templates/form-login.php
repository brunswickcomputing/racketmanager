<?php
    global $wp_query;
    $postID = $wp_query->post->ID;
    $tab = 0;
    $action = isset($_GET[('action')]) ? $_GET[('action')] : '' ;
    if ( isset($action) && $action == 'register' ) {
        $tab = 1;
    }
?>
    <script type='text/javascript'>
       jQuery(function() {
              jQuery(".jquery-ui-tabs").tabs({
                                             active: <?php echo $tab ?>
                                             });
              });
</script>
<div id="tabs-login" class="jquery-ui-tabs login-form-container">
    <ul id="tablist">
        <li><h3><a href="#login"><?php _e( 'Login', 'racketmanager' ) ?></a></h3></li>
        <li><h3><a href="#register"><?php _e( 'Sign Up', 'racketmanager' ) ?></a><h3></li>
    </ul>
    <div id="login" class="">
        <?php if ( $vars['show_title'] ) { ?>
        <h2><?php _e( 'Sign In', 'racketmanager' ); ?></h2>
        <?php } ?>
    <?php if ( count( $vars['errors'] ) > 0 ) { ?>
        <?php foreach ( $vars['errors'] as $error ) { ?>
        <p class="login-error">
            <?php echo $error; ?>
        </p>
        <?php } ?>
    <?php } ?>
    <?php if ( $vars['logged_out'] ) { ?>
        <p class="login-info">
            <?php _e( 'You have signed out. Would you like to sign in again?', 'racketmanager' ); ?>
        </p>
    <?php } ?>
    <?php if ( $vars['registered'] ) { ?>
        <p class="login-info">
            <?php
                printf(
                    __( 'You have successfully registered to <strong>%s</strong>. We have emailed your password to the email address you entered.', 'racketmanager' ),
                    get_bloginfo( 'name' )
                );
            ?>
        </p>
    <?php } ?>
    <?php if ( $vars['lost_password_sent'] ) { ?>
        <p class="login-info">
            <?php _e( 'Check your email for a link to reset your password.', 'racketmanager' ); ?>
        </p>
    <?php } ?>

    <?php if ( $vars['password_updated'] ) { ?>
        <p class="login-info">
            <?php _e( 'Your password has been changed. You can sign in now.', 'racketmanager' ); ?>
        </p>
    <?php } ?>

        <form method="post" action="<?php echo wp_login_url(); ?>">
            <fieldset class="p-fieldset">
                <div class="form-group">
                    <label class="hidden" for="user_login"><?php _e( 'Email', 'racketmanager' ); ?></label>
                    <div class="input">
                        <input type="text" placeholder="<?php _e( 'Email', 'racketmanager' ); ?>" name="log" id="user_login">
                    </div>
                </div>
                <div class="form-group">
                    <label class="hidden" for="user_pass"><?php _e( 'Password', 'racketmanager' ); ?></label>
                    <div class="input">
                        <input type="password" class="password" name="pwd" placeholder="<?php _e( 'Password', 'racketmanager' ); ?>" id="user_pass">
                        <i class="passwordShow racketmanager-svg-icon">
                            <?php racketmanager_the_svg('icon-eye') ?>
                        </i>

                    </div>
                </div>
            </fieldset>
            <fieldset class="p-fieldset-split">
                <a class="forgot-password" href="<?php echo wp_lostpassword_url(); ?>">
                    <?php _e( 'Forgot your password?', 'racketmanager' ); ?>
                </a>
            </fieldset>
            <fieldset class="p-fieldset-split">
                <p class="login-submit">
                    <input type="submit" value="<?php _e( 'Login', 'racketmanager' ); ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( $vars['redirect'] ) ?>" />
                </p>
            </fieldset>
        </form>
    </div>
    <div id="register" class="">
    <?php if ( $vars['show_title'] ) { ?>
        <h3><?php _e( 'Register', 'racketmanager' ); ?></h3>
    <?php } ?>

    <?php if ( count( $vars['errors'] ) > 0 ) { ?>
        <?php foreach ( $vars['errors'] as $error ) { ?>
        <p><?php echo $error; ?></p>
        <?php } ?>
    <?php } ?>

        <form id="signupform" method="post" action="<?php echo wp_registration_url(); ?>">
            <fieldset class="p-fieldset">
                <p class="form-row">
                    <label class="hidden" for="email"><?php _e( 'Email', 'racketmanager' ); ?> <strong>*</strong></label>
                    <input type="email" placeholder="<?php _e( 'Email', 'racketmanager' ); ?>" name="email" id="email">
                </p>
                <p class="form-row">
                    <label class="hidden" for="first_name"><?php _e( 'First name', 'racketmanager' ); ?></label>
                    <input type="text" placeholder="<?php _e( 'First name', 'racketmanager' ); ?>" name="first_name" id="first-name">
                </p>
                <p class="form-row">
                    <label class="hidden" for="last_name"><?php _e( 'Last name', 'racketmanager' ); ?></label>
                    <input type="text" placeholder="<?php _e( 'Last name', 'racketmanager' ); ?>" name="last_name" id="last-name">
                </p>
            </fieldset>
            <p class="form-row">
                <?php _e( 'Note: Your password will be generated automatically and sent to your email address.', 'racketmanager' ); ?>
            </p>
    <?php if ( $vars['recaptcha_site_key'] ) { ?>
            <div class="recaptcha-container">
                <div class="g-recaptcha" data-sitekey="<?php echo $vars['recaptcha_site_key']; ?>"></div>
            </div>
    <?php } ?>
            <div class="register-submit">
                <p class="login-submit">
                    <input type="submit" name="submit" class="register-button"
                           value="<?php _e( 'Register', 'racketmanager' ); ?>"/>
                </p>
            </div>
        </form>
    </div>
</div>
