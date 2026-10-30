<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Resolves_League_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Match_Admin_Controller_Test extends TestCase {
    use Resolves_League_Admin_Controllers;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_league( int $id = 42, bool $is_cup = false ): League {
        $competition = Competition_Test_Factory::create( id: 1, type: $is_cup ? Competition_Type::CUP : Competition_Type::LEAGUE );
        $event       = \Racketmanager\Domain\Competition\Event::from_object(
            (object) array(
                'id'             => 93,
                'name'           => 'Event',
                'type'           => 'singles',
                'seasons'        => array( '2026' => array( 'name' => '2026' ) ),
                'current_season' => array( 'name' => '2026' ),
            ),
            $competition
        );

        $league = League::from_object(
            (object) array(
                'id'              => $id,
                'title'           => 'Division 1',
                'current_season'  => array( 'name' => '2026' ),
            ),
            $event
        );
        $league->set_season( '2026' );
        $league->num_teams_total = 8;
        if ( $is_cup ) {
            $league->mode            = 'championship';
            $league->is_championship = true;
            $championship            = $this->createStub( Championship::class );
            $championship->method( 'get_finals' )->willReturn( array(
                'key'         => 'semi',
                'name'        => 'Semi-Final',
                'num_matches' => 2,
                'round'       => 2,
            ) );
            $championship->method( 'get_current_final_key' )->willReturn( 'semi' );
            $championship->method( 'get_final_teams' )->willReturn( array() );
            $championship->method( 'get_final_keys' )->willReturn( 'semi' );
            $championship->method( '__get' )->willReturnCallback( static fn( string $name ): ?int => 'num_teams_first_round' === $name ? 8 : null );
            $league->championship                = $championship;
        }

        return $league;
    }

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'league_match_admin_controller' );
        $this->deny_capability( 'edit_matches' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->match_page( array(), array() );
    }

    public function test_missing_league_throws(): void {
        $controller = $this->resolve_controller( 'league_match_admin_controller' );

        $this->expectException( League_Not_Found_Exception::class );
        $controller->match_page( array( 'league_id' => '999' ), array() );
    }

    public function test_unknown_edit_fixture_throws(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )->method( 'find_by_id' )->with( 123 )->willReturn( null );

        $controller = $this->resolve_controller( 'league_match_admin_controller', array( 'fixture_repository' => $fixture_repo ) );

        $this->expectException( Fixture_Not_Found_Exception::class );
        $controller->match_page( array( 'edit' => '123' ), array() );
    }

    public function test_get_edit_single_fixture_returns_view_model(): void {
        $league = $this->create_league( 42 );

        $fixture            = new Fixture();
        $fixture->id        = 123;
        $fixture->league_id = 42;
        $fixture->match_day = 3;

        $fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $controller = $this->resolve_controller(
            'league_match_admin_controller',
            array(
                'fixture_repository' => $fixture_repo,
                'league_service'     => $league_service,
            )
        );

        $result = $controller->match_page( array( 'edit' => '123' ), array() );

        self::assertInstanceOf( Tournament_Fixtures_Page_View_Model::class, $result['view_model'] );
        self::assertTrue( $result['view_model']->edit );
        self::assertSame( 1, $result['view_model']->max_fixtures );
        self::assertSame( $fixture, $result['view_model']->fixtures[0] );
    }

    public function test_get_final_without_mode_treats_as_edit_mode_and_queries_matches(): void {
        $league = $this->create_league( 42, true );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $league_query_service = $this->createMock( League_Query_Service::class );
        $league_query_service->expects( self::once() )
            ->method( 'get_matches' )
            ->with( $league, self::callback( static fn( array $args ): bool => 'semi' === $args['final'] && array( 'id' => 'ASC' ) === $args['orderby'] ) )
            ->willReturn( array( (object) array( 'id' => 777 ) ) );

        $controller = $this->resolve_controller(
            'league_match_admin_controller',
            array(
                'league_service'       => $league_service,
                'league_query_service' => $league_query_service,
            )
        );

        $result = $controller->match_page( array( 'league_id' => '42', 'final' => 'semi' ), array() );

        self::assertInstanceOf( Tournament_Fixtures_Page_View_Model::class, $result['view_model'] );
        self::assertStringContainsString( 'Edit Matches', $result['view_model']->form_title );
        self::assertCount( 1, $result['view_model']->fixtures );
        self::assertSame( 777, $result['view_model']->fixtures[0]->id );
    }

    public function test_get_final_with_add_mode_prepares_blank_fixtures(): void {
        $league = $this->create_league( 42, true );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $controller = $this->resolve_controller(
            'league_match_admin_controller',
            array(
                'league_service' => $league_service,
            )
        );

        $result = $controller->match_page( array( 'league_id' => '42', 'final' => 'final', 'mode' => 'add' ), array() );

        self::assertInstanceOf( Tournament_Fixtures_Page_View_Model::class, $result['view_model'] );
        self::assertStringContainsString( 'Add Matches', $result['view_model']->form_title );
        self::assertSame( 'final', $result['view_model']->final_key );
    }

    public function test_final_without_championship_throws(): void {
        $league = $this->create_league( 42, false );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $controller = $this->resolve_controller(
            'league_match_admin_controller',
            array(
                'league_service' => $league_service,
            )
        );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->match_page( array( 'league_id' => '42', 'final' => 'semi' ), array() );
    }

    public function test_get_add_matches_returns_view_model(): void {
        $league = $this->create_league( 42 );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $league_query_service = $this->createStub( League_Query_Service::class );
        $league_query_service->method( 'set_season' )->willReturnCallback(
            static function( League $league ): void {
                $league->num_teams_total = 8;
            }
        );

        $controller = $this->resolve_controller(
            'league_match_admin_controller',
            array(
                'league_service'       => $league_service,
                'league_query_service' => $league_query_service,
            )
        );

        $result = $controller->match_page( array( 'league_id' => '42' ), array() );

        self::assertInstanceOf( Tournament_Fixtures_Page_View_Model::class, $result['view_model'] );
        self::assertFalse( $result['view_model']->edit );
        self::assertSame( 8, $result['view_model']->max_fixtures );
    }
}
