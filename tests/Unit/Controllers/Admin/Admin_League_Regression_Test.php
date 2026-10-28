<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

/**
 * Regression tests for Admin_League display flows: missing entities, injected collaborators, standings template.
 */
#[AllowMockObjectsWithoutExpectations]
final class Admin_League_Regression_Test extends TestCase {
	use Creates_Admin_Delivery_Container;
	use Creates_Event_Query_Service;
	use Manages_Wordpress_Globals;

	private Simple_Container $container;
	private RacketManager $plugin;
	/** @var string[] */
	private array $warnings = array();

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_current_user_can'] = true;
		$GLOBALS['wp_stubs_leagues']          = array();
		$GLOBALS['wp_stubs_events']           = array();
		$_GET                                 = array();
		$_POST                                = array();
		$_SERVER['REQUEST_METHOD']            = 'GET';

		$this->container          = $this->create_admin_delivery_container(
			renderer: new \Racketmanager\Services\View\Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' )
		);
		$this->plugin             = $this->createStub( RacketManager::class );
		$this->plugin->container  = $this->container;
		$GLOBALS['racketmanager'] = $this->plugin;
		$this->warnings           = array();
		set_error_handler(
			function ( int $severity, string $message ): bool {
				$this->warnings[] = $message;
				return true;
			}
		);
	}

	private function screen(): Admin_League {
		return $this->container->get( 'admin_league_screen' );
	}

	protected function tearDown(): void {
		restore_error_handler();
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_every_league_view_resolves_its_controller_from_the_container(): void {
		$this->assertInstanceOf( Admin_League::class, $this->container->get( 'admin_league_screen' ) );
		foreach (
			array(
				'league_competitions_admin_controller'  => \Racketmanager\Admin\Controllers\League_Competitions_Admin_Controller::class,
				'league_overview_admin_controller'      => \Racketmanager\Admin\Controllers\League_Overview_Admin_Controller::class,
				'league_setup_admin_controller'         => \Racketmanager\Admin\Controllers\League_Setup_Admin_Controller::class,
				'league_setup_event_admin_controller'   => \Racketmanager\Admin\Controllers\League_Setup_Event_Admin_Controller::class,
				'league_event_admin_controller'         => \Racketmanager\Admin\Controllers\League_Event_Admin_Controller::class,
				'league_constitution_admin_controller'  => \Racketmanager\Admin\Controllers\League_Constitution_Admin_Controller::class,
				'league_standings_admin_controller'     => \Racketmanager\Admin\Controllers\League_Standings_Admin_Controller::class,
				'league_matches_admin_controller'       => \Racketmanager\Admin\Controllers\League_Matches_Admin_Controller::class,
				'league_match_admin_controller'         => \Racketmanager\Admin\Controllers\League_Match_Admin_Controller::class,
				'league_schedule_admin_controller'      => \Racketmanager\Admin\Controllers\League_Schedule_Admin_Controller::class,
				'teams_admin_controller'                => \Racketmanager\Admin\Controllers\Teams_Admin_Controller::class,
				'contact_admin_controller'              => \Racketmanager\Admin\Controllers\Contact_Admin_Controller::class,
				'competition_seasons_admin_controller'  => \Racketmanager\Admin\Controllers\Competition_Seasons_Admin_Controller::class,
			) as $id => $class
		) {
			$this->assertInstanceOf( $class, $this->container->get( $id ), $id );
		}
	}

	public function test_crosstable_teams_are_hydrated_and_cells_precomputed_for_the_template(): void {
		$hydrated        = League_Team::from_object( (object) array( 'id' => 22, 'title' => 'Leigh 2' ) );
		$already_entity  = League_Team::from_object( (object) array( 'id' => 11, 'title' => 'Westcliff 1' ) );
		$raw_row         = (object) array( 'id' => 22, 'table_id' => 22, 'title' => 'Leigh 2' );
		$league          = $this->createStub( \Racketmanager\Domain\Competition\League::class );
		$league->id      = 7;

		$league_team_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface::class );
		$league_team_repository->expects( self::once() )->method( 'hydrate' )->with( $raw_row )->willReturn( $hydrated );

		$league_query_service = $this->createMock( League_Query_Service::class );
		$league_query_service->expects( self::exactly( 4 ) )
			->method( 'get_crosstable_field' )
			->willReturnCallback( static fn( $l, int $team_id, int $opponent_id ): string => $team_id === $opponent_id ? '&nbsp;' : "{$team_id}v{$opponent_id}" );

		$this->container->set( 'league_team_repository', fn() => $league_team_repository );
		$this->container->set( 'league_query_service', fn() => $league_query_service );
		$screen = $this->container->get( 'league_standings_admin_controller' );

		$hydrate = new \ReflectionMethod( $screen, 'hydrate_crosstable_teams' );
		$teams   = $hydrate->invoke( $screen, array( $already_entity, $raw_row, 'not-a-team' ) );
		$this->assertSame( array( $already_entity, $hydrated ), $teams );

		$build = new \ReflectionMethod( $screen, 'build_crosstable_cells' );
		$this->assertSame(
			array(
				11 => array( 11 => '&nbsp;', 22 => '11v22' ),
				22 => array( 11 => '22v11', 22 => '&nbsp;' ),
			),
			$build->invoke( $screen, $league, $teams )
		);
		$this->assertSame( array(), $this->warnings );
	}

	/**
	 * Controller-to-template contract: display_league_page() hands the hydrated teams and the
	 * pre-computed score matrix to templates/admin/league/crosstable.php (which no longer calls
	 * get_league_team()/get_league_query_service()).
	 *
	 * Runs in its own process because show-league.php includes its tab partials with require_once.
	 */
	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	#[\PHPUnit\Framework\Attributes\PreserveGlobalState( false )]
	public function test_display_league_page_renders_precomputed_crosstable_for_non_championship_league(): void {
		$_GET        = array( 'league_id' => '42', 'season' => '2026', 'tab' => 'crosstable' );
		$competition = Competition_Test_Factory::create( id: 1, name: 'Premier League', type: Competition_Type::LEAGUE );
		$event       = \Racketmanager\Domain\Competition\Event::from_object(
			(object) array(
				'id'      => 93,
				'name'    => 'Mixed Doubles',
				'seasons' => array( '2026' => array( 'name' => '2026' ) ),
			),
			$competition
		);
		$league      = \Racketmanager\Domain\Competition\League::from_object( (object) array( 'id' => 42, 'title' => 'Division 1' ), $event );
		$league->set_season( '2026' );
		$league->num_teams = 2;
		$GLOBALS['wp_stubs_leagues'][42] = $league;

		$westcliff = League_Team::from_object( $this->league_table_row( 11, 'Westcliff 1', 1 ) );
		$raw_leigh = $this->league_table_row( 22, 'Leigh 2', 2 );
		$leigh     = League_Team::from_object( $this->league_table_row( 22, 'Leigh 2', 2 ) );

		$league_team_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface::class );
		$league_team_repository->expects( self::once() )->method( 'hydrate' )->with( $raw_leigh )->willReturn( $leigh );
		$this->container->set( 'league_team_repository', fn() => $league_team_repository );

		$league_query_service = $this->createMock( League_Query_Service::class );
		$league_query_service->method( 'get_league_teams' )->willReturnCallback(
			static function ( object $l ) use ( $westcliff, $raw_leigh ): array {
				$l->teams = array( $westcliff, $raw_leigh );
				return $l->teams;
			}
		);
		$league_query_service->method( 'get_matches' )->willReturn( array() );
		$league_query_service->expects( self::exactly( 4 ) )
			->method( 'get_crosstable_field' )
			->with( $league, self::anything(), self::anything() )
			->willReturnCallback( static fn( $l, int $team_id, int $opponent_id ): string => $team_id === $opponent_id ? '&nbsp;' : "{$team_id}v{$opponent_id}" );

		$this->container->set( 'league_query_service', fn() => $league_query_service );

		ob_start();
		try {
			$this->screen()->handle_display( 'league' );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( "activaTab('crosstable')", $output );
		$this->assertStringContainsString( 'aria-describedby="Crosstable Division 1"', $output );
		$this->assertMatchesRegularExpression( '/<th scope="row">1<\/th>\s*<td>Westcliff 1<\/td>\s*<td class="fixture">&nbsp;<\/td>\s*<td class="fixture">11v22<\/td>/', $output );
		$this->assertMatchesRegularExpression( '/<th scope="row">2<\/th>\s*<td>Leigh 2<\/td>\s*<td class="fixture">22v11<\/td>\s*<td class="fixture">&nbsp;<\/td>/', $output );
	}

	public function test_display_match_page_with_missing_league_shows_not_found_message(): void {
		$_GET = array( 'league_id' => '999', 'season' => '2026' );

		ob_start();
		try {
			$this->screen()->handle_display( 'match' );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'League not found', $output );
	}

	public function test_display_league_page_with_missing_league_renders_without_warnings(): void {
		$_GET = array( 'league_id' => '999', 'season' => '2026' );

		ob_start();
		try {
			$this->screen()->handle_display( 'league' );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'League not found', $output );
	}

	public function test_display_event_page_renders_with_injected_event_repository(): void {
		$competition           = Competition_Test_Factory::create( 5, 'Premier League', Competition_Type::LEAGUE );
		$event                 = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
		$event->id             = 10;
		$event->name           = 'Mixed Doubles';
		$event->competition    = $competition;
		$event->current_season = array( 'name' => '2026' );
		$event->method( 'get_seasons' )->willReturn( array( '2026' => array( 'name' => '2026' ) ) );

		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( self::atLeastOnce() )->method( 'find_by_id' )->with( 10 )->willReturn( $event );

		$this->container->set( 'event_repository', fn() => $event_repository );
		$_GET         = array( 'event_id' => '10', 'season' => '2026' );

		ob_start();
		try {
			$this->screen()->handle_display( 'event' );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Leagues', $output );
		$this->assertStringContainsString( 'Mixed Doubles', $output );
	}

	public function test_display_event_page_with_invalid_event_id_renders_error_without_fatal(): void {
		$_GET = array( 'event_id' => '0', 'season' => '2026' );

		ob_start();
		try {
			$this->screen()->handle_display( 'event' );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Event', $output );
	}

	public function test_display_setup_event_page_renders_using_injected_repository(): void {
		$competition           = Competition_Test_Factory::create( 5, 'Premier League', Competition_Type::LEAGUE );
		$event                 = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
		$event->id             = 10;
		$event->name           = 'Mixed Doubles';
		$event->competition    = $competition;
		$event->current_season = array( 'name' => '2026' );
		$event->method( 'get_season_by_name' )->willReturn(
			array(
				'name'           => '2026',
				'match_dates'    => array( '2026-05-01' ),
				'home_away'      => 0,
				'num_match_days' => 1,
			)
		);
		$GLOBALS['wp_stubs_events'][10] = $event;

		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( self::once() )->method( 'find_by_id' )->with( 10 )->willReturn( $event );

		$this->container->set( 'event_repository', fn() => $event_repository );
		$_GET         = array( 'event_id' => '10', 'season' => '2026' );

		ob_start();
		try {
			$this->screen()->handle_display( 'setup-event' );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Match dates', $output );
		$this->assertStringContainsString( 'name="event_id" value="10"', $output );
	}

	public function test_display_matches_page_orders_matches_by_home_team_asc(): void {
		$competition           = Competition_Test_Factory::create( 5, 'Premier League', Competition_Type::LEAGUE );
		$event                 = $this->createMock( \Racketmanager\Domain\Competition\Event::class );
		$event->id             = 10;
		$event->name           = 'Mixed Doubles';
		$event->competition    = $competition;
		$event->current_season = array( 'name' => '2026' );
		$event->method( 'get_seasons' )->willReturn( array( '2026' => array( 'name' => '2026' ) ) );
		$GLOBALS['wp_stubs_events'][10] = $event;

		$event_repository = $this->createStub( Event_Repository_Interface::class );
		$event_repository->method( 'find_by_id' )->willReturn( $event );

		$captured_args       = null;
		$event_query_service = $this->createMock( Event_Query_Service::class );
		$event_query_service->expects( self::once() )
			->method( 'get_matches' )
			->with(
				$event,
				self::callback( static function ( array $args ) use ( &$captured_args ): bool {
					$captured_args = $args;
					return true;
				} )
			)
			->willReturn( array() );

		$this->container->set( 'event_repository', fn() => $event_repository );
		$this->container->set( 'event_query_service', fn() => $event_query_service );
		$_GET         = array( 'event_id' => '10', 'season' => '2026' );

		ob_start();
		try {
			$this->screen()->handle_display( 'matches' );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertIsArray( $captured_args );
		$this->assertSame(
			array(
				'match_day' => 'ASC',
				'date'      => 'ASC',
				'home_team' => 'ASC',
				'league_id' => 'ASC',
			),
			$captured_args['orderby'] ?? null
		);
	}

	public function test_standings_template_marks_withdrawn_team_using_profile_getter(): void {
		$withdrawn = League_Team::from_object(
			(object) array(
				'table_id'      => 1,
				'id'            => 1,
				'title'         => 'Withdrawn Team',
				'profile'       => 3,
				'rank'          => 1,
				'old_rank'      => 1,
				'points_plus'   => 0,
				'points_minus'  => 0,
				'add_points'    => 0,
				'done_matches'  => 0,
				'won_matches'   => 0,
				'draw_matches'  => 0,
				'lost_matches'  => 0,
				'status'        => '',
				'group'         => '',
				'rating'        => null,
				'games_won'     => 0,
				'games_allowed' => 0,
				'sets_won'      => 0,
				'sets_allowed'  => 0,
			)
		);
		$active    = League_Team::from_object(
			(object) array(
				'table_id'      => 2,
				'id'            => 2,
				'title'         => 'Active Team',
				'profile'       => 0,
				'rank'          => 2,
				'old_rank'      => 2,
				'points_plus'   => 0,
				'points_minus'  => 0,
				'add_points'    => 0,
				'done_matches'  => 0,
				'won_matches'   => 0,
				'draw_matches'  => 0,
				'lost_matches'  => 0,
				'status'        => '',
				'group'         => '',
				'rating'        => null,
				'games_won'     => 0,
				'games_allowed' => 0,
				'sets_won'      => 0,
				'sets_allowed'  => 0,
			)
		);

		$output = $this->render_standings( array( $withdrawn, $active ) );

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Withdrawn Team has withdrawn', $output );
		$this->assertStringNotContainsString( 'Active Team has withdrawn', $output );
		$this->assertStringContainsString( 'Active Team', $output );
	}

	public function test_standings_template_supports_legacy_profile_property(): void {
		$legacy = (object) array(
			'table_id'      => 3,
			'id'            => 3,
			'title'         => 'Legacy Team',
			'profile'       => '3',
			'rank'          => 1,
			'old_rank'      => 1,
			'points_plus'   => 0,
			'points_minus'  => 0,
			'add_points'    => 0,
			'played'        => 0,
			'won'           => 0,
			'draw'          => 0,
			'lost'          => 0,
			'done_matches'  => 0,
			'won_matches'   => 0,
			'draw_matches'  => 0,
			'lost_matches'  => 0,
			'status'        => '',
			'group'         => '',
			'rating'        => '',
			'games_won'     => 0,
			'games_allowed' => 0,
			'sets_won'      => 0,
			'sets_allowed'  => 0,
		);

		$output = $this->render_standings( array( $legacy ) );

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Legacy Team has withdrawn', $output );
	}

	/**
	 * Minimal league-table row (the standings partial reads every column).
	 */
	private function league_table_row( int $id, string $title, int $rank ): \stdClass {
		return (object) array(
			'table_id'      => $id,
			'id'            => $id,
			'title'         => $title,
			'profile'       => 0,
			'rank'          => $rank,
			'old_rank'      => $rank,
			'points_plus'   => 0,
			'points_minus'  => 0,
			'add_points'    => 0,
			'done_matches'  => 0,
			'won_matches'   => 0,
			'draw_matches'  => 0,
			'lost_matches'  => 0,
			'status'        => '',
			'group'         => '',
			'rating'        => null,
			'games_won'     => 0,
			'games_allowed' => 0,
			'sets_won'      => 0,
			'sets_allowed'  => 0,
		);
	}

	/**
	 * @param object[] $teams
	 */
	private function render_standings( array $teams ): string {
		$competition                 = new \stdClass();
		$competition->is_championship = false;
		$competition->is_player_entry = false;
		$competition->type           = 'league';
		$competition->settings       = array( 'team_ranking' => 'auto' );
		$event                       = new \stdClass();
		$event->competition          = $competition;
		$league                      = new \stdClass();
		$league->id                  = 42;
		$league->event               = $event;
		$league->is_championship     = false;
		$league->groups              = array();
		$league->standings           = array();
		$league->point_rule          = 'two';
		$league->point_format        = '%d:%d';
		$league->sport               = 'tennis';
		$season                      = '2026';

		ob_start();
		try {
			( static function () use ( $league, $teams, $season ): void {
				require dirname( __DIR__, 4 ) . '/templates/admin/league/standings.php';
			} )();
			return (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}
}
