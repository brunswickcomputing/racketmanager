<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture_Service;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Match_Action_Dispatcher_Test extends TestCase {

    private function create_dispatcher(
        ?Fixture_Repository_Interface $fixture_repo = null,
        ?Fixture_Maintenance_Service $maintenance_service = null,
        ?Action_Guard_Interface $guard = null,
        ?Fixture_Result_Manager $result_manager = null,
        ?Fixture_Service $fixture_service = null
    ): League_Match_Action_Dispatcher {
        return new League_Match_Action_Dispatcher(
            $fixture_repo ?? $this->createMock( Fixture_Repository_Interface::class ),
            $maintenance_service ?? $this->createMock( Fixture_Maintenance_Service::class ),
            $guard ?? $this->createStub( Action_Guard_Interface::class ),
            $result_manager ?? $this->createMock( Fixture_Result_Manager::class ),
            $fixture_service ?? $this->createMock( Fixture_Service::class )
        );
    }

    public function test_delete_matches_nonce_failure_returns_error(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_matches-bulk', 'del_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = $this->create_dispatcher( guard: $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'delMatches' => '1',
                'match'      => array( '101' ),
            ),
            $league
        );

        self::assertSame( 'Security token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_delete_matches_success(): void {
        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $maintenance_service->expects( self::exactly( 2 ) )
            ->method( 'delete_fixture' )
            ->willReturn( true );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = $this->create_dispatcher( maintenance_service: $maintenance_service );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'delMatches' => '1',
                'match'      => array( '101', '102' ),
            ),
            $league
        );

        self::assertSame( 'Match id 101 deleted<br>Match id 102 deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_add_matches_existing_final_returns_error(): void {
        $league = new class() {
            public int $id = 5;
            public function get_matches( array $args ): array {
                return array( new stdClass() );
            }
        };

        $dispatcher = $this->create_dispatcher();
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'match',
                'mode'         => 'add',
                'final'        => 'semi',
                'season'       => '2026',
                'match'        => array( '1' ),
            ),
            $league
        );

        self::assertSame( 'Matches already exist for semi', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_add_matches_success(): void {
        $league = new class() {
            public int $id = 5;
            public function get_matches( array $args ): array {
                return array();
            }
        };

        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::exactly( 2 ) )
            ->method( 'add_match' )
            ->with(
                self::isInstanceOf( stdClass::class ),
                $league
            );

        $dispatcher = $this->create_dispatcher( fixture_service: $fixture_service );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5', 'group' => 'A' ),
            array(
                'updateLeague'  => 'match',
                'mode'          => 'add',
                'season'        => '2026',
                'match'         => array( 0 => '1', 1 => '2' ),
                'home_team'     => array( 0 => 'Team A', 1 => 'Team C' ),
                'away_team'     => array( 0 => 'Team B', 1 => 'Team D' ),
                'myDatePicker'  => array( 0 => '2026-05-01', 1 => '2026-05-08' ),
                'begin_hour'    => array( 0 => '10', 1 => '11' ),
                'begin_minutes' => array( 0 => '30', 1 => '00' ),
                'match_day'     => array( 0 => '1', 1 => '2' ),
            ),
            $league
        );

        self::assertSame( '2 Matches added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_add_matches_validates_all_rows_before_writing(): void {
        $league = (object) [ 'id' => 5 ];
        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::never() )->method( 'add_match' );
        $result = $this->create_dispatcher( fixture_service: $fixture_service )->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'match', 'mode' => 'add', 'match' => array( 0, 1 ),
                'home_team' => array( '1', '3' ), 'away_team' => array( '2', '4' ),
                'myDatePicker' => array( '2026-05-15', '2026-02-31' ),
                'begin_hour' => array( '19', '18' ), 'begin_minutes' => array( '30', '00' ),
            ),
            $league
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'row 2', $result->message );
        self::assertStringContainsString( '2026-02-31', $result->message );
    }

    public function test_add_matches_skips_empty_date_without_overcounting(): void {
        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::once() )->method( 'add_match' );
        $result = $this->create_dispatcher( fixture_service: $fixture_service )->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'match', 'mode' => 'add', 'match' => array( 0, 1 ),
                'home_team' => array( '1', '3' ), 'away_team' => array( '2', '4' ),
                'myDatePicker' => array( '2026-05-15', '' ),
            ),
            (object) array( 'id' => 5 )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( '1 Match added', $result->message );
    }

    public function test_edit_matches_success(): void {
        $existing_match = new Fixture();
        $existing_match->id = 55;
        $existing_match->league_id = 5;

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 55 )
            ->willReturn( $existing_match );

        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::once() )
            ->method( 'update_match' )
            ->with( $existing_match );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = $this->create_dispatcher(
            fixture_repo: $fixture_repo,
            fixture_service: $fixture_service
        );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague'  => 'match',
                'mode'          => 'edit',
                'match'         => array( 0 => '55' ),
                'home_team'     => array( 0 => 'Team Home' ),
                'away_team'     => array( 0 => 'Team Away' ),
                'myDatePicker'  => array( 0 => '2026-06-01' ),
                'begin_hour'    => array( 0 => '14' ),
                'begin_minutes' => array( 0 => '00' ),
                'match_day'     => array( 0 => '3' ),
            ),
            $league
        );

        self::assertSame( '1 Match updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
        self::assertSame( '2026-06-01 14:00:00', $existing_match->date );
        self::assertSame( '14:00', $existing_match->get_start_time() );
    }

    public function test_edit_matches_validates_all_rows_before_writing(): void {
        $first = new Fixture();
        $first->set_date( '2026-05-15 19:30:00' );
        $second = new Fixture();
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturnMap( [ [ 55, $first ], [ 56, $second ] ] );
        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::never() )->method( 'update_match' );
        $result = $this->create_dispatcher( fixture_repo: $fixture_repo, fixture_service: $fixture_service )->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'match', 'mode' => 'edit', 'match' => array( 55, 56 ),
                'myDatePicker' => array( '2026-06-01', 'invalid-date' ),
                'begin_hour' => array( '18', '18' ), 'begin_minutes' => array( '30', '30' ),
            ),
            (object) array( 'id' => 5 )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'row 2', $result->message );
        self::assertSame( '2026-05-15 19:30:00', $first->get_date() );
    }

    public function test_edit_matches_keeps_existing_date_when_date_is_empty(): void {
        $fixture = new Fixture();
        $fixture->set_date( '2026-05-15 19:30:00' );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );
        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::once() )->method( 'update_match' );
        $result = $this->create_dispatcher( fixture_repo: $fixture_repo, fixture_service: $fixture_service )->handle(
            array( 'league_id' => '5' ),
            array( 'updateLeague' => 'match', 'mode' => 'edit', 'match' => array( 55 ), 'myDatePicker' => array( '' ) ),
            (object) array( 'id' => 5 )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( '2026-05-15 19:30:00', $fixture->get_date() );
        self::assertSame( '19:30', $fixture->get_start_time() );
    }

    public function test_edit_matches_keeps_existing_date_when_date_components_are_empty(): void {
        $fixture = new Fixture();
        $fixture->set_date( '2026-05-15 19:30:00' );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );
        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::once() )->method( 'update_match' );
        $result = $this->create_dispatcher( fixture_repo: $fixture_repo, fixture_service: $fixture_service )->handle(
            array( 'league_id' => '5' ),
            array( 'updateLeague' => 'match', 'mode' => 'edit', 'match' => array( 55 ),
                'year' => array( '' ), 'month' => array( '' ), 'day' => array( '' ) ),
            (object) array( 'id' => 5 )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( '2026-05-15 19:30:00', $fixture->get_date() );
        self::assertSame( '19:30', $fixture->get_start_time() );
    }

    public function test_edit_matches_midnight_clears_start_time(): void {
        $existing_match = new Fixture();
        $existing_match->id = 56;
        $existing_match->league_id = 5;
        $existing_match->set_date( '2026-05-15 19:30:00' );
        self::assertSame( '19:30', $existing_match->get_start_time() );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 56 )
            ->willReturn( $existing_match );

        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::once() )
            ->method( 'update_match' )
            ->with( $existing_match );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = $this->create_dispatcher(
            fixture_repo: $fixture_repo,
            fixture_service: $fixture_service
        );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague'  => 'match',
                'mode'          => 'edit',
                'match'         => array( 0 => '56' ),
                'home_team'     => array( 0 => 'Team Home' ),
                'away_team'     => array( 0 => 'Team Away' ),
                'myDatePicker'  => array( 0 => '2026-06-01' ),
                'begin_hour'    => array( 0 => '00' ),
                'begin_minutes' => array( 0 => '00' ),
                'match_day'     => array( 0 => '3' ),
            ),
            $league
        );

        self::assertSame( '1 Match updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
        self::assertSame( '2026-06-01 00:00:00', $existing_match->date );
        self::assertNull( $existing_match->get_start_time() );
    }

    public function test_update_results_success_and_no_updates(): void {
        $league     = ( new \ReflectionClass( \Racketmanager\Domain\Competition\League::class ) )->newInstanceWithoutConstructor();
        $league->id = 5;

        $result_manager = $this->createMock( Fixture_Result_Manager::class );
        $result_manager->expects( self::once() )
            ->method( 'update_match_results' )
            ->with(
                $league,
                array( '1' => 101, '2' => 102 ),
                array( '1' => 6, '2' => 4 ),
                array( '1' => 0, '2' => 2 ),
                array(),
                '2026',
                false
            )
            ->willReturn( 2 );

        $dispatcher = $this->create_dispatcher( result_manager: $result_manager );

        $success = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'results',
                'season'       => '2026',
                'matches'      => array( '1' => 101, '2' => 102 ),
                'home_points'  => array( '1' => 6, '2' => 4 ),
                'away_points'  => array( '1' => 0, '2' => 2 ),
            ),
            $league
        );
        self::assertSame( 'Updated Results of 2 matches', $success->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $success->message_type );
        self::assertSame( 'matches', $success->tab_override );

        $no_update = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'results',
                'matches'      => array(),
            ),
            $league
        );
        self::assertNull( $no_update->message );
        self::assertNull( $no_update->message_type );
        self::assertSame( 'matches', $no_update->tab_override );
    }

    public function test_manage_matches_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-matches', 'edit_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'Manage matches forbidden' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = $this->create_dispatcher( guard: $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'match',
                'mode'         => 'add',
            ),
            $league
        );

        self::assertSame( 'Manage matches forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_update_results_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_matches-bulk', 'update_results' )
            ->willThrowException( new Invalid_Status_Exception( 'Results forbidden' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = $this->create_dispatcher( guard: $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'results',
            ),
            $league
        );

        self::assertSame( 'Results forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_add_matches_with_invalid_date_returns_error_result(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-matches', 'edit_matches' );

        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::never() )->method( 'add_match' );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = $this->create_dispatcher( guard: $guard, fixture_service: $fixture_service );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'match',
                'mode'         => 'add',
                'match'        => array( 0 ),
                'home_team'    => array( '1' ),
                'away_team'    => array( '2' ),
                'myDatePicker' => array( '2026-02-31' ),
                'begin_hour'   => array( '19' ),
                'begin_minutes'=> array( '30' ),
            ),
            $league
        );

        self::assertSame( 'Invalid date format provided for match row 1: "2026-02-31 19:30:00"', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_edit_matches_with_invalid_date_returns_error_result(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-matches', 'edit_matches' );

        $fixture = new Fixture();
        $fixture->set_id( 12 );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )->method( 'find_by_id' )->with( 12 )->willReturn( $fixture );

        $fixture_service = $this->createMock( Fixture_Service::class );
        $fixture_service->expects( self::never() )->method( 'update_match' );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = $this->create_dispatcher(
            fixture_repo: $fixture_repo,
            guard: $guard,
            fixture_service: $fixture_service
        );

        $result = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'match',
                'mode'         => 'edit',
                'match'        => array( 12 ),
                'myDatePicker' => array( 'invalid-date' ),
                'begin_hour'   => array( '19' ),
                'begin_minutes'=> array( '30' ),
            ),
            $league
        );

        self::assertSame( 'Invalid date format provided for match row 1: "invalid-date 19:30:00"', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'matches', $result->tab_override );
    }
}
