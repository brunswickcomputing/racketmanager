<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
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

	public function test_player_does_not_expose_match_query_proxy(): void {
		$player = new Player();
		$this->assertFalse( method_exists( $player, 'get_matches' ) );
	}

	public function test_player_does_not_expose_competition_query_proxy(): void {
		$player = new Player();
		$this->assertFalse( method_exists( $player, 'get_competitions' ) );
	}

	public function test_player_does_not_expose_stats_proxy(): void {
		$player = new Player();
		$this->assertFalse( method_exists( $player, 'get_stats' ) );
	}

	public function test_player_does_not_expose_career_stats_proxy(): void {
		$player = new Player();
		$this->assertFalse( method_exists( $player, 'get_career_stats' ) );
	}

	public function test_player_does_not_expose_team_stats_query_proxy(): void {
		$player = new Player();
		$this->assertFalse( method_exists( $player, 'get_stats_teams' ) );
	}

	public function test_player_does_not_expose_player_stats_query_proxy(): void {
		$player = new Player();
		$this->assertFalse( method_exists( $player, 'get_stats_players' ) );
	}

	public function test_entity_and_factory_have_no_service_constructor_dependencies(): void {
		$this->assertSame( 1, ( new \ReflectionMethod( Player::class, '__construct' ) )->getNumberOfParameters() );
		$this->assertSame( 1, ( new \ReflectionMethod( Player_Factory::class, 'create_from_dto' ) )->getNumberOfParameters() );
	}

	public function test_player_serialization_preserves_hooked_aliases_and_ignores_legacy_services(): void {
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

		$player = Player_Factory::create_from_dto( $dto );

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

		// Public aliases remain synchronized in both directions.
		$unserialized->ID = 456;
		$this->assertSame( 456, $unserialized->id );
		$unserialized->id = 789;
		$this->assertSame( 789, $unserialized->ID );

		$unserialized->user_email = 'updated@example.com';
		$this->assertSame( 'updated@example.com', $unserialized->email );

		$unserialized->display_name = 'Updated Player';
		$this->assertSame( 'Updated Player', $unserialized->fullname );
		$unserialized->__unserialize( [ 'player_query_service' => new \stdClass() ] );
		$this->assertFalse( property_exists( $unserialized, 'player_query_service' ) );
	}

	public function test_player_repository_has_no_collaborator_wiring(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache_mode']    = 'clone';
		$this->assertSame( 1, ( new \ReflectionMethod( Player_Repository::class, '__construct' ) )->getNumberOfParameters() );
		$repo = new Player_Repository();

		$cached_player = new Player();
		$cached_player->ID = 50;
		$cached_player->id = 50;
		$cached_player->user_login = 'player50';
		$cached_player->display_name = 'Player Fifty';
		$cached_player->btm = '505050';

		wp_cache_set( 50, $cached_player, 'players' );

		$player = $repo->find( 50 );
		$this->assertInstanceOf( Player::class, $player );
		$this->assertFalse( property_exists( $player, 'player_query_service' ) );
		$this->assertFalse( property_exists( $player, 'player_stats_service' ) );

		// Test in serialize cache mode
		$GLOBALS['wp_stubs_cache_mode'] = 'serialize';
		wp_cache_set( 50, $cached_player, 'players' );

		$player_serialized = $repo->find( 50 );
		$this->assertInstanceOf( Player::class, $player_serialized );
		$this->assertFalse( property_exists( $player_serialized, 'player_query_service' ) );
		$this->assertFalse( property_exists( $player_serialized, 'player_stats_service' ) );
	}

	public function test_production_container_registers_player_services_separately_from_repository(): void {
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

		$this->assertInstanceOf( Player_Query_Service::class, $container->get( 'player_query_service' ) );
		$this->assertInstanceOf( Player_Stats_Service::class, $container->get( 'player_stats_service' ) );
	}

	public function test_production_get_player_object_path_works_with_and_without_container(): void {
		$script = dirname( __DIR__, 4 ) . '/tests/Fixtures/production-get-player-object-path.php';
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ), $output, $exit_code );

		$this->assertSame( 0, $exit_code, implode( "\n", $output ) );
		$this->assertSame( 'ok', implode( "\n", $output ) );
	}
}
