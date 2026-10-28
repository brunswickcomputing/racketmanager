<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Setup_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_League_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Setup_Event_Admin_Controller_Test extends TestCase {
    use Resolves_League_Admin_Controllers;

    private function repository_with_event(): Event_Repository_Interface {
        $competition = Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE );
        $event       = Event::from_object(
            (object) array( 'id' => 10, 'name' => 'Mixed Doubles', 'seasons' => array( '2026' => array( 'name' => '2026' ) ) ),
            $competition
        );
        $repository = $this->createMock( Event_Repository_Interface::class );
        $repository->method( 'find_by_id' )->willReturnCallback( static fn( $id ) => 10 === (int) $id ? $event : null );

        return $repository;
    }

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'league_setup_event_admin_controller' );
        $this->deny_capability( 'edit_matches' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->setup_event_page( array(), array() );
    }

    public function test_unknown_event_throws(): void {
        $controller = $this->resolve_controller( 'league_setup_event_admin_controller', array( 'event_repository' => $this->repository_with_event() ) );

        $this->expectException( Event_Not_Found_Exception::class );
        $controller->setup_event_page( array( 'event_id' => '99', 'season' => '2026' ), array() );
    }

    public function test_missing_season_throws(): void {
        $controller = $this->resolve_controller( 'league_setup_event_admin_controller', array( 'event_repository' => $this->repository_with_event() ) );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->setup_event_page( array( 'event_id' => '10' ), array() );
    }

    public function test_get_returns_setup_view_model_for_the_event(): void {
        $controller = $this->resolve_controller( 'league_setup_event_admin_controller', array( 'event_repository' => $this->repository_with_event() ) );

        $result = $controller->setup_event_page( array( 'event_id' => '10', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 10, (int) $result['view_model']->event->id );
        self::assertSame( '2026', $result['view_model']->season );
    }

    public function test_post_redirects_back_to_the_league_screen(): void {
        $controller = $this->resolve_controller( 'league_setup_event_admin_controller' );

        $result = $controller->setup_event_page(
            array( 'page' => 'racketmanager-leagues', 'view' => 'setup-event', 'event_id' => '10', 'season' => '2026' ),
            array( 'action' => 'setMatchDates' )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
    }
}
