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

    public function expose_flash(): array {
        return $this->apply_flash_message();
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

    public function test_flash_message_is_applied_on_get(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $store                     = $this->createMock( Admin_Flash_Message_Store::class );
        $store->expects( self::once() )->method( 'pop' )->willReturn(
            array(
                'message'      => 'Season(s) deleted',
                'message_type' => false,
            )
        );

        $message_service = new Admin_Message_Service( $store );
        $display         = new Testable_League_Admin_Display(
            new Admin_Action_Adapter( $message_service, $this->createStub( Admin_Redirector_Interface::class ) ),
            $message_service
        );

        $display->expose_flash();
        self::assertSame( 'Season(s) deleted', $display->get_exposed_message() );
        self::assertFalse( $display->get_exposed_error() );
    }

    public function test_flash_data_is_applied_and_pops_once(): void {
        $store           = new In_Memory_Admin_Flash_Message_Store();
        $redirector      = new In_Memory_Admin_Redirector();
        $message_service = new Admin_Message_Service( $store );
        $adapter         = new Admin_Action_Adapter( $message_service, $redirector );

        $report = array(
            'retry'             => 12,
            'scheduled'         => 0,
            'conflicts'         => array( array( 'week' => 1, 'division' => 'Div 1', 'court' => '5_Mon 18:00_C0', 'home' => 'A', 'with' => 'B' ) ),
            'alerts'            => array(),
            'resource_pairings' => array( '5_Mon 18:00_C0' => array( array( 'id' => 1, 'name' => 'A', 'division' => 'Div 1' ) ) ),
        );

        self::assertTrue(
            $adapter->deliver_redirect(
                array(
                    'redirect'     => 'https://example.test/wp-admin/admin.php?page=racketmanager-leagues&view=plan',
                    'message'      => 'Scheduling failed after 12 attempts',
                    'message_type' => true,
                    'flash_data'   => $report,
                )
            )
        );

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $display                   = new Testable_League_Admin_Display( $adapter, $message_service );
        $flash                     = $display->expose_flash();

        self::assertSame( 'Scheduling failed after 12 attempts', $display->get_exposed_message() );
        self::assertSame( 'error', $display->get_exposed_error() );
        self::assertSame( $report, $flash['flash_data'] ?? null );

        $second_display = new Testable_League_Admin_Display( $adapter, $message_service );
        self::assertSame( array(), $second_display->expose_flash() );
    }

    public function test_season_delete_dispatcher_success_returns_dto(): void {
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

        self::assertInstanceOf( Action_Result_DTO::class, $result );
        self::assertSame( 'Season(s) deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_league_setup_nonce_failure_returns_error_dto(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_competition' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->method( 'assert_allowed' )->willThrowException( new \Racketmanager\Exceptions\Invalid_Status_Exception( 'Security token invalid' ) );

        $result = ( new League_Setup_Action_Dispatcher( $competition_service, $guard, $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array( 'action' => 'update', 'season' => '2026', 'rounds' => array( array( 'round' => '1', 'match_date' => '2026-04-01' ) ) )
        );

        self::assertInstanceOf( Action_Result_DTO::class, $result );
        self::assertSame( 'Security token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_event_setup_dispatcher_is_wired_for_delivery(): void {
        self::assertTrue( class_exists( Event_Setup_Action_Dispatcher::class ) );
    }
}
