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
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Match_Action_Dispatcher_Test extends TestCase {

    public function test_delete_matches_nonce_failure_returns_error(): void {
        $fixture_repo        = $this->createMock( Fixture_Repository_Interface::class );
        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_matches-bulk', 'del_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Match_Action_Dispatcher( $fixture_repo, $maintenance_service, $guard );
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
        $fixture_repo        = $this->createMock( Fixture_Repository_Interface::class );
        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $maintenance_service->expects( self::exactly( 2 ) )
            ->method( 'delete_fixture' )
            ->willReturn( true );

        $guard = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Match_Action_Dispatcher( $fixture_repo, $maintenance_service, $guard );
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
        $fixture_repo        = $this->createMock( Fixture_Repository_Interface::class );
        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
            public function get_matches( array $args ): array {
                return array( new stdClass() );
            }
        };

        $dispatcher = new League_Match_Action_Dispatcher( $fixture_repo, $maintenance_service, $guard );
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
        $fixture_repo        = $this->createMock( Fixture_Repository_Interface::class );
        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $added_matches = array();
        $league        = new class( $added_matches ) {
            public int $id = 5;
            public function __construct( public array &$added_matches ) {}
            public function get_matches( array $args ): array {
                return array();
            }
            public function add_match( object $match ): void {
                $this->added_matches[] = $match;
            }
        };

        $dispatcher = new League_Match_Action_Dispatcher( $fixture_repo, $maintenance_service, $guard );
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
        self::assertCount( 2, $added_matches );
        self::assertSame( '2026-05-01 10:30:00', $added_matches[0]->date );
        self::assertNull( $added_matches[0]->group );
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

        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $updated_matches = array();
        $league          = new class( $updated_matches ) {
            public int $id = 5;
            public function __construct( public array &$updated_matches ) {}
            public function update_match( object $match ): void {
                $this->updated_matches[] = $match;
            }
        };

        $dispatcher = new League_Match_Action_Dispatcher( $fixture_repo, $maintenance_service, $guard );
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
        self::assertCount( 1, $updated_matches );
        self::assertSame( '2026-06-01 14:00:00', $updated_matches[0]->date );
    }

    public function test_update_results_success_and_no_updates(): void {
        $fixture_repo        = $this->createMock( Fixture_Repository_Interface::class );
        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
            public function set_finals( bool $finals ): void {}
            public function update_match_results( array $matches, array $home_points, array $away_points, array $custom, string $season, bool $final ): int {
                return count( $matches );
            }
        };

        $dispatcher = new League_Match_Action_Dispatcher( $fixture_repo, $maintenance_service, $guard );

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
        $fixture_repo        = $this->createMock( Fixture_Repository_Interface::class );
        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-matches', 'edit_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'Manage matches forbidden' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Match_Action_Dispatcher( $fixture_repo, $maintenance_service, $guard );
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
        $fixture_repo        = $this->createMock( Fixture_Repository_Interface::class );
        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_matches-bulk', 'update_results' )
            ->willThrowException( new Invalid_Status_Exception( 'Results forbidden' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Match_Action_Dispatcher( $fixture_repo, $maintenance_service, $guard );
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
}
