<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Request\Auth;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Custom_Login_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Password_Reset_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Password_Reset_Route_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\User_Registration_Request;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use WP_Error;

final class Auth_Requests_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    // ==========================================
    // Custom_Login_Request tests
    // ==========================================

    public function test_custom_login_request_parses_get_and_redirect_to(): void {
        $server  = [ 'REQUEST_METHOD' => 'GET' ];
        $request = [ 'redirect_to' => 'https://example.com/dashboard' ];

        $dto = Custom_Login_Request::from_globals( $server, $request );

        $this->assertTrue( $dto->has_http_method() );
        $this->assertTrue( $dto->is_get() );
        $this->assertFalse( $dto->is_post() );
        $this->assertSame( 'GET', $dto->get_http_method() );
        $this->assertSame( 'https://example.com/dashboard', $dto->get_redirect_to() );
        $this->assertTrue( $dto->has_redirect_to() );
    }

    public function test_custom_login_request_handles_missing_or_empty_redirect_to(): void {
        $server  = [ 'REQUEST_METHOD' => 'POST' ];
        $request = [];

        $dto = Custom_Login_Request::from_globals( $server, $request );

        $this->assertTrue( $dto->has_http_method() );
        $this->assertFalse( $dto->is_get() );
        $this->assertTrue( $dto->is_post() );
        $this->assertSame( 'POST', $dto->get_http_method() );
        $this->assertNull( $dto->get_redirect_to() );
        $this->assertFalse( $dto->has_redirect_to() );
    }

    public function test_custom_login_request_handles_non_string_input(): void {
        $server  = [ 'REQUEST_METHOD' => [ 'GET' ] ];
        $request = [ 'redirect_to' => [ 'https://malicious.com' ] ];

        $dto = Custom_Login_Request::from_globals( $server, $request );

        $this->assertFalse( $dto->has_http_method() );
        $this->assertNull( $dto->get_http_method() );
        $this->assertFalse( $dto->is_get() );
        $this->assertFalse( $dto->is_post() );
        $this->assertNull( $dto->get_redirect_to() );
        $this->assertFalse( $dto->has_redirect_to() );
    }

    public function test_custom_login_request_normalizes_method_and_handles_absent_method(): void {
        // Lowercase and whitespace trimmed
        $dto_trimmed = Custom_Login_Request::from_globals( [ 'REQUEST_METHOD' => '  get  ' ], [] );
        $this->assertTrue( $dto_trimmed->has_http_method() );
        $this->assertTrue( $dto_trimmed->is_get() );
        $this->assertSame( 'GET', $dto_trimmed->get_http_method() );

        // Absent method
        $dto_absent = Custom_Login_Request::from_globals( [], [] );
        $this->assertFalse( $dto_absent->has_http_method() );
        $this->assertNull( $dto_absent->get_http_method() );
        $this->assertFalse( $dto_absent->is_get() );
        $this->assertFalse( $dto_absent->is_post() );
    }

    public function test_custom_login_request_falls_back_to_superglobals(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST['redirect_to']   = 'https://example.com/members';

        $dto = Custom_Login_Request::from_globals();

        $this->assertTrue( $dto->is_get() );
        $this->assertSame( 'https://example.com/members', $dto->get_redirect_to() );
    }

    // ==========================================
    // User_Registration_Request tests
    // ==========================================

    public function test_user_registration_request_parses_and_sanitizes_post_data(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];
        $post   = [
            'racketmanager_register_nonce' => 'test_nonce_123',
            'g-recaptcha-response'         => 'recaptcha_token_xyz',
            'email'                        => '  User@Example.COM  ',
            'first_name'                   => '<b>John</b>',
            'last_name'                    => 'Doe',
        ];

        $dto = User_Registration_Request::from_globals( $server, $post );

        $this->assertTrue( $dto->has_http_method() );
        $this->assertTrue( $dto->is_post() );
        $this->assertFalse( $dto->is_get() );
        $this->assertSame( 'POST', $dto->get_http_method() );
        $this->assertSame( 'test_nonce_123', $dto->get_nonce() );
        $this->assertSame( 'recaptcha_token_xyz', $dto->get_recaptcha_token() );
        $this->assertTrue( $dto->has_recaptcha_token() );
        $this->assertSame( 'User@Example.COM', $dto->get_email() );
        $this->assertSame( 'John', $dto->get_first_name() );
        $this->assertSame( 'Doe', $dto->get_last_name() );
        $this->assertTrue( $dto->has_email() );
        $this->assertTrue( $dto->has_first_name() );
        $this->assertTrue( $dto->has_last_name() );
        $this->assertSame( [], $dto->validate_field_errors() );
    }

    public function test_user_registration_request_handles_absent_and_non_post_methods(): void {
        // Absent method: has_http_method is false, is_post is false
        $dto_absent = User_Registration_Request::from_globals( [], [] );
        $this->assertFalse( $dto_absent->has_http_method() );
        $this->assertNull( $dto_absent->get_http_method() );
        $this->assertFalse( $dto_absent->is_post() );
        // Future adapter distinguishes absent method from non-POST:
        $is_non_post_absent = $dto_absent->has_http_method() && ! $dto_absent->is_post();
        $this->assertFalse( $is_non_post_absent, 'Absent method must not be treated as non-POST' );

        // GET method: has_http_method is true, is_post is false -> non-POST
        $dto_get = User_Registration_Request::from_globals( [ 'REQUEST_METHOD' => 'GET' ], [] );
        $this->assertTrue( $dto_get->has_http_method() );
        $this->assertTrue( $dto_get->is_get() );
        $this->assertFalse( $dto_get->is_post() );
        $is_non_post_get = $dto_get->has_http_method() && ! $dto_get->is_post();
        $this->assertTrue( $is_non_post_get, 'GET method must be recognized as non-POST' );

        // POST method
        $dto_post = User_Registration_Request::from_globals( [ 'REQUEST_METHOD' => '  post  ' ], [] );
        $this->assertTrue( $dto_post->has_http_method() );
        $this->assertTrue( $dto_post->is_post() );
        $is_non_post_post = $dto_post->has_http_method() && ! $dto_post->is_post();
        $this->assertFalse( $is_non_post_post, 'POST method must not be recognized as non-POST' );

        // Array input for method
        $dto_invalid = User_Registration_Request::from_globals( [ 'REQUEST_METHOD' => [ 'POST' ] ], [] );
        $this->assertFalse( $dto_invalid->has_http_method() );
        $this->assertNull( $dto_invalid->get_http_method() );
    }

    public function test_user_registration_request_handles_non_string_post_fields(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];
        $post   = [
            'racketmanager_register_nonce' => [ 'array_nonce' ],
            'g-recaptcha-response'         => 12345,
            'email'                        => [ 'nested@example.com' ],
            'first_name'                   => false,
            'last_name'                    => [ 'invalid' ],
        ];

        $dto = User_Registration_Request::from_globals( $server, $post );

        $this->assertSame( '', $dto->get_nonce() );
        $this->assertFalse( $dto->verify_nonce() );
        $this->assertSame( '', $dto->get_recaptcha_token() );
        $this->assertFalse( $dto->has_recaptcha_token() );
        $this->assertSame( '', $dto->get_email() );
        $this->assertSame( '', $dto->get_first_name() );
        $this->assertSame( '', $dto->get_last_name() );
        $this->assertSame( [ 'email', 'first_name', 'last_name' ], $dto->validate_field_errors() );
    }

    public function test_user_registration_request_nonce_verification(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];

        // Missing nonce
        $dto_missing = User_Registration_Request::from_globals( $server, [] );
        $this->assertFalse( $dto_missing->verify_nonce() );

        // Valid nonce via stub
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $dto_valid = User_Registration_Request::from_globals( $server, [ 'racketmanager_register_nonce' => 'valid' ] );
        $this->assertTrue( $dto_valid->verify_nonce() );

        // Invalid nonce via stub
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;
        $this->assertFalse( $dto_valid->verify_nonce() );
    }

    public function test_user_registration_request_recaptcha_verification(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];

        // Empty token immediately returns false
        $dto_no_token = User_Registration_Request::from_globals( $server, [] );
        $this->assertFalse( $dto_no_token->has_recaptcha_token() );
        $this->assertFalse( $dto_no_token->verify_recaptcha( 'secret_key' ) );

        // Token present, but empty secret key returns false
        $dto_with_token = User_Registration_Request::from_globals( $server, [ 'g-recaptcha-response' => 'token' ] );
        $this->assertTrue( $dto_with_token->has_recaptcha_token() );
        $this->assertFalse( $dto_with_token->verify_recaptcha( '' ) );

        // Verification success with string secret key
        $GLOBALS['wp_stubs_wp_remote_post_return'] = [ 'body' => '{"success":true}' ];
        $this->assertTrue( $dto_with_token->verify_recaptcha( 'secret_123' ) );

        // Verification failure with remote error response
        $GLOBALS['wp_stubs_wp_remote_post_return'] = [ 'body' => '{"success":false}' ];
        $this->assertFalse( $dto_with_token->verify_recaptcha( 'secret_123' ) );

        // Verification using Option_Service mock
        $option_service = $this->createMock( Option_Service::class );
        $option_service->expects( $this->once() )
            ->method( 'get_options' )
            ->with( 'keys' )
            ->willReturn( [ 'recaptchaSecretKey' => 'configured_secret' ] );

        $GLOBALS['wp_stubs_wp_remote_post_return'] = [ 'body' => '{"success":true}' ];
        $this->assertTrue( $dto_with_token->verify_recaptcha( $option_service ) );

        // Option_Service with empty secret key
        $empty_option_service = $this->createMock( Option_Service::class );
        $empty_option_service->expects( $this->once() )
            ->method( 'get_options' )
            ->with( 'keys' )
            ->willReturn( [] );
        $this->assertFalse( $dto_with_token->verify_recaptcha( $empty_option_service ) );
    }

    public function test_user_registration_request_recaptcha_failure_edge_cases(): void {
        $server         = [ 'REQUEST_METHOD' => 'POST' ];
        $dto_with_token = User_Registration_Request::from_globals( $server, [ 'g-recaptcha-response' => 'valid_token' ] );

        // WP_Error return from wp_remote_post
        $GLOBALS['wp_stubs_wp_remote_post_return'] = new WP_Error( 'http_request_failed', 'Connection timeout' );
        $this->assertFalse( $dto_with_token->verify_recaptcha( 'secret_key' ) );

        // Malformed non-JSON body
        $GLOBALS['wp_stubs_wp_remote_post_return'] = [ 'body' => '<html lang="en-GB">502 Bad Gateway</html>' ];
        $this->assertFalse( $dto_with_token->verify_recaptcha( 'secret_key' ) );

        // JSON response without "success" key
        $GLOBALS['wp_stubs_wp_remote_post_return'] = [ 'body' => '{"error-codes":["missing-input-response"]}' ];
        $this->assertFalse( $dto_with_token->verify_recaptcha( 'secret_key' ) );

        // Non-array response
        $GLOBALS['wp_stubs_wp_remote_post_return'] = false;
        $this->assertFalse( $dto_with_token->verify_recaptcha( 'secret_key' ) );
    }

    public function test_user_registration_request_detects_missing_field_errors(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];

        $dto_empty = User_Registration_Request::from_globals( $server, [] );
        $this->assertSame( [ 'email', 'first_name', 'last_name' ], $dto_empty->validate_field_errors() );

        $dto_email_only = User_Registration_Request::from_globals( $server, [ 'email' => 'test@example.com' ] );
        $this->assertSame( [ 'first_name', 'last_name' ], $dto_email_only->validate_field_errors() );

        $dto_no_last_name = User_Registration_Request::from_globals(
            $server,
            [
                'email'      => 'test@example.com',
                'first_name' => 'Alice',
            ]
        );
        $this->assertSame( [ 'last_name' ], $dto_no_last_name->validate_field_errors() );
    }

    public function test_user_registration_request_falls_back_to_superglobals(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['email']            = 'super@example.com';
        $_POST['first_name']       = 'Global';
        $_POST['last_name']        = 'User';

        $dto = User_Registration_Request::from_globals();

        $this->assertTrue( $dto->is_post() );
        $this->assertSame( 'super@example.com', $dto->get_email() );
        $this->assertSame( 'Global', $dto->get_first_name() );
        $this->assertSame( 'User', $dto->get_last_name() );
    }

    // ==========================================
    // Password_Reset_Route_Request tests
    // ==========================================

    public function test_password_reset_route_request_parses_get_parameters(): void {
        $server = [ 'REQUEST_METHOD' => 'GET' ];
        $get    = [
            'key'   => 'abc-123_XYZ!',
            'login' => 'alice_smith',
        ];

        $dto = Password_Reset_Route_Request::from_globals( $server, $get );

        $this->assertTrue( $dto->has_http_method() );
        $this->assertTrue( $dto->is_get() );
        $this->assertFalse( $dto->is_post() );
        $this->assertSame( 'GET', $dto->get_http_method() );
        $this->assertSame( 'abc-123_XYZ!', $dto->get_key() );
        $this->assertSame( 'abc123XYZ', $dto->get_clean_key() );
        $this->assertSame( 'alice_smith', $dto->get_login() );
        $this->assertTrue( $dto->has_key() );
        $this->assertTrue( $dto->has_login() );
    }

    public function test_password_reset_route_request_handles_missing_params(): void {
        $server = [ 'REQUEST_METHOD' => 'GET' ];
        $dto    = Password_Reset_Route_Request::from_globals( $server, [] );

        $this->assertSame( '', $dto->get_key() );
        $this->assertSame( '', $dto->get_clean_key() );
        $this->assertSame( '', $dto->get_login() );
        $this->assertFalse( $dto->has_key() );
        $this->assertFalse( $dto->has_login() );
        $this->assertFalse( $dto->check_key() );
    }

    public function test_password_reset_route_request_handles_non_string_input(): void {
        $server = [ 'REQUEST_METHOD' => [ 'GET' ] ];
        $get    = [
            'key'   => [ 'array_key' ],
            'login' => 12345,
        ];

        $dto = Password_Reset_Route_Request::from_globals( $server, $get );

        $this->assertFalse( $dto->has_http_method() );
        $this->assertNull( $dto->get_http_method() );
        $this->assertSame( '', $dto->get_key() );
        $this->assertSame( '', $dto->get_clean_key() );
        $this->assertSame( '', $dto->get_login() );
        $this->assertFalse( $dto->has_key() );
        $this->assertFalse( $dto->has_login() );
        $this->assertFalse( $dto->check_key() );
    }

    public function test_password_reset_route_request_method_handling(): void {
        $dto_absent = Password_Reset_Route_Request::from_globals( [], [] );
        $this->assertFalse( $dto_absent->has_http_method() );
        $this->assertNull( $dto_absent->get_http_method() );
        $this->assertFalse( $dto_absent->is_get() );
        $this->assertFalse( $dto_absent->is_post() );

        $dto_trimmed = Password_Reset_Route_Request::from_globals( [ 'REQUEST_METHOD' => '  get  ' ], [] );
        $this->assertTrue( $dto_trimmed->has_http_method() );
        $this->assertTrue( $dto_trimmed->is_get() );
        $this->assertSame( 'GET', $dto_trimmed->get_http_method() );
    }

    public function test_password_reset_route_request_check_key_interaction(): void {
        $server = [ 'REQUEST_METHOD' => 'GET' ];
        $dto    = Password_Reset_Route_Request::from_globals( $server, [ 'key' => 'validkey', 'login' => 'user1' ] );

        // Success case
        $user = $dto->check_key();
        $this->assertIsObject( $user );
        $this->assertSame( 'user1', $user->user_login );

        // Expired key error case
        $GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'expired_key', 'Expired' );
        $result = $dto->check_key();
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'expired_key', $result->get_error_code() );

        // Invalid key error case
        $GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'invalid_key', 'Invalid' );
        $result = $dto->check_key();
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'invalid_key', $result->get_error_code() );
    }

    public function test_password_reset_route_request_falls_back_to_superglobals(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['key']               = 'superkey123';
        $_GET['login']             = 'bob';

        $dto = Password_Reset_Route_Request::from_globals();

        $this->assertTrue( $dto->is_get() );
        $this->assertSame( 'superkey123', $dto->get_key() );
        $this->assertSame( 'bob', $dto->get_login() );
    }

    // ==========================================
    // Password_Reset_Request tests
    // ==========================================

    public function test_password_reset_request_parses_post_parameters(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];
        $post   = [
            'racketmanager_nonce' => 'reset_nonce_123',
            'rp_key'              => 'reset_key_abc',
            'rp_login'            => 'reset_user',
            'password'            => 'NewSecretPass1!',
            'rePassword'          => 'NewSecretPass1!',
        ];

        $dto = Password_Reset_Request::from_globals( $server, $post );

        $this->assertTrue( $dto->has_http_method() );
        $this->assertTrue( $dto->is_post() );
        $this->assertFalse( $dto->is_get() );
        $this->assertSame( 'POST', $dto->get_http_method() );
        $this->assertSame( 'reset_nonce_123', $dto->get_nonce() );
        $this->assertSame( 'reset_key_abc', $dto->get_rp_key() );
        $this->assertSame( 'reset_user', $dto->get_rp_login() );
        $this->assertTrue( $dto->has_rp_key() );
        $this->assertTrue( $dto->has_rp_login() );
        $this->assertSame( 'NewSecretPass1!', $dto->get_password() );
        $this->assertSame( 'NewSecretPass1!', $dto->get_re_password() );
        $this->assertTrue( $dto->passwords_match() );
        $this->assertFalse( $dto->is_password_empty() );
    }

    public function test_password_reset_request_passwords_match_semantics_and_empty_checks(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];

        // Case 1: Both password and re_password are null (omitted from POST)
        // Preserves legacy PHP behavior: (null !== null) is false, so passwords match, empty(null) is true -> password_reset_empty
        $dto_both_null = Password_Reset_Request::from_globals( $server, [] );
        $this->assertNull( $dto_both_null->get_password() );
        $this->assertNull( $dto_both_null->get_re_password() );
        $this->assertTrue( $dto_both_null->passwords_match(), 'Both null passwords must match (null === null)' );
        $this->assertTrue( $dto_both_null->is_password_empty(), 'Null password must be considered empty' );

        // Case 2: Both password and re_password are empty strings
        $dto_both_empty_strings = Password_Reset_Request::from_globals(
            $server,
            [
                'password'   => '',
                'rePassword' => '',
            ]
        );
        $this->assertTrue( $dto_both_empty_strings->passwords_match() );
        $this->assertTrue( $dto_both_empty_strings->is_password_empty() );

        // Case 3: Passwords mismatch
        $dto_mismatch = Password_Reset_Request::from_globals(
            $server,
            [
                'password'   => 'passwordA',
                'rePassword' => 'passwordB',
            ]
        );
        $this->assertFalse( $dto_mismatch->passwords_match() );
        $this->assertFalse( $dto_mismatch->is_password_empty() );

        // Case 4: One is null, other is provided
        $dto_one_null = Password_Reset_Request::from_globals(
            $server,
            [
                'password' => 'passwordA',
            ]
        );
        $this->assertFalse( $dto_one_null->passwords_match() );
        $this->assertFalse( $dto_one_null->is_password_empty() );

        // Case 5: Passwords match and non-empty
        $dto_valid = Password_Reset_Request::from_globals(
            $server,
            [
                'password'   => 'ValidPass123',
                'rePassword' => 'ValidPass123',
            ]
        );
        $this->assertTrue( $dto_valid->passwords_match() );
        $this->assertFalse( $dto_valid->is_password_empty() );
    }

    public function test_password_reset_request_nonce_verification(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];

        // Missing nonce
        $dto_missing = Password_Reset_Request::from_globals( $server, [] );
        $this->assertFalse( $dto_missing->verify_nonce() );

        // Valid nonce via stub
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $dto_valid = Password_Reset_Request::from_globals( $server, [ 'racketmanager_nonce' => 'valid_nonce' ] );
        $this->assertTrue( $dto_valid->verify_nonce() );

        // Invalid nonce via stub
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;
        $this->assertFalse( $dto_valid->verify_nonce() );
    }

    public function test_password_reset_request_check_key_interaction_with_expired_and_invalid_keys(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];

        // Missing key/login returns false
        $dto_missing = Password_Reset_Request::from_globals( $server, [] );
        $this->assertFalse( $dto_missing->has_rp_key() );
        $this->assertFalse( $dto_missing->has_rp_login() );
        $this->assertFalse( $dto_missing->check_key() );

        // Present key/login checks with core
        $dto = Password_Reset_Request::from_globals(
            $server,
            [
                'rp_key'   => 'mykey',
                'rp_login' => 'mylogin',
            ]
        );
        $this->assertTrue( $dto->has_rp_key() );
        $this->assertTrue( $dto->has_rp_login() );

        // Success case
        $user = $dto->check_key();
        $this->assertIsObject( $user );
        $this->assertSame( 'mylogin', $user->user_login );

        // Expired key error case
        $GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'expired_key', 'Expired' );
        $result = $dto->check_key();
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'expired_key', $result->get_error_code() );

        // Invalid key error case
        $GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'invalid_key', 'Invalid key' );
        $result = $dto->check_key();
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'invalid_key', $result->get_error_code() );
    }

    public function test_password_reset_request_adapter_flow_sequence(): void {
        // Simulates the exact sequential validation pipeline used by the Step 3 adapter:
        // 1. verify_nonce() -> 'form_has_timedout'
        // 2. check_key() -> 'expiredkey' or 'invalidkey'
        // 3. passwords_match() -> 'password_reset_mismatch'
        // 4. is_password_empty() -> 'password_reset_empty'
        // 5. valid -> proceed to reset_password()

        $evaluate_flow = static function ( Password_Reset_Request $request ): string {
            if ( ! $request->verify_nonce() ) {
                return 'form_has_timedout';
            }

            $user = $request->check_key();
            if ( ! $user || is_wp_error( $user ) ) {
                if ( $user instanceof WP_Error && 'expired_key' === $user->get_error_code() ) {
                    return 'expiredkey';
                }
                return 'invalidkey';
            }

            if ( ! $request->passwords_match() ) {
                return 'password_reset_mismatch';
            }

            if ( $request->is_password_empty() ) {
                return 'password_reset_empty';
            }

            return 'success';
        };

        $server = [ 'REQUEST_METHOD' => 'POST' ];

        // 1. Nonce fails
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;
        $dto1 = Password_Reset_Request::from_globals(
            $server,
            [
                'racketmanager_nonce' => 'bad_nonce',
                'rp_key'              => 'key',
                'rp_login'            => 'user',
            ]
        );
        $this->assertSame( 'form_has_timedout', $evaluate_flow( $dto1 ) );

        // 2. Nonce valid, key expired (must trigger expiredkey before checking passwords)
        $GLOBALS['wp_stubs_wp_verify_nonce_return']          = true;
        $GLOBALS['wp_stubs_check_password_reset_key_return'] = new WP_Error( 'expired_key', 'Expired' );
        $dto2 = Password_Reset_Request::from_globals(
            $server,
            [
                'racketmanager_nonce' => 'good_nonce',
                'rp_key'              => 'expired_key',
                'rp_login'            => 'user',
                'password'            => 'pass1',
                'rePassword'          => 'mismatch',
            ]
        );
        $this->assertSame( 'expiredkey', $evaluate_flow( $dto2 ), 'Key expiration must precede password mismatch check' );

        // 3. Nonce valid, key valid, passwords mismatch
        unset( $GLOBALS['wp_stubs_check_password_reset_key_return'] );
        $dto3 = Password_Reset_Request::from_globals(
            $server,
            [
                'racketmanager_nonce' => 'good_nonce',
                'rp_key'              => 'good_key',
                'rp_login'            => 'user',
                'password'            => 'pass1',
                'rePassword'          => 'mismatch',
            ]
        );
        $this->assertSame( 'password_reset_mismatch', $evaluate_flow( $dto3 ) );

        // 4. Nonce valid, key valid, both passwords missing (null)
        // Must yield password_reset_empty, NOT mismatch!
        $dto4 = Password_Reset_Request::from_globals(
            $server,
            [
                'racketmanager_nonce' => 'good_nonce',
                'rp_key'              => 'good_key',
                'rp_login'            => 'user',
            ]
        );
        $this->assertSame( 'password_reset_empty', $evaluate_flow( $dto4 ), 'Missing password fields must yield password_reset_empty' );

        // 5. Nonce valid, key valid, matching valid password
        $dto5 = Password_Reset_Request::from_globals(
            $server,
            [
                'racketmanager_nonce' => 'good_nonce',
                'rp_key'              => 'good_key',
                'rp_login'            => 'user',
                'password'            => 'NewSecurePass123',
                'rePassword'          => 'NewSecurePass123',
            ]
        );
        $this->assertSame( 'success', $evaluate_flow( $dto5 ) );
    }

    public function test_password_reset_request_handles_non_string_post_data(): void {
        $server = [ 'REQUEST_METHOD' => 'POST' ];
        $post   = [
            'racketmanager_nonce' => [ 'bad_nonce' ],
            'rp_key'              => [ 'bad_key' ],
            'rp_login'            => 12345,
            'password'            => [ 'bad_pass' ],
            'rePassword'          => false,
        ];

        $dto = Password_Reset_Request::from_globals( $server, $post );

        $this->assertNull( $dto->get_nonce() );
        $this->assertFalse( $dto->verify_nonce() );
        $this->assertNull( $dto->get_rp_key() );
        $this->assertNull( $dto->get_rp_login() );
        $this->assertFalse( $dto->has_rp_key() );
        $this->assertFalse( $dto->has_rp_login() );
        $this->assertFalse( $dto->check_key() );
        $this->assertNull( $dto->get_password() );
        $this->assertNull( $dto->get_re_password() );
        $this->assertTrue( $dto->passwords_match() );
        $this->assertTrue( $dto->is_password_empty() );
    }

    public function test_password_reset_request_method_handling(): void {
        // Absent method
        $dto_absent = Password_Reset_Request::from_globals( [], [] );
        $this->assertFalse( $dto_absent->has_http_method() );
        $this->assertNull( $dto_absent->get_http_method() );
        $this->assertFalse( $dto_absent->is_post() );
        $this->assertFalse( $dto_absent->is_get() );

        // Normalized method
        $dto_post = Password_Reset_Request::from_globals( [ 'REQUEST_METHOD' => '  post  ' ], [] );
        $this->assertTrue( $dto_post->has_http_method() );
        $this->assertTrue( $dto_post->is_post() );
        $this->assertSame( 'POST', $dto_post->get_http_method() );

        // Array method
        $dto_array = Password_Reset_Request::from_globals( [ 'REQUEST_METHOD' => [ 'POST' ] ], [] );
        $this->assertFalse( $dto_array->has_http_method() );
        $this->assertNull( $dto_array->get_http_method() );
    }

    public function test_password_reset_request_falls_back_to_superglobals(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['racketmanager_nonce'] = 'global_nonce';
        $_POST['password']            = 'Secret123';
        $_POST['rePassword']          = 'Secret123';

        $dto = Password_Reset_Request::from_globals();

        $this->assertTrue( $dto->is_post() );
        $this->assertSame( 'global_nonce', $dto->get_nonce() );
        $this->assertSame( 'Secret123', $dto->get_password() );
    }
}
