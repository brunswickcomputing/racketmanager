<div>
  <form id="signupForm" method="post" action="<?php echo wp_registration_url(); ?>">
    <script>
    function submitForm() {
      var signupForm = document.getElementById("signupForm");
      signupForm.submit();
    }
    </script>
    <fieldset class="mt-3">
      <div class="form-floating mb-3">
        <input class="form-control <?php if ( $emailErr ) { echo 'is-invalid'; } ?>" type="email" placeholder="<?php _e( 'Email', 'racketmanager' ); ?>" name="email" id="email" aria-describedby="emailFeedback" />
        <label class="" for="email"><?php _e( 'Email', 'racketmanager' ); ?></label>
        <div id="emailFeedback" class="<?php if ( $emailErr ) { echo 'invalid-feedback'; } ?>">
          <?php if ( $emailErr ) { echo $emailMsg; } ?>
        </div>
      </div>
      <div class="form-floating mb-3">
        <input class="form-control <?php if ( $firstNameErr ) { echo 'is-invalid'; } ?>" type="text" placeholder="<?php _e( 'First name', 'racketmanager' ); ?>" name="first_name" id="first-name" aria-describedby="firstNameFeedback" />
        <label class="" for="first_name"><?php _e( 'First name', 'racketmanager' ); ?></label>
        <div id="firstNameFeedback" class="<?php if ( $firstNameErr ) { echo 'invalid-feedback'; } ?>">
          <?php if ( $firstNameErr ) { echo $firstNameMsg; } ?>
        </div>
      </div>
      <div class="form-floating mb-3">
        <input class="form-control <?php if ( $surnameErr ) { echo 'is-invalid'; } ?>" type="text" placeholder="<?php _e( 'Last name', 'racketmanager' ); ?>" name="last_name" id="last-name" aria-describedby="surnameFeedback" />
        <label class="" for="last_name"><?php _e( 'Last name', 'racketmanager' ); ?></label>
        <div id="surnameFeedback" class="<?php if ( $surnameErr ) { echo 'invalid-feedback'; } ?>">
          <?php if ( $surnameErr ) { echo $surnameMsg; } ?>
        </div>
      </div>
    </fieldset>
    <div class="row">
      <?php if ( $vars['recaptcha_site_key'] ) { ?>
        <div class="recaptcha-container col-6">
          <div class="g-recaptcha <?php if ( $recaptchaErr ) { echo 'is-invalid'; } ?>" data-sitekey="<?php echo $vars['recaptcha_site_key']; ?>" data-size="invisible" aria-describedby="recaptchaFeedback" data-badge="inline" data-bind="recaptchaSubmit"  data-callback="submitForm"></div>
          <div id="recaptchaFeedback" class="<?php if ( $recaptchaErr ) { echo 'invalid-feedback'; } ?>">
            <?php if ( $recaptchaErr ) { echo $recaptchaMsg; } ?>
          </div>
        </div>
      <?php } ?>
      <div class="register-submit col-6">
        <p class="login-submit">
          <input type="submit" name="btnSubmit" class="register-button" id="recaptchaSubmit" value="<?php _e( 'Register', 'racketmanager' ); ?>" />
        </p>
      </div>
    </div>
  </form>
</div>
<div class="form-row">
  <?php _e( 'Note: Your password will be generated automatically and sent to your email address.', 'racketmanager' ); ?>
</div>
