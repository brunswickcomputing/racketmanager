<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Tournament;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Tournament\Tournament_Hydration_DTO;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Domain\Tournament\Tournament_Factory;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Tournament_Collaborator_Wiring_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		unset( $GLOBALS['racketmanager'], $GLOBALS['wp'], $GLOBALS['wp_stubs_cache'], $GLOBALS['wp_stubs_cache_enabled'], $GLOBALS['wp_stubs_cache_mode'] );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_tournament_hydration_dto_and_factory(): void {
		$data = [
			'id'               => 42,
			'name'             => 'Summer Championship',
			'competition_id'   => 10,
			'season'           => '2026',
			'num_courts'       => 6,
			'start_time'       => '09:00',
			'date_end'         => '2026-08-15',
			'date_closing'     => '2026-08-01',
			'date_withdrawal'  => '2026-08-05',
			'date_open'        => '2026-07-01',
			'date_start'       => '2026-08-10',
			'venue'            => 3,
			'venue_name'       => 'Central Courts',
			'competition_code' => 'SUM26',
			'grade'            => 'Grade 3',
			'num_entries'      => 64,
			'type'             => 'Open',
			'information'      => [ 'entry_events' => [ 1, 2, 3 ] ],
		];

		$dto = Tournament_Hydration_DTO::from_array( $data );
		$this->assertSame( 42, $dto->id );
		$this->assertSame( 'Summer Championship', $dto->name );
		$this->assertSame( 10, $dto->competition_id );
		$this->assertSame( '2026', $dto->season );
		$this->assertSame( 6, $dto->num_courts );

		$tournament = Tournament_Factory::create_from_dto( $dto );
		$this->assertSame( 42, $tournament->id );
		$this->assertSame( 'Summer Championship', $tournament->name );
		$presenter = new Tournament_Presenter( 'Y-m-d' );
		$this->assertSame( '/tournament/summer-championship/', $presenter->get_link( $tournament ) );
		$this->assertSame( 'https://tennis.example.com/entry-form/summer-championship-tournament/', $presenter->get_entry_link( $tournament, 'https://tennis.example.com' ) );
		$dates = $presenter->present_dates( $tournament );
		$this->assertSame( '2026-08-15', $dates->date_display );
		$this->assertSame( '2026-08-01', $dates->date_closing_display );
	}

	public function test_tournament_hydration_dto_hydrates_payments_as_float(): void {
		$dto = Tournament_Hydration_DTO::from_object( (object) [ 'id' => 1, 'payments' => '12.50' ] );
		$this->assertIsFloat( $dto->payments );
		$this->assertSame( 12.5, $dto->payments );

		$dto_without = Tournament_Hydration_DTO::from_object( (object) [ 'id' => 1 ] );
		$this->assertNull( $dto_without->payments );

		$tournament           = new Tournament();
		$tournament->payments = 12.5;
		$this->assertSame( 12.5, $tournament->payments );
	}

	public function test_tournament_in_pure_isolation_without_wordpress_globals_or_container(): void {
		// Verify globals are completely absent
		$this->assertArrayNotHasKey( 'racketmanager', $GLOBALS );
		$this->assertArrayNotHasKey( 'wp', $GLOBALS );

		$competition = $this->createStub( Competition::class );
		$competition->id = 15;

		$raw = (object) [
			'id'             => 7,
			'name'           => 'Autumn Open',
			'competition_id' => 15,
			'season'         => '2026',
			'date_end'       => '2026-10-31',
			'date_closing'   => '2026-10-15',
			'date_start'     => '2026-10-25',
			'date_open'      => '2026-10-01',
		];

		$tournament = Tournament_Factory::from_object( $raw );
		$tournament->set_competition( $competition );

		$presenter = new Tournament_Presenter( 'd/m/Y' );
		$this->assertSame( '/tournament/autumn-open/', $presenter->get_link( $tournament ) );
		$this->assertSame( 'https://tennis.example.com/entry-form/autumn-open-tournament/', $presenter->get_entry_link( $tournament, 'https://tennis.example.com' ) );
		$dates = $presenter->present_dates( $tournament );
		$this->assertSame( '31/10/2026', $dates->date_display );
		$this->assertSame( '15/10/2026', $dates->date_closing_display );

		$resolved_comp = $tournament->get_competition_object();
		$this->assertSame( $competition, $resolved_comp );
	}

	public function test_tournament_serialization_and_unserialization_safely_ignores_stale_properties(): void {
		$dto = new Tournament_Hydration_DTO(
			id: 101,
			name: 'Winter Classic',
			competition_id: 22,
			season: '2026',
			date_end: '2026-12-20'
		);

		$tournament = Tournament_Factory::create_from_dto( $dto );

		$serialized = serialize( $tournament );
		/** @var Tournament $unserialized */
		$unserialized = unserialize( $serialized );

		$this->assertInstanceOf( Tournament::class, $unserialized );
		$this->assertSame( 101, $unserialized->id );
		$this->assertSame( 'Winter Classic', $unserialized->name );
		$this->assertSame( 22, $unserialized->competition_id );

		// Stale cached payload simulation with legacy properties
		$stale_payload = serialize( [
			'id'             => 101,
			'name'           => 'Winter Classic',
			'date_display'   => '2026-12-20',
			'option_gateway' => 'dummy',
			'site_url'       => 'http://example.com',
		] );
		$unserialized_stale = new Tournament();
		$unserialized_stale->__unserialize( unserialize( $stale_payload ) );
		$this->assertSame( 101, $unserialized_stale->id );
		$this->assertSame( 'Winter Classic', $unserialized_stale->name );
		$this->assertFalse( property_exists( $unserialized_stale, 'option_gateway' ) );
	}

	public function test_tournament_repository_wires_competition_on_hydrated_tournaments(): void {
		$competition = $this->createStub( Competition::class );
		$competition->id = 12;

		$comp_service = $this->createMock( Competition_Service::class );
		$comp_service->expects( $this->once() )
			->method( 'get_by_id' )
			->with( 12 )
			->willReturn( $competition );

		$repo = new Tournament_Repository(
			db: null,
			competition_service: static fn() => $comp_service
		);

		$dummy = new Tournament();
		$dummy->id = 55;
		$dummy->competition_id = 12;
		$dummy->name = 'Spring Open';

		$repo->wire_tournament_collaborators( $dummy );

		$this->assertSame( $competition, $dummy->get_competition() );
	}

	public function test_production_container_bootstrap_registers_tournament_repository_with_lazy_collaborators(): void {
		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$GLOBALS['wpdb'] = $wpdb;

		$app = $this->createStub( RacketManager::class );
		$app->method( 'get_options' )->willReturn( [] );
		$app->site_url    = 'https://tennis.example.com';
		$app->date_format = 'Y-m-d';

		$container = Container_Bootstrap::boot( $app );
		$this->assertTrue( $container->has( 'tournament_repository' ) );
		$this->assertTrue( $container->has( 'competition_service' ) );
		$this->assertTrue( $container->has( 'option_gateway' ) );

		$repo = $container->get( 'tournament_repository' );
		$this->assertInstanceOf( Tournament_Repository::class, $repo );
	}

	public function test_hydration_via_repository_reads_no_globals_and_wires_collaborators(): void {
		$spy = new class() {
			public array $accesses = [];
			public function __get( $name ) {
				$this->accesses[] = "get:$name";
				return null;
			}
			public function __isset( $name ) {
				$this->accesses[] = "isset:$name";
				return false;
			}
		};
		$GLOBALS['racketmanager'] = $spy;

		$wp_spy = new class() {
			public array $calls = [];
			public function set_query_var( $key, $val ): void {
				$this->calls[] = "set_query_var:$key:$val";
			}
		};
		$GLOBALS['wp'] = $wp_spy;

		$competition = $this->createStub( Competition::class );
		$competition->id = 12;

		$comp_service = $this->createMock( Competition_Service::class );
		$comp_service->expects( $this->once() )
			->method( 'get_by_id' )
			->with( 12 )
			->willReturn( $competition );

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturn( '1=1' );
		$wpdb->method( 'get_row' )->willReturn( (object) [
			'id'             => 55,
			'name'           => 'Summer Cup',
			'competition_id' => 12,
			'season'         => '2026',
			'date_end'       => '2026-08-20',
			'date_closing'   => '2026-08-01',
		] );

		$repo = new Tournament_Repository(
			db: $wpdb,
			competition_service: static fn() => $comp_service
		);

		$tournament = $repo->find_by_id( 55 );
		$this->assertInstanceOf( Tournament::class, $tournament );
		$this->assertSame( 55, $tournament->id );
		$this->assertSame( '2026-08-20', $tournament->get_end_date() );
		$this->assertSame( '2026-08-01', $tournament->get_closing_date() );
		$this->assertInstanceOf( Competition::class, $tournament->get_competition() );

		// Prove zero global reads/calls occurred during hydration
		$this->assertSame( [], $spy->accesses, 'Global $racketmanager was accessed during hydration: ' . implode( ', ', $spy->accesses ) );
		$this->assertSame( [], $wp_spy->calls, 'Global $wp was accessed during hydration: ' . implode( ', ', $wp_spy->calls ) );
	}

	public function test_cache_hit_in_serialize_mode_rewires_collaborators(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'serialize';

		$competition = $this->createStub( Competition::class );
		$competition->id = 12;

		$comp_service = $this->createMock( Competition_Service::class );
		$comp_service->expects( $this->atLeastOnce() )
			->method( 'get_by_id' )
			->with( 12 )
			->willReturn( $competition );

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturn( '1=1' );
		$wpdb->method( 'get_row' )->willReturn( (object) [
			'id'             => 77,
			'name'           => 'Winter Trophy',
			'competition_id' => 12,
			'season'         => '2026',
			'date_end'       => '2026-12-15',
		] );

		$repo = new Tournament_Repository(
			db: $wpdb,
			competition_service: static fn() => $comp_service
		);

		// First call: populates serialized cache
		$first = $repo->find_by_id( 77 );
		$this->assertInstanceOf( Tournament::class, $first );

		// Second call: cache hit deserializes object and attaches competition
		$second = $repo->find_by_id( 77 );
		$this->assertInstanceOf( Tournament::class, $second );
		$this->assertNotNull( $second->get_competition(), 'Competition was not attached on cache hit.' );

		$resolved = $second->get_competition_object();
		$this->assertSame( $competition, $resolved );
	}
}
