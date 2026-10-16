<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Testable_League_Admin_Display extends Admin_Display {
    public function __construct( Admin_Action_Adapter $adapter, Admin_Message_Service $messages ) {
        $this->admin_action_adapter  = $adapter;
        $this->admin_message_service = $messages;
        $this->message               = '';
        $this->error                 = false;
    }

    public function expose_deliver( Action_Result_DTO $result, array $query ): bool {
        return $this->deliver_league_action_result( $result, $query );
    }

    public function expose_flash(): void {
        $this->apply_flash_message();
    }

    public function get_exposed_message(): string {
        return $this->message;
    }

    public function get_exposed_error(): bool|string {
        return $this->error;
    }
}

final class In_Memory_Admin_Flash_Message_Store extends Admin_Flash_Message_Store {
    private ?array $data = null;

    public function set( string $message, bool|string $type = false, array $flash_data = array() ): void {
        $this->data = array(
            'message'      => $message,
            'message_type' => $type,
        );
        if ( ! empty( $flash_data ) ) {
            $this->data['flash_data'] = $flash_data;
        }
    }

    public function pop(): array {
        $copy       = $this->data ?? array();
        $this->data = null;
        return $copy;
    }
}

final class In_Memory_Admin_Redirector implements Admin_Redirector_Interface {
    public string $redirect_url = '';

    public function redirect( string $url, int $status = 302 ): void {
        $this->redirect_url = $url;
    }
}

#[AllowMockObjectsWithoutExpectations]
final class League_Admin_Delivery_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_display( Admin_Flash_Message_Store $store, Admin_Redirector_Interface $redirector ): Testable_League_Admin_Display {
        $messages = new Admin_Message_Service( $store );
        return new Testable_League_Admin_Display(
            new Admin_Action_Adapter( $messages, $redirector ),
            $messages
        );
    }

    /**
     * @return array{0: Testable_League_Admin_Display, 1: In_Memory_Admin_Redirector, 2: Admin_Action_Adapter, 3: Admin_Message_Service}
     */
    private function create_roundtrip_environment(): array {
        $store           = new In_Memory_Admin_Flash_Message_Store();
        $redirector      = new In_Memory_Admin_Redirector();
        $message_service = new Admin_Message_Service( $store );
        $adapter         = new Admin_Action_Adapter( $message_service, $redirector );
        $display         = new Testable_League_Admin_Display( $adapter, $message_service );

        return array( $display, $redirector, $adapter, $message_service );
    }

    public function test_success_redirects_with_flash_and_preserves_event_id(): void {
        $store      = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::once() )->method( 'set' )->with( 'Match dates updated', false );
        $redirector->expects( self::once() )->method( 'redirect' )->with(
            self::callback(
                static function ( string $url ): bool {
                    return str_contains( $url, 'page=racketmanager-leagues' )
                        && str_contains( $url, 'view=setup-event' )
                        && str_contains( $url, 'event_id=7' )
                        && str_contains( $url, 'season=2026' );
                }
            )
        );

        $display = $this->create_display( $store, $redirector );

        $result = new Action_Result_DTO(
            message: 'Match dates updated',
            message_type: Admin_Message_Type::SUCCESS,
        );

        self::assertTrue(
            $display->expose_deliver(
                $result,
                array(
                    'page'     => 'racketmanager-leagues',
                    'view'     => 'setup-event',
                    'event_id' => '7',
                    'season'   => '2026',
                )
            )
        );
    }

    public function test_validation_error_still_redirects_with_error_flash(): void {
        $store      = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::once() )->method( 'set' )->with( 'Match date missing for round 1', true );
        $redirector->expects( self::once() )->method( 'redirect' );

        $display = $this->create_display( $store, $redirector );

        self::assertTrue(
            $display->expose_deliver(
                new Action_Result_DTO(
                    message: 'Match date missing for round 1',
                    message_type: Admin_Message_Type::ERROR,
                ),
                array( 'page' => 'racketmanager-leagues', 'view' => 'setup', 'competition_id' => '3' )
            )
        );
    }

    public function test_get_request_does_not_redirect_and_flash_is_applied(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $store                     = $this->createMock( Admin_Flash_Message_Store::class );
        $store->expects( self::once() )->method( 'pop' )->willReturn(
            array(
                'message'      => 'Season(s) deleted',
                'message_type' => false,
            )
        );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::never() )->method( 'redirect' );

        $display = $this->create_display( $store, $redirector );

        self::assertFalse(
            $display->expose_deliver(
                new Action_Result_DTO( message: 'Season(s) deleted', message_type: Admin_Message_Type::SUCCESS ),
                array( 'page' => 'racketmanager-leagues', 'view' => 'seasons', 'competition_id' => '3' )
            )
        );

        $display->expose_flash();
        self::assertSame( 'Season(s) deleted', $display->message );
        self::assertFalse( $display->error );
    }

    public function test_season_delete_dispatcher_success_feeds_prg_redirect(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'delete_seasons' )->with( 3, array( 2026 ) );

        $dispatcher = new Season_Delete_Action_Dispatcher(
            $competition_service,
            $this->createStub( Action_Guard_Interface::class )
        );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '3' ),
            array( 'doActionSeason' => '1', 'competition_id' => '3', 'action' => 'delete', 'del_season' => array( 2026 ) )
        );

        $store      = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::once() )->method( 'set' )->with( 'Season(s) deleted', false );
        $redirector->expects( self::once() )->method( 'redirect' );

        $display = $this->create_display( $store, $redirector );

        self::assertTrue(
            $display->expose_deliver(
                $result,
                array( 'page' => 'racketmanager-leagues', 'view' => 'seasons', 'competition_id' => '3' )
            )
        );
    }

    public function test_league_setup_nonce_failure_feeds_error_flash_redirect(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_competition' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->method( 'assert_allowed' )->willThrowException( new \Racketmanager\Exceptions\Invalid_Status_Exception( 'Security token invalid' ) );

        $result = ( new League_Setup_Action_Dispatcher( $competition_service, $guard ) )->handle(
            array( 'competition_id' => '3' ),
            array( 'action' => 'update', 'season' => '2026', 'rounds' => array( array( 'round' => '1', 'match_date' => '2026-04-01' ) ) )
        );

        $store      = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::once() )->method( 'set' )->with( 'Security token invalid', true );
        $redirector->expects( self::once() )->method( 'redirect' );

        $display = $this->create_display( $store, $redirector );

        self::assertTrue(
            $display->expose_deliver( $result, array( 'page' => 'racketmanager-leagues', 'view' => 'setup', 'competition_id' => '3' ) )
        );
    }

    public function test_event_setup_dispatcher_is_wired_for_delivery(): void {
        self::assertTrue( class_exists( Event_Setup_Action_Dispatcher::class ) );
    }

    public function test_league_match_delete_redirects_with_tab_override_matches(): void {
        $store      = $this->createMock( Admin_Flash_Message_Store::class );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $store->expects( self::once() )->method( 'set' )->with( 'Match id 101 deleted', false );
        $redirector->expects( self::once() )->method( 'redirect' )->with(
            self::callback(
                static function ( string $url ): bool {
                    return str_contains( $url, 'page=racketmanager-leagues' )
                        && str_contains( $url, 'view=league' )
                        && str_contains( $url, 'league_id=5' )
                        && str_contains( $url, 'tab=matches' );
                }
            )
        );

        $display = $this->create_display( $store, $redirector );

        $result = new Action_Result_DTO(
            message: 'Match id 101 deleted',
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'matches',
        );

        self::assertTrue(
            $display->expose_deliver(
                $result,
                array(
                    'page'      => 'racketmanager-leagues',
                    'view'      => 'league',
                    'league_id' => '5',
                    'season'    => '2026',
                )
            )
        );
    }

    public function test_post_delivery_and_get_flash_roundtrip(): void {
        list( $display, $redirector, $adapter, $message_service ) = $this->create_roundtrip_environment();

        // 1. POST delivery
        $result = new Action_Result_DTO(
            message: 'Standings Table updated',
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'standings',
        );

        $redirected = $display->expose_deliver(
            $result,
            array(
                'page'      => 'racketmanager-leagues',
                'view'      => 'league',
                'league_id' => '10',
                'season'    => '2026',
                'group'     => 'Premier',
            )
        );

        self::assertTrue( $redirected );
        self::assertStringContainsString( 'league_id=10', $redirector->redirect_url );
        self::assertStringContainsString( 'group=Premier', $redirector->redirect_url );
        self::assertStringContainsString( 'tab=standings', $redirector->redirect_url );

        // 2. Next GET request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $get_display = new Testable_League_Admin_Display( $adapter, $message_service );
        $get_display->expose_flash();

        self::assertSame( 'Standings Table updated', $get_display->get_exposed_message() );
        self::assertFalse( $get_display->get_exposed_error() );
    }

    public function test_constitution_delivery_and_get_flash_roundtrip(): void {
        list( $display, $redirector, $adapter, $message_service ) = $this->create_roundtrip_environment();

        $result = new Action_Result_DTO(
            message: 'Matches generated',
            message_type: Admin_Message_Type::SUCCESS,
        );

        $redirected = $display->expose_deliver(
            $result,
            array(
                'page'     => 'racketmanager-leagues',
                'view'     => 'constitution',
                'event_id' => '12',
                'season'   => '2026',
            )
        );

        self::assertTrue( $redirected );
        self::assertStringContainsString( 'view=constitution', $redirector->redirect_url );
        self::assertStringContainsString( 'event_id=12', $redirector->redirect_url );

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $get_display = new Testable_League_Admin_Display( $adapter, $message_service );
        $get_display->expose_flash();

        self::assertSame( 'Matches generated', $get_display->get_exposed_message() );
        self::assertFalse( $get_display->get_exposed_error() );
    }

    public function test_event_delivery_and_get_flash_roundtrip(): void {
        list( $display, $redirector, $adapter, $message_service ) = $this->create_roundtrip_environment();

        $result = new Action_Result_DTO(
            message: 'League added',
            message_type: Admin_Message_Type::SUCCESS,
        );

        $redirected = $display->expose_deliver(
            $result,
            array(
                'page'     => 'racketmanager-leagues',
                'view'     => 'event',
                'event_id' => '5',
                'season'   => '2026',
            )
        );

        self::assertTrue( $redirected );
        self::assertStringContainsString( 'view=event', $redirector->redirect_url );
        self::assertStringContainsString( 'event_id=5', $redirector->redirect_url );

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $get_display = new Testable_League_Admin_Display( $adapter, $message_service );
        $get_display->expose_flash();

        self::assertSame( 'League added', $get_display->get_exposed_message() );
        self::assertFalse( $get_display->get_exposed_error() );
    }

    public function test_schedule_delivery_and_get_flash_roundtrip(): void {
        list( $display, $redirector, $adapter, $message_service ) = $this->create_roundtrip_environment();

        $result = new Action_Result_DTO(
            message: 'Matches scheduled',
            message_type: Admin_Message_Type::SUCCESS,
        );

        $redirected = $display->expose_deliver(
            $result,
            array(
                'page'           => 'racketmanager-leagues',
                'view'           => 'plan',
                'competition_id' => '3',
                'season'         => '2026',
            )
        );

        self::assertTrue( $redirected );
        self::assertStringContainsString( 'view=plan', $redirector->redirect_url );
        self::assertStringContainsString( 'competition_id=3', $redirector->redirect_url );

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $get_display = new Testable_League_Admin_Display( $adapter, $message_service );
        $get_display->expose_flash();

        self::assertSame( 'Matches scheduled', $get_display->get_exposed_message() );
        self::assertFalse( $get_display->get_exposed_error() );
    }
}
