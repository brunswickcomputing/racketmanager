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
wp_cache_set( 10, new \Racketmanager\Stub_Event( 10 ), 'events' );

$league_row = static fn(): object => (object) array(
	'id'       => 777,
	'title'    => 'Object League',
	'event_id' => 10,
	'settings' => array(),
	'seasons'  => array(),
);

unset( $GLOBALS['racketmanager'] );
$unwired_app_league = \Racketmanager\get_league( $league_row() );
if ( ! $unwired_app_league instanceof \Racketmanager\Domain\Competition\League
	|| ! $unwired_app_league->get_league_repository() instanceof \Racketmanager\Repositories\League_Repository
) {
	fwrite( STDERR, 'The production get_league() object fallback did not wire its fallback repository.' );
	exit( 1 );
}

$repository = new \Racketmanager\Repositories\League_Repository();
$container  = new \Racketmanager\Services\Container\Simple_Container();
$container->set( 'league_repository', static fn() => $repository );
$GLOBALS['racketmanager'] = (object) array( 'container' => $container );
$wired_app_league = \Racketmanager\get_league( $league_row() );
if ( ! $wired_app_league instanceof \Racketmanager\Domain\Competition\League
	|| $repository !== $wired_app_league->get_league_repository()
) {
	fwrite( STDERR, 'The production get_league() object path did not use the container repository.' );
	exit( 1 );
}

echo 'ok';
