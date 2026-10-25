<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Repositories\Results_Checker_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

require_once __DIR__ . '/../../wp-stubs.php';

/**
 * Captures SQL issued through the repositories that back the RacketManager facade
 * and asserts the legacy (pre-extraction) filters, ordering and return shapes.
 */
final class Facade_Query_Parity_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Capturing_Wpdb_For_Parity $db;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->db                          = new Capturing_Wpdb_For_Parity();
		$GLOBALS['wpdb']                   = $this->db;
		$GLOBALS['wp_stubs_cache_enabled'] = false;
		$GLOBALS['wp_stubs_cache']         = array();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_league_standings_keeps_league_type_filter_and_id_ordering(): void {
		$this->db->results = array( (object) array( 'id' => 1, 'won_matches' => 2, 'rank' => 1 ) );
		$repo              = new League_Repository( $this->db );

		$rows = $repo->get_league_standings( array( 'season' => 2026, 'team' => 5, 'age_group' => 'open' ) );

		$this->assertCount( 1, $rows );
		$sql = $this->db->last_sql;
		$this->assertStringContainsString( "AND c.`type` = 'league'))", $sql );
		$this->assertStringContainsString( 'AND t.`season` = 2026', $sql );
		$this->assertStringContainsString( 'AND t.`team_id` = 5', $sql );
		$this->assertStringContainsString( "`age_group` = 'open'", $sql );
		$this->assertStringEndsWith( ' ORDER BY l.`id` ASC', $sql );
	}

	public function test_league_standings_returns_array_when_no_rows(): void {
		$this->db->results = null;
		$this->assertSame( array(), ( new League_Repository( $this->db ) )->get_league_standings() );
	}

	public function test_fixture_find_all_default_order_is_league_then_id(): void {
		$this->db->results = array();
		$repo              = new Fixture_Repository( $this->db );

		$this->assertSame( array(), $repo->find_all( array( 'season' => '2026' ) ) );
		$this->assertStringEndsWith( ' ORDER BY `league_id` ASC,`id` ASC', $this->db->last_sql );
	}

	public function test_fixture_find_all_count_returns_int(): void {
		$this->db->var = '7';
		$repo          = new Fixture_Repository( $this->db );

		$this->assertSame( 7, $repo->find_all( array( 'count' => true ) ) );
		$this->assertStringNotContainsString( 'ORDER BY', $this->db->last_sql );
	}

	public function test_fixture_find_all_honours_explicit_orderby(): void {
		$this->db->results = array();
		( new Fixture_Repository( $this->db ) )->find_all( array( 'orderby' => array( 'date' => 'DESC' ) ) );
		$this->assertStringEndsWith( ' ORDER BY `date` DESC', $this->db->last_sql );
	}

	public function test_club_is_player_in_club_uses_legacy_columns(): void {
		$this->db->var = '1';
		$repo          = new Club_Repository();

		$this->assertTrue( $repo->is_player_in_club( array( 'team' => 3, 'player' => 9, 'active' => true, 'type' => true ) ) );
		$sql = $this->db->last_sql;
		$this->assertStringContainsString( 'FROM wp_racketmanager_club_players', $sql );
		$this->assertStringContainsString( '`player_id` = 9', $sql );
		$this->assertStringContainsString( '`system_record` IS NULL', $sql );
		$this->assertStringContainsString( '`removed_date` IS NULL', $sql );
		$this->assertStringNotContainsString( "`status` = 'A'", $sql );
	}

	public function test_club_is_player_in_club_false_when_no_rows(): void {
		$this->db->var = '0';
		$this->assertFalse( ( new Club_Repository() )->is_player_in_club( array( 'player' => 9 ) ) );
	}

	public function test_registration_repository_no_longer_duplicates_is_player_in_club(): void {
		$this->assertFalse( method_exists( Registration_Repository::class, 'is_player_in_club' ) );
	}

	public function test_results_checker_find_all_returns_count_or_rows(): void {
		$repo          = new Results_Checker_Repository();
		$this->db->var = '331';

		$this->assertSame( 331, $repo->find_all( array( 'count' => true, 'season' => '2026' ) ) );
		$this->assertStringStartsWith( 'SELECT COUNT(*) FROM wp_racketmanager_results_checker rc WHERE 1', $this->db->last_sql );
		$this->assertStringContainsString( '`match_id` IN (SELECT `id` FROM wp_racketmanager_matches WHERE `season` = \'2026\')', $this->db->last_sql );

		$this->db->results = array();
		$this->assertSame( array(), $repo->find_all( array( 'status' => 'outstanding' ) ) );
		$this->assertStringContainsString( 'AND `status` IS NULL', $this->db->last_sql );
	}

	public function test_active_rubber_players_excludes_tournament_entries(): void {
		$this->db->results = array();
		$repo              = new Player_Repository( $this->db );

		$this->assertSame( array(), $repo->get_active_rubber_players() );
		$this->assertStringContainsString( 'racketmanager_rubber_players', $this->db->last_sql );
		$this->assertStringNotContainsString( 'UNION', $this->db->last_sql );
		$this->assertStringNotContainsString( 'tournament_entries', $this->db->last_sql );
	}

	public function test_get_winners_returns_false_without_rows_and_uses_final_filter(): void {
		$this->db->results = array();
		$repo              = new Competition_Repository();

		$this->assertFalse( $repo->get_winners( '2026', 4, 'tournament' ) );
		$this->assertStringContainsString( "m.`final` = 'FINAL'", $this->db->last_sql );
		$this->assertStringContainsString( 'c.`id` = 4', $this->db->last_sql );
	}

	public function test_get_winners_resolves_clubs_through_injected_fixture_repository(): void {
		$this->db->results = array(
			(object) array(
				'id'        => 11,
				'title'     => 'Open Singles',
				'home_team' => 1,
				'away_team' => 2,
				'winner_id' => 1,
				'loser_id'  => 2,
				'type'      => 'MS',
			),
		);
		$fixture           = new Fixture( new Fixture_Hydration_DTO( id: 11, home_team: '1', away_team: '2' ) );
		$fixture->set_teams(
			array(
				'home' => (object) array( 'club' => (object) array( 'shortcode' => 'LEI' ) ),
				'away' => (object) array( 'club' => (object) array( 'shortcode' => 'WES' ) ),
			)
		);
		$fixtures          = $this->createMock( Fixture_Repository_Interface::class );
		$fixtures->expects( $this->once() )->method( 'find_by_id' )->with( 11 )->willReturn( $fixture );

		$repo   = new Competition_Repository( static fn() => $fixtures );
		$result = $repo->get_winners( '2026', 4, 'tournament', true );

		$this->assertArrayHasKey( 'MS', $result );
		$this->assertSame( 'Open Singles', $result['MS'][0]->league );
		$this->assertSame( 'LEI', $result['MS'][0]->winner_club );
		$this->assertSame( 'WES', $result['MS'][0]->loser_club );
	}

	public function test_get_winners_does_not_fatal_without_fixture_repository(): void {
		$this->db->results = array(
			(object) array( 'id' => 11, 'title' => 'T', 'home_team' => 1, 'away_team' => 2, 'winner_id' => 1, 'loser_id' => 2, 'type' => 'MS' ),
		);
		$result            = ( new Competition_Repository() )->get_winners( '2026', 4 );

		$this->assertCount( 1, $result );
		$this->assertNull( $result[0]->winner_club );
		$this->assertNull( $result[0]->loser_club );
	}

	public function test_club_find_all_defaults_to_affiliated(): void {
		$this->db->results = array();
		$repo              = new Club_Repository( $this->db );

		$this->assertSame( array(), $repo->find_all() );
		$this->assertStringContainsString( "`type` = 'affiliated'", $this->db->last_sql );
	}

	public function test_club_find_all_supports_type_false(): void {
		$this->db->results = array();
		$repo              = new Club_Repository( $this->db );

		$this->assertSame( array(), $repo->find_all( array( 'type' => false ) ) );
		$this->assertStringNotContainsString( '`type` =', $this->db->last_sql );
	}

	public function test_club_find_all_player_type_filters(): void {
		$this->db->results = array();
		$repo              = new Club_Repository( $this->db );

		$repo->find_all( array( 'player_type' => 'secretary', 'player' => 42 ) );
		$this->assertStringContainsString( 'SELECT `club_id` FROM wp_racketmanager_club_roles WHERE `role_id` = 1 AND `user_id` = 42', $this->db->last_sql );

		$repo->find_all( array( 'player_type' => 'captain', 'player' => 43 ) );
		$this->assertStringContainsString( '`captain` = 43', $this->db->last_sql );
		$this->assertStringContainsString( 'wp_racketmanager_teams_events te, wp_racketmanager_teams t', $this->db->last_sql );

		$repo->find_all( array( 'player_type' => 'player', 'player' => 44 ) );
		$this->assertStringContainsString( 'SELECT `club_id` FROM wp_racketmanager_club_players cp WHERE `player_id` = 44 AND `removed_date` IS NULL', $this->db->last_sql );
	}

	public function test_club_find_all_club_filter_and_count(): void {
		$this->db->var = '5';
		$repo          = new Club_Repository( $this->db );

		$this->assertSame( 5, $repo->find_all( array( 'count' => true, 'club' => 12 ) ) );
		$this->assertStringStartsWith( 'SELECT COUNT(ID) FROM wp_racketmanager_clubs', $this->db->last_sql );
		$this->assertStringContainsString( '`id` = 12', $this->db->last_sql );
	}

	public function test_club_find_all_limit_offset_and_orderby(): void {
		$this->db->results = array();
		$repo              = new Club_Repository( $this->db );

		$repo->find_all( array( 'limit' => 10, 'offset' => 20, 'orderby' => 'desc' ) );
		$this->assertStringContainsString( 'ORDER BY `name` DESC LIMIT 20, 10', $this->db->last_sql );
	}

	public function test_team_find_all_player_and_partner_filters(): void {
		$this->db->results = array();
		$repo              = new Team_Repository( $this->db );

		$repo->find_all( array( 'player' => 15, 'partner' => 16 ) );
		$this->assertStringContainsString( '`team_id` FROM wp_racketmanager_team_players WHERE `player_id` = 15', $this->db->last_sql );
		$this->assertStringContainsString( '`team_id` FROM wp_racketmanager_team_players WHERE `player_id` = 16', $this->db->last_sql );
		$this->assertStringContainsString( 'ORDER BY `id` ASC', $this->db->last_sql );
	}

	public function test_team_find_all_count_returns_int(): void {
		$this->db->var = '1325';
		$repo          = new Team_Repository( $this->db );

		$this->assertSame( 1325, $repo->find_all( array( 'count' => true ) ) );
		$this->assertStringStartsWith( 'SELECT COUNT(distinct(`id`)) FROM wp_racketmanager_teams', $this->db->last_sql );
	}

	public function test_league_find_all_filters_and_ordering(): void {
		$this->db->results = array();
		$repo              = new League_Repository( $this->db );

		$repo->find_all( array( 'name' => 'Premier', 'competition_type' => 'league', 'limit' => 5, 'offset' => 10 ) );
		$this->assertStringContainsString( "`title` like 'Premier%'", $this->db->last_sql );
		$this->assertStringContainsString( "AND c.`type` = 'league')", $this->db->last_sql );
		$this->assertStringContainsString( 'ORDER BY `title` ASC LIMIT 10, 5', $this->db->last_sql );
	}

	public function test_event_find_all_filters_and_ordering(): void {
		$this->db->results = array();
		$repo              = new Event_Repository( $this->db );

		$repo->find_all( array( 'name' => 'Men', 'competition_type' => 'tournament', 'entry_type' => 'team' ) );
		$this->assertStringContainsString( "`name` like 'Men%'", $this->db->last_sql );
		$this->assertStringContainsString( "`competition_id` in (select `id` from wp_racketmanager_competitions WHERE `type` = 'tournament')", $this->db->last_sql );
		$this->assertStringContainsString( "`competition_id` in (select `id` from wp_racketmanager_competitions WHERE `type` in ( 'league', 'cup' ) )", $this->db->last_sql );
		$this->assertStringContainsString( 'ORDER BY `name` ASC', $this->db->last_sql );
	}
}

/**
 * wpdb subclass that records the last SQL and returns canned results.
 */
class Capturing_Wpdb_For_Parity extends wpdb {
	public string $last_sql = '';
	public mixed $results   = array();
	public mixed $var       = 0;

	public function prepare( $query, ...$args ) {
		if ( isset( $args[0] ) && is_array( $args[0] ) && 1 === count( $args ) ) {
			$args = $args[0];
		}
		$i = 0;
		return (string) preg_replace_callback(
			'/%[ds]/',
			static function ( array $m ) use ( &$i, $args ) {
				$value = $args[ $i++ ] ?? '';
				return '%d' === $m[0] ? (string) (int) $value : "'" . $value . "'";
			},
			(string) $query
		);
	}

	public function get_results( $query = null, $output = OBJECT ) {
		$this->last_sql = (string) $query;
		return $this->results;
	}

	public function get_var( $query = null, $x = 0, $y = 0 ) {
		$this->last_sql = (string) $query;
		return $this->var;
	}

	public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
		$this->last_sql = (string) $query;
		return null;
	}
}
