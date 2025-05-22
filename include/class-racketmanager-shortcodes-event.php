<?php
/**
 * Racketmanager_Shortcodes_Event API: Shortcodes_Event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Event
 */

namespace Racketmanager;

/**
 * Class to implement the Racketmanager_Shortcodes_Event object
 */
class Racketmanager_Shortcodes_Event extends Racketmanager_Shortcodes {
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'event', array( &$this, 'show_event' ) );
		add_shortcode( 'event-standings', array( &$this, 'show_event_standings' ) );
		add_shortcode( 'event-draw', array( &$this, 'show_event_draw' ) );
		add_shortcode( 'event-matches', array( &$this, 'show_event_matches' ) );
		add_shortcode( 'event-clubs', array( &$this, 'show_event_clubs' ) );
		add_shortcode( 'event-teams', array( &$this, 'show_event_teams' ) );
		add_shortcode( 'event-players', array( &$this, 'show_event_players' ) );
	}
	/**
	 * Show Event
	 *
	 * [event_id=ID season=X template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_event( $atts ) {
		global $wp;
		$args     = shortcode_atts(
			array(
				'id'       => 0,
				'season'   => false,
				'template' => '',
				'tab'      => null,
			),
			$atts
		);
		$id       = $args['id'];
		$season   = $args['season'];
		$template = $args['template'];
		$tab      = $args['tab'];
		if ( $id ) {
			$event = get_event( $id );
		} else {
			$event = get_query_var( 'event' );
			if ( $event ) {
				$event = str_replace( '-', ' ', $event );
				$event = get_event( $event, 'name' );
			}
		}
		if ( ! $tab ) {
			if ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tab = wp_strip_all_tags( wp_unslash( $_GET['tab'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['tab'] ) ) {
				$tab = get_query_var( 'tab' );
			}
		}
		if ( ! $event ) {
			$msg = __( 'Event not found', 'racketmanager' );
			return $this->return_error( $msg );

		}
		if ( ! $season ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['season'] ) && ! empty( $_GET['season'] ) ) {
				$season = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
			} elseif ( isset( $_GET['season'] ) ) {
				$season = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
			} else {
				$season = null !== get_query_var( 'season' ) ? get_query_var( 'season' ) : false;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		$seasons = $event->seasons;
		if ( ! $season ) {
			if ( $seasons ) {
				$season = $event->current_season['name'];
			} else {
				$season = '';
			}
		}
		if ( empty( $event->seasons[ $season ] ) ) {
			$msg = __( 'Season not found for event', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( empty( $template ) && $this->check_template( 'event-' . $event->competition->sport ) ) {
			$filename = 'event-' . $event->competition->sport;
		} else {
			$filename = ( ! empty( $template ) ) ? 'event-' . $template : 'event';
		}
		return $this->load_template(
			$filename,
			array(
				'event'       => $event,
				'seasons'     => $seasons,
				'curr_season' => $season,
				'tab'         => $tab,
			)
		);
	}
	/**
	 * Function to display event standings
	 *
	 * @param array $atts shortcode attributes.
	 * @return string - the content
	 */
	public function show_event_standings( $atts ) {
		$args     = shortcode_atts(
			array(
				'id'       => 0,
				'template' => '',
				'season'   => false,
			),
			$atts
		);
		$event_id = $args['id'];
		$template = $args['template'];
		$season   = $args['season'];
		$event    = get_event( $event_id );
		if ( ! $event ) {
			$msg = __( 'Event not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		$event->leagues = $event->get_leagues();
		$event->set_season( $season );
		$filename = ( ! empty( $template ) ) ? 'standings-' . $template : 'standings';
		return $this->load_template(
			$filename,
			array(
				'event' => $event,
			),
			'event'
		);
	}
	/**
	 * Function to display event draw
	 *
	 * @param array $atts shortcode attributes.
	 * @return string - the content
	 */
	public function show_event_draw( $atts ) {
		$args     = shortcode_atts(
			array(
				'id'       => 0,
				'template' => '',
				'season'   => false,
			),
			$atts
		);
		$event_id = $args['id'];
		$template = $args['template'];
		$season   = $args['season'];
		$event    = get_event( $event_id );
		if ( ! $event ) {
			$msg = __( 'Event not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		$event->set_season( $season );
		if ( $event->competition->is_championship ) {
			$event->leagues = $this->get_draw( $event, $season );
		} else {
			$event->leagues = $event->get_leagues();
		}
		$filename = ( ! empty( $template ) ) ? 'draw-' . $template : 'draw';
		return $this->load_template(
			$filename,
			array(
				'event' => $event,
			),
			'event'
		);
	}
	/**
	 * Function to display event matches
	 *
	 * @param array $atts shortcode attributes.
	 * @return string - the content
	 */
	public function show_event_matches( $atts ) {
		$args     = shortcode_atts(
			array(
				'id'       => 0,
				'template' => '',
				'season'   => false,
			),
			$atts
		);
		$event_id = $args['id'];
		$template = $args['template'];
		$season   = $args['season'];
		$event    = get_event( $event_id );
		if ( ! $event ) {
			$msg = __( 'Event not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		$event->set_season( $season );
		if ( $event->competition->is_championship ) {
			$event->leagues = $this->get_draw( $event, $season );
		} else {
			$event->leagues = $event->get_leagues();
		}
		$filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches';
		return $this->load_template(
			$filename,
			array(
				'event' => $event,
			),
			'event'
		);
	}
	/**
	 * Function to display event Clubs
	 *
	 * @param array $atts shortcode attributes.
	 * @return string - the content
	 */
	public function show_event_clubs( $atts ) {
		global $wp;
		$args     = shortcode_atts(
			array(
				'id'       => 0,
				'clubs'    => null,
				'template' => '',
				'season'   => false,
			),
			$atts
		);
		$event_id = $args['id'];
		$club_id  = $args['clubs'];
		$template = $args['template'];
		$season   = $args['season'];
		$event    = get_event( $event_id );
		if ( ! $event ) {
			$msg = __( 'Event not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		$club       = null;
		$event_club = null;
		$event->set_season( $season );
		if ( ! $club_id ) {
			if ( isset( $wp->query_vars['club_name'] ) ) {
				$club_id = get_query_var( 'club_name' );
				$club_id = str_replace( '-', ' ', $club_id );
			}
		}
		if ( $club_id ) {
			if ( is_numeric( $club_id ) ) {
				$event_club = get_club( $club_id );
			} else {
				$event_club = get_club( $club_id, 'shortcode' );
			}
			if ( $event_club ) {
				$event_club->teams   = $event->get_teams(
					array(
						'club'   => $event_club->id,
						'season' => $event->current_season['name'],
						'status' => 1,
					)
				);
				$event_club->matches = array();
				$matches             = $event->get_matches(
					array(
						'season'  => $event->current_season['name'],
						'club'    => $event_club->id,
						'time'    => 'next',
						'orderby' => array(
							'date'      => 'ASC',
							'league_id' => 'DESC',
						),
					)
				);
				foreach ( $matches as $match ) {
					$key = substr( $match->date, 0, 10 );
					if ( false === array_key_exists( $key, $event_club->matches ) ) {
						$event_club->matches[ $key ] = array();
					}
					$event_club->matches[ $key ][] = $match;
				}
				$event_club->results = array();
				$matches             = $event->get_matches(
					array(
						'season'  => $event->current_season['name'],
						'club'    => $event_club->id,
						'time'    => 'latest',
						'orderby' => array(
							'date'      => 'ASC',
							'league_id' => 'DESC',
						),
					)
				);
				foreach ( $matches as $match ) {
					$key = substr( $match->date, 0, 10 );
					if ( false === array_key_exists( $key, $event_club->results ) ) {
						$event_club->results[ $key ] = array();
					}
					$event_club->results[ $key ][] = $match;
				}
				$event_club->players = $event->get_players(
					array(
						'club'   => $event_club->id,
						'season' => $event->current_season['name'],
						'stats'  => true,
					)
				);
			} else {
				$msg = __( 'Club not found', 'racketmanager' );
				return $this->return_error( $msg );
			}
		}
		$event->clubs = $event->get_clubs( array( 'status' => 1 ) );
		$filename     = ( ! empty( $template ) ) ? 'clubs-' . $template : 'clubs';
		return $this->load_template(
			$filename,
			array(
				'event'      => $event,
				'event_club' => $event_club,
			),
			'event'
		);
	}
	/**
	 * Function to display event teams
	 *
	 * @param array $atts shortcode attributes.
	 * @return string - the content
	 */
	public function show_event_teams( $atts ) {
		global $wp;
		$args     = shortcode_atts(
			array(
				'id'       => false,
				'season'   => false,
				'template' => '',
			),
			$atts
		);
		$event_id = $args['id'];
		$season   = $args['season'];
		$template = $args['template'];
		$event    = get_event( $event_id );
		if ( ! $event ) {
			$msg = __( 'Event not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		$event->set_season( $season );
		$event->teams = $event->get_teams(
			array(
				'season'  => $event->current_season['name'],
				'orderby' => array( 'name' => 'ASC' ),
			)
		);
		$team         = null;
		if ( isset( $wp->query_vars['team'] ) ) {
			$team = get_query_var( 'team' );
			$team = str_replace( '-', ' ', $team );
		}
		if ( $team ) {
			$team = get_team( $team );
			if ( $team ) {
				$team->info    = $event->get_team_info( $team->id );
				$team->matches = $event->get_matches(
					array(
						'team_id'          => $team->id,
						'match_day'        => false,
						'limit'            => 'false',
						'reset_query_args' => true,
						'season'           => $event->current_season['name'],
						'orderby'          => array( 'date' => 'ASC' ),
					)
				);
				$players       = $event->get_players(
					array(
						'team'  => $team->id,
						'stats' => true,
					)
				);
				$team->players = $players;
				$event->team   = $team;
			}
		} elseif ( $event->competition->is_championship ) {
			if ( empty( $template ) ) {
				$template = 'list';
			}
		}
		$tab      = 'teams';
		$filename = ( empty( $template ) ) ? 'teams' : 'teams-' . $template;

		return $this->load_template(
			$filename,
			array(
				'event'       => $event,
				'tab'         => $tab,
				'curr_season' => $event->current_season['name'],
			),
			'event'
		);
	}
	/**
	 * Function to display event Players
	 *
	 * @param array $atts shortcode attributes.
	 * @return string - the content
	 */
	public function show_event_players( $atts ) {
		global $wp;
		$args      = shortcode_atts(
			array(
				'id'       => 0,
				'season'   => null,
				'players'  => null,
				'template' => '',
			),
			$atts
		);
		$event_id  = $args['id'];
		$season    = $args['season'];
		$player_id = $args['players'];
		$template  = $args['template'];
		$event     = get_event( $event_id );
		if ( $event ) {
			$event->set_season( $season );
			$player         = null;
			$event->players = array();
			if ( ! $player_id ) {
				if ( isset( $wp->query_vars['player_id'] ) ) {
					$player_id = un_seo_url( get_query_var( 'player_id' ) );
				}
			}
			if ( $player_id ) {
				if ( is_numeric( $player_id ) ) {
					$player = get_player( $player_id ); // get player by name.
				} else {
					$player = get_player( $player_id, 'name' ); // get player by name.
				}
				if ( $player ) {
					$player->matches = $player->get_matches( $event, $event->current_season['name'], 'event' );
					asort( $player->matches );
					$player->stats = $player->get_stats();
					$event->player = $player;
				} else {
					esc_html_e( 'Player not found', 'racketmanager' );
				}
			} else {
				$players        = $event->get_players( array( 'season' => $event->current_season['name'] ) );
				$event->players = RacketManager_Util::get_players_list( $players );
			}
			$filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
			return $this->load_template(
				$filename,
				array(
					'event' => $event,
				),
				'event'
			);
		} else {
			$msg = __( 'Event not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
	}
}
