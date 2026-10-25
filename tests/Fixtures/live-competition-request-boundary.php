<?php
/** Manual, read-only smoke check for the local site's review fixtures. */
declare( strict_types=1 );

if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}

$started = microtime( true );
require dirname( __DIR__, 5 ) . '/wp-load.php';

add_filter( 'query', static function ( string $sql ): string {
    if ( preg_match( '/^\s*(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|CREATE|TRUNCATE)\b/i', $sql ) ) {
        throw new RuntimeException( 'Read-only smoke check blocked a database write.' );
    }
    return $sql;
}, PHP_INT_MAX );

function request_boundary_check( bool $condition, string $label ): void {
    if ( ! $condition ) {
        throw new RuntimeException( 'FAIL: ' . $label );
    }
    print 'PASS: ' . $label . PHP_EOL;
}

function request_boundary_reset( array $get = [], array $vars = [], array $post = [] ): void {
    $_GET = $get;
    $_POST = $post;
    $GLOBALS['wp']->query_vars = $vars;
    if ( isset( $GLOBALS['wp_query'] ) && is_object( $GLOBALS['wp_query'] ) ) {
        $GLOBALS['wp_query']->query_vars = $vars;
    }
    wp_cache_flush();
}

function request_boundary_render( string $shortcode, array $get = [], array $vars = [], array $post = [] ): string {
    request_boundary_reset( $get, $vars, $post );
    $html = do_shortcode( $shortcode );
    printf( "%s GET=%s vars=%s POST=%s: %d bytes, %s\n", $shortcode, json_encode( $get ), json_encode( $vars ), json_encode( $post ), strlen( $html ), md5( $html ) );
    return $html;
}

$baseline = request_boundary_render( '[standings league_id=2347 season=2026]' );
request_boundary_check( 'b6a157288e7af7720eb99ccbe44b55e9' === md5( $baseline ), 'unchanged no-request HEAD standings baseline' );
$expected = request_boundary_render( '[standings league_id=2347 season=2025]' );
foreach ( [
    [ [ 'season' => '2025' ], [] ],
    [ [ 'season_2347' => '2025' ], [] ],
    [ [], [ 'season' => '2025' ] ],
] as [ $get, $vars ] ) {
    $actual = request_boundary_render( '[standings league_id=2347 season=2026]', $get, $vars );
    request_boundary_check( $expected === $actual, 'league URL season overrides shortcode attribute' );
}
$actual = request_boundary_render( '[standings league_id=2347 season=2026]', [ 'season' => '2026' ], [], [ 'season' => '2025' ] );
request_boundary_check( $expected === $actual, 'POST season wins over GET and attribute' );

$expected = request_boundary_render( '[event-standings id=93 season=2025]' );
request_boundary_check( 842 === strlen( $expected ), 'event 2025 matches recorded HEAD output length' );
foreach ( [ [ [ 'season' => '2025' ], [] ], [ [], [ 'season' => '2025' ] ] ] as [ $get, $vars ] ) {
    $actual = request_boundary_render( '[event-standings id=93 season=2026]', $get, $vars );
    request_boundary_check( $expected === $actual, 'event URL season overrides shortcode attribute' );
}

$unknown_season_output = request_boundary_render( '[event id=93]', [ 'season' => '1999' ] );
request_boundary_check( str_contains( $unknown_season_output, 'Season not found for event' ), 'unknown URL season yields error message' );

$event_2025_expected = request_boundary_render( '[event id=93 season=2025]' );
$event_2025_actual   = request_boundary_render( '[event id=93]', [ 'season' => '2025' ] );
request_boundary_check( $event_2025_expected === $event_2025_actual, 'event shortcode URL season matches explicit season' );

$dropdown_expected = request_boundary_render( '[league-dropdown id=93 season=2025]' );
$dropdown_actual   = request_boundary_render( '[league-dropdown id=93]', [ 'season' => '2025' ] );
request_boundary_check( $dropdown_expected === $dropdown_actual, 'league-dropdown shortcode URL season matches explicit season' );

$club_event_2025 = request_boundary_render( '[club-event]', [], [ 'club_name' => 'dl-southend', 'event' => 'ladies-jubilee-bowl', 'season' => '2025' ] );
preg_match_all( '/\/2025\/player\//', $club_event_2025, $m2025 );
preg_match_all( '/\/2026\/player\//', $club_event_2025, $m2026 );
request_boundary_check( 5 === count( $m2025[0] ) && 0 === count( $m2026[0] ), 'club-event season 2025 query var generates 2025 player links' );

$club_event_default = request_boundary_render( '[club-event]', [], [ 'club_name' => 'dl-southend', 'event' => 'ladies-jubilee-bowl' ] );
preg_match_all( '/\/2026\/player\//', $club_event_default, $m_def_2026 );
request_boundary_check( 6 === count( $m_def_2026[0] ), 'club-event default season generates 2026 player links' );

$club_event_1999 = request_boundary_render( '[club-event]', [], [ 'club_name' => 'dl-southend', 'event' => 'ladies-jubilee-bowl', 'season' => '1999' ] );
request_boundary_check( str_contains( $club_event_1999, 'Event not found for competition' ), 'club-event unknown season 1999 yields error' );

$club_team_1999 = request_boundary_render( '[club-team]', [], [ 'club_name' => 'dl-southend', 'event' => 'ladies-jubilee-bowl', 'team' => 'dl-southend-ladies-1', 'season' => '1999' ] );
request_boundary_check( str_contains( $club_team_1999, 'Event not found for competition' ), 'club-team unknown season 1999 yields error' );

$club_team_2025 = request_boundary_render( '[club-team]', [], [ 'club_name' => 'dl-southend', 'event' => 'ladies-jubilee-bowl', 'team' => 'dl-southend-ladies-1', 'season' => '2025' ] );
request_boundary_check( ! str_contains( $club_team_2025, 'Event not found for competition' ) && str_contains( $club_team_2025, 'DL Southend Ladies 1' ), 'club-team season 2025 renders team page' );

$hashes = [];
foreach ( [ 1, 2, 3 ] as $day ) {
    $expected = request_boundary_render( '[matches league_id=1 template="" limit=false matches=' . $day . ']' );
    $actual = request_boundary_render( '[matches league_id=1 template="" limit=false]', [ 'match_day' => (string) $day ] );
    request_boundary_check( $expected === $actual, 'match-day URL ' . $day . ' equals explicit selection' );
    $hashes[] = md5( $actual );
}
request_boundary_check( 3 === count( array_unique( $hashes ) ), 'match-day selector changes rendered fixtures' );

$query_service = \Racketmanager\get_league_query_service();
$rendered_all = request_boundary_render( '[matches league_id=1 template="" limit=false matches=-1]' );
preg_match_all( '/class="team-match__wrapper" href="([^"]+)"/', $rendered_all, $all_links );
request_boundary_check( 30 === count( $all_links[1] ), 'unpaginated shortcode contains all 30 fixture links' );
request_boundary_reset();
$league = \Racketmanager\get_league( 1 );
$query_service->set_season( $league, '2026' );
$all = $query_service->get_matches( $league, [ 'limit' => false, 'match_day' => -1, 'final' => '', 'orderby' => [ 'id' => 'ASC' ] ] );
$all_ids = array_map( static fn( $fixture ) => $fixture->id, $all );
foreach ( [ 1, 2, 3 ] as $page ) {
    request_boundary_reset( [ 'match_paged' => (string) $page ] );
    $league = \Racketmanager\get_league( 1 );
    $query_service->set_season( $league, '2026' );
    $matches = $query_service->get_matches( $league, [ 'limit' => 5, 'match_day' => -1, 'final' => '', 'orderby' => [ 'id' => 'ASC' ] ] );
    $query_service->set_num_matches( $league );
    $ids = array_map( static fn( $fixture ) => $fixture->id, $matches );
    print 'Page ' . $page . ': ' . implode( ',', $ids ) . PHP_EOL;
    request_boundary_check( array_slice( $all_ids, ( $page - 1 ) * 5, 5 ) === $ids, 'requested page returns correct fixture slice' );
    $page_links = $league->get_page_links();
    request_boundary_check( $page === $league->current_page_matches, 'pagination links retain requested page' );
    request_boundary_check( 1 === preg_match( '/class="page-numbers current">' . $page . '<\/span>/', $page_links ), 'generated pagination highlights requested page' );
    $html = request_boundary_render( '[matches league_id=1 template="" limit=5 matches=-1]', [ 'match_paged' => (string) $page ] );
    preg_match_all( '/class="team-match__wrapper" href="([^"]+)"/', $html, $page_links );
    request_boundary_check( array_slice( $all_links[1], ( $page - 1 ) * 5, 5 ) === $page_links[1], 'rendered shortcode contains the correct five fixture links' );
}

request_boundary_reset( [ 'final' => 'semi' ] );
$league = \Racketmanager\get_league( 42 );
$query_service->set_season( $league );
request_boundary_check( 'semi' === $league->championship->get_current_final_key(), 'league 42 requested final matches HEAD selection' );

// The generic league editor renders cup leagues; tournaments use a separate screen.
request_boundary_reset( [ 'league_id' => '64', 'final' => 'semi', 'mode' => 'edit' ] );
add_filter( 'user_has_cap', static function ( array $caps ): array {
    $caps['edit_matches'] = true;
    return $caps;
} );
$screen = new \Racketmanager\Admin\Admin_League( $GLOBALS['racketmanager'] );
ob_start();
try {
    $screen->display_match_page();
    $html = (string) ob_get_contents();
} finally {
    ob_end_clean();
}
request_boundary_check( str_contains( $html, 'Edit Matches - Semi' ), 'admin finals edit page selects semi-final' );
request_boundary_check( str_contains( $html, 'value="semi"' ), 'admin final round is included in form' );
printf( "Read-only checks completed in %.2fs\n", microtime( true ) - $started );