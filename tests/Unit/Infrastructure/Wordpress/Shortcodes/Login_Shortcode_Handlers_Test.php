<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Query;
use Racketmanager\Domain\Player\User;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes_Login;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Util\Util;

class Login_Shortcode_Handlers_Test extends TestCase {
    use Creates_Test_Container;

    private RacketManager $racketmanager;
    private View_Renderer_Interface $renderer;
    private Shortcode_View_Presenter $view_presenter;

    protected function setUp(): void {
        parent::setUp();

        $this->racketmanager = $this->createStub( RacketManager::class );
        $this->racketmanager->site_name = 'Leigh Tennis';
        $this->racketmanager->site_url  = 'http://example.com';

        $this->renderer = new class implements View_Renderer_Interface {
            public array $rendered_templates = [];
            public function render( string $template, object|array $data, array $additional_vars = [] ): void {}
            public function render_to_string( string $template, object|array $data, array $additional_vars = [] ): string {
                $this->rendered_templates[] = [ 'template' => $template, 'data' => $data, 'folder' => 'templates' ];
                return '<div class="rendered-' . $template . '">' . ( $data['site_name'] ?? '' ) . '</div>';
            }
            public function template_exists( string $template ): bool {
                return true;
            }
        };

        $this->view_presenter = new Shortcode_View_Presenter( new \Racketmanager\Services\View\Shortcode_Template_Renderer( $this->renderer ) );
    }

    public function test_login_form_handler_logged_in_returns_redirect_script(): void {
        $handler = $this->login_form_handler();
        $query   = new Get_Login_Form_Query(
            redirect_to: 'http://example.com/dashboard',
            is_logged_in: true,
            request_method: 'GET'
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_raw_html() );
        $this->assertStringContainsString( '<script>location.href = "http://example.com/dashboard"</script>', $vm->raw_html );
    }

    public function test_login_form_handler_login_form_template_vars(): void {
        $handler = $this->login_form_handler( keys: [ 'recaptchaSiteKey' => 'site-key-123' ] );
        $query   = new Get_Login_Form_Query(
            show_title: true,
            action: '',
            registered: true,
            redirect_to: 'http://example.com/matches',
            login_errors: 'empty_username,invalid_email',
            checkemail: 'confirm',
            password_updated: true,
            logged_out: true,
            is_logged_in: false
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'form-login', $vm->template );
        $this->assertSame( 'form', $vm->template_type );

        $data = $vm->vars;
        $this->assertTrue( $data['show_title'] );
        $this->assertSame( 'Leigh Tennis', $data['site_name'] );
        $this->assertTrue( $data['registered'] );
        $this->assertTrue( $data['lost_password_sent'] );
        $this->assertTrue( $data['password_updated'] );
        $this->assertTrue( $data['logged_out'] );
        $this->assertSame( 'http://example.com/matches', $data['redirect'] );
        $this->assertSame( 'site-key-123', $data['recaptcha_site_key'] );
        $this->assertCount( 2, $data['errors'] );
        $this->assertCount( 2, $data['error_codes'] );
    }

    public function test_login_form_handler_register_action_when_registration_allowed(): void {
        $handler = $this->login_form_handler( users_can_register: 1 );
        $query   = new Get_Login_Form_Query(
            action: 'register',
            register_errors: 'empty_username',
            is_logged_in: false
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'form-login', $vm->template );
        $data = $vm->vars;
        $this->assertCount( 1, $data['error_codes'] );
    }

    public function test_login_form_handler_register_action_when_registration_disallowed(): void {
        $handler = $this->login_form_handler( users_can_register: 0 );
        $query   = new Get_Login_Form_Query(
            action: 'register',
            is_logged_in: false
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_raw_html() );
        $this->assertStringContainsString( 'Registering new users is currently not allowed', $vm->raw_html );
    }

    public function test_password_lost_form_handler_when_logged_in(): void {
        $handler = new Get_Password_Lost_Form_Handler();
        $query   = new Get_Password_Lost_Form_Query(
            is_logged_in: true
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_raw_html() );
        $this->assertStringContainsString( 'You are already signed in', $vm->raw_html );
    }

    public function test_password_lost_form_handler_when_logged_out(): void {
        $handler = new Get_Password_Lost_Form_Handler();
        $query   = new Get_Password_Lost_Form_Query(
            show_title: true,
            errors: 'invalid_email',
            checkemail: 'confirm',
            is_logged_in: false
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'form-password-lost', $vm->template );
        $data = $vm->vars;
        $this->assertTrue( $data['show_title'] );
        $this->assertTrue( $data['lost_password_sent'] );
        $this->assertCount( 1, $data['errors'] );
    }

    public function test_password_reset_form_handler_invalid_link(): void {
        $handler = new Get_Password_Reset_Form_Handler();
        $query   = new Get_Password_Reset_Form_Query(
            login: '',
            key: ''
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_raw_html() );
        $this->assertStringContainsString( 'Invalid password reset link.', $vm->raw_html );
    }

    public function test_password_reset_form_handler_valid_link(): void {
        $handler = new Get_Password_Reset_Form_Handler();
        $query   = new Get_Password_Reset_Form_Query(
            show_title: false,
            login: 'john_doe',
            key: 'secret123',
            error: 'password_reset_empty'
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'form-password-reset', $vm->template );
        $data = $vm->vars;
        $this->assertSame( 'john_doe', $data['login'] );
        $this->assertSame( 'secret123', $data['key'] );
        $this->assertArrayHasKey( 'errors', $data );
        $this->assertArrayHasKey( 'error_codes', $data );
        $this->assertCount( 1, $data['errors'] );
    }

    public function test_member_account_form_handler_logged_out(): void {
        $user_repository = $this->createStub( User_Repository_Interface::class );
        $handler         = new Get_Member_Account_Form_Handler( $user_repository );
        $query           = new Get_Member_Account_Form_Query(
            is_logged_in: false,
            user_id: null
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_raw_html() );
        $this->assertStringContainsString( 'You must be signed in to access this page', $vm->raw_html );
    }

    public function test_member_account_form_handler_logged_in(): void {
        $user = $this->createStub( User::class );
        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 123 )
            ->willReturn( $user );

        $handler = new Get_Member_Account_Form_Handler( $user_repository );
        $query   = new Get_Member_Account_Form_Query(
            is_logged_in: true,
            user_id: 123
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'form-member-account', $vm->template );
        $this->assertSame( 'form', $vm->template_type );
        $this->assertSame( $user, $vm->vars['user'] );
        $this->assertSame( Util::get_email_opt_ins(), $vm->vars['opt_in_choices'] );
    }

    public function test_adapter_and_facade_delegation(): void {
        $login_handler          = $this->login_form_handler( users_can_register: 0 );
        $password_lost_handler  = new Get_Password_Lost_Form_Handler();
        $password_reset_handler = new Get_Password_Reset_Form_Handler();
        $member_account_handler = new Get_Member_Account_Form_Handler(
            $this->createStub( User_Repository_Interface::class )
        );

        $adapter = new Login_Shortcode_Adapter(
            $this->racketmanager,
            $this->view_presenter,
            $login_handler,
            $password_lost_handler,
            $password_reset_handler,
            $member_account_handler
        );

        $this->racketmanager->container = $this->create_container_with_services( [ 'login_shortcode_adapter' => $adapter ] );

        $facade = new Shortcodes_Login( $this->racketmanager );

        $facade->load_translations();
        $this->assertNotEmpty( $facade->render_login_form( [] ) );
        $GLOBALS['wp_stubs_options']['users_can_register'] = 0;
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $register_output = $facade->form_register();
        unset( $GLOBALS['wp_stubs_is_user_logged_in'] );
        $this->assertSame(
            'Registering new users is currently not allowed',
            $register_output
        );
        $this->assertNotEmpty( $facade->form_login( [] ) );
        $this->assertNotEmpty( $facade->render_password_lost_form( [] ) );
        $this->assertNotEmpty( $facade->render_password_reset_form( [ 'login' => 'user', 'key' => 'k' ] ) );
        $this->assertNotEmpty( $facade->generate_member_account_form() );
    }

    public function test_login_form_handler_decodes_html_entities_in_site_name(): void {
        $handler = $this->login_form_handler( site_name: 'Leigh &amp; Westcliff Tennis' );
        $query   = new Get_Login_Form_Query( is_logged_in: false );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'Leigh & Westcliff Tennis', $vm->vars['site_name'] );
    }

    public function test_from_atts_normalizes_empty_action_override_to_get_action(): void {
        $query = Get_Login_Form_Query::from_atts(
            atts: [],
            query_vars: [],
            get_params: [ 'action' => 'register' ],
            action_override: ''
        );

        $this->assertSame( 'register', $query->action );
    }

    public function test_from_atts_normalizes_whitespace_action_override_to_get_action(): void {
        $query = Get_Login_Form_Query::from_atts(
            atts: [],
            query_vars: [],
            get_params: [ 'action' => 'register' ],
            action_override: '   '
        );

        $this->assertSame( 'register', $query->action );
    }

    public function test_from_atts_preserves_explicit_action_override_over_get_action(): void {
        $query = Get_Login_Form_Query::from_atts(
            atts: [],
            query_vars: [],
            get_params: [ 'action' => 'login' ],
            action_override: 'register'
        );

        $this->assertSame( 'register', $query->action );
    }

    public function test_from_atts_sanitizes_action(): void {
        $query = Get_Login_Form_Query::from_atts(
            atts: [],
            query_vars: [],
            get_params: [ 'action' => '<script>alert(1)</script>custom' ],
            action_override: null
        );

        $this->assertSame( 'alert(1)custom', $query->action );
    }

    /**
     * @param mixed $users_can_register
     * @param array<string, mixed> $keys
     */
    private function login_form_handler( mixed $users_can_register = false, array $keys = [], string $site_name = 'Leigh Tennis' ): Get_Login_Form_Handler {
        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            static function ( string $name, mixed $default = false ) use ( $users_can_register, $site_name ) {
                if ( 'users_can_register' === $name ) {
                    return $users_can_register;
                }
                if ( 'blogname' === $name ) {
                    return $site_name;
                }
                if ( 'siteurl' === $name ) {
                    return 'http://example.com';
                }
                return $default;
            }
        );

        return new Get_Login_Form_Handler(
            $option_gateway,
            new Settings_Service( [ 'keys' => $keys ] )
        );
    }
}
