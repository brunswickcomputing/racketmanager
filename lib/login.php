<?php
/**
* Login class for the WordPress plugin RacketManager
*
* @author     Paul Moffat
* @package    RacketManager
* @copyright Copyright 2018
*/

class RacketManagerLogin extends RacketManager {

  private $alreadySignedIn = 'You are already signed in';
  private $registerLink = "member-login?action=register";

  /**
  * initialize shortcodes
  *
  * @return void
  */
  public function __construct() {
    add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) );
    add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) );
    add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) );
    add_shortcode( 'account-info', array( $this, 'render_member_account_form' ) );

    add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
    add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );
    add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );
    add_action( 'login_form_register', array( $this, 'do_register_user' ) );
    add_action( 'admin_init', array( $this, 'disable_dashboard' ) );
    add_action( 'wp_print_footer_scripts', array( $this, 'add_captcha_js_to_footer' ) );
    add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
    add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
    add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
    add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );
    add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
    add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
    add_action( 'member_account_update', array( $this, 'do_member_account_update' ) );

    add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 );
    add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
    add_filter( 'retrieve_password_message', array( $this, 'racketmanager_retrieve_password_email' ), 10, 4 );
    add_filter( 'password_change_email', array( $this, 'racketmanager_password_change_email' ), 10, 3 );
    add_filter( 'wp_privacy_personal_data_email_content', array( $this, 'racketmanager_privacy_personal_data_email' ), 10, 3 );
    add_filter( 'user_request_action_email_content', array( $this, 'racketmanager_user_request_action_email' ), 10, 2 );
    add_filter( 'wp_new_user_notification_email_admin', array( $this, 'my_wp_new_user_notification_email_admin' ), 10, 3 );
    add_filter( 'wp_new_user_notification_email', array( $this, 'my_wp_new_user_notification_email' ), 10, 3 );
    add_filter( 'password_hint', array( $this, 'racketmanager_change_password_hint' ), 10, 1 );
  }

  public function racketmanager_change_password_hint( $hintText ) {
    return "Please use a strong password. Passwords that consist of special characters (&#%!@), upper case/lower case characters and numbers are considered strong.";
  }

  public function my_wp_new_user_notification_email_admin($wpNewUserNotificationEmail, $user, $blogname) {

    $userCount = count_users();

    $wpNewUserNotificationEmail['subject'] = sprintf('[%s] New user %s registered.', $blogname, $user->user_login);
    $wpNewUserNotificationEmail['message'] = sprintf( "%s has registered to %s.", $user->user_login, $blogname) . "\n\n\r" . sprintf("You now have %d users", $userCount['total_users']);

    return $wpNewUserNotificationEmail;
  }

  public function my_wp_new_user_notification_email($wpNewUserNotificationEmail, $user, $blogname) {
    global $racketmanager_shortcodes, $racketmanager;

    $key = get_password_reset_key($user);
    $vars['site_name'] = $racketmanager->site_name;
    $vars['site_url'] = $racketmanager->site_url;
    $vars['user_login'] = $user->user_login;
    $vars['display_name'] = $user->display_name;
    $vars['action_url'] = $racketmanager->site_url . '/member-password-reset/?key='.$key.'&login='.rawurlencode($user->user_login);
    $vars['email_link'] = $racketmanager->admin_email;
    $wpNewUserNotificationEmail['message'] = $racketmanager_shortcodes->loadTemplate( 'email-welcome', $vars, 'email' );
    $wpNewUserNotificationEmail['headers'] = 'Content-Type: text/html; charset=UTF-8';

    return $wpNewUserNotificationEmail;
  }

  public function racketmanager_wp_email_content_type() {
    return 'text/html';
  }

  public function racketmanager_retrieve_password_email($message, $key, $userLogin, $userData) {
    global $racketmanager_shortcodes, $racketmanager;

    add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
    $vars['site_name'] = $racketmanager->site_name;
    $vars['site_url'] = $racketmanager->site_url;
    $vars['user_login'] = $userLogin;
    $vars['display_name'] = $userData->display_name;
    $vars['action_url'] = $racketmanager->site_url . '/member-password-reset/?key='.$key.'&login='.rawurlencode($userLogin);
    return $racketmanager_shortcodes->loadTemplate( 'email-password-reset', $vars, 'email' );

  }

  public function racketmanager_password_change_email($passwordChangeMessage, $userData, $userDataNew) {
    global $racketmanager_shortcodes, $racketmanager;

    add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
    $vars['site_name'] = $racketmanager->site_name;
    $vars['site_url'] = $racketmanager->site_url;
    $vars['user_login'] = $userData['user_login'];
    $vars['display_name'] = $userData['display_name'];
    $vars['email_link'] = $racketmanager->admin_email;
    $passwordChangeMessage['message'] = $racketmanager_shortcodes->loadTemplate( 'email-password-change', $vars, 'email' );

    return $passwordChangeMessage;
  }

  public function racketmanager_privacy_personal_data_email($message, $request, $emailData) {
    global $racketmanager_shortcodes, $racketmanager;

    add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
    $vars['site_name'] = $racketmanager->site_name;
    $vars['site_url'] = $racketmanager->site_url;
    return $racketmanager_shortcodes->loadTemplate( 'email-privacy-personal-data', $vars, 'email' );
  }

  public function racketmanager_user_request_action_email($message, $emailData) {
    global $racketmanager_shortcodes, $racketmanager;

    add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
    $vars['site_name'] = $racketmanager->site_name;
    $vars['site_url'] = $racketmanager->site_url;
    return $racketmanager_shortcodes->loadTemplate( 'email-user-request-action', $vars, 'email' );
  }

  public function disable_dashboard() {
    if (current_user_can('subscriber') && is_admin() && !DOING_AJAX ) {
      wp_redirect(home_url());
      exit;
    }
  }

  /**
  * A shortcode for rendering the login form.
  *
  * @param  array   $vars  Shortcode vars.
  * @param  string  $content     The text content for shortcode. Not used.
  *
  * @return string  The shortcode output
  */
  public function render_login_form( $vars, $content = null ) {
    global $racketmanager;

    // Parse shortcode vars
    $defaultVars = array( 'show_title' => false );
    $vars = shortcode_atts( $defaultVars, $vars );
    $vars['site_name'] = $racketmanager->site_name;
    $vars['site_url'] = $racketmanager->site_url;

    if ( is_user_logged_in() ) {
      return __( $this->alreadySignedIn, 'racketmanager' );
    }
    // Retrieve recaptcha key
    $keys = $racketmanager->getOptions('keys');
    $recaptchaSiteKey = isset($keys['recaptchaSiteKey']) ? $keys['recaptchaSiteKey'] : '';
    $vars['recaptcha_site_key'] = $recaptchaSiteKey;
    $action = isset($_GET[('action')]) ? $_GET[('action')]: '';
    if ( isset($action) && $action == 'register' ) {
      return $this->formRegister($vars);
    } else {
      return $this->formLogin($vars);
    }
  }

  public function formRegister($vars) {
    global $racketmanager_shortcodes;
    // Retrieve possible errors from request parameters
    $errors = array();
    $errorCodes = array();

    if ( isset( $_REQUEST['register-errors'] ) ) {
      $errorCodes = explode( ',', $_REQUEST['register-errors'] );

      foreach ( $errorCodes as $code ) {
        $errors[]= $this->get_error_message( $code );
      }
    }
    $vars['errors'] = $errors;
    $vars['error_codes'] = $errorCodes;

    if ( ! get_option( 'users_can_register' ) ) {
      $return = __( 'Registering new users is currently not allowed', 'racketmanager' );
    } else {
      $return = $racketmanager_shortcodes->loadTemplate( 'form-login', $vars, 'form' );
    }
    return $return;
}

public function formLogin($vars) {
  global $racketmanager_shortcodes, $racketmanager;
  // Check if the user just registered
  $vars['registered'] = isset( $_REQUEST['registered'] );
  // Pass the redirect parameter to the WordPress login functionality: by default,
  // don't specify a redirect, but if a valid redirect URL has been passed as
  // request parameter, use it.
  $vars['redirect'] = '';
  if ( isset( $_REQUEST['redirect_to'] ) ) {
    $vars['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $vars['redirect'] );
  } elseif ( wp_get_referer() ) {
    if ( strpos(wp_get_referer(), 'member-login' ) > 0 ) {
      $vars['redirect'] = '';
    } elseif ( strpos(wp_get_referer(), $racketmanager->site_url ) === 0 ) {
      $vars['redirect'] = wp_validate_redirect( wp_get_referer(), $vars['redirect'] );
    }
  }
  // Error messages
  $errors = array();
  $errorCodes = array();
  if ( isset( $_REQUEST['login'] ) ) {
    $errorCodes = explode( ',', $_REQUEST['login'] );

    foreach ( $errorCodes as $code ) {
      $errors []= $this->get_error_message( $code );
    }
  }
  $vars['errors'] = $errors;
  $vars['error_codes'] = $errorCodes;

  // Check if the user just requested a new password
  $vars['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';

  // Check if user just updated password
  $vars['password_updated'] = isset( $_REQUEST['passwordUpdate'] ) && $_REQUEST['passwordUpdate'] == 'true';

  // Check if user just logged out
  $vars['logged_out'] = isset( $_REQUEST['logged_out'] ) && $_REQUEST['logged_out'] ;

  // Render the login form using an external template
  return $racketmanager_shortcodes->loadTemplate( 'form-login', $vars, 'form' );

}

/**
  * Redirect the user to the custom login page instead of wp-login.php.
  */
  public function redirect_to_custom_login() {
    if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
      $redirectTo = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;

      if ( is_user_logged_in() ) {
        $this->redirect_logged_in_user( $redirectTo );
        exit;
      }

      // The rest are redirected to the login page
      $loginUrl = home_url( 'member-login' );
      if ( ! empty( $redirectTo ) ) {
        $loginUrl = add_query_arg( 'redirect_to', $redirectTo, $loginUrl );
      }

      wp_redirect( $loginUrl );
      exit;
    }
  }

  /**
  * Redirects the user to the correct page depending on whether admin or not.
  *
  * @param string $redirectTo   An optional redirect_to URL for admin users
  */
  public function redirect_logged_in_user( $redirectTo = null ) {
    $user = wp_get_current_user();
    if ( user_can( $user, 'manage_options' ) ) {
      if ( $redirectTo ) {
        wp_safe_redirect( $redirectTo );
      } else {
        wp_redirect( admin_url() );
      }
    } else {
      wp_redirect( home_url() );
    }
  }

  /**
  * Redirect the user after authentication if there were any errors.
  *
  * @param Wp_User|Wp_Error  $user       The signed in user, or the errors that have occurred during login.
  * @param string            $username   The user name used to log in.
  * @param string            $password   The password used to log in.
  *
  * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
  */
  public function maybe_redirect_at_authenticate( $user, $username, $password ) {
    // Check if the earlier authenticate filter (most likely,
    // the default WordPress authentication) functions have found errors
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && is_wp_error( $user ) ) {
      $errorCodes = join( ',', $user->get_error_codes() );

      $loginUrl = home_url( 'member-login' );
      $loginUrl = add_query_arg( 'login', $errorCodes, $loginUrl );

      wp_redirect( $loginUrl );
      exit;
    }

    return $user;
  }

  /**
  * Finds and returns a matching error message for the given error code.
  *
  * @param string $errorCode    The error code to look up.
  *
  * @return string               An error message.
  */
  public function get_error_message( $errorCode ) {
    switch ( $errorCode ) {
      case 'empty_password':
      $message = __( 'You need to enter a password to login.', 'racketmanager' );
      break;
      case 'incorrect_password':
      $err = __( "The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?", 'racketmanager' );
      $message = sprintf( $err, wp_lostpassword_url() );
      break;
      case 'email':
      $message = __( 'The email address you entered is not valid.', 'racketmanager' );
      break;
      case 'email_exists':
      $message = __( 'An account exists with this email address.', 'racketmanager' );
      break;
      case 'closed':
      $message = __( 'Registering new users is currently not allowed.', 'racketmanager' );
      break;
      case 'captcha':
      $message = __( 'The Google reCAPTCHA check failed. Are you a robot?', 'racketmanager' );
      break;
      case 'empty_username':
      $message = __( 'You need to enter your email address to continue.', 'racketmanager' );
      break;
      case 'invalid_email':
      case 'invalidcombo':
      case 'invalid_username':
      $message = __( 'There are no users registered with this email address.', 'racketmanager' );
      break;
      case 'expiredkey':
      case 'invalidkey':
      $message = __( 'The password reset link you used is not valid anymore.', 'racketmanager' );
      break;
      case 'password_reset_mismatch':
      $message = __( "The two passwords you entered don't match.", 'racketmanager' );
      break;
      case 'password_reset_empty':
      $message = __( "Sorry, we don't accept empty passwords.", 'racketmanager' );
      break;
      case 'firstname_field_empty':
      $message = __( 'First name must be specified', 'racketmanager' );
      break;
      case 'lastname_field_empty':
      $message = __( 'Last name must be specified', 'racketmanager' );
      break;
      case 'gender_field_empty':
      $message = __( 'Gender must be specified', 'racketmanager' );
      break;
      case 'no_updates':
      $message = __( 'No updates to be made', 'racketmanager' );
      break;
      default:
      $message = $errorCode;
    }
    return $message;
  }

  /**
  * Redirect to custom login page after the user has been logged out.
  */
  public function redirect_after_logout() {
    $redirectUrl = home_url( 'member-login?logged_out=true' );
    wp_safe_redirect( $redirectUrl );
    exit;
  }

  /**
  * Returns the URL to which the user should be redirected after the (successful) login.
  *
  * @param string           $redirectTo           The redirect destination URL.
  * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
  * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
  *
  * @return string Redirect URL
  */
  public function redirect_after_login( $redirectTo, $requestedRedirectTo, $user ) {
    if ( strpos($redirectTo, 'password-reset') ) {
      $redirectTo = '';
    }
    $redirectUrl = home_url();
    if ( ! isset( $user->ID ) ) {
      return $redirectUrl;
    }

    if ( user_can( $user, 'manage_options' ) ) {
      // Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
      if ( $redirectTo == '' ) {
        $redirectUrl = admin_url();
      } else {
        $redirectUrl = $redirectTo;
      }
    } else {
      // Use the redirect_to parameter if one is set, otherwise redirect to homepage.
      if ( $redirectTo == '' ) {
        $redirectUrl = home_url();
      } else {
        $redirectUrl = $redirectTo;
      }
    }

    return wp_validate_redirect( $redirectUrl, home_url() );
  }

  /**
  * Redirects the user to the custom registration page instead
  * of thedefault.
  */
  public function redirect_to_custom_register() {
    if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
      if ( is_user_logged_in() ) {
        $this->redirect_logged_in_user();
      } else {
        wp_redirect( home_url( $this->registerLink ) );
      }
      exit;
    }
  }

  /**
  * Validates and then completes the new user signup process if all went well.
  *
  * @param string $email         The new user's email address
  * @param string $firstName    The new user's first name
  * @param string $lastName     The new user's last name
  *
  * @return int|WP_Error         The id of the user that was created, or error if failed.
  */
  public function register_user( $email, $firstName, $lastName ) {
    $errors = new WP_Error();

    // Email address is used as both username and email. It is also the only
    // parameter we need to validate
    if ( ! is_email( $email ) ) {
      $errors->add( 'email', $this->get_error_message( 'email' ) );
      return $errors;
    }

    if ( username_exists( $email ) || email_exists( $email ) ) {
      $errors->add( 'email_exists', $this->get_error_message( 'email_exists') );
      return $errors;
    }

    // Generate the password so that the subscriber will have to check email...
    $password = wp_generate_password( 12, false );

    $userData = array(
      'user_login'    => $email,
      'user_email'    => $email,
      'user_pass'     => $password,
      'first_name'    => $firstName,
      'last_name'     => $lastName,
      'nickname'      => $firstName,
    );

    $userId = wp_insert_user( $userData );
    wp_new_user_notification( $userId, null, 'both' );

    return $userId;
  }

  /**
  * Handles the registration of a new user.
  *
  * Used through the action hook "login_form_register" activated on wp-login.php
  * when accessed through the registration action.
  */
  public function do_register_user() {
    $redirectUrl = home_url( $this->registerLink );
    if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
      wp_redirect( $redirectUrl );
      exit;
    }

    $errors = array();

    if ( ! get_option( 'users_can_register' ) ) { // Registration closed, display error
      $errors[] = 'closed';
      $redirectUrl = add_query_arg( 'register-errors', 'closed', $redirectUrl );
      wp_redirect( $redirectUrl );
      exit;
    }
    if ( ! $this->verifyRecaptcha() ) { // Recaptcha check failed, display error
      $errors[] = 'captcha';
      $redirectUrl = add_query_arg( 'register-errors', 'captcha', $redirectUrl );
      wp_redirect( $redirectUrl );
      exit;
    }
    $email = $_POST['email'];
    if ( !$email ) {
      $errors[] = 'email';
    }
    $firstName = sanitize_text_field( $_POST['first_name'] );
    if ( !$firstName ) {
      $errors[] = 'first_name';
    }
    $lastName = sanitize_text_field( $_POST['last_name'] );
    if ( !$lastName ) {
      $errors[] = 'last_name';
    }
    if ( !$errors ) {
      $result = $this->register_user( $email, $firstName, $lastName );
      if ( !is_wp_error( $result ) ) {
        update_user_meta( $result, 'show_admin_bar_front', false );
        // Success, redirect to login page.
        $redirectUrl = home_url( 'member-login' );
        $redirectUrl = add_query_arg( 'registered', $email, $redirectUrl );
      }
      $errors = $result->get_error_codes();
    }
    if ( $errors ) {
      $errorMsgs = join( ',', $errors );
      $redirectUrl = add_query_arg( 'register-errors', $errorMsgs, $redirectUrl );
    }
    wp_redirect( $redirectUrl );
    exit;
}

  /**
  * Checks that the reCAPTCHA parameter sent with the registration
  * request is valid.
  *
  * @return bool True if the CAPTCHA is OK, otherwise false.
  */
  private function verifyRecaptcha() {
    global $racketmanager;
    // This field is set by the recaptcha widget if check is successful
    if ( isset ( $_POST['g-recaptcha-response'] ) ) {
      $captchaResponse = $_POST['g-recaptcha-response'];
    } else {
      return false;
    }

    $keys = $racketmanager->getOptions('keys');
    $recaptchaSecretKey = isset($keys['recaptchaSecretKey']) ? $keys['recaptchaSecretKey'] : '';
    if ( !$recaptchaSecretKey ) {
      return false;
    }
    // Verify the captcha response from Google
    $response = wp_remote_post(
      'https://www.google.com/recaptcha/api/siteverify',
      array(
        'body' => array(
          'secret' => $recaptchaSecretKey,
          'response' => $captchaResponse
        )
      )
    );

    $success = false;
    if ( $response && is_array( $response ) ) {
      $decodedResponse = json_decode( $response['body'] );
      $success = $decodedResponse->success;
    }
    return $success;
  }

  /**
  * An action function used to include the reCAPTCHA JavaScript file
  * at the end of the page.
  */
  public function add_captcha_js_to_footer() {
    echo "<script src='https://www.google.com/recaptcha/api.js' async defer></script>";
  }

  /**
  * Redirects the user to the custom "Forgot your password?" page instead of
  * wp-login.php?action=lostpassword.
  */
  public function redirect_to_custom_lostpassword() {
    if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
      if ( is_user_logged_in() ) {
        $this->redirect_logged_in_user();
        exit;
      }

      wp_redirect( home_url( 'member-password-lost' ) );
      exit;
    }
  }

  /**
  * A shortcode for rendering the form used to initiate the password reset.
  *
  * @param  array   $vars  Shortcode vars.
  * @param  string  $content     The text content for shortcode. Not used.
  *
  * @return string  The shortcode output
  */
  public function render_password_lost_form( $vars, $content = null ) {

    global $racketmanager_shortcodes;

    if ( is_user_logged_in() ) {
      return __( $this->alreadySignedIn, 'racketmanager' );
    }

    // Parse shortcode vars
    $defaultVars = array( 'show_title' => true );
    $vars = shortcode_atts( $defaultVars, $vars );

    // Retrieve possible errors from request parameters
    $errors = array();
    $errorCodes = array();
    if ( isset( $_REQUEST['errors'] ) ) {
      $errorCodes = explode( ',', $_REQUEST['errors'] );

      foreach ( $errorCodes as $code ) {
        $errors[] = $this->get_error_message( $code );
      }
    }
    $vars['errors'] = $errors;
    $vars['error_codes'] = $errorCodes;
    // Check if the user just requested a new password
    $vars['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';

    return $racketmanager_shortcodes->loadTemplate( 'form-password-lost', $vars, 'form' );
  }

  /**
  * Initiates password reset.
  */
  public function do_password_lost() {
    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
      $redirectUrl = home_url( 'member-password-lost' );
      $errors = retrieve_password();
      if ( is_wp_error( $errors ) ) {
        // Errors found
        $redirectUrl = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirectUrl );
      } else {
        // Email sent
        $redirectUrl = add_query_arg( 'checkemail', 'confirm', $redirectUrl );
      }
      wp_redirect( $redirectUrl );
      exit;
    }
  }

  /**
  * Redirects to the custom password reset page, or the login page
  * if there are errors.
  */
  public function redirect_to_custom_password_reset() {
    if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
      // Verify key / login combo
      preg_replace('/[^a-z0-9]/i', '', $_REQUEST['key']);
      $user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
      if ( !$user || is_wp_error( $user ) ) {
        if ( $user && $user->get_error_code() === 'expired_key' ) {
          wp_redirect( home_url( 'member-password-lost?errors=expiredkey' ) );
        } else {
          wp_redirect( home_url( 'member-password-lost?errors=invalidkey' ) );
        }
        exit;
      }

      $redirectUrl = home_url( 'member-password-reset' );
      $redirectUrl = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirectUrl );
      $redirectUrl = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirectUrl );

      wp_redirect( $redirectUrl );
      exit;
    }
  }

  /**
  * A shortcode for rendering the form used to reset a user's password.
  *
  * @param  array   $vars  Shortcode vars.
  * @param  string  $content     The text content for shortcode. Not used.
  *
  * @return string  The shortcode output
  */
  public function render_password_reset_form( $vars, $content = null ) {
    global $racketmanager_shortcodes;

    // Parse shortcode vars
    $defaultVars = array( 'show_title' => false );
    $vars = shortcode_atts( $defaultVars, $vars );

    if ( is_user_logged_in() ) {
      return __( $this->alreadySignedIn, 'racketmanager' );
    } else {
      if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
        $vars['login'] = $_REQUEST['login'];
        $vars['key'] = $_REQUEST['key'];

        // Error messages
        $errors = array();
        $errorCodes = array();
        if ( isset( $_REQUEST['error'] ) ) {
          $errorCodes = explode( ',', $_REQUEST['error'] );

          foreach ( $errorCodes as $code ) {
            $errors []= $this->get_error_message( $code );
          }
        }
        $vars['errors'] = $errors;
        $vars['error_codes'] = $errorCodes;

        return $racketmanager_shortcodes->loadTemplate( 'form-password-reset', $vars, 'form' );
      } else {
        return __( 'Invalid password reset link.', 'racketmanager' );
      }
    }
  }

  /**
  * Resets the user's password if the password reset form was submitted.
  */
  public function do_password_reset() {
    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
      $rpKey = $_REQUEST['rp_key'];
      $rpLogin = $_REQUEST['rp_login'];

      $user = check_password_reset_key( $rpKey, $rpLogin );

      if ( ! $user || is_wp_error( $user ) ) {
        if ( $user && $user->get_error_code() === 'expired_key' ) {
          wp_redirect( home_url( 'member-login?login=expiredkey' ) );
        } else {
          wp_redirect( home_url( 'member-login?login=invalidkey' ) );
        }
        exit;
      }

      if ( $_POST['password'] != $_POST['rePassword'] ) {
        // Passwords don't match
        $redirectUrl = home_url( 'member-password-reset' );

        $redirectUrl = add_query_arg( 'key', $rpKey, $redirectUrl );
        $redirectUrl = add_query_arg( 'login', $rpLogin, $redirectUrl ) ;
        $redirectUrl = add_query_arg( 'error', 'password_reset_mismatch', $redirectUrl );

        wp_redirect( $redirectUrl );
        exit;
      }

      if ( empty( $_POST['password'] ) ) {
        // Password is empty
        $redirectUrl = home_url( 'member-password-reset' );

        $redirectUrl = add_query_arg( 'key', $rpKey, $redirectUrl );
        $redirectUrl = add_query_arg( 'login', $rpLogin, $redirectUrl );
        $redirectUrl = add_query_arg( 'error', 'password_reset_empty', $redirectUrl );

        wp_redirect( $redirectUrl );
        exit;
      }

      // Parameter checks OK, reset password
      reset_password( $user, $_POST['password'] );
      wp_redirect( home_url( 'member-login?passwordUpdate=true' ) );

      exit;
    }
  }

  /**
  * A shortcode for rendering the form used to display a member account.
  *
  * @return string  The shortcode output
  */
  public function render_member_account_form() {

    return $this->generate_member_account_form();

  }

  /**
  * Generate the form used to display a member account.
  *
  * @return string  The output
  */
  public function generate_member_account_form() {
    global $racketmanager_shortcodes;

    if ( !is_user_logged_in() ) {
      return __( 'You must be signed in to access this page', 'racketmanager' );
    }

    $currentUser = wp_get_current_user();
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      if ( isset( $_POST['member_account_nonce_field'] ) && wp_verify_nonce( $_POST['member_account_nonce_field'], 'member_account_nonce' ) ) {
        $userData = array(
          'user_name' => sanitize_email( $_POST['username'] ),
          'first_name' => sanitize_text_field( $_POST['firstname'] ),
          'last_name' => sanitize_text_field( $_POST['lastname'] ),
          'password' => $_POST['password'],
          'rePassword' => $_POST['rePassword'],
          'contactno' => sanitize_text_field( $_POST['contactno'] ),
          'gender' => sanitize_text_field( $_POST['gender'] ),
          'btm' => sanitize_text_field( $_POST['btm'] )
        );
      } else {
        return __( 'You are not authorised for this action', 'racketmanager' );
      }
      if ( !empty( $_POST['action'] ) && $_POST['action'] == 'update-user' ) {
        $userData = $this->updateUserProfile($currentUser, $userData);
      }
    } elseif ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
      $userData = array(
        'user_name' => $currentUser->user_email,
        'first_name' => get_user_meta($currentUser->ID,'first_name',true),
        'last_name' => get_user_meta($currentUser->ID,'last_name',true),
        'contactno' => get_user_meta($currentUser->ID,'contactno',true),
        'gender' => get_user_meta($currentUser->ID,'gender',true),
        'btm' => get_user_meta($currentUser->ID,'btm',true),
      );
    }
    return $racketmanager_shortcodes->loadTemplate( 'form-member-account', array('userData' => $userData), 'form' );
  }

  /**
  * Generate the form used to display a member account.
  *
  * @param object  $currentUser     current user object
  * @param array   $userData        user data from form
  * @return array  $userData        updated user data
  */
  private function updateUserProfile($currentUser, $userData) {

    $updates = false;

    $userData = $this->validateUserProfile($userData);

    if ( isset($userData['error']) ) {
      $userData['message'] = __( 'Errors in form', 'racketmanager');
      return $userData;
    }

    if ( $userData['user_name'] != $currentUser->user_email ) {
      $updates = true;
    }
    if ( $userData['first_name'] != get_user_meta($currentUser->ID,'first_name',true) ) {
      $updates = true;
    }
    if ( $userData['last_name'] != get_user_meta($currentUser->ID,'last_name',true) ) {
      $updates = true;
    }
    if ( empty($userData['contactno']) && !empty(get_user_meta($currentUser->ID,'contactno',true)) ) {
      $updates = true;
    }
    if ( $userData['contactno'] != get_user_meta($currentUser->ID,'contactno',true) ) {
      $updates = true;
    }
    if ( $userData['gender'] != get_user_meta($currentUser->ID,'gender',true) ) {
      $updates = true;
    }
    if ( empty($userData['btm']) ) {
      if ( !empty(get_user_meta($currentUser->ID,'btm',true)) ) {
        $updates = true;
      }
    } elseif ( $userData['btm'] != get_user_meta($currentUser->ID,'btm',true) ) {
      $updates = true;
    }
    if ( !empty( $userData['password'] ) ) {
      unset( $userData['rePassword'] );
      $updates = true;
    }

    if ( !$updates ) {
      $userData['message'] = $this->get_error_message('no_updates');
      return $userData;
    }
    foreach( $userData as $key => $value ) {
      // http://codex.wordpress.org/Function_Reference/wp_update_user
      if( $key == 'contactno' ) {
        update_user_meta( $currentUser->ID, $key, $value );
      } elseif( $key == 'btm' ) {
        update_user_meta( $currentUser->ID, $key, $value );
      } elseif( $key == 'gender' ) {
        update_user_meta( $currentUser->ID, $key, $value );
      } elseif( $key == 'first_name' ) {
        if ( $userData['first_name'] != get_user_meta($currentUser->ID,'first_name',true) ) {
          update_user_meta( $currentUser->ID, $key, $value );
          wp_update_user( array( 'ID' => $currentUser->ID, 'display_name' => $value.' '.sanitize_text_field( $userData['last_name'] ) ) );
        }
      } elseif( $key == 'last_name' ) {
        if ( $userData['last_name'] != get_user_meta($currentUser->ID,'last_name',true) ) {
          update_user_meta( $currentUser->ID, $key, $value );
          wp_update_user( array( 'ID' => $currentUser->ID, 'display_name' => sanitize_text_field( $userData['first_name'] ).' '.$value ) );
        }
      } elseif ( $key == 'password' ) {
        wp_update_user( array( 'ID' => $currentUser->ID, 'user_pass' => $value ) );
      } else {
        wp_update_user( array( 'ID' => $currentUser->ID, $key => $value ) );
      }
    }
    $userData['message'] = __( 'Your profile has been successfully updated', 'racketmanager');
    return $userData;
  }

  private function validateUserProfile($userData) {
    if ( empty($userData['user_name']) ) {
      $userData['user_name_error'] = $this->get_error_message('empty_username');
      $userData['error'] = true;
    }
    if ( empty($userData['first_name']) ) {
      $userData['first_name_error'] = $this->get_error_message('firstname_field_empty');
      $userData['error'] = true;
    }
    if ( empty($userData['last_name']) ) {
      $userData['last_name_error'] = $this->get_error_message('lastname_field_empty');
      $userData['error'] = true;
    }
    if ( empty($userData['gender']) ) {
      $userData['gender_error'] = $this->get_error_message('gender_field_empty');
      $userData['error'] = true;
    }
    if ( $userData['password'] != $userData['rePassword'] ) {
      $userData['rePassword_error'] = $this->get_error_message('password_reset_mismatch');
      $userData['error'] = true;
    }
    return $userData;
  }

}
