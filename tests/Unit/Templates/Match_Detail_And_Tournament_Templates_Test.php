<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Matches\DTOs\Match_Detail_Result;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Option_Request;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use ReflectionClass;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;
use stdClass;

class Match_Detail_And_Tournament_Templates_Test extends TestCase {
	use Creates_Team_Match_Row_Presenter;

	private Php_View_Renderer $renderer;
	private Shortcode_Template_Renderer $template_renderer;

	protected function setUp(): void {
		parent::setUp();
		$template_path           = defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH . 'templates/' : dirname( __DIR__, 3 ) . '/templates/';
		$this->renderer          = new Php_View_Renderer( $template_path );
		$this->template_renderer = new Shortcode_Template_Renderer( $this->renderer );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_stubs_is_user_logged_in'] );
		parent::tearDown();
	}

	/**
	 * @return array{0: mixed, 1: list<string>}
	 */
	private function with_warning_capture( callable $callback ): array {
		$warnings = [];
		set_error_handler( static function ( int $errno, string $errstr, string $file = '', int $line = 0 ) use ( &$warnings ): bool {
			$warnings[] = $errno . ' ' . $errstr . ' ' . basename( $file ) . ':' . $line;
			return true;
		} );
		try {
			$result = $callback();
		} finally {
			restore_error_handler();
		}
		return [ $result, $warnings ];
	}

	private function make_club_player( string $display_name, ?bool $system_record = null ): object {
		return new class( $display_name, $system_record ) {
			public function __construct(
				public string $display_name,
				public ?bool $system_record = null,
				public ?string $class = null,
				public mixed $description = null,
			) {}
		};
	}

	private function make_rubber( int $id, int $number, string $type, array $players, int $winner_id = 10 ): Rubber {
		$rubber = ( new ReflectionClass( Rubber::class ) )->newInstanceWithoutConstructor();
		$rubber->id            = $id;
		$rubber->rubber_number = $number;
		$rubber->type          = $type;
		$rubber->players       = $players;
		$rubber->winner_id     = $winner_id;
		$rubber->loser_id      = 20;
		$rubber->date          = '2026-10-15';
		$rubber->sets          = [
			1 => new Set_Score( home_games: 6, away_games: 4 ),
			2 => new Set_Score( home_games: 6, away_games: 2 ),
		];
		$rubber->is_abandoned  = false;
		$rubber->is_walkover   = false;
		$rubber->is_invalid    = false;
		$rubber->is_retired    = false;
		$rubber->is_shared     = false;
		return $rubber;
	}

	public function test_fixture_presenter_date_and_time_format_with_gateway(): void {
		$gateway = $this->createMock( Option_Gateway_Interface::class );
		$gateway->expects( $this->exactly( 2 ) )
			->method( 'get_option' )
			->willReturnMap( [
				[ 'date_format', 'Y-m-d', 'd/m/Y' ],
				[ 'time_format', 'H:i', 'g:i a' ],
			] );

		$link_service = $this->createStub( Fixture_Link_Service::class );
		$presenter    = new Fixture_Presenter( $link_service, $gateway );

		$this->assertSame( 'd/m/Y', $presenter->get_date_format() );
		$this->assertSame( 'g:i a', $presenter->get_time_format() );
	}

	public function test_fixture_presenter_date_and_time_format_fallback_when_empty(): void {
		$gateway = $this->createStub( Option_Gateway_Interface::class );
		$gateway->method( 'get_option' )->willReturn( '' );

		$link_service = $this->createStub( Fixture_Link_Service::class );
		$presenter    = new Fixture_Presenter( $link_service, $gateway );

		$this->assertSame( 'Y-m-d', $presenter->get_date_format() );
		$this->assertSame( 'H:i', $presenter->get_time_format() );
	}

	public function test_render_match_tournament_championship_draw_real_template(): void {
		$tournament = Tournament::from_object( (object) [
			'id'         => 101,
			'name'       => 'Summer Championship 2026',
			'date_start' => '2026-07-01',
			'date_end'   => '2026-07-10',
			'date'       => '2026-07-10',
		] );

		$player1 = Player_Factory::from_object( (object) [ 'id' => 1, 'display_name' => 'Roger Federer', 'first_name' => 'Roger', 'last_name' => 'Federer' ] );
		$player2 = Player_Factory::from_object( (object) [ 'id' => 2, 'display_name' => 'Rafael Nadal', 'first_name' => 'Rafael', 'last_name' => 'Nadal' ] );

		$team1 = Team::from_object( (object) [ 'id' => 10, 'title' => 'Roger Federer', 'players' => [ $player1 ] ] );
		$team2 = Team::from_object( (object) [ 'id' => 20, 'title' => 'Rafael Nadal', 'players' => [ $player2 ] ] );

		$home_team_dto = new Team_Details_DTO( $team1 );
		$away_team_dto = new Team_Details_DTO( $team2 );

		$fixture = Fixture_Test_Factory::create(
			id: 555,
			home_team: '10',
			away_team: '20',
			date: '2026-07-05 09:05:00',
			final: 'final',
			league_id: 202
		);
		$fixture->start_time = '09:05';
		$fixture->season     = '2026';

		$league                  = $this->createStub( League::class );
		$league->title           = 'Championship Singles';
		$league->is_championship = true;
		$league->scoring         = '3_sets';
		$league->num_sets        = 3;
		$league->num_rubbers     = 1;

		$event       = $this->createStub( Event::class );
		$event->name = 'Mens Singles';

		$competition       = $this->createStub( Competition::class );
		$competition->type = Competition_Type::TOURNAMENT;
		$competition->method( 'get_type' )->willReturn( 'tournament' );

		$club = $this->createStub( Club::class );
		$club->method( 'get_shortcode' )->willReturn( 'SW19' );

		$fixture_details = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $competition,
			home_team: $home_team_dto,
			away_team: $away_team_dto
		);

		$tournament_service = $this->createStub( Tournament_Service::class );
		$tournament_service->method( 'get_tournament_with_details_by_name' )
			->willReturn( new Tournament_Details_DTO( $tournament, $competition, $club ) );

		$fixture_service = $this->createStub( Fixture_Service::class );
		$fixture_service->method( 'get_tournament_fixture_with_details' )
			->willReturn( $fixture_details );
		$fixture_service->method( 'is_update_allowed' )
			->willReturn( (object) [ 'user_can_update' => true, 'user_type' => 'admin' ] );

		$presenter = new Tournament_Presenter( 'Y-m-d' );
		$handler   = new Get_Tournament_Match_Handler( $tournament_service, $fixture_service, $presenter );

		$query      = new Get_Tournament_Match_Query( 'summer-championship-2026', 555 );
		$view_model = $handler->handle( $query );

		$this->assertSame( 'match-tournament', $view_model->template );
		$this->assertSame( 'summer-championship-2026', $view_model->vars['tournament_seo_name'] );
		$this->assertSame( 'Final', $view_model->vars['round_name'] );
		$this->assertSame( 'Sun 5 Jul', $view_model->vars['match_date_formatted'] );
		$this->assertSame( '9:05', $view_model->vars['match_time_formatted'] );
		$this->assertSame( 'Draw', $view_model->vars['svg_link_text'] );
		$this->assertSame( 'Mens Singles', $view_model->vars['svg_link'] );
		$this->assertSame( '/tournament/summer-championship-2026/draw/mens-singles/', $view_model->vars['svg_link_location'] );
		$this->assertTrue( $view_model->vars['user_can_update'] );
		$this->assertSame( 'is-editable', $view_model->vars['match_editable'] );
		$this->assertTrue( $view_model->vars['allow_schedule_match'] );
		$this->assertFalse( $view_model->vars['allow_reset_match_result'] );
		$this->assertTrue( $view_model->vars['show_menu'] );
		$this->assertSame( 'assets/icons/lta-icons.svg#icon-bracket', $view_model->vars['image'] );

		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		[ $html, $warnings ] = $this->with_warning_capture( fn() => $this->renderer->render_to_string( 'match-tournament', $view_model, $view_model->vars ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( 'Summer Championship 2026', $html );
		$this->assertStringContainsString( 'Sun 5 Jul', $html );
		$this->assertStringContainsString( 'at 9:05', $html );
		$this->assertStringNotContainsString( 'at 09:05', $html );
		$this->assertStringContainsString( '2026-07-01 to 2026-07-10', $html );
		$this->assertStringContainsString( 'Draw', $html );
		$this->assertStringContainsString( '/tournament/summer-championship-2026/draw/mens-singles/', $html );
		$this->assertStringContainsString( 'Final', $html );
		$this->assertStringContainsString( 'Championship Singles', $html );
		$this->assertStringContainsString( 'id="tournamentName" value="summer-championship-2026"', $html );
		$this->assertStringContainsString( 'href="/tournament/summer-championship-2026/player/roger-federer"', $html );
		$this->assertStringContainsString( 'href="/tournament/summer-championship-2026/player/rafael-nadal"', $html );
		$this->assertStringContainsString( 'Roger Federer', $html );
		$this->assertStringContainsString( 'Rafael Nadal', $html );
		$this->assertStringContainsString( 'is-editable', $html );
		$this->assertStringContainsString( '(Re)schedule match', $html );
		$this->assertStringContainsString( 'data-match-option="schedule_match"', $html );
		$this->assertStringNotContainsString( 'Reset match result', $html );
	}

	private function build_tournament_match_view_model( ?string $referrer ): \Racketmanager\Presentation\View_Models\Shortcode_View_Model {
		$tournament = Tournament::from_object( (object) [
			'id'         => 101,
			'name'       => 'Summer Championship 2026',
			'date_start' => '2026-07-01',
			'date_end'   => '2026-07-10',
			'date'       => '2026-07-10',
		] );

		$player1 = Player_Factory::from_object( (object) [ 'id' => 1, 'display_name' => 'Roger Federer', 'first_name' => 'Roger', 'last_name' => 'Federer' ] );
		$player2 = Player_Factory::from_object( (object) [ 'id' => 2, 'display_name' => 'Rafael Nadal', 'first_name' => 'Rafael', 'last_name' => 'Nadal' ] );
		$team1   = Team::from_object( (object) [ 'id' => 10, 'title' => 'Roger Federer', 'players' => [ $player1 ] ] );
		$team2   = Team::from_object( (object) [ 'id' => 20, 'title' => 'Rafael Nadal', 'players' => [ $player2 ] ] );

		$fixture = Fixture_Test_Factory::create( id: 555, home_team: '10', away_team: '20', date: '2026-07-05 09:05:00', final: 'final', league_id: 202 );
		$fixture->start_time = '09:05';
		$fixture->season     = '2026';

		$league                  = $this->createStub( League::class );
		$league->title           = 'Championship Singles';
		$league->is_championship = true;
		$league->scoring         = '3_sets';
		$league->num_sets        = 3;
		$league->num_rubbers     = 1;

		$event       = $this->createStub( Event::class );
		$event->name = 'Mens Singles';

		$competition       = $this->createStub( Competition::class );
		$competition->type = Competition_Type::TOURNAMENT;
		$competition->method( 'get_type' )->willReturn( 'tournament' );

		$club = $this->createStub( Club::class );
		$club->method( 'get_shortcode' )->willReturn( 'SW19' );

		$fixture_details = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $competition,
			home_team: new Team_Details_DTO( $team1 ),
			away_team: new Team_Details_DTO( $team2 )
		);

		$tournament_service = $this->createStub( Tournament_Service::class );
		$tournament_service->method( 'get_tournament_with_details_by_name' )
			->willReturn( new Tournament_Details_DTO( $tournament, $competition, $club ) );
		$fixture_service = $this->createStub( Fixture_Service::class );
		$fixture_service->method( 'get_tournament_fixture_with_details' )->willReturn( $fixture_details );
		$fixture_service->method( 'is_update_allowed' )
			->willReturn( (object) [ 'user_can_update' => true, 'user_type' => 'admin' ] );

		$handler = new Get_Tournament_Match_Handler( $tournament_service, $fixture_service, new Tournament_Presenter( 'Y-m-d' ) );

		return $handler->handle( new Get_Tournament_Match_Query( 'summer-championship-2026', 555, $referrer ) );
	}

	/**
	 * @return array<string, array{?string, string}>
	 */
	public static function tournament_match_back_url_provider(): array {
		return [
			'referrer supplied'            => [ 'https://example.test/tournament/summer-championship-2026/draw/', 'https://example.test/tournament/summer-championship-2026/draw/' ],
			'no referrer uses matches page' => [ null, '/tournament/summer-championship-2026/matches/' ],
			'blank referrer uses matches page' => [ '', '/tournament/summer-championship-2026/matches/' ],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider( 'tournament_match_back_url_provider' )]
	public function test_tournament_match_back_url_comes_from_producer_not_request_state( ?string $referrer, string $expected ): void {
		// The template must not read the request: contradict the query with a different request referrer.
		$GLOBALS['wp_stubs_wp_get_referer'] = 'https://elsewhere.test/should-not-be-used/';
		try {
			$view_model = $this->build_tournament_match_view_model( $referrer );
			$this->assertSame( $expected, $view_model->vars['back_url'] );

			$GLOBALS['wp_stubs_is_user_logged_in'] = true;
			[ $html, $warnings ] = $this->with_warning_capture( fn() => $this->renderer->render_to_string( 'match-tournament', $view_model, $view_model->vars ) );
		} finally {
			unset( $GLOBALS['wp_stubs_wp_get_referer'] );
		}

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( '<a href="' . $expected . '">', $html );
		$this->assertStringNotContainsString( 'elsewhere.test', $html );
	}

	public function test_render_match_tournament_league_box_real_template(): void {
		$tournament = Tournament::from_object( (object) [
			'id'         => 102,
			'name'       => 'Autumn Box League',
			'date_start' => '2026-09-01',
			'date_end'   => '2026-09-30',
			'date'       => '2026-09-30',
		] );

		$player1 = Player_Factory::from_object( (object) [ 'id' => 3, 'display_name' => 'Team Alpha Player', 'first_name' => 'Alpha', 'last_name' => 'Player' ] );
		$player2 = Player_Factory::from_object( (object) [ 'id' => 4, 'display_name' => 'Team Beta Player', 'first_name' => 'Beta', 'last_name' => 'Player' ] );

		$team1 = Team::from_object( (object) [ 'id' => 30, 'title' => 'Team Alpha', 'players' => [ $player1 ] ] );
		$team2 = Team::from_object( (object) [ 'id' => 40, 'title' => 'Team Beta', 'players' => [ $player2 ] ] );

		$fixture = Fixture_Test_Factory::create(
			id: 777,
			home_team: '30',
			away_team: '40',
			date: '2026-09-12',
			final: 'round-2',
			league_id: 303
		);
		$fixture->start_time = '';
		$fixture->season     = '2';

		$league                  = $this->createStub( League::class );
		$league->title           = 'Box Division 1';
		$league->is_championship = false;
		$league->scoring         = '3_sets';
		$league->num_sets        = 3;
		$league->num_rubbers     = 1;

		$event         = $this->createStub( Event::class );
		$event->name   = 'Box League';
		$event->is_box = true;

		$competition       = $this->createStub( Competition::class );
		$competition->type = Competition_Type::TOURNAMENT;
		$competition->method( 'get_type' )->willReturn( 'tournament' );

		$club = $this->createStub( Club::class );
		$club->method( 'get_shortcode' )->willReturn( 'LTC' );

		$fixture_details = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $competition,
			home_team: new Team_Details_DTO( $team1 ),
			away_team: new Team_Details_DTO( $team2 )
		);

		$tournament_service = $this->createStub( Tournament_Service::class );
		$tournament_service->method( 'get_tournament_with_details_by_name' )
			->willReturn( new Tournament_Details_DTO( $tournament, $competition, $club ) );

		$fixture_service = $this->createStub( Fixture_Service::class );
		$fixture_service->method( 'get_tournament_fixture_with_details' )
			->willReturn( $fixture_details );
		$fixture_service->method( 'is_update_allowed' )
			->willReturn( (object) [ 'user_can_update' => false ] );

		$presenter = new Tournament_Presenter( 'Y-m-d' );
		$handler   = new Get_Tournament_Match_Handler( $tournament_service, $fixture_service, $presenter );

		$view_model = $handler->handle( new Get_Tournament_Match_Query( 'autumn-box-league', 777 ) );

		$this->assertSame( 'League', $view_model->vars['svg_link_text'] );
		$this->assertSame( 'Box Division 1', $view_model->vars['svg_link'] );
		$this->assertSame( '/league/box-division-1/round-2/', $view_model->vars['svg_link_location'] );
		$this->assertFalse( $view_model->vars['user_can_update'] );
		$this->assertFalse( $view_model->vars['match_editable'] );
		$this->assertFalse( $view_model->vars['allow_schedule_match'] );
		$this->assertFalse( $view_model->vars['allow_reset_match_result'] );
		$this->assertFalse( $view_model->vars['show_menu'] );
		$this->assertSame( 'Sat 12 Sep', $view_model->vars['match_date_formatted'] );
		$this->assertSame( '', $view_model->vars['match_time_formatted'] );

		$html = $this->renderer->render_to_string( 'match-tournament', $view_model, $view_model->vars );

		$this->assertStringContainsString( 'Autumn Box League', $html );
		$this->assertStringContainsString( 'Sat 12 Sep', $html );
		$this->assertStringNotContainsString( 'class="match_time"', $html );
		$this->assertStringContainsString( 'League', $html );
		$this->assertStringContainsString( '/league/box-division-1/round-2/', $html );
		$this->assertStringContainsString( 'Team Alpha', $html );
		$this->assertStringContainsString( 'Team Beta', $html );
		$this->assertStringNotContainsString( 'is-editable', $html );
		$this->assertStringNotContainsString( '(Re)schedule match', $html );
		$this->assertStringNotContainsString( 'Reset match result', $html );
	}

	public function test_render_match_detail_teams_scores_played_match_real_template(): void {
		$fixture = Fixture_Test_Factory::create(
			id: 888,
			home_team: '10',
			away_team: '20',
			winner_id: 10,
			loser_id: 20,
			home_points: '4',
			away_points: '0',
			date: '2026-10-15',
			league_id: 404
		);
		$fixture->season = '2026';
		$fixture->custom = [
			'comments' => [
				'result' => 'Great match played in good spirits',
			],
		];
		$fixture->home_captain = 101;
		$fixture->away_captain = 202;

		$home_team = Team::from_object( (object) [ 'id' => 10, 'title' => 'Leigh 1' ] );
		$away_team = Team::from_object( (object) [ 'id' => 20, 'title' => 'Westcliff 1' ] );

		$rubber_1 = $this->make_rubber( 1, 1, 'MD', [
			'home' => [
				1 => $this->make_club_player( 'Alice One' ),
				2 => $this->make_club_player( 'System Bye', true ),
			],
			'away' => [
				1 => $this->make_club_player( 'Bob Two' ),
			],
		] );
		$rubber_2 = $this->make_rubber( 2, 2, 'MD', [
			'home' => [
				1 => $this->make_club_player( 'Carol Three' ),
			],
			'away' => [
				1 => $this->make_club_player( 'Dan Four' ),
			],
		] );

		$fixture->set_rubbers( [ $rubber_1, $rubber_2 ] );

		$league                  = $this->createStub( League::class );
		$league->scoring         = 'pairs';
		$league->num_rubbers     = 2;
		$league->num_sets        = 2;
		$league->is_championship = false;
		$league->title           = 'Mens Doubles Premier';

		$event       = $this->createStub( Event::class );
		$event->name = 'Mens Doubles';

		$competition       = $this->createStub( Competition::class );
		$competition->type = Competition_Type::LEAGUE;

		$dto = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $competition,
			home_team: new Team_Details_DTO( $home_team ),
			away_team: new Team_Details_DTO( $away_team ),
			is_update_allowed: (object) [ 'user_can_update' => false ],
			link: '/match/888/'
		);

		$player_service = $this->createStub( Player_Service::class );
		$player_service->method( 'get_player' )->willReturnCallback( function ( int $id ) {
			if ( 101 === $id ) {
				$p = $this->createStub( Player::class );
				$p->method( 'get_name' )->willReturn( 'Alice Captain' );
				return $p;
			}
			if ( 202 === $id ) {
				$p = $this->createStub( Player::class );
				$p->method( 'get_name' )->willReturn( 'Bob Captain' );
				return $p;
			}
			return null;
		} );

		$gateway = $this->createStub( Option_Gateway_Interface::class );
		$gateway->method( 'get_option' )->willReturn( 'Y-m-d' );
		$fixture_presenter = new Fixture_Presenter( $this->createStub( Fixture_Link_Service::class ), $gateway );

		$view_presenter      = new Shortcode_View_Presenter( $this->template_renderer );
		$shortcode_presenter = new Match_Shortcode_Presenter(
			$view_presenter,
			$fixture_presenter,
			$player_service
		);

		$result = new Match_Detail_Result(
			template: 'teams-scores',
			type: 'league',
			round: 'round-1',
			details: $dto
		);

		[ $html, $warnings ] = $this->with_warning_capture( fn() => $shortcode_presenter->render_detail( $result ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( 'Leigh 1', $html );
		$this->assertStringContainsString( 'Westcliff 1', $html );
		$this->assertStringContainsString( 'Alice Captain', $html );
		$this->assertStringContainsString( 'Bob Captain', $html );
		$this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/player/alice-one/"', $html );
		$this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/player/bob-two/"', $html );
		$this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/player/carol-three/"', $html );
		$this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/player/dan-four/"', $html );
		$this->assertStringContainsString( 'Alice One', $html );
		$this->assertStringContainsString( 'Carol Three', $html );
		$this->assertStringContainsString( 'System Bye', $html );
		$this->assertStringNotContainsString( 'player/system-bye/', $html );
		$this->assertDoesNotMatchRegularExpression( '/href="[^"]+"[^>]*>\\s*System Bye/', $html );
		$this->assertStringContainsString( 'Great match played in good spirits', $html );
		$this->assertNull( $rubber_1->players['home'][1]->url ?? null );
		$this->assertNull( $rubber_2->players['home'][1]->url ?? null );
	}

	public function test_render_match_detail_teams_scores_with_player_summary_header(): void {
		$fixture = Fixture_Test_Factory::create(
			id: 888,
			home_team: '10',
			away_team: '20',
			winner_id: 10,
			loser_id: 20,
			home_points: '4',
			away_points: '0',
			date: '2026-10-15',
			league_id: 404
		);
		$fixture->season = '2026';

		$home_team = Team::from_object( (object) [ 'id' => 10, 'title' => 'Leigh 1' ] );
		$away_team = Team::from_object( (object) [ 'id' => 20, 'title' => 'Westcliff 1' ] );

		$league                  = $this->createStub( League::class );
		$league->scoring         = 'pairs';
		$league->num_rubbers     = 1;
		$league->num_sets        = 2;
		$league->is_championship = false;
		$league->title           = 'Mens Doubles Premier';

		$event       = $this->createStub( Event::class );
		$event->name = 'Mens Doubles';

		$competition       = $this->createStub( Competition::class );
		$competition->type = Competition_Type::LEAGUE;

		$dto = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $competition,
			home_team: new Team_Details_DTO( $home_team ),
			away_team: new Team_Details_DTO( $away_team ),
			is_update_allowed: (object) [ 'user_can_update' => false ],
			link: '/match/888/'
		);

		$gateway = $this->createStub( Option_Gateway_Interface::class );
		$gateway->method( 'get_option' )->willReturn( 'j F Y' );
		$fixture_presenter = new Fixture_Presenter( $this->createStub( Fixture_Link_Service::class ), $gateway );

		$view_presenter      = new Shortcode_View_Presenter( $this->template_renderer );
		$shortcode_presenter = new Match_Shortcode_Presenter(
			$view_presenter,
			$fixture_presenter,
			$this->createStub( Player_Service::class )
		);

		$result = new Match_Detail_Result(
			template: 'teams-scores',
			type: 'league',
			round: 'round-1',
			details: $dto,
			match_player: (object) [ 'id' => 1 ]
		);

		[ $html, $warnings ] = $this->with_warning_capture( fn() => $shortcode_presenter->render_detail( $result ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( '15 October 2026', $html );
		$this->assertStringContainsString( 'datetime="2026-10-15"', $html );
		$this->assertStringContainsString( 'Leigh 1', $html );
		$this->assertStringContainsString( 'Westcliff 1', $html );
	}

	public function test_render_match_detail_teams_scores_championship_final_header(): void {
		$fixture = Fixture_Test_Factory::create(
			id: 999,
			home_team: '10',
			away_team: '20',
			final: 'final',
			date: '2026-11-20',
			league_id: 505
		);

		$home_team = Team::from_object( (object) [ 'id' => 10, 'title' => 'Home Team' ] );
		$away_team = Team::from_object( (object) [ 'id' => 20, 'title' => 'Away Team' ] );

		$dto = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $this->createStub( League::class ),
			event: $this->createStub( Event::class ),
			competition: $this->createStub( Competition::class ),
			home_team: new Team_Details_DTO( $home_team ),
			away_team: new Team_Details_DTO( $away_team ),
			is_update_allowed: (object) [ 'user_can_update' => false ],
			link: '/match/999/'
		);

		$gateway = $this->createStub( Option_Gateway_Interface::class );
		$gateway->method( 'get_option' )->willReturn( 'Y-m-d' );
		$fixture_presenter = new Fixture_Presenter( $this->createStub( Fixture_Link_Service::class ), $gateway );

		$view_presenter      = new Shortcode_View_Presenter( $this->template_renderer );
		$shortcode_presenter = new Match_Shortcode_Presenter(
			$view_presenter,
			$fixture_presenter,
			$this->createStub( Player_Service::class )
		);

		$result = new Match_Detail_Result(
			template: 'teams-scores',
			type: 'championship',
			round: 'final',
			details: $dto,
			match_player: (object) [ 'id' => 1 ]
		);

		[ $html, $warnings ] = $this->with_warning_capture( fn() => $shortcode_presenter->render_detail( $result ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( 'Final', $html );
	}

	public function test_render_match_tournament_played_admin_shows_reset_menu(): void {
		$tournament = Tournament::from_object( (object) [
			'id'         => 103,
			'name'       => 'Played Finals',
			'date_start' => '2026-07-01',
			'date_end'   => '2026-07-10',
			'date'       => '2026-07-10',
		] );

		$player1 = Player_Factory::from_object( (object) [ 'id' => 1, 'display_name' => 'Ada Lovelace', 'first_name' => 'Ada', 'last_name' => 'Lovelace' ] );
		$player2 = Player_Factory::from_object( (object) [ 'id' => 2, 'display_name' => 'Grace Hopper', 'first_name' => 'Grace', 'last_name' => 'Hopper' ] );
		$team1   = Team::from_object( (object) [ 'id' => 10, 'title' => 'Ada Lovelace', 'players' => [ $player1 ] ] );
		$team2   = Team::from_object( (object) [ 'id' => 20, 'title' => 'Grace Hopper', 'players' => [ $player2 ] ] );

		$fixture = Fixture_Test_Factory::create(
			id: 999,
			home_team: '10',
			away_team: '20',
			date: '2026-07-05 09:05:00',
			winner_id: 10,
			loser_id: 20,
			final: 'final',
			league_id: 202
		);
		$fixture->start_time = '09:05';

		$league                  = $this->createStub( League::class );
		$league->title           = 'Championship Singles';
		$league->is_championship = true;
		$league->scoring         = '3_sets';
		$league->num_sets        = 3;
		$league->num_rubbers     = 1;

		$event       = $this->createStub( Event::class );
		$event->name = 'Mens Singles';

		$competition       = $this->createStub( Competition::class );
		$competition->type = Competition_Type::TOURNAMENT;
		$competition->method( 'get_type' )->willReturn( 'tournament' );

		$club = $this->createStub( Club::class );
		$club->method( 'get_shortcode' )->willReturn( 'SW19' );

		$fixture_details = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $competition,
			home_team: new Team_Details_DTO( $team1 ),
			away_team: new Team_Details_DTO( $team2 )
		);

		$tournament_service = $this->createStub( Tournament_Service::class );
		$tournament_service->method( 'get_tournament_with_details_by_name' )
			->willReturn( new Tournament_Details_DTO( $tournament, $competition, $club ) );

		$fixture_service = $this->createStub( Fixture_Service::class );
		$fixture_service->method( 'get_tournament_fixture_with_details' )
			->willReturn( $fixture_details );
		$fixture_service->method( 'is_update_allowed' )
			->willReturn( (object) [ 'user_can_update' => true, 'user_type' => 'admin' ] );

		$handler    = new Get_Tournament_Match_Handler( $tournament_service, $fixture_service, new Tournament_Presenter( 'Y-m-d' ) );
		$view_model = $handler->handle( new Get_Tournament_Match_Query( 'played-finals', 999 ) );

		$this->assertTrue( $view_model->vars['user_can_update'] );
		$this->assertSame( 'is-editable', $view_model->vars['match_editable'] );
		$this->assertFalse( $view_model->vars['allow_schedule_match'] );
		$this->assertTrue( $view_model->vars['allow_reset_match_result'] );
		$this->assertTrue( $view_model->vars['show_menu'] );
		$this->assertSame( 'home', $view_model->vars['winner'] );
		$this->assertSame( '9:05', $view_model->vars['match_time_formatted'] );

		$GLOBALS['wp_stubs_is_user_logged_in'] = true;
		[ $html, $warnings ] = $this->with_warning_capture( fn() => $this->renderer->render_to_string( 'match-tournament', $view_model, $view_model->vars ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( 'Reset match result', $html );
		$this->assertStringContainsString( 'data-match-option="reset_match_result"', $html );
		$this->assertStringNotContainsString( '(Re)schedule match', $html );
		$this->assertStringContainsString( 'is-editable', $html );
		$this->assertStringContainsString( 'at 9:05', $html );
	}

	public function test_match_card_template_renders_precomputed_date_with_zero_warnings(): void {
		$fixture = $this->createStub( Fixture::class );
		$fixture->method( 'get_id' )->willReturn( 101 );
		$fixture->method( 'get_date' )->willReturn( '2026-10-18 19:30:00' );
		$fixture->method( 'get_match_day' )->willReturn( 3 );

		$league           = $this->createStub( League::class );
		$league->title    = 'Premier League';
		$league->num_sets = 3;

		$event       = $this->createStub( Event::class );
		$competition = $this->createStub( Competition::class );

		$home_team = $this->createStub( Team::class );
		$home_team->method( 'get_name' )->willReturn( 'Home Club 1' );
		$away_team = $this->createStub( Team::class );
		$away_team->method( 'get_name' )->willReturn( 'Away Club 2' );

		$dto = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $competition,
			home_team: new Team_Details_DTO( $home_team ),
			away_team: new Team_Details_DTO( $away_team ),
			fixture_title: 'Home Club 1 v Away Club 2'
		);

		$presenter = new Fixture_Presenter(
			$this->createStub( Fixture_Link_Service::class ),
			$this->createStub( Option_Gateway_Interface::class ),
			$this->create_team_match_row_presenter()
		);

		$vars = $presenter->map_to_match_card_vars( $dto, $fixture, '<div class="sponsor">Sponsor</div>' );

		[ $html, $warnings ] = $this->with_warning_capture( fn() => $this->renderer->render_to_string( 'match/match-card', $vars ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( 'Premier League', $html );
		$this->assertStringContainsString( '<div class="matchDate">2026-10-18</div>', $html );
		$this->assertStringContainsString( 'Week3', $html );
		$this->assertStringContainsString( 'Home Club 1 v Away Club 2', $html );
		$this->assertStringContainsString( '<div class="sponsor">Sponsor</div>', $html );
	}

	public function test_match_card_template_renders_with_null_date_with_zero_warnings(): void {
		$fixture = $this->createStub( Fixture::class );
		$fixture->method( 'get_id' )->willReturn( 102 );
		$fixture->method( 'get_date' )->willReturn( null );
		$fixture->method( 'get_match_day' )->willReturn( 1 );

		$league           = $this->createStub( League::class );
		$league->title    = 'Division 2';
		$league->num_sets = 2;

		$dto = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $this->createStub( Event::class ),
			competition: $this->createStub( Competition::class )
		);

		$presenter = new Fixture_Presenter(
			$this->createStub( Fixture_Link_Service::class ),
			$this->createStub( Option_Gateway_Interface::class ),
			$this->create_team_match_row_presenter()
		);

		$vars = $presenter->map_to_match_card_vars( $dto, $fixture );

		[ $html, $warnings ] = $this->with_warning_capture( fn() => $this->renderer->render_to_string( 'match/match-card', $vars ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( 'Division 2', $html );
		$this->assertStringContainsString( '<div class="matchDate"></div>', $html );
	}

	public function test_match_card_rubbers_template_renders_precomputed_date_with_zero_warnings(): void {
		$fixture = $this->createStub( Fixture::class );
		$fixture->method( 'get_id' )->willReturn( 201 );
		$fixture->method( 'get_date' )->willReturn( '2026-10-18 19:30:00' );
		$fixture->method( 'get_match_day' )->willReturn( 4 );
		$fixture->method( 'get_rubbers' )->willReturn( [] );

		$league              = $this->createStub( League::class );
		$league->title       = 'Premier Rubbers';
		$league->num_rubbers = 3;
		$league->num_sets    = 3;

		$dto = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $this->createStub( Event::class ),
			competition: $this->createStub( Competition::class ),
			fixture_title: 'Team A v Team B'
		);

		$presenter = new Fixture_Presenter(
			$this->createStub( Fixture_Link_Service::class ),
			$this->createStub( Option_Gateway_Interface::class ),
			$this->create_team_match_row_presenter()
		);

		$vars = $presenter->map_to_match_card_vars( $dto, $fixture, '<!-- sponsor -->' );

		[ $html, $warnings ] = $this->with_warning_capture( fn() => $this->renderer->render_to_string( 'match/match-card-rubbers', $vars ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( 'Premier Rubbers', $html );
		$this->assertStringContainsString( '<div class="matchDate">2026-10-18</div>', $html );
		$this->assertStringContainsString( 'Team A v Team B', $html );
	}

	public function test_match_option_modal_renders_schedule_date_inputs_with_zero_warnings(): void {
		$fixture = $this->createStub( Fixture::class );
		$fixture->method( 'get_id' )->willReturn( 301 );
		$fixture->method( 'get_home_team' )->willReturn( '10' );
		$fixture->method( 'get_away_team' )->willReturn( '20' );
		$fixture->method( 'get_date' )->willReturn( '2026-10-18 00:00:00' );
		$fixture->method( 'get_start_time' )->willReturn( null );

		$league        = $this->createStub( League::class );
		$league->id    = 1;
		$league->title = 'Division 1';
		$league->method( 'get_name' )->willReturn( 'Division 1' );
		$event         = $this->createStub( Event::class );
		$event->id     = 2;
		$event->name   = 'Men Singles';
		$comp          = $this->createStub( Competition::class );
		$comp->method( 'get_type' )->willReturn( 'league' );

		$dto = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $comp
		);

		$presenter = new Fixture_Presenter(
			$this->createStub( Fixture_Link_Service::class ),
			$this->createStub( Option_Gateway_Interface::class ),
			$this->create_team_match_row_presenter()
		);

		$request = new Fixture_Option_Request( 301, 'schedule-modal', 'schedule_match' );
		$vars    = $presenter->map_to_fixture_option_vars( $dto, $request, '(Re)schedule fixture', 'Save Date', 'setMatchDate' );

		[ $html, $warnings ] = $this->with_warning_capture( fn() => $this->renderer->render_to_string( 'match/match-option-modal', $vars ) );

		$this->assertSame( [], $warnings );
		$this->assertStringContainsString( '(Re)schedule fixture', $html );
		$this->assertStringContainsString( 'type="date"', $html );
		$this->assertStringContainsString( 'value="2026-10-18"', $html );
		$this->assertStringContainsString( 'data-action="set-match-date"', $html );

		// Test datetime-local with start time
		$fixture_time = $this->createStub( Fixture::class );
		$fixture_time->method( 'get_id' )->willReturn( 302 );
		$fixture_time->method( 'get_home_team' )->willReturn( '10' );
		$fixture_time->method( 'get_away_team' )->willReturn( '20' );
		$fixture_time->method( 'get_date' )->willReturn( '2026-10-18 19:30:00' );
		$fixture_time->method( 'get_start_time' )->willReturn( '19:30' );

		$dto_time = new Fixture_Details_DTO(
			fixture: $fixture_time,
			league: $league,
			event: $event,
			competition: $comp
		);
		$vars_time = $presenter->map_to_fixture_option_vars( $dto_time, $request, '(Re)schedule fixture', 'Save Date', 'setMatchDate' );

		[ $html_time, $warnings_time ] = $this->with_warning_capture( fn() => $this->renderer->render_to_string( 'match/match-option-modal', $vars_time ) );

		$this->assertSame( [], $warnings_time );
		$this->assertStringContainsString( 'type="datetime-local"', $html_time );
		$this->assertStringContainsString( 'value="2026-10-18 19:30:00"', $html_time );
	}
}
