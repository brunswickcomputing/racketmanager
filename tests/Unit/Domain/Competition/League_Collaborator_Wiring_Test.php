<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Result\Result;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\League_Schedule_Service;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class League_Collaborator_Wiring_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		unset( $GLOBALS['racketmanager'], $GLOBALS['wp_stubs_cache'], $GLOBALS['wp_stubs_cache_enabled'], $GLOBALS['wp_stubs_cache_mode'] );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_factory_passes_collaborators_through_to_league(): void {
		$fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
		$standings_service  = $this->createStub( Standings_Service::class );
		$schedule_service   = $this->createStub( League_Schedule_Service::class );
		$notification       = $this->createStub( Competition_Notification_Service::class );
		$fixture_service    = $this->createStub( Fixture_Service::class );
		$result_manager     = $this->createStub( Fixture_Result_Manager::class );
		$app_notification   = $this->createStub( Notification_Service::class );
		$league_repository  = new League_Repository();

		$league = League_Factory::from_object(
			(object) array(
				'id'       => 12,
				'title'    => 'Division 1',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			),
			null,
			$league_repository,
			$fixture_repository,
			$standings_service,
			$schedule_service,
			$notification,
			$fixture_service,
			$result_manager,
			$app_notification
		);

		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( $league_repository, $league->get_league_repository() );
		$this->assertSame( $fixture_repository, $league->get_fixture_repository() );
		$this->assertSame( $standings_service, $league->get_standings_service() );
		$this->assertSame( $schedule_service, $league->get_schedule_service() );
		$this->assertSame( $notification, $league->get_notification_service() );
		$this->assertSame( $fixture_service, $league->get_fixture_service() );
		$this->assertSame( $result_manager, $league->get_fixture_result_manager() );
		$this->assertSame( $app_notification, $league->get_app_notification_service() );
	}

	public function test_repository_hydrates_league_with_lazy_collaborators_without_container(): void {
		$resolved = false;
		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$result_manager     = $this->createMock( Fixture_Result_Manager::class );
		$fixture            = Fixture_Factory::from_array(
			array(
				'id'        => 15,
				'home_team' => '100',
				'away_team' => '200',
				'status'    => 1,
				'custom'    => array( 'sets' => array( array( 'home' => 6, 'away' => 3 ) ) ),
			)
		);

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'       => 12,
				'title'    => 'Division 1',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$repository = new League_Repository(
			db: $wpdb,
			fixture_repository: static function () use ( &$resolved, $fixture_repository ) {
				$resolved = true;
				return $fixture_repository;
			},
			fixture_result_manager: $result_manager
		);

		$this->assertFalse( $resolved );

		$league = $repository->find_by_id( 12 );

		$this->assertTrue( $resolved );
		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( $fixture_repository, $league->get_fixture_repository() );
		$this->assertSame( $result_manager, $league->get_fixture_result_manager() );
		$this->assertSame( $repository, $league->get_league_repository() );

		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 15 )
			->willReturn( $fixture );
		$result_manager->expects( $this->once() )
			->method( 'confirm_result' )
			->with(
				$fixture,
				'',
				null,
				$this->isInstanceOf( Result::class )
			);

		$result_count = $league->update_match_results(
			array( 15 ),
			array( 15 => '5.0' ),
			array( 15 => '3.0' ),
			array( 15 => array( 'notes' => 'Great match' ) ),
			'2026',
			'Final',
			'Y'
		);

		$this->assertSame( 1, $result_count );
	}

	public function test_league_update_match_results_is_safe_without_global_container(): void {
		$fixture = Fixture_Factory::from_array(
			array(
				'id'        => 21,
				'home_team' => '1',
				'away_team' => '2',
				'status'    => 1,
				'custom'    => array(),
			)
		);
		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 21 )
			->willReturn( $fixture );
		$result_manager = $this->createMock( Fixture_Result_Manager::class );
		$result_manager->expects( $this->once() )
			->method( 'confirm_result' );

		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->set_fixture_repository( $fixture_repository );
		$league->set_fixture_result_manager( $result_manager );

		$this->assertSame(
			1,
			$league->update_match_results(
				array( 21 ),
				array( 21 => '2' ),
				array( 21 => '1' ),
				array(),
				'2026',
				'Final',
				'Y'
			)
		);
	}

	public function test_container_league_repository_wires_collaborators_and_update_match_results(): void {
		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'       => 44,
				'title'    => 'Premier',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$app = $this->createStub( RacketManager::class );
		$app->method( 'get_options' )->willReturn( array() );
		$app->site_url  = 'https://example.com';
		$app->site_name = 'Test Site';

		$container = Container_Bootstrap::boot( $app );
		$container->get( 'fixture_repository' );
		$container->get( 'standings_service' );
		$repository = $container->get( 'league_repository' );

		$this->assertInstanceOf( League_Repository::class, $repository );
		$this->assertInstanceOf( Fixture_Repository::class, $container->get( 'fixture_repository' ) );

		$league = $repository->find_by_id( 44 );
		$this->assertInstanceOf( League::class, $league );
		$this->assertInstanceOf( Fixture_Repository::class, $league->get_fixture_repository() );
		$this->assertInstanceOf( Fixture_Result_Manager::class, $league->get_fixture_result_manager() );
		$this->assertInstanceOf( Standings_Service::class, $league->get_standings_service() );
		$this->assertInstanceOf( Fixture_Service::class, $league->get_fixture_service() );
		$this->assertInstanceOf( League_Schedule_Service::class, $league->get_schedule_service() );
		$this->assertInstanceOf( Competition_Notification_Service::class, $league->get_notification_service() );
		$this->assertInstanceOf( Notification_Service::class, $league->get_app_notification_service() );

		$fixture = Fixture_Factory::from_array(
			array(
				'id'        => 90,
				'home_team' => '1',
				'away_team' => '2',
				'status'    => 1,
				'custom'    => array(),
			)
		);
		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 90 )
			->willReturn( $fixture );
		$result_manager = $this->createMock( Fixture_Result_Manager::class );
		$result_manager->expects( $this->once() )
			->method( 'confirm_result' )
			->with(
				$fixture,
				'',
				null,
				$this->isInstanceOf( Result::class )
			);

		$league->set_fixture_repository( $fixture_repository );
		$league->set_fixture_result_manager( $result_manager );

		$this->assertSame(
			1,
			$league->update_match_results(
				array( 90 ),
				array( 90 => '6' ),
				array( 90 => '3' ),
				array(),
				'2026',
				'Final',
				'Y'
			)
		);
	}

	public function test_cache_hit_rewires_bare_cached_league_when_read_by_wired_repository_in_clone_mode(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'clone';

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'       => 50,
				'title'    => 'Championship',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$bare_repo = new League_Repository( db: $wpdb );
		$cached_bare_league = $bare_repo->find_by_id( 50 );
		$this->assertInstanceOf( League::class, $cached_bare_league );
		$this->assertNull( $cached_bare_league->get_fixture_repository() );

		$fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
		$result_mgr   = $this->createStub( Fixture_Result_Manager::class );
		$wired_repo   = new League_Repository(
			db: $wpdb,
			fixture_repository: $fixture_repo,
			fixture_result_manager: $result_mgr
		);

		$hit_league = $wired_repo->find_by_id( 50 );
		$this->assertInstanceOf( League::class, $hit_league );
		$this->assertSame( $wired_repo, $hit_league->get_league_repository() );
		$this->assertSame( $fixture_repo, $hit_league->get_fixture_repository() );
		$this->assertSame( $result_mgr, $hit_league->get_fixture_result_manager() );
	}

	public function test_cache_hit_rewires_bare_cached_league_when_read_by_wired_repository_in_serialize_mode(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'serialize';

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'       => 51,
				'title'    => 'Conference',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$bare_repo = new League_Repository( db: $wpdb );
		$cached_bare_league = $bare_repo->find_by_id( 51 );
		$this->assertInstanceOf( League::class, $cached_bare_league );

		$fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
		$standings_svc = $this->createStub( Standings_Service::class );
		$wired_repo = new League_Repository(
			db: $wpdb,
			fixture_repository: $fixture_repo,
			standings_service: $standings_svc
		);

		$hit_league = $wired_repo->find_by_id( 51 );
		$this->assertInstanceOf( League::class, $hit_league );
		$this->assertSame( $wired_repo, $hit_league->get_league_repository() );
		$this->assertSame( $fixture_repo, $hit_league->get_fixture_repository() );
		$this->assertSame( $standings_svc, $hit_league->get_standings_service() );
	}

	public function test_cache_hit_by_name_rewires_cached_league(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'clone';

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'       => 52,
				'title'    => 'League One',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$bare_repo = new League_Repository( db: $wpdb );
		$bare_repo->find_by_name( 'League One' );

		$fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
		$wired_repo = new League_Repository( db: $wpdb, fixture_repository: $fixture_repo );

		$hit_league = $wired_repo->find_by_name( 'League One' );
		$this->assertInstanceOf( League::class, $hit_league );
		$this->assertSame( $fixture_repo, $hit_league->get_fixture_repository() );
	}

	public function test_wired_then_bare_read_preserves_collaborators_and_swaps_league_repository(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'clone';

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'       => 53,
				'title'    => 'League Two',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
		$wired_repo   = new League_Repository( db: $wpdb, fixture_repository: $fixture_repo );
		$wired_repo->find_by_id( 53 );

		$bare_repo   = new League_Repository( db: $wpdb );
		$read_league = $bare_repo->find_by_id( 53 );

		$this->assertSame( $bare_repo, $read_league->get_league_repository() );
		$this->assertSame( $fixture_repo, $read_league->get_fixture_repository() );
	}

	public function test_league_serialization_and_unserialization_omits_collaborators_safely(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 70;
		$league->title = 'Serialized League';
		$event = ( new \ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
		$league->event = $event;

		$fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
		$standings_svc = $this->createStub( Standings_Service::class );
		$schedule_svc  = $this->createStub( League_Schedule_Service::class );
		$notif_svc     = $this->createStub( Competition_Notification_Service::class );
		$fixture_svc   = $this->createStub( Fixture_Service::class );
		$result_mgr    = $this->createStub( Fixture_Result_Manager::class );
		$app_notif     = $this->createStub( Notification_Service::class );
		$league_repo   = new League_Repository();

		$league->set_fixture_repository( $fixture_repo );
		$league->set_standings_service( $standings_svc );
		$league->set_league_schedule_service( $schedule_svc );
		$league->set_competition_notification_service( $notif_svc );
		$league->set_fixture_service( $fixture_svc );
		$league->set_fixture_result_manager( $result_mgr );
		$league->set_notification_service( $app_notif );
		$league->set_league_repository( $league_repo );

		$serialized = serialize( $league );
		$this->assertIsString( $serialized );

		/** @var League $unserialized */
		$unserialized = unserialize( $serialized );
		$this->assertInstanceOf( League::class, $unserialized );
		$this->assertSame( 70, $unserialized->id );
		$this->assertSame( 'Serialized League', $unserialized->title );
		$this->assertNull( $unserialized->get_league_repository() );
		$this->assertNull( $unserialized->get_fixture_repository() );
		$this->assertNull( $unserialized->get_standings_service() );
		$this->assertNull( $unserialized->get_schedule_service() );
		$this->assertNull( $unserialized->get_notification_service() );
		$this->assertNull( $unserialized->get_fixture_service() );
		$this->assertNull( $unserialized->get_fixture_result_manager() );
		$this->assertNull( $unserialized->get_app_notification_service() );

		$league_repo->wire_league_collaborators( $unserialized );
		$this->assertSame( $league_repo, $unserialized->get_league_repository() );
	}

	public function test_league_with_closure_collaborators_in_repository_serializes_without_closure_error(): void {
		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'       => 80,
				'title'    => 'Closure League',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$repo = new League_Repository(
			db: $wpdb,
			fixture_repository: fn() => $this->createStub( Fixture_Repository_Interface::class ),
			standings_service: fn() => $this->createStub( Standings_Service::class )
		);

		$league = $repo->find_by_id( 80 );
		$event = ( new \ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
		$league->event = $event;

		$serialized = serialize( $league );
		$this->assertIsString( $serialized );

		$unserialized = unserialize( $serialized );
		$this->assertInstanceOf( League::class, $unserialized );
		$this->assertNull( $unserialized->get_fixture_repository() );
	}

	public function test_unwired_league_update_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 1;
		$league->title = 'T';
		$league->sequence = '1';

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot update League: League_Repository_Interface collaborator is required.' );
		$league->update( 'New', '2' );
	}

	public function test_unwired_league_delete_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 1;

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot delete League: League_Repository_Interface collaborator is required.' );
		$league->delete();
	}

	public function test_unwired_league_delete_team_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 1;

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot delete team from League: League_Repository_Interface collaborator is required.' );
		$league->delete_team( 10, '2026' );
	}

	public function test_unwired_league_withdraw_team_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 1;

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot withdraw team from League: League_Repository_Interface collaborator is required.' );
		$league->withdraw_team( 10, '2026' );
	}

	public function test_unwired_league_add_team_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 1;

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot add team to League: League_Repository_Interface collaborator is required.' );
		$league->add_team( 10, '2026' );
	}

	public function test_unwired_league_set_rounds_throws_runtime_exception_and_does_not_cache(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'clone';

		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 99;
		$league->seasons = array();

		$thrown = false;
		try {
			$league->set_rounds( '2026', array( 'round1' ) );
		} catch ( \RuntimeException $e ) {
			$thrown = true;
			$this->assertStringContainsString( 'Cannot set rounds: League_Repository_Interface collaborator is required.', $e->getMessage() );
		}

		$this->assertTrue( $thrown );
		$this->assertFalse( wp_cache_get( 99, 'leagues' ) );
	}

	public function test_league_set_rounds_does_not_cache_after_repository_save_fails(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'clone';

		$league_repository = $this->createMock( League_Repository::class );
		$league_repository->expects( $this->once() )
			->method( 'update_seasons' )
			->with( 99, array( '2026' => array( 'rounds' => array( 'round1' ) ) ) )
			->willReturn( false );

		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 99;
		$league->seasons = array();
		$league->set_league_repository( $league_repository );

		$league->set_rounds( '2026', array( 'round1' ) );

		$this->assertFalse( wp_cache_get( 99, 'leagues' ) );
	}

	public function test_league_set_rounds_caches_after_repository_save_succeeds(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'clone';

		$league_repository = $this->createMock( League_Repository::class );
		$league_repository->expects( $this->once() )
			->method( 'update_seasons' )
			->with( 99, array( '2026' => array( 'rounds' => array( 'round1' ) ) ) )
			->willReturn( true );

		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 99;
		$league->seasons = array();
		$league->set_league_repository( $league_repository );

		$league->set_rounds( '2026', array( 'round1' ) );

		$this->assertInstanceOf( League::class, wp_cache_get( 99, 'leagues' ) );
	}

	public function test_unwired_league_get_rank_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 1;

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot get rank: League_Repository_Interface collaborator is required.' );
		$league->get_rank( 10, '2026' );
	}

	public function test_unwired_league_get_status_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 1;

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot get status: League_Repository_Interface collaborator is required.' );
		$league->get_status( 10, '2026' );
	}

	public function test_unwired_league_get_standings_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot get standings: Standings_Service collaborator is required.' );
		$league->get_standings( array(), 1 );
	}

	public function test_unwired_league_update_standings_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot update standings: Standings_Service collaborator is required.' );
		$league->update_standings( '2026' );
	}

	public function test_unwired_league_set_teams_rank_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot set teams rank: Standings_Service collaborator is required.' );
		$league->set_teams_rank( '2026' );
	}

	public function test_unwired_league_save_standings_manually_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot save standings manually: Standings_Service collaborator is required.' );
		$league->save_standings_manually( array(), array(), array(), array() );
	}

	public function test_unwired_league_calculate_points_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot calculate points: Standings_Service collaborator is required.' );
		$league->calculate_points( (object) array() );
	}

	public function test_unwired_league_is_tie_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot determine tie: Standings_Service collaborator is required.' );
		$league->is_tie( (object) array(), (object) array() );
	}

	public function test_unwired_league_get_standings_data_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot get standings data: Standings_Service collaborator is required.' );
		$league->get_standings_data( 1 );
	}

	public function test_unwired_league_schedule_matches_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot schedule matches: League_Schedule_Service collaborator is required.' );
		$league->schedule_matches();
	}

	public function test_unwired_league_create_schedule_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot create schedule: League_Schedule_Service collaborator is required.' );
		$league->create_schedule( array(), '1', true );
	}

	public function test_unwired_league_create_match_schedule_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot create match schedule: League_Schedule_Service collaborator is required.' );
		$league->create_match_schedule( array(), array(), array(), '2026', false );
	}

	public function test_unwired_league_add_match_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot add match: Fixture_Service collaborator is required.' );
		$league->add_match( (object) array() );
	}

	public function test_unwired_league_update_match_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot update match: Fixture_Repository_Interface collaborator is required.' );
		$league->update_match( (object) array() );
	}

	public function test_unwired_league_update_match_results_throws_runtime_exception(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot update match results: Fixture_Repository_Interface and Fixture_Result_Manager collaborators are required.' );
		$league->update_match_results( array( 1 ), array( 1 => '1' ), array( 1 => '0' ), array(), '2026' );
	}

	public function test_production_get_league_object_path_works_with_and_without_container(): void {
		$script = dirname( __DIR__, 4 ) . '/tests/Fixtures/production-get-league-object-path.php';
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ), $output, $exit_code );

		$this->assertSame( 0, $exit_code, implode( "\n", $output ) );
		$this->assertSame( 'ok', implode( "\n", $output ) );
	}
}
