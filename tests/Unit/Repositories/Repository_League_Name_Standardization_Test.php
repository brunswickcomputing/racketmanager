<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Fixture_Find_All_Query;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Recording_Wpdb_Double extends wpdb {
	public $last_query        = '';
	public array $prepared    = array();
	public array $executed    = array();
	public mixed $get_var_return     = 0;
	public mixed $get_results_return = array();
	public mixed $get_row_return     = null;

	public function __construct() {
		$this->prefix = 'wp_';
	}

	public function prepare( $query, ...$args ): string {
		if ( isset( $args[0] ) && is_array( $args[0] ) && 1 === count( $args ) ) {
			$args = $args[0];
		}
		$this->prepared[] = array(
			'sql'  => (string) $query,
			'args' => $args,
		);
		$this->last_query = (string) $query;
		return (string) $query;
	}

	public function get_var( $query = null, $x = 0, $y = 0 ): mixed {
		$this->last_query = (string) $query;
		return $this->get_var_return;
	}

	public function get_results( $query = null, $output = 'OBJECT' ): array {
		$this->last_query = (string) $query;
		return is_array( $this->get_results_return ) ? $this->get_results_return : array();
	}

	public function get_row( $query = null, $output = 'OBJECT', $y = 0 ): ?object {
		$this->last_query = (string) $query;
		return is_object( $this->get_row_return ) ? $this->get_row_return : null;
	}

	public function last_sql(): string {
		if ( ! empty( $this->prepared ) ) {
			return $this->prepared[ array_key_last( $this->prepared ) ]['sql'];
		}
		return $this->last_query;
	}
}

final class Repository_League_Name_Standardization_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Recording_Wpdb_Double $db;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->db        = new Recording_Wpdb_Double();
		$GLOBALS['wpdb'] = $this->db;
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_competition_repository_find_teams_projections_and_default_orderby(): void {
		$this->db->get_results_return = array();
		$repo                         = new Competition_Repository( $this->createStub( Fixture_Repository_Interface::class ) );

		$repo->find_teams( 1 );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( '`l`.`title` AS `league_name`', $sql );
		$this->assertStringContainsString( '`l`.`title` AS `league_title`', $sql );
		$this->assertStringContainsString( 'ORDER BY `league_name` ASC,`name` ASC', $sql );
	}

	public function test_competition_repository_find_teams_normalizes_legacy_league_title_orderby(): void {
		$this->db->get_results_return = array();
		$repo                         = new Competition_Repository( $this->createStub( Fixture_Repository_Interface::class ) );

		$repo->find_teams( 1, array( 'orderby' => array( 'league_title' => 'DESC' ) ) );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'ORDER BY `league_name` DESC', $sql );
	}

	public function test_competition_repository_query_teams_alias(): void {
		$this->db->get_results_return = array();
		$repo                         = new Competition_Repository( $this->createStub( Fixture_Repository_Interface::class ) );

		$repo->query_teams( 1 );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( '`l`.`title` AS `league_name`', $sql );
		$this->assertStringContainsString( '`l`.`title` AS `league_title`', $sql );
	}

	public function test_competition_repository_winners_queries_dual_projection(): void {
		$this->db->get_results_return = array();
		$repo                         = new Competition_Repository( $this->createStub( Fixture_Repository_Interface::class ) );

		$repo->get_league_winners( 1, 2026 );
		$league_sql = $this->db->last_sql();
		$this->assertStringContainsString( 'l.`title` AS `league_name`', $league_sql );
		$this->assertStringContainsString( 'l.`title` AS `league_title`', $league_sql );

		$repo->get_championship_winners( 1, 2026 );
		$champ_sql = $this->db->last_sql();
		$this->assertStringContainsString( 'l.`title` AS `league_name`', $champ_sql );
		$this->assertStringContainsString( 'l.`title` AS `league_title`', $champ_sql );
	}

	public function test_competition_repository_get_winners_populates_league_name_and_league_title(): void {
		$winner_row = (object) array(
			'id'               => 42,
			'title'            => 'Division 1',
			'winner'           => 'Team A',
			'loser'            => 'Team B',
			'home_team'        => 10,
			'away_team'        => 20,
			'winner_id'        => 10,
			'loser_id'         => 20,
			'type'             => 'league',
			'event_name'       => 'Mens',
			'event_id'         => 5,
			'competition_name' => 'Summer',
			'competition_id'   => 1,
			'team_type'        => 'standard',
		);
		$this->db->get_results_return = array( $winner_row );

		$fixture_repo   = $this->createStub( Fixture_Repository_Interface::class );
        $fixture = new Fixture();
        $fixture->set_teams( array( 'home' => (object) array(), 'away' => (object) array() ) );
		$fixture_repo->method( 'find_by_id' )->willReturn( $fixture );

		$repo    = new Competition_Repository( $fixture_repo );
		$winners = $repo->get_winners( '2026', 1 );

		$this->assertIsArray( $winners );
		$this->assertCount( 1, $winners );
		$this->assertSame( 'Division 1', $winners[0]->league_name );
		$this->assertSame( 'Division 1', $winners[0]->league_title );
		$this->assertSame( 'Division 1', $winners[0]->league );
	}

	public function test_event_repository_query_teams_projections_and_default_orderby(): void {
		$this->db->get_results_return = array();
		$repo                         = new Event_Repository( $this->db );

		$repo->query_teams( 5 );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( '`l`.`title` AS `league_name`', $sql );
		$this->assertStringContainsString( '`l`.`title` AS `league_title`', $sql );
		$this->assertStringContainsString( 'ORDER BY `league_name` ASC,`name` ASC', $sql );
	}

	public function test_event_repository_query_teams_normalizes_legacy_league_title_orderby(): void {
		$this->db->get_results_return = array();
		$repo                         = new Event_Repository( $this->db );

		$repo->query_teams( 5, array( 'orderby' => array( 'league_title' => 'DESC' ) ) );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'ORDER BY `league_name` DESC', $sql );
	}

	public function test_event_repository_query_constitutions_dual_projection(): void {
		$this->db->get_results_return = array();
		$repo                         = new Event_Repository( $this->db );

		$repo->query_constitution( 5, array( 'oldseason' => '2025' ) );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( '`l`.`title` AS `league_name`', $sql );
		$this->assertStringContainsString( '`l`.`title` AS `league_title`', $sql );

		$repo->query_constitutions( 5, array( 'oldseason' => '2025' ) );
		$this->assertStringContainsString( '`l`.`title` AS `league_name`', $this->db->last_sql() );
	}

	public function test_event_repository_build_constitution_dual_projection(): void {
		$this->db->get_results_return = array();
		$repo                         = new Event_Repository( $this->db );

		$repo->build_constitution( 5 );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( '`l`.`title` AS `old_league_name`', $sql );
		$this->assertStringContainsString( '`l`.`title` AS `old_league_title`', $sql );
	}

	public function test_league_repository_find_all_supports_league_name_filter_and_orderby_normalization(): void {
		$this->db->get_results_return = array();
		$repo                         = new League_Repository( $this->db );

		$repo->find_all( array(
			'league_name' => 'Premier',
			'orderby'     => array( 'league_name' => 'DESC' ),
		) );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( '`title` like %s', $sql );
		$like_entry = null;
		foreach ( $this->db->prepared as $entry ) {
			if ( str_contains( $entry['sql'], '`title` like %s' ) ) {
				$like_entry = $entry;
				break;
			}
		}
		$this->assertNotNull( $like_entry );
		$this->assertSame( 'Premier%', $like_entry['args'][0] );
		$this->assertStringContainsString( 'ORDER BY `title` DESC', $sql );

		$repo->find_all( array(
			'orderby' => array( 'league_title' => 'ASC' ),
		) );
		$this->assertStringContainsString( 'ORDER BY `title` ASC', $this->db->last_sql() );
	}

	public function test_league_repository_find_by_title_and_get_by_event_id_projections(): void {
		$this->db->get_row_return     = null;
		$this->db->get_results_return = array();
		$repo                         = new League_Repository( $this->db );

		$repo->find_by_title( 'Division 2' );
		$this->assertStringContainsString( 'SELECT * FROM wp_racketmanager_leagues WHERE `title` = %s', $this->db->last_sql() );

		$repo->get_by_event_id( 3 );
		$sql = $this->db->last_sql();
		$this->assertStringContainsString( '`title` AS `name`', $sql );
		$this->assertStringContainsString( '`title` AS `league_name`', $sql );
		$this->assertStringContainsString( '`title` AS `league_title`', $sql );
	}

	public function test_tournament_repository_find_leagues_by_event_dual_projection(): void {
		$this->db->get_results_return = array();
		$repo                         = new Tournament_Repository();

		$repo->find_leagues_by_event_for_tournament( 12 );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( 'l.title as league_name', $sql );
		$this->assertStringContainsString( 'l.title as league_title', $sql );
	}

	public function test_player_repository_get_titles_projects_league_name_and_legacy_title(): void {
		$this->db->get_results_return = array();
		$repo                         = new Player_Repository( $this->db );

		$repo->get_titles( 7 );

		$sql = $this->db->last_sql();
		$this->assertStringContainsString( "l.`title` as `league_name`", $sql );
		$this->assertStringContainsString( "l.`title` as `title`", $sql );
		$this->assertStringContainsString( "t.`name` as `tournament`", $sql );
		$this->assertStringContainsString( "e.`name` as `draw`", $sql );
		$this->assertStringNotContainsString( "as `name`", $sql );
	}

	public function test_fixture_find_all_supports_league_name_and_league_title_filters(): void {
		$this->db->get_results_return = array();
		$fixture_repo                 = $this->createStub( Fixture_Repository_Interface::class );
		$query                        = new Fixture_Find_All_Query( $this->db, 'wp_racketmanager_matches', $fixture_repo, null );

		$query->execute( array( 'league_name' => 'Division 1' ) );
		$this->assertStringContainsString( "WHERE `title` = 'Division 1'", $this->db->last_query );

		$query->execute( array( 'league_title' => 'Division 2' ) );
		$this->assertStringContainsString( "WHERE `title` = 'Division 2'", $this->db->last_query );
	}
}
