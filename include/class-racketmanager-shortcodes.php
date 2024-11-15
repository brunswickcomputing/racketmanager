<?php
/**
 * RacketManager_Shortcodes API: RacketManagerShortcodes class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodes
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement shortcode functions
 */
class RacketManager_Shortcodes {
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'dailymatches', array( &$this, 'show_daily_matches' ) );
		add_shortcode( 'latest_results', array( &$this, 'show_latest_results' ) );

		add_shortcode( 'clubs', array( &$this, 'show_clubs' ) );
		add_shortcode( 'club', array( &$this, 'show_club' ) );
		add_shortcode( 'club-players', array( &$this, 'show_club_players' ) );
		add_shortcode( 'club-competitions', array( &$this, 'show_club_competitions' ) );
		add_shortcode( 'club-event', array( &$this, 'show_club_event' ) );
		add_shortcode( 'club-team', array( &$this, 'show_club_team' ) );
		add_shortcode( 'club-player', array( &$this, 'show_club_player' ) );
		add_shortcode( 'players', array( &$this, 'show_players' ) );
		add_shortcode( 'player', array( &$this, 'show_player' ) );

		add_shortcode( 'competition-entry', array( &$this, 'show_competition_entry' ) );

		add_shortcode( 'favourites', array( &$this, 'show_favourites' ) );
		add_shortcode( 'invoice', array( &$this, 'show_invoice' ) );
		add_shortcode( 'messages', array( &$this, 'show_messages' ) );
		add_shortcode( 'memberships', array( &$this, 'show_memberships' ) );
		add_shortcode( 'search-players', array( &$this, 'show_player_search' ) );
	}
	/**
	 * Display Daily Matches
	 *
	 *    [dailymatches league_id="1" competition_id="1" match_date="dd/mm/yyyy" template="name"]
	 *
	 * - league_id is the ID of league (optional)
	 * - competition_id is the ID of the competition (optional)
	 * - season: display specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	 *
	 * @param array $atts shorcode attributes.
	 * @return string
	 */
	public function show_daily_matches( $atts ) {
		global $racketmanager, $wp;
		wp_verify_nonce( 'matches-daily' );
		$args             = shortcode_atts(
			array(
				'competition_type' => 'league',
				'template'         => 'daily',
				'match_date'       => false,
			),
			$atts
		);
		$competition_type = $args['competition_type'];
		$template         = $args['template'];
		$match_date       = $args['match_date'];

		$matches = false;

		if ( ! $match_date ) {
			$match_date = get_query_var( 'match_date' );
			if ( '' === $match_date && isset( $_GET['match_date'] ) ) {
				$match_date = sanitize_text_field( wp_unslash( $_GET['match_date'] ) );
			}
		}
		if ( '' === $match_date ) {
			$match_date = gmdate( 'Y-m-d' );
		}
		if ( isset( $wp->query_vars['competition_type'] ) ) {
			$competition_type = un_seo_url( get_query_var( 'competition_type' ) );
		}
		$matches      = $racketmanager->get_matches(
			array(
				'match_date'       => $match_date,
				'competition_type' => $competition_type,
			)
		);
		$matches_list = array();
		foreach ( $matches as $match ) {
			$key = $match->league->title;
			if ( false === array_key_exists( $key, $matches_list ) ) {
				$matches_list[ $key ] = array();
			}
			$matches_list[ $key ][] = $match;
		}

		$filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches-daily';

		return $this->load_template(
			$filename,
			array(
				'matches_list' => $matches_list,
				'match_date'   => $match_date,
			)
		);
	}
	/**
	 * Display Latest Match results
	 *
	 *    [latest_results league_id="1" competition_id="1" match_date="dd/mm/yyyy" template="name"]
	 *
	 * - league_id is the ID of league (optional)
	 * - competition_id is the ID of the competition (optional)
	 * - season: display specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	 *
	 * @param array $atts shorcode attributes.
	 * @return string
	 */
	public function show_latest_results( $atts ) {
		global $racketmanager, $wp;

		$args             = shortcode_atts(
			array(
				'competition_type' => 'league',
				'template'         => 'results',
				'days'             => 7,
				'club'             => '',
				'competition_id'   => '',
				'header_level'     => 1,
			),
			$atts
		);
		$competition_type = $args['competition_type'];
		$template         = $args['template'];
		$days             = $args['days'];
		$club_id          = $args['club'];
		$competition_id   = $args['competition_id'];
		$header_level     = $args['header_level'];
		if ( isset( $wp->query_vars['club_name'] ) ) {
			$club_name = str_replace( '-', ' ', get_query_var( 'club_name' ) );
			$club      = get_club( $club_name, 'shortcode' );
			$club_id   = $club->id;
		}
		if ( isset( $wp->query_vars['days'] ) ) {
			$days = str_replace( '-', ' ', get_query_var( 'days' ) );
		}
		if ( isset( $wp->query_vars['competition_type'] ) ) {
			$competition_type = un_seo_url( get_query_var( 'competition_type' ) );
		}
		if ( isset( $wp->query_vars['competition_name'] ) ) {
			$competition_name = un_seo_url( get_query_var( 'competition_name' ) );
			$competition      = get_competition( $competition_name, 'name' );
			if ( $competition ) {
				$competition_id = $competition->id;
			}
		}
		$matches      = false;
		$time         = 'latest';
		$matches      = $racketmanager->get_matches(
			array(
				'days'             => $days,
				'competition_type' => $competition_type,
				'time'             => $time,
				'history'          => $days,
				'club'             => $club_id,
				'competition_id'   => $competition_id,
			)
		);
		$matches_list = array();
		foreach ( $matches as $match ) {
			$key = $match->league->title;
			if ( false === array_key_exists( $key, $matches_list ) ) {
				$matches_list[ $key ] = array();
			}
			$matches_list[ $key ][] = $match;
		}
		if ( empty( $template ) ) {
			$filename = 'matches-results';
		} elseif ( isset( $league ) && $this->check_template( 'matches-results-' . $league->sport ) ) {
			$filename = 'matches-results-' . $league->sport;
		} else {
			$filename = 'matches-' . $template;
		}
		return $this->load_template(
			$filename,
			array(
				'matches_list' => $matches_list,
				'header_level' => $header_level,
			)
		);
	}
	/**
	 * Function to display Clubs Info Page
	 *
	 *    [clubs template=X]
	 *
	 * @param array $atts attributes.
	 * @return the content
	 */
	public function show_clubs( $atts ) {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		$clubs    = $racketmanager->get_clubs(
			array(
				'type' => 'current',
			)
		);

		$user_can_update_club   = false;
		$user_can_update_player = false;
		$filename               = ( ! empty( $template ) ) ? 'clubs-' . $template : 'clubs';

		return $this->load_template(
			$filename,
			array(
				'clubs'                  => $clubs,
				'user_can_update_club'   => $user_can_update_club,
				'user_can_update_player' => $user_can_update_player,
				'standalone'             => false,
			)
		);
	}
	/**
	 * Function to display Club Info Page
	 *
	 *    [club id=ID template=X]
	 *
	 * @param array $atts attributes.
	 * @return the content
	 */
	public function show_club( $atts ) {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get League by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = str_replace( '-', ' ', $club_name );

		$club = get_club( $club_name, 'shortcode' );

		if ( ! $club ) {
			return false;
		}
		$user_can_update_club   = false;
		$user_can_update_player = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update_club   = true;
				$user_can_update_player = true;
			} else {
				$options = $racketmanager->get_options( 'rosters' );
				if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] && $club->is_player_captain( $userid ) ) {
					$user_can_update_player = true;
				}
			}
		}
		$club_players    = $club->get_players(
			array(
				'active' => true,
				'type'   => 'real',
				'cache'  => false,
			)
		);
		$player_requests = Racketmanager_Util::get_player_requests(
			array(
				'club'   => $club->id,
				'status' => 'outstanding',
			)
		);
		$keys            = $racketmanager->get_options( 'keys' );
		$google_maps_key = isset( $keys['googleMapsKey'] ) ? $keys['googleMapsKey'] : '';

		$club->single = true;

		$filename = ( ! empty( $template ) ) ? 'club-' . $template : 'club';
		return $this->load_template(
			$filename,
			array(
				'club'                   => $club,
				'club_players'           => $club_players,
				'player_requests'        => $player_requests,
				'google_maps_key'        => $google_maps_key,
				'user_can_update_club'   => $user_can_update_club,
				'user_can_update_player' => $user_can_update_player,
				'standalone'             => true,
			)
		);
	}
	/**
	 * Function to display Club Players
	 *
	 *  [club-players player_id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_club_players( $atts ) {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return __( 'Club not found', 'racketmanager' );
		}
		// Get Player by Name.
		$player_name = get_query_var( 'player_id' );
		if ( $player_name ) {
			$player_name = un_seo_url( $player_name );
			$player      = get_player( $player_name, 'name' ); // get player by name.
			if ( ! $player ) {
				return __( 'Player not found', 'racketmanager' );
			}
			$club_player = $club->get_players( array( 'player' => $player->id ) );
			if ( ! $club_player ) {
				return __( 'Player not found for club', 'racketmanager' );
			}
			$player->club_name         = $club->shortcode;
			$player->created_date      = $club_player[0]->created_date;
			$player->created_user      = $club_player[0]->created_user;
			$player->created_user_name = $club_player[0]->created_user_name;
			$club->player              = $player;
		} else {
			$club->players = $club->get_players(
				array(
					'active' => true,
					'type'   => 'real',
					'cache'  => false,
				)
			);
		}
		$user_can_update         = new \stdClass();
		$user_can_update->club   = false;
		$user_can_update->player = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update->club   = true;
				$user_can_update->player = true;
			} elseif ( isset( $club->player ) && intval( $club->player->ID ) === $userid ) {
				$user_can_update->player = true;
			} else {
				$options = $racketmanager->get_options( 'rosters' );
				if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] && $club->is_player_captain( $userid ) ) {
					$user_can_update->player = true;
				}
			}
		}
		$filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
		return $this->load_template(
			$filename,
			array(
				'club'            => $club,
				'user_can_manage' => $user_can_update,
			),
			'club'
		);
	}
	/**
	 * Function to display Club competitions
	 *
	 *  [club-competitions club= competition_name= template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_club_competitions( $atts ) {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return __( 'Club not found', 'racketmanager' );
		}
		// Get competition by Name.
		$competition_name = get_query_var( 'competition_name' );
		if ( $competition_name ) {
			$competition_name = un_seo_url( $competition_name );
			$competition      = get_competition( $competition_name, 'name' );
			if ( ! $competition ) {
				return __( 'Competition not found', 'racketmanager' );
			}
			$club->competition = $competition;
		} else {
			$competitions_types = array( 'cup', 'league' );
			$club_competitions  = array();
			foreach ( $competitions_types as $competition_type ) {
				$c            = 0;
				$competitions = $racketmanager->get_competitions( array( 'type' => $competition_type ) );
				foreach ( $competitions as $competition ) {
					$events = $competition->get_events();
					$e      = 0;
					foreach ( $events as $event ) {
						$teams        = $event->get_teams_info(
							array(
								'club'    => $club->id,
								'orderby' => array( 'title' => 'ASC' ),
							)
						);
						$event->teams = $teams;
						$events[ $e ] = $event;
						++$e;
					}
					$competition->events = $events;
					$competitions[ $c ]  = $competition;
					++$c;
				}
				$club_competitions = array_merge( $club_competitions, $competitions );
			}
		}
		$club->competitions = $club_competitions;
		$user_can_update    = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update = true;
			}
		}
		$filename = ( ! empty( $template ) ) ? 'competitions-' . $template : 'competitions';
		return $this->load_template(
			$filename,
			array(
				'club'            => $club,
				'user_can_update' => $user_can_update,
			),
			'club'
		);
	}
	/**
	 * Function to display Club team
	 *
	 *  [club-team club= team= template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_club_team( $atts ) {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return __( 'Club not found', 'racketmanager' );
		}
		// Get team by Name.
		$team_name = get_query_var( 'team' );
		if ( $team_name ) {
			$team_name = un_seo_url( $team_name );
			$team      = get_team( $team_name, 'name' );
			if ( ! $team ) {
				return __( 'Team not found', 'racketmanager' );
			}
		} else {
			return __( 'Team not supplied', 'racketmanager' );
		}
		$event_name = get_query_var( 'event' );
		if ( $event_name ) {
			$event_name = un_seo_url( $event_name );
			$event      = get_event( $event_name, 'name' );
			if ( ! $event ) {
				return __( 'Event not found', 'racketmanager' );
			}
		} else {
			return __( 'Event not supplied', 'racketmanager' );
		}
		$team_info       = $event->get_team_info( $team->id );
		$team            = (object) array_merge( (array) $team, (array) $team_info );
		$club->event     = $event;
		$club->team      = $team;
		$user_can_update = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update = true;
			}
		}
		$filename = ( ! empty( $template ) ) ? 'team-' . $template : 'team';
		return $this->load_template(
			$filename,
			array(
				'club'            => $club,
				'user_can_update' => $user_can_update,
			),
			'club'
		);
	}
	/**
	 * Function to display Club event
	 *
	 *  [club-event club= event= template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_club_event( $atts ) {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return __( 'Club not found', 'racketmanager' );
		}
		$event_name = get_query_var( 'event' );
		if ( $event_name ) {
			$event_name = un_seo_url( $event_name );
			$event      = get_event( $event_name, 'name' );
			if ( ! $event ) {
				return __( 'Event not found', 'racketmanager' );
			}
		} else {
			return __( 'Event not supplied', 'racketmanager' );
		}
		$season = get_query_var( 'season' );
		if ( ! $season ) {
			if ( isset( $event->current_season['name'] ) ) {
				$season = $event->current_season['name'];
			} else {
				return __( 'No seasons for event', 'racketmanager' );
			}
		}
		$season_dtls        = $event->current_season;
		$player_stats       = $event->get_player_stats(
			array(
				'season' => $season_dtls['name'],
				'club'   => $club->id,
			)
		);
		$club->event        = $event;
		$club->player_stats = $player_stats;
		$filename           = ( ! empty( $template ) ) ? 'event-' . $template : 'event';
		return $this->load_template(
			$filename,
			array(
				'club' => $club,
			),
			'club'
		);
	}
	/**
	 * Function to display Player
	 *
	 *  [[player] player_id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_club_player( $atts ) {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return false;
		}
		// Get Player by Name.
		$player_name = get_query_var( 'player_id' );
		$player_name = un_seo_url( $player_name );
		$player      = get_player( $player_name, 'name' ); // get player by name.
		if ( ! $player ) {
			return false;
		}
		$club_player = $club->get_players( array( 'player' => $player->id ) );
		if ( ! $club_player ) {
			return __( 'Player not found for club', 'racketmanager' );
		}
		$player->club_name = $club->shortcode;
		$user_can_update   = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) ) {
				$user_can_update = true;
			} elseif ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) {
				$user_can_update = true;
			} elseif ( null !== $player->ID && intval( $player->ID ) === $userid ) {
				$user_can_update = true;
			} else {
				$options = $racketmanager->get_options( 'rosters' );
				if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] && $club->is_player_captain( $userid ) ) {
					$user_can_update = true;
				}
			}
		}
		$filename = ( ! empty( $template ) ) ? 'player-' . $template : 'player';
		return $this->load_template(
			$filename,
			array(
				'club'            => $club,
				'player'          => $player,
				'user_can_update' => $user_can_update,
			),
			'club'
		);
	}
	/**
	 * Function to display Players
	 *
	 *  [[players] template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_players( $atts ) {
		$args           = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template       = $args['template'];
		$search_string  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_results = null;
		if ( $search_string ) {
			$search_results = racketmanager_player_search( $search_string );
		}
		$favourites = array();
		if ( is_user_logged_in() ) {
			$userid     = get_current_user_id();
			$user       = get_user( $userid );
			$favourites = $user->get_favourites( 'player' );
		}
		$filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
		return $this->load_template(
			$filename,
			array(
				'favourites'     => $favourites,
				'search_string'  => $search_string,
				'search_results' => $search_results,
			)
		);
	}
	/**
	 * Function to display Player
	 *
	 *  [[player] template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_player( $atts ) {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Player by Name.
		$player_name = get_query_var( 'player_id' );
		$player_name = un_seo_url( $player_name );
		$btm         = get_query_var( 'btm' );
		if ( $btm ) {
			$player = get_player( $btm, 'btm' );
		} else {
			$player = get_player( $player_name, 'name' ); // get player by name.
		}
		if ( ! $player ) {
			return __( 'Player not found', 'racketmanager' );
		}
		$player->clubs        = $player->get_clubs();
		$player->titles       = $player->get_titles();
		$player->stats        = $player->get_career_stats();
		$player->competitions = array( 'cup', 'league', 'tournament' );
		foreach ( $player->competitions as $competition_type ) {
			if ( 'tournament' === $competition_type ) {
				$player->$competition_type = $player->get_tournaments( array( 'type' => $competition_type ) );
			} else {
				$player->$competition_type = $player->get_competitions( array( 'type' => $competition_type ) );
			}
		}

		$filename = ( ! empty( $template ) ) ? 'player-' . $template : 'player';
		return $this->load_template(
			$filename,
			array(
				'player' => $player,
			)
		);
	}
	/**
	 * Get match score
	 *
	 * @param object $match match details.
	 * @return the score
	 */
	public function get_match_score( $match ) {
		if ( null !== $match->home_points && null !== $match->away_points ) {
			if ( isset( $match->league->num_rubbers ) && $match->league->num_rubbers > 0 ) {
				$score = sprintf( '%s - %s', $match->home_points, $match->away_points );
			} else {
				$score = '';
				$sets  = $match->custom['sets'];
				foreach ( $sets as $set ) {
					if ( null !== $set['player1'] && null !== $set['player2'] ) {
						$score .= $set['player1'] . '-' . $set['player2'] . ' ';
					}
				}
				if ( '' === $score ) {
					$score = __( 'Walkover', 'racketmanager' );
				}
			}
		} elseif ( 0 !== $match->winner_id ) {
			if ( -1 === $match->home_team || -1 === $match->away_team ) {
				$score = '';
			} else {
				$score = __( 'Walkover', 'racketmanager' );
			}
		} else {
			$score = '';
		}
		return $score;
	}
	/**
	 * Function to display Competition Entry Page
	 *
	 *    [competition-entry id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_competition_entry( $atts ) {
		$args      = shortcode_atts(
			array(
				'player_id' => false,
				'template'  => '',
			),
			$atts
		);
		$player_id = $args['player_id'];
		$template  = $args['template'];
		$valid     = true;
		$type      = get_query_var( 'competition_type' );
		if ( ! $type ) {
			$valid = false;
			$msg   = esc_html_e( 'No competition type specified', 'racketmanager' );
		} else {
			$competition_name = get_query_var( 'competition_name' );
			$competition      = un_seo_url( $competition_name );
			if ( ! $competition ) {
				$valid = false;
				$msg   = esc_html_e( 'No competition name specified', 'racketmanager' );
			} elseif ( 'tournament' === $type ) {
				$player    = null;
				$player_id = get_query_var( 'player_id' );
				if ( $player_id ) {
					$player_id = un_seo_url( $player_id );
					$player    = get_player( $player_id, 'name' );
					if ( ! $player ) {
						$valid = false;
						$msg   = esc_html_e( 'Player not found', 'racketmanager' );
					}
				} else {
					$player = get_player( wp_get_current_user()->ID );
				}
			} else {
				$season = get_query_var( 'season' );
				if ( ! $season ) {
					$valid = false;
					$msg   = esc_html_e( 'No season specified', 'racketmanager' );
				} else {
					$club_name = get_query_var( 'club_name' );
					$club      = un_seo_url( $club_name );
					if ( ! $club ) {
						$valid = false;
						$msg   = esc_html_e( 'No club specified', 'racketmanager' );
					}
				}
			}
			if ( $valid ) {
				switch ( $type ) {
					case 'league':
						$output = $this->show_league_entry( $competition, $season, $club, $template );
						break;
					case 'cup':
						$output = $this->show_cup_entry( $competition, $season, $club, $template );
						break;
					case 'tournament':
						$output = $this->show_tournament_entry( $competition, $player, $template );
						break;
					default:
						$output = esc_html_e( 'Invalid competition type specified', 'racketmanager' );
				}
				return $output;
			}
		}
		return $msg;
	}
	/**
	 * Function to display Cup Entry Page
	 *
	 * @param string $competition_name competition name.
	 * @param string $season season.
	 * @param string $club_name club name.
	 * @param string $template template name.
	 * @return the content
	 */
	public function show_cup_entry( $competition_name, $season, $club_name, $template ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="contact-login-msg">You need to <a href="' . wp_login_url() . '">login</a> to enter cups</p>';
		}
		$valid = true;
		$club  = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			$valid = false;
			$msg   = esc_html_e( 'Club not found', 'racketmanager' );
		} else {
			$user                 = wp_get_current_user();
			$userid               = $user->ID;
			$user_can_update_club = false;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update_club = true;
			}
			if ( ! $user_can_update_club ) {
				$valid = false;
				$msg   = esc_html_e( 'User not authorised for club', 'racketmanager' );
			}
		}
		if ( ! $competition_name ) {
			$valid = false;
			$msg   = esc_html_e( 'Cup not found', 'racketmanager' );
		}
		if ( ! $season ) {
			$valid = false;
			$msg   = esc_html_e( 'Season not found', 'racketmanager' );
		}
		if ( $valid ) {
			$competition = get_competition( $competition_name, 'name' );
			$events      = $competition->get_events();
			foreach ( $events as $i => $event ) {
				$event->status = '';
				$events[ $i ]  = $event;
				$event         = get_event( $event );
				$event->status = '';
				$event_teams   = $event->get_teams(
					array(
						'season' => $season,
						'club'   => $club->id,
					)
				);
				foreach ( $event_teams as $event_team ) {
					$event_team->team_info = $event->get_team_info( $event_team->team_id );
					$event->team           = $event_team;
					$event->status         = 'checked';
				}
			}
			$ladies_teams = $club->get_teams( false, 'WD' );
			$mens_teams   = $club->get_teams( false, 'MD' );
			$mixed_teams  = $club->get_teams( false, 'XD' );
			$weekdays     = Racketmanager_Util::get_weekdays();

			$filename = ( ! empty( $template ) ) ? 'entry-cup-' . $template : 'entry-cup';

			return $this->load_template(
				$filename,
				array(
					'club'         => $club,
					'events'       => $events,
					'ladies_teams' => $ladies_teams,
					'mens_teams'   => $mens_teams,
					'mixed_teams'  => $mixed_teams,
					'season'       => $season,
					'competition'  => $competition,
					'weekdays'     => $weekdays,
				),
				'entry'
			);
		} else {
			return $msg;
		}
	}
	/**
	 * Function to display league Entry Page
	 *
	 * @param string $competition_name competition name.
	 * @param string $season season.
	 * @param string $club_name club name.
	 * @param string $template template name.
	 * @return the content
	 */
	public function show_league_entry( $competition_name, $season, $club_name, $template ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="contact-login-msg">You need to <a href="' . wp_login_url() . '">login</a> to enter leagues</p>';
		}
		$valid = true;
		$club  = get_club( $club_name, 'shortcode' );

		if ( ! $club ) {
			$valid = false;
			$msg   = esc_html_e( 'Club not found', 'racketmanager' );
		} else {
			$user                 = wp_get_current_user();
			$userid               = $user->ID;
			$user_can_update_club = false;
			if ( current_user_can( 'manage_racketmanager' ) ) {
				$user_can_update_club = true;
			} elseif ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) {
				$user_can_update_club = true;
			} else {
				$player = $club->get_player( $userid );
				if ( $player ) {
					$user_can_update_club = true;
				}
			}
			if ( ! $user_can_update_club ) {
				$valid = false;
				$msg   = esc_html_e( 'User not authorised for club', 'racketmanager' );
			}
		}
		if ( ! $competition_name ) {
			$valid = false;
			$msg   = esc_html_e( 'League not found', 'racketmanager' );
		}
		if ( ! $season ) {
			$valid = false;
			$msg   = esc_html_e( 'Season not found', 'racketmanager' );
		}
		if ( $valid ) {
			$competition = get_competition( $competition_name, 'name' );
			if ( ! $competition ) {
				$valid = false;
				$msg   = esc_html_e( 'Competition not found', 'racketmanager' );
			}
		}
		if ( $valid && empty( $competition->seasons[ $season ] ) ) {
			$valid = false;
			$msg   = esc_html_e( 'Season not found for competition', 'racketmanager' );
		}
		if ( $valid ) {
			$events = $competition->get_events();
			foreach ( $events as $i => $event ) {
				$event         = get_event( $event );
				$event->status = '';
				$event_teams   = $event->get_teams(
					array(
						'season' => $season,
						'club'   => $club->id,
					)
				);
				foreach ( $event_teams as $c => $event_team ) {
					$event_team->team_info = $event->get_team_info( $event_team->team_id );
					if ( '0' === $event_team->profile || '1' === $event_team->profile || '2' === $event_team->profile ) {
						$event_team->status = 'checked';
						$event->status      = 'checked';
					} else {
						$event_team->status = '';
					}
					$event_teams[ $c ] = $event_team;
				}
				$event->event_teams = $event_teams;
				if ( 'LD' === $event->type ) {
					$event->teams = $club->get_teams( false, 'XD' );
				} else {
					$event->teams = $club->get_teams( false, $event->type );
				}
				$key = 0;
				foreach ( $event->teams as $team ) {
					$found = array_search( $team->id, array_column( $event->event_teams, 'team_id' ), true );
					if ( false !== $found ) {
						unset( $event->teams[ $key ] );
					} else {
						$event_team            = new \stdClass();
						$event_team->team_id   = $team->id;
						$event_team->name      = $team->title;
						$event_team->league_id = 0;
						$event_team->status    = null;
						$event->event_teams[]  = $event_team;
					}
					++$key;
				}
				$event_team            = new \stdClass();
				$event_team->team_id   = 0;
				$event_team->name      = __( 'New team', 'racketmanager' );
				$event_team->league_id = 0;
				$event_team->status    = null;
				$event->event_teams[]  = $event_team;
				$events[ $i ]          = $event;
			}
			$filename = ( ! empty( $template ) ) ? 'entry-league-' . $template : 'entry-league';
			return $this->load_template(
				$filename,
				array(
					'club'        => $club,
					'competition' => $competition,
					'events'      => $events,
					'season'      => $season,
				),
				'entry'
			);
		} else {
			return $msg;
		}
	}
	/**
	 * Function to display Tournament Entry Page
	 *
	 * @param string $tournament tournament name.
	 * @param object $player player object.
	 * @param string $template template name.
	 * @return the content
	 */
	public function show_tournament_entry( $tournament, $player = null, $template = null ) {
		global $racketmanager;
		if ( $tournament ) {
			$tournament = get_tournament( $tournament, 'name' );
		}
		if ( ! $tournament ) {
			return esc_html_e( 'Tournament not found', 'racketmanager' );
		}
		$player->firstname = get_user_meta( $player->ID, 'first_name', true );
		$player->surname   = get_user_meta( $player->ID, 'last_name', true );
		$player->contactno = get_user_meta( $player->ID, 'contactno', true );
		$player->gender    = get_user_meta( $player->ID, 'gender', true );

		$events = $tournament->get_events();
		$c      = 0;
		foreach ( $events as $event ) {
			$event = get_event( $event );
			if ( ( 'M' === $player->gender && substr( $event->type, 0, 1 ) !== 'W' ) || ( 'F' === $player->gender && substr( $event->type, 0, 1 ) !== 'M' ) ) {
				if ( empty( $event->age_limit ) || 'open' === $event->age_limit ) {
					$entry_valid = true;
				} elseif ( empty( $player->age ) ) {
					$entry_valid = false;
				} elseif ( $player->age < $event->age_limit ) {
					if ( 'F' === $player->gender && ! empty( $event->age_offset ) ) {
						$age_limit = $event->age_limit - $event->age_offset;
						if ( $player->age < $age_limit ) {
							$entry_valid = false;
						}
					} else {
						$entry_valid = false;
					}
				}
				if ( $entry_valid ) {
					$player_entry = new \stdClass();
					$teams        = $event->get_teams(
						array(
							'name'   => $player->display_name,
							'season' => $tournament->season,
						)
					);
					if ( $teams ) {
						$team                  = $teams[0];
						$player_entry->team_id = $team->id;
						$p                     = 1;
						foreach ( $team->player as $team_player ) {
							if ( $team_player !== $player->display_name ) {
								$player_entry->partner    = $team_player;
								$player_entry->partner_id = $team->player_id[ $p ];
								break;
							}
							++$p;
						}
						$player_entry->event         = $event->name;
						$player->entry[ $event->id ] = $player_entry;
					}
				} else {
					unset( $events[ $c ] );
				}
			} else {
				unset( $events[ $c ] );
			}
			++$c;
		}

		$club_players    = $racketmanager->get_club_players(
			array(
				'player' => $player->ID,
				'active' => true,
			)
		);
		$male_partners   = $racketmanager->get_club_players(
			array(
				'gender' => 'M',
				'active' => true,
				'type'   => true,
			)
		);
		$female_partners = $racketmanager->get_club_players(
			array(
				'gender' => 'F',
				'active' => true,
				'type'   => true,
			)
		);

		$filename = ( ! empty( $template ) ) ? 'entry-tournament-' . $template : 'entry-tournament';

		return $this->load_template(
			$filename,
			array(
				'tournament'      => $tournament,
				'events'          => $events,
				'player'          => $player,
				'club_players'    => $club_players,
				'season'          => $tournament->season,
				'male_partners'   => $male_partners,
				'female_partners' => $female_partners,
			),
			'entry'
		);
	}
	/**
	 * Function to show favourites
	 *
	 *    [favourites template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_favourites( $atts ) {
		$args = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		if ( ! is_user_logged_in() ) {
			return esc_html__( 'You must be logged in to view favourites', 'racketmanager' );
		}
		$template   = $args['template'];
		$user       = get_user( get_current_user_id() );
		$favourites = $user->get_favourites();
		$filename   = ( ! empty( $template ) ) ? 'form-favourites-' . $template : 'form-favourites';
		return $this->load_template( $filename, array( 'favourite_types' => $favourites ), 'form' );
	}
	/**
	 * Function to show invoice
	 *
	 *    [invoice template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_invoice( $atts ) {
		$args = shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		);
		$id   = $args['id'];
		if ( ! $id ) {
			$id = get_query_var( 'id' );
		}
		if ( $id ) {
			$invoice = get_invoice( $id );
			if ( $invoice ) {
				return $invoice->generate();
			}
		}
		return esc_html_e( 'No invoice found', 'racketmanager' );
	}
	/**
	 * Function to show messages
	 *
	 *    [messages template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_messages( $atts ) {
		$args = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		if ( ! is_user_logged_in() ) {
			return esc_html__( 'You must be logged in to view messages', 'racketmanager' );
		}
		$messages       = array();
		$template       = $args['template'];
		$user           = get_user( get_current_user_id() );
		$messages_total = $user->get_messages( array( 'count' => true ) );
		if ( $messages_total ) {
			$messages['total']  = $messages_total;
			$messages['detail'] = $user->get_messages( array() );
			$messages['unread'] = $user->get_messages(
				array(
					'count'  => true,
					'status' => 'unread',
				)
			);
		}
		$filename = ( ! empty( $template ) ) ? 'messages-' . $template : 'messages';

		return $this->load_template( $filename, array( 'messages' => $messages ), 'account' );
	}
	/**
	 * Function to show memberships
	 *
	 *    [memberships template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_memberships( $atts ) {
		$args = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		if ( ! is_user_logged_in() ) {
			return esc_html__( 'You must be logged in to view memberships', 'racketmanager' );
		}
		$template = $args['template'];
		$player   = get_player( get_current_user_id() );
		if ( $player ) {
			$player->clubs         = $player->get_clubs( array( 'type' => 'active' ) );
			$player->clubs_archive = $player->get_clubs( array( 'type' => 'inactive' ) );
		} else {
			return esc_html__( 'Player not found', 'racketmanager' );
		}
		$filename = ( ! empty( $template ) ) ? 'player-clubs-' . $template : 'player-clubs';

		return $this->load_template( $filename, array( 'player' => $player ), 'account' );
	}
	/**
	 * Function to search players messages
	 *
	 *    [messages template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_player_search( $atts ) {
		global $racketmanager;
		$args          = shortcode_atts(
			array(
				'search'   => null,
				'template' => '',
			),
			$atts
		);
		$template      = $args['template'];
		$search_string = $args['search'];
		$players       = $racketmanager->get_all_players( array( 'name' => $search_string ) );
		$filename      = ( ! empty( $template ) ) ? 'players-list-' . $template : 'players-list';

		return $this->load_template( $filename, array( 'players' => $players ) );
	}
	/**
	 * Load template for user display. First the current theme directory is checked for a template
	 * before defaulting to the plugin
	 *
	 * @param string $template Name of the template file (without extension).
	 * @param array  $vars Array of variables name=>value available to display code (optional).
	 * @param string $template_type Type of content template (email, page).
	 * @return the content
	 */
	public function load_template( $template, $vars = array(), $template_type = false ) {
		global $league, $team, $match, $racketmanager;

		if ( $template_type ) {
			switch ( $template_type ) {
				case 'competition':
					$template_dir = 'templates/competition';
					break;
				case 'event':
					$template_dir = 'templates/event';
					break;
				case 'email':
					$template_dir = 'templates/email';
					break;
				case 'entry':
					$template_dir = 'templates/entry';
					break;
				case 'form':
					$template_dir = 'templates/forms';
					break;
				case 'includes':
					$template_dir = 'templates/includes';
					break;
				case 'page':
					$template_dir = 'templates/page';
					break;
				case 'tournament':
					$template_dir = 'templates/tournament';
					break;
				case 'account':
					$template_dir = 'templates/account';
					break;
				case 'league':
					$template_dir = 'templates/league';
					break;
				case 'club':
					$template_dir = 'templates/club';
					break;
				default:
					$template_dir = 'templates';
					break;
			}
		} else {
			$template_dir = 'templates';
		}
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		ob_start();

		if ( file_exists( get_stylesheet_directory() . "/racketmanager/$template.php" ) ) {
			require get_stylesheet_directory() . "/racketmanager/$template.php";
		} elseif ( file_exists( get_template_directory() . "/racketmanager/$template.php" ) ) {
			require get_template_directory() . "/racketmanager/$template.php";
		} elseif ( file_exists( RACKETMANAGER_PATH . $template_dir . '/' . $template . '.php' ) ) {
			require RACKETMANAGER_PATH . $template_dir . '/' . $template . '.php';
		} else {
			/* translators: %1$s: template %2$s: directory */
			echo esc_html( sprintf( __( 'Could not load template %1$s.php from %2$s directory', 'racketmanager' ), $template, $template_dir ) );
		}
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	/**
	 * Check if template exists
	 *
	 * @param string $template template name.
	 * @param string $directory optional directory name.
	 * @return boolean
	 */
	public function check_template( $template, $directory = null ) {
		$template_dir = 'templates/';
		if ( $directory ) {
			$template_dir .= $directory . '/';
		}
		return file_exists( get_stylesheet_directory() . "/racketmanager/$template.php" ) || file_exists( get_template_directory() . "/racketmanager/$template.php" ) || file_exists( RACKETMANAGER_PATH . $template_dir . $template . '.php' );
	}
	/**
	 * Get league
	 *
	 * @param int $league_id league id.
	 * @return null|League
	 */
	public function get_league( $league_id ) {
		global $league;

		if ( 0 === $league_id ) {
			$league = get_league();
		} else {
			$league = get_league( intval( $league_id ) );
		}
		return $league;
	}
	/**
	 * Get draws for event function
	 *
	 * @param object $event event object.
	 * @param string $season season.
	 * @return array of leagues with draws.
	 */
	public function get_draw( $event, $season ) {
		$leagues = $event->get_leagues();
		foreach ( $leagues as $l => $league ) {
			$league = get_league( $league->id );
			$finals = array_reverse( $league->championship->get_finals() );
			foreach ( $finals as $f => $final ) {
				$matches = $league->get_matches(
					array(
						'season'  => $season,
						'final'   => $final['key'],
						'orderby' => array(
							'id' => 'ASC',
						),
					)
				);
				if ( count( $matches ) ) {
					$final['matches'] = $matches;
					$finals[ $f ]     = (object) $final;
				} else {
					unset( $finals[ $f ] );
				}
			}
			$league->finals = $finals;
			$leagues[ $l ]  = $league;
		}
		return $leagues;
	}
}
