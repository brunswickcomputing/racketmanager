<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Login\DTOs\Login_Form_Result;
use Racketmanager\Application\Login\DTOs\Member_Account_Form_Result;
use Racketmanager\Application\Login\DTOs\Password_Lost_Form_Result;
use Racketmanager\Application\Login\DTOs\Password_Reset_Form_Result;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Query;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Query;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

class Login_Shortcode_Adapter_Test extends TestCase {
    private Login_Shortcode_Adapter $adapter;
    private Get_Login_Form_Handler $login_form_handler;
    private Get_Password_Lost_Form_Handler $password_lost_form_handler;
    private Get_Password_Reset_Form_Handler $password_reset_form_handler;
    private Get_Member_Account_Form_Handler $member_account_form_handler;

    protected function setUp(): void {
        parent::setUp();

        $racketmanager = $this->createStub( RacketManager::class );
        $renderer = new class implements View_Renderer_Interface {
            public function render( string $template, object|array $data, array $additional_vars = [] ): void {}
            public function render_to_string( string $template, object|array $data, array $additional_vars = [] ): string {
                return '<div class="rendered">' . $template . '</div>';
            }
            public function template_exists( string $template ): bool {
                return true;
            }
        };

        $view_presenter = new Shortcode_View_Presenter( new \Racketmanager\Services\View\Shortcode_Template_Renderer( $renderer ) );
        $this->login_form_handler         = $this->createStub( Get_Login_Form_Handler::class );
        $this->password_lost_form_handler  = $this->createStub( Get_Password_Lost_Form_Handler::class );
        $this->password_reset_form_handler = $this->createStub( Get_Password_Reset_Form_Handler::class );
        $this->member_account_form_handler = $this->createStub( Get_Member_Account_Form_Handler::class );

        $this->adapter = new Login_Shortcode_Adapter(
            $racketmanager,
            $view_presenter,
            $this->login_form_handler,
            $this->password_lost_form_handler,
            $this->password_reset_form_handler,
            $this->member_account_form_handler
        );
    }

    public function test_login_form_returns_string(): void {
        $this->login_form_handler->method( 'handle' )
            ->willReturn( Login_Form_Result::html( '<form class="login-form" action="/login">Sign in</form>' ) );

        $output = $this->adapter->login_form( [] );
        $this->assertStringContainsString( 'login-form', $output );
        $this->assertStringContainsString( 'Sign in', $output );
    }

    public function test_render_login_form_returns_logged_out_markup(): void {
        $this->login_form_handler->method( 'handle' )
            ->willReturn( Login_Form_Result::html( '<form class="custom-login-form">Email</form>' ) );

        $output = $this->adapter->render_login_form( [] );
        $this->assertStringContainsString( 'custom-login-form', $output );
    }

    public function test_render_password_lost_form_returns_string(): void {
        $this->password_lost_form_handler->method( 'handle' )
            ->willReturn( Password_Lost_Form_Result::html( '<form class="lost-password-form">Reset</form>' ) );

        $output = $this->adapter->render_password_lost_form( [] );
        $this->assertStringContainsString( 'lost-password-form', $output );
    }

    public function test_render_password_reset_form_returns_string(): void {
        $this->password_reset_form_handler->method( 'handle' )
            ->willReturn( Password_Reset_Form_Result::html( '<form class="reset-form">Update</form>' ) );

        $output = $this->adapter->render_password_reset_form( [] );
        $this->assertStringContainsString( 'reset-form', $output );
    }

    public function test_generate_member_account_form_returns_string(): void {
        $this->member_account_form_handler->method( 'handle' )
            ->willReturn( Member_Account_Form_Result::html( '<form class="member-account-form">Account</form>' ) );

        $output = $this->adapter->generate_member_account_form( [] );
        $this->assertStringContainsString( 'member-account-form', $output );
    }

    public function test_render_login_form_with_empty_action_override_passes_get_action_to_handler(): void {
        $_GET['action'] = 'register';

        $login_form_handler = $this->createMock( Get_Login_Form_Handler::class );
        $login_form_handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->callback( static function ( Get_Login_Form_Query $query ): bool {
                return 'register' === $query->action;
            } ) )
            ->willReturn( Login_Form_Result::html( '<div data-tabid="registration"></div>' ) );

        $view_presenter = $this->createStub( Shortcode_View_Presenter::class );
        $view_presenter->method( 'render' )->willReturnCallback( static fn( Shortcode_View_Model $vm ) => $vm->raw_html );

        $adapter = new Login_Shortcode_Adapter(
            null,
            $view_presenter,
            $login_form_handler,
            $this->password_lost_form_handler,
            $this->password_reset_form_handler,
            $this->member_account_form_handler
        );

        $output = $adapter->render_login_form( [], '' );
        $this->assertStringContainsString( 'data-tabid="registration"', $output );

        unset( $_GET['action'] );
    }

    public function test_render_login_form_renders_real_registration_tab_when_action_override_is_empty_string(): void {
        $_GET['action'] = 'register';

        $option_gateway = $this->createStub( \Racketmanager\Application\Ports\Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            static fn( string $name, mixed $default = false ) => match ( $name ) {
                'users_can_register' => true,
                'blogname'           => 'Leigh Tennis',
                'siteurl'            => 'https://example.test',
                default              => $default,
            }
        );

        $real_handler     = new Get_Login_Form_Handler( $option_gateway, new \Racketmanager\Services\Settings_Service( [] ) );
        $php_renderer     = new \Racketmanager\Services\View\Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $view_presenter   = new Shortcode_View_Presenter( new Shortcode_Template_Renderer( $php_renderer ) );
        $security_service = $this->createStub( \Racketmanager\Infrastructure\Security\Security_Service_Interface::class );
        $security_service->method( 'is_user_logged_in' )->willReturn( false );

        $adapter = new Login_Shortcode_Adapter(
            null,
            $view_presenter,
            $real_handler,
            $this->password_lost_form_handler,
            $this->password_reset_form_handler,
            $this->member_account_form_handler,
            null,
            $security_service
        );

        $output = $adapter->render_login_form( [], '' );
        $this->assertStringContainsString( 'data-tabid="registration"', $output );
        $this->assertStringContainsString( 'id="registration-tab"', $output );
        $this->assertStringContainsString( 'id="registration"', $output );

        unset( $_GET['action'] );
    }
}
