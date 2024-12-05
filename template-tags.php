<?php
/**
 * Template tags
 *
 * @package Racketmanager
 */

namespace Racketmanager;

	/**
	 * Get league ID
	 *
	 * @return int
	 * @category template-tags
	 */
function get_league_id() {
	global $league;
	return $league->id;
}
	/**
	 * Print league ID
	 *
	 * @category template-tags
	 */
function the_league_id() {
	echo get_league_id();
}

	/**
	 * Get league title
	 *
	 * @return string
	 * @category template-tags
	 */
function get_league_title() {
	global $league;
	return $league->title;
}

	/**
	 * Print league title
	 *
	 * @category template-tags
	 */
function the_league_title() {
	echo get_league_title();
}

	/**
	 * Get current season
	 *
	 * @return string
	 * @category template-tags
	 */
function get_current_season() {
	global $league;
	return $league->current_season['name'];
}
	/**
	 * Print current season
	 *
	 * @category template-tags
	 */
function the_current_season() {
	echo get_current_season();
}

	/**
	 * Get current match day
	 *
	 * @return int
	 * @category template-tags
	 */
function get_current_match_day() {
	global $league;
	return $league->match_day;
}

	/**
	 * Get number of match days
	 *
	 * @return int
	 * @category template-tags
	 */
function get_num_match_days() {
	global $league;
	return $league->num_match_days;
}

	/**
	 * Get specific template
	 *
	 * @param string $template template.
	 * @return string
	 * @category template-tags
	 */
function get_league_template( $template = '' ) {
	global $league;

	if ( ! empty( $template ) && isset( $league->templates[ $template ] ) ) {
		return $league->templates[ $template ];
	}
	return '';
}

	/**
	 * Print current match day
	 *
	 * @category template-tags
	 */
function the_current_match_day() {
	echo get_current_match_day();
}

	/**
	 * Check if a specific standings columns is activated for display
	 *
	 * @param string $key key.
	 * @return boolean
	 * @category template-tags
	 */
function show_standings( $key ) {
	global $league;

	if ( isset( $league->standings[ $key ] ) && 1 === $league->standings[ $key ] ) {
		return true;
	}
	return false;
}

	/**
	 * Get League point rule
	 *
	 * @return string
	 * @category template-tags
	 */
function get_league_pointrule() {
	global $league;
	return $league->point_rule;
}

	/**
	 * Get total number of teams
	 *
	 * @return int
	 * @category template-tags
	 */
function get_num_teams_total() {
	global $league;
	return $league->num_teams_total;
}

	/**
	 * Display standings header
	 *
	 * @category template-tags
	 */
function the_standings_header() {
	global $league;
	$league->display_standings_header();
}
	/**
	 * Display standings columns
	 *
	 * @category template-tags
	 */
function the_standings_columns() {
	global $league, $team;
	$league->display_standings_columns( $team, get_league_pointrule() );
}

	/**
	 * Test whether league has teams or we are in the loop
	 *
	 * @return boolean
	 */
function have_teams() {
	global $league;

	if ( $league->current_team + 1 < count( $league->teams ) ) {
		return true;
	} elseif ( count( $league->teams ) - 1 === $league->current_team && count( $league->teams ) > 0 ) {
		// End of Loop.
		$league->current_team = -1;
	}

	$league->in_the_team_loop = false;
	return false;
}
	/**
	 * Loop through teams
	 */
function the_team() {
	global $league, $team;

	$league->in_the_team_loop = true;

	// Increment team count.
	++$league->current_team;
	$team = $league->teams[ $league->current_team ];
}

	/**
	 * Get team ID
	 *
	 * @return int
	 * @category template-tags
	 */
function get_team_id() {
	global $team;
	return $team->id;
}
	/**
	 * Print team ID
	 *
	 * @category template-tags
	 */
function the_team_id() {
	echo get_team_id();
}

	/**
	 * Get team name
	 *
	 * @return string
	 * @category template-tags
	 */
function get_team_name() {
	global $team;
	return $team->title;
}
	/**
	 * Print team name
	 *
	 * @category template-tags
	 */
function the_team_name() {
	echo get_team_name();
}

	/**
	 * Print team CSS class
	 *
	 * @category template-tags
	 */
function the_team_class() {
	global $team;

	echo $team->class;
}

	/**
	 * Get team rank
	 *
	 * @return int
	 * @category template-tags
	 */
function get_team_rank() {
	global $team;
	return $team->rank;
}

	/**
	 * Print team rank
	 *
	 * @category template-tags
	 */
function the_team_rank() {
	echo get_team_rank();
}

	/**
	 * Print team status
	 *
	 * @category template-tags
	 */
function the_team_status() {
	global $team;
	echo esc_html( $team->status );
}
	/**
	 * Print team status text
	 *
	 * @category template-tags
	 */
function the_team_status_text() {
	global $team;
	echo esc_html( $team->status_text );
}

	/**
	 * Print team status icon
	 *
	 * @category template-tags
	 */
function the_team_status_icon() {
	global $team;
	return $team->status_icon;
}

	/**
	 * Print formatted team points
	 *
	 * @param string $ind index.
	 * @category template-tags
	 */
function the_team_points( $ind = 'primary' ) {
	global $team;
	echo $team->points_formatted[ $ind ];
}

	/**
	 * Print adjusted team points
	 *
	 * @category template-tags
	 */
function the_team_points_adjust() {
	global $team;
	echo $team->add_points;
}

	/**
	 * Print number of done matches of team
	 *
	 * @category template-tags
	 */
function num_done_matches() {
	global $team;
	echo $team->done_matches;
}
	/**
	 * Print number of sets of team
	 *
	 * @category template-tags
	 */
function num_sets() {
	global $team;
	echo esc_html( $team->sets_won . '-' . $team->sets_allowed );
}
	/**
	 * Print number of games of team
	 *
	 * @category template-tags
	 */
function num_games() {
	global $team;
	echo esc_html( $team->games_won . '-' . $team->games_allowed );
}
	/**
	 * Print number of won matches of team
	 *
	 * @category template-tags
	 */
function num_won_matches() {
	global $team;
	echo $team->won_matches;
}
	/**
	 * Print number of lost matches of team
	 *
	 * @category template-tags
	 */
function num_lost_matches() {
	global $team;
	echo $team->lost_matches;
}
	/**
	 * Print number of draw matches of team
	 *
	 * @category template-tags
	 */
function num_draw_matches() {
	global $team;
	echo $team->draw_matches;
}
	/**
	 * Print win percentage
	 *
	 * @category template-tags
	 */
function win_percentage() {
	global $team;
	echo $team->win_percent;
}

	/**
	 * Check if team has a next match
	 *
	 * @return boolean
	 * @category template-tags
	 */
function has_next_match() {
	global $team, $match;

	$match = $team->get_next_match();

	if ( $match ) {
		return true;
	}
	return false;
}
	/**
	 * Check if team has a previous match
	 *
	 * @return boolean
	 * @category template-tags
	 */
function has_prev_match() {
	global $team, $match;

	$match = $team->get_prev_match();

	if ( $match ) {
		return true;
	}
	return false;
}

	/**
	 * Print last5 matches column for team
	 *
	 * @param boolean $url url.
	 * @category template-tags
	 */
function the_last5_matches( $url = true ) {
	global $team;

	echo $team->last5( $url );
}

	/**
	 * Check if match is selected
	 *
	 * @return boolean
	 * @category template-tags
	 */
function is_single_match() {
	global $league;
	return $league->is_selected_match;
}

	/**
	 * Test whether league has matches or we are in the loop
	 *
	 * @return boolean
	 */
function have_matches() {
	global $league;

	if ( ! isset( $league->matches ) ) {
		return false;
	}
	if ( $league->current_match + 1 < count( $league->matches ) ) {
		return true;
	} elseif ( count( $league->matches ) - 1 === $league->current_match && count( $league->matches ) > 0 ) {
		// End of Loop.
		$league->current_match = -1;
	}

	$league->in_the_match_loop = false;
	return false;
}
	/**
	 * Loop through matches
	 */
function the_match() {
	global $league, $match;

	$league->in_the_match_loop = true;
	// Increment dataset count.
	++$league->current_match;
	$match = $league->matches[ $league->current_match ];
}

	/**
	 * Display single match
	 *
	 * @param string $template template.
	 */
function the_single_match( $template = '' ) {
	global $league;
	echo do_shortcode( "[match id='" . $league->current_match . "' template='" . $template . "']" );
}

	/**
	 * Print matches pagination
	 *
	 * @param string $start_el start text.
	 * @param string $end_el end text.
	 * @category template-tags
	 */
function the_matches_pagination( $start_el = "<p class='racketmanager-pagination page-numbers'>", $end_el = '</p>' ) {
	global $league;

	if ( ! empty( $league->pagination_matches ) ) {
		echo $start_el . $league->pagination_matches . $end_el;
	}
}

	/**
	 * Print Match CSS class
	 *
	 * @category template-tags
	 */
function the_match_class() {
	global $match;
	echo $match->class;
}

	/**
	 * Print Match title
	 *
	 * @param boolean $show_logo show logo indicator.
	 * @category template-tags
	 */
function the_match_title( $show_logo = true ) {
	global $match;

	echo $match->get_title( $show_logo );
}

	/**
	 * Get Match day
	 *
	 * @return int
	 * @category template-tags
	 */
function get_match_day() {
	global $match;
	return $match->match_day;
}
	/**
	 * Print Match day
	 *
	 * @category template-tags
	 */
function the_match_day() {
	echo get_match_day();
}

	/**
	 * Print Match date
	 *
	 * @param string $format format.
	 * @category template-tags
	 */
function the_match_date( $format = '' ) {
	global $match;

	if ( $format ) {
		echo mysql2date( $format, $match->date );
	} else {
		echo $match->match_date;
	}
}

	/**
	 * Print Match time
	 *
	 * @category template-tags
	 */
function the_match_time() {
	global $match;
	if ( '00:00' === $match->start_time ) {
		echo '';
	} else {
		echo $match->start_time;
	}
}

	/**
	 * Print Match location
	 *
	 * @category template-tags
	 */
function the_match_location() {
	global $match;
	echo $match->location;
}

	/**
	 * Get Match score
	 *
	 * @return string
	 * @category template-tags
	 */
function get_match_score() {
	global $match;
	return $match->score;
}
	/**
	 * Print Match score
	 *
	 * @category template-tags
	 */
function the_match_score() {
	echo get_match_score();
}

	/**
	 * Check if match has report
	 *
	 * @return boolean
	 * @category template-tags
	 */
function match_has_report() {
	global $match;

	if ( 0 !== $match->post_id ) {
		return true;
	}
	return false;
}
	/**
	 * Print Match report link
	 *
	 * @category template-tags
	 */
function the_match_report() {
	global $match;
	echo $match->report;
}

	/**
	 * Get match template type
	 *
	 * @return string
	 * @category template-tags
	 */
function get_match_template_type() {
	global $league;
	return $league->matches_template_type;
}

	/**
	 * Print crosstable field
	 *
	 * @param int $i index.
	 * @category template-tags
	 */
function the_crosstable_field( $i ) {
	global $league, $team;

	echo $league->get_crosstable_field( $team->id, $league->teams[ $i - 1 ]->id );
}

	/**
	 * Wrapper tags
	 */

	/**
	 * Display one club
	 *
	 * @param int   $club_id club.
	 * @param array $args additional arguments as associative array (optional).
	 * @category template-tags
	 */
function racketmanager_club( $club_id, $args = array() ) {
	$defaults        = array( 'template' => '' );
	$args            = array_merge( $defaults, $args );
	$args['club_id'] = intval( $club_id );

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
 * @param array      $args additional arguments as associative array (optional).
 * @category template-tags
 */
function racketmanager_players( $league_id, $args = array() ) {
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
	 * @param int   $league_id League ID.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_standings( $league_id, $args = array() ) {
	$defaults          = array(
		'season'   => false,
		'template' => '',
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
	 * @param int   $league_id league.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_crosstable( $league_id, $args = array() ) {
	global $league;

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
	 * @param int   $league_id league.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_matches( $league_id, $args = array() ) {
	global $league;

	$defaults          = array(
		'season'                   => '',
		'template'                 => '',
		'mode'                     => '',
		'limit'                    => 'true',
		'match_day'                => -1,
		'group'                    => false,
		'order'                    => false,
		'show_match_day_selection' => '',
		'show_team_selection'      => '',
		'time'                     => '',
		'team'                     => 0,
		'home_only'                => 'false',
		'match_date'               => false,
		'dateformat'               => '',
		'timeformat'               => '',
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
	 * @param int   $match_id match.
	 * @param array $args additional arguments as associative array (optional).
	 * @category template-tags
	 */
function racketmanager_match( $match_id, $args = array() ) {
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
	 * Display team list
	 *
	 * @param int|string $league_id league.
	 * @param array      $args additional arguments as associative array (optional).
	 * @category template-tags
	 */
function racketmanager_teams( $league_id, $args = array() ) {
	global $league;

	$defaults          = array(
		'season'   => false,
		'template' => '',
		'group'    => false,
	);
	$args              = array_merge( $defaults, $args );
	$args['league_id'] = intval( $league_id );

	$shortcode = '[teams';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}

	/**
	 * Display one team manually
	 *
	 * @param int   $team_id team.
	 * @param array $args additional arguments as associative array (optional).
	 * @return void
	 */
function racketmanager_team( $team_id, $args = array() ) {
	$defaults   = array( 'template' => '' );
	$args       = array_merge( $defaults, $args );
	$args['id'] = $team_id;

	$shortcode = '[team';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}

	/**
	 * Display championship manually
	 *
	 * @param int   $league_id league.
	 * @param array $args additional arguments as associative array (optional).
	 * @return void
	 */
function racketmanager_championship( $league_id, $args = array() ) {
	global $league;

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
	 * @param int   $league_id league.
	 * @param array $args additional arguments as associative array (optional).
	 * @return void
	 */
function racketmanager_archive( $league_id, $args = array() ) {
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
	 * @param int   $club_id affilated Club id club.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_results( $club_id, $args = array() ) {
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
	 * @param int   $match_id match id match.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_match_notification( $match_id, $args = array() ) {
	$args['match'] = $match_id;

	$shortcode = '[matchnotification';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}

	/**
	 * Display result email
	 *
	 * @param int   $match_id match id match.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_result_notification( $match_id, $args = array() ) {
	global $racketmanager;

	$args['match'] = $match_id;

	$shortcode = '[resultnotification';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}
/**
 * Match date change notification function
 *
 * @param int   $match_id match id.
 * @param array $args array of arguments.
 * @return string
 */
function racketmanager_match_date_change_notification( $match_id, $args = array() ) {
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
	 * @param int   $match_id match id match.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_captain_result_notification( $match_id, $args = array() ) {
	global $racketmanager;

	$args['match'] = $match_id;

	$shortcode = '[resultnotificationcaptain';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}

	/**
	 * Display result outstanding email for captain
	 *
	 * @param int   $match_id match id match.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_result_outstanding_notification( $match_id, $args = array() ) {
	global $racketmanager;

	$args['match'] = $match_id;

	$shortcode = '[resultoutstandingnotification';
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
	 * @category template-tags
	 */
function racketmanager_club_players_notification( $args = array() ) {
	global $racketmanager;

	$shortcode = '[clubplayernotification';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}

	/**
	 * Display constitution email
	 *
	 * @param int   $event_id event id.
	 * @param array $args associative array of parameters, see default values (optional).
	 * @category template-tags
	 */
function racketmanager_constitution_notification( $event_id, $args = array() ) {
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
 * @param int   $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_overview( $tournament_id, $args = array() ) {
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
 * @param int   $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_events( $tournament_id, $args = array() ) {
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
 * @param int   $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_draws( $tournament_id, $args = array() ) {
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
 * @param int   $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_players( $tournament_id, $args = array() ) {
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
 * @param int   $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_matches( $tournament_id, $args = array() ) {
	$args['id'] = $tournament_id;
	$shortcode  = '[tournament-matches';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	echo do_shortcode( $shortcode );
}
/**
 * Display tournament order of play function
 *
 * @param int   $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_order_of_play( $tournament_id, $args = array() ) {
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
 * @param int   $tournament_id tournament.
 * @param array $args array of arguments.
 */
function racketmanager_tournament_winners( $tournament_id, $args = array() ) {
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
 * @param int   $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_overview( $competition_id, $args = array() ) {
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
 * @param int   $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_events( $competition_id, $args = array() ) {
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
 * @param int   $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_clubs( $competition_id, $args = array() ) {
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
 * @param int   $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_teams( $competition_id, $args = array() ) {
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
 * @param int   $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_players( $competition_id, $args = array() ) {
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
 * @param int   $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_standings( $event_id, $args = array() ) {
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
 * @param int   $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_draw( $event_id, $args = array() ) {
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
 * @param int   $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_matches( $event_id, $args = array() ) {
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
 * @param int   $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_clubs( $event_id, $args = array() ) {
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
 * @param int   $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_teams( $event_id, $args = array() ) {
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
 * @param int   $event_id event.
 * @param array $args array of arguments.
 */
function racketmanager_event_players( $event_id, $args = array() ) {
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
 * @param int   $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_matches( $competition_id, $args = array() ) {
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
 * @param int   $competition_id competition.
 * @param array $args array of arguments.
 */
function racketmanager_competition_winners( $competition_id, $args = array() ) {
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
 * @param array  $args array of arguments.
 */
function racketmanager_player_search( $search_string, $args = array() ) {
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
function racketmanager_withdrawn_team( $args = array() ) {
	$shortcode = '[withdrawn-team';
	foreach ( $args as $key => $value ) {
		$shortcode .= ' ' . $key . "='" . $value . "'";
	}
	$shortcode .= ']';
	return do_shortcode( $shortcode );
}
