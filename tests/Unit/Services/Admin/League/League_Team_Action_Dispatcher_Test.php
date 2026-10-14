<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Team_Action_Dispatcher_Test extends TestCase {

    public function test_nonce_failure_on_bulk_action_returns_error(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'del_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'doAction' => '1',
                'action'   => 'delete',
                'team'     => array( '10' ),
            ),
            $league
        );

        self::assertSame( 'Security token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_teams_success(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $deleted_teams = array();
        $league        = new class( $deleted_teams ) {
            public int $id = 5;
            public function __construct( public array &$deleted_teams ) {}
            public function delete_team( int $team_id, ?string $season ): void {
                $this->deleted_teams[] = array( 'team_id' => $team_id, 'season' => $season );
            }
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'doAction' => '1',
                'action'   => 'delete',
                'season'   => '2026',
                'team'     => array( '10', '12' ),
            ),
            $league
        );

        self::assertSame( '10 deleted<br>12 deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertCount( 2, $deleted_teams );
        self::assertSame( 10, $deleted_teams[0]['team_id'] );
        self::assertSame( '2026', $deleted_teams[0]['season'] );
    }

    public function test_withdraw_teams_success(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $withdrawn_teams = array();
        $league          = new class( $withdrawn_teams ) {
            public int $id = 5;
            public function __construct( public array &$withdrawn_teams ) {}
            public function withdraw_team( int $team_id, ?string $season ): void {
                $this->withdrawn_teams[] = array( 'team_id' => $team_id, 'season' => $season );
            }
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'doAction' => '1',
                'action'   => 'withdraw',
                'season'   => '2026',
                'team'     => array( '10' ),
            ),
            $league
        );

        self::assertStringContainsString( 'withdrawn', strval( $result->message ) );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertCount( 1, $withdrawn_teams );
    }

    public function test_add_teams_success(): void {
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( 15, 5, 2026 );

        $guard = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
            public bool $is_championship = true;
            public function get_id(): int {
                return $this->id;
            }
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'action'   => 'addTeamsToLeague',
                'event_id' => '1',
                'season'   => '2026',
                'team'     => array( '15' ),
            ),
            $league
        );

        self::assertSame( 'Team added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'preliminary', $result->tab_override );
    }

    public function test_add_teams_handles_exception(): void {
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->willThrowException( new League_Not_Found_Exception( 'League not found' ) );

        $guard = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
            public function get_id(): int {
                return $this->id;
            }
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'action'   => 'addTeamsToLeague',
                'event_id' => '1',
                'season'   => '2026',
                'team'     => array( '15' ),
            ),
            $league
        );

        self::assertSame( 'League not found', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_teams_mixed_success_and_error(): void {
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::exactly( 2 ) )
            ->method( 'add_team_to_league' )
            ->willReturnCallback(
                function ( int $team_id, int $league_id, int $season ): League_Team {
                    if ( 20 === $team_id ) {
                        throw new League_Not_Found_Exception( 'Team 20 not found' );
                    }
                    return $this->createStub( League_Team::class );
                }
            );

        $guard = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
            public function get_id(): int {
                return $this->id;
            }
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'action'   => 'addTeamsToLeague',
                'event_id' => '1',
                'season'   => '2026',
                'team'     => array( '10', '20' ),
            ),
            $league
        );

        self::assertSame( 'Team added<br>Team 20 not found', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_teams_guard_failure(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Security check failed' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'action'   => 'addTeamsToLeague',
                'event_id' => '1',
                'season'   => '2026',
                'team'     => array( '10' ),
            ),
            $league
        );

        self::assertSame( 'Security check failed', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_ranking_guard_failure(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'update_results' )
            ->willThrowException( new Invalid_Status_Exception( 'Ranking forbidden' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'saveRanking' => '1',
                'table_id'    => array( 1 => '10' ),
            ),
            $league
        );

        self::assertSame( 'Ranking forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_manual_standings_guard_failure(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'update_results' )
            ->willThrowException( new Invalid_Status_Exception( 'Manual standings forbidden' ) );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague' => 'teams_manual',
            ),
            $league
        );

        self::assertSame( 'Manual standings forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_ranking_success_and_no_updates(): void {
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::exactly( 2 ) )
            ->method( 'rank_teams' )
            ->willReturnOnConsecutiveCalls( true, false );

        $guard = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );

        $success = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'saveRanking' => '1',
                'table_id'    => array( 1 => '10', 2 => '20' ),
            ),
            $league
        );
        self::assertSame( 'Team ranking saved', $success->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $success->message_type );

        $no_update = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'randomRanking' => '1',
                'table_id'      => array( 1 => '10', 2 => '20' ),
            ),
            $league
        );
        self::assertSame( 'No updates', $no_update->message );
        self::assertSame( Admin_Message_Type::WARNING, $no_update->message_type );
    }

    public function test_manual_standings_success(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $saved_data = array();
        $league     = new class( $saved_data ) {
            public int $id = 5;
            public function __construct( public array &$saved_data ) {}
            public function save_standings_manually( mixed $team_id, array $points, array $matches, mixed $custom ): void {
                $this->saved_data = array(
                    'team_id' => $team_id,
                    'points'  => $points,
                    'matches' => $matches,
                    'custom'  => $custom,
                );
            }
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'updateLeague'     => 'teams_manual',
                'points_plus'      => array( '1' => 10 ),
                'points_minus'     => array( '1' => 2 ),
                'add_points'       => array( '1' => 0 ),
                'num_done_matches' => array( '1' => 5 ),
                'num_won_matches'  => array( '1' => 4 ),
                'num_draw_matches' => array( '1' => 0 ),
                'num_lost_matches' => array( '1' => 1 ),
                'team_id'          => array( '1' => 10 ),
                'custom'           => array( '1' => 'note' ),
            ),
            $league
        );

        self::assertSame( 'Standings Table updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 10, $saved_data['points']['points_plus']['1'] );
    }
}
