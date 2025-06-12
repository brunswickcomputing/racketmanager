<?php
/**
 * Template tags
 *
 * @package Racketmanager
 */

namespace Racketmanager;

	/**
	 * Get specific template
	 *
	 * @param string $template template.
	 *
	 * @return string
	 * @category template-tags
	 */
function get_league_template( string $template = '' ): string {
	global $league;

	if ( ! empty( $template ) && isset( $league->templates[ $template ] ) ) {
		return $league->templates[ $template ];
	}
	return '';
}
	/**
	 * Check if a specific standings columns is activated for display
	 *
	 * @param string $key key.
	 *
	 * @return boolean
	 * @category template-tags
	 */
function show_standings( string $key ): bool {
	global $league;

	if ( isset( $league->standings[ $key ] ) && 1 === $league->standings[ $key ] ) {
		return true;
	}
	return false;
}
	/**
	 * Print Match time
	 *
	 * @category template-tags
	 */
function the_match_time( $start_time ): void {
	if ( '00:00' === $start_time ) {
		echo '';
	} else {
		echo $start_time;
	}
}
/**
 * Get formatted currency function
 *
 * @param string|null $amount amount to be formatted.
 *
 * @return void
 */
function the_currency_amount( ?string $amount ): void {
	if ( is_null( $amount ) ) {
		$amount = 0;
	}
	$currency_fmt  = Racketmanager_Util::get_currency_format();
	$currency_code = Racketmanager_Util::get_currency_code();
	echo esc_html( numfmt_format_currency( $currency_fmt, $amount, $currency_code ) );
}
	/**
	 * Wrapper tags
	 */

	/**
	 * Display one club
	 *
	 * @param int $club_id club.
	 * @param array $args additional arguments as associative array (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_club( int $club_id, array $args = array() ): void {
	$defaults        = array( 'template' => '' );
	$args            = array_merge( $defaults, $args );
	$args['club_id'] = $club_id;

	$shortcode = '[club';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}

/**
 * Display player list
 *
 * @param int|string $league_id league.
 * @param array $args additional arguments as associative array (optional).
 *
 * @category template-tags
 */
function racketmanager_league_players( int|string $league_id, array $args = array() ): void {
	$defaults          = array(
		'season'   => false,
		'template' => '',
		'group'    => false,
	);
	$args              = array_merge( $defaults, $args );
	$args['league_id'] = intval( $league_id );

	$shortcode = '[league-players';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
	/**
	 * Display standings table
	 *
	 * @param int $league_id League ID.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_league_standings( int $league_id, array $args = array() ): void {
	$defaults          = array(
		'season'   => false,
		'template' => 'last5',
		'group'    => false,
		'home'     => 0,
	);
	$args              = array_merge( $defaults, $args );
	$args['league_id'] = $league_id;

	$shortcode = '[standings';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}

	/**
	 * Display crosstable table
	 *
	 * @param int $league_id league.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_league_crosstable( int $league_id, array $args = array() ): void {
	$defaults          = array(
		'season'   => false,
		'group'    => '',
		'template' => '',
		'mode'     => '',
	);
	$args              = array_merge( $defaults, $args );
	$args['league_id'] = $league_id;

	$shortcode = '[crosstable';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}

	/**
	 * Display matches table
	 *
	 * @param int $league_id league.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_league_matches( int $league_id, array $args = array() ): void {
	$defaults          = array(
		'season'                   => '',
		'template'                 => '',
		'mode'                     => '',
		'limit'                    => 'true',
		'match_day'                => 'current',
		'group'                    => false,
		'order'                    => false,
		'show_match_day_selection' => '',
		'time'                     => '',
		'team'                     => 0,
		'home_only'                => 'false',
		'match_date'               => false,
		'dateformat'               => '',
	);
	$args              = array_merge( $defaults, $args );
	$args['league_id'] = $league_id;

	$shortcode = '[matches';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';

	echo do_shortcode( $shortcode );
}

	/**
	 * Display one match
	 *
	 * @param int $match_id match.
	 * @param array $args additional arguments as associative array (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_match( int $match_id, array $args = array() ): void {
	$defaults         = array( 'template' => '' );
	$args             = array_merge( $defaults, $args );
	$args['match_id'] = $match_id;

	$shortcode = '[match';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
	/**
	 * Display tournament match
	 *
	 * @param int $match_id match.
	 * @param array $args additional arguments as associative array (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_tournament_match( int $match_id, array $args = array() ): void {
	$defaults         = array( 'template' => '' );
	$args             = array_merge( $defaults, $args );
	$args['match_id'] = $match_id;

	$shortcode = '[tournament-match';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}

	/**
	 * Display team list
	 *
	 * @param int $league_id league.
	 * @param array $args additional arguments as associative array (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_league_teams( int $league_id, array $args = array() ): void {
	$defaults          = array(
		'season'   => false,
		'template' => '',
		'group'    => false,
	);
	$args              = array_merge( $defaults, $args );
	$args['league_id'] = $league_id;

	$shortcode = '[teams';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
	/**
	 * Display championship manually
	 *
	 * @param int $league_id league.
	 * @param array $args additional arguments as associative array (optional).
	 *
	 * @return void
	 */
function racketmanager_championship( int $league_id, array $args = array() ): void {
	$defaults          = array(
		'template' => '',
		'season'   => false,
	);
	$args              = array_merge( $defaults, $args );
	$args['league_id'] = $league_id;

	$shortcode = '[championship';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}

	/**
	 * Display championship manually
	 *
	 * @param int $league_id league.
	 * @param array $args additional arguments as associative array (optional).
	 *
	 * @return void
	 */
function racketmanager_archive( int $league_id, array $args = array() ): void {
	$defaults          = array( 'template' => '' );
	$args              = array_merge( $defaults, $args );
	$args['league_id'] = $league_id;

	$shortcode = '[leaguearchive';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}

	/**
	 * Display results table
	 *
	 * @param int $club_id affiliated Club id club.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_results( int $club_id, array $args = array() ): void {
	$args['club'] = $club_id;
	$args['days'] = 3;

	$shortcode = '[latest_results';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
	/**
	 * Display match email
	 *
	 * @param int $match_id match id match.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_match_notification( int $match_id, array $args = array() ): string {
	$args['match'] = $match_id;

	$shortcode = '[match-notification';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}

	/**
	 * Display result email
	 *
	 * @param int $match_id match id match.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_result_notification( int $match_id, array $args = array() ): string {
	$args['match'] = $match_id;

	$shortcode = '[result-notification';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}
/**
 * Match date change notification function
 *
 * @param int $match_id match id.
 * @param array $args array of arguments.
 *
 * @return string
 */
function racketmanager_match_date_change_notification( int $match_id, array $args = array() ): string {
	$args['match'] = $match_id;

	$shortcode = '[match_date_change_notification';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}
	/**
	 * Display result email for captain
	 *
	 * @param int $match_id match id match.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_captain_result_notification( int $match_id, array $args = array() ): string {
	$args['match'] = $match_id;

	$shortcode = '[result-notification-captain';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}

	/**
	 * Display result outstanding email for captain
	 *
	 * @param int $match_id match id match.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_result_outstanding_notification( int $match_id, array $args = array() ): string {
	$args['match'] = $match_id;

	$shortcode = '[result-outstanding-notification';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}

	/**
	 * Display club player request email
	 *
	 * @param array $args array of arguments.
	 *
	 * @category template-tags
	 */
function racketmanager_club_players_notification( array $args = array() ): string {
	$shortcode = '[club-player-notification';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}

	/**
	 * Display constitution email
	 *
	 * @param int $event_id event id.
	 * @param array $args associative array of parameters, see default values (optional).
	 *
	 * @category template-tags
	 */
function racketmanager_constitution_notification( int $event_id, array $args = array() ): string {
	$args['id']        = $event_id;
	$args['standings'] = 'constitution';

	$shortcode = '[event-constitution';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}
/**
 * Display tournament overview function
 *
 * @param int $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_overview( int $tournament_id, array $args = array() ): void {
	$args['id'] = $tournament_id;
	$shortcode  = '[tournament-overview';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display tournament events function
 *
 * @param int $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_events( int $tournament_id, array $args = array() ): void {
	$args['id'] = $tournament_id;
	$shortcode  = '[tournament-events';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display tournament draws function
 *
 * @param int $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_draws( int $tournament_id, array $args = array() ): void {
	$args['id'] = $tournament_id;
	$shortcode  = '[tournament-draws';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display tournament players function
 *
 * @param int $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_players( int $tournament_id, array $args = array() ): void {
	$args['id'] = $tournament_id;
	$shortcode  = '[tournament-players';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display tournament matches function
 *
 * @param int $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_matches( int $tournament_id, array $args = array() ): void {
	$args['id'] = $tournament_id;
	$shortcode  = '[tournament-matches';
	foreach ( $args as $key => $value ) {
		if ( 'matches' === $key ) {
			$key = 'match_date';
		}
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display tournament order of play function
 *
 * @param int $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_order_of_play( int $tournament_id, array $args = array() ): void {
	$args['id'] = $tournament_id;
	$shortcode  = '[orderofplay';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display tournament winners function
 *
 * @param int $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_winners( int $tournament_id, array $args = array() ): void {
	$args['id'] = $tournament_id;
	$shortcode  = '[tournament-winners';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display competition overview function
 *
 * @param int $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_overview( int $competition_id, array $args = array() ): void {
	$args['id'] = $competition_id;
	$shortcode  = '[competition-overview';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display competition events function
 *
 * @param int $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_events( int $competition_id, array $args = array() ): void {
	$args['id'] = $competition_id;
	$shortcode  = '[competition-events';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display competition clubs function
 *
 * @param int $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_clubs( int $competition_id, array $args = array() ): void {
	$args['id'] = $competition_id;
	$shortcode  = '[competition-clubs';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display competition teams function
 *
 * @param int $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_teams( int $competition_id, array $args = array() ): void {
	$args['id'] = $competition_id;
	$shortcode  = '[competition-teams';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display competition players function
 *
 * @param int $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_players( int $competition_id, array $args = array() ): void {
	$args['id'] = $competition_id;
	$shortcode  = '[competition-players';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display event standings function
 *
 * @param int $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_standings( int $event_id, array $args = array() ): void {
	$args['id'] = $event_id;
	$shortcode  = '[event-standings';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display event draw function
 *
 * @param int $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_draw( int $event_id, array $args = array() ): void {
	$args['id'] = $event_id;
	$shortcode  = '[event-draw';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display event matches function
 *
 * @param int $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_matches( int $event_id, array $args = array() ): void {
	$args['id'] = $event_id;
	$shortcode  = '[event-matches';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display event clubs function
 *
 * @param int $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_clubs( int $event_id, array $args = array() ): void {
	$args['id'] = $event_id;
	$shortcode  = '[event-clubs';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display event teams function
 *
 * @param int $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_teams( int $event_id, array $args = array() ): void {
	$args['id'] = $event_id;
	$shortcode  = '[event-teams';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display event players function
 *
 * @param int $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_players( int $event_id, array $args = array() ): void {
	$args['id'] = $event_id;
	$shortcode  = '[event-players';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display competition matches function
 *
 * @param int $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_matches( int $competition_id, array $args = array() ): void {
	$args['id'] = $competition_id;
	$shortcode  = '[competition-matches';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display competition winners function
 *
 * @param int $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_winners( int $competition_id, array $args = array() ): void {
	$args['id'] = $competition_id;
	$shortcode  = '[competition-winners';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display player search function
 *
 * @param string $search_string search string.
 * @param array $args array of arguments.
 */
function racketmanager_player_search( string $search_string, array $args = array() ): string {
	$args['search'] = $search_string;
	$shortcode      = '[search-players';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}
/**
 * Display withdrawn team email
 *
 * @param array $args array of arguments.
 */
function racketmanager_withdrawn_team( array $args = array() ): string {
	$shortcode = '[withdrawn-team';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}
	/**
	 * Display match status modal
	 *
	 * @param array $args array of arguments.
	 */
	function racketmanager_match_status_modal( array $args = array() ): string {
		$shortcode = '[match-status';
		foreach ( $args as $key => $value ) {
			$shortcode .= ' ' . $key . "='" . $value . "'";
		}
		$shortcode .= ']';
		return do_shortcode( $shortcode );
	}
	/**
	 * Display rubber status modal
	 *
	 * @param array $args array of arguments.
	 */
	function racketmanager_rubber_status_modal( array $args = array() ): string {
		$shortcode = '[rubber-status';
		foreach ( $args as $key => $value ) {
			$shortcode .= ' ' . $key . "='" . $value . "'";
		}
		$shortcode .= ']';
		return do_shortcode( $shortcode );
	}
	/**
	 * Display match option modal
	 *
	 * @param array $args array of arguments.
	 */
	function racketmanager_match_option_modal( array $args = array() ): string {
		$shortcode = '[match-option';
		foreach ( $args as $key => $value ) {
			$shortcode .= ' ' . $key . "='" . $value . "'";
		}
		$shortcode .= ']';
		return do_shortcode( $shortcode );
	}
