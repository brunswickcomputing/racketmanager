<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Login\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Login\Services\Auth_Service;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Exceptions\Template_Renderer_Missing_Exception;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use WP_Error;
use WP_User;

#[AllowMockObjectsWithoutExpectations]
final class Auth_Service_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Option_Service $option_service;
    private Template_Renderer_Interface $renderer;
    private Auth_Service $service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->option_service = $this->createMock( Option_Service::class );
        $this->renderer       = $this->createMock( Template_Renderer_Interface::class );
        $this->service        = new Auth_Service( $this->option_service, $this->renderer );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_register_user_returns_error_when_email_invalid(): void {
        $result = $this->service->register_user( 'invalid-email', 'John', 'Doe' );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'email', $result->get_error_code() );
    }

    public function test_register_user_returns_error_when_email_or_user_exists(): void {
        $GLOBALS['wp_stubs_email_exists_return']['taken@example.com'] = 12;

        $result = $this->service->register_user( 'taken@example.com', 'John', 'Doe' );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'email_exists', $result->get_error_code() );
    }

    public function test_register_user_creates_user_and_sends_notification(): void {
        $GLOBALS['wp_stubs_generate_password_return'] = 'custom_secret_pass';
        $GLOBALS['wp_stubs_insert_user_return']       = 99;

        $result = $this->service->register_user( 'newuser@example.com', 'Jane', 'Smith' );

        $this->assertSame( 99, $result );
        $this->assertNotEmpty( $GLOBALS['wp_stubs_last_inserted_user'] );
        $this->assertSame( 'newuser@example.com', $GLOBALS['wp_stubs_last_inserted_user']['user_login'] );
        $this->assertSame( 'Jane', $GLOBALS['wp_stubs_last_inserted_user']['first_name'] );
        $this->assertSame( 'Smith', $GLOBALS['wp_stubs_last_inserted_user']['last_name'] );
        $this->assertSame( 'custom_secret_pass', $GLOBALS['wp_stubs_last_inserted_user']['user_pass'] );

        $this->assertCount( 1, $GLOBALS['wp_stubs_new_user_notifications'] ?? [] );
        $this->assertSame( 99, $GLOBALS['wp_stubs_new_user_notifications'][0]['user_id'] );
        $this->assertSame( 'both', $GLOBALS['wp_stubs_new_user_notifications'][0]['notify'] );
    }

    public function test_register_user_returns_wp_error_on_insert_failure(): void {
        $GLOBALS['wp_stubs_insert_user_error'] = new WP_Error( 'db_insert_error', 'Failed to insert' );

        $result = $this->service->register_user( 'error@example.com', 'Fail', 'User' );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'db_insert_error', $result->get_error_code() );
    }

    public function test_get_password_hint_returns_formatted_string(): void {
        $hint = $this->service->get_password_hint();

        $this->assertStringContainsString( 'Please use a strong password', $hint );
    }

    public function test_get_email_content_type_returns_html(): void {
        $this->assertSame( 'text/html', $this->service->get_email_content_type() );
    }

    public function test_format_new_user_notification_email_admin(): void {
        $user             = new stdClass();
        $user->user_login = 'tester';
        $GLOBALS['wp_stubs_count_users_return'] = [
            'total_users' => 45,
            'avail_roles' => [],
        ];

        $email = $this->service->format_new_user_notification_email_admin( [], $user, 'Test Club' );

        $this->assertSame( '[Test Club] New user tester registered.', $email['subject'] );
        $this->assertStringContainsString( 'You now have 45 users', $email['message'] );
    }

    public function test_format_new_user_notification_email_subscriber(): void {
        $user               = new WP_User( 10 );
        $user->user_login   = 'member1';
        $user->display_name = 'Member One';

        $this->option_service->method( 'get_site_name' )->willReturn( 'Tennis Org' );
        $this->option_service->method( 'get_site_url' )->willReturn( 'https://tennis.local' );
        $this->option_service->method( 'get_admin_email' )->willReturn( 'admin@tennis.local' );

        $GLOBALS['wp_stubs_password_reset_key_return'] = 'reset-key-xyz';

        $this->renderer->expects( $this->once() )
            ->method( 'render' )
            ->with(
                'email-welcome',
                $this->callback(
                    function ( array $vars ): bool {
                        return 'Tennis Org' === $vars['site_name']
                        && 'member1' === $vars['user_login']
                        && 'Member One' === $vars['display_name']
                        && str_contains( $vars['action_url'], 'key=reset-key-xyz' )
                        && 'admin@tennis.local' === $vars['email_link'];
                    }
                ),
                'email'
            )
            ->willReturn( '<p>Welcome!</p>' );

        $email = $this->service->format_new_user_notification_email( [], $user );

        $this->assertSame( '<p>Welcome!</p>', $email['message'] );
        $this->assertSame( 'Content-Type: text/html; charset=UTF-8', $email['headers'] );
    }

    public function test_format_new_user_notification_throws_when_renderer_is_missing(): void {
        $service = new Auth_Service( $this->option_service, null );
        $user    = new WP_User( 1 );

        $this->expectException( Template_Renderer_Missing_Exception::class );
        $this->expectExceptionMessageIsOrContains( 'Template renderer is required' );

        $service->format_new_user_notification_email( [], $user );
    }

    public function test_format_retrieve_password_email(): void {
        $user_data               = new stdClass();
        $user_data->display_name = 'Test Player';

        $this->option_service->method( 'get_site_name' )->willReturn( 'Club' );
        $this->option_service->method( 'get_site_url' )->willReturn( 'https://club.local' );

        $this->renderer->expects( $this->once() )
            ->method( 'render' )
            ->with(
                'email-password-reset',
                $this->callback(
                    function ( array $vars ): bool {
                        return 'player1' === $vars['user_login']
                        && 'Test Player' === $vars['display_name']
                        && str_contains( $vars['action_url'], 'key=key123' );
                    }
                ),
                'email'
            )
            ->willReturn( '<p>Reset link</p>' );

        $html = $this->service->format_retrieve_password_email( 'key123', 'player1', $user_data );

        $this->assertSame( '<p>Reset link</p>', $html );
    }

    public function test_format_password_change_email(): void {
        $user_data = [
            'user_login'   => 'user9',
            'display_name' => 'User Nine',
        ];

        $this->option_service->method( 'get_site_name' )->willReturn( 'Club' );
        $this->option_service->method( 'get_site_url' )->willReturn( 'https://club.local' );
        $this->option_service->method( 'get_admin_email' )->willReturn( 'admin@club.local' );

        $this->renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'email-password-change', $this->anything(), 'email' )
            ->willReturn( '<p>Password changed</p>' );

        $result = $this->service->format_password_change_email( [], $user_data );

        $this->assertSame( '<p>Password changed</p>', $result['message'] );
    }

    public function test_format_privacy_personal_data_email(): void {
        $this->option_service->method( 'get_site_name' )->willReturn( 'Club' );
        $this->option_service->method( 'get_site_url' )->willReturn( 'https://club.local' );

        $this->renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'email-privacy-personal-data', $this->anything(), 'email' )
            ->willReturn( '<p>Privacy data</p>' );

        $html = $this->service->format_privacy_personal_data_email();

        $this->assertSame( '<p>Privacy data</p>', $html );
    }

    public function test_format_user_request_action_email(): void {
        $this->option_service->method( 'get_site_name' )->willReturn( 'Club' );
        $this->option_service->method( 'get_site_url' )->willReturn( 'https://club.local' );

        $this->renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'email-user-request-action', $this->anything(), 'email' )
            ->willReturn( '<p>User action</p>' );

        $html = $this->service->format_user_request_action_email();

        $this->assertSame( '<p>User action</p>', $html );
    }

    public function test_evaluate_redirect_after_login_without_user_id(): void {
        $error  = new WP_Error( 'invalid_user', 'Error' );
        $target = $this->service->evaluate_redirect_after_login( 'https://club.local/target', $error );

        $this->assertSame( 'https://example.test', $target );
    }

    public function test_evaluate_redirect_after_login_for_administrator(): void {
        $user     = new WP_User( 1 );
        $user->ID = 1;
        $GLOBALS['wp_stubs_user_can_return'] = true;

        $target = $this->service->evaluate_redirect_after_login( 'https://club.local/dash', $user );
        $this->assertSame( 'https://club.local/dash', $target );

        $target_empty = $this->service->evaluate_redirect_after_login( '', $user );
        $this->assertSame( 'https://example.test/wp-admin/', $target_empty );
    }

    public function test_evaluate_redirect_after_login_for_subscriber(): void {
        $user     = new WP_User( 2 );
        $user->ID = 2;
        $GLOBALS['wp_stubs_user_can_return'] = false;

        $target = $this->service->evaluate_redirect_after_login( 'https://club.local/page', $user );
        $this->assertSame( 'https://club.local/page', $target );

        $target_empty = $this->service->evaluate_redirect_after_login( '', $user );
        $this->assertSame( 'https://example.test', $target_empty );
    }

    public function test_evaluate_redirect_after_login_clears_password_reset_url(): void {
        $user     = new WP_User( 2 );
        $user->ID = 2;
        $GLOBALS['wp_stubs_user_can_return'] = false;

        $target = $this->service->evaluate_redirect_after_login( 'https://club.local/member-password-reset', $user );
        $this->assertSame( 'https://example.test', $target );
    }

    public function test_get_logged_in_redirect_url(): void {
        $user = new WP_User( 1 );
        $GLOBALS['wp_stubs_user_can_return'] = true;

        $this->assertSame( 'https://club.local/custom', $this->service->get_logged_in_redirect_url( 'https://club.local/custom', $user ) );
        $this->assertSame( 'https://example.test/wp-admin/', $this->service->get_logged_in_redirect_url( null, $user ) );

        $GLOBALS['wp_stubs_user_can_return'] = false;
        $this->assertSame( 'https://example.test', $this->service->get_logged_in_redirect_url( 'https://club.local/custom', $user ) );
    }
}
