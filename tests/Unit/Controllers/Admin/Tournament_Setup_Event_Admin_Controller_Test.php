<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Setup_Event_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Setup_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;
use ReflectionClass;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Tournament_Setup_Event_Admin_Controller_Test extends TestCase {

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    public function test_get_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament = $this->create_tournament_instance( 10, 'Winter Championship', '2026' );

        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_season_by_name' )->willReturn( [ 'match_dates' => [ '2026-06-01' ] ] );

        $event = $this->createStub( Event::class );
        $event->competition = $competition;
        $event->method( 'get_season_by_name' )->willReturn( [ 'match_dates' => [ '2026-06-01' ] ] );

        $league = $this->createStub( League::class );
        $league->event = $event;
        $league->method( 'get_matches' )->willReturn( 5 );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service
            ->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( 10 )
            ->willReturn( $tournament );

        $league_service = $this->createMock( League_Service::class );
        $league_service
            ->expects( self::once() )
            ->method( 'get_league' )
            ->with( 20 )
            ->willReturn( $league );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_matches' );

        $dispatcher = $this->createStub( Draw_Action_Dispatcher::class );

        $controller = new Tournament_Setup_Event_Admin_Controller(
            $tournament_service,
            $league_service,
            $dispatcher,
            $guard
        );

        $result = $controller->setup_event_page(
            [
                'page'       => 'racketmanager-tournaments',
                'view'       => 'setup-event',
                'tournament' => '10',
                'league'     => '20',
            ],
            []
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertArrayNotHasKey( 'redirect', $result );
        self::assertInstanceOf( Tournament_Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertSame( 5, $result['view_model']->match_count );
    }

    public function test_post_returns_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createStub( Tournament_Service::class );
        $league_service = $this->createStub( League_Service::class );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_matches' );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add_championship-fixtures', 'edit_matches' );

        $dispatcher = $this->createMock( Draw_Action_Dispatcher::class );
        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( self::callback( function( Draw_Action_Request_DTO $dto ) {
                return $dto->tournament_id === 10
                    && $dto->league_id === 20
                    && $dto->season === '2026'
                    && isset( $dto->post['action'] )
                    && $dto->post['action'] === 'save';
            } ) )
            ->willReturn( new Draw_Action_Response_DTO( 'Fixtures saved successfully', Admin_Message_Type::SUCCESS, 'fixtures' ) );

        $controller = new Tournament_Setup_Event_Admin_Controller(
            $tournament_service,
            $league_service,
            $dispatcher,
            $guard
        );

        $result = $controller->setup_event_page(
            [
                'page'       => 'racketmanager-tournaments',
                'view'       => 'setup-event',
                'tournament' => '10',
                'league'     => '20',
            ],
            ['season' => '2026', 'action' => 'save']
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=setup-event', $result['redirect'] );
        self::assertStringContainsString( 'tournament=10', $result['redirect'] );
        self::assertStringContainsString( 'league=20', $result['redirect'] );
        self::assertSame( 'Fixtures saved successfully', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    private function create_tournament_instance( int $id, string $name, string $season ): Tournament {
        $reflection = new ReflectionClass( Tournament::class );
        $tournament = $reflection->newInstanceWithoutConstructor();

        $id_prop = $reflection->getProperty( 'id' );
        $id_prop->setAccessible( true );
        $id_prop->setValue( $tournament, $id );

        $name_prop = $reflection->getProperty( 'name' );
        $name_prop->setAccessible( true );
        $name_prop->setValue( $tournament, $name );

        $season_prop = $reflection->getProperty( 'season' );
        $season_prop->setAccessible( true );
        $season_prop->setValue( $tournament, $season );

        return $tournament;
    }
}
