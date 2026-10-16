<?php
declare( strict_types=1 );

$plugin_root = dirname( __DIR__, 2 );
require $plugin_root . '/vendor/autoload.php';
if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ): void {}
}
require $plugin_root . '/functions.php';
require $plugin_root . '/tests/wp-stubs.php';

$GLOBALS['wp_stubs_cache_enabled'] = true;
$GLOBALS['wp_stubs_cache_mode']    = 'clone';

$player_row = static fn(): object => (object) array(
	'ID'           => 888,
	'user_login'   => 'objplayer',
	'display_name' => 'Object Player',
	'user_email'   => 'obj@example.com',
);

// 1. Without container: fallback repository is used and collaborators are wired.
unset( $GLOBALS['racketmanager'] );
$unwired_app_player = \Racketmanager\get_player( $player_row() );
if ( ! $unwired_app_player instanceof \Racketmanager\Domain\Player ) {
	fwrite( STDERR, "The production get_player() did not return a Player instance.\n" );
	exit( 1 );
}

// 2. With container: container player_repository is used.
$repository = new \Racketmanager\Repositories\Player_Repository();
$container  = new \Racketmanager\Services\Container\Simple_Container();
$container->set( 'player_repository', static fn() => $repository );
$GLOBALS['racketmanager'] = (object) array( 'container' => $container );

$wired_app_player = \Racketmanager\get_player( $player_row() );
if ( ! $wired_app_player instanceof \Racketmanager\Domain\Player ) {
	fwrite( STDERR, "The production get_player() with container did not return a Player instance.\n" );
	exit( 1 );
}

// 3. Passing an existing Player instance re-wires it.
$existing_player = new \Racketmanager\Domain\Player();
$existing_player->ID = 999;
$existing_player->user_login = 'existing';
$rewired_player = \Racketmanager\get_player( $existing_player );
if ( $rewired_player !== $existing_player ) {
	fwrite( STDERR, "The production get_player() with existing Player instance did not return the same instance.\n" );
	exit( 1 );
}

echo 'ok';
