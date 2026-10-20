<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Legacy;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\User;
use Racketmanager\Infrastructure\Legacy\Legacy_User;
use Racketmanager\Infrastructure\Legacy\Legacy_User_Factory;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Legacy_User_Test extends TestCase {
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

	public function test_get_messages_delegates_to_injected_message_repository_with_count(): void {
		$repo = $this->createMock( Message_Repository_Interface::class );
		$repo->expects( $this->once() )
			->method( 'find_by_user' )
			->with( 15, [ 'count' => true, 'status' => 'unread' ] )
			->willReturn( 3 );

		$user     = new Legacy_User( null, $repo );
		$user->id = 15;

		$result = $user->get_messages( [ 'count' => true, 'status' => 'unread' ] );

		$this->assertSame( 3, $result );
	}

	public function test_get_messages_delegates_to_injected_message_repository_for_list(): void {
		$dummy_messages = [
			(object) [ 'id' => 1, 'subject' => 'Hello' ],
			(object) [ 'id' => 2, 'subject' => 'World' ],
		];

		$repo = $this->createMock( Message_Repository_Interface::class );
		$repo->expects( $this->once() )
			->method( 'find_by_user' )
			->with( 15, [] )
			->willReturn( $dummy_messages );

		$user     = new Legacy_User( null, $repo );
		$user->id = 15;

		$result = $user->get_messages( [] );

		$this->assertSame( $dummy_messages, $result );
	}

	public function test_factory_wraps_domain_user_and_container_wires_repository(): void {
		$wpdb            = $this->createStub( wpdb::class );
		$GLOBALS['wpdb'] = $wpdb;

		$app = $this->createStub( RacketManager::class );
		$app->method( 'get_options' )->willReturn( [] );

		$container = Container_Bootstrap::boot( $app );

		$repo = $this->createMock( Message_Repository_Interface::class );
		$repo->expects( $this->once() )
			->method( 'find_by_user' )
			->with( 1, [ 'count' => true ] )
			->willReturn( 5 );

		$container->set( 'message_repository', fn() => $repo );

		$domain_user     = new User();
		$domain_user->id = 1;
		$domain_user->ID = 1;

		$this->assertFalse( method_exists( $domain_user, 'get_messages' ) );

		/** @var Legacy_User_Factory $factory */
		$factory = $container->get( 'legacy_user_factory' );
		$legacy  = $factory->from_user( $domain_user );

		$this->assertInstanceOf( Legacy_User::class, $legacy );
		$this->assertInstanceOf( User::class, $legacy );
		$this->assertInstanceOf( \Racketmanager\Domain\User::class, $legacy );
		$this->assertSame( 1, $legacy->id );
		$this->assertSame( 5, $legacy->get_messages( [ 'count' => true ] ) );
	}

	public function test_user_repository_still_hydrates_domain_user(): void {
		$wpdb            = $this->createStub( wpdb::class );
		$GLOBALS['wpdb'] = $wpdb;

		$app = $this->createStub( RacketManager::class );
		$app->method( 'get_options' )->willReturn( [] );

		$container  = Container_Bootstrap::boot( $app );
		$repository = $container->get( 'user_repository' );
		$user       = $repository->find( 101 );

		$this->assertInstanceOf( User::class, $user );
		$this->assertNotInstanceOf( Legacy_User::class, $user );
		$this->assertFalse( method_exists( $user, 'get_messages' ) );
	}

	public function test_production_get_user_wires_legacy_user_via_container(): void {
		$script = dirname( __DIR__, 4 ) . '/tests/Fixtures/production-get-user-object-path.php';
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ), $output, $exit_code );

		$this->assertSame( 0, $exit_code, implode( "\n", $output ) );
		$this->assertSame( 'ok', implode( "\n", $output ) );
	}
}
