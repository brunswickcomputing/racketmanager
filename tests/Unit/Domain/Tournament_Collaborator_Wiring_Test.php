<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Tournament\Tournament_Hydration_DTO;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Tournament_Factory;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
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

		$tournament = Tournament_Factory::create_from_dto( $dto, null, null, 'https://tennis.example.com', 'Y-m-d' );
		$this->assertSame( 42, $tournament->id );
		$this->assertSame( 'Summer Championship', $tournament->name );
		$this->assertSame( '/tournament/summer-championship/', $tournament->link );
		$this->assertSame( 'https://tennis.example.com/entry-form/summer-championship-tournament/', $tournament->entry_link );
		$this->assertSame( '2026-08-15', $tournament->date_display );
		$this->assertSame( '2026-08-01', $tournament->date_closing_display );
	}

	public function test_tournament_in_pure_isolation_without_wordpress_globals_or_container(): void {
		// Verify globals are completely absent
		$this->assertArrayNotHasKey( 'racketmanager', $GLOBALS );
		$this->assertArrayNotHasKey( 'wp', $GLOBALS );

		$competition = $this->createStub( Competition::class );
		$competition->id = 15;

		$comp_service = $this->createMock( Competition_Service::class );
		$comp_service->expects( $this->once() )
			->method( 'get_by_id' )
			->with( 15 )
			->willReturn( $competition );

		$option_gw = $this->createMock( Option_Gateway_Interface::class );
		$option_gw->expects( $this->atLeastOnce() )
			->method( 'get_option' )
			->with( 'date_format', 'Y-m-d' )
			->willReturn( 'd/m/Y' );

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

		$tournament = Tournament_Factory::from_object(
			$raw,
			competition_service: $comp_service,
			option_gateway: $option_gw,
			site_url: 'https://tennis.example.com',
			date_format: null
		);

		$this->assertSame( '/tournament/autumn-open/', $tournament->link );
		$this->assertSame( 'https://tennis.example.com/entry-form/autumn-open-tournament/', $tournament->entry_link );
		$this->assertSame( '31/10/2026', $tournament->date_display );
		$this->assertSame( '15/10/2026', $tournament->date_closing_display );

		$resolved_comp = $tournament->get_competition_object();
		$this->assertSame( $competition, $resolved_comp );
	}

	public function test_tournament_serialization_and_unserialization_strips_collaborators(): void {
		$comp_service = $this->createStub( Competition_Service::class );
		$option_gw    = $this->createStub( Option_Gateway_Interface::class );

		$dto = new Tournament_Hydration_DTO(
			id: 101,
			name: 'Winter Classic',
			competition_id: 22,
			season: '2026',
			date_end: '2026-12-20'
		);

		$tournament = Tournament_Factory::create_from_dto( $dto, $comp_service, $option_gw, 'https://tennis.example.com', 'Y-m-d' );
		$this->assertSame( $comp_service, $tournament->get_competition_service() );
		$this->assertSame( $option_gw, $tournament->get_option_gateway() );

		$serialized = serialize( $tournament );
		/** @var Tournament $unserialized */
		$unserialized = unserialize( $serialized );

		$this->assertInstanceOf( Tournament::class, $unserialized );
		$this->assertSame( 101, $unserialized->id );
		$this->assertSame( 'Winter Classic', $unserialized->name );
		$this->assertSame( 22, $unserialized->competition_id );

		// Collaborators stripped to prevent closure/service serialization issues
		$this->assertNull( $unserialized->get_competition_service() );
		$this->assertNull( $unserialized->get_option_gateway() );
	}

	public function test_tournament_repository_wires_collaborators_on_hydrated_tournaments(): void {
		$comp_service = $this->createStub( Competition_Service::class );
		$option_gw    = $this->createStub( Option_Gateway_Interface::class );

		$repo = new Tournament_Repository(
			db: null,
			competition_service: static fn() => $comp_service,
			option_gateway: static fn() => $option_gw,
			site_url: static fn() => 'https://tennis.example.com',
			date_format: static fn() => 'Y-m-d'
		);

		$dummy = new Tournament();
		$dummy->id = 55;
		$dummy->name = 'Spring Open';

		$repo->wire_tournament_collaborators( $dummy );

		$this->assertSame( $comp_service, $dummy->get_competition_service() );
		$this->assertSame( $option_gw, $dummy->get_option_gateway() );
		$this->assertSame( 'https://tennis.example.com', $dummy->get_site_url() );
		$this->assertSame( 'Y-m-d', $dummy->get_date_format() );
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

		$dummy = new Tournament();
		$dummy->id = 99;
		$dummy->name = 'Junior Finals';
		$repo->wire_tournament_collaborators( $dummy );

		$this->assertInstanceOf( Competition_Service::class, $dummy->get_competition_service() );
		$this->assertInstanceOf( Option_Gateway_Interface::class, $dummy->get_option_gateway() );
		$this->assertSame( 'https://tennis.example.com', $dummy->get_site_url() );
		$this->assertSame( 'Y-m-d', $dummy->get_date_format() );
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

		$comp_service = $this->createStub( Competition_Service::class );
		$option_gw    = $this->createStub( Option_Gateway_Interface::class );
		$option_gw->method( 'get_option' )->willReturn( 'd/m/Y' );

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
			competition_service: static fn() => $comp_service,
			option_gateway: static fn() => $option_gw,
			site_url: static fn() => 'https://tennis.example.com',
			date_format: static fn() => 'd/m/Y'
		);

		$tournament = $repo->find_by_id( 55 );
		$this->assertInstanceOf( Tournament::class, $tournament );
		$this->assertSame( 55, $tournament->id );
		$this->assertSame( '20/08/2026', $tournament->date_display );
		$this->assertSame( '01/08/2026', $tournament->date_closing_display );
		$this->assertSame( 'https://tennis.example.com/entry-form/summer-cup-tournament/', $tournament->entry_link );
		$this->assertSame( $comp_service, $tournament->get_competition_service() );

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
		$comp_service->expects( $this->once() )
			->method( 'get_by_id' )
			->with( 12 )
			->willReturn( $competition );

		$option_gw = $this->createStub( Option_Gateway_Interface::class );

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
			competition_service: static fn() => $comp_service,
			option_gateway: static fn() => $option_gw,
			site_url: static fn() => 'https://tennis.example.com',
			date_format: static fn() => 'Y-m-d'
		);

		// First call: populates serialized cache
		$first = $repo->find_by_id( 77 );
		$this->assertInstanceOf( Tournament::class, $first );

		// Second call: cache hit deserializes object (collaborators stripped) and re-wires them
		$second = $repo->find_by_id( 77 );
		$this->assertInstanceOf( Tournament::class, $second );
		$this->assertNotNull( $second->get_competition_service(), 'Collaborator competition_service was not rewired on cache hit.' );

		// Verify get_competition_object resolves via rewired collaborator without global container
		$resolved = $second->get_competition_object();
		$this->assertSame( $competition, $resolved );
	}
}
