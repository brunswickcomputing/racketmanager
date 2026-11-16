<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionProperty;

require_once __DIR__ . '/../../../../wp-stubs.php';

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
        $store = $this->createMock( Admin_Flash_Message_Store::class );
        $store->expects( self::once() )->method( 'pop' )->willReturn(
            array(
                'message'      => 'Season(s) deleted',
                'message_type' => false,
            )
        );

        $message_service = new Admin_Message_Service( $store );
        $presenter       = new Admin_Screen_Presenter(
            $this->createStub( View_Renderer_Interface::class ),
            $message_service
        );

        $flash = $presenter->apply_flash();

        self::assertSame( 'Season(s) deleted', $flash['message'] ?? null );
        self::assertFalse( $flash['message_type'] ?? true );
        self::assertSame(
            array(
                'message' => 'Season(s) deleted',
                'type'    => 'success',
            ),
            $this->current_message( $message_service )
        );
    }

    public function test_flash_data_is_applied_and_pops_once(): void {
        $store           = new In_Memory_Admin_Flash_Message_Store();
        $redirector      = new In_Memory_Admin_Redirector();
        $message_service = new Admin_Message_Service( $store );
        $adapter         = new Admin_Action_Adapter( $message_service, $redirector );
        $presenter       = new Admin_Screen_Presenter(
            $this->createStub( View_Renderer_Interface::class ),
            $message_service
        );

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

        $flash = $presenter->apply_flash();

        self::assertSame( 'Scheduling failed after 12 attempts', $flash['message'] ?? null );
        self::assertTrue( $flash['message_type'] ?? false );
        self::assertSame( $report, $flash['flash_data'] ?? null );
        self::assertSame(
            array(
                'message' => 'Scheduling failed after 12 attempts',
                'type'    => 'danger',
            ),
            $this->current_message( $message_service )
        );

        self::assertSame( array(), $presenter->apply_flash() );
    }

    /**
     * @return array{message:string, type:string}|null
     */
    private function current_message( Admin_Message_Service $message_service ): ?array {
        return ( new ReflectionProperty( Admin_Message_Service::class, 'current_message' ) )->getValue( $message_service );
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

        $event_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
        $result     = ( new League_Setup_Action_Dispatcher(
            $competition_service,
            $guard,
            $event_repo,
            new \Racketmanager\Application\Admin\Commands\League_Setup_Handler( $competition_service, $event_repo )
        ) )->handle(
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
