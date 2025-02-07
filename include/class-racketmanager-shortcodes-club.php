<?php
/**
 * RacketManager_Shortcodes_Club API: Shortcodes_Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodesClub
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement shortcode functions
 */
class RacketManager_Shortcodes_Club extends RacketManager_Shortcodes {
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'clubs', array( &$this, 'show_clubs' ) );
		add_shortcode( 'club', array( &$this, 'show_club' ) );
		add_shortcode( 'club-players', array( &$this, 'show_club_players' ) );
		add_shortcode( 'club-competitions', array( &$this, 'show_club_competitions' ) );
		add_shortcode( 'club-event', array( &$this, 'show_club_event' ) );
		add_shortcode( 'club-team', array( &$this, 'show_club_team' ) );
		add_shortcode( 'club-player', array( &$this, 'show_club_player' ) );
		add_shortcode( 'club-invoices', array( &$this, 'show_club_invoices' ) );
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
			return $this->return_error( __( 'Club not found', 'racketmanager' ) );
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
	 * Function to display Club Invoices
	 *
	 *  [club-invoices invoice_id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_club_invoices( $atts ) {
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
			return $this->return_error( __( 'Club not found', 'racketmanager' ) );
		}
		// Get Invoice.
		$invoice_ref = get_query_var( 'invoice' );
		if ( $invoice_ref ) {
			$invoice = get_invoice( $invoice_ref );
			if ( ! $invoice ) {
				return $this->return_error( __( 'Invoice not found', 'racketmanager' ) );
			}
			$invoice->details = $invoice->generate();
			$club->invoice    = $invoice;
		} else {
			$club->invoices = $club->get_invoices();
		}
		$user_can_update         = new \stdClass();
		$user_can_update->club   = false;
		$user_can_update->player = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update->club   = true;
			}
		}
		$filename = ( ! empty( $template ) ) ? 'invoices-' . $template : 'invoices';
		return $this->load_template(
			$filename,
			array(
				'club'            => $club,
				'user_can_manage' => $user_can_update,
			),
			'club'
		);
	}
}
