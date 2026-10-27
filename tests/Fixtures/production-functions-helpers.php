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
require $plugin_root . '/template-tags.php';
require $plugin_root . '/tests/wp-stubs.php';

// Verify the production slug helpers are the functions.php shims and delegate to Seo_Slug.
$seo_url_reflection = new \ReflectionFunction( '\\Racketmanager\\seo_url' );
if ( ! str_ends_with( (string) $seo_url_reflection->getFileName(), '/functions.php' ) ) {
	fwrite( STDERR, "Racketmanager\\seo_url is not loaded from functions.php.\n" );
	exit( 1 );
}

$slug_samples = [
	'Mixed Doubles',
	"Men's Singles",
	'Over-45 Mixed',
	"Tab\tNew\nLine",
	'Café & Bar',
	'  Two   spaces ',
	'!!!',
	'',
];
foreach ( $slug_samples as $slug_sample ) {
	if ( \Racketmanager\seo_url( $slug_sample ) !== \Racketmanager\Domain\Shared\Seo_Slug::encode( $slug_sample ) ) {
		fwrite( STDERR, "Racketmanager\\seo_url does not match Seo_Slug::encode().\n" );
		exit( 1 );
	}
}

$decoded_slug_samples = [
	'mixed-doubles',
	'over_45-mixed',
	'Mixed-DOUBLES',
	'-two-spaces-',
	'',
];
foreach ( $decoded_slug_samples as $decoded_slug_sample ) {
	if ( \Racketmanager\un_seo_url( $decoded_slug_sample ) !== \Racketmanager\Domain\Shared\Seo_Slug::decode( $decoded_slug_sample ) ) {
		fwrite( STDERR, "Racketmanager\\un_seo_url does not match Seo_Slug::decode().\n" );
		exit( 1 );
	}
}
if ( 'mixed doubles' !== \Racketmanager\un_seo_url( 'Mixed-DOUBLES' ) ) {
	fwrite( STDERR, "Racketmanager\\un_seo_url did not lower-case mixed-case input.\n" );
	exit( 1 );
}

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
$team_repo = new class implements \Racketmanager\Repositories\Interfaces\Team_Repository_Interface {
	public function find_by_id( int|string|null $id_or_title ): ?\Racketmanager\Domain\Team\Team {
		$t = new \Racketmanager\Domain\Team\Team();
		if ( is_numeric( $id_or_title ) ) {
			$t->set_id( (int) $id_or_title );
			$t->set_name( 'Team ' . $id_or_title );
		} else {
			$t->set_id( 99 );
			$t->set_name( (string) $id_or_title );
		}
		return $t;
	}
	public function find_id_by_title( string $title ): int {
		return 99;
	}
	public function find_by_club( int $club_id, ?string $type = null ): array { return []; }
	public function find_for_players( string $type ): array { return []; }
	public function find_all( array $args = [] ): array|int { return []; }
	public function save( object $entity ): bool|int { return true; }
	public function has_teams( int $club_id ): bool { return true; }
	public function find_next_sequence_number( string $club_shortcode, string $type ): int { return 1; }
	public function find_captain( int $club_id, int $player ): bool { return true; }
	public function find_teams_by_competition_with_details( int $competition_id, int $season, int $min_fixtures ): array { return []; }
	public function find_team_settings_for_event( int $teamId, int $eventId ): ?\Racketmanager\Domain\DTO\Team\Team_Fixture_Settings_DTO { return null; }
	public function find_team_by_players( array $player_ids ): ?int { return null; }
	public function save_team_players( int $team_id, array $player_ids ): bool { return true; }
	public function save_with_players( \Racketmanager\Domain\Team\Team $team, array $player_ids ): int|false { return 99; }
	public function delete_with_fixtures( int $team_id, \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface $fixture_repository ): bool { return true; }
	public function update_event_settings( int $team_id, int $event_id, string $season, string $captain, string $match_day, ?string $match_time ): bool { return true; }
	public function delete( int $id ): bool { return true; }
	public function find( int|string $id ): ?object { return $this->find_by_id( $id ); }
};

$entry_repo = new class {
	public function find_by_id( $id ) {
		$e = new \Racketmanager\Domain\Tournament\Tournament_Entry();
		$e->id = (int) $id;
		return $e;
	}
	public function find_by_tournament_and_player( int $t_id, int $p_id ) {
		$e = new \Racketmanager\Domain\Tournament\Tournament_Entry();
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

$comp_repo = new class implements \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface {
	public function save( object $entity ): bool|int { return true; }
	public function find_by_id( int|string|null $id ): ?\Racketmanager\Domain\Competition\Competition {
		$c = new class extends \Racketmanager\Domain\Competition\Competition {
			public function __construct() {}
		};
		$c->id = (int) $id;
		$c->name = 'Competition ' . $id;
		return $c;
	}
	public function find_all(): array { return []; }
	public function find_by( array $criteria ): array { return []; }
	public function find_competitions_with_summary( $age_group, $type ): array { return []; }
	public function delete( int $id ): bool { return true; }
	public function get_competition_overview( int $competition_id, int $season, ?int $min_fixtures = null ): ?\Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO { return null; }
	public function is_club_participating( int $competition_id, int $club_id, string $season ): bool { return true; }
	public function get_league_winners( int $competition_id, ?int $season = null ): array { return []; }
	public function get_championship_winners( int $competition_id, ?int $season = null ): array { return []; }
	public function get_winners( string $season, int $competition_id, string $competition_type = 'tournament', bool $group_by = false ): false|array { return []; }
	public function find_by_name( string $name ): ?\Racketmanager\Domain\Competition\Competition {
		$c = new class extends \Racketmanager\Domain\Competition\Competition {
			public function __construct() {}
		};
		$c->id = 56;
		$c->name = 'Summer League';
		return $c;
	}
	public function find_teams( int $competition_id, array $args = [] ): array|int { return []; }
	public function find_players( int $competition_id, array $args = [] ): array|int { return []; }
	public function find_events( int $competition_id, array $args = [] ): array { return []; }
	public function find_matches( int $competition_id, array $args = [] ): array|int { return []; }
};

$fixture_repo = new class {
	public function find_by_id( $id ): ?\Racketmanager\Domain\Fixture\Fixture {
		if ( ! $id || 999999 === (int) $id ) {
			return null;
		}
		$f = new class extends \Racketmanager\Domain\Fixture\Fixture {
			public function __construct() {}
		};
		$f->id = (int) $id;
		return $f;
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
$container->set( 'competition_repository', static fn() => $comp_repo );
$container->set( 'fixture_repository', static fn() => $fixture_repo );

$GLOBALS['racketmanager'] = (object) array( 'container' => $container );

// Test get_team numeric
$team_num = \Racketmanager\get_team( 123 );
if ( ! $team_num instanceof \Racketmanager\Domain\Team\Team || 123 !== $team_num->id ) {
	fwrite( STDERR, "get_team(123) failed.\n" );
	exit( 1 );
}

// Test get_team string name (must call find_by_id, not fail on missing find_by_name)
$team_str = \Racketmanager\get_team( 'Westcliff 1' );
if ( ! $team_str instanceof \Racketmanager\Domain\Team\Team || 'Westcliff 1' !== $team_str->title ) {
	fwrite( STDERR, "get_team('Westcliff 1') failed.\n" );
	exit( 1 );
}

// Test get_team with object pass-through
$orig_team = new \Racketmanager\Domain\Team\Team();
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

// Check amend_points method on League_Team updates in-memory property
$league_team_obj->amend_points( 3.0 );
if ( 3.0 != $league_team_obj->add_points ) {
	fwrite( STDERR, "League_Team amend_points did not set add_points properly.\n" );
	exit( 1 );
}
$league_team_repo = new \Racketmanager\Repositories\League_Team_Repository( $wpdb );
$result = $league_team_repo->amend_points( $league_team_obj->table_id, 3.0 );
if ( ! $result ) {
	fwrite( STDERR, "League_Team_Repository amend_points did not run successfully.\n" );
	exit( 1 );
}

// Test get_league_team with object pass-through
if ( \Racketmanager\get_league_team( $league_team_obj ) !== $league_team_obj ) {
	fwrite( STDERR, "get_league_team(object) failed to return same instance.\n" );
	exit( 1 );
}

// 3. Test get_tournament_entry with ID and with 'key' ('12_34')
$entry_by_id = \Racketmanager\get_tournament_entry( 101, 'id' );
if ( ! $entry_by_id instanceof \Racketmanager\Domain\Tournament\Tournament_Entry || 101 !== $entry_by_id->id ) {
	fwrite( STDERR, "get_tournament_entry by ID failed.\n" );
	exit( 1 );
}

$entry_by_key = \Racketmanager\get_tournament_entry( '12_34', 'key' );
if ( ! $entry_by_key instanceof \Racketmanager\Domain\Tournament\Tournament_Entry || 777 !== $entry_by_key->id || 12 !== $entry_by_key->tournament_id || 34 !== $entry_by_key->player_id ) {
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

// 5. Test get_match deprecation notice and behaviour branches
$match_deprecated_triggered = false;
set_error_handler( function ( $errno, $errstr ) use ( &$match_deprecated_triggered ) {
	if ( E_USER_DEPRECATED === $errno && str_contains( $errstr, 'get_match() is deprecated' ) ) {
		$match_deprecated_triggered = true;
		return true;
	}
	return false;
} );
try {
	$match_by_id = \Racketmanager\get_match( 77 );
} finally {
	restore_error_handler();
}
if ( ! $match_deprecated_triggered || ! $match_by_id instanceof \Racketmanager\Domain\Fixture\Fixture || 77 !== $match_by_id->id ) {
	fwrite( STDERR, "get_match(77) failed or did not trigger E_USER_DEPRECATED notice.\n" );
	exit( 1 );
}

set_error_handler( static fn() => true );
try {
	// Fixture pass-through
	$fixture_pass = \Racketmanager\get_match( $match_by_id );
	if ( $fixture_pass !== $match_by_id ) {
		fwrite( STDERR, "get_match(Fixture) pass-through failed.\n" );
		exit( 1 );
	}

	// Object with ->id property
	$match_from_obj = \Racketmanager\get_match( (object) [ 'id' => 77 ] );
	if ( ! $match_from_obj instanceof \Racketmanager\Domain\Fixture\Fixture || 77 !== $match_from_obj->id ) {
		fwrite( STDERR, "get_match((object)['id'=>77]) lookup failed.\n" );
		exit( 1 );
	}

	// Falsy input / object without id
	if ( null !== \Racketmanager\get_match( null ) ) {
		fwrite( STDERR, "get_match(null) should return null.\n" );
		exit( 1 );
	}
	if ( null !== \Racketmanager\get_match( (object) [ 'no_id' => 123 ] ) ) {
		fwrite( STDERR, "get_match(object without id) should return null.\n" );
		exit( 1 );
	}

	// Nonexistent ID
	if ( null !== \Racketmanager\get_match( 999999 ) ) {
		fwrite( STDERR, "get_match(999999) should return null.\n" );
		exit( 1 );
	}

	// Container fallback when container does not have fixture_repository
	$saved_app = $GLOBALS['racketmanager'];
	$GLOBALS['racketmanager'] = (object) array( 'container' => new \Racketmanager\Services\Container\Simple_Container() );
	$fallback_match = \Racketmanager\get_match( 999999 );
	$GLOBALS['racketmanager'] = $saved_app;
	if ( null !== $fallback_match ) {
		fwrite( STDERR, "get_match container fallback for missing id should return null.\n" );
		exit( 1 );
	}
} finally {
	restore_error_handler();
}

// 6. Test get_rubber deprecation notice
$rubber_deprecated_triggered = false;
set_error_handler( function ( $errno, $errstr ) use ( &$rubber_deprecated_triggered ) {
	if ( E_USER_DEPRECATED === $errno && str_contains( $errstr, 'get_rubber() is deprecated' ) ) {
		$rubber_deprecated_triggered = true;
		return true;
	}
	return false;
} );
try {
	\Racketmanager\get_rubber( 999999 );
} finally {
	restore_error_handler();
}
if ( ! $rubber_deprecated_triggered ) {
	fwrite( STDERR, "get_rubber() did not trigger E_USER_DEPRECATED notice.\n" );
	exit( 1 );
}

// 7. Test the deprecated compatibility helper's legacy match contract.
$legacy_match = (object) [
	'teams' => [
		'home' => (object) [ 'players' => [ (object) [ 'ID' => 30 ] ] ],
		'away' => (object) [ 'players' => [ (object) [ 'ID' => 40 ] ] ],
	],
];

$helper_deprecated_triggered = false;
set_error_handler( function ( $errno, $errstr ) use ( &$helper_deprecated_triggered ) {
	if ( E_USER_DEPRECATED === $errno && str_contains( $errstr, 'match_add_players() is deprecated' ) ) {
		$helper_deprecated_triggered = true;
		return true;
	}
	return false;
} );
try {
	$players_legacy = \Racketmanager\match_add_players( [], $legacy_match );
} finally {
	restore_error_handler();
}
if ( [ 30, 40 ] !== $players_legacy || ! $helper_deprecated_triggered ) {
	fwrite( STDERR, "match_add_players legacy contract or deprecation failed: " . json_encode( $players_legacy ) . "\n" );
	exit( 1 );
}

// 8. Test get_competition with container resolution and object pass-through
$comp_deprecated_triggered = false;
set_error_handler( function ( $errno, $errstr ) use ( &$comp_deprecated_triggered ) {
	if ( E_USER_DEPRECATED === $errno && str_contains( $errstr, 'get_competition() is deprecated' ) ) {
		$comp_deprecated_triggered = true;
		return true;
	}
	return false;
} );
try {
	$comp_by_id = \Racketmanager\get_competition( 55 );
} finally {
	restore_error_handler();
}
if ( ! $comp_deprecated_triggered || ! $comp_by_id instanceof \Racketmanager\Domain\Competition\Competition || 55 !== $comp_by_id->id ) {
	fwrite( STDERR, "get_competition(55) failed or did not trigger deprecation notice.\n" );
	exit( 1 );
}

set_error_handler( static fn() => true );
try {
	$comp_by_name = \Racketmanager\get_competition( 'Summer League', 'name' );
	$comp_pass    = \Racketmanager\get_competition( $comp_by_id );
} finally {
	restore_error_handler();
}

if ( ! $comp_by_name instanceof \Racketmanager\Domain\Competition\Competition || 'Summer League' !== $comp_by_name->name ) {
	fwrite( STDERR, "get_competition('Summer League', 'name') failed.\n" );
	exit( 1 );
}

if ( $comp_pass !== $comp_by_id ) {
	fwrite( STDERR, "get_competition(object) pass-through failed.\n" );
	exit( 1 );
}

// 9. Test get_competition with competition_service registered in container
$mock_comp_service = new class( $comp_repo ) {
	public function __construct( private $repo ) {}
	public function get_competition( $id_or_name, $search_term = 'id' ) {
		if ( 'name' === $search_term ) {
			return $this->repo->find_by_name( (string) $id_or_name );
		}
		return $this->repo->find_by_id( (int) $id_or_name );
	}
};
$container->set( 'competition_service', static fn() => $mock_comp_service );

set_error_handler( static fn() => true );
try {
	$comp_svc_by_id   = \Racketmanager\get_competition( 55 );
	$comp_svc_by_name = \Racketmanager\get_competition( 'Summer League', 'name' );
} finally {
	restore_error_handler();
}

if ( ! $comp_svc_by_id instanceof \Racketmanager\Domain\Competition\Competition || 55 !== $comp_svc_by_id->id ) {
	fwrite( STDERR, "get_competition(55) with competition_service failed.\n" );
	exit( 1 );
}

if ( ! $comp_svc_by_name instanceof \Racketmanager\Domain\Competition\Competition || 'Summer League' !== $comp_svc_by_name->name ) {
	fwrite( STDERR, "get_competition('Summer League', 'name') with competition_service failed.\n" );
	exit( 1 );
}

// 10. Test get_league_repository_for_helpers fallback hydration with event repository
unset( $GLOBALS['racketmanager'] );
$wpdb->get_row_callback = function( $query ) {
	if ( str_contains( $query, 'wp_racketmanager_leagues' ) ) {
		return (object) [
			'id'       => 2395,
			'title'    => 'Division 1 Men',
			'event_id' => 93,
			'seasons'  => serialize( [ '2026' => [ 'name' => '2026' ] ] ),
			'settings' => serialize( [] ),
		];
	}
	if ( str_contains( $query, 'wp_racketmanager_events' ) ) {
		return (object) [
			'id'             => 93,
			'name'           => 'Men Doubles Event',
			'type'           => 'doubles',
			'competition_id' => 12,
			'seasons'        => '[]',
		];
	}
	return null;
};

$helper_league_repo = \Racketmanager\get_league_repository_for_helpers();
$hydrated_league    = $helper_league_repo->find_by_id( 2395 );

if ( ! $hydrated_league instanceof \Racketmanager\Domain\Competition\League || 2395 !== $hydrated_league->id ) {
	fwrite( STDERR, "get_league_repository_for_helpers find_by_id failed.\n" );
	exit( 1 );
}

if ( ! $hydrated_league->event instanceof \Racketmanager\Domain\Competition\Event || 93 !== $hydrated_league->event->id ) {
	fwrite( STDERR, "get_league_repository_for_helpers did not hydrate attached event.\n" );
	exit( 1 );
}

// 11. Test get_club_role and get_club_role_repository_for_helpers
$mock_club_role_repo = new class implements \Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface {
	public function find( int|string|null $id ): ?\Racketmanager\Domain\Club\Club_Role {
		if ( 5 === (int) $id ) {
			$role = new \Racketmanager\Domain\Club\Club_Role();
			$role->id = 5;
			$role->user_id = 42;
			$role->club_id = 1;
			$role->role_id = 2;
			return $role;
		}
		return null;
	}
	public function find_by_id( int|string|null $id ): ?\Racketmanager\Domain\Club\Club_Role {
		return $this->find( $id );
	}
	public function find_all(): array { return []; }
	public function find_by_club( int $club_id ): array { return []; }
	public function find_by_user( int $user_id ): array { return []; }
	public function save( object $entity ): bool|int { return true; }
	public function delete( int $id ): bool { return true; }
	public function search( array $args ): ?array { return null; }
	public function build_club_roles( string $sql, string $group ): array { return []; }
	public function delete_for_role( int $id ): bool { return true; }
	public function delete_for_club( int $club_id ): bool { return true; }
	public function get_roles_for_club( int $club_id ): array { return []; }
	public function find_clubs_by_player_and_role( int $user_id, string $role_name, ?int $club_id ): array { return []; }
};

$container->set( 'club_role_repository', static fn() => $mock_club_role_repo );
$GLOBALS['racketmanager'] = (object) array( 'container' => $container );

$club_role_by_id = @\Racketmanager\get_club_role( 5 );
if ( ! $club_role_by_id instanceof \Racketmanager\Domain\Club\Club_Role || 5 !== $club_role_by_id->id || 42 !== $club_role_by_id->user_id ) {
	fwrite( STDERR, "get_club_role(5) container resolution failed.\n" );
	exit( 1 );
}

// Object pass-through
if ( @\Racketmanager\get_club_role( $club_role_by_id ) !== $club_role_by_id ) {
	fwrite( STDERR, "get_club_role(object) pass-through failed.\n" );
	exit( 1 );
}

// Global fallback with object
$GLOBALS['club_role'] = $club_role_by_id;
if ( @\Racketmanager\get_club_role() !== $club_role_by_id ) {
	fwrite( STDERR, "get_club_role() global fallback failed.\n" );
	exit( 1 );
}

// Global fallback with numeric ID
$GLOBALS['club_role'] = 5;
if ( @\Racketmanager\get_club_role()?->id !== 5 ) {
	fwrite( STDERR, "get_club_role() global numeric ID fallback failed.\n" );
	exit( 1 );
}
unset( $GLOBALS['club_role'] );

// 12. Test get_competition with string query var fallback
$mock_comp_repo = new class extends \Racketmanager\Repositories\Competition_Repository {
	public function __construct() {}
	public function find_by_id( string|int|null $id ): ?\Racketmanager\Domain\Competition\Competition {
		if ( 12 === (int) $id ) {
			return \Racketmanager\Domain\Competition\Competition::from_object( (object) [ 'id' => 12, 'name' => 'Summer League', 'type' => 'league' ] );
		}
		return null;
	}
	public function find_by_name( string $name ): ?\Racketmanager\Domain\Competition\Competition {
		$names = [
			'summer league' => [ 12, 'Summer League' ],
			'under-18 cup'  => [ 18, 'Under-18 Cup' ],
			'Under-18 Cup'  => [ 18, 'Under-18 Cup' ],
			'2026'          => [ 26, '2026' ],
		];
		if ( isset( $names[ $name ] ) ) {
			return \Racketmanager\Domain\Competition\Competition::from_object( (object) [ 'id' => $names[ $name ][0], 'name' => $names[ $name ][1], 'type' => 'league' ] );
		}
		return null;
	}
};

$container->set( 'competition_repository', static fn() => $mock_comp_repo );
$competition_service = ( new \ReflectionClass( \Racketmanager\Services\Competition_Service::class ) )->newInstanceWithoutConstructor();
( new \ReflectionProperty( \Racketmanager\Services\Competition_Service::class, 'competition_repository' ) )->setValue( $competition_service, $mock_comp_repo );
$container->set( 'competition_service', static fn() => $competition_service );

$GLOBALS['competition'] = 'summer-league';
$resolved_comp = @\Racketmanager\get_competition();
if ( ! $resolved_comp instanceof \Racketmanager\Domain\Competition\Competition || 12 !== $resolved_comp->id ) {
	fwrite( STDERR, "get_competition() global string query var 'summer-league' resolution failed.\n" );
	exit( 1 );
}

$GLOBALS['competition'] = 'under_18-cup';
foreach ( [ null, 0, '' ] as $input ) {
	$resolved_comp = @\Racketmanager\get_competition( $input );
	if ( ! $resolved_comp instanceof \Racketmanager\Domain\Competition\Competition || 18 !== $resolved_comp->id ) {
		fwrite( STDERR, "get_competition() global underscored slug lookup failed for empty input.\n" );
		exit( 1 );
	}
}

$GLOBALS['competition'] = '2026';
$resolved_comp = @\Racketmanager\get_competition();
if ( ! $resolved_comp instanceof \Racketmanager\Domain\Competition\Competition || 26 !== $resolved_comp->id ) {
	fwrite( STDERR, "get_competition() global numeric-string query var must resolve as a name.\n" );
	exit( 1 );
}

$explicit_comp = @\Racketmanager\get_competition( 'Under-18 Cup', 'name' );
if ( ! $explicit_comp instanceof \Racketmanager\Domain\Competition\Competition || 18 !== $explicit_comp->id ) {
	fwrite( STDERR, "get_competition() explicit hyphenated name lookup failed.\n" );
	exit( 1 );
}
unset( $GLOBALS['competition'] );

// 14. Test is_lta_number_required template tag
$option_service = new class extends \Racketmanager\Infrastructure\Wordpress\Options\Option_Service {
	public function __construct() {}
	public function is_lta_number_required(): bool {
		return true;
	}
};

$container->set( \Racketmanager\Infrastructure\Wordpress\Options\Option_Service::class, static fn() => $option_service );
$GLOBALS['racketmanager'] = (object) array( 'container' => $container );

if ( ! \Racketmanager\is_lta_number_required() ) {
	fwrite( STDERR, "is_lta_number_required() container resolution failed.\n" );
	exit( 1 );
}

echo 'ok';
