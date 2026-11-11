<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Competition_Repository_Player_Query_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_competition_matches_are_league_enriched_before_player_grouping_and_null_rows_are_filtered(): void {
		$rows = array( (object) array( 'id' => 42 ), (object) array( 'id' => 99 ) );
		$wpdb = new class( $rows ) extends wpdb {
			private array $rows;

			public function __construct( array $rows ) {
				$this->prefix = 'wp_';
				$this->rows  = $rows;
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				return $this->rows;
			}

			public function prepare( $query, ...$args ): string {
				return $query;
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture = new Fixture();
		$fixture->set_id( 42 );
		$fixture->set_league_id( 7 );
		$fixture->set_season( '2026' );

		$league = $this->createStub( League::class );
		$league->id    = 7;
		$league->title = 'Division 7';

		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->exactly( 2 ) )
			->method( 'find_by_id' )
			->willReturnCallback( static fn( $id ): ?Fixture => 42 === (int) $id ? $fixture : null );

		$league_repository = $this->createMock( League_Repository_Interface::class );
		$league_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 7 )
			->willReturn( $league );

		$competition_repository = new Competition_Repository(
			static fn(): Fixture_Repository_Interface => $fixture_repository,
			static fn(): Fixture_Relation_Loader => new Fixture_Relation_Loader( $league_repository )
		);
		$competition = new class( $competition_repository ) extends Competition {
			public function __construct( private readonly Competition_Repository $repository ) {
				parent::__construct( 'Test Competition', Competition_Type::LEAGUE, 'open', id: 5 );
			}

			public function get_matches( array $match_args = array(), ?\Racketmanager\Repositories\Interfaces\Competition_Repository_Interface $competition_repository = null ): array {
				return $this->repository->find_matches( 5, $match_args );
			}
		};

		$player  = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Test Player' ) );
		$service = new Player_Query_Service( null, $competition_repository );
		$matches = $service->get_matches( $player, $competition, '2026', 'competition' );

		$this->assertSame( $league, $fixture->get_league() );
		$this->assertSame( array( $fixture ), $matches['Division 7']['matches'] );
		$this->assertSame( $league, $player->matches['Division 7']['league'] );
	}

	public function test_find_matches_with_player_filter_builds_distinct_query_with_rubber_joins_and_where_clause(): void {
		$captured_sql = '';
		$wpdb = new class( $captured_sql ) extends wpdb {
			public function __construct( private string &$captured_sql ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				$this->captured_sql = (string) $query;
				return array();
			}

			public function prepare( $query, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( '%d', "'%s'" ), $query ), $args );
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
		$league_repository  = $this->createStub( League_Repository_Interface::class );

		$competition_repository = new Competition_Repository(
			static fn(): Fixture_Repository_Interface => $fixture_repository,
			static fn(): Fixture_Relation_Loader => new Fixture_Relation_Loader( $league_repository )
		);

		$competition_repository->find_matches( 5, array( 'season' => '2026', 'player' => 7 ) );

		$this->assertStringStartsWith( 'SELECT DISTINCT m.id', $captured_sql );
		$this->assertStringContainsString( 'INNER JOIN wp_racketmanager_rubbers r ON m.id = r.match_id', $captured_sql );
		$this->assertStringContainsString( 'INNER JOIN wp_racketmanager_rubber_players rp ON r.id = rp.rubber_id', $captured_sql );
		$this->assertStringContainsString( 'rp.`player_id` = 7', $captured_sql );
		$this->assertStringContainsString( "m.`season` = '2026'", $captured_sql );
	}

	public function test_find_matches_with_player_id_and_count_builds_count_distinct_query(): void {
		$captured_sql = '';
		$wpdb = new class( $captured_sql ) extends wpdb {
			public function __construct( private string &$captured_sql ) {
				$this->prefix = 'wp_';
			}

			public function get_var( $query = null, $x = 0, $y = 0 ): mixed {
				$this->captured_sql = (string) $query;
				return 4;
			}

			public function prepare( $query, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( '%d', "'%s'" ), $query ), $args );
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture_repository     = $this->createStub( Fixture_Repository_Interface::class );
		$competition_repository = new Competition_Repository( $fixture_repository );

		$count = $competition_repository->find_matches( 5, array( 'season' => '2026', 'player_id' => 7, 'count' => true ) );

		$this->assertSame( 4, $count );
		$this->assertStringStartsWith( 'SELECT COUNT(DISTINCT m.id)', $captured_sql );
		$this->assertStringContainsString( 'INNER JOIN wp_racketmanager_rubbers r ON m.id = r.match_id', $captured_sql );
		$this->assertStringContainsString( 'INNER JOIN wp_racketmanager_rubber_players rp ON r.id = rp.rubber_id', $captured_sql );
		$this->assertStringContainsString( 'rp.`player_id` = 7', $captured_sql );
	}

	public function test_find_matches_without_player_filter_preserves_unfiltered_sql_semantics(): void {
		$captured_sql = '';
		$wpdb = new class( $captured_sql ) extends wpdb {
			public function __construct( private string &$captured_sql ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				$this->captured_sql = (string) $query;
				return array();
			}

			public function get_var( $query = null, $x = 0, $y = 0 ): mixed {
				$this->captured_sql = (string) $query;
				return 10;
			}

			public function prepare( $query, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( '%d', "'%s'" ), $query ), $args );
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture_repository     = $this->createStub( Fixture_Repository_Interface::class );
		$league_repository      = $this->createStub( League_Repository_Interface::class );
		$competition_repository = new Competition_Repository(
			static fn(): Fixture_Repository_Interface => $fixture_repository,
			static fn(): Fixture_Relation_Loader => new Fixture_Relation_Loader( $league_repository )
		);

		$competition_repository->find_matches( 5, array( 'season' => '2026', 'player' => false ) );

		$this->assertStringStartsWith( 'SELECT m.id', $captured_sql );
		$this->assertStringNotContainsString( 'rubbers', $captured_sql );
		$this->assertStringNotContainsString( 'rp.`player_id`', $captured_sql );

		$count = $competition_repository->find_matches( 5, array( 'season' => '2026', 'count' => true ) );
		$this->assertSame( 10, $count );
		$this->assertStringStartsWith( 'SELECT COUNT(*)', $captured_sql );
		$this->assertStringNotContainsString( 'rubbers', $captured_sql );
	}

	public function test_find_matches_hydrates_player_rubbers_when_rubber_repository_provided(): void {
		$rows = array( (object) array( 'id' => 42 ) );
		$wpdb = new class( $rows ) extends wpdb {
			public function __construct( private readonly array $rows ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				return $this->rows;
			}

			public function prepare( $query, ...$args ): string {
				return $query;
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture = new Fixture();
		$fixture->set_id( 42 );
		$fixture->set_league_id( 7 );

		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 42 )
			->willReturn( $fixture );

		$league_repository = $this->createStub( League_Repository_Interface::class );

		$rubber = $this->createStub( Rubber::class );
		$rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
		$rubber_repository->expects( $this->once() )
			->method( 'find_by_fixture_id' )
			->with( 42, 7 )
			->willReturn( array( $rubber ) );

		$competition_repository = new Competition_Repository(
			static fn(): Fixture_Repository_Interface => $fixture_repository,
			static fn(): Fixture_Relation_Loader => new Fixture_Relation_Loader( $league_repository ),
			null,
			null,
			null,
			static fn(): Rubber_Repository_Interface => $rubber_repository
		);

		$matches = $competition_repository->find_matches( 5, array( 'player' => 7 ) );

		$this->assertCount( 1, $matches );
		$this->assertSame( array( $rubber ), $matches[0]->get_rubbers() );
	}

	public function test_find_matches_with_player_and_production_orderby_qualifies_order_columns_and_selects_them_for_strict_sql(): void {
		$captured_sql = '';
		$wpdb = new class( $captured_sql ) extends wpdb {
			public function __construct( private string &$captured_sql ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				$this->captured_sql = (string) $query;
				return array();
			}

			public function prepare( $query, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( '%d', "'%s'" ), $query ), $args );
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
		$league_repository  = $this->createStub( League_Repository_Interface::class );

		$competition_repository = new Competition_Repository(
			static fn(): Fixture_Repository_Interface => $fixture_repository,
			static fn(): Fixture_Relation_Loader => new Fixture_Relation_Loader( $league_repository )
		);

		$competition_repository->find_matches(
			5,
			array(
				'season'  => '2022',
				'player'  => 1491,
				'orderby' => array(
					'date'      => 'ASC',
					'league_id' => 'DESC',
				),
			)
		);

		$this->assertStringStartsWith( 'SELECT DISTINCT m.id, m.`date`, m.`league_id`', $captured_sql );
		$this->assertStringContainsString( 'INNER JOIN wp_racketmanager_rubbers r ON m.id = r.match_id', $captured_sql );
		$this->assertStringContainsString( 'INNER JOIN wp_racketmanager_rubber_players rp ON r.id = rp.rubber_id', $captured_sql );
		$this->assertStringContainsString( 'rp.`player_id` = 1491', $captured_sql );
		$this->assertStringContainsString( 'ORDER BY m.`date` ASC, m.`league_id` DESC', $captured_sql );
	}

	public function test_find_matches_with_invalid_explicit_player_returns_empty_result_without_exposing_all_fixtures(): void {
		$captured_sql = '';
		$wpdb = new class( $captured_sql ) extends wpdb {
			public function __construct( private string &$captured_sql ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				$this->captured_sql = (string) $query;
				return array( (object) array( 'id' => 999 ) );
			}

			public function get_var( $query = null, $x = 0, $y = 0 ): mixed {
				$this->captured_sql = (string) $query;
				return 466;
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture_repository     = $this->createStub( Fixture_Repository_Interface::class );
		$competition_repository = new Competition_Repository( $fixture_repository );

		$matches = $competition_repository->find_matches( 5, array( 'player' => 'abc' ) );
		$this->assertSame( array(), $matches );
		$this->assertSame( '', $captured_sql );

		$count = $competition_repository->find_matches( 5, array( 'player' => 'abc', 'count' => true ) );
		$this->assertSame( 0, $count );
		$this->assertSame( '', $captured_sql );

		$matches_zero = $competition_repository->find_matches( 5, array( 'player' => 0 ) );
		$this->assertSame( array(), $matches_zero );
		$this->assertSame( '', $captured_sql );

		$matches_negative = $competition_repository->find_matches( 5, array( 'player_id' => -1 ) );
		$this->assertSame( array(), $matches_negative );
		$this->assertSame( '', $captured_sql );
	}

	public function test_find_matches_player_id_alias_precedence_and_fallback(): void {
		$captured_sql = '';
		$wpdb         = new class( $captured_sql ) extends wpdb {
			public function __construct( private string &$captured_sql ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				$this->captured_sql = (string) $query;
				return array();
			}

			public function prepare( $query, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( '%d', "'%s'" ), $query ), $args );
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture_repository     = $this->createStub( Fixture_Repository_Interface::class );
		$league_repository      = $this->createStub( League_Repository_Interface::class );
		$competition_repository = new Competition_Repository(
			static fn(): Fixture_Repository_Interface => $fixture_repository,
			static fn(): Fixture_Relation_Loader => new Fixture_Relation_Loader( $league_repository )
		);

		// When both player_id and player are provided, player_id takes precedence.
		$competition_repository->find_matches( 5, array( 'player_id' => 42, 'player' => 99 ) );
		$this->assertStringContainsString( 'rp.`player_id` = 42', $captured_sql );
		$this->assertStringNotContainsString( 'rp.`player_id` = 99', $captured_sql );

		// When player_id is null, it falls back to player.
		$competition_repository->find_matches( 5, array( 'player_id' => null, 'player' => 99 ) );
		$this->assertStringContainsString( 'rp.`player_id` = 99', $captured_sql );

		// When player_id is false, it falls back to player.
		$competition_repository->find_matches( 5, array( 'player_id' => false, 'player' => 88 ) );
		$this->assertStringContainsString( 'rp.`player_id` = 88', $captured_sql );
	}

	public function test_find_matches_handles_multiple_rubbers_per_fixture_with_distinct_and_attaches_all_player_rubbers(): void {
		$captured_sql = '';
		$wpdb         = new class( $captured_sql ) extends wpdb {
			public function __construct( private string &$captured_sql ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				$this->captured_sql = (string) $query;
				// Simulate the SQL join behavior: without SELECT DISTINCT, a fixture with multiple
				// matching rubbers produces duplicate match rows from the join on rubbers & rubber_players.
				if ( str_starts_with( ltrim( (string) $query ), 'SELECT DISTINCT' ) ) {
					return array( (object) array( 'id' => 50 ) );
				}
				return array( (object) array( 'id' => 50 ), (object) array( 'id' => 50 ) );
			}

			public function get_var( $query = null, $x = 0, $y = 0 ): mixed {
				$this->captured_sql = (string) $query;
				// Simulate COUNT behavior: COUNT(DISTINCT m.id) returns 1 distinct fixture,
				// whereas non-distinct COUNT(*) over the joined rubbers returns 2.
				if ( str_contains( (string) $query, 'COUNT(DISTINCT' ) ) {
					return 1;
				}
				return 2;
			}

			public function prepare( $query, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( '%d', "'%s'" ), $query ), $args );
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$fixture = new Fixture();
		$fixture->set_id( 50 );
		$fixture->set_league_id( 3 );

		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 50 )
			->willReturn( $fixture );

		$league_repository = $this->createStub( League_Repository_Interface::class );

		// Simulate multiple rubbers for the player in the same fixture (e.g. singles and doubles).
		$rubber_singles = $this->createStub( Rubber::class );
		$rubber_doubles = $this->createStub( Rubber::class );

		$rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
		$rubber_repository->expects( $this->once() )
			->method( 'find_by_fixture_id' )
			->with( 50, 7 )
			->willReturn( array( $rubber_singles, $rubber_doubles ) );

		$competition_repository = new Competition_Repository(
			static fn(): Fixture_Repository_Interface => $fixture_repository,
			static fn(): Fixture_Relation_Loader => new Fixture_Relation_Loader( $league_repository ),
			null,
			null,
			null,
			static fn(): Rubber_Repository_Interface => $rubber_repository
		);

		$matches = $competition_repository->find_matches( 5, array( 'player' => 7 ) );

		// Because SELECT DISTINCT is used, the duplicate rows from multiple rubbers are collapsed to exactly one fixture.
		// If DISTINCT were missing, find_by_id would be called twice (failing the once() mock expectation) and 2 matches returned.
		$this->assertCount( 1, $matches );
		$this->assertSame( 50, $matches[0]->get_id() );
		$this->assertStringStartsWith( 'SELECT DISTINCT m.id', $captured_sql );
		$this->assertSame( array( $rubber_singles, $rubber_doubles ), $matches[0]->get_rubbers() );

		// Count query uses COUNT(DISTINCT m.id) ensuring multiple rubbers don't inflate match count.
		// If COUNT(DISTINCT) were missing, the simulated join returns 2, failing this assertion.
		$count = $competition_repository->find_matches( 5, array( 'player' => 7, 'count' => true ) );
		$this->assertSame( 1, $count );
		$this->assertStringStartsWith( 'SELECT COUNT(DISTINCT m.id)', $captured_sql );
	}

	public function test_competition_matches_query_filters_out_other_player_leagues_and_matches_at_database_boundary(): void {
		$captured_sql = '';
		$wpdb         = new class( $captured_sql ) extends wpdb {
			public function __construct( private string &$captured_sql ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				$this->captured_sql = (string) $query;

				// Simulate the joined query at database boundary:
				// If both rubber joins and the player filter (rp.player_id = 7) are present in SQL,
				// return only fixtures 101 and 201 where player 7 played.
				// If the filter or joins are absent, all fixtures across the competition for that season
				// (including fixture 102 in Premier Division and fixture 301 in Division 2) are returned.
				$has_rubber_joins  = str_contains( (string) $query, 'wp_racketmanager_rubbers r ON m.id = r.match_id' )
					&& str_contains( (string) $query, 'wp_racketmanager_rubber_players rp ON r.id = rp.rubber_id' );
				$has_player_filter = str_contains( (string) $query, 'rp.`player_id` = 7' );

				if ( $has_rubber_joins && $has_player_filter ) {
					return array(
						(object) array( 'id' => 101 ),
						(object) array( 'id' => 201 ),
					);
				}

				return array(
					(object) array( 'id' => 101 ),
					(object) array( 'id' => 102 ),
					(object) array( 'id' => 201 ),
					(object) array( 'id' => 301 ),
				);
			}

			public function prepare( $query, ...$args ): string {
				return vsprintf( str_replace( array( '%d', '%s' ), array( '%d', "'%s'" ), $query ), $args );
			}
		};
		$GLOBALS['wpdb'] = $wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$make_fixture = static function ( int $id, int $league_id ): Fixture {
			$f = new Fixture();
			$f->set_id( $id );
			$f->set_league_id( $league_id );
			$f->set_season( '2026' );
			return $f;
		};

		$league_1        = $this->createStub( League::class );
		$league_1->id    = 1;
		$league_1->title = 'Premier Division';

		$league_2        = $this->createStub( League::class );
		$league_2->id    = 2;
		$league_2->title = 'Division 1';

		$league_3        = $this->createStub( League::class );
		$league_3->id    = 3;
		$league_3->title = 'Division 2';

		// 4 fixtures are loaded during unfiltered query (101, 102, 201, 301) and 2 during filtered query (101, 201).
		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->exactly( 6 ) )
			->method( 'find_by_id' )
			->willReturnCallback(
				static fn( $id ): ?Fixture => match ( (int) $id ) {
					101     => $make_fixture( 101, 1 ),
					102     => $make_fixture( 102, 1 ),
					201     => $make_fixture( 201, 2 ),
					301     => $make_fixture( 301, 3 ),
					default => null,
				}
			);

		$league_repository = $this->createMock( League_Repository_Interface::class );
		$league_repository->expects( $this->exactly( 6 ) )
			->method( 'find_by_id' )
			->willReturnCallback(
				static fn( $id ): ?League => match ( (int) $id ) {
					1       => $league_1,
					2       => $league_2,
					3       => $league_3,
					default => null,
				}
			);

		$competition_repository = new Competition_Repository(
			static fn(): Fixture_Repository_Interface => $fixture_repository,
			static fn(): Fixture_Relation_Loader => new Fixture_Relation_Loader( $league_repository )
		);

		// 1. Without player filter, the database stub simulates the full competition season results (all 4 matches across 3 leagues).
		$unfiltered_matches = $competition_repository->find_matches( 5, array( 'season' => '2026', 'player' => false ) );
		$this->assertCount( 4, $unfiltered_matches );
		$this->assertSame( array( 101, 102, 201, 301 ), array_map( static fn( Fixture $f ): int => $f->get_id(), $unfiltered_matches ) );

		// 2. With player 7 filter, the SQL joins rubbers & rubber_players with rp.player_id = 7.
		// The database stub derives returned rows from the SQL: returning only matches 101 and 201,
		// and excluding fixture 102 and Division 2 (fixture 301).
		$competition = new class( $competition_repository ) extends Competition {
			public function __construct( private readonly Competition_Repository $repository ) {
				parent::__construct( 'Test Competition', Competition_Type::LEAGUE, 'open', id: 5 );
			}

			public function get_matches( array $match_args = array(), ?\Racketmanager\Repositories\Interfaces\Competition_Repository_Interface $competition_repository = null ): array {
				return $this->repository->find_matches( 5, $match_args );
			}
		};

		$player  = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );
		$service = new Player_Query_Service( null, $competition_repository );
		$grouped = $service->get_matches( $player, $competition, '2026', 'competition' );

		// Verifies SQL query contains joins and player predicate.
		$this->assertStringContainsString( 'INNER JOIN wp_racketmanager_rubbers r ON m.id = r.match_id', $captured_sql );
		$this->assertStringContainsString( 'INNER JOIN wp_racketmanager_rubber_players rp ON r.id = rp.rubber_id', $captured_sql );
		$this->assertStringContainsString( 'rp.`player_id` = 7', $captured_sql );

		// Verifies that only player 7's leagues are present in grouped results; Division 2 is excluded at the database query boundary.
		$this->assertSame( array( 'Premier Division', 'Division 1' ), array_keys( $grouped ) );
		$this->assertArrayNotHasKey( 'Division 2', $grouped );
		$this->assertCount( 1, $grouped['Premier Division']['matches'] );
		$this->assertSame( 101, $grouped['Premier Division']['matches'][0]->get_id() );
		$this->assertCount( 1, $grouped['Division 1']['matches'] );
		$this->assertSame( 201, $grouped['Division 1']['matches'][0]->get_id() );
	}
}
