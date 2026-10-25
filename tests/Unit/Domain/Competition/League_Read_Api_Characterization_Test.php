<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Util\Util_Lookup;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

/**
 * Characterises the public League read API (get_league_teams,
 * get_matches) as served by League_Query_Service against real League entities.
 * get_players, get_league_team, and get_team_dtls are also covered on
 * League_Query_Service.
 */
#[AllowMockObjectsWithoutExpectations]
final class League_Read_Api_Characterization_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		unset( $GLOBALS['racketmanager'] );
		$_GET = array();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	private function league( array $overrides = array() ): League {
		$league                 = new League( (object) array_merge( array(
			'id'      => 42,
			'title'   => 'Division 1',
			'sport'   => 'tennis',
			'seasons' => array( '2026' => array( 'name' => '2026' ) ),
		), $overrides ) );
		$league->current_season = array( 'name' => '2026' );
		return $league;
	}

	public function test_calls_with_empty_league_id_return_empty_results_like_unconfigured_head(): void {
		$league  = $this->league( array( 'id' => 0 ) );
		$service = new League_Query_Service( $this->createStub( League_Repository_Interface::class ) );

		$this->assertSame( array(), $service->get_matches( $league, array() ) );
		$this->assertSame( 0, $service->get_matches( $league, array( 'count' => true ) ) );
		$this->assertSame( array(), $league->matches );
		$this->assertSame( 0, $league->num_matches );
	}

	public function test_get_team_dtls_returns_bye_team_without_repository(): void {
		$service = new League_Query_Service( $this->createStub( League_Repository_Interface::class ) );
		$team    = $service->get_team_dtls( $this->league(), -1 );

		$this->assertSame( -1, $team->id );
		$this->assertSame( 'Bye', $team->title );
		$this->assertSame( '', $team->captain );
		$this->assertSame( '', $team->roster );
	}

	public function test_get_matches_returns_empty_without_querying_for_unknown_season(): void {
		$league     = $this->league();
		$repository = $this->createMock( League_Repository_Interface::class );
		$repository->expects( $this->never() )->method( 'query_matches' );
		$service    = new League_Query_Service( $repository );

		$this->assertSame( array(), $service->get_matches( $league, array( 'season' => '1999' ) ) );
	}

	public function test_get_matches_maps_status_hydrates_fixtures_and_stores_matches(): void {
		$league     = $this->league();
		$repository = $this->createMock( League_Repository_Interface::class );
		$captured   = null;
		$repository->expects( $this->once() )->method( 'query_matches' )->willReturnCallback(
			static function ( int $id, array $args, bool $count ) use ( &$captured ): array {
				$captured = $args;
				return array( (object) array( 'id' => 100 ), (object) array( 'id' => 101 ) );
			}
		);
		$first  = $this->createStub( Fixture::class );
		$second = $this->createStub( Fixture::class );
		$first->method( 'get_id' )->willReturn( 100 );
		$second->method( 'get_id' )->willReturn( 101 );
		$fixtures = $this->createMock( Fixture_Repository_Interface::class );
		$fixtures->method( 'find_by_id' )->willReturnMap( array( array( 100, $first ), array( 101, $second ) ) );
		$rubbers = $this->createMock( Rubber_Repository_Interface::class );
		$rubbers->expects( $this->exactly( 2 ) )->method( 'find_by_fixture_id' )->willReturn( array() );

		$service = new League_Query_Service( $repository, $fixtures, $rubbers );

		$matches = $service->get_matches(
			$league,
			array( 'season' => '2026', 'player' => 9, 'status' => array( 'status_code' => 'cancelled', 'compare' => '!=' ), 'limit' => false )
		);

		$this->assertSame( array( $first, $second ), $matches );
		$this->assertSame( array( $first, $second ), $league->matches );
		$this->assertSame( Util_Lookup::get_match_status_code( 'cancelled' ), $captured['status_value'] );
		$this->assertSame( '!=', $captured['status_compare'] );
		$this->assertSame( '2026', $captured['current_season'] );
		$this->assertSame( 0, $captured['offset'] );
		$this->assertFalse( $captured['is_championship'] );
	}

	public function test_get_matches_with_limit_one_returns_single_fixture(): void {
		$league     = $this->league();
		$repository = $this->createStub( League_Repository_Interface::class );
		$repository->method( 'query_matches' )->willReturn( array( (object) array( 'id' => 100 ) ) );
		$fixture  = $this->createStub( Fixture::class );
		$fixtures = $this->createStub( Fixture_Repository_Interface::class );
		$fixtures->method( 'find_by_id' )->willReturn( $fixture );

		$service = new League_Query_Service( $repository, $fixtures );

		$this->assertSame( $fixture, $service->get_matches( $league, array( 'limit' => 1 ) ) );
	}

	public function test_get_matches_count_sets_num_matches_and_clears_count_arg(): void {
		$league     = $this->league();
		$repository = $this->createStub( League_Repository_Interface::class );
		$repository->method( 'query_matches' )->willReturn( 12 );

		$service = new League_Query_Service( $repository );

		$this->assertSame( 12, $service->get_matches( $league, array( 'count' => true ) ) );
		$this->assertSame( 12, $league->num_matches );
		$this->assertFalse( $league->get_match_query_args()['count'] );
	}

	public function test_get_matches_reset_limit_and_reset_query_args_restore_previous_args(): void {
		$league     = $this->league();
		$repository = $this->createStub( League_Repository_Interface::class );
		$repository->method( 'query_matches' )->willReturn( array() );
		$initial_limit = $league->get_match_query_args()['limit'];

		$service = new League_Query_Service( $repository );

		$service->get_matches( $league, array( 'limit' => 3, 'home_team' => 5, 'reset_limit' => true ) );
		$this->assertSame( $initial_limit, $league->get_match_query_args()['limit'] );
		$this->assertSame( 5, (int) $league->get_match_query_args()['home_team'] );

		$before = $league->get_match_query_args();
		$service->get_matches( $league, array( 'away_team' => 6, 'reset_query_args' => true ) );
		$after = $league->get_match_query_args();
		$this->assertSame( $before['away_team'] ?? null, $after['away_team'] ?? null );
		$this->assertFalse( $after['reset_query_args'] );
	}

	public function test_get_league_teams_hydrates_resolves_unknown_season_and_resets_args(): void {
		$league   = $this->league();
		$captured = null;
		$repository = $this->createMock( League_Repository_Interface::class );
		$repository->expects( $this->once() )->method( 'query_league_teams' )->willReturnCallback(
			static function ( int $id, array $args, bool $count ) use ( &$captured ): array {
				$captured = $args;
				return array( (object) array( 'table_id' => 77 ) );
			}
		);
		$team = new League_Team( (object) array(
			'id'             => 940,
			'table_id'       => 77,
			'title'          => 'Leigh A',
			'points_plus'    => 3,
			'points_minus'   => 1,
			'points_2_plus'  => 0,
			'points_2_minus' => 0,
			'custom'         => '',
			'roster'         => '',
		) );
		$league_teams = $this->createMock( League_Team_Repository_Interface::class );
		$league_teams->expects( $this->once() )->method( 'find_by_id' )->with( 77 )->willReturn( $team );
		$before = $league->get_team_query_args();

		$service = new League_Query_Service( $repository, null, null, null, $league_teams );
		$teams   = $service->get_league_teams( $league, array( 'season' => '1999', 'reset_query_args' => true ) );

		$this->assertSame( array( $team ), $teams );
		$this->assertSame( array( $team ), $league->teams );
		$this->assertSame( array( 940 => 0 ), $league->team_index );
		$this->assertArrayNotHasKey( 'season', $captured );
		$this->assertTrue( $captured['skip_season_filter'] );
		$this->assertSame( 'Leigh A', $team->title );
		$this->assertSame( $before['season'], $league->get_team_query_args()['season'] );
	}

	public function test_get_league_teams_count_passes_current_season(): void {
		$league   = $this->league();
		$captured = null;
		$repository = $this->createStub( League_Repository_Interface::class );
		$repository->method( 'query_league_teams' )->willReturnCallback(
			static function ( int $id, array $args, bool $count ) use ( &$captured ): int {
				$captured = $args;
				return $count ? 4 : 0;
			}
		);

		$service = new League_Query_Service( $repository );
		$this->assertSame( 4, $service->get_league_teams( $league, array( 'count' => true ) ) );
		$this->assertSame( '2026', $captured['season'] );
	}

	public function test_get_team_dtls_enriches_captain_club_and_roster_through_repositories(): void {
		$league     = $this->league();
		$repository = $this->createStub( League_Repository_Interface::class );
		$repository->method( 'get_team_details' )->willReturn( (object) array(
			'id'             => 940,
			'title'          => 'Leigh A',
			'captain'        => '55',
			'club_id'        => '3',
			'stadium'        => 'Main Courts',
			'roster'         => array( 61 ),
			'team_type'      => 'P',
			'league_status'  => 'W',
			'points_plus'    => 0,
			'points_minus'   => 0,
			'points_2_plus'  => 0,
			'points_2_minus' => 0,
		) );
		$captain               = new Player();
		$captain->display_name = 'Captain Kirk';
		$captain->user_email   = 'kirk@example.test';
		$captain->contactno    = '0123';
		$member = $this->createStub( Player::class );
		$member->method( 'get_fullname' )->willReturn( 'Pair Player' );
		$players = $this->createStub( Player_Repository_Interface::class );
		$players->method( 'find' )->willReturnMap( array( array( 55, 'id', $captain ), array( 61, 'id', $member ) ) );
		$clubs  = $this->createStub( Club::class );
		$clubs_repo = $this->createMock( Club_Repository_Interface::class );
		$clubs_repo->expects( $this->once() )->method( 'find_by_id' )->with( 3 )->willReturn( $clubs );

		$service = new League_Query_Service( $repository, null, null, null, null, $clubs_repo, $players );
		$team    = $service->get_team_dtls( $league, 940 );

		$this->assertSame( '55', $team->captain_id );
		$this->assertSame( 'Captain Kirk', $team->captain );
		$this->assertSame( 'kirk@example.test', $team->contactemail );
		$this->assertSame( '0123', $team->contactno );
		$this->assertSame( $clubs, $team->club );
		$this->assertSame( array( 1 => 'Pair Player' ), $team->player );
		$this->assertSame( array( 1 => 61 ), $team->player_id );
		$this->assertTrue( $team->is_withdrawn );
	}

	public function test_get_players_groups_by_initial_and_skips_system_records(): void {
		$league     = $this->league();
		$repository = $this->createStub( League_Repository_Interface::class );
		$repository->method( 'query_players' )->willReturn( array( (object) array( 'player_id' => 1 ), (object) array( 'player_id' => 2 ) ) );
		$alice                = $this->createStub( Player::class );
		$alice->system_record = false;
		$alice->method( 'get_fullname' )->willReturn( 'Alice' );
		$system                = $this->createStub( Player::class );
		$system->system_record = true;
		$players = $this->createStub( Player_Repository_Interface::class );
		$players->method( 'find' )->willReturnMap( array( array( 1, 'id', $alice ), array( 2, 'id', $system ) ) );
		$service = new League_Query_Service( $repository, null, null, null, null, null, $players );

		$this->assertSame( array( 'A' => array( $alice ) ), $service->get_players( $league, array( 'group' => true ) ) );
	}

	public function test_query_service_get_matches_queries_its_league_repository(): void {
		$league_repository = $this->createMock( League_Repository_Interface::class );
		$fixtures          = $this->createStub( Fixture_Repository_Interface::class );
		$rubbers           = $this->createStub( Rubber_Repository_Interface::class );
		$league_teams      = $this->createStub( League_Team_Repository_Interface::class );
		$service           = new League_Query_Service( $league_repository, $fixtures, $rubbers, null, $league_teams );

		$league_repository->expects( $this->once() )->method( 'query_matches' )
			->with( 42, $this->anything(), true )->willReturn( 3 );

		$league = $this->league();

		$this->assertSame( 3, $service->get_matches( $league, array( 'count' => true ) ) );
	}

	public function test_query_service_has_no_reflection_based_dispatch(): void {
		$source = (string) file_get_contents( dirname( __DIR__, 4 ) . '/src/php/Services/Competition/League_Query_Service.php' );

		$this->assertStringNotContainsString( 'Reflection', $source );
		$this->assertStringNotContainsString( 'overrides_league_method', $source );
	}
}
