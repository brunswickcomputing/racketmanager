<?php
declare( strict_types=1 );

namespace {
	if ( ! function_exists( 'mysql2date' ) ) {
		function mysql2date( $format, $date ): string {
			return date( $format, strtotime( $date ) );
		}
	}
}

namespace Racketmanager\Tests\Unit\Services\Result {

	use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Tournament\Championship_Settings;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Result\Result_Reporting_Service;
use stdClass;

class Result_Reporting_Service_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! isset( $GLOBALS['racketmanager'] ) || ! ( $GLOBALS['racketmanager'] instanceof stdClass ) ) {
			$GLOBALS['racketmanager'] = new stdClass();
		}
		$GLOBALS['racketmanager']->site_name = 'Test Site';
	}

	private function create_reporting_fixture( array $overrides = [] ): Fixture {
		$fixture = $this->createStub( Fixture::class );
		$fixture->method( 'get_id' )->willReturn( $overrides['id'] ?? 1 );
		$fixture->method( 'get_league_id' )->willReturn( $overrides['league_id'] ?? 10 );
		$fixture->method( 'get_season' )->willReturn( $overrides['season'] ?? '2024' );
		$fixture->method( 'get_match_day' )->willReturn( $overrides['match_day'] ?? 1 );
		$fixture->method( 'get_home_team' )->willReturn( $overrides['home_team'] ?? '1' );
		$fixture->method( 'get_away_team' )->willReturn( $overrides['away_team'] ?? '2' );
		$fixture->method( 'get_winner_id' )->willReturn( $overrides['winner_id'] ?? 1 );
		$fixture->method( 'get_date' )->willReturn( $overrides['date'] ?? '2024-05-01 10:00:00' );
		$fixture->method( 'is_walkover' )->willReturn( $overrides['is_walkover'] ?? false );
		$fixture->method( 'is_retired' )->willReturn( $overrides['is_retired'] ?? false );
		$fixture->method( 'is_shared' )->willReturn( $overrides['is_shared'] ?? false );
		$fixture->method( 'is_cancelled' )->willReturn( $overrides['is_cancelled'] ?? false );
		$fixture->method( 'is_withdrawn' )->willReturn( $overrides['is_withdrawn'] ?? false );
		$fixture->method( 'get_custom' )->willReturn( $overrides['custom'] ?? [] );
		return $fixture;
	}

	private function create_standard_competition( array $overrides = [] ): Competition {
		$competition = $this->createStub( Competition::class );
		$season_info = array_key_exists( 'season_info', $overrides )
			? $overrides['season_info']
			: [ 'competition_code' => 'COMP123', 'date_start' => '2024-01-01', 'date_end' => '2024-12-31' ];
		$competition->method( 'get_season_by_name' )->willReturn( $season_info );
		$competition->name = $overrides['name'] ?? 'Test Competition';
		$competition->date_start = $overrides['date_start'] ?? '2024-01-01';
		$competition->date_end = $overrides['date_end'] ?? '2024-12-31';
		$competition->type = $overrides['type'] ?? Competition_Type::LEAGUE;
		$competition->settings = $overrides['settings'] ?? new Competition_Settings( [ 'grade' => 4 ] );
		if ( array_key_exists( 'competition_code', $overrides ) ) {
			$competition->competition_code = $overrides['competition_code'];
		}
		return $competition;
	}

	private function create_standard_event( array $overrides = [] ): Event {
		$event = $this->createStub( Event::class );
		$event->method( 'get_competition_id' )->willReturn( $overrides['competition_id'] ?? 30 );
		$season_info = array_key_exists( 'season_info', $overrides )
			? $overrides['season_info']
			: [ 'grade' => 4 ];
		$event->method( 'get_season_by_name' )->willReturn( $season_info );
		$event->name = $overrides['name'] ?? 'Test Event';
		$event->age_limit = $overrides['age_limit'] ?? 'Open';
		$event->type = $overrides['type'] ?? 'MS';
		$event->primary_league = $overrides['primary_league'] ?? 10;
		return $event;
	}

	private function create_standard_league( array $overrides = [] ): League {
		$league = $this->createStub( League::class );
		$league->method( 'get_event_id' )->willReturn( $overrides['event_id'] ?? 20 );
		$league->title = $overrides['title'] ?? 'Test League';
		$league->num_teams_total = $overrides['num_teams_total'] ?? 8;
		$league->num_rubbers = $overrides['num_rubbers'] ?? 0;
		$league->championship = $this->createStub( Championship::class );
		$league->championship->method( 'num_teams_first_round' )->willReturn( $overrides['first_round_teams'] ?? 8 );
		return $league;
	}

	public function test_report_result_returns_null_if_no_competition_code(): void {
		$fixture = $this->create_reporting_fixture();
		$league = $this->create_standard_league();
		$event = $this->create_standard_event( [ 'season_info' => null ] );
		$competition = $this->create_standard_competition( [
			'season_info' => null,
			'competition_code' => null,
		] );

		$repository_provider = $this->setup_repository_provider_mock( $league, $event, $competition );
		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		
		$result = $service->report_result( $fixture );
		$this->assertNull( $result );
	}

	public function test_report_result_basic_structure(): void {
		$fixture = $this->create_reporting_fixture();
		$competition = $this->create_standard_competition();
		$event = $this->create_standard_event();
		$league = $this->create_standard_league();

		$player1 = (object) [ 'display_name' => 'Player One', 'btm' => '123456' ];
		$player2 = (object) [ 'display_name' => 'Player Two', 'btm' => '654321' ];

		$home_team = $this->createStub( Team::class );
		$home_team->method('get_players')->willReturn([$player1]);
		
		$away_team = $this->createStub( Team::class );
		$away_team->method('get_players')->willReturn([$player2]);

		$repository_provider = $this->setup_repository_provider_mock( $league, $event, $competition, $home_team, $away_team );
		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		
		$result = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertEquals( 'COMP123', $result->code );
		$this->assertEquals( 'Test Site Test Competition', $result->tournament );
		$this->assertCount( 1, $result->matches );
		$this->assertEquals( 'Player One', $result->matches[0]->winner_name );
	}

	public function test_report_result_with_rubbers(): void {
		$fixture = $this->create_reporting_fixture();
		$competition = $this->create_standard_competition();
		$event = $this->create_standard_event();
		$league = $this->create_standard_league( [ 'num_rubbers' => 1 ] );

		$player1 = (object) [ 'display_name' => 'Rubber Winner', 'btm' => '111' ];
		$player2 = (object) [ 'display_name' => 'Rubber Loser', 'btm' => '222' ];

		$rubber = $this->createStub( Rubber::class );
		$rubber->method('get_id')->willReturn(101);
		$rubber->method('get_winner_id')->willReturn(1);
		$rubber->method('get_loser_id')->willReturn(2);
		$rubber->method('get_status')->willReturn(1);
		$rubber->method('is_walkover')->willReturn(false);
		$rubber->method('is_shared')->willReturn(false);
		$rubber->players = [
			'home' => ['1' => $player1],
			'away' => ['1' => $player2],
		];

		$repository_provider = $this->setup_repository_provider_mock( $league, $event, $competition, null, null, [ $rubber ] );
		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		
		$result = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertCount( 1, $result->matches );
		$this->assertEquals( 'Rubber Winner', $result->matches[0]->winner_name );
	}

	public function test_report_rubber_status_codes_skip_walkover_and_shared_and_mark_retired(): void {
		$fixture     = $this->create_reporting_fixture();
		$competition = $this->create_standard_competition();
		$event       = $this->create_standard_event();
		$league      = $this->create_standard_league( [ 'num_rubbers' => 3 ] );
		$rubbers     = [
			$this->create_rubber_entity( 1, 1 ),
			$this->create_rubber_entity( 2, 3 ),
			$this->create_rubber_entity( 3, 2 ),
		];

		$repository_provider = $this->setup_repository_provider_mock( $league, $event, $competition, null, null, $rubbers );
		$service             = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		$result              = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertCount( 1, $result->matches );
		$this->assertSame( 3, $result->matches[0]->match );
		$this->assertSame( 'Retired', $result->matches[0]->score_code );
	}

	public function test_report_result_with_scores(): void {
		$fixture = $this->create_reporting_fixture( [
			'custom' => [
				'sets' => [
					1 => ['player1' => 6, 'player2' => 4],
					2 => ['player1' => 7, 'player2' => 6, 'tiebreak' => 5],
				]
			]
		] );

		$competition = $this->create_standard_competition();
		$event = $this->create_standard_event();
		$league = $this->create_standard_league();

		$home_team = $this->createStub( Team::class );
		$home_team->method('get_players')->willReturn([ (object) [ 'display_name' => 'Winner' ] ]);
		$away_team = $this->createStub( Team::class );
		$away_team->method('get_players')->willReturn([ (object) [ 'display_name' => 'Loser' ] ]);

		$repository_provider = $this->setup_repository_provider_mock( $league, $event, $competition, $home_team, $away_team );
		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		$result = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertCount( 1, $result->matches );
		$this->assertEquals( '6-4 7-6(5)', $result->matches[0]->score );
		$this->assertEquals( 6, $result->matches[0]->set1team1 );
		$this->assertEquals( 4, $result->matches[0]->set1team2 );
		$this->assertEquals( 7, $result->matches[0]->set2team1 );
		$this->assertEquals( 6, $result->matches[0]->set2team2 );
		$this->assertEquals( 5, $result->matches[0]->tiebreak2 );
	}

	public function test_report_result_match_tiebreak_legacy_format(): void {
		$fixture = $this->create_reporting_fixture( [
			'custom' => [
				'sets' => [
					1 => ['player1' => 6, 'player2' => 4],
					2 => ['player1' => 4, 'player2' => 6],
					3 => ['player1' => 1, 'player2' => 0], // MTB legacy
				]
			]
		] );

		$competition = $this->create_standard_competition();
		$event = $this->create_standard_event();
		$league = $this->create_standard_league();

		$home_team = $this->createStub( Team::class );
		$home_team->method('get_players')->willReturn([ (object)['display_name' => 'W'] ]);
		$away_team = $this->createStub( Team::class );
		$away_team->method('get_players')->willReturn([ (object)['display_name' => 'L'] ]);

		$repository_provider = $this->setup_repository_provider_mock($league, $event, $competition, $home_team, $away_team);

		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		$result = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertEquals( '6-4 4-6 [10-8]', $result->matches[0]->score );
	}

	public function test_report_result_retired(): void {
		$fixture = $this->create_reporting_fixture( [
			'is_retired' => true,
			'custom'     => [ 'sets' => [ 1 => ['player1' => 6, 'player2' => 4], 2 => ['player1' => 3, 'player2' => 2] ] ],
		] );

		$competition = $this->create_standard_competition( [ 'name' => 'Comp' ] );
		$event = $this->create_standard_event( [ 'name' => 'Event' ] );
		$league = $this->create_standard_league( [ 'title' => 'League' ] );

		$repository_provider = $this->setup_repository_provider_mock($league, $event, $competition);
		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		
		$result = $service->report_result( $fixture );
		$this->assertEquals( 'R', $result->matches[0]->score_code );
	}

	public function test_report_result_walkover(): void {
		$fixture = $this->create_reporting_fixture( [ 'is_walkover' => true ] );
		$competition = $this->create_standard_competition( [ 'name' => 'Comp' ] );
		$event = $this->create_standard_event( [ 'name' => 'Event' ] );
		$league = $this->create_standard_league( [ 'title' => 'League' ] );

		$repository_provider = $this->setup_repository_provider_mock($league, $event, $competition);
		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		
		$result = $service->report_result( $fixture );
		$this->assertNotNull( $result );
		$this->assertCount( 0, $result->matches );
	}

	public function test_report_result_doubles(): void {
		$fixture = $this->create_reporting_fixture( [
			'custom' => [ 'sets' => [ 1 => ['player1' => 6, 'player2' => 0] ] ],
		] );

		$competition = $this->create_standard_competition( [ 'name' => 'Comp' ] );
		$event = $this->create_standard_event( [ 'name' => 'Event', 'type' => 'MD' ] );
		$league = $this->create_standard_league( [ 'title' => 'League' ] );

		$p1 = (object)['display_name' => 'H1', 'btm' => 'H1'];
		$p2 = (object)['display_name' => 'H2', 'btm' => 'H2'];
		$p3 = (object)['display_name' => 'A1', 'btm' => 'A1'];
		$p4 = (object)['display_name' => 'A2', 'btm' => 'A2'];

		$home_team = $this->createStub( Team::class );
		$home_team->method('get_players')->willReturn([$p1, $p2]);
		$away_team = $this->createStub( Team::class );
		$away_team->method('get_players')->willReturn([$p3, $p4]);

		$repository_provider = $this->setup_repository_provider_mock($league, $event, $competition, $home_team, $away_team);
		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		
		$result = $service->report_result( $fixture );

		$this->assertEquals( 'Male', $result->gender );
		$match = $result->matches[0];
		$this->assertEquals( 'H1', $match->winner_name );
		$this->assertEquals( 'H2', $match->winnerpartner );
	}

	public function test_report_result_shared(): void {
		$fixture = $this->create_reporting_fixture( [
			'is_shared' => true,
			'custom'    => [ 'sets' => [ 1 => ['player1' => 6, 'player2' => 4] ] ],
		] );

		$competition = $this->create_standard_competition( [ 'name' => 'Comp' ] );
		$event = $this->create_standard_event( [ 'name' => 'Event' ] );
		$league = $this->create_standard_league( [ 'title' => 'League' ] );

		$repository_provider = $this->setup_repository_provider_mock($league, $event, $competition);
		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway() );
		
		$result = $service->report_result( $fixture );
		$this->assertEquals( 'N', $result->matches[0]->score_code );
	}

	public function test_report_result_uses_option_gateway_for_site_name(): void {
		$fixture = $this->create_reporting_fixture( [
			'custom' => [ 'sets' => [ 1 => [ 'player1' => 6, 'player2' => 4 ] ] ],
		] );

		$competition = $this->create_standard_competition( [ 'name' => 'Championship 2026' ] );
		$event       = $this->create_standard_event( [ 'name' => 'Men Singles' ] );
		$league      = $this->create_standard_league( [ 'title' => 'Division 1' ] );

		$repository_provider = $this->setup_repository_provider_mock( $league, $event, $competition );
		$option_gateway      = $this->createMock( Option_Gateway_Interface::class );
		$option_gateway->expects( $this->once() )
			->method( 'get_option' )
			->with( 'blogname', '' )
			->willReturn( 'Leigh &amp; Westcliff Tennis' );

		$service = new Result_Reporting_Service( $repository_provider, $option_gateway );
		$result  = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertSame( 'Leigh & Westcliff Tennis Championship 2026', $result->tournament );
	}

	public function test_report_result_uses_explicit_site_name(): void {
		$fixture = $this->create_reporting_fixture( [
			'custom' => [ 'sets' => [ 1 => [ 'player1' => 6, 'player2' => 4 ] ] ],
		] );

		$competition = $this->create_standard_competition( [ 'name' => 'Championship 2026' ] );
		$event       = $this->create_standard_event( [ 'name' => 'Men Singles' ] );
		$league      = $this->create_standard_league( [ 'title' => 'Division 1' ] );

		$repository_provider = $this->setup_repository_provider_mock( $league, $event, $competition );

		$service = new Result_Reporting_Service( $repository_provider, $this->create_option_gateway(), 'Custom Club' );
		$result  = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertSame( 'Custom Club Championship 2026', $result->tournament );
	}

	private function create_option_gateway( string $site_name = 'Test Site' ): Option_Gateway_Interface {
		$option_gateway = $this->createStub( Option_Gateway_Interface::class );
		$option_gateway->method( 'get_option' )->willReturn( $site_name );

		return $option_gateway;
	}

	private function create_rubber_entity( int $id, int $status ): Rubber {
		return new class( $id, $status ) extends Rubber {
			public function __construct( int $id, int $status ) {
				parent::__construct();
				$this->id        = $id;
				$this->status    = $status;
				$this->winner_id = 1;
				$this->loser_id  = 2;
				$this->custom    = [ 'sets' => [ 1 => [ 'player1' => 6, 'player2' => 4 ] ] ];
				$this->players   = [
					'home' => [ '1' => (object) [ 'display_name' => 'Rubber Winner' ] ],
					'away' => [ '1' => (object) [ 'display_name' => 'Rubber Loser' ] ],
				];
			}
		};
	}

	private function setup_repository_provider_mock($league, $event, $competition, $home_team = null, $away_team = null, array $rubbers = []): Repository_Provider {
		$league_repo = $this->createStub( League_Repository_Interface::class );
		$league_repo->method('find_by_id')->willReturn($league);

		$event_repo = $this->createStub( Event_Repository_Interface::class );
		$event_repo->method('find_by_id')->willReturn($event);

		$competition_repo = $this->createStub( Competition_Repository_Interface::class );
		$competition_repo->method('find_by_id')->willReturn($competition);

		$rubber_repo = $this->createStub( Rubber_Repository_Interface::class );
		$rubber_repo->method('find_by_fixture_id')->willReturn($rubbers);

		$team_repo = $this->createStub( Team_Repository_Interface::class );
		if (!$home_team) {
			$home_team = $this->createStub( Team::class );
			$home_team->method('get_players')->willReturn([ (object)['display_name' => 'Home'] ]);
		}
		if (!$away_team) {
			$away_team = $this->createStub( Team::class );
			$away_team->method('get_players')->willReturn([ (object)['display_name' => 'Away'] ]);
		}
		$team_repo->method('find_by_id')->willReturnMap([ [1, $home_team], [2, $away_team] ]);

		$repository_provider = $this->createStub( Repository_Provider::class );
		$repository_provider->method('get_league_repository')->willReturn($league_repo);
		$repository_provider->method('get_event_repository')->willReturn($event_repo);
		$repository_provider->method('get_competition_repository')->willReturn($competition_repo);
		$repository_provider->method('get_rubber_repository')->willReturn($rubber_repo);
		$repository_provider->method('get_team_repository')->willReturn($team_repo);

		return $repository_provider;
	}
}
}
