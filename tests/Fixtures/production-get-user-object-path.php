<?php
declare( strict_types=1 );

$plugin_root = dirname( __DIR__, 2 );
require $plugin_root . '/vendor/autoload.php';
if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ): void {}
}
require $plugin_root . '/functions.php';
require $plugin_root . '/tests/wp-stubs.php';

$domain_user = new \Racketmanager\Repositories\User_Repository();
$plain_user  = $domain_user->find( 42 );
if ( ! $plain_user instanceof \Racketmanager\Domain\Player\User ) {
	fwrite( STDERR, "User_Repository::find() did not return a domain User.\n" );
	exit( 1 );
}
if ( $plain_user instanceof \Racketmanager\Infrastructure\Legacy\Legacy_User ) {
	fwrite( STDERR, "User_Repository::find() should not hydrate Legacy_User.\n" );
	exit( 1 );
}
if ( method_exists( $plain_user, 'get_messages' ) ) {
	fwrite( STDERR, "Domain User should not expose get_messages().\n" );
	exit( 1 );
}

$message_repository = new class implements \Racketmanager\Repositories\Interfaces\Message_Repository_Interface {
	public array $calls = [];
	public function find( int|string $message_id ): ?\Racketmanager\Domain\Communication\Message { return null; }
	public function find_by_id( int|string|null $id ): ?\Racketmanager\Domain\Communication\Message { return null; }
	public function find_by_user( int $user_id, array $args = [] ): array|int {
		$this->calls[] = [ $user_id, $args ];
		if ( ! empty( $args['count'] ) ) {
			return 7;
		}
		return [
			(object) [ 'id' => 1, 'subject' => 'Hello' ],
		];
	}
	public function delete_by_user_and_type( int $user_id, string $type ): int|false { return 0; }
	public function save( object $entity ): bool|int { return false; }
	public function delete( int $id ): bool { return false; }
};

$container = new \Racketmanager\Services\Container\Simple_Container();
$container->set( 'user_repository', static fn() => new \Racketmanager\Repositories\User_Repository() );
$container->set( 'message_repository', static fn() => $message_repository );
$container->set(
	'legacy_user_factory',
	static fn( \Racketmanager\Services\Container\Simple_Container $c ) => new \Racketmanager\Infrastructure\Legacy\Legacy_User_Factory( $c->get( 'message_repository' ) )
);
$GLOBALS['racketmanager'] = (object) [ 'container' => $container ];

$legacy_user = \Racketmanager\get_user( 42 );
if ( ! $legacy_user instanceof \Racketmanager\Infrastructure\Legacy\Legacy_User ) {
	fwrite( STDERR, "get_user() did not return Legacy_User.\n" );
	exit( 1 );
}
if ( ! $legacy_user instanceof \Racketmanager\Domain\Player\User ) {
	fwrite( STDERR, "Legacy_User is not a domain User.\n" );
	exit( 1 );
}
if ( ! $legacy_user instanceof \Racketmanager\Domain\User ) {
	fwrite( STDERR, "Legacy_User is not recognized as the Domain\\User alias.\n" );
	exit( 1 );
}
if ( 42 !== $legacy_user->id ) {
	fwrite( STDERR, "get_user() did not preserve the hydrated user id.\n" );
	exit( 1 );
}

if ( \Racketmanager\get_user( $legacy_user ) !== $legacy_user ) {
	fwrite( STDERR, "get_user(Legacy_User) did not return the same object.\n" );
	exit( 1 );
}
$rewrapped = \Racketmanager\get_user( $plain_user );
if ( ! $rewrapped instanceof \Racketmanager\Infrastructure\Legacy\Legacy_User || $rewrapped === $plain_user ) {
	fwrite( STDERR, "get_user(plain User) did not wrap into a distinct Legacy_User.\n" );
	exit( 1 );
}

$count = $legacy_user->get_messages( [ 'count' => true, 'status' => 'unread' ] );
if ( 7 !== $count ) {
	fwrite( STDERR, "get_messages() count path did not return the repository result.\n" );
	exit( 1 );
}

$list = $legacy_user->get_messages();
if ( ! is_array( $list ) || 1 !== count( $list ) ) {
	fwrite( STDERR, "get_messages() list path did not return the repository result.\n" );
	exit( 1 );
}

if ( [
	[ 42, [ 'count' => true, 'status' => 'unread' ] ],
	[ 42, [] ],
] !== $message_repository->calls ) {
	fwrite( STDERR, "get_messages() did not call find_by_user with the expected arguments.\n" );
	exit( 1 );
}

echo 'ok';
