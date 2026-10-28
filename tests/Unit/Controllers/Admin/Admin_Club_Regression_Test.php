<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Admin\Security\Wp_Action_Guard;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

/**
 * Regression tests for Admin_Club display flows: team page, missing team handling, injected services.
 */
#[AllowMockObjectsWithoutExpectations]
final class Admin_Club_Regression_Test extends TestCase {
	use Creates_Admin_Delivery_Container;
	use Manages_Wordpress_Globals;

	private Simple_Container $container;
	private RacketManager $plugin;
	private Club_Service $club_service;
	private League_Service $league_service;
	private Team_Service $team_service;
	private Tournament_Service $tournament_service;
	private Registration_Service $registration_service;
	private League_Query_Service $league_query_service;
	private Admin_Club $screen;
	/** @var string[] */
	private array $warnings = array();

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_current_user_can'] = true;
		$GLOBALS['wp_stubs_clubs']            = array();
		$GLOBALS['wp_stubs_leagues']          = array();
		$_GET                                 = array();
		$_POST                                = array();
		$_SERVER['REQUEST_METHOD']            = 'GET';

		$this->club_service         = $this->createStub( Club_Service::class );
		$this->league_service       = $this->createStub( League_Service::class );
		$this->team_service         = $this->createStub( Team_Service::class );
		$this->tournament_service   = $this->createStub( Tournament_Service::class );
		$this->registration_service = $this->createStub( Registration_Service::class );
		$this->league_query_service = $this->createStub( League_Query_Service::class );

		$this->container = $this->create_admin_delivery_container(
			services: array(
				'club_service'         => $this->club_service,
				'league_service'       => $this->league_service,
				'team_service'         => $this->team_service,
				'tournament_service'   => $this->tournament_service,
				'registration_service' => $this->registration_service,
				'league_query_service' => $this->league_query_service,
			),
			guard: new Wp_Action_Guard(),
			renderer: new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' )
		);

		$this->plugin             = $this->createStub( RacketManager::class );
		$this->plugin->container  = $this->container;
		$GLOBALS['racketmanager'] = $this->plugin;
		$this->screen             = $this->container->get( 'admin_club_screen' );
		$this->warnings           = array();

		set_error_handler(
			function ( int $severity, string $message ): bool {
				$this->warnings[] = $message;
				return true;
			}
		);
	}

	protected function tearDown(): void {
		restore_error_handler();
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_display_team_page_with_no_team_specified_shows_message(): void {
		$_GET = array();

		ob_start();
		try {
			$this->screen->display_team_page();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Team not specified', $output );
	}

	public function test_display_team_page_with_missing_team_handles_exception_cleanly(): void {
		$_GET = array( 'edit' => '999999', 'club_id' => '1' );

		$this->team_service->method( 'get_team_by_id' )
			->willThrowException( new Team_Not_Found_Exception( 'Team not found' ) );

		ob_start();
		try {
			$this->screen->display_team_page();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Team not found', $output );
	}

	public function test_display_team_page_with_missing_league_shows_league_not_found(): void {
		$_GET = array( 'edit' => '10', 'league_id' => '999999' );

		$this->league_service->method( 'get_league' )
			->willReturn( null );

		ob_start();
		try {
			$this->screen->display_team_page();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'League not found', $output );
	}

	public function test_display_team_page_renders_club_team_form_using_injected_services(): void {
		$_GET = array( 'edit' => '10', 'club_id' => '1' );

		$team         = new Team();
		$team->id     = 10;
		$team->title  = 'Men 1st';
		$team->roster = array();

		$this->team_service->method( 'get_team_by_id' )
			->willReturn( $team );

		$club = Club_Test_Factory::create( id: 1, name: 'Leigh LTC', shortcode: 'LLTC' );
		$this->club_service->method( 'get_clubs' )
			->willReturn( array( $club ) );

		ob_start();
		try {
			$this->screen->display_team_page();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Edit Team', $output );
		$this->assertStringContainsString( 'Men 1st', $output );
		$this->assertStringContainsString( 'Leigh LTC', $output );
		$this->assertStringContainsString( 'name="club_id" value="1"', $output );
	}

	public function test_display_team_page_with_league_id_uses_league_service_and_league_query_service(): void {
		$_GET = array( 'edit' => '10', 'league_id' => '42', 'season' => '2026' );

		$competition                  = new stdClass();
		$competition->type            = 'league';
		$competition->is_player_entry = false;

		$event              = new stdClass();
		$event->competition = $competition;

		$league             = new stdClass();
		$league->id         = 42;
		$league->title      = 'Division 1';
		$league->event      = $event;
		$league->sport      = 'tennis';
		$league->type       = 'MD';

		$team               = new stdClass();
		$team->id           = 10;
		$team->title        = 'Division 1 Team A';
		$team->captain      = 'John Doe';
		$team->captain_id   = '123';
		$team->contactno    = '0123456789';
		$team->contactemail = 'john@example.com';
		$team->match_time   = '18:30';
		$team->match_day    = 'Mon';
		$team->club_id      = '1';
		$team->type         = 'MD';
		$team->roster       = array();

		$this->league_service->method( 'get_league' )
			->willReturn( $league );

		$this->league_query_service->method( 'get_team_dtls' )
			->willReturn( $team );

		$club = Club_Test_Factory::create( id: 1, name: 'Leigh LTC', shortcode: 'LLTC' );
		$this->club_service->method( 'get_clubs' )
			->willReturn( array( $club ) );

		ob_start();
		try {
			$this->screen->display_team_page();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Division 1', $output );
		$this->assertStringContainsString( 'Division 1 Team A', $output );
		$this->assertStringContainsString( 'John Doe', $output );
		$this->assertStringNotContainsString( 'name="club_id"', $output );
		$this->assertStringContainsString( '18:30', $output );
	}

	public function test_display_team_page_with_player_entry_renders_player_team_template(): void {
		$_GET = array(
			'edit'       => '10',
			'league_id'  => '42',
			'season'     => '2026',
			'tournament' => '3',
		);

		$competition                  = new stdClass();
		$competition->type            = 'tournament';
		$competition->is_player_entry = true;

		$event              = new stdClass();
		$event->competition = $competition;

		$league             = new stdClass();
		$league->id         = 42;
		$league->title      = 'Singles Draw';
		$league->event      = $event;
		$league->type       = 'MS';
		$league->sport      = 'tennis';

		$tournament         = new \Racketmanager\Domain\Tournament\Tournament();
		$tournament->id     = 3;
		$tournament->name   = 'Club Championship';
		$tournament->season = '2026';

		$team               = new stdClass();
		$team->id           = 10;
		$team->title        = 'Player One';
		$team->captain      = 'Player One';
		$team->captain_id   = '101';
		$team->contactno    = '0123456789';
		$team->contactemail = 'player@example.com';
		$team->club_id      = '1';
		$team->player       = array( '1' => 'Player One' );
		$team->player_id    = array( '1' => '101' );
		$team->roster       = array();

		$this->league_service->method( 'get_league' )
			->willReturn( $league );

		$this->tournament_service->method( 'get_tournament' )
			->willReturn( $tournament );

		$this->league_query_service->method( 'get_team_dtls' )
			->willReturn( $team );

		$club = Club_Test_Factory::create( id: 1, name: 'Leigh LTC', shortcode: 'LLTC' );
		$this->club_service->method( 'get_clubs' )
			->willReturn( array( $club ) );

		ob_start();
		try {
			$this->screen->display_team_page();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'Singles Draw', $output );
		$this->assertStringContainsString( 'Player One', $output );
		$this->assertStringContainsString( 'player@example.com', $output );
		$this->assertStringContainsString( 'actionPlayerTeam', $output );
	}

	public function test_display_team_page_without_permission_shows_invalid_permissions(): void {
		$GLOBALS['wp_stubs_current_user_can'] = false;

		ob_start();
		try {
			$this->screen->display_team_page();
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertSame( array(), $this->warnings );
		$this->assertStringContainsString( 'You do not have permission to perform this task', $output );
	}
}
