<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Scoring\Scoring_Context;
use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Event_Matches_Hydration_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Simple_Container $container;
	private $event_repository;
	private $fixture_repository;
	private Event $event;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();

		$this->container          = new Simple_Container();
		$this->event_repository   = $this->createMock( Event_Repository_Interface::class );
		$this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );

		$this->container->set( 'event_repository', $this->event_repository );
		$this->container->set( 'fixture_repository', $this->fixture_repository );

		$competition_service = $this->createMock( Competition_Service::class );
		$this->container->set( 'competition_service', $competition_service );

		$competition                  = $this->createMock( Competition::class );
		$competition->settings        = new Competition_Settings( array( 'mode' => 'league' ) );
		$competition->type            = Competition_Type::LEAGUE;
		$competition->is_player_entry = false;
		$competition->standings       = array();
		$competition_service->method( 'get_by_id' )->willReturn( $competition );

		$racketmanager            = $this->getMockBuilder( RacketManager::class )
			->disableOriginalConstructor()
			->getMock();
		$racketmanager->container = $this->container;
		$GLOBALS['racketmanager'] = $racketmanager;

		$event_raw                 = new stdClass();
		$event_raw->id             = 42;
		$event_raw->name           = 'Men\'s Doubles';
		$event_raw->type           = 'doubles';
		$event_raw->competition_id = 1;
		$event_raw->seasons        = '[]';
		$event_raw->num_rubbers    = 4;
		$event_raw->num_sets       = 3;

		$this->event = new Event( $event_raw );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_get_matches_hydrates_fixtures_via_repository_without_get_match(): void {
		$row_one     = (object) array( 'id' => 11, 'date' => '2026-05-01 18:00' );
		$row_two     = (object) array( 'id' => 12, 'date' => '2026-05-02 18:00' );
		$fixture_one = Fixture_Factory::from_array(
			array(
				'id'        => 11,
				'date'      => '2026-05-01 18:00',
				'home_team' => '1',
				'away_team' => '2',
			)
		);
		$fixture_two = Fixture_Factory::from_array(
			array(
				'id'        => 12,
				'date'      => '2026-05-02 18:00',
				'home_team' => '3',
				'away_team' => '4',
			)
		);

		$this->event_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with(
				42,
				$this->callback(
					static function ( array $args ): bool {
						return '2026' === ( $args['season'] ?? null ) && false === ( $args['count'] ?? true );
					}
				)
			)
			->willReturn( array( $row_one, $row_two ) );

		$this->fixture_repository->expects( $this->exactly( 2 ) )
			->method( 'find_by_id' )
			->willReturnCallback(
				static function ( $id ) use ( $fixture_one, $fixture_two ): ?Fixture {
					return match ( (int) $id ) {
						11      => $fixture_one,
						12      => $fixture_two,
						default => null,
					};
				}
			);

		$matches = $this->event->get_matches( array( 'season' => '2026' ) );

		$this->assertCount( 2, $matches );
		$this->assertInstanceOf( Fixture::class, $matches[0] );
		$this->assertInstanceOf( Fixture::class, $matches[1] );
		$this->assertSame( 11, $matches[0]->id );
		$this->assertSame( 12, $matches[1]->id );
		$this->assertSame( 'alternate', $matches[0]->class );
		$this->assertSame( '', $matches[1]->class );
		$this->assertSame( '2026-05-01 18:00', $matches[0]->date );
	}

	public function test_get_matches_count_returns_integer_without_fixture_hydration(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with(
				42,
				$this->callback(
					static function ( array $args ): bool {
						return true === ( $args['count'] ?? false );
					}
				)
			)
			->willReturn( 3 );

		$this->fixture_repository->expects( $this->never() )
			->method( 'find_by_id' );

		$result = $this->event->get_matches( array( 'count' => true ) );

		$this->assertSame( 3, $result );
	}

	public function test_get_matches_loads_player_rubbers_on_hydrated_fixtures(): void {
		$row     = (object) array( 'id' => 21 );
		$rubber  = $this->createStub( Rubber::class );
		$fixture = $this->getMockBuilder( Fixture::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_rubbers' ) )
			->getMock();
		$fixture->id = 21;
		$fixture->expects( $this->once() )
			->method( 'get_rubbers' )
			->with( 7 )
			->willReturn( array( $rubber ) );

		$this->event_repository->expects( $this->once() )
			->method( 'query_matches' )
			->with(
				42,
				$this->callback(
					static function ( array $args ): bool {
						return 7 === ( $args['player'] ?? null );
					}
				)
			)
			->willReturn( array( $row ) );

		$this->fixture_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 21 )
			->willReturn( $fixture );

		$matches = $this->event->get_matches( array( 'player' => 7 ) );

		$this->assertCount( 1, $matches );
		$this->assertSame( $fixture, $matches[0] );
		$this->assertSame( array( $rubber ), $matches[0]->rubbers );
		$this->assertSame( 'alternate', $matches[0]->class );
	}

	public function test_get_matches_skips_missing_fixtures_when_repository_misses_a_row(): void {
		$row_found = (object) array( 'id' => 10 );
		$row_miss  = (object) array( 'id' => 99 );
		$fixture   = Fixture_Factory::from_array(
			array(
				'id'   => 10,
				'date' => '2026-05-01 18:00:00',
			)
		);

		$this->event_repository->expects( $this->once() )
			->method( 'query_matches' )
			->willReturn( array( $row_found, $row_miss ) );

		$this->fixture_repository->expects( $this->exactly( 2 ) )
			->method( 'find_by_id' )
			->willReturnCallback(
				static function ( $id ) use ( $fixture ): ?Fixture {
					return 10 === (int) $id ? $fixture : null;
				}
			);

		$matches = $this->event->get_matches( array() );

		$this->assertCount( 1, $matches );
		$this->assertSame( 10, $matches[0]->id );
		$this->assertSame( '2026-05-01 18:00', $matches[0]->date );
	}

	public function test_get_matches_derives_title_from_team_service_and_trims_seconds_on_full_datetime(): void {
		$league_repo = $this->createMock( League_Repository_Interface::class );
		$this->container->set( 'league_repository', $league_repo );

		$league_mock        = $this->createMock( League::class );
		$league_mock->id    = 77;
		$league_mock->title = 'Division 1';

		$league_repo->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 77 )
			->willReturn( $league_mock );

		$competition_service = $this->container->get( 'competition_service' );
		$competition_service->method( 'get_league_repository' )
			->willReturn( $league_repo );

		$team_service = $this->createMock( Team_Service::class );
		$this->container->set( 'team_service', $team_service );

		$home_team = $this->createMock( Team::class );
		$home_team->method( 'get_name' )->willReturn( 'Westcliff Men 1' );
		$home_dto = new Team_Details_DTO( $home_team );

		$away_team = $this->createMock( Team::class );
		$away_team->method( 'get_name' )->willReturn( 'Leigh Men 1' );
		$away_dto = new Team_Details_DTO( $away_team );

		$team_service->method( 'get_team_details' )
			->willReturnCallback(
				static function ( int $team_id ) use ( $home_dto, $away_dto ): ?Team_Details_DTO {
					return match ( $team_id ) {
						101     => $home_dto,
						102     => $away_dto,
						default => null,
					};
				}
			);

		$row     = (object) array( 'id' => 50 );
		$fixture = Fixture_Factory::from_array(
			array(
				'id'        => 50,
				'league_id' => 77,
				'home_team' => '101',
				'away_team' => '102',
				'date'      => '2026-06-15 19:30:45',
				'match_day' => 1,
			)
		);

		$this->event_repository->expects( $this->once() )
			->method( 'query_matches' )
			->willReturn( array( $row ) );

		$this->fixture_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 50 )
			->willReturn( $fixture );

		$matches = $this->event->get_matches( array() );

		$this->assertCount( 1, $matches );
		$match = $matches[0];
		$this->assertSame( '2026-06-15 19:30', $match->date );
		$this->assertSame( 'Westcliff Men 1 - Leigh Men 1', $match->match_title );
		$this->assertSame( 'Westcliff Men 1 - Leigh Men 1', $match->get_title() );
		$this->assertNotNull( $match->league );
		$this->assertSame( 'Division 1', $match->league->title );
	}

	public function test_fixture_get_title_is_pure_and_does_not_cache_stale_title(): void {
		$fixture = Fixture_Factory::from_array(
			array(
				'id'        => 60,
				'home_team' => '1',
				'away_team' => '2',
			)
		);

		$home_team_obj        = new stdClass();
		$home_team_obj->title = 'Alpha';
		$away_team_obj        = new stdClass();
		$away_team_obj->title = 'Beta';

		$fixture->teams = array(
			'home' => $home_team_obj,
			'away' => $away_team_obj,
		);

		$this->assertSame( 'Alpha - Beta', $fixture->get_title() );

		// Modify team titles and verify get_title reflects the change dynamically
		$fixture->teams['home']->title = 'Gamma';
		$this->assertSame( 'Gamma - Beta', $fixture->get_title() );
	}

	public function test_fixture_get_title_uses_unknown_and_bye_for_unresolved_team_ids(): void {
		$team_service = $this->createMock( Team_Service::class );
		$team_service->method( 'get_team_details' )
			->willThrowException( new Team_Not_Found_Exception( 'Missing team' ) );
		$this->container->set( 'team_service', $team_service );

		$fixture_empty = Fixture_Factory::from_array(
			array(
				'id' => 61,
			)
		);
		$this->assertSame( 'Unknown - Unknown', $fixture_empty->get_title() );

		$fixture_with_missing_teams = Fixture_Factory::from_array(
			array(
				'id'        => 62,
				'home_team' => '999',
				'away_team' => '998',
			)
		);
		$this->assertSame( 'Unknown - Unknown', $fixture_with_missing_teams->get_title() );
		$this->assertSame( '999', $fixture_with_missing_teams->get_teams()['home']->id );

		$fixture_with_bye = Fixture_Factory::from_array(
			array(
				'id'        => 63,
				'home_team' => '-1',
				'away_team' => '999',
			)
		);
		$this->assertSame( 'Bye - Unknown', $fixture_with_bye->get_title() );
		$this->assertSame( '-1', $fixture_with_bye->get_teams()['home']->id );
	}

	public function test_fixture_final_round_is_included_in_scoring_context(): void {
		$fixture = Fixture_Factory::from_array(
			array(
				'id'    => 64,
				'final' => 'Final',
			)
		);
		$league                   = $this->createMock( League::class );
		$league->num_sets_to_win   = 2;
		$league->scoring           = 'TB';
		$league->is_championship   = true;
		$league->num_rubbers       = 5;
		$league->num_sets          = 3;
		$league->expects( $this->once() )->method( 'get_point_rule' )->willReturn( array() );
		$fixture->league = $league;

		$this->assertSame( 'Final', $fixture->final_round );
		$scoring_context = Scoring_Context::from_legacy_match( $fixture );
		$this->assertSame( 'Final', $scoring_context->final_round );
		$this->assertSame( 5, $scoring_context->num_rubbers );
	}

	public function test_player_query_service_with_null_league_handles_gracefully_without_fatal(): void {
		$fixture = Fixture_Factory::from_array(
			array(
				'id'        => 70,
				'season'    => '2026',
				'home_team' => '1',
				'away_team' => '2',
			)
		);
		$fixture->league = null;

		$player               = $this->getMockBuilder( Player::class )
			->disableOriginalConstructor()
			->getMock();
		$player->id           = 5;
		$player->display_name = 'Test Player';
		$player->matches      = array();

		$player_query_service = new Player_Query_Service();

		$this->event_repository->method( 'query_matches' )->willReturn( array( (object) array( 'id' => 70 ) ) );
		$this->fixture_repository->expects( $this->once() )->method( 'find_by_id' )->with( 70 )->willReturn( $fixture );

		$result = $player_query_service->get_matches( $player, $this->event, '2026', 'event' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( '', $player->matches );
		$this->assertNull( $player->matches['']['league'] );
		$this->assertSame( $fixture, $player->matches['']['matches'][0] );
	}

	public function test_player_query_service_calculates_statistics_with_real_rubbers(): void {
		$league_mock        = $this->createMock( League::class );
		$league_mock->id    = 88;
		$league_mock->title = 'Division 2';

		$rubber_raw                = new stdClass();
		$rubber_raw->id            = 1;
		$rubber_raw->match_id      = 80;
		$rubber_raw->rubber_number = 1;
		$rubber_raw->winner_id     = 1;
		$rubber_raw->loser_id      = 2;
		$rubber_raw->home_points   = 2.0;
		$rubber_raw->away_points   = 0.0;
		$rubber_raw->date          = '2026-06-15 19:30:00';
		$rubber_raw->type          = 'ms';
		$rubber_raw->title         = 'Rubber 1';
		$rubber_raw->custom        = array(
			'sets' => array(
				array( 'player1' => '6', 'player2' => '4' ),
				array( 'player1' => '6', 'player2' => '2' ),
			),
		);

		$rubber          = new Rubber( $rubber_raw );
		$rubber->title   = 'Rubber 1';
		$rubber->players = array(
			'home' => array(
				'1' => (object) array( 'display_name' => 'Test Player' ),
			),
			'away' => array(
				'1' => (object) array( 'display_name' => 'Opponent Player' ),
			),
		);

		$fixture = $this->getMockBuilder( Fixture::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_rubbers', 'get_league' ) )
			->getMock();
		$fixture->id        = 80;
		$fixture->season    = '2026';
		$fixture->home_team = '1';
		$fixture->away_team = '2';
		$fixture->method( 'get_league' )->willReturn( $league_mock );
		$fixture->method( 'get_rubbers' )->willReturn( array( $rubber ) );

		$player               = $this->getMockBuilder( Player::class )
			->disableOriginalConstructor()
			->getMock();
		$player->id           = 5;
		$player->display_name = 'Test Player';
		$player->matches      = array();

		$player_query_service = new Player_Query_Service();

		$this->event_repository->method( 'query_matches' )->willReturn( array( (object) array( 'id' => 80 ) ) );
		$this->fixture_repository->expects( $this->once() )->method( 'find_by_id' )->with( 80 )->willReturn( $fixture );

		$player_query_service->get_matches( $player, $this->event, '2026', 'event' );

		$this->assertArrayHasKey( 'Division 2', $player->matches );
		$this->assertArrayHasKey( 'Rubber 1', $player->statistics );
		$this->assertSame( 1, $player->statistics['Rubber 1']['played']['winner'] );
		$this->assertSame( 2, $player->statistics['Rubber 1']['sets']['winner'] );
		$this->assertSame( 12, $player->statistics['Rubber 1']['games']['winner'] );
		$this->assertSame( 6, $player->statistics['Rubber 1']['games']['loser'] );
	}

	public function test_template_team_matches_renders_fixture_title_cleanly(): void {
		$fixture = Fixture_Factory::from_array(
			array(
				'id'        => 11,
				'league_id' => 77,
				'home_team' => '101',
				'away_team' => '102',
			)
		);
		$matches = $this->get_event_matches_for_template( $fixture );

		ob_start();
		include __DIR__ . '/../../../../templates/event/team-matches.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( '<option value="11">Westcliff Men 1 - Leigh Men 1</option>', $output );
	}

	public function test_template_admin_event_matches_renders_fixture_cleanly(): void {
		$fixture = Fixture_Factory::from_array(
			array(
				'id'        => 50,
				'date'      => '2026-06-15 19:30:00',
				'match_day' => 1,
				'league_id' => 77,
				'home_team' => '101',
				'away_team' => '102',
			)
		);
		$matches = $this->get_event_matches_for_template( $fixture );

		ob_start();
		include __DIR__ . '/../../../../templates/admin/event/matches.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( '<td>2026-06-15 19:30</td>', $output );
		$this->assertStringContainsString( '<td>Westcliff Men 1 - Leigh Men 1</td>', $output );
		$this->assertStringContainsString( '<td>Division 1</td>', $output );
	}

	private function get_event_matches_for_template( Fixture $fixture ): array {
		$league_repository = $this->createMock( League_Repository_Interface::class );
		$league            = $this->createMock( League::class );
		$league->id        = 77;
		$league->title     = 'Division 1';
		$league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 77 )->willReturn( $league );
		$this->container->set( 'league_repository', $league_repository );
		$this->container->get( 'competition_service' )->method( 'get_league_repository' )->willReturn( $league_repository );

		$home_team = $this->createMock( Team::class );
		$home_team->method( 'get_name' )->willReturn( 'Westcliff Men 1' );
		$away_team = $this->createMock( Team::class );
		$away_team->method( 'get_name' )->willReturn( 'Leigh Men 1' );
		$team_service = $this->createMock( Team_Service::class );
		$team_service->method( 'get_team_details' )->willReturnMap(
			array(
				array( 101, new Team_Details_DTO( $home_team ) ),
				array( 102, new Team_Details_DTO( $away_team ) ),
			)
		);
		$this->container->set( 'team_service', $team_service );

		$this->event_repository->method( 'query_matches' )->willReturn( array( (object) array( 'id' => $fixture->id ) ) );
		$this->fixture_repository->expects( $this->once() )->method( 'find_by_id' )->with( $fixture->id )->willReturn( $fixture );

		$matches = $this->event->get_matches( array() );
		$this->assertInstanceOf( Fixture::class, $matches[0] );
		$this->assertNull( $matches[0]->fixture_title );
		$this->assertSame( 'Westcliff Men 1 - Leigh Men 1', $matches[0]->match_title );

		return $matches;
	}
}
