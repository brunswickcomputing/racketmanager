<?php
declare( strict_types=1 );

$plugin_root = dirname( __DIR__, 2 );
require $plugin_root . '/vendor/autoload.php';
if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ): void {}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( ...$args ): void {}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) { echo $text; }
}

require $plugin_root . '/functions.php';
require $plugin_root . '/tests/wp-stubs.php';

// Setup mock wpdb
global $wpdb;
$wpdb = new \wpdb( 'test', 'test', 'test', 'localhost' );
$wpdb->prefix = 'wp_';
$wpdb->racketmanager_teams = 'wp_racketmanager_teams';
$wpdb->racketmanager_league_teams = 'wp_racketmanager_league_teams';
$wpdb->racketmanager_events = 'wp_racketmanager_events';
$wpdb->racketmanager_tournament_entries = 'wp_racketmanager_tournament_entries';
$wpdb->racketmanager_clubs = 'wp_racketmanager_clubs';
$wpdb->racketmanager_leagues = 'wp_racketmanager_leagues';

$wpdb->get_row_callback = function( $query ) {
	if ( ! str_contains( $query, 'wp_racketmanager_league_teams' ) ) {
		return null;
	}

	$is_legacy_join = str_contains( $query, 'INNER JOIN' )
		&& str_contains( $query, 'A.`id` AS `table_id`' );

	if ( $is_legacy_join ) {
		return (object) [
			'id'             => 10,
			'table_id'       => 20,
			'title'          => 'League Team A',
			'club_id'        => 1,
			'stadium'        => 'Court 1',
			'home'           => 1,
			'group'          => null,
			'roster'         => null,
			'profile'        => 0,
			'points_plus'    => 5.0,
			'points_minus'   => 0.0,
			'points_2_plus'  => 0,
			'points_2_minus' => 0,
			'add_points'     => 0.0,
			'done_matches'   => 1,
			'won_matches'    => 1,
			'draw_matches'   => 0,
			'lost_matches'   => 0,
			'diff'           => 0,
			'league_id'      => 5,
			'season'         => '2026',
			'rank'           => 1,
			'status'         => '',
			'custom'         => '',
			'team_type'      => 'club',
			'rating'         => null,
		];
	}

	// Raw league_teams row (SELECT *) has no joined title/table_id fields.
	return (object) [
		'id'             => 20,
		'team_id'        => 10,
		'league_id'      => 5,
		'season'         => '2026',
		'rank'           => 1,
		'status'         => '',
		'custom'         => '',
		'points_plus'    => 5.0,
		'points_minus'   => 0.0,
		'points_2_plus'  => 0,
		'points_2_minus' => 0,
		'add_points'     => 0.0,
		'done_matches'   => 1,
		'won_matches'    => 1,
		'draw_matches'   => 0,
		'lost_matches'   => 0,
		'diff'           => 0,
		'rating'         => null,
		'group'          => null,
	];
};

// 1. Setup container with repositories and player_service required by Team
$team_repo = new class {
	public function find_by_id( $id_or_title ) {
		$t = new \Racketmanager\Domain\Team();
		if ( is_numeric( $id_or_title ) ) {
			$t->set_id( (int) $id_or_title );
			$t->set_name( 'Team ' . $id_or_title );
		} else {
			$t->set_id( 99 );
			$t->set_name( (string) $id_or_title );
		}
		return $t;
	}
};

$entry_repo = new class {
	public function find_by_id( $id ) {
		$e = new \Racketmanager\Domain\Tournament_Entry();
		$e->id = (int) $id;
		return $e;
	}
	public function find_by_tournament_and_player( int $t_id, int $p_id ) {
		$e = new \Racketmanager\Domain\Tournament_Entry();
		$e->id = 777;
		$e->tournament_id = $t_id;
		$e->player_id = $p_id;
		return $e;
	}
};

$event_repo = new class {
	public function find_by_id( $id ) {
		$ev = new class extends \Racketmanager\Domain\Competition\Event {
			public function __construct() {}
		};
		$ev->id = is_numeric( $id ) ? (int) $id : 88;
		$ev->name = 'Event ' . $id;
		return $ev;
	}
	public function find_by_name( string $name ) {
		$ev = new class extends \Racketmanager\Domain\Competition\Event {
			public function __construct() {}
		};
		$ev->id = 89;
		$ev->name = $name;
		return $ev;
	}
};

$container = new \Racketmanager\Services\Container\Simple_Container();
$container->set( 'player_service', static fn() => new class extends \Racketmanager\Services\Player_Service {
	public function __construct() {}
	public function save_opt_in( int $id, string $opt_in ): bool { return true; }
} );
$container->set( 'team_repository', static fn() => $team_repo );
$container->set( 'tournament_entry_repository', static fn() => $entry_repo );
$container->set( 'event_repository', static fn() => $event_repo );

$GLOBALS['racketmanager'] = (object) array( 'container' => $container );

// Test get_team numeric
$team_num = \Racketmanager\get_team( 123 );
if ( ! $team_num instanceof \Racketmanager\Domain\Team || 123 !== $team_num->id ) {
	fwrite( STDERR, "get_team(123) failed.\n" );
	exit( 1 );
}

// Test get_team string name (must call find_by_id, not fail on missing find_by_name)
$team_str = \Racketmanager\get_team( 'Westcliff 1' );
if ( ! $team_str instanceof \Racketmanager\Domain\Team || 'Westcliff 1' !== $team_str->title ) {
	fwrite( STDERR, "get_team('Westcliff 1') failed.\n" );
	exit( 1 );
}

// Test get_team with object pass-through
$orig_team = new \Racketmanager\Domain\Team();
$orig_team->set_id( 55 );
if ( \Racketmanager\get_team( $orig_team ) !== $orig_team ) {
	fwrite( STDERR, "get_team(object) failed to return same instance.\n" );
	exit( 1 );
}

// 2. Test get_league_team shape and mutations via actual helper call
$league_team_obj = \Racketmanager\get_league_team( 20 );
if ( ! $league_team_obj instanceof \Racketmanager\Domain\Competition\League_Team ) {
	fwrite( STDERR, "get_league_team(20) did not return League_Team instance.\n" );
	exit( 1 );
}
if ( 10 !== $league_team_obj->id || 20 !== $league_team_obj->table_id || 'League Team A' !== $league_team_obj->title ) {
	fwrite( STDERR, "get_league_team shape check failed (id={$league_team_obj->id}, table_id={$league_team_obj->table_id}, title={$league_team_obj->title}).\n" );
	exit( 1 );
}

// Check amend_points method on League_Team executes update and sets add_points
$league_team_obj->amend_points( 3.0 );
if ( 3.0 != $league_team_obj->add_points ) {
	fwrite( STDERR, "League_Team amend_points did not set add_points properly.\n" );
	exit( 1 );
}
if ( ! str_contains( $wpdb->last_query, 'wp_racketmanager_league_teams' ) || ! str_contains( $wpdb->last_query, '20' ) ) {
	fwrite( STDERR, "League_Team amend_points did not run expected query: {$wpdb->last_query}\n" );
	exit( 1 );
}

// Test get_league_team with object pass-through
if ( \Racketmanager\get_league_team( $league_team_obj ) !== $league_team_obj ) {
	fwrite( STDERR, "get_league_team(object) failed to return same instance.\n" );
	exit( 1 );
}

// 3. Test get_tournament_entry with ID and with 'key' ('12_34')
$entry_by_id = \Racketmanager\get_tournament_entry( 101, 'id' );
if ( ! $entry_by_id instanceof \Racketmanager\Domain\Tournament_Entry || 101 !== $entry_by_id->id ) {
	fwrite( STDERR, "get_tournament_entry by ID failed.\n" );
	exit( 1 );
}

$entry_by_key = \Racketmanager\get_tournament_entry( '12_34', 'key' );
if ( ! $entry_by_key instanceof \Racketmanager\Domain\Tournament_Entry || 777 !== $entry_by_key->id || 12 !== $entry_by_key->tournament_id || 34 !== $entry_by_key->player_id ) {
	fwrite( STDERR, "get_tournament_entry by key ('12_34') failed.\n" );
	exit( 1 );
}

// 4. Test get_event with ID, with 'name', and nonnumeric default
$ev_id = \Racketmanager\get_event( 42 );
if ( ! $ev_id instanceof \Racketmanager\Domain\Competition\Event || 42 !== $ev_id->id ) {
	fwrite( STDERR, "get_event(42) failed.\n" );
	exit( 1 );
}

$ev_name = \Racketmanager\get_event( 'Men Singles', 'name' );
if ( ! $ev_name instanceof \Racketmanager\Domain\Competition\Event || 'Men Singles' !== $ev_name->name ) {
	fwrite( STDERR, "get_event('Men Singles', 'name') failed.\n" );
	exit( 1 );
}

$ev_nonnum = \Racketmanager\get_event( 'Men Singles' );
if ( ! $ev_nonnum instanceof \Racketmanager\Domain\Competition\Event || 'Event Men Singles' !== $ev_nonnum->name ) {
	fwrite( STDERR, "get_event('Men Singles') without int cast failed.\n" );
	exit( 1 );
}

echo 'ok';
