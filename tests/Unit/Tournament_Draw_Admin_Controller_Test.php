<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Draw_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Domain\League;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\League_Service;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;

require_once __DIR__ . '/../wp-stubs.php';

final class Tournament_Draw_Admin_Controller_Test extends TestCase {

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    /**
     * @throws ReflectionException
     */
    public function test_draw_page_returns_view_model_on_get(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament = $this->create_tournament_instance( 1, 'Test Tournament', 2024 );
        $league     = $this->create_league_instance( 10, 'Test League' );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( 1 )
            ->willReturn( $tournament );

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
            $this->createMock( \Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface::class ),
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
            'league-tab' => 'matches',
        );
        $post = array();

        $result = $controller->draw_page( $query, $post );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Draw_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'matches', $result['view_model']->tab );
        self::assertSame( '10', (string) $result['view_model']->league->id );
        self::assertSame( '2024', (string) $result['view_model']->season );
        self::assertSame( 'matches', $result['redirect_tab'] );
        self::assertArrayNotHasKey( 'redirect', $result );
    }

    /**
     * @throws ReflectionException
     */
    public function test_draw_page_returns_redirect_on_post(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament = $this->create_tournament_instance( 1, 'Test Tournament', 2024 );
        $league     = $this->create_league_instance( 10, 'Test League' );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $league_service = $this->createMock( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_matches' );

        $handler = $this->createMock( \Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface::class );
        $handler->method( 'update_final_results' )
            ->willReturn( new \Racketmanager\Domain\DTO\Admin\Action_Result_DTO( tab_override: 'finalResults' ) );

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
        $post = array(
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
        self::assertStringContainsString( 'league-tab=finalResults', $result['redirect'] );
        self::assertStringContainsString( 'season=2025', $result['redirect'] );
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

    /**
     * @throws ReflectionException
     */
    private function create_league_instance( int $id, string $name ): League {
        $reflection = new ReflectionClass( League::class );
        $league     = $reflection->newInstanceWithoutConstructor();

        $this->set_property( $league, 'id', $id );
        $this->set_property( $league, 'title', $name );

        return $league;
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
}
