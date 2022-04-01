<div id="password-reset" class="login-form-container  widecolumn">
  <?php if ( $vars['show_title'] ) { ?>
    <h3><?php _e( 'Pick a New Password', 'racketmanager' ); ?></h3>
  <?php } ?>

  <form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
    <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $vars['login'] ); ?>" autocomplete="off" />
    <input type="hidden" name="rp_key" value="<?php echo esc_attr( $vars['key'] ); ?>" />

    <?php if ( count( $vars['errors'] ) > 0 ) { ?>
      <?php foreach ( $vars['errors'] as $error ) { ?>
        <p><?php echo $error; ?></p>
      <?php } ?>
    <?php } ?>

    <p class="description"><?php echo wp_get_password_hint(); ?></p>
    <div class="form-floating mb-3">
        <input class="form-control password" type="password" placeholder="<?php _e( 'New password', 'racketmanager' ) ?>" name="password" id="password" size="20" value="" autocomplete="off" />
        <i class="passwordShow racketmanager-svg-icon">
          <?php racketmanager_the_svg('icon-eye') ?>
        </i>
        <label class="" for="password"><?php _e( 'New password', 'racketmanager' ) ?></label>
    </div>
    <div class="form-floating mb-3">
        <input class="form-control password" type="password" placeholder="<?php _e( 'Repeat new password', 'racketmanager' ) ?>" name="rePassword" id="rePassword" size="20" value="" autocomplete="off" />
        <label class="" for="rePassword"><?php _e( 'Repeat new password', 'racketmanager' ) ?></label>
        <i class="passwordShow racketmanager-svg-icon">
          <?php racketmanager_the_svg('icon-eye') ?>
        </i>
    </div>
    <div class="form-group mb-3">
      <span id="password-strength"></span>
    </div>

    <p class="resetpass-submit">
      <input type="submit" name="submit" id="resetpassButton"
      class="button" value="<?php _e( 'Reset Password', 'racketmanager' ); ?>" />
    </p>
  </form>
</div>
