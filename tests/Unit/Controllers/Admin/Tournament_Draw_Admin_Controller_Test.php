<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Draw_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Domain\Event;
use Racketmanager\Domain\League;
use Racketmanager\Domain\Tournament;
use Racketmanager\Services\Championship;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Tournament_Draw_Admin_Controller_Test extends TestCase {

    /**
     * @throws ReflectionException
     */
    public function test_draw_page_returns_view_model_on_get(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament        = $this->create_tournament_instance( 1, 'Test Tournament', 2024 );
        $league            = $this->createMock( League::class );
        $league->id        = 10;
        $league->event     = $this->createMock( Event::class );
        $league->event->id = 100;
        $league->method( 'get_league_teams' )->willReturn( array() );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->expects( self::once() )
                           ->method( 'get_tournament' )
                           ->with( 1 )
                           ->willReturn( $tournament );

        $tournament_service->expects( self::once() )
                           ->method( 'get_draw_view_model' )
                           ->willReturn( new Tournament_Draw_Page_View_Model(
                               tournament: $tournament,
                               league: $league,
                               tab: 'fixtures',
                               season: '2024',
                               matches: array(),
                               teams: array(),
                               finals: array(),
                           ) );

        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
                       ->method( 'get_league' )
                       ->with( 10 )
                       ->willReturn( $league );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
              ->method( 'assert_capability' )
              ->with( 'edit_matches' );

        $dispatcher = new Draw_Action_Dispatcher(
            $this->createMock( Draw_Action_Handler_Interface::class ),
            $guard
        );

        $controller = new Tournament_Draw_Admin_Controller(
            $tournament_service,
            $league_service,
            $dispatcher,
            $guard
        );

        $query = array(
            'tournament' => '1',
            'league'     => '10',
            'tab'        => 'fixtures',
        );
        $post  = array();

        $result = $controller->draw_page( $query, $post );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Draw_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'fixtures', $result['view_model']->tab );
        self::assertSame( '10', (string) $result['view_model']->league->id );
        self::assertSame( '2024', (string) $result['view_model']->season );
        self::assertIsArray( $result['view_model']->matches );
        self::assertIsArray( $result['view_model']->teams );
        self::assertIsArray( $result['view_model']->finals );
        self::assertSame( 'fixtures', $result['redirect_tab'] );
        self::assertArrayNotHasKey( 'redirect', $result );
    }

    /**
     * @throws ReflectionException
     */
    private function create_tournament_instance( int $id, string $name, int $season ): Tournament {
        $reflection = new ReflectionClass( Tournament::class );
        $tournament = $reflection->newInstanceWithoutConstructor();

        $this->set_property( $tournament, 'id', $id );
        $this->set_property( $tournament, 'name', $name );

        foreach ( array( 'season', 'current_season' ) as $prop ) {
            if ( $this->has_property( $tournament, $prop ) ) {
                $this->set_property( $tournament, $prop, $season );
                break;
            }
        }

        return $tournament;
    }

    private function set_property( object $object, string $property_name, mixed $value ): void {
        $reflection = new ReflectionObject( $object );
        while ( false !== $reflection ) {
            if ( $reflection->hasProperty( $property_name ) ) {
                $property = $reflection->getProperty( $property_name );
                $property->setValue( $object, $value );

                return;
            }
            $reflection = $reflection->getParentClass();
        }
        self::fail( sprintf( 'Property "%s" not found on %s', $property_name, $object::class ) );
    }

    private function has_property( object $object, string $property_name ): bool {
        $reflection = new ReflectionObject( $object );
        while ( false !== $reflection ) {
            if ( $reflection->hasProperty( $property_name ) ) {
                return true;
            }
            $reflection = $reflection->getParentClass();
        }

        return false;
    }

    /**
     * @throws ReflectionException
     */
    public function test_draw_page_returns_redirect_on_post(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament        = $this->create_tournament_instance( 1, 'Test Tournament', 2024 );
        $league            = $this->createMock( League::class );
        $league->id        = 10;
        $league->event     = $this->createMock( Event::class );
        $league->event->id = 100;
        $league->method( 'get_league_teams' )->willReturn( array() );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service->method( 'get_draw_view_model' )
                           ->willReturn( new Tournament_Draw_Page_View_Model(
                               tournament: $tournament,
                               league: $league,
                               tab: 'finalResults',
                               season: '2025'
                           ) );

        $league_service = $this->createMock( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
              ->method( 'assert_capability' )
              ->with( 'edit_matches' );

        $handler = $this->createMock( Draw_Action_Handler_Interface::class );
        $handler->method( 'update_final_results' )
                ->willReturn( new Action_Result_DTO( tab_override: 'finalResults' ) );

        $dispatcher = new Draw_Action_Dispatcher( $handler, $guard );

        $controller = new Tournament_Draw_Admin_Controller(
            $tournament_service,
            $league_service,
            $dispatcher,
            $guard
        );

        $query = array(
            'page'       => 'racketmanager-tournaments',
            'tournament' => '1',
            'league'     => '10',
        );
        $post  = array(
            'action' => 'updateFinalResults',
            'season' => '2025',
        );

        $result = $controller->draw_page( $query, $post );

        self::assertArrayHasKey( 'redirect', $result );
        // The Admin_Redirect_Url_Builder::tournament_draw_view will be called.
        // Based on its implementation, it uses add_query_arg and admin_url.
        // Our wp-stubs.php should handle these.
        self::assertStringContainsString( 'page=racketmanager-tournaments', $result['redirect'] );
        self::assertStringContainsString( 'view=draw', $result['redirect'] );
        self::assertStringContainsString( 'tournament=1', $result['redirect'] );
        self::assertStringContainsString( 'league=10', $result['redirect'] );
        self::assertStringContainsString( 'tab=finalResults', $result['redirect'] );
        self::assertStringContainsString( 'season=2025', $result['redirect'] );
    }

    /**
     * @throws ReflectionException
     */
    public function test_draw_page_populates_matches_from_tournament_service(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament = $this->create_tournament_instance( 1, 'Test Tournament', 2024 );
        $league     = $this->createMock( League::class );

        $league->id        = 10;
        $league->event     = $this->createMock( Event::class );
        $league->event->id = 100;
        $league->method( 'get_league_teams' )->willReturn( array() );

        // Mock championship
        $reflection                  = new ReflectionClass( Championship::class );
        $championship                = $reflection->newInstanceWithoutConstructor();
        $championship->finals        = array( array( 'key' => 'final1', 'name' => 'Final Round' ) );
        $championship->current_final = 'final1';
        $league->championship        = $championship;

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $league_service = $this->createMock( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $mock_matches = array( 'final1' => array( (object) array( 'id' => 101 ) ) );

        $tournament_service->expects( self::once() )
                           ->method( 'get_draw_view_model' )
                           ->willReturn( new Tournament_Draw_Page_View_Model(
                               tournament: $tournament,
                               league: $league,
                               tab: 'finalResults',
                               season: '2024',
                               matches: $mock_matches
                           ) );

        $guard      = $this->createMock( Action_Guard_Interface::class );
        $dispatcher = new Draw_Action_Dispatcher(
            $this->createMock( Draw_Action_Handler_Interface::class ),
            $guard
        );

        $controller = new Tournament_Draw_Admin_Controller(
            $tournament_service,
            $league_service,
            $dispatcher,
            $guard
        );

        $query = array(
            'tournament' => '1',
            'league'     => '10',
            'tab'        => 'finalResults',
        );

        $result = $controller->draw_page( $query, array() );

        self::assertSame( $mock_matches['final1'], $result['view_model']->matches['final1'] );
    }

    public function test_draw_page_throws_exception_on_invalid_tournament(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )
                           ->with( 999 )
                           ->willThrowException( new Tournament_Not_Found_Exception( 'Tournament not found' ) );

        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $dispatcher     = $this->createMock( Draw_Action_Dispatcher::class );

        $controller = new Tournament_Draw_Admin_Controller(
            $tournament_service,
            $league_service,
            $dispatcher,
            $guard
        );

        $this->expectException( Tournament_Not_Found_Exception::class );
        $controller->draw_page( array( 'tournament' => 999 ), array() );
    }

    /**
     * @throws ReflectionException
     */
    public function test_draw_page_throws_exception_on_invalid_league(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $this->create_tournament_instance( 1, 'T', 2024 ) );

        $league_service = $this->createMock( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( null );

        $guard      = $this->createMock( Action_Guard_Interface::class );
        $dispatcher = $this->createMock( Draw_Action_Dispatcher::class );

        $controller = new Tournament_Draw_Admin_Controller(
            $tournament_service,
            $league_service,
            $dispatcher,
            $guard
        );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'League not found' );

        $controller->draw_page( array( 'tournament' => 1, 'league' => 999 ), array() );
    }

    /**
     * @throws ReflectionException
     */
    public function test_draw_page_handles_null_event_details(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament        = $this->create_tournament_instance( 1, 'T', 2024 );
        $league            = $this->createMock( League::class );
        $league->id        = 10;
        $league->event     = $this->createMock( Event::class );
        $league->event->id = 100;
        $league->method( 'get_league_teams' )->willReturn( array() );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service->method( 'get_draw_view_model' )
                           ->willReturn( new Tournament_Draw_Page_View_Model(
                               tournament: $tournament,
                               league: $league,
                               tab: 'draw',
                               season: '2024'
                           ) );

        $league_service = $this->createMock( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $guard      = $this->createMock( Action_Guard_Interface::class );
        $dispatcher = new Draw_Action_Dispatcher(
            $this->createMock( Draw_Action_Handler_Interface::class ),
            $guard
        );

        $controller = new Tournament_Draw_Admin_Controller( $tournament_service, $league_service, $dispatcher, $guard );

        $result = $controller->draw_page( array( 'tournament' => 1, 'league' => 10 ), array() );

        self::assertEmpty( $result['view_model']->matches );
        self::assertEmpty( $result['view_model']->finals );
    }

    /**
     * @throws ReflectionException
     */
    public function test_extract_tab_priority(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament        = $this->create_tournament_instance( 1, 'T', 2024 );
        $league            = $this->createMock( League::class );
        $league->id        = 10;
        $league->event     = $this->createMock( Event::class );
        $league->event->id = 100;
        $league->method( 'get_league_teams' )->willReturn( array() );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $league_service = $this->createMock( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $guard = $this->createMock( Action_Guard_Interface::class );

        // 1. Test override from dispatcher
        $dispatcher = $this->createMock( Draw_Action_Dispatcher::class );
        $dispatcher->method( 'handle' )->willReturn( new Draw_Action_Response_DTO( tab_override: 'overrideTab' ) );

        $tournament_service->method( 'get_draw_view_model' )->willReturn( new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'overrideTab',
            season: '2024'
        ) );

        $controller = new Tournament_Draw_Admin_Controller( $tournament_service, $league_service, $dispatcher, $guard );

        $result = $controller->draw_page( array( 'tournament' => 1, 'league' => 10, 'tab' => 'queryTab' ), array() );
        self::assertSame( 'overrideTab', $result['view_model']->tab );

        // 2. Test query string (GET)
        $dispatcher2 = $this->createMock( Draw_Action_Dispatcher::class );
        $dispatcher2->method( 'handle' )->willReturn( new Draw_Action_Response_DTO() ); // No override

        $tournament_service2 = $this->createMock( Tournament_Service::class );
        $tournament_service2->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service2->method( 'get_draw_view_model' )->willReturn( new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'queryTab',
            season: '2024'
        ) );

        $controller2 = new Tournament_Draw_Admin_Controller( $tournament_service2, $league_service, $dispatcher2, $guard );

        $result2 = $controller2->draw_page( array( 'tournament' => 1, 'league' => 10, 'tab' => 'queryTab' ), array() );
        self::assertSame( 'queryTab', $result2['view_model']->tab );

        // 3. Test default
        $tournament_service3 = $this->createMock( Tournament_Service::class );
        $tournament_service3->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service3->method( 'get_draw_view_model' )->willReturn( new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'finalResults',
            season: '2024'
        ) );
        $controller3 = new Tournament_Draw_Admin_Controller( $tournament_service3, $league_service, $dispatcher2, $guard );

        $result3 = $controller3->draw_page( array( 'tournament' => 1, 'league' => 10 ), array() );
        self::assertSame( 'finalResults', $result3['view_model']->tab );
    }

    /**
     * @throws ReflectionException
     */
    public function test_extract_season_priority(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament        = $this->create_tournament_instance( 1, 'T', 2024 );
        $league            = $this->createMock( League::class );
        $league->id        = 10;
        $league->event     = $this->createMock( Event::class );
        $league->event->id = 100;
        $league->method( 'get_league_teams' )->willReturn( array() );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $league_service = $this->createMock( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $guard      = $this->createMock( Action_Guard_Interface::class );
        $dispatcher = new Draw_Action_Dispatcher(
            $this->createMock( Draw_Action_Handler_Interface::class ),
            $guard
        );

        $controller = new Tournament_Draw_Admin_Controller( $tournament_service, $league_service, $dispatcher, $guard );

        // 1. POST season
        $tournament_service->method( 'get_draw_view_model' )->willReturn( new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'draw',
            season: '2026'
        ) );
        $result = $controller->draw_page( array( 'tournament' => 1, 'league' => 10 ), array( 'season' => '2026' ) );
        self::assertSame( '2026', (string) $result['view_model']->season );

        // 2. Query season
        $tournament_service_q = $this->createMock( Tournament_Service::class );
        $tournament_service_q->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service_q->method( 'get_draw_view_model' )->willReturn( new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'draw',
            season: '2025'
        ) );
        $controller_q = new Tournament_Draw_Admin_Controller( $tournament_service_q, $league_service, $dispatcher, $guard );
        $result2      = $controller_q->draw_page( array( 'tournament' => 1, 'league' => 10, 'season' => '2025' ), array() );
        self::assertSame( '2025', (string) $result2['view_model']->season );

        // 3. Fallback to tournament season
        $tournament_service_f = $this->createMock( Tournament_Service::class );
        $tournament_service_f->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service_f->method( 'get_draw_view_model' )->willReturn( new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'draw',
            season: '2024'
        ) );
        $controller_f = new Tournament_Draw_Admin_Controller( $tournament_service_f, $league_service, $dispatcher, $guard );
        $result3      = $controller_f->draw_page( array( 'tournament' => 1, 'league' => 10 ), array() );
        self::assertSame( '2024', (string) $result3['view_model']->season );
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }
}
