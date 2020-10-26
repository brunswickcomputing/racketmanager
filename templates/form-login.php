<?php
    global $wp_query;
    $postID = $wp_query->post->ID;
    $tab = 0;
    $action = $_GET[('action')];
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
        <li><h3><a href="#login"><?php _e( 'Login', 'leaguemanager' ) ?></a></h3></li>
        <li><h3><a href="#register"><?php _e( 'Sign Up', 'leaguemanager' ) ?></a><h3></li>
    </ul>
    <div id="login" class="">
        <?php if ( $vars['show_title'] ) { ?>
        <h2><?php _e( 'Sign In', 'leaguemanager' ); ?></h2>
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
            <?php _e( 'You have signed out. Would you like to sign in again?', 'leaguemanager' ); ?>
        </p>
    <?php } ?>
    <?php if ( $vars['registered'] ) { ?>
        <p class="login-info">
            <?php
                printf(
                    __( 'You have successfully registered to <strong>%s</strong>. We have emailed your password to the email address you entered.', 'leaguemanager' ),
                    get_bloginfo( 'name' )
                );
            ?>
        </p>
    <?php } ?>
    <?php if ( $vars['lost_password_sent'] ) { ?>
        <p class="login-info">
            <?php _e( 'Check your email for a link to reset your password.', 'leaguemanager' ); ?>
        </p>
    <?php } ?>

    <?php if ( $vars['password_updated'] ) { ?>
        <p class="login-info">
            <?php _e( 'Your password has been changed. You can sign in now.', 'leaguemanager' ); ?>
        </p>
    <?php } ?>

        <form method="post" action="<?php echo wp_login_url(); ?>">
            <fieldset class="p-fieldset">
                <p class="login-username">
                    <label class="hidden" for="user_login"><?php _e( 'Email', 'leaguemanager' ); ?></label>
                    <input type="text" placeholder="<?php _e( 'Email', 'leaguemanager' ); ?>" name="log" id="user_login">
                </p>
                <p class="login-password">
                    <label class="hidden" for="user_pass"><?php _e( 'Password', 'leaguemanager' ); ?></label>
                    <input type="password" name="pwd" placeholder="<?php _e( 'Password', 'leaguemanager' ); ?>" id="user_pass">
                </p>
            </fieldset>
            <fieldset class="p-fieldset-split">
                <a class="forgot-password" href="<?php echo wp_lostpassword_url(); ?>">
                    <?php _e( 'Forgot your password?', 'leaguemanager' ); ?>
                </a>
            </fieldset>
            <fieldset class="p-fieldset-split">
                <p class="login-submit">
                    <input type="submit" value="<?php _e( 'Login', 'leaguemanager' ); ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( $vars['redirect'] ) ?>" />
                </p>
            </fieldset>
        </form>
    </div>
    <div id="register" class="">
    <?php if ( $vars['show_title'] ) { ?>
        <h3><?php _e( 'Register', 'leaguemanager' ); ?></h3>
    <?php } ?>

    <?php if ( count( $vars['errors'] ) > 0 ) { ?>
        <?php foreach ( $vars['errors'] as $error ) { ?>
        <p><?php echo $error; ?></p>
        <?php } ?>
    <?php } ?>

        <form id="signupform" method="post" action="<?php echo wp_registration_url(); ?>">
            <fieldset class="p-fieldset">
                <p class="form-row">
                    <label class="hidden" for="email"><?php _e( 'Email', 'leaguemanager' ); ?> <strong>*</strong></label>
                    <input type="email" placeholder="<?php _e( 'Email', 'leaguemanager' ); ?>" name="email" id="email">
                </p>
                <p class="form-row">
                    <label class="hidden" for="first_name"><?php _e( 'First name', 'leaguemanager' ); ?></label>
                    <input type="text" placeholder="<?php _e( 'First name', 'leaguemanager' ); ?>" name="first_name" id="first-name">
                </p>
                <p class="form-row">
                    <label class="hidden" for="last_name"><?php _e( 'Last name', 'leaguemanager' ); ?></label>
                    <input type="text" placeholder="<?php _e( 'Last name', 'leaguemanager' ); ?>" name="last_name" id="last-name">
                </p>
            </fieldset>
            <p class="form-row">
                <?php _e( 'Note: Your password will be generated automatically and sent to your email address.', 'leaguemanager' ); ?>
            </p>
    <?php if ( $vars['recaptcha_site_key'] ) { ?>
            <div class="recaptcha-container">
                <div class="g-recaptcha" data-sitekey="<?php echo $vars['recaptcha_site_key']; ?>"></div>
            </div>
    <?php } ?>
            <div class="register-submit">
                <p class="login-submit">
                    <input type="submit" name="submit" class="register-button"
                           value="<?php _e( 'Register', 'leaguemanager' ); ?>"/>
                </p>
            </div>
        </form>
    </div>
</div>
