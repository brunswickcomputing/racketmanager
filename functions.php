<?php
/**
 * RacketManager standalone functions
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

/**
 * Send debug code to the Javascript console
 *
 * @param string $data Optional message that will be sent the the error_log before the backtrace.
 */
function debug_to_console( $data ) {
	if ( is_array( $data ) || is_object( $data ) ) {
		if ( is_array( $data ) ) {
			error_log( 'PHP: array' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		} else {
			error_log( 'PHP: object' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		if ( ! wp_doing_ajax() ) {
			echo "<script>console.log('PHP: " . wp_json_encode( $data ) . "');</script>";
		}
		error_log( 'PHP: "' . wp_json_encode( $data ) . '"' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	} else {
		if ( ! wp_doing_ajax() ) {
			echo "<script>console.log( 'PHP: " . esc_html( $data ) . "' ) ;</script>";
		}
		error_log( 'PHP: "' . $data . '"' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * Send the output from a backtrace to the error_log
 *
 * @param string $message Optional message that will be sent the the error_log before the backtrace.
 */
function log_trace( $message = '' ) {
	$trace = debug_backtrace(); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
	if ( $message ) {
		error_log( $message ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
	$caller        = array_shift( $trace );
	$function_name = $caller['function'];
	error_log( sprintf( '%s: Called from %s:%s', $function_name, $caller['file'], $caller['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	foreach ( $trace as $entry_id => $entry ) {
		$entry['file'] = isset( $entry['file'] ) ? $entry['file'] : '-';
		$entry['line'] = isset( $entry['line'] ) ? $entry['line'] : '-';
		if ( empty( $entry['class'] ) ) {
			error_log( sprintf( '%s %3s. %s() %s:%s', $function_name, $entry_id + 1, $entry['function'], $entry['file'], $entry['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		} else {
			error_log( sprintf( '%s %3s. %s->%s() %s:%s', $function_name, $entry_id + 1, $entry['class'], $entry['function'], $entry['file'], $entry['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

/**
 * Sort multi array
 *
 * @param array $array_field array to be sorted.
 * @param array $cols columns to sort by.
 */
function array_msort( $array_field, $cols ) {
	$colarr = array();
	foreach ( $cols as $col => $order ) {
		$colarr[ $col ] = array();
		foreach ( $array_field as $k => $row ) {
			$colarr[ $col ][ '_' . $k ] = strtolower( $row[ $col ] );
		}
	}

	$eval = 'array_multisort(';

	foreach ( $cols as $col => $order ) {
		$eval .= '$colarr[\'' . $col . '\'],' . $order . ',';
	}

	$eval = substr( $eval, 0, -1 ) . ');';
	eval( $eval ); //phpcs:ignore Squiz.PHP.Eval.Discouraged
	$ret = array();
	foreach ( $colarr as $col => $arr ) {
		foreach ( $arr as $k => $v ) {
			$k = substr( $k, 1 );
			if ( ! isset( $ret[ $k ] ) ) {
				$ret[ $k ] = $array_field[ $k ];
			}
			$ret[ $k ][ $col ] = $array_field[ $k ][ $col ];
		}
	}
	return $ret;
}

/**
 * Create SEO friendly string
 *
 * @param string $string_field query string.
 */
function seo_url( $string_field ) {
	// Lower case everything.
	$string_field = strtolower( $string_field );
	// Make alphanumeric (removes all other characters).
	$string_field = preg_replace( '/[^a-z0-9_\s-]/', '', $string_field );
	// Clean up multiple whitespaces.
	$string_field = preg_replace( '/[\s]+/', ' ', $string_field );
	// Convert dash to underscore.
	$string_field = str_replace( '-', '_', $string_field );
	// Convert whitespaces to dash.
	$string_field = preg_replace( '/\s/', '-', $string_field );
	return $string_field;
}
/**
 * Reverses SEO friendly string
 *
 * @param string $string_field query string.
 */
function un_seo_url( $string_field ) {
	// Convert dash to whitespaces.
	$string_field = str_replace( '-', ' ', $string_field );
	// Convert underscore to dash.
	$string_field = str_replace( '_', '-', $string_field );
	// Lower case everything.
	$string_field = strtolower( $string_field );
	return $string_field;
}
/**
 * Create formatted url
 */
function create_new_url_querystring() {
	add_rewrite_tag( '%competition_name%', '(.+?)' );
	add_rewrite_tag( '%competition_type%', '(.+?)' );
	add_rewrite_tag( '%round%', '(.+?)' );
	add_rewrite_tag( '%league_name%', '(.+?)' );
	add_rewrite_tag( '%league_id%', '([^/]*)' );
	add_rewrite_tag( '%match_id%', '([^/]*)' );
	add_rewrite_tag( '%season%', '([0-9]{4})' );
	add_rewrite_tag( '%match_day%', '([0-9]{1,2})' );
	add_rewrite_tag( '%team%', '(.+?)' );
	add_rewrite_tag( '%teamHome%', '(.+?)' );
	add_rewrite_tag( '%teamAway%', '(.+?)' );
	add_rewrite_tag( '%club_name%', '(.+?)' );
	add_rewrite_tag( '%match_date%', '(.+?)' );
	add_rewrite_tag( '%type%', '(.+?)' );
	add_rewrite_tag( '%tournament%', '(.+?)' );
	add_rewrite_tag( '%player_id%', '(.+?)' );
	add_rewrite_tag( '%id%', '(.+?)' );
	add_rewrite_tag( '%days%', '([0-9]{1,3})' );
	add_rewrite_tag( '%event%', '(.+?)' );
	add_rewrite_tag( '%draw%', '(.+?)' );
	add_rewrite_tag( '%tab%', '(.+?)' );
	add_rewrite_tag( '%player%', '(.+?)' );
	add_rewrite_tag( '%leg%', '([0-9]{1})' );
	add_rewrite_tag( '%action%', '(.+?)' );
	add_rewrite_tag( '%competition%', '(.+?)' );
	add_rewrite_tag( '%btm%', '([0-9]+)' );
	add_rewrite_tag( '%invoice%', '([0-9]+)' );
	add_rewrite_tag( '%age_group%', '(.+?)' );
}
add_action( 'init', 'Racketmanager\create_new_url_querystring' );

/**
 * Create calendar download
 */
function racketmanager_download() {
	if ( isset( $_GET['racketmanager_export'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$exporter = new Racketmanager_Exporter();
		if ( 'calendar' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$exporter->calendar();
		} elseif ( 'fixtures' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$exporter->fixtures();
		} elseif ( 'results' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$exporter->results();
		} elseif ( 'standings' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$exporter->standings();
		} elseif ( 'report_results' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$exporter->report_results();
		} else {
			echo esc_html_e( 'Export function not found', 'racketmanager' );
			exit();
		}
	}
}
add_action( 'init', 'Racketmanager\racketmanager_download' );

/**
 * Output and Get SVG.
 * Output and get the SVG markup for an icon in the Racketmanager_SVG_Icons class.
 *
 * @param string $svg_name The name of the icon.
 */
function racketmanager_the_svg( $svg_name ) {
	echo racketmanager_get_svg( $svg_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get information about the SVG icon.
 *
 * @param string $svg_name The name of the icon.
 */
function racketmanager_get_svg( $svg_name ) {

	// Make sure that only our allowed tags and attributes are included.
	$svg = wp_kses(
		Racketmanager_SVG_Icons::get_svg( $svg_name ),
		array(
			'svg'     => array(
				'class'       => true,
				'xmlns'       => true,
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'aria-hidden' => true,
				'role'        => true,
				'focusable'   => true,
			),
			'path'    => array(
				'fill'      => true,
				'fill-rule' => true,
				'd'         => true,
				'transform' => true,
			),
			'polygon' => array(
				'fill'      => true,
				'fill-rule' => true,
				'points'    => true,
				'transform' => true,
				'focusable' => true,
			),
		)
	);

	if ( ! $svg ) {
		return false;
	}
	return $svg;
}

/**
 * Generate match title.
 *
 * @param object $match match object.
 * @return string match title.
 */
function get_match_title( $match ) {
	if ( 0 === $match->winner_id ) {
		$match_title = $match->match_title;
	} else {
		if ( $match->winner_id === $match->home_team ) {
			$home_team = "<span class='winner'>" . $match->teams['home']->title . '</span>';
		} else {
			$home_team = $match->teams['home']->title;
		}
		if ( $match->winner_id === $match->away_team ) {
			$away_team = "<span class='winner'>" . $match->teams['away']->title . '</span>';
		} else {
			$away_team = $match->teams['away']->title;
		}
		$match_title = $home_team . ' - ' . $away_team;
	}
	return $match_title;
}

/**
 * Get current page url
 */
function wp_get_current_url() {
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		return home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	} else {
		return null;
	}
}
/**
 * Get Club object
 *
 * @param int|object|string $club Club ID or club object. Defaults to global $club.
 * @param string            $search_term type of search.
 * @return object club|null
 */
function get_club( $club = null, $search_term = 'id' ) {
	if ( empty( $club ) && isset( $GLOBALS['club'] ) ) {
		$club = $GLOBALS['club'];
	}
	if ( $club instanceof Racketmanager_Club ) {
		$_club = $club;
	} elseif ( is_object( $club ) ) {
		$_club = new Racketmanager_Club( $club );
	} else {
		$_club = Racketmanager_Club::get_instance( $club, $search_term );
	}
	if ( ! $_club ) {
		return null;
	} else {
		return $_club;
	}
}
/**
 * Get Competition object
 *
 * @param int|Competition|null $competition Competition ID or competition object. Defaults to global $competition.
 * @param string               $search_term type of search.
 * @return object competition|null
 */
function get_competition( $competition = null, $search_term = 'id' ) {
	if ( empty( $competition ) && isset( $GLOBALS['competition'] ) ) {
		$competition = $GLOBALS['competition'];
	}

	if ( $competition instanceof Racketmanager_Competition ) {
		$_competition = $competition;
	} elseif ( is_object( $competition ) ) {
		// check if specific sports class exists.
		if ( ! isset( $competition->sport ) ) {
			$competition->sport = '';
		}
		$instance = 'Racketmanager\Racketmanager_Competition_' . ucfirst( $competition->sport );
		if ( class_exists( $instance ) ) {
			$_competition = new $instance( $competition );
		} else {
			$_competition = new Racketmanager_Competition( $competition );
		}
	} else {
		$_competition = Racketmanager_Competition::get_instance( $competition, $search_term );
	}

	if ( ! $_competition ) {
		return null;
	}

	return $_competition;
}
/**
 * Get Event object
 *
 * @param int|Event|null $event Event ID or event object. Defaults to global $event.
 * @param string         $search_term type of search.
 * @return object event|null
 */
function get_event( $event = null, $search_term = 'id' ) {
	if ( empty( $event ) && isset( $GLOBALS['event'] ) ) {
		$event = $GLOBALS['event'];
	}

	if ( $event instanceof Racketmanager_Event ) {
		$_event = $event;
	} elseif ( is_object( $event ) ) {
		// check if specific sports class exists.
		if ( ! isset( $event->sport ) ) {
			$event->sport = '';
		}
		$instance = 'Racketmanager\Racketmanager_Event_' . ucfirst( $event->sport );
		if ( class_exists( $instance ) ) {
			$_event = new $instance( $event );
		} else {
			$_event = new Racketmanager_Event( $event );
		}
	} else {
		$_event = Racketmanager_Event::get_instance( $event, $search_term );
	}

	if ( ! $_event ) {
		return null;
	}

	return $_event;
}
/**
 * Get League object
 *
 * @param int|object|null $league League ID or league object. Defaults to global $league.
 * @return object League|null
 */
function get_league( $league = null ) {
	if ( empty( $league ) && isset( $GLOBALS['league'] ) ) {
		$league = $GLOBALS['league'];
	}
	if ( $league instanceof Racketmanager_League ) {
		$_league = $league;
	} elseif ( is_object( $league ) ) {
		// check if specific sports class exists.
		if ( ! isset( $league->sport ) ) {
			$league->sport = '';
		}
		$instance = 'Racketmanager\Racketmanager_League_' . ucfirst( $league->sport );
		if ( class_exists( $instance ) ) {
			$_league = new $instance( $league );
		} else {
			$_league = new Racketmanager_League( $league );
		}
	} else {
		$_league = Racketmanager_League::get_instance( $league );
	}
	if ( ! $_league ) {
		return null;
	}
	return $_league;
}
/**
 * Get Racketmanager_Match object
 *
 * @param int|null $match Match ID or match object. Defaults to global $match.
 * @return object Racketmanager_Match|null
 */
function get_match( $match = null ) {
	if ( empty( $match ) && isset( $GLOBALS['match'] ) ) {
		$match = $GLOBALS['match'];
	}

	if ( $match instanceof Racketmanager_Match ) {
		$_match = $match;
	} elseif ( is_object( $match ) ) {
		$_match = new Racketmanager_Match( $match );
	} else {
		$_match = Racketmanager_Match::get_instance( $match );
	}

	if ( ! $_match ) {
		return null;
	}

	return $_match;
}
/**
 * Get Racketmanager_Rubber object
 *
 * @param int|null $rubber Rubber ID or rubber object. Defaults to global $rubber.
 * @return object Racketmanager_Rubber|null
 */
function get_rubber( $rubber = null ) {
	if ( empty( $rubber ) && isset( $GLOBALS['rubber'] ) ) {
		$rubber = $GLOBALS['rubber'];
	}

	if ( $rubber instanceof Racketmanager_Rubber ) {
		$_rubber = $rubber;
	} elseif ( is_object( $rubber ) ) {
		$_rubber = new Racketmanager_Rubber( $rubber );
	} else {
		$_rubber = Racketmanager_Rubber::get_instance( $rubber );
	}

	if ( ! $_rubber ) {
		return null;
	}

	return $_rubber;
}
/**
 * Get Tournament object
 *
 * @param int    $tournament Tournament ID or tournament object. Defaults to global $tournament.
 * @param string $search_term search term - defaults to id.
 * @return object tournament|null
 */
function get_tournament( $tournament = null, $search_term = 'id' ) {
	if ( empty( $tournament ) && isset( $GLOBALS['tournament'] ) ) {
		$tournament = $GLOBALS['tournament'];
	}

	if ( $tournament instanceof Racketmanager_Tournament ) {
		$_tournament = $tournament;
	} elseif ( is_object( $tournament ) ) {
		$_tournament = new Racketmanager_Tournament( $tournament );
	} else {
		$_tournament = Racketmanager_Tournament::get_instance( $tournament, $search_term );
	}

	if ( ! $_tournament ) {
		return null;
	}

	return $_tournament;
}
/**
 * Get Tournament Entry object
 *
 * @param int    $tournament_entry tournament entry ID or tournament entry object. Defaults to global $tournament.
 * @param string $search_term search term - defaults to id.
 * @return object tournament|null
 */
function get_tournament_entry( $tournament_entry = null, $search_term = 'id' ) {
	if ( empty( $tournament_entry ) && isset( $GLOBALS['tournament_entry'] ) ) {
		$tournament_entry = $GLOBALS['tournament_entry'];
	}
	if ( $tournament_entry instanceof Racketmanager_Tournament_Entry ) {
		$_tournament_entry = $tournament_entry;
	} elseif ( is_object( $tournament_entry ) ) {
		$_tournament_entry = new Racketmanager_Tournament_Entry( $tournament_entry );
	} else {
		$_tournament_entry = Racketmanager_Tournament_Entry::get_instance( $tournament_entry, $search_term );
	}
	if ( ! $_tournament_entry ) {
		return null;
	}
	return $_tournament_entry;
}
/**
 * Get Team object
 *
 * @param int|object|null $team Team ID or team object. Defaults to global $team.
 * @return object Team|null
 */
function get_team( $team = null ) {
	if ( empty( $team ) && isset( $GLOBALS['team'] ) ) {
		$team = $GLOBALS['team'];
	}

	if ( $team instanceof Racketmanager_Team ) {
		$_team = $team;
	} elseif ( is_object( $team ) ) {
		$_team = new Racketmanager_Team( $team );
	} else {
		$_team = Racketmanager_Team::get_instance( $team );
	}

	if ( ! $_team ) {
		return null;
	}

	return $_team;
}
/**
 * Get Player object
 *
 * @param int    $player Player ID or player object. Defaults to global $player.
 * @param string $search_term search type term (defaults to id).
 * @return object player|null
 */
function get_player( $player = null, $search_term = 'id' ) {
	if ( empty( $player ) && isset( $GLOBALS['player'] ) ) {
		$player = $GLOBALS['player'];
	}
	if ( $player instanceof Racketmanager_Player ) {
		$_player = $player;
	} elseif ( is_object( $player ) ) {
		$_player = new Racketmanager_Player( $player );
	} else {
		$_player = Racketmanager_Player::get_instance( $player, $search_term );
	}
	if ( ! $_player ) {
		return null;
	}
	return $_player;
}
/**
 * Get User object
 *
 * @param int    $user User ID or user object. Defaults to global $user.
 * @param string $search_term search type term (defaults to id).
 * @return object user|null
 */
function get_user( $user = null, $search_term = 'id' ) {
	if ( empty( $user ) && isset( $GLOBALS['user'] ) ) {
		$user = $GLOBALS['user'];
	}
	if ( $user instanceof Racketmanager_User ) {
		$_user = $user;
	} elseif ( is_object( $user ) ) {
		$_user = new Racketmanager_User( $user );
	} else {
		$_user = Racketmanager_User::get_instance( $user, $search_term );
	}
	if ( ! $_user ) {
		return null;
	}
	return $_user;
}

/**
 * Get Racketmanager_Charges object
 *
 * @param int|null $charges Racketmanager_Charges ID or charges object. Defaults to global $charges.
 * @return object charges|null
 */
function get_charge( $charges = null ) {
	if ( empty( $charges ) && isset( $GLOBALS['charges'] ) ) {
		$charges = $GLOBALS['charges'];
	}

	if ( $charges instanceof Racketmanager_Charges ) {
		$_charges = $charges;
	} elseif ( is_object( $charges ) ) {
		$_charges = new Racketmanager_Charges( $charges );
	} else {
		$_charges = Racketmanager_Charges::get_instance( $charges );
	}

	if ( ! $_charges ) {
		return null;
	}

	return $_charges;
}
/**
 * Get invoice object
 *
 * @param int|null $invoice invoice ID or invoice object. Defaults to global $invoice.
 * @return object invoice|null
 */
function get_invoice( $invoice = null ) {
	if ( empty( $invoice ) && isset( $GLOBALS['invoice'] ) ) {
		$invoice = $GLOBALS['invoice'];
	}

	if ( $invoice instanceof Racketmanager_Invoice ) {
		$_invoice = $invoice;
	} elseif ( is_object( $invoice ) ) {
		$_invoice = new Racketmanager_Invoice( $invoice );
	} else {
		$_invoice = Racketmanager_Invoice::get_instance( $invoice );
	}

	if ( ! $_invoice ) {
		return null;
	}

	return $_invoice;
}
/**
 * Get LeagueTeam object
 *
 * @param int|object|null $league_team LeagueTeam ID or leagueteam object. Defaults to global $league_team.
 * @return league_team|null
 */
function get_league_team( $league_team = null ) {
	if ( empty( $league_team ) && isset( $GLOBALS['league_team'] ) ) {
		$league_team = $GLOBALS['league_team'];
	}

	if ( $league_team instanceof Racketmanager_League_Team ) {
		$_league_team = $league_team;
	} elseif ( is_object( $league_team ) ) {
		$_league_team = new Racketmanager_League_Team( $league_team );
	} else {
		$_league_team = Racketmanager_League_Team::get_instance( $league_team );
	}

	if ( ! $_league_team ) {
		return null;
	}

	return $_league_team;
}
/**
 * Get results report object
 *
 * @param int|null $results_report results_report ID or results_report object. Defaults to global $results_report.
 * @return object results_report|null
 */
function get_results_report( $results_report = null ) {
	if ( empty( $results_report ) && isset( $GLOBALS['results_report'] ) ) {
		$results_report = $GLOBALS['results_report'];
	}

	if ( $results_report instanceof Racketmanager_Results_Report ) {
		$_results_report = $results_report;
	} elseif ( is_object( $results_report ) ) {
		$_results_report = new Racketmanager_Results_Report( $results_report );
	} else {
		$_results_report = Racketmanager_Results_Report::get_instance( $results_report );
	}

	if ( ! $_results_report ) {
		return null;
	}

	return $_results_report;
}
/**
 * Get results check object
 *
 * @param int|null $results_check results_check ID or results_check object. Defaults to global $results_check.
 * @return object results_represults_checkort|null
 */
function get_result_check( $results_check = null ) {
	if ( empty( $results_check ) && isset( $GLOBALS['results_check'] ) ) {
		$results_check = $GLOBALS['results_check'];
	}

	if ( $results_check instanceof Racketmanager_Results_Checker ) {
		$_results_check = $results_check;
	} elseif ( is_object( $results_check ) ) {
		$_results_check = new Racketmanager_Results_Checker( $results_check );
	} else {
		$_results_check = Racketmanager_Results_Checker::get_instance( $results_check );
	}

	if ( ! $_results_check ) {
		return null;
	}

	return $_results_check;
}
/**
 * Get message object
 *
 * @param int|null $message message ID or message object. Defaults to global $message.
 * @return object message|null
 */
function get_message( $message = null ) {
	if ( empty( $message ) && isset( $GLOBALS['message'] ) ) {
		$message = $GLOBALS['message'];
	}

	if ( $message instanceof Racketmanager_Message ) {
		$_message = $message;
	} elseif ( is_object( $message ) ) {
		$_message = new Racketmanager_Message( $message );
	} else {
		$_message = Racketmanager_Message::get_instance( $message );
	}

	if ( ! $_message ) {
		return null;
	}

	return $_message;
}
/**
 * Undocumented function
 *
 * @param array  $match_players array of players.
 * @param object $match match details.
 * @return array
 */
function match_add_players( $match_players, $match ) {
	$teams = array( 'home', 'away' );
	foreach ( $teams as $team ) {
		$team = $match->teams[ $team ];
		if ( ! empty( $team->players ) ) {
			foreach ( $team->players as $player ) {
				$match_players[] = $player->ID;
			}
		}
	}
	return $match_players;
}
