<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\League_Team_Admin_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Team_Admin_Service_Test extends TestCase {

    private League_Service $league_service;
    private Team_Service $team_service;
    private Team_Repository_Interface $team_repository;
    private League_Team_Admin_Service $service;

    protected function setUp(): void {
        $this->league_service  = $this->createMock( League_Service::class );
        $this->team_service    = $this->createMock( Team_Service::class );
        $this->team_repository = $this->createMock( Team_Repository_Interface::class );
        $this->service         = new League_Team_Admin_Service( $this->league_service, $this->team_service, $this->team_repository );
    }

    private function league( string $type = 'M', bool $player_entry = false, bool $consolation = false ): League {
        $league               = $this->createMock( League::class );
        $league->type         = $type;
        $championship         = $this->createMock( Championship::class );
        $championship->method( 'is_consolation' )->willReturn( $consolation );
        $league->championship = $championship;
        $event                = $this->createMock( Event::class );
        $event->competition   = (object) array( 'is_player_entry' => $player_entry );
        $league->event        = $event;
        $league->method( 'get_id' )->willReturn( 7 );
        return $league;
    }

    public function test_addable_teams_use_club_teams_by_default_and_map_ld_to_xd(): void {
        $this->team_service->expects( self::once() )->method( 'get_club_teams' )->with( 'XD' )->willReturn( array( 'club' ) );

        self::assertSame( array( 'club' ), $this->service->get_addable_teams( $this->league( 'LD' ), '2024' ) );
    }

    public function test_addable_teams_use_player_teams_for_player_entry(): void {
        $this->team_service->expects( self::once() )->method( 'get_player_teams' )->with( 'M' )->willReturn( array( 'player' ) );

        self::assertSame( array( 'player' ), $this->service->get_addable_teams( $this->league( 'M', true ), '2024' ) );
    }

    public function test_addable_teams_for_consolation_delegates_with_integer_season(): void {
        $league = $this->league( 'M', false, true );
        $this->league_service->expects( self::once() )->method( 'get_consolation_teams' )->with( $league, 2024 )->willReturn( array( 'loser' ) );

        self::assertSame( array( 'loser' ), $this->service->get_addable_teams( $league, '2024' ) );
    }

    public function test_add_teams_casts_ids_and_continues_after_failure(): void {
        $calls = array();
        $this->league_service->method( 'add_team_to_league' )->willReturnCallback(
            function ( int $team_id, int $league_id, int $season ) use ( &$calls ) {
                if ( 11 === $team_id ) {
                    throw new Team_Not_Found_Exception( 'Team not found' );
                }
                $calls[] = array( $team_id, $league_id, $season );
                return $this->createMock( League_Team::class );
            }
        );

        $result = $this->service->add_teams( $this->league(), array( '10', '11', '2_final_5', '12' ), 2024 );

        self::assertSame( array( array( 10, 7, 2024 ), array( 12, 7, 2024 ) ), $calls );
        self::assertSame( 2, $result['count'] );
        self::assertCount( 2, $result['errors'] );
    }

    public function test_delete_teams_reports_per_team_messages(): void {
        $league = $this->league();
        $this->league_service->expects( self::exactly( 2 ) )->method( 'delete_team' );

        $result = $this->service->delete_teams( $league, array( '3', '4' ), '2024' );

        self::assertSame( 2, $result['count'] );
        self::assertSame( array( '3 deleted', '4 deleted' ), $result['messages'] );
        self::assertSame( array(), $result['errors'] );
    }

    public function test_delete_teams_stops_and_reports_runtime_errors(): void {
        $this->league_service->method( 'delete_team' )->willThrowException( new \RuntimeException( 'Has matches' ) );

        $result = $this->service->delete_teams( $this->league(), array( '3', '4' ), '2024' );

        self::assertSame( 0, $result['count'] );
        self::assertSame( array( 'Has matches' ), $result['errors'] );
    }

    public function test_withdraw_teams_uses_team_title_in_message(): void {
        $team        = $this->createMock( Team::class );
        $team->title = 'Alpha 1';
        $this->team_repository->method( 'find_by_id' )->willReturn( $team );
        $this->league_service->expects( self::once() )->method( 'withdraw_team' );

        $result = $this->service->withdraw_teams( $this->league(), array( '3' ), '2024' );

        self::assertSame( array( 'Alpha 1 withdrawn' ), $result['messages'] );
    }

    public function test_rank_teams_delegates_to_league_service(): void {
        $league = $this->league();
        $this->league_service->expects( self::once() )->method( 'rank_teams' )->with( $league, 'manual', array( 'a' => 1 ), array( '1' ) )->willReturn( true );

        self::assertTrue( $this->service->rank_teams( $league, 'manual', array( '1' ), array( 'a' => 1 ) ) );
    }
}
