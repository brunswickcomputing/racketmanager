<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Seo;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Seo\Page_Title_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Page_Title_Service_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Page_Title_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->service = new Page_Title_Service( 'My Club' );
		global $wp;
		$wp = new stdClass();
		$wp->query_vars = [];
		$GLOBALS['post'] = (object) [ 'post_name' => '' ];
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_player_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'player';
		$wp->query_vars['player_id'] = 'roger-federer';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Roger Federer - My Club', $title );
	}

	public function test_event_slug_title_with_player(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'event';
		$wp->query_vars['event'] = 'mens-singles';
		$wp->query_vars['season'] = '2026';
		$wp->query_vars['player_id'] = 'rafael-nadal';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Rafael Nadal - Mens Singles 2026 - My Club', $title );
	}

	public function test_event_slug_title_with_club(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'event';
		$wp->query_vars['event'] = 'mens-singles';
		$wp->query_vars['club_name'] = 'westcliff';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Westcliff - Mens Singles - My Club', $title );
	}

	public function test_event_slug_title_event_only(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'event';
		$wp->query_vars['event'] = 'mens-singles';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Mens Singles - My Club', $title );
	}

	public function test_competitions_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'competitions';
		$wp->query_vars['type'] = 'league';
		$wp->query_vars['club_name'] = 'leigh';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'League List - Leigh - My Club', $title );

		// Without type
		unset( $wp->query_vars['type'], $wp->query_vars['club_name'] );
		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Competitions - My Club', $title );
	}

	public function test_competition_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'competition';
		$wp->query_vars['competition'] = 'summer-league';
		$wp->query_vars['season'] = '2026';
		$wp->query_vars['club_name'] = 'westcliff';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Summer League  2026 - Westcliff - My Club', $title );

		// Default competition
		unset( $wp->query_vars['competition'], $wp->query_vars['season'], $wp->query_vars['club_name'] );
		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Competition - My Club', $title );
	}

	public function test_latest_results_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'latest-results';
		$wp->query_vars['competition_type'] = 'league';
		$wp->query_vars['competition_name'] = 'summer';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Latest League Results - Summer - My Club', $title );
	}

	public function test_cup_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'cup';
		$wp->query_vars['event'] = 'charity-cup';
		$wp->query_vars['season'] = '2026';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Charity Cup - 2026 - My Club', $title );
	}

	public function test_league_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'league';
		$wp->query_vars['league_name'] = 'division-1';
		$wp->query_vars['team'] = 'team-a';
		$wp->query_vars['season'] = '2026';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Team A - Division 1 - 2026 - My Club', $title );
	}

	public function test_team_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'team';
		$wp->query_vars['team'] = 'leigh-1';
		$wp->query_vars['competition_name'] = 'main';
		$wp->query_vars['event'] = 'mens-doubles';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Leigh 1 - Main - Mens Doubles - My Club', $title );
	}

	public function test_match_slug_title_with_teams(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'match';
		$wp->query_vars['league_name'] = 'division-1';
		$wp->query_vars['season'] = '2026';
		$wp->query_vars['teamHome'] = 'team-home';
		$wp->query_vars['teamAway'] = 'team-away';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Team Home vs Team Away - Division 1 - 2026 - My Club', $title );
	}

	public function test_entry_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'entry';
		$wp->query_vars['competition_name'] = 'annual-open';
		$wp->query_vars['season'] = '2026';
		$wp->query_vars['club_name'] = 'westcliff';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'Entry Form - Annual Open - 2026 - Westcliff - My Club', $title );
	}

	public function test_tournament_slug_title(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'tournament';
		$wp->query_vars['tournament'] = 'annual-closed';
		$wp->query_vars['player'] = 'john-smith';
		$wp->query_vars['draw'] = 'championship';
		$wp->query_vars['event'] = 'singles';
		$wp->query_vars['tab'] = 'matches';

		$title = $this->service->build_title( 'Default Title' );
		$this->assertSame( 'John Smith - Player - Championship Draw - Singles Event - Annual Closed - Tournament - My Club', $title );
	}

	public function test_club_invoices_roles_players_slug_titles(): void {
		global $wp;

		// Club
		$GLOBALS['post']->post_name = 'club';
		$wp->query_vars['club_name'] = 'westcliff';
		$this->assertSame( 'Westcliff - My Club', $this->service->build_title( 'Default' ) );

		// Invoices
		$GLOBALS['post']->post_name = 'invoices';
		$wp->query_vars['invoice'] = 'inv-1001';
		$this->assertSame( 'Invoice - Inv 1001 - Westcliff - My Club', $this->service->build_title( 'Default' ) );

		// Roles
		$GLOBALS['post']->post_name = 'roles';
		$this->assertSame( 'Roles - Westcliff - My Club', $this->service->build_title( 'Default' ) );

		// Players
		$GLOBALS['post']->post_name = 'players';
		$wp->query_vars['player_id'] = 'jane-doe';
		$this->assertSame( 'Jane Doe - Westcliff - My Club', $this->service->build_title( 'Default' ) );
	}

	public function test_site_name_override_parameter(): void {
		global $wp;
		$GLOBALS['post']->post_name = 'player';
		$wp->query_vars['player_id'] = 'roger-federer';

		$title = $this->service->build_title( 'Default Title', 'Overridden Site Name' );
		$this->assertSame( 'Roger Federer - Overridden Site Name', $title );
	}
}
