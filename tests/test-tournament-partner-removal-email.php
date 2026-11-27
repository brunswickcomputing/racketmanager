<?php
/**
 * Tests for tournament partner removal email template and currency helper.
 *
 * Covers:
 * 1. get_currency_amount() formatting with string, null, empty string, and float amounts.
 * 2. the_currency_amount() output buffering and escaping.
 * 3. Email template rendering: component structure (header, title, salutation, paragraph, action-link, hr, contact, closing, footer).
 * 4. Single vs multiple event names formatting (array and comma-delimited string).
 * 5. Conditional refund notice when refund_amount > 0 and exact escaping.
 * 6. Situational messaging: remaining events active vs full withdrawal confirmation.
 * 7. Action link and deadline rendering when tournament is open vs closed.
 * 8. Tournament link vs escaped tournament name fallback.
 */

if ( file_exists( __DIR__ . '/../../../../wp-load.php' ) ) {
	require_once __DIR__ . '/../../../../wp-load.php';
}

require_once __DIR__ . '/../template-tags.php';

use function Racketmanager\get_currency_amount;
use function Racketmanager\the_currency_amount;

function assert_true( bool $condition, string $message ): void {
	if ( ! $condition ) {
		echo "[-] FAILED: $message\n";
		exit( 1 );
	}
	echo "[+] PASSED: $message\n";
}

function assert_false( bool $condition, string $message ): void {
	if ( $condition ) {
		echo "[-] FAILED: $message (Expected false, got true)\n";
		exit( 1 );
	}
	echo "[+] PASSED: $message\n";
}

function assert_equals( mixed $expected, mixed $actual, string $message ): void {
	if ( $expected !== $actual ) {
		echo "[-] FAILED: $message (Expected " . var_export( $expected, true ) . ", got " . var_export( $actual, true ) . ")\n";
		exit( 1 );
	}
	echo "[+] PASSED: $message\n";
}

function assert_contains( string $needle, string $haystack, string $message ): void {
	if ( strpos( $haystack, $needle ) === false ) {
		echo "[-] FAILED: $message (Expected string not found: '$needle')\n";
		exit( 1 );
	}
	echo "[+] PASSED: $message\n";
}

function assert_not_contains( string $needle, string $haystack, string $message ): void {
	if ( strpos( $haystack, $needle ) !== false ) {
		echo "[-] FAILED: $message (String should not be present: '$needle')\n";
		exit( 1 );
	}
	echo "[+] PASSED: $message\n";
}

echo "=== Running Tournament Partner Removal Email & Currency Helper Tests ===\n\n";

// -------------------------------------------------------------
// Test 1: get_currency_amount() and the_currency_amount() helpers
// -------------------------------------------------------------
echo "Test 1: Currency formatting helpers...\n";

$amt_40 = get_currency_amount( '40.00' );
assert_true( str_contains( $amt_40, '40.00' ), "get_currency_amount('40.00') contains 40.00" );

$amt_25 = get_currency_amount( '25' );
assert_true( str_contains( $amt_25, '25.00' ), "get_currency_amount('25') formats with 2 decimals" );

$amt_null = get_currency_amount( null );
assert_true( str_contains( $amt_null, '0.00' ), "get_currency_amount(null) returns formatted 0.00" );

$amt_empty = get_currency_amount( '' );
assert_true( str_contains( $amt_empty, '0.00' ), "get_currency_amount('') returns formatted 0.00" );

ob_start();
the_currency_amount( '15.50' );
$the_amt = ob_get_clean();
assert_true( str_contains( $the_amt, '15.50' ), "the_currency_amount('15.50') echoes formatted amount" );
assert_true( $the_amt === esc_html( get_currency_amount( '15.50' ) ), "the_currency_amount matches escaped get_currency_amount" );

// -------------------------------------------------------------
// Test 2: Full withdrawal with refund, registration open
// -------------------------------------------------------------
echo "\nTest 2: Template render - full withdrawal with refund, open registration...\n";

global $racketmanager;

$tournament_obj = (object) array(
	'name'                 => 'Summer Open 2026',
	'is_open'              => true,
	'date_closing_display' => '1 June 2026',
);

$output_withdrawn = $racketmanager->shortcodes->load_template(
	'tournament-partner-removed',
	array(
		'tournament'       => $tournament_obj,
		'tournament_name'  => 'Summer Open 2026',
		'tournament_link'  => '<a href="https://example.com/tournaments/summer-open">Summer Open 2026</a>',
		'action_url'       => 'https://example.com/tournaments/summer-open/entry',
		'partner_name'     => 'Alice Walker',
		'player_name'      => 'Bob Smith',
		'event_names'      => array( "Men's Doubles" ),
		'refund_amount'    => 40.0,
		'remaining_events' => array(),
		'contact_email'    => 'secretary@example.com',
		'organisation'     => 'Test Tennis Club',
	),
	'email'
);

// Verify core email component inclusion
assert_contains( '<!DOCTYPE html', $output_withdrawn, 'Contains HTML doctype from email-header' );
assert_contains( 'Partner removal notification', $output_withdrawn, 'Contains title component' );
assert_contains( 'Dear Alice Walker', $output_withdrawn, 'Contains salutation with partner name' );
assert_contains( 'Bob Smith', $output_withdrawn, 'Contains removing player name' );
assert_contains( "Men&#039;s Doubles", $output_withdrawn, "Contains escaped event name" );
assert_contains( '<a href="https://example.com/tournaments/summer-open">Summer Open 2026</a>', $output_withdrawn, 'Renders tournament link as HTML when provided' );
assert_contains( 'You have now been withdrawn from the <a href="https://example.com/tournaments/summer-open">Summer Open 2026</a> tournament.', $output_withdrawn, 'Contains withdrawal confirmation when remaining_events is empty' );
assert_not_contains( 'Your entries for other events in this tournament remain active.', $output_withdrawn, 'Does not contain retention message when withdrawn' );

// Verify refund notice and monetary escaping
$expected_refund_phrase = 'A refund of ' . get_currency_amount( '40.00' ) . ' is due for your entry fees and will be processed by the tournament organizers.';
assert_contains( $expected_refund_phrase, $output_withdrawn, 'Contains exact refund notice phrase' );
assert_true( substr_count( $output_withdrawn, $expected_refund_phrase ) === 1, 'Refund notice appears exactly once (escaped once by paragraph component)' );

// Verify action link and closing deadline for open tournament
assert_contains( '1 June 2026', $output_withdrawn, 'Contains closing deadline' );
assert_contains( 'Entry form', $output_withdrawn, 'Contains Entry form action link' );
assert_contains( 'https://example.com/tournaments/summer-open/entry', $output_withdrawn, 'Contains action URL' );

// Verify contact, closing, and footer
assert_contains( 'secretary@example.com', $output_withdrawn, 'Contains contact email' );
assert_contains( 'The Test Tennis Club Team', $output_withdrawn, 'Contains closing section' );
assert_contains( '©', $output_withdrawn, 'Contains footer copyright' );

// -------------------------------------------------------------
// Test 3: Multi-event retention, multiple event names, registration closed, no refund
// -------------------------------------------------------------
echo "\nTest 3: Template render - multi-event retention, registration closed, no refund...\n";

$tournament_closed = (object) array(
	'name'                 => 'Winter Championship',
	'is_open'              => false,
	'date_closing_display' => '1 November 2025',
);

$output_retained = $racketmanager->shortcodes->load_template(
	'tournament-partner-removed',
	array(
		'tournament'       => $tournament_closed,
		'tournament_name'  => 'Winter Championship',
		'partner_name'     => 'Charlie Brown',
		'player_name'      => 'Diana Prince',
		'event_names'      => array( "Men's Doubles", "Mixed Doubles" ),
		'refund_amount'    => 0,
		'remaining_events' => array( (object) array( 'name' => "Men's Singles" ) ),
		'contact_email'    => 'admin@example.com',
		'organisation'     => 'Winter League',
	),
	'email'
);

assert_contains( 'Dear Charlie Brown', $output_retained, 'Contains partner name' );
assert_contains( 'Diana Prince', $output_retained, 'Contains removing player name' );
assert_contains( "Men&#039;s Doubles, Mixed Doubles", $output_retained, 'Formats multiple event names with comma separation' );
assert_contains( 'Your entries for other events in this tournament remain active.', $output_retained, 'Contains retention message when remaining_events is non-empty' );
assert_not_contains( 'You have now been withdrawn', $output_retained, 'Does not contain withdrawal statement when remaining_events is non-empty' );
assert_not_contains( 'A refund of', $output_retained, 'Does not contain refund statement when refund_amount is 0' );
assert_not_contains( 'Entry form', $output_retained, 'Does not contain entry form link when tournament is closed' );

// -------------------------------------------------------------
// Test 4: Comma-delimited event names string and partial refund
// -------------------------------------------------------------
echo "\nTest 4: Comma-delimited event names string and partial refund...\n";

$output_string_events = $racketmanager->shortcodes->load_template(
	'tournament-partner-removed',
	array(
		'tournament'       => $tournament_obj,
		'tournament_name'  => 'Summer Open 2026',
		'partner_name'     => 'Frank Castle',
		'player_name'      => 'Peter Parker',
		'event_names'      => "Men's Doubles, Mixed Doubles",
		'refund_amount'    => 15.00,
		'remaining_events' => array(),
	),
	'email'
);

assert_contains( 'Dear Frank Castle', $output_string_events, 'Renders partner name from partner_name' );
assert_contains( 'Peter Parker', $output_string_events, 'Renders removing player name from player_name' );
assert_contains( "Men&#039;s Doubles, Mixed Doubles", $output_string_events, 'Renders string event names' );
$expected_partial_refund = 'A refund of ' . get_currency_amount( '15.00' ) . ' is due for your entry fees and will be processed by the tournament organizers.';
assert_contains( $expected_partial_refund, $output_string_events, 'Renders partial refund amount' );

// -------------------------------------------------------------
// Test 5: Unbilled pending partner (0 refund, no remaining events)
// -------------------------------------------------------------
echo "\nTest 5: Unbilled pending partner (0 refund, empty remaining events)...\n";

$output_pending = $racketmanager->shortcodes->load_template(
	'tournament-partner-removed',
	array(
		'tournament'       => $tournament_closed,
		'tournament_name'  => 'Winter Championship',
		'partner_name'     => 'Pending Partner',
		'player_name'      => 'Host Player',
		'event_names'      => array( "Men's Doubles" ),
		'refund_amount'    => 0.0,
		'remaining_events' => array(),
	),
	'email'
);

assert_contains( 'Dear Pending Partner', $output_pending, 'Contains pending partner name' );
assert_not_contains( 'A refund of', $output_pending, 'Pending partner with 0 refund has no refund notice' );
assert_contains( 'You have now been withdrawn from the Winter Championship tournament.', $output_pending, 'Withdrawn confirmation shown when no remaining events' );

// -------------------------------------------------------------
// Test 6: Fallback escaping when no tournament link is supplied
// -------------------------------------------------------------
echo "\nTest 6: Tournament name fallback is escaped when tournament_link is absent...\n";

$output_fallback = $racketmanager->shortcodes->load_template(
	'tournament-partner-removed',
	array(
		'tournament'       => $tournament_closed,
		'tournament_name'  => 'Tom & Jerry <Open>',
		'partner_name'     => 'Grace Hopper',
		'player_name'      => 'Alan Turing',
		'event_names'      => array( "Men's Doubles" ),
		'refund_amount'    => 0,
		'remaining_events' => array(),
	),
	'email'
);

assert_contains( 'Tom &amp; Jerry &lt;Open&gt; tournament.', $output_fallback, 'Escapes raw tournament name in withdrawal paragraph' );
assert_not_contains( 'Tom & Jerry <Open>', $output_fallback, 'Raw tournament name is not emitted unescaped' );
assert_not_contains( '<Open>', $output_fallback, 'Raw angle brackets from tournament name are not emitted' );

echo "\nAll Step 1 tests passed successfully!\n";

// =============================================================
// STEP 2: Financial Reconciliation & Refund Reporting Tests
// =============================================================
echo "\n=== Running Step 2: Financial Reconciliation & Refund Reporting Tests ===\n\n";

// Set up isolated SQLite database to mock wp_racketmanager_invoices,
// wp_racketmanager_tournament_entries, and wp_options for test safety without touching MyISAM.
$pdo = new PDO( 'sqlite::memory:' );
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

$pdo->exec( '
CREATE TABLE wp_racketmanager_tournament_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tournament_id INT,
    player_id INT,
    status INT,
    fee VARCHAR(255),
    club_id INT
);
CREATE TABLE wp_racketmanager_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    charge_id INT,
    club_id INT,
    player_id INT,
    status VARCHAR(50),
    invoiceNumber INT,
    date DATE,
    date_due DATE,
    amount DECIMAL(10,2),
    payment_reference VARCHAR(255),
    purchase_order VARCHAR(255),
    details TEXT
);
CREATE TABLE wp_options (
    option_id INTEGER PRIMARY KEY AUTOINCREMENT,
    option_name VARCHAR(191) UNIQUE,
    option_value TEXT,
    autoload VARCHAR(20)
);
CREATE TABLE wp_racketmanager_table (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    team_id INT,
    league_id INT,
    season VARCHAR(50),
    points_plus FLOAT NOT NULL DEFAULT 0,
    points_minus FLOAT NOT NULL DEFAULT 0,
    points_2_plus INT NOT NULL DEFAULT 0,
    points_2_minus INT NOT NULL DEFAULT 0,
    add_points FLOAT NOT NULL DEFAULT 0,
    done_matches INT NOT NULL DEFAULT 0,
    won_matches INT NOT NULL DEFAULT 0,
    draw_matches INT NOT NULL DEFAULT 0,
    lost_matches INT NOT NULL DEFAULT 0,
    diff INT NOT NULL DEFAULT 0,
    "group" VARCHAR(30) NOT NULL DEFAULT "",
    rank INT NOT NULL DEFAULT 0,
    profile INT NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT "",
    rating DECIMAL(10,2),
    custom TEXT
);
CREATE TABLE wp_racketmanager_teams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255),
    stadium VARCHAR(255),
    home VARCHAR(255),
    roster TEXT,
    profile INT,
    status INT,
    club_id INT,
    type VARCHAR(50),
    team_type VARCHAR(50)
);
CREATE TABLE wp_racketmanager_team_players (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    team_id INT,
    player_id INT
);
CREATE TABLE wp_racketmanager_team_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    team_id INT,
    event_id INT,
    captain INT,
    match_day VARCHAR(50),
    match_time VARCHAR(50)
);
CREATE TABLE wp_racketmanager_matches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    league_id INT,
    season VARCHAR(50),
    home_team VARCHAR(50),
    away_team VARCHAR(50),
    winner_id INT,
    status INT,
    home_points VARCHAR(30),
    away_points VARCHAR(30),
    date_result_entered DATETIME
);
CREATE TABLE wp_racketmanager_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    userid INT,
    date DATETIME,
    status VARCHAR(50),
    subject VARCHAR(255),
    sender VARCHAR(255),
    message_object TEXT
);
' );

// Prepopulate billing option in SQLite
$billing = $racketmanager->get_options( 'billing' );
$pdo->prepare( 'INSERT INTO wp_options (option_name, option_value) VALUES (?, ?)' )
    ->execute( array( 'racketmanager_billing', serialize( $billing ) ) );

global $wpdb;
$original_wpdb = $wpdb;

// Record snapshot of live database options before Step 2 tests run to verify zero mutations
$live_options_raw_before = $original_wpdb->get_var( "SELECT option_value FROM $original_wpdb->options WHERE option_name = 'racketmanager'" );
$live_options_before     = maybe_unserialize( $live_options_raw_before );
$live_invoice_num_before = isset( $live_options_before['billing']['invoiceNumber'] ) ? (int) $live_options_before['billing']['invoiceNumber'] : null;

// Snapshot in-memory racketmanager options
$original_racketmanager_options = $racketmanager->get_options();

// Prevent WordPress update_option('racketmanager') from modifying options in live DB
$prevent_option_mutation = function( $value, $old_value, $option ) {
	return $old_value;
};
add_filter( 'pre_update_option_racketmanager', $prevent_option_mutation, 10, 3 );

class Step2MockWpDb {
	public $original;
	public PDO $pdo;
	public string $prefix = 'wp_';
	public string $racketmanager_invoices = 'wp_racketmanager_invoices';
	public string $racketmanager_tournament_entries = 'wp_racketmanager_tournament_entries';
	public string $racketmanager_tournaments = 'wp_racketmanager_tournaments';
	public string $racketmanager_charges = 'wp_racketmanager_charges';
	public string $racketmanager_table = 'wp_racketmanager_table';
	public string $racketmanager_teams = 'wp_racketmanager_teams';
	public string $racketmanager_team_players = 'wp_racketmanager_team_players';
	public string $racketmanager_team_events = 'wp_racketmanager_team_events';
	public string $racketmanager_matches = 'wp_racketmanager_matches';
	public string $racketmanager_messages = 'wp_racketmanager_messages';
	public string $options = 'wp_options';
	public int $insert_id = 0;
	public int $invoice_insert_count = 0;
	public array $mock_team_counts = array();

	public function __construct( $orig, PDO $pdo ) {
		$this->original = $orig;
		$this->pdo      = $pdo;
	}

	public function __get( $name ) {
		return $this->original->$name;
	}

	public function __set( $name, $val ) {
		$this->original->$name = $val;
	}

	public function __call( $name, $args ) {
		return call_user_func_array( array( $this->original, $name ), $args );
	}

	public function prepare( $query, ...$args ) {
		return call_user_func_array( array( $this->original, 'prepare' ), func_get_args() );
	}

	private function should_intercept( string $target ): bool {
		if ( str_contains( $target, 'racketmanager_leagues' ) ) {
			return false;
		}
		return str_contains( $target, 'racketmanager_invoices' ) ||
		       str_contains( $target, 'racketmanager_tournament_entries' ) ||
		       str_contains( $target, 'racketmanager_team_events' ) ||
		       str_contains( $target, 'racketmanager_matches' ) ||
		       str_contains( $target, 'racketmanager_table' ) ||
		       str_contains( $target, 'options' );
	}

	private function should_intercept_write( string $target ): bool {
		return $this->should_intercept( $target ) ||
		       str_contains( $target, 'racketmanager_table' ) ||
		       str_contains( $target, 'racketmanager_teams' ) ||
		       str_contains( $target, 'racketmanager_team_players' ) ||
		       str_contains( $target, 'racketmanager_team_events' ) ||
		       str_contains( $target, 'racketmanager_matches' ) ||
		       str_contains( $target, 'racketmanager_messages' );
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( ! is_array( $data ) || ! is_array( $where ) ) {
			return false;
		}
		if ( $this->should_intercept_write( $table ) ) {
			$set    = array();
			$params = array();
			foreach ( $data as $col => $val ) {
				$set[]    = "`$col` = ?";
				$params[] = $val;
			}
			$where_clause = array();
			foreach ( $where as $col => $val ) {
				$where_clause[] = "`$col` = ?";
				$params[]       = $val;
			}
			$sql  = "UPDATE `$table` SET " . implode( ', ', $set ) . ' WHERE ' . implode( ' AND ', $where_clause );
			$stmt = $this->pdo->prepare( $sql );
			$stmt->execute( $params );
			return $stmt->rowCount();
		}
		throw new RuntimeException( "Unauthorized DB update during Step 2 mock tests on table: $table" );
	}

	public function insert( $table, $data, $format = null ) {
		if ( ! is_array( $data ) ) {
			return false;
		}
		if ( $this->should_intercept_write( $table ) ) {
			if ( str_contains( $table, 'racketmanager_invoices' ) ) {
				$this->invoice_insert_count++;
			}
			$cols         = array_keys( $data );
			$placeholders = array_fill( 0, count( $cols ), '?' );
			$sql          = "INSERT INTO `$table` (`" . implode( '`, `', $cols ) . '`) VALUES (' . implode( ', ', $placeholders ) . ')';
			$stmt         = $this->pdo->prepare( $sql );
			$stmt->execute( array_values( $data ) );
			$this->insert_id = (int) $this->pdo->lastInsertId();
			return 1;
		}
		throw new RuntimeException( "Unauthorized DB insert during Step 2 mock tests on table: $table" );
	}

	public function replace( $table, $data, $format = null ) {
		if ( ! is_array( $data ) ) {
			return false;
		}
		if ( $this->should_intercept_write( $table ) ) {
			$cols         = array_keys( $data );
			$placeholders = array_fill( 0, count( $cols ), '?' );
			$sql          = "REPLACE INTO `$table` (`" . implode( '`, `', $cols ) . '`) VALUES (' . implode( ', ', $placeholders ) . ')';
			$stmt         = $this->pdo->prepare( $sql );
			$stmt->execute( array_values( $data ) );
			$this->insert_id = (int) $this->pdo->lastInsertId();
			return 1;
		}
		throw new RuntimeException( "Unauthorized DB replace during Step 2 mock tests on table: $table" );
	}

	public function delete( $table, $where, $where_format = null ) {
		if ( ! is_array( $where ) ) {
			return false;
		}
		if ( $this->should_intercept_write( $table ) ) {
			$where_clause = array();
			$params       = array();
			foreach ( $where as $col => $val ) {
				$where_clause[] = "`$col` = ?";
				$params[]       = $val;
			}
			$sql  = "DELETE FROM `$table` WHERE " . implode( ' AND ', $where_clause );
			$stmt = $this->pdo->prepare( $sql );
			$stmt->execute( $params );
			return $stmt->rowCount();
		}
		throw new RuntimeException( "Unauthorized DB delete during Step 2 mock tests on table: $table" );
	}

	public function query( $query ) {
		$trimmed = ltrim( $query );
		if ( preg_match( '/^START TRANSACTION\b/i', $trimmed ) ) {
			if ( ! $this->pdo->inTransaction() ) {
				$this->pdo->beginTransaction();
			}
			return 1;
		}
		if ( preg_match( '/^COMMIT\b/i', $trimmed ) ) {
			if ( $this->pdo->inTransaction() ) {
				$this->pdo->commit();
			}
			return 1;
		}
		if ( preg_match( '/^ROLLBACK\b/i', $trimmed ) ) {
			if ( $this->pdo->inTransaction() ) {
				$this->pdo->rollBack();
			}
			return 1;
		}
		if ( $this->should_intercept_write( $query ) ) {
			$clean = preg_replace( '/\s+FOR\s+UPDATE\b/i', '', $query );
			if ( preg_match( '/^INSERT\s+INTO\s+[`]?wp_racketmanager_invoices[`]?/i', $clean ) ) {
				$this->invoice_insert_count++;
			}
			$stmt  = $this->pdo->prepare( $clean );
			$stmt->execute();
			$this->insert_id = (int) $this->pdo->lastInsertId();
			return $stmt->rowCount();
		}
		if ( preg_match( '/^(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE)\b/i', $trimmed ) ) {
			throw new RuntimeException( "Unauthorized write query during Step 2 mock tests: $query" );
		}
		return $this->original->query( $query );
	}

	public function get_results( $query, $output = OBJECT ) {
		if ( $this->should_intercept( $query ) ) {
			$clean = preg_replace( '/\s+FOR\s+UPDATE\b/i', '', $query );
			$stmt  = $this->pdo->query( $clean );
			return $stmt->fetchAll( PDO::FETCH_OBJ );
		}
		return $this->original->get_results( $query, $output );
	}

	public function get_row( $query, $output = OBJECT ) {
		if ( $this->should_intercept( $query ) ) {
			$clean = preg_replace( '/\s+FOR\s+UPDATE\b/i', '', $query );
			$stmt  = $this->pdo->query( $clean );
			$row   = $stmt->fetch( PDO::FETCH_OBJ );
			return $row ?: null;
		}
		return $this->original->get_row( $query, $output );
	}

	public function get_var( $query ) {
		if ( str_contains( $query, 'COUNT(distinct(`team_id`))' ) ) {
			foreach ( $this->mock_team_counts as $event_id => $count ) {
				if ( str_contains( $query, "event_id` = $event_id" ) || str_contains( $query, "event_id = $event_id" ) ) {
					return $count;
				}
			}
			return 0;
		}
		if ( $this->should_intercept( $query ) ) {
			$clean = preg_replace( '/\s+FOR\s+UPDATE\b/i', '', $query );
			$stmt  = $this->pdo->query( $clean );
			return $stmt->fetchColumn();
		}
		return $this->original->get_var( $query );
	}
}

$mock_wpdb       = new Step2MockWpDb( $original_wpdb, $pdo );
$GLOBALS['wpdb'] = $mock_wpdb;

function reset_step2_data( PDO $pdo, Step2MockWpDb $mock_wpdb ): void {
	$pdo->exec( 'DELETE FROM wp_racketmanager_invoices' );
	$pdo->exec( 'DELETE FROM wp_racketmanager_tournament_entries' );
	$pdo->exec( 'DELETE FROM wp_racketmanager_table' );
	$pdo->exec( 'DELETE FROM wp_racketmanager_teams' );
	$pdo->exec( 'DELETE FROM wp_racketmanager_team_players' );
	$pdo->exec( 'DELETE FROM wp_racketmanager_team_events' );
	$pdo->exec( 'DELETE FROM wp_racketmanager_matches' );
	$pdo->exec( 'DELETE FROM wp_racketmanager_messages' );
	$mock_wpdb->mock_team_counts = array();
	$mock_wpdb->invoice_insert_count = 0;
	wp_cache_flush_group( 'tournament_entries' );
	wp_cache_flush_group( 'invoice' );
	wp_cache_flush_group( 'event_teams' );
	wp_cache_flush_group( 'teams' );
}

try {
	// Setup tournament clone with custom charge (fee_comp = 10.0, fee_event = 15.0)
	$test_tournament         = clone Racketmanager\get_tournament( 2 );
	$test_tournament->id     = 9999;
	$custom_charge           = (object) array(
		'id'              => 1,
		'competition_id'  => 1,
		'season'          => (string) $test_tournament->season,
		'fee_competition' => 10.0,
		'fee_event'       => 15.0,
	);
	$test_tournament->charge = new Racketmanager\Charges( $custom_charge );

	$reconcile_ref = new ReflectionMethod( $test_tournament, 'reconcile_partner_financials' );
	$reconcile_ref->setAccessible( true );

	$singles_event = (object) array(
		'id'   => 1,
		'name' => 'Winter Mixed',
	);

	// -------------------------------------------------------------
	// Test 7: Full refund when partner removed from only event (status 3)
	// -------------------------------------------------------------
	echo "\nTest 7: Full refund reconciliation when partner has no remaining events (status 3)...\n";
	reset_step2_data( $pdo, $mock_wpdb );

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 100, "2026-01-01", "2026-01-15")' );

	$res7 = $reconcile_ref->invoke( $test_tournament, 1, array() );

	assert_equals( 40.0, $res7['amount_paid'], 'Calculated amount_paid is 40.0' );
	assert_equals( 0.0, $res7['new_fee'], 'Calculated new_fee is 0.0 when no events remain' );
	assert_equals( -40.0, $res7['fee_due'], 'fee_due is -40.0' );
	assert_equals( 40.0, $res7['refund_amount'], 'refund_amount is 40.0' );

	$inv7 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv7 ), 'One negative refund invoice created' );
	assert_equals( -40.0, (float) $inv7[0]['amount'], 'Negative refund invoice amount is -40.0' );

	$entry7 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry7['status'], 'Entry status is updated to 3 (withdrawn)' );
	assert_true( (int) $entry7['status'] !== 1 && (int) $entry7['status'] !== 2, 'Entry status is not 1 or 2' );

	// -------------------------------------------------------------
	// Test 8: Partial refund when partner remains in another event (status 2 retained)
	// -------------------------------------------------------------
	echo "\nTest 8: Partial refund reconciliation with remaining event (status 2 retained)...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$mock_wpdb->mock_team_counts[1] = 1; // 1 team in singles event

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 101, "2026-01-01", "2026-01-15")' );

	$res8 = $reconcile_ref->invoke( $test_tournament, 1, array( $singles_event ) );

	assert_equals( 40.0, $res8['amount_paid'], 'Calculated amount_paid is 40.0' );
	assert_equals( 25.0, $res8['new_fee'], 'Calculated new_fee for 1 event is 25.0' );
	assert_equals( -15.0, $res8['fee_due'], 'fee_due is -15.0' );
	assert_equals( 15.0, $res8['refund_amount'], 'refund_amount is 15.0' );

	$inv8 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv8 ), 'One refund invoice created' );
	assert_equals( -15.0, (float) $inv8[0]['amount'], 'Negative refund invoice amount is -15.0' );

	$entry8 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 2, (int) $entry8['status'], 'Entry status is retained at 2 (confirmed)' );
	assert_equals( '25', (string) $entry8['fee'], 'Stored entry fee is updated to 25' );

	// -------------------------------------------------------------
	// Test 9: Remaining status 0 retained (pending partner remains in other event)
	// -------------------------------------------------------------
	echo "\nTest 9: Remaining status 0 retained for pending partner (never set to 1 or prematurely confirmed)...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$mock_wpdb->mock_team_counts[1] = 1;

	// Pending partner (status 0) had an open invoice of 40
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 0, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "open", 40.00, 102, "2026-01-01", "2026-01-15")' );

	$res9 = $reconcile_ref->invoke( $test_tournament, 1, array( $singles_event ) );

	assert_equals( 0.0, $res9['amount_paid'], 'Calculated amount_paid is 0.0' );
	assert_equals( 25.0, $res9['new_fee'], 'new_fee is 25.0' );
	assert_equals( 25.0, $res9['fee_due'], 'fee_due is 25.0' );
	assert_equals( 0.0, $res9['refund_amount'], 'refund_amount is 0.0' );

	$entry9 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 0, (int) $entry9['status'], 'Entry status remains 0 (pending, NOT set to 1 or 2)' );
	assert_equals( '25', (string) $entry9['fee'], 'Stored entry fee is updated to 25' );

	// -------------------------------------------------------------
	// Test 10: Positive balance fee adjustment with existing unpaid open invoice (open 40 -> 25)
	// -------------------------------------------------------------
	echo "\nTest 10: Positive balance fee adjustment with existing unpaid open invoice (open £40 -> £25)...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$mock_wpdb->mock_team_counts[1] = 1;

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (id, charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (50, 1, 1, "open", 40.00, 103, "2026-01-01", "2026-01-15")' );

	$res10 = $reconcile_ref->invoke( $test_tournament, 1, array( $singles_event ) );

	assert_equals( 0.0, $res10['amount_paid'], 'amount_paid is 0.0' );
	assert_equals( 25.0, $res10['new_fee'], 'new_fee is 25.0' );
	assert_equals( 25.0, $res10['fee_due'], 'fee_due is 25.0' );

	// Old open invoice 50 cancelled, new open invoice of 25.0 created
	$old_inv = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE id = 50' )->fetch( PDO::FETCH_ASSOC );
	assert_false( (bool) $old_inv, 'Prior open invoice for 40.0 was cancelled' );

	$new_invs = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $new_invs ), 'One open invoice remains' );
	assert_equals( 25.0, (float) $new_invs[0]['amount'], 'Reduced open invoice amount is 25.0' );

	$entry10 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 2, (int) $entry10['status'], 'Entry status is retained at 2 (not 1)' );
	assert_equals( '25', (string) $entry10['fee'], 'Stored entry fee is updated to 25' );

	// -------------------------------------------------------------
	// Test 11: Partial paid balance due (paid £10, new fee £25 -> balance due £15)
	// -------------------------------------------------------------
	echo "\nTest 11: Partial paid balance due (paid £10, new fee £25 -> due £15)...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$mock_wpdb->mock_team_counts[1] = 1;

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 10.00, 104, "2026-01-01", "2026-01-15")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (id, charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (51, 1, 1, "open", 30.00, 105, "2026-01-01", "2026-01-15")' );

	$res11 = $reconcile_ref->invoke( $test_tournament, 1, array( $singles_event ) );

	assert_equals( 10.0, $res11['amount_paid'], 'amount_paid is 10.0' );
	assert_equals( 25.0, $res11['new_fee'], 'new_fee is 25.0' );
	assert_equals( 15.0, $res11['fee_due'], 'fee_due is 15.0' );
	assert_equals( 0.0, $res11['refund_amount'], 'refund_amount is 0.0' );

	$old_open11 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE id = 51' )->fetch( PDO::FETCH_ASSOC );
	assert_false( (bool) $old_open11, 'Prior open invoice for 30.0 was cancelled' );

	$open11 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND status != "paid"' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $open11 ), 'New open invoice created for remaining balance' );
	assert_equals( 15.0, (float) $open11[0]['amount'], 'Open invoice amount is 15.0' );

	$entry11 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 2, (int) $entry11['status'], 'Entry status is retained at 2' );
	assert_equals( '25', (string) $entry11['fee'], 'Stored entry fee is updated to 25' );

	// -------------------------------------------------------------
	// Test 12: Settled negative refund netting
	// -------------------------------------------------------------
	echo "\nTest 12: Settled negative refund netting (initial 40, settled -15 -> net paid 25; refund 25, not 40)...\n";
	reset_step2_data( $pdo, $mock_wpdb );

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "25.00")' );
	// Paid initial 40.00
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 106, "2026-01-01", "2026-01-15")' );
	// Settled prior negative refund of -15.00
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", -15.00, 107, "2026-01-10", "2026-01-15")' );

	// Now partner is removed from remaining event too (0 events remain)
	$res12 = $reconcile_ref->invoke( $test_tournament, 1, array() );

	assert_equals( 25.0, $res12['amount_paid'], 'Netted amount_paid is 25.0 (40 - 15)' );
	assert_equals( 0.0, $res12['new_fee'], 'new_fee is 0.0' );
	assert_equals( -25.0, $res12['fee_due'], 'fee_due is -25.0 (not -40.0)' );
	assert_equals( 25.0, $res12['refund_amount'], 'refund_amount is 25.0' );

	$new_refund12 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND status != "paid"' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $new_refund12 ), 'New refund invoice created for net remaining balance' );
	assert_equals( -25.0, (float) $new_refund12[0]['amount'], 'New refund invoice amount is -25.0' );

	$entry12 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry12['status'], 'Entry status is updated to 3 (withdrawn)' );

	// -------------------------------------------------------------
	// Test 13: Unbilled pending partner (status 0, no prior invoices)
	// -------------------------------------------------------------
	echo "\nTest 13: Unbilled pending partner (no prior invoices -> no invoice created)...\n";
	// Subcase 13a: No remaining events
	reset_step2_data( $pdo, $mock_wpdb );
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 0, "40.00")' );

	$res13a = $reconcile_ref->invoke( $test_tournament, 1, array() );

	assert_equals( 0.0, $res13a['amount_paid'], 'amount_paid is 0.0' );
	assert_equals( 0.0, $res13a['new_fee'], 'new_fee is 0.0' );
	assert_equals( 0.0, $res13a['fee_due'], 'fee_due is 0.0' );
	assert_equals( 0.0, $res13a['refund_amount'], 'refund_amount is 0.0' );

	$inv13a = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 0, count( $inv13a ), 'No invoices created for unbilled pending partner with no events' );

	$entry13a = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry13a['status'], 'Entry status updated to 3 (withdrawn) when no events remain' );

	// Subcase 13b: Remaining events exist
	reset_step2_data( $pdo, $mock_wpdb );
	$mock_wpdb->mock_team_counts[1] = 1;
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 0, "40.00")' );

	$res13b = $reconcile_ref->invoke( $test_tournament, 1, array( $singles_event ) );

	assert_equals( 0.0, $res13b['amount_paid'], 'amount_paid is 0.0' );
	assert_equals( 25.0, $res13b['new_fee'], 'new_fee is 25.0' );
	assert_equals( 25.0, $res13b['fee_due'], 'fee_due is 25.0' );
	assert_equals( 0.0, $res13b['refund_amount'], 'refund_amount is 0.0' );

	$inv13b = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 0, count( $inv13b ), 'No invoice created for unbilled pending partner when remaining events exist' );

	$entry13b = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 0, (int) $entry13b['status'], 'Entry status remains 0 (pending)' );
	assert_equals( '25', (string) $entry13b['fee'], 'Stored fee updated to 25' );

	// -------------------------------------------------------------
	// Test 14: 2-decimal rounding precision with genuinely noisy float amounts
	// -------------------------------------------------------------
	echo "\nTest 14: 2-decimal rounding precision prevents spurious sub-cent invoices with noisy floats...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$mock_wpdb->mock_team_counts[1] = 1;

	// In IEEE 754 floating point arithmetic, 0.1 + 0.2 evaluates to 0.30000000000000004.
	// When subtracted from new fee 0.30, the unrounded difference is -0.00000000000000004.
	// Without round( $fee_due, 2 ), this float noise would falsely trigger a refund invoice.
	// 2-decimal rounding guarantees fee_due is exact 0.0 and no invoice is created.

	$custom_charge_noisy = (object) array(
		'id'              => 1,
		'competition_id'  => 1,
		'season'          => '2026',
		'status'          => 'active',
		'date'            => '2026-01-01',
		'fee_competition' => 0.15,
		'fee_event'       => 0.15,
	);
	$test_tournament->charge = new Racketmanager\Charges( $custom_charge_noisy );

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "0.30")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 0.1, 108, "2026-01-01", "2026-01-15")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 0.2, 109, "2026-01-02", "2026-01-15")' );

	$res14 = $reconcile_ref->invoke( $test_tournament, 1, array( $singles_event ) );

	assert_equals( 0.0, $res14['fee_due'], 'fee_due rounded to exact 0.0 despite IEEE 754 float noise' );
	assert_equals( 0.0, $res14['refund_amount'], 'refund_amount rounded to exact 0.0' );

	$new_inv14 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND status != "paid"' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 0, count( $new_inv14 ), 'No spurious invoice created when noisy float balance rounds to 0.0' );

	// Restore standard charge fixture after Test 14
	$test_tournament->charge = new Racketmanager\Charges( $custom_charge );

	// -------------------------------------------------------------
	// Test 15: Tournament::get_entries(['status' => 'refund']) for status 2 and status 3
	// -------------------------------------------------------------
	echo "\nTest 15: Tournament::get_entries(['status' => 'refund']) queries status IN (2, 3)...\n";
	reset_step2_data( $pdo, $mock_wpdb );

	// Entry A: status 2 (confirmed), unpaid negative invoice -> MATCH
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (id, tournament_id, player_id, status) VALUES (101, 9999, 1, 2)' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount) VALUES (1, 1, "open", -15.00)' );

	// Entry B: status 3 (withdrawn), unpaid negative invoice -> MATCH
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (id, tournament_id, player_id, status) VALUES (102, 9999, 2, 3)' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount) VALUES (1, 2, "open", -40.00)' );

	// Entry C: status 0 (pending), unpaid negative invoice -> NO MATCH (status not 2 or 3)
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (id, tournament_id, player_id, status) VALUES (103, 9999, 3, 0)' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount) VALUES (1, 3, "open", -10.00)' );

	// Entry D: status 2 (confirmed), paid negative invoice -> NO MATCH (status == "paid")
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (id, tournament_id, player_id, status) VALUES (104, 9999, 4, 2)' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount) VALUES (1, 4, "paid", -20.00)' );

	// Entry E: status 2 (confirmed), unpaid positive invoice -> NO MATCH (amount > 0)
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (id, tournament_id, player_id, status) VALUES (105, 9999, 5, 2)' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount) VALUES (1, 5, "open", 25.00)' );

	// Entry F: status 3 (withdrawn), no invoices -> NO MATCH
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (id, tournament_id, player_id, status) VALUES (106, 9999, 6, 3)' );

	$refund_entries = $test_tournament->get_entries( array( 'status' => 'refund' ) );
	assert_equals( 2, count( $refund_entries ), 'get_entries(status => refund) returns exactly 2 entries' );

	$returned_statuses = array();
	$returned_players  = array();
	foreach ( $refund_entries as $re ) {
		$returned_statuses[] = (int) $re->status;
		$returned_players[]  = (int) $re->id;
	}
	sort( $returned_statuses );
	sort( $returned_players );

	assert_equals( array( 2, 3 ), $returned_statuses, 'Refund entries contain both confirmed (status 2) and withdrawn (status 3)' );
	assert_equals( array( 1, 2 ), $returned_players, 'Refund entries contain player 1 and player 2' );

	$refund_count = $test_tournament->get_entries(
		array(
			'status' => 'refund',
			'count'  => true,
		)
	);
	assert_equals( 2, (int) $refund_count, 'get_entries(status => refund, count => true) returns 2' );

	// -------------------------------------------------------------
	// Test 16: Verify live database options and billing counter are completely untouched (Step 2 checkpoint)
	// -------------------------------------------------------------
	echo "\nTest 16: Asserting live MySQL options and invoice counter remained completely unchanged after Step 2...\n";
	$live_options_raw_mid = $original_wpdb->get_var( "SELECT option_value FROM $original_wpdb->options WHERE option_name = 'racketmanager'" );
	$live_options_mid     = maybe_unserialize( $live_options_raw_mid );
	$live_invoice_num_mid = isset( $live_options_mid['billing']['invoiceNumber'] ) ? (int) $live_options_mid['billing']['invoiceNumber'] : null;

	assert_equals( $live_invoice_num_before, $live_invoice_num_mid, 'Live invoiceNumber option is completely unchanged in MySQL' );
	assert_equals( $live_options_raw_before, $live_options_raw_mid, 'Full live racketmanager option is completely unchanged in MySQL' );

	echo "\nAll Step 2 financial reconciliation and refund reporting tests passed successfully!\n";

	// =============================================================
	// STEP 3: Partner Removal Notification, Cache Invalidation, and Trigger Wiring
	// =============================================================
	echo "\n=== Running Step 3: Partner Removal Notification, Cache Invalidation, & Trigger Wiring Tests ===\n\n";

	$sent_emails  = array();
	$email_filter = function( $null, $atts ) use ( &$sent_emails ) {
		$sent_emails[] = $atts;
		return true; // prevent actual email transmission
	};
	add_filter( 'pre_wp_mail', $email_filter, 10, 2 );

	$notify_ref = new ReflectionMethod( $test_tournament, 'notify_partner_removal' );
	$notify_ref->setAccessible( true );

	$partner_id_ref = new ReflectionMethod( $test_tournament, 'get_team_partner_id' );
	$partner_id_ref->setAccessible( true );

	$withdraw_event_ref = new ReflectionMethod( $test_tournament, 'withdraw_from_event' );
	$withdraw_event_ref->setAccessible( true );

	// Shield user meta updates during test execution
	add_filter( 'update_user_metadata', '__return_true' );

	// Setup mock players in cache
	$partner_1 = new Racketmanager\Player(
		(object) array(
			'ID'              => 1,
			'display_name'    => 'Alice Partner',
			'user_email'      => 'alice@example.com',
			'user_registered' => '2026-01-01 00:00:00',
		)
	);
	$partner_1->opt_ins   = array( '1' );
	$partner_1->btm       = 0;
	$partner_1->contactno = '';

	$player_2 = new Racketmanager\Player(
		(object) array(
			'ID'              => 2,
			'display_name'    => 'Bob Remover',
			'user_email'      => 'bob@example.com',
			'user_registered' => '2026-01-01 00:00:00',
		)
	);
	$player_2->opt_ins   = array( '1' );
	$player_2->btm       = 0;
	$player_2->contactno = '';

	$partner_3 = new Racketmanager\Player(
		(object) array(
			'ID'              => 3,
			'display_name'    => 'Charlie Swap',
			'user_email'      => 'charlie@example.com',
			'user_registered' => '2026-01-01 00:00:00',
		)
	);
	$partner_3->opt_ins   = array( '1' );
	$partner_3->btm       = 0;
	$partner_3->contactno = '';

	wp_cache_set( 1, $partner_1, 'players' );
	wp_cache_set( 2, $player_2, 'players' );
	wp_cache_set( 3, $partner_3, 'players' );

	$mock_club = new Racketmanager\Club(
		(object) array(
			'id'        => 1,
			'name'      => 'Test Tennis Club',
			'shortcode' => 'TTC',
		)
	);
	wp_cache_set( 1, $mock_club, 'clubs' );

	// -------------------------------------------------------------
	// Test 17: notify_partner_removal() sends removal email and creates refund invoice
	// -------------------------------------------------------------
	echo "Test 17: notify_partner_removal() sends removal email and creates refund invoice (no remaining events)...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$test_tournament->is_open = true;

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 201, "2026-01-01", "2026-01-15")' );

	$res17 = $notify_ref->invoke( $test_tournament, 1, 2, array( "Men's Doubles" ) );
	assert_true( $res17, 'notify_partner_removal returns true on successful dispatch' );
	assert_equals( 1, count( $sent_emails ), 'Exactly one email dispatched' );
	assert_true( str_contains( $sent_emails[0]['to'], 'alice@example.com' ), 'Recipient is partner email' );
	assert_true( str_contains( $sent_emails[0]['subject'], 'Tournament Partner Removal' ), 'Email subject contains Tournament Partner Removal' );
	assert_true( str_contains( $sent_emails[0]['message'], 'Alice Partner' ), 'Email body addresses partner' );
	assert_true( str_contains( $sent_emails[0]['message'], 'Bob Remover' ), 'Email body names removing player' );
	assert_true( str_contains( $sent_emails[0]['message'], 'Men&#039;s Doubles' ), 'Email body contains event name' );
	assert_true( str_contains( $sent_emails[0]['message'], 'A refund of ' . get_currency_amount( '40.00' ) ), 'Email body contains refund notice for 40.00' );
	assert_true( str_contains( $sent_emails[0]['message'], 'withdrawn from the' ), 'Email body contains withdrawal statement' );

	$inv17 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv17 ), 'One negative refund invoice created in DB' );
	assert_equals( -40.0, (float) $inv17[0]['amount'], 'Refund invoice amount is -40.0' );

	$entry17 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry17['status'], 'Entry status set to 3 (withdrawn)' );

	// -------------------------------------------------------------
	// Test 18: notify_partner_removal() with remaining events retains status 2
	// -------------------------------------------------------------
	echo "\nTest 18: notify_partner_removal() with remaining events retains status 2 and notes retention in email...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$mock_wpdb->mock_team_counts[1] = 1;
	$sent_emails = array();

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 202, "2026-01-01", "2026-01-15")' );

	$remaining_team = (object) array(
		'id'      => 901,
		'players' => array( (object) array( 'id' => 1, 'display_name' => 'Alice Partner', 'fullname' => 'Alice Partner' ) ),
	);
	$mock_event_with_team = new class( 1, 'Men\'s Singles', array( $remaining_team ) ) extends Racketmanager\Event {
		public array $mock_teams = array();
		public function __construct( int $id, string $name, array $teams = array() ) {
			$this->id         = $id;
			$this->name       = $name;
			$this->type       = 'MS';
			$this->mock_teams = $teams;
		}
		public function get_teams( array $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return count( $this->mock_teams );
			}
			return $this->mock_teams;
		}
		public function get_players( array|string $args = array() ): array|int {
			return 1;
		}
	};
	$mock_comp = new class( (object) array( 'id' => 1, 'name' => 'Test Comp', 'type' => 'T', 'seasons' => array() ), $mock_event_with_team ) extends Racketmanager\Competition {
		public $event;
		public function __construct( $comp_data, $e ) {
			parent::__construct( $comp_data );
			$this->event = $e;
		}
		public function get_events( array $args = array() ): array { return array( $this->event ); }
	};
	$orig_comp = $test_tournament->competition;
	$test_tournament->competition = $mock_comp;
	wp_cache_set( 1, $mock_comp, 'competitions' );
	wp_cache_set( 1, $mock_event_with_team, 'events' );

	$res18 = $notify_ref->invoke( $test_tournament, 1, 2, array( "Men's Doubles" ) );
	assert_true( $res18, 'notify_partner_removal returns true' );
	assert_equals( 1, count( $sent_emails ), 'One email dispatched' );
	assert_true( str_contains( $sent_emails[0]['message'], 'Your entries for other events in this tournament remain active.' ), 'Email contains active retention notice' );
	assert_true( str_contains( $sent_emails[0]['message'], 'A refund of ' . get_currency_amount( '15.00' ) ), 'Email contains refund notice for 15.00' );

	$entry18 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 2, (int) $entry18['status'], 'Entry status retained at 2 (confirmed)' );
	assert_equals( '25', (string) $entry18['fee'], 'Entry fee updated to 25' );

	$test_tournament->competition = $orig_comp;
	wp_cache_delete( 1, 'competitions' );
	wp_cache_delete( 1, 'events' );

	// -------------------------------------------------------------
	// Test 19: Missing partner email and missing removing player graceful handling
	// -------------------------------------------------------------
	echo "\nTest 19: Missing partner email or player graceful handling (financials safe, returns false, no email)...\n";
	// Subcase 19a: Partner has empty email
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$partner_no_email = new Racketmanager\Player(
		(object) array(
			'ID'              => 4,
			'display_name'    => 'No Email Partner',
			'user_email'      => '',
			'user_registered' => '2026-01-01 00:00:00',
		)
	);
	$partner_no_email->opt_ins   = array( '1' );
	$partner_no_email->btm       = 0;
	$partner_no_email->contactno = '';
	wp_cache_set( 4, $partner_no_email, 'players' );

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 4, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 4, "paid", 40.00, 203, "2026-01-01", "2026-01-15")' );

	$res19a = $notify_ref->invoke( $test_tournament, 4, 2, array( "Men's Doubles" ) );
	assert_false( $res19a, 'Returns false when partner has no email' );
	assert_equals( 0, count( $sent_emails ), 'Zero emails sent when partner email is missing' );

	$inv19a = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 4 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv19a ), 'Refund invoice still created safely for partner without email' );
	$entry19a = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 4' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry19a['status'], 'Entry status still updated to 3 (withdrawn) for partner without email' );

	// Subcase 19b: Removing player ID does not exist
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 204, "2026-01-01", "2026-01-15")' );

	$res19b = $notify_ref->invoke( $test_tournament, 1, 999999, array( "Men's Doubles" ) );
	assert_false( $res19b, 'Returns false when removing player cannot be loaded' );
	assert_equals( 0, count( $sent_emails ), 'Zero emails sent when player cannot be loaded' );
	$entry19b = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry19b['status'], 'Entry status still updated to 3 when remover is missing' );

	// -------------------------------------------------------------
	// Test 20: Multi-event batching in notify_partner_removal
	// -------------------------------------------------------------
	echo "\nTest 20: Multi-event batching sends single email listing multiple events...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 205, "2026-01-01", "2026-01-15")' );

	$res20 = $notify_ref->invoke( $test_tournament, 1, 2, array( "Men's Doubles", "Mixed Doubles" ) );
	assert_true( $res20, 'notify_partner_removal returns true for multiple events' );
	assert_equals( 1, count( $sent_emails ), 'Exactly one email sent for multiple events' );
	assert_true( str_contains( $sent_emails[0]['message'], "Men&#039;s Doubles, Mixed Doubles" ), 'Email lists both events separated by commas' );
	$inv20 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv20 ), 'Single refund invoice created for batched partner removal' );

	// -------------------------------------------------------------
	// Test 21: Cache invalidation verification via trigger
	// -------------------------------------------------------------
	echo "\nTest 21: Cache groups event_teams and teams are flushed upon partner removal...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$test_tournament->is_open = true;
	$test_tournament->is_closed = false;
	$test_tournament->is_withdrawal = false;
	$test_tournament->is_started = false;
	$test_tournament->is_complete = false;

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 204, "2026-01-01", "2026-01-15")' );

	$deleted_t21_teams = array();
	$mock_league_21 = new class( $deleted_t21_teams ) extends Racketmanager\League {
		public $del_ref;
		public int $id = 301;
		public string $type = 'MD';
		public function __construct( &$d ) { $this->del_ref = &$d; }
		public function delete_team( int $team, string $season ): void {
			$this->del_ref[] = $team;
		}
	};
	wp_cache_set( 301, $mock_league_21, 'leagues' );

	$t21_team = (object) array(
		'team_id' => 501,
		'id'      => 501,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover', 'fullname' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner', 'fullname' => 'Alice Partner' ),
		),
	);

	$mock_event_21 = new class( $t21_team ) extends Racketmanager\Event {
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array {
			$cached = wp_cache_get( 'stale_event_teams_data_21', 'event_teams' );
			if ( false !== $cached ) {
				return array( $this->team ); // Stale data returned if cache was NOT flushed
			}
			return array(); // Fresh state after deletion
		}
	};
	wp_cache_set( 201, $mock_event_21, 'events' );

	// Seed stale cache in event_teams and teams before deletion
	wp_cache_set( 'test_stale_event_teams', 'stale_val1', 'event_teams' );
	wp_cache_set( 'test_stale_teams', 'stale_val2', 'teams' );
	wp_cache_set( 'stale_event_teams_data_21', 'stale_team_marker', 'event_teams' );
	assert_equals( 'stale_val1', wp_cache_get( 'test_stale_event_teams', 'event_teams' ), 'Stale cache entry exists in event_teams' );
	assert_equals( 'stale_val2', wp_cache_get( 'test_stale_teams', 'teams' ), 'Stale cache entry exists in teams' );
	assert_equals( 'stale_team_marker', wp_cache_get( 'stale_event_teams_data_21', 'event_teams' ), 'Stale team data cached in event_teams' );

	$entry_desel_21 = (object) array(
		'player_id'    => 2,
		'btm'          => 0,
		'contactno'    => '',
		'contactemail' => 'bob@example.com',
		'club_id'      => 1,
		'all_events'   => array( 201 ),
		'events'       => array(), // Deselect
		'partners'     => array(),
		'fee'          => 0,
		'paid'         => 0,
		'comments'     => '',
	);

	// Run trigger: set_player_entry deselection
	$test_tournament->set_player_entry( $entry_desel_21 );

	// Assert that cache groups event_teams and teams were flushed by trigger
	assert_false( wp_cache_get( 'test_stale_event_teams', 'event_teams' ), 'event_teams cache group was flushed by trigger' );
	assert_false( wp_cache_get( 'test_stale_teams', 'teams' ), 'teams cache group was flushed by trigger' );
	assert_false( wp_cache_get( 'stale_event_teams_data_21', 'event_teams' ), 'Stale team data was flushed from event_teams' );

	$entry21 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry21['status'], 'Partner status 3 confirms fresh post-deletion data was read' );

	// -------------------------------------------------------------
	// Test 22: Helper get_team_partner_id()
	// -------------------------------------------------------------
	echo "\nTest 22: get_team_partner_id() extracts partner across multiple team player representations...\n";
	// Case 22a: players array of player objects
	$team_obj_players = (object) array(
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice' ),
		),
	);
	assert_equals( 1, $partner_id_ref->invoke( $test_tournament, $team_obj_players, 2 ), 'Extracts partner 1 from players array when player is 2' );
	assert_equals( 2, $partner_id_ref->invoke( $test_tournament, $team_obj_players, 1 ), 'Extracts player 2 from players array when partner is 1' );

	// Case 22b: player_id array of ints
	$team_player_id_arr = (object) array(
		'player_id' => array( 2, 5 ),
	);
	assert_equals( 5, $partner_id_ref->invoke( $test_tournament, $team_player_id_arr, 2 ), 'Extracts partner 5 from player_id array' );
	assert_equals( 2, $partner_id_ref->invoke( $test_tournament, $team_player_id_arr, 5 ), 'Extracts player 2 from player_id array' );

	// Case 22c: player_1_id and player_2_id properties
	$team_player_ids = (object) array(
		'player_1_id' => 2,
		'player_2_id' => 7,
	);
	assert_equals( 7, $partner_id_ref->invoke( $test_tournament, $team_player_ids, 2 ), 'Extracts partner 7 from player_2_id' );
	assert_equals( 2, $partner_id_ref->invoke( $test_tournament, $team_player_ids, 7 ), 'Extracts partner 2 from player_1_id' );

	// -------------------------------------------------------------
	// Test 23: Trigger 1 - Open-phase partner replacement in set_player_entry()
	// -------------------------------------------------------------
	echo "\nTest 23: Trigger 1 - Open-phase partner replacement in set_player_entry()...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$test_tournament->is_open = true;
	$test_tournament->is_closed = false;
	$test_tournament->is_withdrawal = false;
	$test_tournament->is_started = false;
	$test_tournament->is_complete = false;

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 206, "2026-01-01", "2026-01-15")' );

	$deleted_team_ids = array();
	$mock_league_201 = new class( $deleted_team_ids ) extends Racketmanager\League {
		public $deleted_ref;
		public int $id = 301;
		public string $type = 'MD';
		public int $event_id = 201;
		public function __construct( &$del ) { $this->deleted_ref = &$del; }
		public function delete_team( int $team, string $season ): void {
			$this->deleted_ref[] = $team;
		}
		public function add_team( int|string $team_id, string $season, ?string $rank = null, ?string $status = null, int|string $profile = 1 ): bool|int { return 999; }
	};
	wp_cache_set( 301, $mock_league_201, 'leagues' );

	$existing_team = (object) array(
		'team_id' => 501,
		'id'      => 501,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover', 'fullname' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner', 'fullname' => 'Alice Partner' ),
		),
	);
	$mock_event_201 = new class( $existing_team ) extends Racketmanager\Event {
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array {
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
	};
	wp_cache_set( 201, $mock_event_201, 'events' );

	$entry_replacement = (object) array(
		'player_id'    => 2,
		'btm'          => 0,
		'contactno'    => '',
		'contactemail' => 'bob@example.com',
		'club_id'      => 1,
		'all_events'   => array( 201 ),
		'events'       => array( 201 ),
		'partners'     => array( 201 => 3 ),
		'fee'          => 0,
		'paid'         => 0,
		'comments'     => '',
	);

	$res23 = $test_tournament->set_player_entry( $entry_replacement );
	assert_true( in_array( 501, $deleted_team_ids ), 'Old team 501 was deleted on partner replacement' );

	$removal_emails = array_filter( $sent_emails, fn($e) => str_contains( $e['subject'], 'Tournament Partner Removal' ) );
	assert_equals( 1, count( $removal_emails ), 'Partner removal email dispatched to old partner' );
	$first_removal_email = array_values( $removal_emails )[0];
	assert_true( str_contains( $first_removal_email['to'], 'alice@example.com' ), 'Removal email sent to Alice Partner' );
	assert_true( str_contains( $first_removal_email['message'], 'A refund of ' . get_currency_amount( '40.00' ) ), 'Removal email contains refund notice' );

	$inv23 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv23 ), 'Refund invoice created for replaced partner' );
	assert_equals( -40.0, (float) $inv23[0]['amount'], 'Refund invoice amount is -40.0' );

	$entry23 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry23['status'], 'Old partner entry status set to 3 (withdrawn)' );

	// -------------------------------------------------------------
	// Test 24: Trigger 2 - Closed-phase admin partner swap in set_player_entry()
	// -------------------------------------------------------------
	echo "\nTest 24: Trigger 2 - Closed-phase admin partner swap in set_player_entry()...\n";
	// Subcase 24a: Successful admin swap
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$test_tournament->is_open = false;
	$test_tournament->is_closed = true;
	$tourn_season = (string) $test_tournament->season;

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 207, "2026-01-01", "2026-01-15")' );
	$pdo->exec( "INSERT INTO wp_racketmanager_table (id, team_id, league_id, season) VALUES (801, 501, 301, '$tourn_season')" );

	$admin_cap_filter = function( $allcaps ) {
		$allcaps['manage_racketmanager'] = true;
		return $allcaps;
	};
	add_filter( 'user_has_cap', $admin_cap_filter );

	$mock_league_team_data = (object) array(
		'table_id'       => 801,
		'id'             => 501,
		'league_id'      => 301,
		'season'         => $tourn_season,
		'title'          => 'Bob / Alice',
		'club_id'        => 1,
		'stadium'        => '',
		'team_type'      => 'T',
		'status'         => '',
		'roster'         => null,
		'custom'         => '',
		'done_matches'   => 0,
		'won_matches'    => 0,
		'draw_matches'   => 0,
		'lost_matches'   => 0,
		'points_plus'    => 0,
		'points_minus'   => 0,
		'points_2_plus'  => 0,
		'points_2_minus' => 0,
		'add_points'     => 0,
		'diff'           => 0,
		'rating'         => 0,
	);
	$mock_league_team_entry = new Racketmanager\League_Team( $mock_league_team_data );
	wp_cache_set( 801, $mock_league_team_entry, 'league-teams' );

	$existing_swap_team = (object) array(
		'team_id'  => 501,
		'id'       => 501,
		'table_id' => 801,
		'players'  => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover', 'fullname' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner', 'fullname' => 'Alice Partner' ),
		),
	);
	$mock_event_201_swap = new class( $existing_swap_team ) extends Racketmanager\Event {
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array {
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
	};
	wp_cache_set( 201, $mock_event_201_swap, 'events' );

	$res24a = $test_tournament->set_player_entry( $entry_replacement );
	$swap_removal_emails = array_filter( $sent_emails, fn($e) => str_contains( $e['subject'], 'Tournament Partner Removal' ) );
	assert_equals( 1, count( $swap_removal_emails ), 'Old partner received removal notification after successful admin swap' );
	$inv24a = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv24a ), 'Refund invoice created for old partner after admin swap' );
	$entry24a = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry24a['status'], 'Old partner set to status 3 after admin swap' );

	// Subcase 24b: Failed admin swap (preserves account integrity: NO notification, NO invoices modified)
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 208, "2026-01-01", "2026-01-15")' );
	$pdo->exec( "INSERT INTO wp_racketmanager_table (id, team_id, league_id, season) VALUES (801, 501, 301, '$tourn_season')" );
	$pdo->exec( "INSERT INTO wp_racketmanager_matches (league_id, season, home_team, away_team, winner_id) VALUES (301, '$tourn_season', '501', '999', 501)" );

	$mock_league_team_fail_data = (object) array(
		'table_id'       => 801,
		'id'             => 501,
		'league_id'      => 301,
		'season'         => $tourn_season,
		'title'          => 'Bob / Alice',
		'club_id'        => 1,
		'stadium'        => '',
		'team_type'      => 'T',
		'status'         => '',
		'roster'         => null,
		'custom'         => '',
		'done_matches'   => 0,
		'won_matches'    => 0,
		'draw_matches'   => 0,
		'lost_matches'   => 0,
		'points_plus'    => 0,
		'points_minus'   => 0,
		'points_2_plus'  => 0,
		'points_2_minus' => 0,
		'add_points'     => 0,
		'diff'           => 0,
		'rating'         => 0,
	);
	$mock_league_team_fail = new Racketmanager\League_Team( $mock_league_team_fail_data );
	wp_cache_set( 801, $mock_league_team_fail, 'league-teams' );

	$res24b = $test_tournament->set_player_entry( $entry_replacement );
	assert_false( $res24b, 'Failed admin swap returns false' );
	$fail_swap_emails = array_filter( $sent_emails, fn($e) => str_contains( $e['subject'], 'Tournament Partner Removal' ) );
	assert_equals( 0, count( $fail_swap_emails ), 'No notification sent to partner when admin swap fails' );
	$inv24b = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 0, count( $inv24b ), 'No refund invoices created when swap fails' );
	$entry24b = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 2, (int) $entry24b['status'], 'Partner status remains untouched (status 2) on failed swap' );

	remove_filter( 'user_has_cap', $admin_cap_filter );

	// Subcase 24c: Two admin swaps in a single submission - Event A succeeds (committed), Event B fails
	echo "\nSubcase 24c: Two admin swaps in closed registration - Event A succeeds (committed) and Event B fails...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$test_tournament->is_open = false;
	$test_tournament->is_closed = true;
	$tourn_season = (string) $test_tournament->season;

	add_filter( 'user_has_cap', $admin_cap_filter );

	$partner_diana = new Racketmanager\Player();
	$partner_diana->id = 4;
	$partner_diana->display_name = 'Diana Partner';
	$partner_diana->fullname = 'Diana Partner';
	$partner_diana->email = 'diana@example.com';
	$partner_diana->btm = 0;
	$partner_diana->contactno = '';
	wp_cache_set( 4, $partner_diana, 'players' );

	$partner_evan = new Racketmanager\Player();
	$partner_evan->id = 5;
	$partner_evan->display_name = 'Evan New';
	$partner_evan->fullname = 'Evan New';
	$partner_evan->email = 'evan@example.com';
	$partner_evan->btm = 0;
	$partner_evan->contactno = '';
	wp_cache_set( 5, $partner_evan, 'players' );

	// Partner 1 (Alice) in Event 201: has paid invoice of £40, status 2
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 301, "2026-01-01", "2026-01-15")' );
	$pdo->exec( "INSERT INTO wp_racketmanager_table (id, team_id, league_id, season) VALUES (801, 501, 301, '$tourn_season')" );

	// Partner 4 (Diana) in Event 202: has paid invoice of £40, status 2
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 4, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 4, "paid", 40.00, 302, "2026-01-01", "2026-01-15")' );
	$pdo->exec( "INSERT INTO wp_racketmanager_table (id, team_id, league_id, season) VALUES (802, 502, 302, '$tourn_season')" );
	// Event 202 has a played match, which causes swap_doubles_partner() to fail
	$pdo->exec( "INSERT INTO wp_racketmanager_matches (league_id, season, home_team, away_team, winner_id) VALUES (302, '$tourn_season', '502', '999', 502)" );

	$mock_lt_801_data = (object) array(
		'table_id'       => 801,
		'id'             => 501,
		'league_id'      => 301,
		'season'         => $tourn_season,
		'title'          => 'Bob / Alice',
		'club_id'        => 1,
		'stadium'        => '',
		'team_type'      => 'T',
		'status'         => '',
		'roster'         => null,
		'custom'         => '',
		'done_matches'   => 0,
		'won_matches'    => 0,
		'draw_matches'   => 0,
		'lost_matches'   => 0,
		'points_plus'    => 0,
		'points_minus'   => 0,
		'points_2_plus'  => 0,
		'points_2_minus' => 0,
		'add_points'     => 0,
		'diff'           => 0,
		'rating'         => 0,
	);
	$mock_lt_801 = new Racketmanager\League_Team( $mock_lt_801_data );
	wp_cache_set( 801, $mock_lt_801, 'league-teams' );

	$mock_lt_802_data = (object) array(
		'table_id'       => 802,
		'id'             => 502,
		'league_id'      => 302,
		'season'         => $tourn_season,
		'title'          => 'Bob / Diana',
		'club_id'        => 1,
		'stadium'        => '',
		'team_type'      => 'T',
		'status'         => '',
		'roster'         => null,
		'custom'         => '',
		'done_matches'   => 0,
		'won_matches'    => 0,
		'draw_matches'   => 0,
		'lost_matches'   => 0,
		'points_plus'    => 0,
		'points_minus'   => 0,
		'points_2_plus'  => 0,
		'points_2_minus' => 0,
		'add_points'     => 0,
		'diff'           => 0,
		'rating'         => 0,
	);
	$mock_lt_802 = new Racketmanager\League_Team( $mock_lt_802_data );
	wp_cache_set( 802, $mock_lt_802, 'league-teams' );

	$mock_league_301_subc = new class extends Racketmanager\League {
		public int $id = 301;
		public string $type = 'MD';
		public int $event_id = 201;
		public function __construct() {}
		public function add_team( int|string $team_id, string $season, ?string $rank = null, ?string $status = null, int|string $profile = 1 ): bool|int { return 999; }
	};
	wp_cache_set( 301, $mock_league_301_subc, 'leagues' );

	$mock_league_302_subc = new class extends Racketmanager\League {
		public int $id = 302;
		public string $type = 'XD';
		public int $event_id = 202;
		public function __construct() {}
		public function add_team( int|string $team_id, string $season, ?string $rank = null, ?string $status = null, int|string $profile = 1 ): bool|int { return 998; }
	};
	wp_cache_set( 302, $mock_league_302_subc, 'leagues' );

	$team_201_swap = (object) array(
		'team_id'  => 501,
		'id'       => 501,
		'table_id' => 801,
		'players'  => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover', 'fullname' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner', 'fullname' => 'Alice Partner' ),
		),
	);
	$mock_event_201_subc = new class( $team_201_swap ) extends Racketmanager\Event {
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array {
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
	};
	wp_cache_set( 201, $mock_event_201_subc, 'events' );

	$team_202_swap = (object) array(
		'team_id'  => 502,
		'id'       => 502,
		'table_id' => 802,
		'players'  => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover', 'fullname' => 'Bob Remover' ),
			(object) array( 'id' => 4, 'display_name' => 'Diana Partner', 'fullname' => 'Diana Partner' ),
		),
	);
	$mock_event_202_subc = new class( $team_202_swap ) extends Racketmanager\Event {
		public int $id = 202;
		public string $name = 'Mixed Doubles';
		public string $type = 'XD';
		public int $primary_league = 302;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array {
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
	};
	wp_cache_set( 202, $mock_event_202_subc, 'events' );

	$entry_ab = (object) array(
		'player_id'    => 2,
		'btm'          => 0,
		'contactno'    => '',
		'contactemail' => 'bob@example.com',
		'club_id'      => 1,
		'all_events'   => array( 201, 202 ),
		'events'       => array( 201, 202 ),
		'partners'     => array( 201 => 3, 202 => 5 ),
		'fee'          => 0,
		'paid'         => 0,
		'comments'     => '',
	);

	$res24c = $test_tournament->set_player_entry( $entry_ab );
	assert_false( $res24c, 'set_player_entry returns false when second swap fails' );
	assert_true( isset( $test_tournament->entry_errors['partner[202]'] ), 'Entry error recorded for failed second swap' );

	// Partner 1 (Alice) from succeeded swap 201 MUST be notified and reconciled
	$removal_emails_alice = array_filter( $sent_emails, fn($e) => str_contains( $e['to'], 'alice@example.com' ) );
	assert_equals( 1, count( $removal_emails_alice ), 'Alice (event A) received partner removal email despite later swap failure' );

	$inv_alice = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv_alice ), 'Refund invoice created for Alice (event A)' );
	assert_equals( -40.0, (float) $inv_alice[0]['amount'], 'Alice refund invoice amount is -40.0' );

	$entry_alice = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry_alice['status'], 'Alice entry status set to 3 (withdrawn)' );

	// Partner 4 (Diana) from failed swap 202 MUST be UNTOUCHED
	$removal_emails_diana = array_filter( $sent_emails, fn($e) => str_contains( $e['to'], 'diana@example.com' ) );
	assert_equals( 0, count( $removal_emails_diana ), 'Diana (event B) received NO removal email on failed swap' );

	$inv_diana = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 4 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 0, count( $inv_diana ), 'Diana (event B) has NO refund invoices created' );

	$entry_diana = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 4' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 2, (int) $entry_diana['status'], 'Diana entry status remains untouched at 2' );

	remove_filter( 'user_has_cap', $admin_cap_filter );

	// -------------------------------------------------------------
	// Test 25: Trigger 3 - Open-phase event deselection in set_player_entry()
	// -------------------------------------------------------------
	echo "\nTest 25: Trigger 3 - Open-phase event deselection in set_player_entry()...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$test_tournament->is_open = true;
	$test_tournament->is_closed = false;

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 209, "2026-01-01", "2026-01-15")' );

	$deleted_desel_teams = array();
	$mock_league_desel = new class( $deleted_desel_teams ) extends Racketmanager\League {
		public $del_ref;
		public int $id = 301;
		public string $type = 'MD';
		public function __construct( &$d ) { $this->del_ref = &$d; }
		public function delete_team( int $team, string $season ): void {
			$this->del_ref[] = $team;
		}
	};
	wp_cache_set( 301, $mock_league_desel, 'leagues' );

	$desel_team = (object) array(
		'team_id' => 502,
		'id'      => 502,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner' ),
		),
	);
	$mock_event_desel = new class( $desel_team ) extends Racketmanager\Event {
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array {
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
	};
	wp_cache_set( 201, $mock_event_desel, 'events' );

	$entry_desel = (object) array(
		'player_id'    => 2,
		'btm'          => 0,
		'contactno'    => '',
		'contactemail' => 'bob@example.com',
		'club_id'      => 1,
		'all_events'   => array( 201 ),
		'events'       => array(), // deselected
		'partners'     => array(),
		'fee'          => 0,
		'paid'         => 0,
		'comments'     => '',
	);

	$res25 = $test_tournament->set_player_entry( $entry_desel );
	assert_true( in_array( 502, $deleted_desel_teams ), 'Team was deleted on event deselection' );

	$desel_removal_emails = array_filter( $sent_emails, fn($e) => str_contains( $e['subject'], 'Tournament Partner Removal' ) );
	assert_equals( 1, count( $desel_removal_emails ), 'Deselected partner received removal notification' );
	$first_desel_email = array_values( $desel_removal_emails )[0];
	assert_true( str_contains( $first_desel_email['to'], 'alice@example.com' ), 'Removal email sent to deselected partner' );

	$inv25 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv25 ), 'Refund invoice created for deselected partner' );
	assert_equals( -40.0, (float) $inv25[0]['amount'], 'Refund invoice amount is -40.0' );

	$entry25 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry25['status'], 'Deselected partner entry status set to 3 (withdrawn)' );

	// Subcase 25b: set_player_entry two-event deselection with same partner (batching & single reconciliation)
	echo "\nSubcase 25b: Two-event deselection with same partner dispatches 1 email and reconciles once...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$test_tournament->is_open = true;
	$test_tournament->is_closed = false;

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 310, "2026-01-01", "2026-01-15")' );

	$deleted_desel_batch_teams = array();
	$mock_league_desel_batch = new class( $deleted_desel_batch_teams ) extends Racketmanager\League {
		public $del_ref;
		public int $id = 301;
		public string $type = 'MD';
		public function __construct( &$d ) { $this->del_ref = &$d; }
		public function delete_team( int $team, string $season ): void {
			$this->del_ref[] = $team;
		}
	};
	wp_cache_set( 301, $mock_league_desel_batch, 'leagues' );

	$team_desel_1 = (object) array(
		'team_id' => 506,
		'id'      => 506,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner' ),
		),
	);
	$mock_event_desel_1 = new class( $team_desel_1 ) extends Racketmanager\Event {
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array {
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
	};
	wp_cache_set( 201, $mock_event_desel_1, 'events' );

	$team_desel_2 = (object) array(
		'team_id' => 507,
		'id'      => 507,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner' ),
		),
	);
	$mock_event_desel_2 = new class( $team_desel_2 ) extends Racketmanager\Event {
		public int $id = 202;
		public string $name = 'Mixed Doubles';
		public string $type = 'XD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array {
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
	};
	wp_cache_set( 202, $mock_event_desel_2, 'events' );

	$entry_desel_both = (object) array(
		'player_id'    => 2,
		'btm'          => 0,
		'contactno'    => '',
		'contactemail' => 'bob@example.com',
		'club_id'      => 1,
		'all_events'   => array( 201, 202 ),
		'events'       => array(), // both deselected
		'partners'     => array(),
		'fee'          => 0,
		'paid'         => 0,
		'comments'     => '',
	);

	$mock_wpdb->invoice_insert_count = 0;
	$res25b = $test_tournament->set_player_entry( $entry_desel_both );
	assert_true( in_array( 506, $deleted_desel_batch_teams ), 'First doubles team deleted' );
	assert_true( in_array( 507, $deleted_desel_batch_teams ), 'Second doubles team deleted' );

	$desel_batch_emails = array_filter( $sent_emails, fn($e) => str_contains( $e['subject'], 'Tournament Partner Removal' ) );
	assert_equals( 1, count( $desel_batch_emails ), 'Partner receives EXACTLY ONE email for two-event deselection' );
	$first_desel_batch_email = array_values( $desel_batch_emails )[0];
	assert_true( str_contains( $first_desel_batch_email['to'], 'alice@example.com' ), 'Email addressed to Alice' );
	assert_true( str_contains( $first_desel_batch_email['message'], "Men&#039;s Doubles, Mixed Doubles" ), 'Email lists both deselected events' );

	$inv25b = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv25b ), 'Refund invoice created for partner' );
	assert_equals( 1, $mock_wpdb->invoice_insert_count, 'Invoice created exactly once during two-event deselection reconciliation' );

	// -------------------------------------------------------------
	// Test 26: Trigger 4 - Tournament withdrawal and multi-event batching in withdraw_from_event()
	// -------------------------------------------------------------
	echo "\nTest 26: Trigger 4 - Tournament withdrawal and multi-event batching in withdraw_from_event()...\n";
	// Subcase 26a: Single doubles event withdrawal
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 210, "2026-01-01", "2026-01-15")' );

	$deleted_withdr_teams = array();
	$mock_league_withdr = new class( $deleted_withdr_teams ) extends Racketmanager\League {
		public $del_ref;
		public int $id = 301;
		public function __construct( &$d ) { $this->del_ref = &$d; }
		public function delete_team( int $team, string $season ): void {
			$this->del_ref[] = $team;
		}
	};
	wp_cache_set( 301, $mock_league_withdr, 'leagues' );

	$withdr_team_single = (object) array(
		'team_id' => 503,
		'id'      => 503,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner' ),
		),
	);
	$mock_event_withdr_single = new class( $withdr_team_single ) extends Racketmanager\Event {
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return 1;
			}
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
		public function get_players( $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return 2;
			}
			return array();
		}
	};
	$mock_comp_withdr_single = new class( (object) array( 'id' => 1, 'name' => 'Test Comp', 'type' => 'T', 'seasons' => array() ), $mock_event_withdr_single ) extends Racketmanager\Competition {
		public $e;
		public function __construct( $comp_data, $event ) {
			parent::__construct( $comp_data );
			$this->e = $event;
		}
		public function get_events( array $args = array() ): array { return array( $this->e ); }
	};
	$test_tournament->competition = $mock_comp_withdr_single;

	$res26a = $withdraw_event_ref->invoke( $test_tournament, 2 );
	assert_true( $res26a, 'withdraw_from_event returns true' );
	assert_true( in_array( 503, $deleted_withdr_teams ), 'Doubles team deleted on withdrawal' );

	$withdr_emails = array_filter( $sent_emails, fn($e) => str_contains( $e['subject'], 'Tournament Partner Removal' ) );
	assert_equals( 1, count( $withdr_emails ), 'Partner received removal notification on player withdrawal' );
	$first_withdr_email = array_values( $withdr_emails )[0];
	assert_true( str_contains( $first_withdr_email['to'], 'alice@example.com' ), 'Email sent to Alice Partner' );

	$inv26a = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv26a ), 'Refund invoice created for partner upon withdrawal' );
	$entry26a = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 3, (int) $entry26a['status'], 'Partner set to status 3 upon withdrawal' );

	// Subcase 26b: Multi-event batching across 2 doubles events with the SAME partner
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$deleted_withdr_teams_batch = array();

	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "paid", 40.00, 211, "2026-01-01", "2026-01-15")' );

	$mock_league_withdr_batch = new class( $deleted_withdr_teams_batch ) extends Racketmanager\League {
		public $del_ref;
		public int $id = 301;
		public function __construct( &$d ) { $this->del_ref = &$d; }
		public function delete_team( int $team, string $season ): void {
			$this->del_ref[] = $team;
		}
	};
	wp_cache_set( 301, $mock_league_withdr_batch, 'leagues' );

	$team_event_1 = (object) array(
		'team_id' => 504,
		'id'      => 504,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner' ),
		),
	);
	$mock_event_1 = new class( $team_event_1 ) extends Racketmanager\Event {
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return 1;
			}
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
		public function get_players( $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return 2;
			}
			return array();
		}
	};
	$team_event_2 = (object) array(
		'team_id' => 505,
		'id'      => 505,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner' ),
		),
	);
	$mock_event_2 = new class( $team_event_2 ) extends Racketmanager\Event {
		public int $id = 202;
		public string $name = 'Mixed Doubles';
		public string $type = 'XD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return 1;
			}
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return array( $this->team );
			}
			return array();
		}
		public function get_players( $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return 2;
			}
			return array();
		}
	};
	$mock_comp_withdr_batch = new class( (object) array( 'id' => 1, 'name' => 'Test Comp', 'type' => 'T', 'seasons' => array() ), $mock_event_1, $mock_event_2 ) extends Racketmanager\Competition {
		public $e1;
		public $e2;
		public function __construct( $comp_data, $e1, $e2 ) {
			parent::__construct( $comp_data );
			$this->e1 = $e1;
			$this->e2 = $e2;
		}
		public function get_events( array $args = array() ): array { return array( $this->e1, $this->e2 ); }
	};
	$test_tournament->competition = $mock_comp_withdr_batch;

	$res26b = $withdraw_event_ref->invoke( $test_tournament, 2 );
	assert_true( $res26b, 'withdraw_from_event returns true for multi-event withdrawal' );
	assert_true( in_array( 504, $deleted_withdr_teams_batch ), 'First doubles team deleted' );
	assert_true( in_array( 505, $deleted_withdr_teams_batch ), 'Second doubles team deleted' );

	$batch_withdr_emails = array_filter( $sent_emails, fn($e) => str_contains( $e['subject'], 'Tournament Partner Removal' ) );
	assert_equals( 1, count( $batch_withdr_emails ), 'Partner receives EXACTLY ONE notification email across multiple events' );
	$batched_email = array_values( $batch_withdr_emails )[0];
	assert_true( str_contains( $batched_email['message'], "Men&#039;s Doubles, Mixed Doubles" ), 'Consolidated email lists both affected events' );

	$inv26b = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND amount < 0' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $inv26b ), 'Partner reconciled once with single refund invoice' );
	assert_equals( 1, $mock_wpdb->invoice_insert_count, 'Invoice created exactly once during multi-event withdrawal reconciliation' );

	// -------------------------------------------------------------
	// Test 27: Positive balance fee adjustment through real trigger (deselection)
	// -------------------------------------------------------------
	echo "\nTest 27: Positive balance fee adjustment through real trigger (fee drops £40 -> £25 due)...\n";
	reset_step2_data( $pdo, $mock_wpdb );
	$sent_emails = array();
	$test_tournament->is_open = true;
	$test_tournament->is_closed = false;
	$tourn_season = (string) $test_tournament->season;

	// Partner 1 has open invoice for £40.00, £0 paid, status 2, fee 40.00
	$pdo->exec( 'INSERT INTO wp_racketmanager_tournament_entries (tournament_id, player_id, status, fee) VALUES (9999, 1, 2, "40.00")' );
	$pdo->exec( 'INSERT INTO wp_racketmanager_invoices (charge_id, player_id, status, amount, invoiceNumber, date, date_due) VALUES (1, 1, "open", 40.00, 212, "2026-01-01", "2026-01-15")' );

	// Doubles team for Event 201: Bob (2) and Alice (1)
	$team_doubles_27 = (object) array(
		'team_id' => 508,
		'id'      => 508,
		'players' => array(
			(object) array( 'id' => 2, 'display_name' => 'Bob Remover' ),
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner' ),
		),
	);

	class Test27MockEvent201 extends Racketmanager\Event {
		public static bool $is_deleted = false;
		public int $id = 201;
		public string $name = 'Men\'s Doubles';
		public string $type = 'MD';
		public int $primary_league = 301;
		public object $team;
		public function __construct( object $t ) { $this->team = $t; }
		public function get_teams( $args = array() ): array|int {
			$cached = wp_cache_get( 'stale_event_201_teams', 'event_teams' );
			if ( false !== $cached ) {
				return ! empty( $args['count'] ) ? 1 : array( $this->team ); // Stale cache if not flushed!
			}
			if ( self::$is_deleted ) {
				return ! empty( $args['count'] ) ? 0 : array();
			}
			if ( isset( $args['player'] ) && (int) $args['player'] === 2 ) {
				return ! empty( $args['count'] ) ? 1 : array( $this->team );
			}
			if ( isset( $args['player'] ) && (int) $args['player'] === 1 ) {
				return ! empty( $args['count'] ) ? 1 : array( $this->team );
			}
			return ! empty( $args['count'] ) ? 0 : array();
		}
		public function get_players( $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return 2;
			}
			return array();
		}
	}
	Test27MockEvent201::$is_deleted = false;
	$mock_event_201_desel = new Test27MockEvent201( $team_doubles_27 );
	wp_cache_set( 201, $mock_event_201_desel, 'events' );

	$deleted_desel_teams_27 = array();
	$mock_league_27 = new class( $deleted_desel_teams_27 ) extends Racketmanager\League {
		public $del_ref;
		public int $id = 301;
		public string $type = 'MD';
		public function __construct( &$d ) {
			$this->del_ref   = &$d;
		}
		public function delete_team( int $team, string $season ): void {
			$this->del_ref[]                  = $team;
			Test27MockEvent201::$is_deleted   = true;
		}
	};
	wp_cache_set( 301, $mock_league_27, 'leagues' );

	// Singles team for Event 101: Alice (1)
	$team_singles_27 = (object) array(
		'team_id' => 509,
		'id'      => 509,
		'players' => array(
			(object) array( 'id' => 1, 'display_name' => 'Alice Partner' ),
		),
	);
	$mock_event_101_singles = new class( $team_singles_27, $tourn_season ) extends Racketmanager\Event {
		public int $id = 101;
		public string $name = 'Winter Singles';
		public string $type = 'MS';
		public int $primary_league = 301;
		public object $team;
		public string $season_val;
		public function __construct( object $t, string $s ) {
			$this->team = $t;
			$this->season_val = $s;
		}
		public function get_teams( $args = array() ): array|int {
			$season_match = ! isset( $args['season'] ) || (string) $args['season'] === $this->season_val;
			$player_match = isset( $args['player'] ) && (int) $args['player'] === 1;
			if ( $season_match && $player_match ) {
				return ! empty( $args['count'] ) ? 1 : array( $this->team );
			}
			return ! empty( $args['count'] ) ? 0 : array();
		}
		public function get_players( $args = array() ): array|int {
			if ( ! empty( $args['count'] ) ) {
				return 1;
			}
			return array();
		}
	};
	wp_cache_set( 101, $mock_event_101_singles, 'events' );

	// Setup competition containing Event 201 (doubles) and Event 101 (singles)
	$mock_comp_27 = new class( (object) array( 'id' => 1, 'name' => 'Test Comp', 'type' => 'T', 'seasons' => array() ), $mock_event_201_desel, $mock_event_101_singles ) extends Racketmanager\Competition {
		public $e1;
		public $e2;
		public function __construct( $comp_data, $e1, $e2 ) {
			parent::__construct( $comp_data );
			$this->e1 = $e1;
			$this->e2 = $e2;
		}
		public function get_events( array $args = array() ): array { return array( $this->e1, $this->e2 ); }
	};
	$test_tournament->competition = $mock_comp_27;
	wp_cache_set( 1, $mock_comp_27, 'competitions' );

	// SEED STALE CACHE in event_teams and teams pre-delete!
	wp_cache_set( 'test_stale_event_teams_27', 'stale_marker1', 'event_teams' );
	wp_cache_set( 'test_stale_teams_27', 'stale_marker2', 'teams' );
	wp_cache_set( 'stale_event_201_teams', array( $team_doubles_27 ), 'event_teams' );
	assert_equals( 'stale_marker1', wp_cache_get( 'test_stale_event_teams_27', 'event_teams' ), 'Stale cache pre-seeded in event_teams' );
	assert_equals( 'stale_marker2', wp_cache_get( 'test_stale_teams_27', 'teams' ), 'Stale cache pre-seeded in teams' );

	$entry_desel_27 = (object) array(
		'player_id'    => 2,
		'btm'          => 0,
		'contactno'    => '',
		'contactemail' => 'bob@example.com',
		'club_id'      => 1,
		'all_events'   => array( 201 ),
		'events'       => array(), // Deselect event 201
		'partners'     => array(),
		'fee'          => 0,
		'paid'         => 0,
		'comments'     => '',
	);

	$mock_wpdb->invoice_insert_count = 0;
	// Execute THROUGH REAL TRIGGER: set_player_entry deselection
	$res27 = $test_tournament->set_player_entry( $entry_desel_27 );

	// Verify that the trigger deleted team 508 and flushed cache groups
	assert_true( in_array( 508, $deleted_desel_teams_27 ), 'Doubles team deleted by trigger' );
	assert_false( wp_cache_get( 'test_stale_event_teams_27', 'event_teams' ), 'event_teams cache group flushed by trigger' );
	assert_false( wp_cache_get( 'test_stale_teams_27', 'teams' ), 'teams cache group flushed by trigger' );
	assert_false( wp_cache_get( 'stale_event_201_teams', 'event_teams' ), 'Stale team cache flushed from event_teams' );

	// Partner 1 received notification email
	$desel_emails_27 = array_filter( $sent_emails, fn($e) => str_contains( $e['subject'], 'Tournament Partner Removal' ) );
	assert_equals( 1, count( $desel_emails_27 ), 'Partner received removal email via trigger' );
	$first_desel_email_27 = array_values( $desel_emails_27 )[0];
	assert_true( str_contains( $first_desel_email_27['message'], 'Your entries for other events in this tournament remain active.' ), 'Email contains active retention notice' );

	// Prior £40 open invoice was replaced by single £25 invoice
	$open_inv27 = $pdo->query( 'SELECT * FROM wp_racketmanager_invoices WHERE charge_id = 1 AND player_id = 1 AND status != "paid"' )->fetchAll( PDO::FETCH_ASSOC );
	assert_equals( 1, count( $open_inv27 ), 'Prior open invoice replaced with a single outstanding invoice' );
	assert_equals( 25.0, (float) $open_inv27[0]['amount'], 'Reduced balance due is 25.0' );
	assert_equals( 1, $mock_wpdb->invoice_insert_count, 'Invoice created exactly once during trigger positive balance reconciliation' );

	// Status 2 preserved and stored fee updated to 25
	$entry27 = $pdo->query( 'SELECT * FROM wp_racketmanager_tournament_entries WHERE tournament_id = 9999 AND player_id = 1' )->fetch( PDO::FETCH_ASSOC );
	assert_equals( 2, (int) $entry27['status'], 'Entry status 2 preserved for partner retaining singles event' );
	assert_equals( '25', (string) $entry27['fee'], 'Stored fee updated to 25 in tournament entries' );

	$test_tournament->competition = $orig_comp;
	wp_cache_delete( 1, 'competitions' );
	wp_cache_delete( 101, 'events' );
	wp_cache_delete( 201, 'events' );

	// -------------------------------------------------------------
	// Test 28: Verify live database options and billing counter are completely untouched
	// -------------------------------------------------------------
	echo "\nTest 28: Asserting live MySQL options and invoice counter remained completely unchanged across all tests...\n";
	$live_options_raw_after = $original_wpdb->get_var( "SELECT option_value FROM $original_wpdb->options WHERE option_name = 'racketmanager'" );
	$live_options_after     = maybe_unserialize( $live_options_raw_after );
	$live_invoice_num_after = isset( $live_options_after['billing']['invoiceNumber'] ) ? (int) $live_options_after['billing']['invoiceNumber'] : null;

	assert_equals( $live_invoice_num_before, $live_invoice_num_after, 'Live invoiceNumber option is completely unchanged in MySQL' );
	assert_equals( $live_options_raw_before, $live_options_raw_after, 'Full live racketmanager option is completely unchanged in MySQL' );

	echo "\nAll Step 3 partner removal notification, cache invalidation, and trigger tests passed successfully!\n";

} finally {
	if ( isset( $email_filter ) ) {
		remove_filter( 'pre_wp_mail', $email_filter, 10 );
	}
	remove_filter( 'pre_update_option_racketmanager', $prevent_option_mutation, 10 );
	$GLOBALS['wpdb'] = $original_wpdb;
	$racketmanager->options = $original_racketmanager_options;
}
