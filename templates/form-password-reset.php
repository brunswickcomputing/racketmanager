<div id="password-reset" class="login-form-container  widecolumn">
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

        <div class="form-group">
            <label class="hidden" for="password"><?php _e( 'New password', 'leaguemanager' ) ?></label>
            <div class="input">
                <input type="password" placeholder="<?php _e( 'New password', 'leaguemanager' ) ?>" name="password" id="password" class="password" size="20" value="" autocomplete="off" />
                <i class="passwordShow leaguemanager-svg-icon">
                    <?php leaguemanager_the_svg('icon-eye') ?>
                </i>
            </div>
        </div>
        <div class="form-group">
            <label class="hidden" for="rePassword"><?php _e( 'Repeat new password', 'leaguemanager' ) ?></label>
            <div class="input">
                <input type="password" placeholder="<?php _e( 'Repeat new password', 'leaguemanager' ) ?>" name="rePassword" id="rePassword" class="password" size="20" value="" autocomplete="off" />
                <i class="passwordShow leaguemanager-svg-icon">
                    <?php leaguemanager_the_svg('icon-eye') ?>
                </i>
            </div>
        </div>

        <p class="description"><?php echo wp_get_password_hint(); ?></p>

        <p class="resetpass-submit">
            <input type="submit" name="submit" id="resetpass-button"
                   class="button" value="<?php _e( 'Reset Password', 'leaguemanager' ); ?>" />
        </p>
    </form>
</div>
