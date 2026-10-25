<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Repository_Slug_Criteria_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private $wpdb;
	private $league_repository;
	private Fixture_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->wpdb              = $this->createMock( wpdb::class );
		$this->wpdb->prefix      = 'wp_';
		$GLOBALS['wpdb']         = $this->wpdb;
		$this->league_repository = $this->createMock( League_Repository_Interface::class );

		$this->repository = new Fixture_Repository(
			$this->wpdb,
			null,
			null,
			$this->league_repository
		);
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_find_one_by_slug_criteria_returns_null_if_league_repository_is_missing(): void {
		$repo   = new Fixture_Repository( $this->wpdb );
		$result = $repo->find_one_by_slug_criteria( array( 'league_slug' => 24 ) );

		$this->assertNull( $result );
	}

	public function test_find_one_by_slug_criteria_returns_null_if_no_league_slug_or_id(): void {
		$result = $this->repository->find_one_by_slug_criteria( array( 'season' => '2023-24' ) );

		$this->assertNull( $result );
	}

	public function test_find_one_by_slug_criteria_returns_null_if_league_not_found(): void {
		$this->league_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 1 )
			->willReturn( null );

		$result = $this->repository->find_one_by_slug_criteria(
			array(
				'league_id' => 1,
				'season'    => '2023-24',
			)
		);

		$this->assertNull( $result );
	}

	public function test_find_one_by_slug_criteria_returns_null_when_home_team_slug_not_found(): void {
		$league = new League( (object) array( 'id' => 10 ) );
		$this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );

		$this->wpdb->method( 'get_var' )->willReturn( null );

		$result = $this->repository->find_one_by_slug_criteria(
			array(
				'league_slug'    => 10,
				'home_team_slug' => 'Unknown Home Team',
			)
		);

		$this->assertNull( $result );
	}

	public function test_find_one_by_slug_criteria_returns_null_when_away_team_slug_not_found(): void {
		$league = new League( (object) array( 'id' => 10 ) );
		$this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );

		$this->wpdb->method( 'get_var' )->willReturn( null );

		$result = $this->repository->find_one_by_slug_criteria(
			array(
				'league_slug'    => 10,
				'away_team_slug' => 'Unknown Away Team',
			)
		);

		$this->assertNull( $result );
	}

	public function test_find_one_by_slug_criteria_returns_null_on_ambiguous_or_empty_matches(): void {
		$league = new League( (object) array( 'id' => 24 ) );
		$this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 24 )->willReturn( $league );

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->willReturn(
				array(
					(object) array( 'id' => 101 ),
					(object) array( 'id' => 102 ),
				)
			);

		$result = $this->repository->find_one_by_slug_criteria(
			array(
				'league_slug' => 24,
				'season'      => '2017',
			)
		);

		$this->assertNull( $result );
	}

	public function test_find_one_by_slug_criteria_forwards_filters_and_hydrates_fixture(): void {
		$league                 = new League(
			(object) array(
				'id'              => 24,
				'title'           => 'Summer Mixed 1',
				'is_championship' => false,
			)
		);
		$league->current_season = array( 'name' => '2026' );

		$this->league_repository->expects( $this->once() )
			->method( 'find_by_name' )
			->with( 'Summer Mixed 1' )
			->willReturn( $league );

		// Team lookups for home and away
		$this->wpdb->method( 'get_var' )
			->willReturnOnConsecutiveCalls( 6, 3 );

		$row = (object) array(
			'id'        => 736,
			'season'    => '2017',
			'home_team' => '6',
			'away_team' => '3',
			'match_day' => 1,
		);

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with(
				24,
				array(
					'season'          => '2017',
					'home_team'       => 6,
					'away_team'       => 3,
					'match_day'       => 1,
					'final'           => 'final',
					'leg'             => 2,
					'is_championship' => false,
					'current_season'  => '2026',
				)
			)
			->willReturn( array( $row ) );

		$result = $this->repository->find_one_by_slug_criteria(
			array(
				'league_slug'    => 'Summer Mixed 1',
				'season'         => '2017',
				'home_team_slug' => 'Westcliff Lawn Mixed 1',
				'away_team_slug' => 'Rayleigh Mixed 1',
				'match_day'      => 1,
				'round'          => 'final',
				'leg'            => 2,
			)
		);

		$this->assertInstanceOf( Fixture::class, $result );
		$this->assertSame( 736, $result->get_id() );
	}

	public function test_find_one_by_slug_criteria_resolves_teams_via_injected_team_repository(): void {
		$league                 = new League(
			(object) array(
				'id'              => 24,
				'title'           => 'Summer Mixed 1',
				'is_championship' => false,
			)
		);
		$league->current_season = array( 'name' => '2026' );

		$this->league_repository->expects( $this->once() )
			->method( 'find_by_name' )
			->with( 'Summer Mixed 1' )
			->willReturn( $league );

		$team_repository = $this->createMock( Team_Repository_Interface::class );
		$team_repository->expects( $this->exactly( 2 ) )
			->method( 'find_id_by_title' )
			->willReturnMap(
				array(
					array( 'Westcliff Lawn Mixed 1', 6 ),
					array( 'Rayleigh Mixed 1', 3 ),
				)
			);

		$this->wpdb->expects( $this->never() )->method( 'get_var' );

		$row = (object) array(
			'id'        => 736,
			'season'    => '2017',
			'home_team' => '6',
			'away_team' => '3',
			'match_day' => 1,
		);

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with(
				24,
				array(
					'season'          => '2017',
					'home_team'       => 6,
					'away_team'       => 3,
					'match_day'       => 1,
					'final'           => null,
					'leg'             => null,
					'is_championship' => false,
					'current_season'  => '2026',
				)
			)
			->willReturn( array( $row ) );

		$repository = new Fixture_Repository(
			$this->wpdb,
			null,
			null,
			$this->league_repository,
			$team_repository
		);

		$result = $repository->find_one_by_slug_criteria(
			array(
				'league_slug'    => 'Summer Mixed 1',
				'season'         => '2017',
				'home_team_slug' => 'Westcliff Lawn Mixed 1',
				'away_team_slug' => 'Rayleigh Mixed 1',
				'match_day'      => 1,
			)
		);

		$this->assertInstanceOf( Fixture::class, $result );
		$this->assertSame( 736, $result->get_id() );
	}

	public function test_find_one_by_slug_criteria_returns_null_when_injected_team_repository_does_not_find_team_without_redundant_query(): void {
		$league                 = new League(
			(object) array(
				'id'              => 24,
				'title'           => 'Summer Mixed 1',
				'is_championship' => false,
			)
		);
		$league->current_season = array( 'name' => '2026' );

		$this->league_repository->expects( $this->once() )
			->method( 'find_by_name' )
			->with( 'Summer Mixed 1' )
			->willReturn( $league );

		$team_repository = $this->createMock( Team_Repository_Interface::class );
		$team_repository->expects( $this->once() )
			->method( 'find_id_by_title' )
			->with( 'Unknown Team' )
			->willReturn( 0 );

		$this->wpdb->expects( $this->never() )->method( 'get_var' );
		$this->league_repository->expects( $this->never() )->method( 'query_matches' );

		$repository = new Fixture_Repository(
			$this->wpdb,
			null,
			null,
			$this->league_repository,
			$team_repository
		);

		$result = $repository->find_one_by_slug_criteria(
			array(
				'league_slug'    => 'Summer Mixed 1',
				'season'         => '2017',
				'home_team_slug' => 'Unknown Team',
				'away_team_slug' => 'Rayleigh Mixed 1',
				'match_day'      => 1,
			)
		);

		$this->assertNull( $result );
	}

	public function test_find_one_by_slug_criteria_falls_back_to_wpdb_when_team_repository_is_not_injected(): void {
		$league                 = new League(
			(object) array(
				'id'              => 24,
				'title'           => 'Summer Mixed 1',
				'is_championship' => false,
			)
		);
		$league->current_season = array( 'name' => '2026' );

		$this->league_repository->expects( $this->once() )
			->method( 'find_by_name' )
			->with( 'Summer Mixed 1' )
			->willReturn( $league );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturnOnConsecutiveCalls( '6', '3' );

		$row = (object) array(
			'id'        => 736,
			'season'    => '2017',
			'home_team' => '6',
			'away_team' => '3',
			'match_day' => 1,
		);

		$this->league_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with(
				24,
				array(
					'season'          => '2017',
					'home_team'       => 6,
					'away_team'       => 3,
					'match_day'       => 1,
					'final'           => null,
					'leg'             => null,
					'is_championship' => false,
					'current_season'  => '2026',
				)
			)
			->willReturn( array( $row ) );

		$repository = new Fixture_Repository(
			$this->wpdb,
			null,
			null,
			$this->league_repository,
			null
		);

		$result = $repository->find_one_by_slug_criteria(
			array(
				'league_slug'    => 'Summer Mixed 1',
				'season'         => '2017',
				'home_team_slug' => 'Westcliff Lawn Mixed 1',
				'away_team_slug' => 'Rayleigh Mixed 1',
				'match_day'      => 1,
			)
		);

		$this->assertInstanceOf( Fixture::class, $result );
		$this->assertSame( 736, $result->get_id() );
	}
}
