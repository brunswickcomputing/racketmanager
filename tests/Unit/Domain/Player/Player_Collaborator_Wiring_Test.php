<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Player_Factory;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use RuntimeException;
use wpdb;

final class Player_Collaborator_Wiring_Test extends TestCase {
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

	public function test_unwired_player_get_matches_throws_runtime_exception(): void {
		$player = new Player();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot retrieve player matches: Player_Query_Service collaborator is required.' );
		$player->get_matches( null, null, 'league' );
	}

	public function test_unwired_player_get_competitions_throws_runtime_exception(): void {
		$player = new Player();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot retrieve player competitions: Player_Query_Service collaborator is required.' );
		$player->get_competitions();
	}

	public function test_unwired_player_get_stats_throws_runtime_exception(): void {
		$player = new Player();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot retrieve player stats: Player_Stats_Service collaborator is required.' );
		$player->get_stats();
	}

	public function test_unwired_player_get_career_stats_throws_runtime_exception(): void {
		$player = new Player();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot retrieve player career stats: Player_Stats_Service collaborator is required.' );
		$player->get_career_stats();
	}

	public function test_unwired_player_get_stats_teams_throws_runtime_exception(): void {
		$player = new Player();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot retrieve player team stats: Player_Query_Service collaborator is required.' );
		$player->get_stats_teams();
	}

	public function test_unwired_player_get_stats_players_throws_runtime_exception(): void {
		$player = new Player();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot retrieve player stats: Player_Query_Service collaborator is required.' );
		$player->get_stats_players();
	}

	public function test_player_with_injected_collaborators_delegates_successfully(): void {
		$query_service = $this->createMock( Player_Query_Service::class );
		$stats_service = $this->createMock( Player_Stats_Service::class );

		$player = new Player( null, $query_service, $stats_service );
		$this->assertSame( $query_service, $player->get_player_query_service() );
		$this->assertSame( $stats_service, $player->get_player_stats_service() );

		$query_service->expects( $this->once() )
			->method( 'get_matches' )
			->with( $player, null, null, 'league', null, null )
			->willReturn( [ 'match1' ] );

		$query_service->expects( $this->once() )
			->method( 'get_competitions' )
			->with( $player, [], null )
			->willReturn( [ 'comp1' ] );

		$stats_service->expects( $this->once() )
			->method( 'get_stats' )
			->with( $player, null )
			->willReturn( [ 'stats1' ] );

		$stats_service->expects( $this->once() )
			->method( 'get_career_stats' )
			->with( $player )
			->willReturn( [ 'career1' ] );

		$query_service->expects( $this->once() )
			->method( 'get_stats_teams' )
			->with( $player, [] )
			->willReturn( [ 'team_stat' ] );

		$query_service->expects( $this->once() )
			->method( 'get_stats_players' )
			->with( $player, [] )
			->willReturn( [ 'player_stat' ] );

		$this->assertSame( [ 'match1' ], $player->get_matches( null, null, 'league' ) );
		$this->assertSame( [ 'comp1' ], $player->get_competitions() );
		$this->assertSame( [ 'stats1' ], $player->get_stats() );
		$this->assertSame( [ 'career1' ], $player->get_career_stats() );
		$this->assertSame( [ 'team_stat' ], $player->get_stats_teams() );
		$this->assertSame( [ 'player_stat' ], $player->get_stats_players() );
	}

	public function test_player_serialization_and_unserialization_strips_collaborators_and_restores_references(): void {
		$query_service = $this->createStub( Player_Query_Service::class );
		$stats_service = $this->createStub( Player_Stats_Service::class );

		$dto = new Player_Hydration_DTO(
			id: 123,
			email: 'test@example.com',
			display_name: 'Test Player',
			firstname: 'Test',
			surname: 'Player',
			gender: 'M',
			btm: '87654321',
			year_of_birth: 1985
		);

		$player = Player_Factory::create_from_dto( $dto, $query_service, $stats_service );
		$this->assertNotNull( $player->get_player_query_service() );
		$this->assertNotNull( $player->get_player_stats_service() );

		$serialized = serialize( $player );
		/** @var Player $unserialized */
		$unserialized = unserialize( $serialized );

		$this->assertInstanceOf( Player::class, $unserialized );
		$this->assertSame( 123, $unserialized->ID );
		$this->assertSame( 123, $unserialized->id );
		$this->assertSame( 'test@example.com', $unserialized->user_email );
		$this->assertSame( 'test@example.com', $unserialized->email );
		$this->assertSame( 'Test Player', $unserialized->display_name );
		$this->assertSame( 'Test Player', $unserialized->fullname );

		// Collaborators stripped to avoid serializing services/closures
		$this->assertNull( $unserialized->get_player_query_service() );
		$this->assertNull( $unserialized->get_player_stats_service() );

		// Verifying reference aliases are maintained
		$unserialized->ID = 456;
		$this->assertSame( 456, $unserialized->id );

		$unserialized->user_email = 'updated@example.com';
		$this->assertSame( 'updated@example.com', $unserialized->email );

		$unserialized->display_name = 'Updated Player';
		$this->assertSame( 'Updated Player', $unserialized->fullname );
	}

	public function test_player_repository_wires_collaborators_on_find_cache_hit_clone_and_serialize(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'clone';

		$query_service = $this->createStub( Player_Query_Service::class );
		$stats_service = $this->createStub( Player_Stats_Service::class );

		$repo = new Player_Repository(
			db: null,
			player_query_service: static fn() => $query_service,
			player_stats_service: static fn() => $stats_service
		);

		$cached_player = new Player();
		$cached_player->ID = 50;
		$cached_player->id = 50;
		$cached_player->user_login = 'player50';
		$cached_player->display_name = 'Player Fifty';
		$cached_player->btm = '505050';

		wp_cache_set( 50, $cached_player, 'players' );

		$player = $repo->find( 50 );
		$this->assertInstanceOf( Player::class, $player );
		$this->assertSame( $query_service, $player->get_player_query_service() );
		$this->assertSame( $stats_service, $player->get_player_stats_service() );

		// Test in serialize cache mode
		$GLOBALS['wp_stubs_cache_mode'] = 'serialize';
		wp_cache_set( 50, $cached_player, 'players' );

		$player_serialized = $repo->find( 50 );
		$this->assertInstanceOf( Player::class, $player_serialized );
		$this->assertSame( $query_service, $player_serialized->get_player_query_service() );
		$this->assertSame( $stats_service, $player_serialized->get_player_stats_service() );
	}

	public function test_production_container_bootstrap_registers_player_repository_with_lazy_collaborators(): void {
		$wpdb = $this->createStub( wpdb::class );
		$wpdb->users = 'wp_users';
		$wpdb->usermeta = 'wp_usermeta';
		$GLOBALS['wpdb'] = $wpdb;

		$app = $this->createStub( RacketManager::class );
		$app->method( 'get_options' )->willReturn( [] );
		$app->site_url  = 'https://example.com';
		$app->site_name = 'Test Site';

		$container = Container_Bootstrap::boot( $app );
		$this->assertTrue( $container->has( 'player_repository' ) );
		$this->assertTrue( $container->has( 'player_query_service' ) );
		$this->assertTrue( $container->has( 'player_stats_service' ) );

		$repo = $container->get( 'player_repository' );
		$this->assertInstanceOf( Player_Repository::class, $repo );

		$dummy_player = new Player();
		$dummy_player->ID = 1;
		$repo->wire_player_collaborators( $dummy_player );

		$this->assertInstanceOf( Player_Query_Service::class, $dummy_player->get_player_query_service() );
		$this->assertInstanceOf( Player_Stats_Service::class, $dummy_player->get_player_stats_service() );
	}

	public function test_production_get_player_object_path_works_with_and_without_container(): void {
		$script = dirname( __DIR__, 4 ) . '/tests/Fixtures/production-get-player-object-path.php';
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ), $output, $exit_code );

		$this->assertSame( 0, $exit_code, implode( "\n", $output ) );
		$this->assertSame( 'ok', implode( "\n", $output ) );
	}
}
