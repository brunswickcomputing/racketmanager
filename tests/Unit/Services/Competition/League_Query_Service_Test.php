<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use stdClass;

require_once __DIR__ . '/../../../../tests/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class League_Query_Service_Test extends TestCase {
	private $league_repository;
	private $fixture_repository;
	private $rubber_repository;
	private $team_repository;
	private $league_team_repository;
	private $club_repository;
	private $player_repository;
	private League_Query_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$this->league_repository      = $this->createMock( League_Repository_Interface::class );
		$this->fixture_repository     = $this->createMock( Fixture_Repository_Interface::class );
		$this->rubber_repository      = $this->createMock( Rubber_Repository_Interface::class );
		$this->team_repository        = $this->createMock( Team_Repository_Interface::class );
		$this->league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$this->club_repository        = $this->createMock( Club_Repository_Interface::class );
		$this->player_repository      = $this->createMock( Player_Repository_Interface::class );

		$this->service = new League_Query_Service(
			$this->league_repository,
			fn() => $this->fixture_repository,
			fn() => $this->rubber_repository,
			fn() => $this->team_repository,
			fn() => $this->league_team_repository,
			fn() => $this->club_repository,
			fn() => $this->player_repository
		);
	}

	public function test_get_repositories_resolves_closures_and_instances(): void {
		$this->assertSame( $this->league_repository, $this->service->get_league_repository() );
		$this->assertSame( $this->fixture_repository, $this->service->get_fixture_repository() );
		$this->assertSame( $this->rubber_repository, $this->service->get_rubber_repository() );
		$this->assertSame( $this->team_repository, $this->service->get_team_repository() );
		$this->assertSame( $this->league_team_repository, $this->service->get_league_team_repository() );
		$this->assertSame( $this->club_repository, $this->service->get_club_repository() );
		$this->assertSame( $this->player_repository, $this->service->get_player_repository() );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function missing_repositories(): array {
		return array(
			'fixture'     => array( 'fixture_repository' ),
			'rubber'      => array( 'rubber_repository' ),
			'team'        => array( 'team_repository' ),
			'league_team' => array( 'league_team_repository' ),
			'club'        => array( 'club_repository' ),
			'player'      => array( 'player_repository' ),
		);
	}

	#[DataProvider( 'missing_repositories' )]
	public function test_lazy_repository_resolving_to_null_fails_loudly( string $repository_name ): void {
		$service = new League_Query_Service(
			$this->createStub( League_Repository_Interface::class ),
			...[ $repository_name => static fn() => null ]
		);
		$getter = 'get_' . $repository_name;
		$this->expectException( \LogicException::class );
		$service->$getter();
	}

	#[DataProvider( 'missing_repositories' )]
	public function test_unconfigured_repository_fails_loudly( string $repository_name ): void {
		$service = new League_Query_Service( $this->createStub( League_Repository_Interface::class ) );
		$getter = 'get_' . $repository_name;
		$this->expectException( \LogicException::class );
		$service->$getter();
	}

	public function test_get_league_teams_passes_repositories_without_globals(): void {
		unset( $GLOBALS['racketmanager'] );

		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );

		$team_row                 = new stdClass();
		$team_row->table_id       = 10;
		$team_row->team_id        = 100;
		$team_row->title          = 'Team A';
		$team_row->custom         = array();
		$team_row->roster         = array();
		$team_row->points_plus    = 10;
		$team_row->points_minus   = 5;
		$team_row->points_2_plus  = 2;
		$team_row->points_2_minus = 1;
		$team_row->players        = array();

		$league_team_mock                 = $this->createStub( League_Team::class );
		$league_team_mock->custom         = array();
		$league_team_mock->roster         = array();
		$league_team_mock->title          = 'Team A';
		$league_team_mock->points_plus    = 10;
		$league_team_mock->points_minus   = 5;
		$league_team_mock->points_2_plus  = 2;
		$league_team_mock->points_2_minus = 1;
		$league_team_mock->players        = array();

		$this->league_team_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 10 )
			->willReturn( $league_team_mock );

		$this->league_repository->expects( $this->once() )
			->method( 'query_league_teams' )
			->with( 42, $this->callback( 'is_array' ), false )
			->willReturn( array( $team_row ) );

		$result = $this->service->get_league_teams( $league );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
	}

	public function test_get_team_dtls_enriches_club_and_players_via_repositories_without_globals(): void {
		unset( $GLOBALS['racketmanager'] );

		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );

		$team_details = (object) array(
			'id'             => 10,
			'title'          => 'Team A',
			'captain'        => null,
			'club_id'        => 133,
			'stadium'        => '',
			'roster'         => array( 55 ),
			'points_plus'    => 10,
			'points_minus'   => 5,
			'points_2_plus'  => 2,
			'points_2_minus' => 1,
			'team_type'      => 'P',
			'league_status'  => '',
		);

		$club_mock   = $this->createStub( Club::class );
		$player_mock = $this->createMock( Player::class );
		$player_mock->method( 'get_fullname' )->willReturn( 'John Doe' );
		$player_mock->method( 'get_id' )->willReturn( 55 );

		$this->league_repository->expects( $this->once() )
			->method( 'get_team_details' )
			->with( 42, 10, 'default' )
			->willReturn( $team_details );

		$this->club_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 133 )
			->willReturn( $club_mock );

		$this->player_repository->expects( $this->once() )
			->method( 'find' )
			->with( 55 )
			->willReturn( $player_mock );

		$result = $this->service->get_team_dtls( $league, 10 );
		$this->assertSame( $team_details, $result );
		$this->assertSame( $club_mock, $result->club );
		$this->assertSame( 'John Doe', $result->player[1] );
		$this->assertSame( 55, $result->player_id[1] );
	}

	public function test_get_team_dtls_returns_bye_team_without_querying_repository(): void {
		$league = new League( (object) array( 'id' => 42, 'title' => 'Premier Division' ) );
		$this->league_repository->expects( $this->never() )->method( 'get_team_details' );

		$team = $this->service->get_team_dtls( $league, -1 );
		$this->assertIsObject( $team );
		$this->assertSame( -1, $team->id );
		$this->assertSame( 'Bye', $team->title );
		$this->assertSame( '', $team->captain );
	}

	public function test_get_team_dtls_returns_false_when_team_not_found(): void {
		$league = new League( (object) array( 'id' => 42, 'title' => 'Premier Division' ) );
		$this->league_repository->expects( $this->once() )
			->method( 'get_team_details' )
			->with( 42, 99, 'default' )
			->willReturn( null );

		$this->assertFalse( $this->service->get_team_dtls( $league, 99 ) );
	}

	public function test_get_team_dtls_generates_team_name_when_title_contains_underscore(): void {
		$league = new League( (object) array( 'id' => 42, 'title' => 'Premier Division' ) );
		$team_details = (object) array(
			'id'             => 10,
			'title'          => 'Leigh_Men_A',
			'captain'        => null,
			'club_id'        => null,
			'stadium'        => '',
			'roster'         => null,
			'points_plus'    => 0,
			'points_minus'   => 0,
			'points_2_plus'  => 0,
			'points_2_minus' => 0,
			'team_type'      => 'S',
			'league_status'  => 'W',
		);
		$this->league_repository->expects( $this->once() )
			->method( 'get_team_details' )
			->with( 42, 10, 'default' )
			->willReturn( $team_details );

		$result = $this->service->get_team_dtls( $league, 10 );
		$this->assertIsObject( $result );
		$this->assertSame( 'Leigh_Men_A', $result->title );
		$this->assertSame( 0, $result->captain_id );
		$this->assertSame( '', $result->captain );
		$this->assertTrue( $result->is_withdrawn );
	}

	public function test_get_league_team_returns_cached_team_without_repository_lookup(): void {
		$league       = new League( (object) array( 'id' => 42, 'title' => 'Premier Division' ) );
		$cached       = (object) array( 'id' => 7, 'title' => 'Cached Team' );
		$league->teams = array( $cached );
		$league->team_index = array( 7 => 0 );

		$this->league_repository->expects( $this->never() )->method( 'get_team_details' );

		$this->assertSame( $cached, $this->service->get_league_team( $league, 7 ) );
	}

	public function test_get_league_team_loads_uncached_team_through_query_service_repositories(): void {
		$league                 = new League( (object) array( 'id' => 42, 'title' => 'Premier Division' ) );
		$league->current_season = array( 'name' => '2026' );
		$team_details            = (object) array(
			'id'             => 7,
			'title'          => 'Team A',
			'captain'        => null,
			'club_id'        => null,
			'stadium'        => '',
			'roster'         => null,
			'points_plus'    => 0,
			'points_minus'   => 0,
			'points_2_plus'  => 0,
			'points_2_minus' => 0,
			'team_type'      => 'S',
		);

		$this->league_repository->expects( $this->once() )
			->method( 'get_team_details' )
			->with( 42, 7, '2026' )
			->willReturn( $team_details );

		$result = $this->service->get_league_team( $league, 7 );

		$this->assertSame( $team_details, $result );
		$this->assertSame( 'Team A', $result->title );
		$this->assertSame( 0, $result->captain_id );
	}

	public function test_get_matches_passes_repositories(): void {
		$league_row          = new stdClass();
		$league_row->id      = 42;
		$league_row->title   = 'Premier Division';
		$league_row->sport   = 'tennis';
		$league_row->seasons = array( '2026' => array( 'name' => '2026' ) );
		$league              = new League( $league_row );

		$fixture_mock = $this->createStub( Fixture::class );
		$this->fixture_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 100 )
			->willReturn( $fixture_mock );

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with( 42, $this->callback( 'is_array' ) )
			->willReturn( array( (object) array( 'id' => 100 ) ) );

		$result = $this->service->get_matches( $league, array( 'season' => '2026', 'limit' => 5 ) );
		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( $fixture_mock, $result[0] );
	}

	public function test_get_players_enriches_players_via_player_repository_without_globals(): void {
		unset( $GLOBALS['racketmanager'] );

		$league_row          = new stdClass();
		$league_row->id      = 42;
		$league_row->title   = 'Premier Division';
		$league_row->sport   = 'tennis';
		$league_row->seasons = array( '2026' => array( 'name' => '2026' ) );
		$league              = new League( $league_row );

		$player_mock                = $this->createMock( Player::class );
		$player_mock->system_record = false;
		$player_mock->method( 'get_fullname' )->willReturn( 'Alice Smith' );

		$league->current_season = array( 'name' => '2026' );

		$this->league_repository->expects( $this->once() )
			->method( 'query_players' )
			->with( 42, self::default_player_args( '2026' ), false )
			->willReturn( array( (object) array( 'player_id' => 77 ) ) );

		$this->player_repository->expects( $this->once() )
			->method( 'find' )
			->with( 77 )
			->willReturn( $player_mock );

		$result = $this->service->get_players( $league );
		$this->assertSame( array( $player_mock ), $result );
		$this->assertSame( array( $player_mock ), $league->players );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function default_player_args( string $season ): array {
		return array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => $season,
			'orderby' => array(),
			'club'    => false,
			'team'    => false,
			'count'   => false,
			'group'   => false,
		);
	}

	private function player_league( int $id = 42 ): League {
		$league_row        = new stdClass();
		$league_row->id    = $id;
		$league_row->title = 'Premier Division';
		$league_row->sport = 'tennis';

		return new League( $league_row );
	}

	public function test_get_players_explicit_season_overrides_current_season(): void {
		$league                 = $this->player_league();
		$league->current_season = array( 'name' => '2026' );

		$this->league_repository->expects( $this->once() )
			->method( 'query_players' )
			->with( 42, self::default_player_args( '2024' ), false )
			->willReturn( array() );

		$this->assertSame( array(), $this->service->get_players( $league, array( 'season' => '2024' ) ) );
		$this->assertSame( array(), $league->players );
	}

	public function test_get_players_merges_caller_args_over_defaults(): void {
		$league                 = $this->player_league();
		$league->current_season = array( 'name' => '2026' );
		$expected               = array_merge( self::default_player_args( '2026' ), array( 'limit' => 5, 'team' => 12 ) );

		$this->league_repository->expects( $this->once() )
			->method( 'query_players' )
			->with( 42, $expected, false )
			->willReturn( array() );

		$this->service->get_players( $league, array( 'limit' => 5, 'team' => 12 ) );
	}

	public function test_get_players_count_returns_repository_total_without_hydrating(): void {
		$league                 = $this->player_league();
		$league->current_season = array( 'name' => '2026' );
		$expected               = array_merge( self::default_player_args( '2026' ), array( 'count' => true ) );

		$this->league_repository->expects( $this->once() )
			->method( 'query_players' )
			->with( 42, $expected, true )
			->willReturn( 9 );
		$this->player_repository->expects( $this->never() )->method( 'find' );

		$this->assertSame( 9, $this->service->get_players( $league, array( 'count' => true ) ) );
	}

	public function test_get_players_without_league_id_returns_empty_without_querying(): void {
		$league     = $this->player_league();
		$league->id = 0;

		$this->league_repository->expects( $this->never() )->method( 'query_players' );
		$this->player_repository->expects( $this->never() )->method( 'find' );

		$this->assertSame( array(), $this->service->get_players( $league ) );
		$this->assertSame( 0, $this->service->get_players( $league, array( 'count' => true ) ) );
	}

	public function test_get_players_skips_unresolved_and_system_players_and_keeps_order_ungrouped(): void {
		$league                 = $this->player_league();
		$league->current_season = array( 'name' => '2026' );

		$bob                = $this->createStub( Player::class );
		$bob->system_record = false;
		$sys                = $this->createStub( Player::class );
		$sys->system_record = true;
		$amy                = $this->createStub( Player::class );
		$amy->system_record = false;

		$this->league_repository->method( 'query_players' )->willReturn(
			array(
				(object) array( 'player_id' => 1 ),
				(object) array( 'player_id' => 2 ),
				(object) array( 'player_id' => 3 ),
				(object) array( 'player_id' => 4 ),
			)
		);
		$this->player_repository->method( 'find' )->willReturnMap(
			array(
				array( 1, $bob ),
				array( 2, $sys ),
				array( 3, null ),
				array( 4, $amy ),
			)
		);

		$result = $this->service->get_players( $league );

		$this->assertSame( array( $bob, $amy ), $result );
		$this->assertSame( array( $bob, $amy ), $league->players );
	}

	public function test_add_team_persists_through_repository_without_calling_entity_method(): void {
		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = $this->getMockBuilder( League::class )
			->setConstructorArgs( array( $league_row ) )
			->onlyMethods( array( 'add_team' ) )
			->getMock();
		$league->expects( $this->never() )->method( 'add_team' );

		$this->league_repository->expects( $this->once() )
			->method( 'add_team_to_league' )
			->with( 42, 10, '2026', '1', 'A', 0 )
			->willReturn( 1 );

		$result = $this->service->add_team( $league, 10, '2026', '1', 'A', 0 );
		$this->assertSame( 1, $result );
	}

	public function test_add_team_resolves_string_team_name_via_team_repository(): void {
		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );

		$this->team_repository->expects( $this->once() )
			->method( 'find_id_by_title' )
			->with( 'Westcliff Lawn Mixed 1' )
			->willReturn( 100 );

		$this->league_repository->expects( $this->once() )
			->method( 'add_team_to_league' )
			->with( 42, 100, '2026', '1', 'A', 1 )
			->willReturn( 101 );

		$result = $this->service->add_team( $league, 'Westcliff Lawn Mixed 1', '2026', '1', 'A', 1 );
		$this->assertSame( 101, $result );
	}

	public function test_add_team_returns_false_for_unknown_team_title(): void {
		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );

		$this->team_repository->expects( $this->once() )
			->method( 'find_id_by_title' )
			->with( 'Nonexistent Team' )
			->willReturn( 0 );

		$this->league_repository->expects( $this->never() )->method( 'add_team_to_league' );

		$result = $this->service->add_team( $league, 'Nonexistent Team', '2026', '1', 'A', 1 );
		$this->assertFalse( $result );
	}

	public function test_add_team_returns_false_for_non_positive_team_id(): void {
		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );

		$this->league_repository->expects( $this->never() )->method( 'add_team_to_league' );

		$this->assertFalse( $this->service->add_team( $league, 0, '2026' ) );
		$this->assertFalse( $this->service->add_team( $league, -10, '2026' ) );
	}

	public function test_add_team_does_not_persist_when_league_has_no_id(): void {
		$league = new League( new stdClass() );
		$this->league_repository->expects( $this->never() )->method( 'add_team_to_league' );

		$this->assertFalse( $this->service->add_team( $league, 10, '2026' ) );
	}

	public function test_update_mutates_entity_then_persists_through_repository(): void {
		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Old Title';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );
		$this->assertSame( 2, ( new \ReflectionMethod( League::class, 'update' ) )->getNumberOfParameters() );

		$this->league_repository->expects( $this->once() )
			->method( 'update_title_and_sequence' )
			->with( 42, 'New Title', '2' )
			->willReturnCallback(
				function ( int $id, string $title, ?string $sequence ) use ( $league ): bool {
					$this->assertSame( $title, $league->title );
					$this->assertSame( $title, $league->name );
					$this->assertSame( $sequence, $league->sequence );
					return true;
				}
			);

		$this->service->update( $league, 'New Title', '2' );
		$this->assertSame( 'New Title', $league->title );
	}

	public function test_update_mutates_entity_without_persisting_when_it_has_no_id(): void {
		$league = new League( (object) array( 'title' => 'Old Title' ) );
		$this->league_repository->expects( $this->never() )->method( 'update_title_and_sequence' );

		$this->service->update( $league, 'New Title', '2' );
		$this->assertSame( 'New Title', $league->title );
	}

	public function test_get_rank_passes_league_repository(): void {
		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );

		$this->league_repository->expects( $this->once() )
			->method( 'get_rank' )
			->with( 42, '2026', 10 )
			->willReturn( 3 );

		$this->assertSame( 3, $this->service->get_rank( $league, 10, '2026' ) );
	}

	public function test_get_status_passes_league_repository(): void {
		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );

		$this->league_repository->expects( $this->once() )
			->method( 'get_status' )
			->with( 42, '2026', 10 )
			->willReturn( 'C' );

		$this->assertSame( 'C', $this->service->get_status( $league, 10, '2026' ) );
	}

	private function league_for_query_service(): League {
		$league = new League( (object) array(
			'id'      => 42,
			'title'   => 'Premier Division',
			'sport'   => 'tennis',
			'seasons' => array( '2026' => array( 'name' => '2026' ) ),
		) );
		$league->current_season = array( 'name' => '2026' );
		return $league;
	}

	private static function has_expected_query_args( array $actual, array $expected ): bool {
		foreach ( $expected as $key => $value ) {
			if ( ! array_key_exists( $key, $actual ) || $actual[ $key ] !== $value ) {
				return false;
			}
		}
		return true;
	}

	public function test_get_num_teams_passes_league_repository(): void {
		$league = $this->league_for_query_service();

		$this->league_repository->expects( $this->once() )
			->method( 'query_league_teams' )
			->with(
				42,
				array(
					'count'            => true,
					'season'           => '2026',
					'reset_query_args' => true,
					'club'             => false,
					'active'           => true,
				),
				true
			)
			->willReturn( 8 );

		$this->assertSame( 8, $this->service->get_num_teams( $league, 'C', false ) );
	}

	public function test_get_num_teams_latest_disables_cache_without_active_filter(): void {
		$league = $this->league_for_query_service();

		$this->league_repository->expects( $this->once() )
			->method( 'query_league_teams' )
			->with(
				42,
				array(
					'count'            => true,
					'season'           => '2026',
					'reset_query_args' => true,
					'club'             => false,
					'cache'            => false,
				),
				true
			)
			->willReturn( 9 );

		$this->assertSame( 9, $this->service->get_num_teams( $league, null, true ) );
	}

	public function test_get_num_teams_plain_count_has_no_active_or_cache_filter(): void {
		$league = $this->league_for_query_service();

		$this->league_repository->expects( $this->once() )
			->method( 'query_league_teams' )
			->with(
				42,
				array(
					'count'            => true,
					'season'           => '2026',
					'reset_query_args' => true,
					'club'             => false,
				),
				true
			)
			->willReturn( 7 );

		$this->assertSame( 7, $this->service->get_num_teams( $league ) );
	}

	public function test_set_num_teams_passes_league_repository(): void {
		$league = $this->league_for_query_service();

		$this->league_repository->expects( $this->once() )
			->method( 'query_league_teams' )
			->with(
				42,
				array(
					'count'            => true,
					'season'           => '2026',
					'reset_query_args' => true,
					'club'             => false,
				),
				true
			)
			->willReturn( 6 );

		$this->service->set_num_teams( $league, true );
		$this->assertSame( 6, $league->num_teams_total );
	}

	public function test_set_num_teams_per_page_copies_total_without_repository_read(): void {
		$league                  = $this->league_for_query_service();
		$league->num_teams_total = 6;
		$this->league_repository->expects( $this->never() )->method( 'query_league_teams' );

		$this->service->set_num_teams( $league );

		$this->assertSame( 6, $league->num_teams );
		$this->assertSame( 1, $league->current_page_teams );
		$this->assertIsString( $league->pagination_teams );
	}

	public function test_set_num_matches_per_page_counts_with_limit_zero(): void {
		$league = $this->league_for_query_service();

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with( 42, $this->callback( static function ( array $args ): bool {
				$expected = array(
					'count'            => true,
					'season'           => null,
					'limit'            => 0,
					'match_day'        => 0,
					'reset_query_args' => true,
					'current_season'   => '2026',
					'offset'           => 0,
					'is_championship'  => false,
				);
				return self::has_expected_query_args( $args, $expected );
			}), true )
			->willReturn( 12 );

		$this->service->set_num_matches( $league, false );
		$this->assertSame( 12, $league->num_matches );
		$this->assertSame( 1, $league->current_page_matches );
		$this->assertIsString( $league->pagination_matches );
	}

	public function test_set_num_matches_total_counts_without_overriding_entity_limit(): void {
		$league = $this->league_for_query_service();

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with( 42, $this->callback( static function ( array $args ): bool {
				$expected = array(
					'count'            => true,
					'season'           => null,
					'limit'            => 30,
					'match_day'        => 0,
					'reset_query_args' => true,
					'current_season'   => '2026',
					'offset'           => 0,
					'is_championship'  => false,
				);
				return self::has_expected_query_args( $args, $expected );
			}), true )
			->willReturn( 30 );

		$this->service->set_num_matches( $league, true );

		$this->assertSame( 30, $league->num_matches_total );
	}

	public function test_get_crosstable_field_passes_league_repository(): void {
		$league = $this->league_for_query_service();

		$this->league_repository->expects( $this->exactly( 2 ) )
			->method( 'query_matches' )
			->with( 42, $this->callback( static function ( array $args ): bool {
				$expected = array(
					array( 'home_team' => 1, 'away_team' => 2, 'match_day' => -1, 'limit' => 0, 'reset_query_args' => true ),
					array( 'home_team' => 2, 'away_team' => 1, 'match_day' => -1, 'limit' => 0, 'reset_query_args' => true ),
				);
				foreach ( $expected as $query_args ) {
					if ( self::has_expected_query_args( $args, $query_args ) ) {
						return true;
					}
				}
				return false;
			}) )
			->willReturn( array() );

		$result = $this->service->get_crosstable_field( $league, 1, 2 );
		$this->assertSame( '&nbsp;', $result );
	}

	public function test_get_crosstable_field_same_team_skips_repository(): void {
		$league = $this->league_for_query_service();
		$this->league_repository->expects( $this->never() )->method( 'query_matches' );

		$this->assertSame( '&nbsp;', $this->service->get_crosstable_field( $league, 1, 1 ) );
	}

	public function test_get_crosstable_field_shows_all_home_away_scores(): void {
		$league = $this->league_for_query_service();
		$event  = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
		$event->current_season = array( 'name' => '2026', 'home_away' => true );
		$league->event = $event;
		$match_one = (object) array( 'id' => 11 );
		$match_two = (object) array( 'id' => 12 );
		$service   = new League_Query_Service( $this->league_repository );

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with( 42, $this->callback( static function ( array $args ): bool {
				$expected = array(
					'home_team'        => 1,
					'away_team'        => 2,
					'match_day'        => -1,
					'limit'            => 0,
					'reset_query_args' => true,
				);
				return self::has_expected_query_args( $args, $expected );
			}) )
			->willReturn( array( $match_one, $match_two ) );

		$this->assertSame(
			"[score team='1' opponent='2' home_away='1' id='11']<br>[score team='1' opponent='2' home_away='1' id='12']<br>",
			$service->get_crosstable_field( $league, 1, 2 )
		);
	}

	public function test_get_crosstable_field_shows_forward_fixture_score(): void {
		$league = $this->league_for_query_service();
		$match  = (object) array( 'id' => 20 );
		$service = new League_Query_Service( $this->league_repository );

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with( 42, $this->callback( static function ( array $args ): bool {
				$expected = array(
					'home_team'        => 1,
					'away_team'        => 2,
					'match_day'        => -1,
					'limit'            => 0,
					'reset_query_args' => true,
				);
				return self::has_expected_query_args( $args, $expected );
			}) )
			->willReturn( array( $match ) );

		$this->assertSame( "[score team='1' opponent='2' home_away='' id='20']", $service->get_crosstable_field( $league, 1, 2 ) );
	}

	public function test_get_crosstable_field_uses_reverse_fixture_when_home_lookup_is_empty(): void {
		$league = $this->league_for_query_service();
		$match  = (object) array( 'id' => 21 );
		$calls  = 0;
		$service = new League_Query_Service( $this->league_repository );

		$this->league_repository->expects( $this->exactly( 2 ) )
			->method( 'query_matches' )
			->willReturnCallback( static function ( int $league_id, array $args ) use ( &$calls, $match ): array {
				++$calls;
				$expected_home = 1 === $calls ? 1 : 2;
				$expected_away = 1 === $calls ? 2 : 1;
				self::assertSame( 42, $league_id );
				self::assertSame( $expected_home, $args['home_team'] );
				self::assertSame( $expected_away, $args['away_team'] );
				self::assertSame( -1, $args['match_day'] );
				self::assertSame( 0, $args['limit'] );
				self::assertTrue( $args['reset_query_args'] );
				return 1 === $calls ? array() : array( $match );
			} );

		$this->assertSame( "[score team='1' opponent='2' home_away='' id='21']", $service->get_crosstable_field( $league, 1, 2 ) );
	}

	public function test_set_season_updates_season_in_memory(): void {
		$league_row          = new stdClass();
		$league_row->id      = 42;
		$league_row->title   = 'Premier Division';
		$league_row->sport   = 'tennis';
		$league_row->seasons = array( '2026' => array( 'name' => '2026' ) );
		$league              = new League( $league_row );

		$this->league_repository->expects( $this->once() )
			->method( 'query_league_teams' )
			->with( 42, $this->callback( 'is_array' ), true )
			->willReturn( 6 );

		$this->service->set_season( $league, '2026' );
		$this->assertSame( '2026', $league->get_season() );
		$this->assertSame( 6, (int) $league->num_teams_total );
	}

	public function test_set_season_initializes_championship_in_championship_mode(): void {
		$league_row          = new stdClass();
		$league_row->id      = 42;
		$league_row->title   = 'Championship Cup';
		$league_row->sport   = 'tennis';
		$league_row->settings = array( 'num_advance' => 2 );
		$league_row->seasons = array( '2026' => array( 'name' => '2026' ) );
		$event               = new \Racketmanager\Domain\Competition\Event(
			(object) array( 'id' => 93, 'name' => 'Event', 'seasons' => $league_row->seasons ),
			\Racketmanager\Tests\Helpers\Competition_Test_Factory::create()
		);
		$league              = new League( $league_row, $event );
		$league->mode        = 'championship';

		$this->league_repository->expects( $this->once() )
			->method( 'query_league_teams' )
			->with( 42, $this->callback( 'is_array' ), true )
			->willReturn( 8 );

		$this->service->set_season( $league, '2026', false, 'final' );
		$this->assertSame( '2026', $league->get_season() );
		$this->assertSame( 8, (int) $league->num_teams_total );
		$this->assertNotNull( $league->championship );
		$this->assertSame( 'final', $league->championship->get_current_final_key() );
	}

	public function test_set_match_day_persists_relative_resolution_in_league_state(): void {
		$league_row         = new stdClass();
		$league_row->id     = 42;
		$league_row->title  = 'Premier Division';
		$league_row->sport  = 'tennis';
		$league             = new League( $league_row );

		$this->league_repository->expects( $this->once() )
			->method( 'get_match_day' )
			->with( 42, $league->current_season['name'] ?? '', 'last' )
			->willReturn( 3 );

		$this->service->set_match_day( $league, 'last' );
		$this->assertSame( 3, $league->match_day );
	}

	public function test_set_rounds_persists_updated_seasons(): void {
		$league_row          = new stdClass();
		$league_row->id      = 42;
		$league_row->title   = 'Premier Division';
		$league_row->sport   = 'tennis';
		$league_row->seasons = array( '2026' => array( 'name' => '2026' ) );
		$league              = new League( $league_row );

		$this->league_repository->expects( $this->once() )
			->method( 'update_seasons' )
			->with( 42, $this->callback( fn( $seasons ) => isset( $seasons['2026']['rounds'] ) ) )
			->willReturn( true );

		$this->service->set_rounds( $league, '2026', array( 'Final' ) );
		$this->assertSame( array( 'Final' ), $league->seasons['2026']['rounds'] );
	}

	public function test_get_league_teams_enriches_from_persisted_get_details_flag(): void {
		$league_row        = new stdClass();
		$league_row->id    = 42;
		$league_row->title = 'Premier Division';
		$league_row->sport = 'tennis';
		$league            = new League( $league_row );
		$league->set_team_query_arg( 'get_details', true );

		$team_row           = new stdClass();
		$team_row->table_id = 10;
		$team_row->id       = 10;

		$league_team = new League_Team(
			(object) array(
				'id'             => 10,
				'table_id'       => 10,
				'title'          => 'Team A',
				'points_plus'    => 0,
				'points_minus'   => 0,
				'points_2_plus'  => 0,
				'points_2_minus' => 0,
				'custom'         => '',
				'roster'         => '',
			)
		);

		$this->league_team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league_team );
		$this->league_repository->method( 'query_league_teams' )->willReturnCallback(
			static fn( int $league_id, array $args, bool $count = false ): array|int => $count ? 1 : array( $team_row )
		);
		$this->league_repository->expects( $this->once() )
			->method( 'get_team_details' )
			->with( 42, 10, $this->anything() )
			->willReturn(
				(object) array(
					'id'            => 10,
					'title'         => 'Team A',
					'captain'       => null,
					'club_id'       => 0,
					'stadium'       => '',
					'roster'        => array(),
					'points_plus'   => 0,
					'points_minus'  => 0,
					'points_2_plus' => 0,
					'points_2_minus'=> 0,
					'team_type'     => 'T',
					'league_status' => '',
					'match_day'     => 'Wednesday',
					'contactemail'  => 'cap@example.test',
				)
			);

		$teams = $this->service->get_league_teams( $league );

		$this->assertIsArray( $teams );
		$this->assertSame( 'Wednesday', $teams[0]->match_day );
	}

	public function test_get_league_teams_skips_detail_lookup_when_get_details_is_absent(): void {
		$league_row        = new stdClass();
		$league_row->id    = 42;
		$league_row->title = 'Premier Division';
		$league_row->sport = 'tennis';
		$league            = new League( $league_row );

		$team_row           = new stdClass();
		$team_row->table_id = 10;
		$team_row->id       = 10;

		$league_team = new League_Team(
			(object) array(
				'id'             => 10,
				'table_id'       => 10,
				'title'          => 'Team A',
				'points_plus'    => 0,
				'points_minus'   => 0,
				'points_2_plus'  => 0,
				'points_2_minus' => 0,
				'custom'         => '',
				'roster'         => '',
			)
		);

		$this->league_team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league_team );
		$this->league_repository->method( 'query_league_teams' )->willReturnCallback(
			static fn( int $league_id, array $args, bool $count = false ): array|int => $count ? 1 : array( $team_row )
		);
		$this->league_repository->expects( $this->never() )->method( 'get_team_details' );

		$teams = $this->service->get_league_teams( $league );

		$this->assertIsArray( $teams );
		$this->assertCount( 1, $teams );
		$this->assertFalse( isset( $teams[0]->contactemail ) );
	}

  public function test_duck_typed_non_league_object_only_delegates_supported_reads(): void {
		$duck = new class {
			public bool $called_teams = false;
			public bool $called_matches = false;
			public bool $called_team_dtls = false;

			public function get_league_teams( array $args = array() ): array {
				$this->called_teams = true;
				return array( (object) array( 'id' => 99 ) );
			}

			public function get_matches( array $args = array() ): array {
				$this->called_matches = true;
				return array();
			}

			public function get_team_dtls( int $team_id, ?string $season = null ): object {
				$this->called_team_dtls = true;
				return (object) array( 'id' => $team_id );
			}
		};

    $this->league_repository->expects( $this->never() )->method( 'query_league_teams' );
    $teams = $this->service->get_league_teams( $duck );
    $this->assertSame( array(), $teams );
    $this->assertFalse( $duck->called_teams );
    $this->assertSame( 0, $this->service->get_league_teams( $duck, array( 'count' => true ) ) );

		$matches = $this->service->get_matches( $duck );
		$this->assertTrue( $duck->called_matches );
		$this->assertSame( array(), $matches );

		$dtls = $this->service->get_team_dtls( $duck, 7 );
		$this->assertTrue( $duck->called_team_dtls );
    $this->assertSame( 7, $dtls->id );
  }

  public function test_get_league_teams_returns_empty_results_when_league_has_no_id(): void {
    $this->league_repository->expects( $this->never() )->method( 'query_league_teams' );
    $league = new League( new stdClass() );

    $this->assertSame( array(), $this->service->get_league_teams( $league ) );
    $this->assertSame( 0, $this->service->get_league_teams( $league, array( 'count' => true ) ) );
  }
}
