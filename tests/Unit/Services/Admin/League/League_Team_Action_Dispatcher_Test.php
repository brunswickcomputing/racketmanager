<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Standings\Standings_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Team_Action_Dispatcher_Test extends TestCase {
    private Standings_Service $standings_service;
    private Team_Repository_Interface $team_repository;

    protected function setUp(): void {
        parent::setUp();
        $this->standings_service = $this->createMock( Standings_Service::class );
        $this->team_repository   = $this->createStub( Team_Repository_Interface::class );
    }

    public function test_constructor_requires_handler_or_team_repository(): void {
        $this->expectException( \LogicException::class );
        $dispatcher = new League_Team_Action_Dispatcher( $this->createStub( League_Service::class ), $this->createStub( Action_Guard_Interface::class ), $this->standings_service );
        self::assertInstanceOf( League_Team_Action_Dispatcher::class, $dispatcher );
    }

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

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        $league = new class() {
            public int $id = 5;
        };

        $league_service->expects( self::exactly( 2 ) )
            ->method( 'delete_team' )
            ->willReturnCallback(
                function ( $passed_league, $team_id, $season ) use ( $league ) {
                    self::assertSame( $league, $passed_league );
                    self::assertContains( $team_id, array( 10, 12 ) );
                    self::assertSame( '2026', $season );
                }
            );

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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
    }

    public function test_withdraw_teams_success(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
        };

        $league_service->expects( self::once() )
            ->method( 'withdraw_team' )
            ->with( $league, 10, '2026' );

        $team            = Team::from_object( (object) array( 'id' => 10, 'title' => 'Leigh A' ) );
        $team_repository = $this->createMock( Team_Repository_Interface::class );
        $team_repository->expects( self::once() )->method( 'find_by_id' )->with( 10 )->willReturn( $team );

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $team_repository );
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

        self::assertSame( 'Leigh A withdrawn', strval( $result->message ) );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_withdraw_teams_falls_back_to_team_id_when_team_not_found(): void {
        $league_service = $this->createStub( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );
        $league         = new class() {
            public int $id = 5;
        };

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        self::assertSame( '10 withdrawn', strval( $result->message ) );
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

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );

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

        $league = League::from_object( (object) [ 'title' => 'Division 1' ] );
        $league->id = 5;
        $this->standings_service->expects( self::once() )->method( 'save_standings_manually' )->with(
            $league,
            [ 1 => 10 ],
            [ 'points_plus' => [ 1 => 10 ], 'points_minus' => [ 1 => 2 ], 'add_points' => [ 1 => 0 ] ],
            [ 'num_done_matches' => [ 1 => 5 ], 'num_won_matches' => [ 1 => 4 ], 'num_draw_matches' => [ 1 => 0 ], 'num_lost_matches' => [ 1 => 1 ] ],
            [ 1 => 'note' ]
        );

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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
    }

    public function test_delete_teams_returns_error_when_league_service_throws(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
        };

        $league_service->expects( self::once() )
            ->method( 'delete_team' )
            ->willThrowException( new \RuntimeException( 'Failed to delete fixture for team' ) );

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
        $result     = $dispatcher->handle(
            array( 'league_id' => '5' ),
            array(
                'doAction' => '1',
                'action'   => 'delete',
                'season'   => '2026',
                'team'     => array( '10' ),
            ),
            $league
        );

        self::assertSame( 'Failed to delete fixture for team', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_withdraw_teams_returns_error_when_league_service_throws(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 5;
        };

        $league_service->expects( self::once() )
            ->method( 'withdraw_team' )
            ->willThrowException( new \RuntimeException( 'Fixture result manager required' ) );

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
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

        self::assertSame( 'Fixture result manager required', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_withdraw_teams_returns_error_when_league_not_found(): void {
        $league_service = $this->createMock( League_Service::class );
        $guard          = $this->createStub( Action_Guard_Interface::class );

        $league = new class() {
            public int $id = 999;
        };

        $league_service->expects( self::once() )
            ->method( 'withdraw_team' )
            ->willThrowException( new League_Not_Found_Exception( 'Cannot withdraw team from League: League not found.' ) );

        $dispatcher = new League_Team_Action_Dispatcher( $league_service, $guard, $this->standings_service, team_repository: $this->team_repository );
        $result     = $dispatcher->handle(
            array( 'league_id' => '999' ),
            array(
                'doAction' => '1',
                'action'   => 'withdraw',
                'season'   => '2026',
                'team'     => array( '10' ),
            ),
            $league
        );

        self::assertSame( 'Cannot withdraw team from League: League not found.', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }
}
