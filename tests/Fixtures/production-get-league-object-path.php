<?php
declare( strict_types=1 );

use Racketmanager\Domain\Competition\Event;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;

$plugin_root = dirname( __DIR__, 2 );
require $plugin_root . '/vendor/autoload.php';
if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ): void {}
}
// Load the production helpers before the test stubs so the real get_league() is used.
require $plugin_root . '/functions.php';
require $plugin_root . '/tests/wp-stubs.php';

$fail = static function ( string $message ): never {
	fwrite( STDERR, $message );
	exit( 1 );
};

$repository = new class() extends League_Repository {
	public int $count_calls = 0;
	public int $teams       = 0;

	public function query_league_teams( int $league_id, array $args, bool $count = false ): array|int {
		++$this->count_calls;
		return $count ? $this->teams : array();
	}
};

$service   = new League_Query_Service(
	$repository,
	static fn() => null,
	static fn() => null,
	static fn() => null,
	static fn() => null,
	static fn() => null,
	static fn() => null
);
$container = new class( $service ) {
	public function __construct( private League_Query_Service $service ) {
	}

	public function has( string $id ): bool {
		return 'league_query_service' === $id;
	}

	public function get( string $id ): mixed {
		return $this->service;
	}
};
$GLOBALS['racketmanager'] = (object) array( 'container' => $container );

$seasons    = array( '2026' => array( 'name' => '2026', 'num_match_days' => 3 ) );
$league_row = static fn( string $mode ): object => (object) array(
	'id'       => 42,
	'title'    => 'Object League',
	'event_id' => 93,
	'settings' => array( 'num_advance' => 2 ),
	'seasons'  => $seasons,
	'event'    => new Event(
		(object) array( 'id' => 93, 'name' => 'Event', 'seasons' => $seasons ),
		Competition_Test_Factory::create( settings: array( 'mode' => $mode ) )
	),
);

$repository->teams = 8;
$league            = \Racketmanager\get_league( $league_row( 'championship' ) );
if ( ! $league instanceof \Racketmanager\Domain\Competition\League ) {
	$fail( 'get_league(object) did not return a League for championship mode.' );
}
if ( 'championship' !== $league->mode ) {
	$fail( 'Expected championship mode, got ' . $league->mode );
}
if ( 8 !== (int) $league->num_teams_total ) {
	$fail( 'Championship object path did not refresh num_teams_total: ' . var_export( $league->num_teams_total, true ) );
}
if ( null === $league->championship ) {
	$fail( 'Championship object path did not build a championship.' );
}

$repository->teams       = 5;
$repository->count_calls = 0;
$league                  = \Racketmanager\get_league( $league_row( 'league' ) );
if ( ! $league instanceof \Racketmanager\Domain\Competition\League ) {
	$fail( 'get_league(object) did not return a League for non-championship mode.' );
}
if ( 5 !== (int) $league->num_teams_total ) {
	$fail( 'Non-championship object path did not refresh num_teams_total: ' . var_export( $league->num_teams_total, true ) );
}
if ( null !== ( $league->championship ?? null ) ) {
	$fail( 'Non-championship league unexpectedly has a championship.' );
}
if ( 1 !== $repository->count_calls ) {
	$fail( 'Expected exactly one team-count query, got ' . $repository->count_calls );
}

echo 'ok';
