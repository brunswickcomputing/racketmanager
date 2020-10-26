<div id="password-reset-form" class="login-form-container  widecolumn">
    <?php if ( $vars['show_title'] ) { ?>
        <h3><?php _e( 'Pick a New Password', 'leaguemanager' ); ?></h3>
    <?php } ?>

    <form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
        <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $vars['login'] ); ?>" autocomplete="off" />
        <input type="hidden" name="rp_key" value="<?php echo esc_attr( $vars['key'] ); ?>" />

        <?php if ( count( $vars['errors'] ) > 0 ) { ?>
            <?php foreach ( $vars['errors'] as $error ) { ?>
                <p><?php echo $error; ?></p>
            <?php } ?>
        <?php } ?>

        <p>
            <label class="hidden" for="pass1"><?php _e( 'New password', 'leaguemanager' ) ?></label>
            <input type="password" placeholder="<?php _e( 'New password', 'leaguemanager' ) ?>" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
        </p>
        <p>
            <label class="hidden" for="pass2"><?php _e( 'Repeat new password', 'leaguemanager' ) ?></label>
            <input type="password" placeholder="<?php _e( 'Repeat new password', 'leaguemanager' ) ?>" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
        </p>

        <p class="description"><?php echo wp_get_password_hint(); ?></p>

        <p class="resetpass-submit">
            <input type="submit" name="submit" id="resetpass-button"
                   class="button" value="<?php _e( 'Reset Password', 'leaguemanager' ); ?>" />
        </p>
    </form>
</div>
