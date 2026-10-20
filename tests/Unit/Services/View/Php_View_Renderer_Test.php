<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\View;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Query;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

class Php_View_Renderer_Test extends TestCase {
    private string $template_base_path;
    private string $temp_dir;
    private ?int $original_users_can_register;
    private ?string $original_stylesheet_directory;
    private ?string $original_template_directory;
    private ?string $original_get_action;

    protected function setUp(): void {
        parent::setUp();
        $this->template_base_path = dirname( __DIR__, 4 ) . '/templates/';
        $this->temp_dir           = sys_get_temp_dir() . '/rm-test-templates-' . uniqid( '', true );
        mkdir( $this->temp_dir, 0777, true );

        $this->original_users_can_register     = $GLOBALS['wp_stubs_options']['users_can_register'] ?? null;
        $this->original_stylesheet_directory   = $GLOBALS['wp_stubs_stylesheet_directory'] ?? null;
        $this->original_template_directory     = $GLOBALS['wp_stubs_template_directory'] ?? null;
        $this->original_get_action             = $_GET['action'] ?? null;

        unset(
            $GLOBALS['wp_stubs_stylesheet_directory'],
            $GLOBALS['wp_stubs_template_directory'],
            $GLOBALS['wp_stubs_options']['users_can_register'],
            $_GET['action']
        );
    }

    protected function tearDown(): void {
        if ( null !== $this->original_users_can_register ) {
            $GLOBALS['wp_stubs_options']['users_can_register'] = $this->original_users_can_register;
        } else {
            unset( $GLOBALS['wp_stubs_options']['users_can_register'] );
        }

        if ( null !== $this->original_stylesheet_directory ) {
            $GLOBALS['wp_stubs_stylesheet_directory'] = $this->original_stylesheet_directory;
        } else {
            unset( $GLOBALS['wp_stubs_stylesheet_directory'] );
        }

        if ( null !== $this->original_template_directory ) {
            $GLOBALS['wp_stubs_template_directory'] = $this->original_template_directory;
        } else {
            unset( $GLOBALS['wp_stubs_template_directory'] );
        }

        if ( null !== $this->original_get_action ) {
            $_GET['action'] = $this->original_get_action;
        } else {
            unset( $_GET['action'] );
        }

        $this->remove_directory_recursively( $this->temp_dir );
        parent::tearDown();
    }

    /**
     * Recursively remove a directory and all its contents.
     *
     * @param string $dir
     */
    private function remove_directory_recursively( string $dir ): void {
        if ( ! is_dir( $dir ) ) {
            return;
        }

        $items = array_diff( scandir( $dir ) ?: [], [ '.', '..' ] );
        foreach ( $items as $item ) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if ( is_dir( $path ) ) {
                $this->remove_directory_recursively( $path );
            } else {
                @unlink( $path );
            }
        }

        @rmdir( $dir );
    }

    /**
     * Helper to execute rendering while trapping PHP warnings and notices.
     *
     * @param callable $callback
     * @return string
     */
    private function render_trapping_warnings( callable $callback ): string {
        $errors = [];
        set_error_handler(
            static function ( int $errno, string $errstr, string $errfile, int $errline ) use ( &$errors ): bool {
                $errors[] = [
                    'errno'   => $errno,
                    'errstr'  => $errstr,
                    'errfile' => $errfile,
                    'errline' => $errline,
                ];
                return true;
            },
            E_ALL
        );

        try {
            $output = $callback();
        } finally {
            restore_error_handler();
        }

        $this->assertEmpty(
            $errors,
            'Rendering triggered unexpected PHP errors/notices/warnings: ' . var_export( $errors, true )
        );

        return $output;
    }

    public function test_template_exists_handles_theme_overrides_and_base_path(): void {
        $theme_dir        = $this->temp_dir . '/theme';
        $parent_theme_dir = $this->temp_dir . '/parent_theme';
        mkdir( $theme_dir . '/racketmanager', 0777, true );
        mkdir( $parent_theme_dir . '/racketmanager', 0777, true );
        file_put_contents( $theme_dir . '/racketmanager/overridden.php', '<?php echo "theme";' );
        file_put_contents( $parent_theme_dir . '/racketmanager/parent_overridden.php', '<?php echo "parent";' );
        file_put_contents( $this->temp_dir . '/base_only.php', '<?php echo "base";' );

        $GLOBALS['wp_stubs_stylesheet_directory'] = $theme_dir;
        $GLOBALS['wp_stubs_template_directory']   = $parent_theme_dir;

        $renderer = new Php_View_Renderer( $this->temp_dir . '/' );

        $this->assertTrue( $renderer->template_exists( 'overridden' ) );
        $this->assertTrue( $renderer->template_exists( 'parent_overridden' ) );
        $this->assertTrue( $renderer->template_exists( 'base_only' ) );
        $this->assertFalse( $renderer->template_exists( 'does_not_exist' ) );

        $this->assertSame( 'theme', $renderer->render_to_string( 'overridden', [] ) );
        $this->assertSame( 'parent', $renderer->render_to_string( 'parent_overridden', [] ) );
        $this->assertSame( 'base', $renderer->render_to_string( 'base_only', [] ) );
    }

    public function test_form_login_renders_cleanly_initial_state_empty_view_model(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string( 'forms/form-login', [] )
        );

        $this->assertStringContainsString( 'action="https://example.com/wp-login.php"', $output );
        $this->assertStringContainsString( 'name="log"', $output );
        $this->assertStringContainsString( 'id="user_login"', $output );
        $this->assertStringContainsString( 'name="pwd"', $output );
        $this->assertStringContainsString( 'id="user_pass"', $output );
        $this->assertStringContainsString( 'name="racketmanager_login_nonce"', $output );
        $this->assertStringContainsString( 'Forgot your password?', $output );
        $this->assertStringNotContainsString( 'alert--danger', $output );
        $this->assertStringNotContainsString( 'is-invalid', $output );
        $this->assertStringNotContainsString( 'Sign Up', $output );
    }

    public function test_form_login_renders_cleanly_with_explicit_empty_errors_array(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-login',
                [
                    'errors'      => [],
                    'error_codes' => [],
                ]
            )
        );

        $this->assertStringContainsString( 'action="https://example.com/wp-login.php"', $output );
        $this->assertStringNotContainsString( 'alert--danger', $output );
        $this->assertStringNotContainsString( 'is-invalid', $output );
    }

    public function test_form_login_renders_cleanly_when_passed_as_additional_vars(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        // Simulates production call: render_to_string( 'forms/form-login', [], $vars )
        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-login',
                [],
                [
                    'errors'      => [],
                    'error_codes' => [],
                    'redirect'    => 'https://example.com/target',
                ]
            )
        );

        $this->assertStringContainsString( 'action="https://example.com/wp-login.php"', $output );
        $this->assertStringContainsString( 'name="redirect_to" value="https://example.com/target"', $output );
        $this->assertStringNotContainsString( 'alert--danger', $output );
    }

    public function test_form_login_renders_registration_tab_when_registration_enabled(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-login',
                [
                    'recaptcha_site_key'  => 'test_recaptcha_key_123',
                    'users_can_register'  => true,
                    'tab'                 => 'registration',
                ]
            )
        );

        $this->assertStringContainsString( 'data-tabid="registration"', $output );
        $this->assertStringContainsString( 'id="registration-tab"', $output );
        $this->assertStringContainsString( 'Sign Up', $output );
        $this->assertStringContainsString( 'id="signupForm"', $output );
        $this->assertStringContainsString( 'action="https://example.com/wp-login.php?action=register"', $output );
        $this->assertStringContainsString( 'name="racketmanager_register_nonce"', $output );
        $this->assertStringContainsString( 'name="first_name"', $output );
        $this->assertStringContainsString( 'name="last_name"', $output );
        $this->assertStringContainsString( 'name="email"', $output );
        $this->assertStringContainsString( 'data-sitekey="test_recaptcha_key_123"', $output );
        $this->assertStringNotContainsString( '?>', $output );
    }

    public function test_form_login_renders_errors_safely(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-login',
                [
                    'errors'      => [ 'empty_username', 'incorrect_password' ],
                    'error_codes' => [ 'empty_username', 'incorrect_password' ],
                ]
            )
        );

        $this->assertStringContainsString( 'id="loginAlert"', $output );
        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'is-invalid', $output );
        $this->assertStringContainsString( 'Email address must be entered.', $output );
        $this->assertStringContainsString( 'Incorrect password.', $output );
    }

    public function test_form_login_renders_security_error_message(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-login',
                [
                    'errors'      => [ 'security' ],
                    'error_codes' => [ 'security' ],
                ]
            )
        );

        $this->assertStringContainsString( 'id="loginAlert"', $output );
        $this->assertStringContainsString( 'Form has expired. Please refresh the page and resubmit.', $output );
    }

    public function test_form_login_renders_status_banners_and_redirect(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-login',
                [
                    'logged_out'       => true,
                    'registered'       => true,
                    'password_updated' => true,
                    'redirect'         => 'https://example.com/custom-redirect',
                ]
            )
        );

        $this->assertStringContainsString( 'You have signed out. Would you like to sign in again?', $output );
        $this->assertStringContainsString( 'You have successfully registered. We have emailed your password', $output );
        $this->assertStringContainsString( 'Your password has been changed. You can sign in now.', $output );
        $this->assertStringContainsString( 'name="redirect_to" value="https://example.com/custom-redirect"', $output );
    }

    public function test_form_login_handles_null_and_malformed_errors_without_typeerror(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-login',
                [
                    'errors'      => null,
                    'error_codes' => null,
                ]
            )
        );

        $this->assertStringContainsString( 'action="https://example.com/wp-login.php"', $output );
        $this->assertStringNotContainsString( 'alert--danger', $output );
    }

    public function test_form_login_renders_registration_errors(): void {
        $GLOBALS['wp_stubs_options']['users_can_register'] = 1;
        $renderer                                          = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-login',
                [
                    'recaptcha_site_key' => 'site_key_xyz',
                    'users_can_register' => true,
                    'errors'             => [ 'email_exists', 'first_name', 'last_name', 'captcha' ],
                    'error_codes'        => [ 'email_exists', 'first_name', 'last_name', 'captcha' ],
                ]
            )
        );

        $this->assertStringContainsString( 'An account exists with this email address.', $output );
        $this->assertStringContainsString( 'First name must be entered', $output );
        $this->assertStringContainsString( 'Last name must be entered', $output );
        $this->assertStringContainsString( 'Google reCAPTCHA verification failed', $output );
    }

    public function test_form_password_lost_renders_cleanly_initial_state_empty_view_model(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string( 'forms/form-password-lost', [] )
        );

        $this->assertStringContainsString( 'Reset Password', $output );
        $this->assertStringContainsString( 'id="lostPasswordForm"', $output );
        $this->assertStringContainsString( 'action="https://example.com/wp-login.php?action=lostpassword"', $output );
        $this->assertStringNotContainsString( 'id="resetAlert"', $output );
    }

    public function test_form_password_lost_renders_cleanly_with_explicit_empty_errors_array(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-lost',
                [
                    'errors'      => [],
                    'error_codes' => [],
                ]
            )
        );

        $this->assertStringContainsString( 'Reset Password', $output );
        $this->assertStringContainsString( 'id="lostPasswordForm"', $output );
        $this->assertStringNotContainsString( 'id="resetAlert"', $output );
    }

    public function test_form_password_lost_renders_cleanly_sent_state(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output_sent = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-lost',
                [ 'lost_password_sent' => true ]
            )
        );

        $this->assertStringContainsString( 'Check your email for a link to reset your password', $output_sent );
        $this->assertStringNotContainsString( 'id="lostPasswordForm"', $output_sent );
    }

    public function test_form_password_lost_renders_errors_safely(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-lost',
                [
                    'errors'      => [ 'empty_username' ],
                    'error_codes' => [ 'empty_username' ],
                ]
            )
        );

        $this->assertStringContainsString( 'id="resetAlert"', $output );
        $this->assertStringContainsString( 'Error in password reset', $output );
        $this->assertStringContainsString( 'Email address must be entered', $output );
        $this->assertStringContainsString( 'is-invalid', $output );
    }

    public function test_form_password_lost_renders_additional_error_codes(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output_invalid_email = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-lost',
                [
                    'errors'      => [ 'invalid_email' ],
                    'error_codes' => [ 'invalid_email' ],
                ]
            )
        );
        $this->assertStringContainsString( 'No user found with that email address', $output_invalid_email );

        $output_invalid_key = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-lost',
                [
                    'errors'      => [ 'invalidkey' ],
                    'error_codes' => [ 'invalidkey' ],
                ]
            )
        );
        $this->assertStringContainsString( 'The password reset link you used is no longer valid', $output_invalid_key );
    }

    public function test_form_password_lost_handles_null_errors_without_typeerror(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-lost',
                [
                    'errors'      => null,
                    'error_codes' => null,
                ]
            )
        );

        $this->assertStringContainsString( 'Reset Password', $output );
        $this->assertStringNotContainsString( 'id="resetAlert"', $output );
    }

    public function test_form_password_reset_renders_cleanly_initial_state_empty_view_model(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-reset',
                [
                    'login' => 'test_user',
                    'key'   => 'test_secret_key',
                ]
            )
        );

        $this->assertStringContainsString( 'Reset Password', $output );
        $this->assertStringContainsString( 'name="rp_login" value="test_user"', $output );
        $this->assertStringContainsString( 'name="rp_key" value="test_secret_key"', $output );
        $this->assertStringContainsString( 'name="racketmanager_nonce"', $output );
        $this->assertStringContainsString( 'name="password"', $output );
        $this->assertStringContainsString( 'name="rePassword"', $output );
        $this->assertStringContainsString( 'The password should be at least twelve characters long', $output );
        $this->assertStringNotContainsString( 'id="loginAlert"', $output );
        $this->assertStringNotContainsString( 'is-invalid', $output );
    }

    public function test_form_password_reset_renders_cleanly_with_explicit_empty_errors_array(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-reset',
                [
                    'login'       => 'test_user',
                    'key'         => 'test_secret_key',
                    'errors'      => [],
                    'error_codes' => [],
                ]
            )
        );

        $this->assertStringContainsString( 'Reset Password', $output );
        $this->assertStringNotContainsString( 'id="loginAlert"', $output );
    }

    public function test_form_password_reset_renders_errors_safely(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output_empty = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-reset',
                [
                    'errors'      => [ 'empty' ],
                    'error_codes' => [ 'password_reset_empty' ],
                ]
            )
        );

        $this->assertStringContainsString( 'id="loginAlert"', $output_empty );
        $this->assertStringContainsString( 'Error in password reset', $output_empty );
        $this->assertStringContainsString( 'New password must be entered', $output_empty );
        $this->assertStringContainsString( 'is-invalid', $output_empty );

        $output_mismatch = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-reset',
                [
                    'errors'      => [ 'mismatch' ],
                    'error_codes' => [ 'password_reset_mismatch' ],
                ]
            )
        );

        $this->assertStringContainsString( 'Passwords do not match', $output_mismatch );

        $output_timeout = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-reset',
                [
                    'errors'      => [ 'Session timeout message' ],
                    'error_codes' => [ 'form_has_timedout' ],
                ]
            )
        );

        $this->assertStringContainsString( 'Session timeout message', $output_timeout );
    }

    public function test_form_password_reset_handles_null_errors_and_missing_keys_without_typeerror(): void {
        $renderer = new Php_View_Renderer( $this->template_base_path );

        $output = $this->render_trapping_warnings(
            static fn() => $renderer->render_to_string(
                'forms/form-password-reset',
                [
                    'errors'      => null,
                    'error_codes' => null,
                ]
            )
        );

        $this->assertStringContainsString( 'Reset Password', $output );
        $this->assertStringContainsString( 'name="rp_login" value=""', $output );
        $this->assertStringContainsString( 'name="rp_key" value=""', $output );
        $this->assertStringNotContainsString( 'id="loginAlert"', $output );
    }

    public function test_production_render_pipeline_login_form_end_to_end(): void {
        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->site_name = 'Leigh Tennis';
        $racketmanager->site_url  = 'http://example.com';

        $handler = new Get_Login_Form_Handler(
            $this->createStub( Option_Gateway_Interface::class ),
            new Settings_Service( [] ),
            $racketmanager
        );
        $query   = new Get_Login_Form_Query(
            redirect_to: 'http://example.com/members',
            is_logged_in: false
        );

        $vm = $handler->handle( $query );

        $php_renderer      = new Php_View_Renderer( $this->template_base_path );
        $template_renderer = new Shortcode_Template_Renderer( $php_renderer );
        $presenter         = new Shortcode_View_Presenter( $template_renderer );

        $output = $this->render_trapping_warnings(
            static fn() => $presenter->render( $vm )
        );

        $this->assertStringContainsString( 'action="https://example.com/wp-login.php"', $output );
        $this->assertStringContainsString( 'name="redirect_to" value="http://example.com/members"', $output );
        $this->assertStringNotContainsString( 'alert--danger', $output );
    }

    public function test_production_render_pipeline_login_form_with_errors_end_to_end(): void {
        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->site_name = 'Leigh Tennis';
        $racketmanager->site_url  = 'http://example.com';

        $handler = new Get_Login_Form_Handler(
            $this->createStub( Option_Gateway_Interface::class ),
            new Settings_Service( [] ),
            $racketmanager
        );
        $query   = new Get_Login_Form_Query(
            login_errors: 'empty_username,incorrect_password',
            is_logged_in: false
        );

        $vm = $handler->handle( $query );

        $php_renderer      = new Php_View_Renderer( $this->template_base_path );
        $template_renderer = new Shortcode_Template_Renderer( $php_renderer );
        $presenter         = new Shortcode_View_Presenter( $template_renderer );

        $output = $this->render_trapping_warnings(
            static fn() => $presenter->render( $vm )
        );

        $this->assertStringContainsString( 'id="loginAlert"', $output );
        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'Email address must be entered.', $output );
        $this->assertStringContainsString( 'Incorrect password.', $output );
    }

    public function test_production_render_pipeline_password_lost_form_end_to_end(): void {
        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->site_name = 'Leigh Tennis';
        $racketmanager->site_url  = 'http://example.com';

        $handler = new Get_Password_Lost_Form_Handler( $racketmanager );
        $query   = new Get_Password_Lost_Form_Query(
            errors: 'empty_username'
        );

        $vm = $handler->handle( $query );

        $php_renderer      = new Php_View_Renderer( $this->template_base_path );
        $template_renderer = new Shortcode_Template_Renderer( $php_renderer );
        $presenter         = new Shortcode_View_Presenter( $template_renderer );

        $output = $this->render_trapping_warnings(
            static fn() => $presenter->render( $vm )
        );

        $this->assertStringContainsString( 'id="resetAlert"', $output );
        $this->assertStringContainsString( 'Email address must be entered', $output );
    }

    public function test_production_render_pipeline_password_reset_form_end_to_end(): void {
        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->site_name = 'Leigh Tennis';
        $racketmanager->site_url  = 'http://example.com';

        $handler = new Get_Password_Reset_Form_Handler( $racketmanager );
        $query   = new Get_Password_Reset_Form_Query(
            login: 'member_user',
            key: 'member_secret_key',
            error: 'password_reset_empty'
        );

        $vm = $handler->handle( $query );

        $php_renderer      = new Php_View_Renderer( $this->template_base_path );
        $template_renderer = new Shortcode_Template_Renderer( $php_renderer );
        $presenter         = new Shortcode_View_Presenter( $template_renderer );

        $output = $this->render_trapping_warnings(
            static fn() => $presenter->render( $vm )
        );

        $this->assertStringContainsString( 'name="rp_login" value="member_user"', $output );
        $this->assertStringContainsString( 'name="rp_key" value="member_secret_key"', $output );
        $this->assertStringContainsString( 'id="loginAlert"', $output );
        $this->assertStringContainsString( 'New password must be entered', $output );
    }
}
