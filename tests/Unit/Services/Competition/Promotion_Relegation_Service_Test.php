<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\Promotion_Relegation_Service;

#[AllowMockObjectsWithoutExpectations]
final class Promotion_Relegation_Service_Test extends TestCase {

	private function league( int $id, int $num_teams_total = 8 ): League {
		$league                   = $this->createStub( League::class );
		$league->id               = $id;
		$league->num_teams_total  = $num_teams_total;
		$league->current_season   = array( 'name' => '2026' );
		return $league;
	}

	private function team_row( int $team_id, int $league_id, int $old_rank, int $rank, int $table_id, string $status = '' ): object {
		return (object) array(
			'team_id'   => $team_id,
			'league_id' => $league_id,
			'old_rank'  => $old_rank,
			'rank'      => $rank,
			'table_id'  => $table_id,
			'status'    => $status,
		);
	}

	private function team_entity( int $club_id ): Team {
		$team          = $this->createStub( Team::class );
		$team->club_id = $club_id;
		return $team;
	}

	/**
	 * @param array<int, Team|null> $teams_by_id
	 */
	private function service(
		League_Team_Repository_Interface $league_team_repository,
		?League_Repository_Interface $league_repository = null,
		?Event_Repository_Interface $event_repository = null,
		?Team_Repository_Interface $team_repository = null,
		array $teams_by_id = array()
	): Promotion_Relegation_Service {
		if ( null === $league_repository ) {
			$league_repository = $this->createStub( League_Repository_Interface::class );
			$league_repository->method( 'query_league_teams' )->willReturn( 1 );
		}
		if ( null === $event_repository ) {
			$event_repository = $this->createStub( Event_Repository_Interface::class );
		}
		if ( null === $team_repository ) {
			$team_repository = $this->createStub( Team_Repository_Interface::class );
			$team_repository->method( 'find_by_id' )->willReturnCallback(
				static function ( $id ) use ( $teams_by_id ) {
					if ( array_key_exists( (int) $id, $teams_by_id ) ) {
						return $teams_by_id[ (int) $id ];
					}
					$team          = new Team();
					$team->club_id = 7;
					return $team;
				}
			);
		}

		return new Promotion_Relegation_Service(
			$league_team_repository,
			$league_repository,
			$event_repository,
			$team_repository
		);
	}

	private function event_with_leagues( array $leagues, array $season = array() ): Event {
		$competition = $this->createMock( Competition::class );
		$competition->expects( $this->once() )
			->method( 'get_season_by_name' )
			->with( '2026' )
			->willReturn(
				$season + array(
					'teams_prom_relg'  => 2,
					'teams_per_club'   => 2,
					'max_teams'        => 8,
					'lowest_promotion' => 2,
				)
			);

		$event              = $this->getMockBuilder( Event::class )
			->disableOriginalConstructor()
			->onlyMethods( array() )
			->getMock();
		$event->id          = 42;
		$event->competition = $competition;
		$event->leagues     = $leagues;

		return $event;
	}

	public function test_returns_false_when_event_has_no_leagues(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->never() )->method( 'update_constitution' );

		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( $this->once() )
			->method( 'query_leagues' )
			->with( 42, array( 'season' => '2026' ) )
			->willReturn( array() );

		$competition = $this->createStub( Competition::class );
		$competition->method( 'get_season_by_name' )->willReturn(
			array(
				'teams_prom_relg'  => 2,
				'teams_per_club'   => 2,
				'max_teams'        => 8,
				'lowest_promotion' => 2,
			)
		);

		$event              = $this->getMockBuilder( Event::class )
			->disableOriginalConstructor()
			->onlyMethods( array() )
			->getMock();
		$event->id          = 42;
		$event->competition = $competition;
		$event->leagues     = array();

		$service = $this->service( $league_team_repository, event_repository: $event_repository );

		$this->assertFalse(
			$service->promote_and_relegate(
				$event,
				array( $this->team_row( 34, 50, 4, 1, 78 ) ),
				2026
			)
		);
	}

	public function test_returns_false_when_a_team_cannot_be_resolved(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->never() )->method( 'update_constitution' );

		$known               = new Team();
		$known->club_id      = 7;
		$team_repository     = $this->createStub( Team_Repository_Interface::class );
		$team_repository->method( 'find_by_id' )->willReturnCallback(
			static fn( $id ) => 34 === (int) $id ? $known : null
		);

		$event   = $this->event_with_leagues( array( $this->league( 50, 4 ) ) );
		$service = $this->service( $league_team_repository, team_repository: $team_repository );

		$this->assertFalse(
			$service->promote_and_relegate(
				$event,
				array(
					$this->team_row( 34, 50, 4, 1, 78 ),
					$this->team_row( 999, 50, 1, 2, 79 ),
				),
				2026
			)
		);
	}

	public function test_bottom_team_when_rank_equals_entry_count(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->once() )
			->method( 'update_constitution' )
			->with( 78, 50, 'BT' )
			->willReturn( true );

		$event   = $this->event_with_leagues(
			array( $this->league( 50, 4 ) ),
			array(
				'teams_prom_relg'  => 2,
				'teams_per_club'   => 2,
				'max_teams'        => 4,
				'lowest_promotion' => 1,
			)
		);
		$service = $this->service( $league_team_repository );

		$this->assertTrue(
			$service->promote_and_relegate(
				$event,
				array( $this->team_row( 34, 50, 4, 1, 78 ) ),
				2026
			)
		);
	}

	public function test_rt_when_club_exceeds_teams_per_club(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->once() )
			->method( 'update_constitution' )
			->with( 80, 20, 'RT' )
			->willReturn( true );

		$league_repository = $this->createStub( League_Repository_Interface::class );
		$league_repository->method( 'query_league_teams' )->willReturn( 3 );

		$event   = $this->event_with_leagues( array( $this->league( 10 ), $this->league( 20 ) ) );
		$service = $this->service( $league_team_repository, $league_repository );

		$this->assertTrue(
			$service->promote_and_relegate(
				$event,
				array(
					$this->team_row( 1, 10, 3, 3, 79 ),
					$this->team_row( 2, 10, 4, 4, 80 ),
				),
				2026
			)
		);
	}

	public function test_rb_when_full_league_team_is_in_relegation_zone(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->once() )
			->method( 'update_constitution' )
			->with( 78, 20, 'RB' )
			->willReturn( true );

		$event   = $this->event_with_leagues( array( $this->league( 10, 8 ), $this->league( 20 ) ) );
		$service = $this->service( $league_team_repository );

		$this->assertTrue(
			$service->promote_and_relegate(
				$event,
				array(
					$this->team_row( 1, 10, 8, 1, 78 ),
					$this->team_row( 2, 10, 5, 2, 79 ),
				),
				2026
			)
		);
	}

	public function test_rn_when_short_league_team_is_in_relegation_zone(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->once() )
			->method( 'update_constitution' )
			->with( 78, 20, 'R2' )
			->willReturn( true );

		$event   = $this->event_with_leagues( array( $this->league( 10, 6 ), $this->league( 20 ) ) );
		$service = $this->service( $league_team_repository );

		$this->assertTrue(
			$service->promote_and_relegate(
				$event,
				array(
					$this->team_row( 1, 10, 7, 1, 78 ),
					$this->team_row( 2, 10, 5, 2, 79 ),
				),
				2026
			)
		);
	}

	public function test_c_champion_when_rank_is_one(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->once() )
			->method( 'update_constitution' )
			->with( 78, 10, 'C' )
			->willReturn( true );

		$event   = $this->event_with_leagues( array( $this->league( 10 ) ) );
		$service = $this->service( $league_team_repository );

		$this->assertTrue(
			$service->promote_and_relegate(
				$event,
				array( $this->team_row( 1, 10, 1, 1, 78 ) ),
				2026
			)
		);
	}

	public function test_pn_promotes_into_previous_league(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$updates                = array();
		$league_team_repository->expects( $this->exactly( 2 ) )
			->method( 'update_constitution' )
			->willReturnCallback(
				static function ( int $table_id, int $new_league_id, string $status ) use ( &$updates ): bool {
					$updates[] = array( $table_id, $new_league_id, $status );
					return true;
				}
			);

		$league_repository = $this->createStub( League_Repository_Interface::class );
		$league_repository->method( 'query_league_teams' )->willReturnCallback(
			static function ( int $league_id, array $args ) {
				if ( ! empty( $args['active'] ) ) {
					return 6;
				}
				if ( isset( $args['club'] ) && 10 === $league_id ) {
					return 0;
				}
				return 1;
			}
		);

		$event   = $this->event_with_leagues( array( $this->league( 10 ), $this->league( 20 ) ) );
		$service = $this->service( $league_team_repository, $league_repository );

		$this->assertTrue(
			$service->promote_and_relegate(
				$event,
				array(
					$this->team_row( 1, 10, 1, 1, 78 ),
					$this->team_row( 2, 20, 1, 2, 79 ),
				),
				2026
			)
		);
		$this->assertSame(
			array(
				array( 78, 10, 'C' ),
				array( 79, 10, 'P1' ),
			),
			$updates
		);
	}

	public function test_w1_when_club_already_at_limit_in_previous_league(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$updates                = array();
		$league_team_repository->expects( $this->exactly( 2 ) )
			->method( 'update_constitution' )
			->willReturnCallback(
				static function ( int $table_id, int $new_league_id, string $status ) use ( &$updates ): bool {
					$updates[] = array( $table_id, $new_league_id, $status );
					return true;
				}
			);

		$league_repository = $this->createStub( League_Repository_Interface::class );
		$league_repository->method( 'query_league_teams' )->willReturnCallback(
			static function ( int $league_id, array $args ) {
				if ( ! empty( $args['active'] ) ) {
					return 6;
				}
				if ( isset( $args['club'] ) && 10 === $league_id ) {
					return 2;
				}
				return 1;
			}
		);

		$event   = $this->event_with_leagues( array( $this->league( 10 ), $this->league( 20 ) ) );
		$service = $this->service( $league_team_repository, $league_repository );

		$this->assertTrue(
			$service->promote_and_relegate(
				$event,
				array(
					$this->team_row( 1, 10, 1, 1, 78 ),
					$this->team_row( 2, 20, 1, 2, 79 ),
				),
				2026
			)
		);
		$this->assertSame(
			array(
				array( 78, 10, 'C' ),
				array( 79, 20, 'W1' ),
			),
			$updates
		);
	}

	public function test_promotion_cap_skips_when_previous_league_is_full(): void {
		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->once() )
			->method( 'update_constitution' )
			->with( 78, 10, 'C' )
			->willReturn( true );

		$league_repository = $this->createStub( League_Repository_Interface::class );
		$league_repository->method( 'query_league_teams' )->willReturnCallback(
			static function ( int $league_id, array $args ) {
				if ( ! empty( $args['active'] ) ) {
					return 8;
				}
				return 1;
			}
		);

		$event   = $this->event_with_leagues( array( $this->league( 10 ), $this->league( 20 ) ) );
		$service = $this->service( $league_team_repository, $league_repository );

		$this->assertTrue(
			$service->promote_and_relegate(
				$event,
				array(
					$this->team_row( 1, 10, 1, 1, 78 ),
					$this->team_row( 2, 20, 1, 2, 79 ),
				),
				2026
			)
		);
	}
}
