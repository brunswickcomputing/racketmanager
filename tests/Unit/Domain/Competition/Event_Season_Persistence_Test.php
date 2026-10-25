<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Tournament\Championship_Settings;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Admin\Competition\Competition_Season_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

final class Event_Season_Persistence_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	private function make_event( int $id = 5, array $seasons = array() ): Event {
		return new Event(
			(object) array(
				'id'             => $id,
				'name'           => 'Men Doubles ' . $id,
				'type'           => 'MD',
				'num_sets'       => 3,
				'num_rubbers'    => 2,
				'settings'       => '',
				'seasons'        => wp_json_encode( $seasons ),
				'competition_id' => 10,
			)
		);
	}

	private function make_competition( int $id = 10, array $seasons = array() ): Competition {
		return Competition::from_object(
			(object) array(
				'id'      => $id,
				'name'    => 'Summer League',
				'sport'   => 'tennis',
				'type'    => 'league',
				'seasons' => wp_json_encode( $seasons ),
			)
		);
	}

	public function test_legacy_serialized_settings_and_seasons_keep_their_keys_and_json_wire_format(): void {
		$seasons  = array( '2026' => array( 'name' => '2026', 'rounds' => array( 1, 2 ) ) );
		$settings = array( 'mode' => 'knockout' );
		$event    = new Event( (object) array(
			'name'     => 'Open',
			'settings' => serialize( $settings ),
			'seasons'  => serialize( $seasons ),
		) );

		$this->assertSame( $settings, $event->get_settings() );
		$this->assertSame( $seasons, $event->get_seasons() );
		$this->assertSame( json_encode( $seasons ), $event->get_seasons_json() );
		$this->assertFalse( $event->update_seasons( $seasons ) );
		$this->assertTrue( $event->update_seasons( array( '2027' => array( 'name' => '2027' ) ) ) );
		$this->assertSame( '{"2027":{"name":"2027"}}', $event->get_seasons_json() );
	}

	public function test_invalid_serialized_settings_are_empty_and_invalid_seasons_remain_unmodified(): void {
		$event = new Event( (object) array( 'name' => 'Open', 'settings' => 'a:2:{bad', 'seasons' => 'not-json' ) );
		$this->assertSame( array(), $event->get_settings() );
		$this->assertSame( 'not-json', $event->get_seasons_json() );
		$this->assertSame( array(), $event->get_seasons() );
	}

	public function test_native_json_encoding_keeps_wordpress_default_shape_and_sanitizes_invalid_utf8(): void {
		$invalid_name = "Broken\xB1";
		$event = new Event( (object) array( 'name' => 'Open', 'seasons' => array( '2026' => array( 'name' => $invalid_name ) ) ) );
		$expected = function_exists( 'mb_convert_encoding' ) ? 'Broken?' : 'Broken�';
		$this->assertSame( json_encode( array( '2026' => array( 'name' => $expected ) ) ), $event->get_seasons_json() );
		$this->assertSame( $expected, $event->get_seasons()['2026']['name'] );
	}

	public function test_competition_season_json_keeps_associative_year_keys(): void {
		$competition = new Competition( 'Summer', 'league', 'adults', array( '2026' => array( 'name' => '2026' ) ) );
		$this->assertSame( '{"2026":{"name":"2026"}}', $competition->get_seasons_json() );
	}

	public function test_update_seasons_persists_to_repository_and_returns_true(): void {
		$event = $this->make_event( 5, array( '2025' => array( 'name' => '2025' ) ) );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with(
				5,
				array(
					'2025' => array( 'name' => '2025' ),
					'2026' => array( 'name' => '2026' ),
				)
			);

		$query_service = new Event_Query_Service( $event_repo );
		$result        = $query_service->update_seasons(
			$event,
			array(
				'2025' => array( 'name' => '2025' ),
				'2026' => array( 'name' => '2026' ),
			)
		);

		$this->assertTrue( $result );
		$this->assertCount( 2, $event->get_seasons() );
	}

	public function test_update_seasons_returns_false_when_seasons_unchanged(): void {
		$event = $this->make_event( 5, array( '2026' => array( 'name' => '2026' ) ) );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->never() )->method( 'update_seasons' );

		$query_service = new Event_Query_Service( $event_repo );
		$result        = $query_service->update_seasons(
			$event,
			array( '2026' => array( 'name' => '2026' ) )
		);

		$this->assertFalse( $result );
	}

	public function test_update_season_persists_seasons_through_repository(): void {
		$event = $this->make_event( 5, array( '2026' => array( 'name' => '2026', 'num_match_days' => 5 ) ) );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with( 5, array( '2026' => array( 'name' => '2026', 'num_match_days' => 10 ) ) );

		$query_service = new Event_Query_Service( $event_repo );
		$query_service->update_season(
			$event,
			array( 'name' => '2026', 'num_match_days' => 10 )
		);

		$this->assertSame( 10, $event->get_seasons()['2026']['num_match_days'] );
	}

	public function test_update_season_does_not_persist_when_season_unchanged(): void {
		$event = $this->make_event( 5, array( '2026' => array( 'name' => '2026', 'num_match_days' => 5 ) ) );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->never() )->method( 'update_seasons' );

		$query_service = new Event_Query_Service( $event_repo );
		$query_service->update_season(
			$event,
			array( 'name' => '2026', 'num_match_days' => 5 )
		);

		$this->assertSame( 5, $event->get_seasons()['2026']['num_match_days'] );
	}

	public function test_event_update_seasons_updates_in_memory_only(): void {
		$event   = $this->make_event( 5, array( '2025' => array( 'name' => '2025' ) ) );
		$updated = $event->update_seasons(
			array(
				'2025' => array( 'name' => '2025' ),
				'2026' => array( 'name' => '2026' ),
			)
		);

		$this->assertTrue( $updated );
		$this->assertCount( 2, $event->get_seasons() );

		$unchanged = $event->update_seasons(
			array(
				'2025' => array( 'name' => '2025' ),
				'2026' => array( 'name' => '2026' ),
			)
		);
		$this->assertFalse( $unchanged );
	}

	public function test_event_update_season_updates_in_memory_only(): void {
		$event = $this->make_event( 5, array( '2026' => array( 'name' => '2026', 'num_match_days' => 5 ) ) );
		$event->update_season( array( 'name' => '2026', 'num_match_days' => 10 ) );
		$this->assertSame( 10, $event->get_seasons()['2026']['num_match_days'] );
	}

	public function test_delete_season_deletes_league_teams_and_persists_seasons(): void {
		$event = $this->make_event(
			5,
			array(
				2025 => array( 'name' => '2025' ),
				2026 => array( 'name' => '2026' ),
			)
		);

		$mock_league_row = (object) array( 'id' => 44 );

		$league_service = $this->createMock( League_Service::class );
		$league_service->expects( $this->once() )
			->method( 'delete_season_fixtures' )
			->with( 44, 2026 );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'query_leagues' )
			->with( 5 )
			->willReturn( array( $mock_league_row ) );
		$event_repo->expects( $this->once() )
			->method( 'delete_league_teams_for_season' )
			->with( 44, '2026' )
			->willReturn( true );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with( 5, array( 2025 => array( 'name' => '2025' ) ) )
			->willReturn( true );

		$comp_service = new Competition_Service(
			$this->createStub( \Racketmanager\RacketManager::class ),
			$this->createStub( \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface::class ),
			$this->createStub( Club_Repository_Interface::class ),
			$event_repo,
			$this->createStub( League_Repository_Interface::class ),
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( Season_Repository_Interface::class ),
			$this->createStub( Team_Repository_Interface::class ),
			null,
			static fn() => $league_service
		);

		$comp_service->delete_event_season( $event, 2026 );
		$this->assertArrayNotHasKey( 2026, $event->get_seasons() );
	}

	public function test_get_leagues_passes_query_args_to_repository(): void {
		$event = $this->make_event( 5 );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->exactly( 2 ) )
			->method( 'query_leagues' )
			->with(
				5,
				$this->callback(
					static function ( array $args ): bool {
						return isset( $args['limit'] );
					}
				)
			)
			->willReturn( array() );

		$query_service = new Event_Query_Service( $event_repo, league_repository: $this->createStub( League_Repository_Interface::class ) );
		$query_service->get_leagues( $event, array( 'limit' => 10, 'offset' => 0 ) );
		$query_service->get_leagues( $event, array( 'limit' => 5, 'offset' => 10 ) );
	}

	public function test_has_leagues_delegates_to_count_leagues(): void {
		$event = $this->make_event( 5 );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'count_leagues' )
			->with( 5 )
			->willReturn( 3 );

		$service = new Event_Query_Service( $event_repo );
		$this->assertSame( 3, $service->has_leagues( $event ) );
	}

	public function test_get_matches_with_player_filter_resolves_rubbers_via_injected_repository(): void {
		$event = $this->make_event( 5 );

		$fixture_raw = (object) array(
			'id'        => 101,
			'league_id' => 20,
			'home_team' => 1,
			'away_team' => 2,
			'season'    => '2026',
		);
		$fixture = \Racketmanager\Domain\Fixture\Fixture_Factory::from_object( $fixture_raw );

		$rubber = new Rubber( (object) array( 'id' => 501, 'match_id' => 101 ) );

		$event_repo = $this->createStub( Event_Repository_Interface::class );
		$event_repo->method( 'query_matches' )->willReturn( array( (object) array( 'id' => 101 ) ) );

		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo->expects( $this->once() )->method( 'find_by_id_with_relations' )->with( 101 )->willReturn( $fixture );

		$rubber_repo = $this->createMock( Rubber_Repository_Interface::class );
		$rubber_repo->expects( $this->once() )
			->method( 'find_by_fixture_id' )
			->with( 101, 777 )
			->willReturn( array( $rubber ) );

		$query_service = new Event_Query_Service( $event_repo, $fixture_repo, $rubber_repo );
		$matches = $query_service->get_matches( $event, array( 'player' => 777 ) );

		$this->assertCount( 1, $matches );
	}

	public function test_event_setup_action_dispatcher_persists_season_to_injected_event_repository(): void {
		$event = $this->make_event( 12, array( '2026' => array( 'name' => '2026', 'match_dates' => array() ) ) );

		$comp_service = $this->createMock( Competition_Service::class );
		$comp_service->expects( $this->once() )
			->method( 'get_event_by_id' )
			->with( 12 )
			->willReturn( $event );

		$guard = $this->createStub( Action_Guard_Interface::class );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with(
				12,
				$this->callback(
					static function ( array $seasons ): bool {
						return isset( $seasons[2026]['match_dates'] )
							&& array( '2026-06-01' ) === $seasons[2026]['match_dates'];
					}
				)
			)
			->willReturn( true );

		$dispatcher = new Event_Setup_Action_Dispatcher( $comp_service, $guard, $event_repo );

		$query  = array( 'event_id' => 12 );
		$post   = array(
			'action' => 'save',
			'season' => 2026,
			'rounds' => array(
				array( 'round' => '1', 'match_date' => '2026-06-01' ),
			),
		);
		$result = $dispatcher->handle( $query, $post );

		$this->assertSame( 'Match dates updated', $result->message );
	}

	public function test_league_setup_action_dispatcher_persists_season_to_injected_event_repository(): void {
		$competition = $this->make_competition(
			10,
			array(
				'2026' => array(
					'name'        => '2026',
					'match_dates' => array(),
				),
			)
		);

		$event = $this->make_event( 15, array( '2026' => array( 'name' => '2026', 'match_dates' => array() ) ) );

		$comp_service = $this->createMock( Competition_Service::class );
		$comp_service->expects( $this->once() )
			->method( 'get_competition' )
			->with( 10 )
			->willReturn( $competition );
		$comp_service->expects( $this->once() )
			->method( 'update_seasons' )
			->willReturn( true );
		$comp_service->expects( $this->once() )
			->method( 'get_events_for_competition' )
			->with( 10, 2026 )
			->willReturn( array( $event ) );

		$guard = $this->createStub( Action_Guard_Interface::class );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with(
				15,
				$this->callback(
					static function ( array $seasons ): bool {
						return isset( $seasons[2026]['match_dates'] )
							&& array( '2026-07-01' ) === $seasons[2026]['match_dates'];
					}
				)
			)
			->willReturn( true );

		$dispatcher = new League_Setup_Action_Dispatcher( $comp_service, $guard, $event_repo );

		$query  = array( 'competition_id' => 10 );
		$post   = array(
			'action' => 'save',
			'season' => 2026,
			'rounds' => array(
				array( 'round' => '1', 'match_date' => '2026-07-01' ),
			),
		);
		$result = $dispatcher->handle( $query, $post );

		$this->assertSame( 'Match dates updated', $result->message );
	}

	public function test_event_setup_action_dispatcher_does_not_persist_when_dates_unchanged(): void {
		$event = $this->make_event( 12, array( '2026' => array( 'name' => '2026', 'match_dates' => array( '2026-06-01' ) ) ) );

		$comp_service = $this->createStub( Competition_Service::class );
		$comp_service->method( 'get_event_by_id' )->willReturn( $event );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->never() )->method( 'update_seasons' );

		$dispatcher = new Event_Setup_Action_Dispatcher( $comp_service, $this->createStub( Action_Guard_Interface::class ), $event_repo );
		$result     = $dispatcher->handle(
			array( 'event_id' => 12 ),
			array(
				'action' => 'save',
				'season' => 2026,
				'rounds' => array( array( 'round' => '1', 'match_date' => '2026-06-01' ) ),
			)
		);

		$this->assertSame( 'No updates', $result->message );
	}

	public function test_league_setup_action_dispatcher_skips_unchanged_events_and_saves_changed_ones(): void {
		$competition = $this->make_competition( 10, array( '2026' => array( 'name' => '2026', 'match_dates' => array() ) ) );
		$unchanged   = $this->make_event( 15, array( '2026' => array( 'name' => '2026', 'match_dates' => array( '2026-07-01' ) ) ) );
		$changed     = $this->make_event( 16, array( '2026' => array( 'name' => '2026', 'match_dates' => array() ) ) );

		$comp_service = $this->createStub( Competition_Service::class );
		$comp_service->method( 'get_competition' )->willReturn( $competition );
		$comp_service->method( 'update_seasons' )->willReturn( true );
		$comp_service->method( 'get_events_for_competition' )->willReturn( array( $unchanged, $changed ) );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )->method( 'update_seasons' )->with( 16, $this->isArray() );

		$dispatcher = new League_Setup_Action_Dispatcher( $comp_service, $this->createStub( Action_Guard_Interface::class ), $event_repo );
		$result     = $dispatcher->handle(
			array( 'competition_id' => 10 ),
			array(
				'action' => 'save',
				'season' => 2026,
				'rounds' => array( array( 'round' => '1', 'match_date' => '2026-07-01' ) ),
			)
		);

		$this->assertSame( 'Match dates updated', $result->message );
	}

	public function test_dispatchers_require_event_repository(): void {
		foreach ( array( Event_Setup_Action_Dispatcher::class, League_Setup_Action_Dispatcher::class, Competition_Season_Action_Dispatcher::class ) as $class ) {
			$param = null;
			foreach ( ( new \ReflectionClass( $class ) )->getConstructor()->getParameters() as $p ) {
				if ( 'event_repository' === $p->getName() ) {
					$param = $p;
				}
			}
			$this->assertNotNull( $param, $class );
			$this->assertFalse( $param->isOptional(), $class );
			$this->assertFalse( $param->allowsNull(), $class );
		}
	}

	public function test_competition_season_service_cascade_season_to_events_persists_to_injected_event_repository(): void {
		$competition = $this->make_competition( 10, array() );

		$event = $this->make_event( 25, array() );

		$league_team_repo = $this->createStub( League_Team_Repository_Interface::class );
		$club_repo        = $this->createStub( Club_Repository_Interface::class );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'find_by_competition_id' )
			->with( 10 )
			->willReturn( array( $event ) );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with(
				25,
				$this->callback(
					static function ( array $seasons ): bool {
						return isset( $seasons['2026'] ) && true === ( $seasons['2026']['home_away'] ?? null );
					}
				)
			)
			->willReturn( true );

		$service = new Competition_Season_Service( $league_team_repo, $club_repo, $event_repo, $this->createStub( League_Repository_Interface::class ) );
		$season  = (object) array(
			'name'           => '2026',
			'home_away'      => true,
			'num_match_days' => 5,
			'match_dates'    => array( '2026-05-01' ),
		);

		$service->cascade_season_to_events( $competition, $season );
		$this->assertArrayHasKey( '2026', $event->get_seasons() );
	}

	public function test_competition_season_action_dispatcher_add_season_cascades_and_persists_to_event_repository(): void {
		$competition = $this->make_competition( 10, array() );

		$event = $this->make_event( 30, array() );

		$comp_service = $this->createMock( Competition_Service::class );
		$comp_service->expects( $this->atLeastOnce() )
			->method( 'get_by_id' )
			->with( 10 )
			->willReturn( $competition );
		$comp_service->expects( $this->atLeastOnce() )
			->method( 'get_competition' )
			->willReturn( $competition );
		$comp_service->expects( $this->once() )
			->method( 'add_season' )
			->willReturn( true );

		$finance_service  = $this->createStub( Finance_Service::class );
		$guard            = $this->createStub( Action_Guard_Interface::class );
		$league_team_repo = $this->createStub( League_Team_Repository_Interface::class );
		$club_repo        = $this->createStub( Club_Repository_Interface::class );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'find_by_competition_id' )
			->with( 10 )
			->willReturn( array( $event ) );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with(
				30,
				$this->callback(
					static function ( array $seasons ): bool {
						return isset( $seasons['2026'] );
					}
				)
			)
			->willReturn( true );

		$season_service = new Competition_Season_Service( $league_team_repo, $club_repo, $event_repo, $this->createStub( League_Repository_Interface::class ) );
		$dispatcher     = new Competition_Season_Action_Dispatcher(
			$comp_service,
			$finance_service,
			$guard,
			$season_service,
			$event_repo
		);

		$query  = array();
		$post   = array(
			'addSeason'      => 1,
			'competition_id' => 10,
			'season'         => '2026',
			'dateStart'      => '2026-05-01',
			'dateEnd'        => '2026-08-01',
			'dateOpen'       => '2026-03-01',
			'dateClose'      => '2026-04-15',
			'max_teams'      => 8,
			'teams_per_club' => 2,
			'teams_prom_relg'=> 2,
			'lowest_promotion'=> 1,
			'num_match_days' => 5,
			'round_length'   => 7,
			'home_away_diff' => 0,
			'filler_weeks'   => 0,
			'grade'          => 'A',
		);
		$result = $dispatcher->handle( $query, $post );

		$this->assertSame( 'Season added to competition', $result->message );
	}

	public function test_competition_season_action_dispatcher_edit_season_persists_changed_events_and_skips_unchanged(): void {
		$match_dates = array( '2026-05-01', '2026-05-08', '2026-05-15', '2026-05-22', '2026-05-29' );
		$competition = $this->make_competition(
			10,
			array(
				'2026' => array(
					'name'           => '2026',
					'num_match_days' => 5,
					'home_away'      => true,
					'match_dates'    => $match_dates,
					'grade'          => 'B',
				),
			)
		);

		$changed_event   = $this->make_event( 31, array( '2026' => array( 'name' => '2026', 'num_match_days' => 3, 'match_dates' => array() ) ) );
		$unchanged_event = $this->make_event( 32, array( '2026' => array( 'name' => '2026', 'home_away' => true, 'num_match_days' => 5, 'match_dates' => $match_dates ) ) );
		$unchanged_season = $unchanged_event->get_seasons();

		$comp_service = $this->createMock( Competition_Service::class );
		$comp_service->expects( $this->atLeastOnce() )
			->method( 'get_by_id' )
			->with( 10 )
			->willReturn( $competition );
		$comp_service->expects( $this->atLeastOnce() )
			->method( 'get_competition' )
			->willReturn( $competition );
		$comp_service->expects( $this->once() )
			->method( 'update_season' )
			->willReturn( true );
		$comp_service->expects( $this->once() )
			->method( 'get_events_for_competition' )
			->with( 10 )
			->willReturn( array( $changed_event, $unchanged_event ) );

		$finance_service  = $this->createStub( Finance_Service::class );
		$guard            = $this->createStub( Action_Guard_Interface::class );
		$league_team_repo = $this->createStub( League_Team_Repository_Interface::class );
		$club_repo        = $this->createStub( Club_Repository_Interface::class );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with(
				31,
				$this->callback(
					static function ( array $seasons ): bool {
						return isset( $seasons['2026'] )
							&& 5 === (int) $seasons['2026']['num_match_days']
							&& true === $seasons['2026']['home_away']
							&& array( '2026-05-01', '2026-05-08', '2026-05-15', '2026-05-22', '2026-05-29' ) === $seasons['2026']['match_dates'];
					}
				)
			)
			->willReturn( true );

		$season_service = new Competition_Season_Service( $league_team_repo, $club_repo, $event_repo, $this->createStub( League_Repository_Interface::class ) );
		$dispatcher     = new Competition_Season_Action_Dispatcher(
			$comp_service,
			$finance_service,
			$guard,
			$season_service,
			$event_repo
		);

		$query  = array();
		$post   = array(
			'editSeason'     => 1,
			'competition_id' => 10,
			'season'         => '2026',
			'dateStart'      => '2026-05-01',
			'dateEnd'        => '2026-08-01',
			'dateOpen'       => '2026-03-01',
			'dateClose'      => '2026-04-15',
			'max_teams'      => 8,
			'teams_per_club' => 2,
			'teams_prom_relg'=> 2,
			'lowest_promotion'=> 1,
			'num_match_days' => 5,
			'round_length'   => 7,
			'homeAway'       => 'true',
			'home_away_diff' => 0,
			'filler_weeks'   => 0,
			'grade'          => 'A',
			'match_dates'    => array( '2026-06-01' ),
		);
		$result = $dispatcher->handle( $query, $post );

		$this->assertSame( 'Season updated', $result->message );
		$this->assertSame( $unchanged_season, $unchanged_event->get_seasons() );
	}

	public function test_championship_admin_service_apply_championship_rounds_persists_season_to_event_repository(): void {
		$event = $this->make_event( 35, array( '2026' => array( 'name' => '2026', 'match_dates' => array() ) ) );

		$championship = new Championship(
			18,
			false,
			new Championship_Settings(),
			1,
			1,
			2,
			2,
			0,
			array(),
			array(),
			array(),
			'final'
		);

		$league_mock                    = $this->createMock( League::class );
		$league_mock->id                = 18;
		$league_mock->event_id          = 35;
		$league_mock->num_rubbers       = 2;
		$league_mock->championship      = $championship;
		$league_mock->expects( $this->once() )->method( 'set_rounds' );

		$league_service          = $this->createStub( League_Service::class );
		$fixture_service         = $this->createStub( Fixture_Service::class );
		$tournament_service      = $this->createStub( Tournament_Service::class );
		$league_team_repo        = $this->createStub( League_Team_Repository_Interface::class );
		$team_repo               = $this->createStub( Team_Repository_Interface::class );
		$fixture_repo            = $this->createStub( Fixture_Repository_Interface::class );
		$championship_manager    = ( new \ReflectionClass( Championship_Manager::class ) )->newInstanceWithoutConstructor();

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 35 )
			->willReturn( $event );
		$event_repo->expects( $this->once() )
			->method( 'update_seasons' )
			->with(
				35,
				$this->callback(
					static function ( array $seasons ): bool {
						return isset( $seasons[2026]['match_dates'] )
							&& array( '2026-06-15' ) === $seasons[2026]['match_dates'];
					}
				)
			)
			->willReturn( true );

		$service = new Championship_Admin_Service(
			$league_service,
			$fixture_service,
			$tournament_service,
			$league_team_repo,
			$team_repo,
			$fixture_repo,
			$championship_manager,
			$event_repo
		);

		$ref_method = new \ReflectionMethod( Championship_Admin_Service::class, 'apply_championship_rounds_and_fixtures' );
		$ref_method->invoke(
			$service,
			$league_mock,
			2026,
			array( 'final' => new stdClass() ),
			array( '2026-06-15' => array( new Fixture() ) ),
			array( 'name' => '2026' ),
			'create'
		);

		$this->assertSame( array( '2026-06-15' ), $event->get_seasons()[2026]['match_dates'] );
	}

	public function test_championship_admin_service_apply_championship_rounds_does_not_persist_when_rounds_and_dates_unchanged(): void {
		$event = $this->make_event(
			36,
			array(
				'2026' => array(
					'name'           => '2026',
					'match_dates'    => array( '2026-06-15' ),
					'num_match_days' => 1,
				),
			)
		);

		$championship = new Championship(
			19,
			false,
			new Championship_Settings(),
			1,
			1,
			2,
			2,
			0,
			array(),
			array(),
			array(),
			'final'
		);

		$league_mock               = $this->createMock( League::class );
		$league_mock->id           = 19;
		$league_mock->event_id     = 36;
		$league_mock->num_rubbers  = 2;
		$league_mock->championship = $championship;
		$league_mock->expects( $this->once() )->method( 'set_rounds' );

		$league_service       = $this->createStub( League_Service::class );
		$fixture_service      = $this->createStub( Fixture_Service::class );
		$tournament_service   = $this->createStub( Tournament_Service::class );
		$league_team_repo     = $this->createStub( League_Team_Repository_Interface::class );
		$team_repo            = $this->createStub( Team_Repository_Interface::class );
		$fixture_repo         = $this->createStub( Fixture_Repository_Interface::class );
		$championship_manager = ( new \ReflectionClass( Championship_Manager::class ) )->newInstanceWithoutConstructor();

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 36 )
			->willReturn( $event );
		$event_repo->expects( $this->never() )
			->method( 'update_seasons' );

		$service = new Championship_Admin_Service(
			$league_service,
			$fixture_service,
			$tournament_service,
			$league_team_repo,
			$team_repo,
			$fixture_repo,
			$championship_manager,
			$event_repo
		);

		$ref_method = new \ReflectionMethod( Championship_Admin_Service::class, 'apply_championship_rounds_and_fixtures' );
		$ref_method->invoke(
			$service,
			$league_mock,
			2026,
			array( 'final' => new stdClass() ),
			array( '2026-06-15' => array( new Fixture() ) ),
			array( 'name' => '2026' ),
			'create'
		);
	}

	public function test_competition_service_delete_seasons_deletes_league_teams_and_persists_event_seasons(): void {
		$competition = $this->make_competition(
			10,
			array(
				'2026' => array( 'name' => '2026' ),
			)
		);

		$event = $this->make_event(
			40,
			array(
				2025 => array( 'name' => '2025' ),
				2026 => array( 'name' => '2026' ),
			)
		);

		$mock_league_row = (object) array( 'id' => 77 );
		$league_service = $this->createMock( League_Service::class );
		$league_service->expects( $this->once() )
			->method( 'delete_season_fixtures' )
			->with( 77, 2026 );

		$racketmanager   = ( new \ReflectionClass( \Racketmanager\RacketManager::class ) )->newInstanceWithoutConstructor();
		$comp_repo       = $this->createMock( \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface::class );
		$comp_repo->expects( $this->atLeastOnce() )->method( 'find_by_id' )->with( 10 )->willReturn( $competition );
		$comp_repo->expects( $this->once() )->method( 'save' );

		$club_repo        = $this->createStub( Club_Repository_Interface::class );
		$league_repo      = $this->createStub( League_Repository_Interface::class );
		$league_team_repo = $this->createStub( League_Team_Repository_Interface::class );
		$season_repo      = $this->createStub( Season_Repository_Interface::class );
		$team_repo        = $this->createStub( Team_Repository_Interface::class );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )->method( 'find_by_competition_id' )->with( 10 )->willReturn( array( (object) array( 'id' => 40 ) ) );
		$event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 40 )->willReturn( $event );
		$event_repo->expects( $this->once() )->method( 'query_leagues' )->willReturn( array( $mock_league_row ) );
		$event_repo->expects( $this->once() )->method( 'delete_league_teams_for_season' )->with( 77, '2026' )->willReturn( true );
		$event_repo->expects( $this->once() )->method( 'update_seasons' )->with( 40, array( 2025 => array( 'name' => '2025' ) ) )->willReturn( true );

		$comp_service = new Competition_Service(
			$racketmanager,
			$comp_repo,
			$club_repo,
			$event_repo,
			$league_repo,
			$league_team_repo,
			$season_repo,
			$team_repo,
			null,
			static fn() => $league_service
		);

		$comp_service->delete_seasons( 10, array( 2026 => array( 'name' => '2026' ) ) );

		$this->assertArrayNotHasKey( 2026, $event->get_seasons() );
	}
}
