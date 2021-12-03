<div id="password-lost-form" class="login-form-container widecolumn">
    <?php if ( $vars['show_title'] ) { ?>
        <h3><?php _e( 'Forgot Your Password?', 'racketmanager' ); ?></h3>
    <?php } ?>

    <?php if ( count( $vars['errors'] ) > 0 ) { ?>
        <?php foreach ( $vars['errors'] as $error ) { ?>
        <p>
            <?php echo $error; ?>
        </p>
        <?php } ?>
    <?php } ?>

    <p><?php _e( "Enter the email address you use and we'll send you a link to create a new password.", 'personalize_login' ); ?></p>

    <form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post">
        <p class="form-row">
            <label class="hidden" for="user_login"><?php _e( 'Email', 'racketmanager' ); ?></label>
            <input type="email" placeholder="<?php _e( 'Email', 'racketmanager' ); ?>" name="user_login" id="user_login">
        </p>
        <p class="lostpassword-submit">
            <input type="submit" name="submit" class="lostpassword-button"
                   value="<?php _e( 'Reset Password', 'racketmanager' ); ?>"/>
        </p>
    </form>
</div>
