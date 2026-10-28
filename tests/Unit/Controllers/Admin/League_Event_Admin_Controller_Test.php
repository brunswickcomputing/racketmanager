<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\League_Event_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_League_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Event_Admin_Controller_Test extends TestCase {
    use Resolves_League_Admin_Controllers;

    private function repository_with_event( array $seasons = array( '2026' => array( 'name' => '2026' ) ) ): Event_Repository_Interface {
        $competition = Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE );
        $event       = Event::from_object( (object) array( 'id' => 10, 'name' => 'Mixed Doubles', 'seasons' => $seasons ), $competition );
        $repository  = $this->createMock( Event_Repository_Interface::class );
        $repository->method( 'find_by_id' )->willReturnCallback( static fn( $id ) => 10 === (int) $id ? $event : null );

        return $repository;
    }

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'league_event_admin_controller' );
        $this->deny_capability( 'edit_leagues' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->event_page( array(), array() );
    }

    public function test_unknown_event_throws(): void {
        $controller = $this->resolve_controller( 'league_event_admin_controller', array( 'event_repository' => $this->repository_with_event() ) );

        $this->expectException( Event_Not_Found_Exception::class );
        $controller->event_page( array( 'event_id' => '99' ), array() );
    }

    public function test_get_returns_event_view_model(): void {
        $controller = $this->resolve_controller( 'league_event_admin_controller', array( 'event_repository' => $this->repository_with_event() ) );

        $result = $controller->event_page( array( 'event_id' => '10', 'season' => '2026' ), array() );

        self::assertInstanceOf( League_Event_Page_View_Model::class, $result['view_model'] );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertSame( 'leagues', $result['view_model']->tab );
        self::assertSame( array(), $result['view_model']->league_matches_map );
    }

    public function test_post_add_league_redirects_back_to_the_league_screen(): void {
        $controller = $this->resolve_controller( 'league_event_admin_controller' );

        $result = $controller->event_page( array( 'page' => 'racketmanager-leagues', 'view' => 'event', 'event_id' => '10' ), array( 'addLeague' => '1' ) );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
    }
}
