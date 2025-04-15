<?php /** @noinspection PhpMissingParentConstructorInspection */

/**
 * Racketmanager_Shortcodes_Competition API: Shortcodes_Competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Competition
 */

namespace Racketmanager;

/**
 * Class to implement the Racketmanager_Shortcodes_Competition object
 */
class Racketmanager_Shortcodes_Competition extends Racketmanager_Shortcodes {
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'competitions', array( &$this, 'show_competitions' ) );
		add_shortcode( 'competition', array( &$this, 'show_competition' ) );
		add_shortcode( 'competition-overview', array( &$this, 'show_competition_overview' ) );
		add_shortcode( 'competition-events', array( &$this, 'show_competition_events' ) );
		add_shortcode( 'competition-teams', array( &$this, 'show_competition_teams' ) );
		add_shortcode( 'competition-clubs', array( &$this, 'show_competition_clubs' ) );
		add_shortcode( 'competition-players', array( &$this, 'show_competition_players' ) );
		add_shortcode( 'competition-winners', array( &$this, 'show_competition_winners' ) );
	}
	/**
	 * Show competitions function
	 *
	 * @param array $atts attributes.
	 * @return string display output
	 */
	public function show_competitions( array $atts ): string {
		global $wp, $racketmanager;
		$args     = shortcode_atts(
			array(
				'type'      => false,
				'age_group' => false,
				'template'  => '',
			),
			$atts
		);
		$type      = $args['type'];
		$age_group = $args['age_group'];
		$template  = $args['template'];
		if ( ! $type ) {
			if ( isset( $_GET['competition_type'] ) && ! empty( $_GET['type'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$type = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['type'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['type'] ) ) {
				$type = get_query_var( 'type' );
			}
			$type = un_seo_url( $type );
		}
		if ( ! $type ) {
			$msg = __( 'Competition type not set', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( isset( $wp->query_vars['age_group'] ) ) {
			$age_group = get_query_var( 'age_group' );
		}
		$user_competitions       = null;
		$query_args['type']      = $type;
		$query_args['age_group'] = $age_group;
		if ( 'tournament' === $type ) {
			$query_args['orderby'] = array( 'date' => 'DESC' );
			$tournaments           = $racketmanager->get_tournaments( $query_args );
			$competitions          = array();
			foreach ( $tournaments as $tournament ) {
				$tournament->type     = $type;
				$tournament->date_end = $tournament->date;
				$competitions[]       = $tournament;
			}
			if ( is_user_logged_in() ) {
				$player = get_player( get_current_user_id() );
				$user_competitions = $player?->get_tournaments($query_args);
			}
		} else {
			$competitions = $racketmanager->get_competitions( $query_args );
			if ( is_user_logged_in() ) {
				$player = get_player( get_current_user_id() );
				$user_competitions = $player?->get_competitions($query_args);
			}
		}
		$competition_type = match ($type) {
			'league'     => __('Leagues', 'racketmanager'),
			'cup'        => __('Cups', 'racketmanager'),
			'tournament' => __('Tournaments', 'racketmanager'),
			default      => __('Competitions', 'racketmanager'),
		};
		$filename = ( ! empty( $template ) ) ? 'competitions-' . $template : 'competitions';

		return $this->load_template(
			$filename,
			array(
				'competitions'      => $competitions,
				'type'              => $competition_type,
				'user_competitions' => $user_competitions,
			)
		);
	}
	/**
	 * Show competition function
	 *
	 * @param array $atts attributes.
	 * @return string display output
	 */
	public function show_competition( array $atts ): string {
		global $wp;
		$args        = shortcode_atts(
			array(
				'competition' => false,
				'tab'         => false,
				'season'      => false,
				'template'    => '',
			),
			$atts
		);
		$competition = $args['competition'];
		$tab         = $args['tab'];
		$season      = $args['season'];
		$template    = $args['template'];
		if ( ! $competition ) {
			if (! empty( $_GET['competition'] )) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$competition = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['competition'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['competition'] ) ) {
				$competition = get_query_var( 'competition' );
			}
			$competition = un_seo_url( $competition );
		}
		if ( $competition ) {
			$competition = get_competition( $competition, 'name' );
		}
		if ( ! $competition ) {
			$msg = __( 'Competition not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( ! $season ) {
			if (! empty( $_GET['season'] )) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$season = wp_strip_all_tags( wp_unslash( $_GET['season'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['season'] ) ) {
				$season = get_query_var( 'season' );
			}
		}
		if ( $season ) {
			$competition_season = $competition->seasons[$season] ?? null;
			if ( $competition_season ) {
				if ( ! empty( $competition_season['venue'] ) ) {
					$venue_club = get_club( $competition_season['venue'] );
					if ( $venue_club ) {
						$competition_season['venue_name'] = $venue_club->shortcode;
					}
				}
			} else {
				$msg = __( 'Season not found', 'racketmanager' );
				return $this->return_error( $msg );
			}
		} elseif ( empty( $competition->seasons ) ) {
			$msg = __( 'No seasons found for competition', 'racketmanager' );
			return $this->return_error( $msg );
		} else {
			$competition_season = $competition->current_season;
			$season             = $competition_season['name'];
		}
		if ( ! $tab ) {
			if (! empty( $_GET['tab'] )) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tab = wp_strip_all_tags( wp_unslash( $_GET['tab'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['tab'] ) ) {
				$tab = get_query_var( 'tab' );
			}
		}
		if ( $competition->is_open && is_user_logged_in() ) {
			$entry_link = '/entry-form/' . seo_url( $competition->name ) . '/' . $season . '/';
			$clubs      = $this->club_selection_available( $competition );
			if ( $clubs ) {
				if ( ! is_array( $clubs ) ) {
					$entry_link .= seo_url( $clubs->shortcode ) . '/';
				}
				$competition->entry_link = $entry_link;
			}
		}
		$filename = ( ! empty( $template ) ) ? 'competition-' . $template : 'competition';

		return $this->load_template(
			$filename,
			array(
				'competition'        => $competition,
				'competition_season' => $competition_season,
				'tab'                => $tab,
			)
		);
	}
	/**
	 * Show competition overview function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_competition_overview( array $atts ): string {
		$args           = shortcode_atts(
			array(
				'id'       => false,
				'season'   => null,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$season         = $args['season'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			$msg = __( 'Competition not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( $season ) {
			$competition->set_season( $season );
		} else {
			$season = $competition->current_season['name'];
		}
		$competition->events  = $competition->get_events();
		$competition->entries = $competition->get_teams(
			array(
				'count'  => true,
				'season' => $season,
				'status' => 1,
			)
		);
		$player_args              = array();
		$player_args['season']    = $competition->current_season['name'];
		$player_args['count']     = true;
		$competition->num_players = $competition->get_players( $player_args );
		$filename = ( ! empty( $template ) ) ? 'overview-' . $template : 'overview';
		return $this->load_template(
			$filename,
			array(
				'competition'        => $competition,
				'competition_season' => $competition->current_season,
			),
			'competition'
		);
	}
	/**
	 * Show events function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_competition_events( array $atts ): string {
		$args           = shortcode_atts(
			array(
				'id'       => false,
				'season'   => null,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$season         = $args['season'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			$msg = __( 'Competition not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( $season ) {
			$competition->set_season( $season );
		} else {
			$season = $competition->current_season['name'];
		}
		$competition->events = $competition->get_events();
		$i                   = 0;
		foreach ( $competition->events as $event ) {
			$event->num_entries        = $event->get_teams(
				array(
					'count'  => true,
					'season' => $season,
					'status' => 1,
				)
			);
			$competition->events[ $i ] = $event;
			++$i;
		}

		$tab      = 'events';
		$filename = ( ! empty( $template ) ) ? 'events-' . $template : 'events';

		return $this->load_template(
			$filename,
			array(
				'competition' => $competition,
				'tab'         => $tab,
			),
			'competition'
		);
	}
	/**
	 * Show teams function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_competition_teams( array $atts ): string {
		$args           = shortcode_atts(
			array(
				'id'       => false,
				'season'   => null,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$season         = $args['season'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			$msg = __( 'Competition not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( $season ) {
			$competition->set_season( $season );
		} else {
			$season = $competition->current_season['name'];
		}
		$competition->teams = $competition->get_teams(
			array(
				'status'  => 1,
				'season'  => $season,
				'orderby' => array( 'name' => 'ASC' ),
			)
		);

		$tab      = 'teams';
		$filename = ( ! empty( $template ) ) ? 'teams-' . $template : 'teams';

		return $this->load_template(
			$filename,
			array(
				'competition' => $competition,
				'tab'         => $tab,
			),
			'competition'
		);
	}
	/**
	 * Function to display competition Clubs
	 *
	 * @param array $atts shortcode attributes.
	 * @return string the content
	 */
	public function show_competition_clubs( array $atts ): string {
		global $wp;
		$args           = shortcode_atts(
			array(
				'id'       => 0,
				'season'   => null,
				'clubs'    => null,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$season         = $args['season'];
		$club_id        = $args['clubs'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			$msg = __( 'Competition not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( $season ) {
			$competition->set_season( $season );
		} else {
			$season = $competition->current_season['name'];
		}
		$competition->clubs = $competition->get_clubs( array( 'status' => 1 ) );
		$competition_club   = null;
		if ( ! $club_id ) {
			if ( isset( $wp->query_vars['club_name'] ) ) {
				$club_id = get_query_var( 'club_name' );
				$club_id = str_replace( '-', ' ', $club_id );
			}
		}
		if ( $club_id ) {
			if ( is_numeric( $club_id ) ) {
				$competition_club = get_club( $club_id );
			} else {
				$competition_club = get_club( $club_id, 'shortcode' );
			}
			if ( $competition_club ) {
				$competition_club->teams   = $competition->get_teams(
					array(
						'club'   => $competition_club->id,
						'season' => $season,
						'status' => 1,
					)
				);
				$competition_club->matches = array();
				$matches                   = $competition->get_matches(
					array(
						'season'  => $season,
						'club'    => $competition_club->id,
						'time'    => 'next',
						'orderby' => array(
							'date'      => 'ASC',
							'league_id' => 'DESC',
						),
					)
				);
				foreach ( $matches as $match ) {
					$key = substr( $match->date, 0, 10 );
					if ( false === array_key_exists( $key, $competition_club->matches ) ) {
						$competition_club->matches[ $key ] = array();
					}
					$competition_club->matches[ $key ][] = $match;
				}
				$competition_club->results = array();
				$matches                   = $competition->get_matches(
					array(
						'season'  => $season,
						'club'    => $competition_club->id,
						'time'    => 'latest',
						'orderby' => array(
							'date'      => 'ASC',
							'league_id' => 'DESC',
						),
					)
				);
				foreach ( $matches as $match ) {
					$key = substr( $match->date, 0, 10 );
					if ( false === array_key_exists( $key, $competition_club->results ) ) {
						$competition_club->results[ $key ] = array();
					}
					$competition_club->results[ $key ][] = $match;
				}
				$competition_club->players = $competition->get_players(
					array(
						'club'   => $competition_club->id,
						'season' => $season,
						'stats'  => true,
					)
				);
			} else {
				$msg = __( 'Club not found', 'racketmanager' );
				return $this->return_error( $msg );
			}
		}
		$filename = ( ! empty( $template ) ) ? 'clubs-' . $template : 'clubs';
		return $this->load_template(
			$filename,
			array(
				'competition'      => $competition,
				'competition_club' => $competition_club,
			),
			'competition'
		);
	}
	/**
	 * Function to display competition Players
	 *
	 * @param array $atts shortcode attributes.
	 * @return string the content
	 */
	public function show_competition_players( array $atts ): string {
		global $wp;
		$args           = shortcode_atts(
			array(
				'id'       => 0,
				'season'   => false,
				'players'  => null,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$season         = $args['season'];
		$player_id      = $args['players'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			$msg = __( 'Competition not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( $season ) {
			$competition->set_season( $season );
		}
		$competition->players = array();
		if ( ! $player_id ) {
			if ( isset( $wp->query_vars['player_id'] ) ) {
				$player_id = un_seo_url( get_query_var( 'player_id' ) );
			}
		}
		if ( $player_id ) {
			if ( is_numeric( $player_id ) ) {
				$player = get_player( $player_id ); // get player by id.
			} else {
				$player = get_player( $player_id, 'name' ); // get player by name.
			}
			if ( $player ) {
				$player->matches = $player->get_matches( $competition, $competition->current_season['name'], 'competition' );
				asort( $player->matches );
				$player->stats       = $player->get_stats();
				$competition->player = $player;
			} else {
				esc_html_e( 'Player not found', 'racketmanager' );
			}
		} else {
			$players              = $competition->get_players( array( 'season' => $competition->current_season['name'] ) );
			$competition->players = RacketManager_Util::get_players_list( $players );
		}
		$filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
		return $this->load_template(
			$filename,
			array(
				'competition' => $competition,
			),
			'competition'
		);
	}
	/**
	 * Function to display competition winners
	 *
	 * @param array $atts shortcode attributes.
	 * @return string the content
	 */
	public function show_competition_winners( array $atts ): string {
		$args           = shortcode_atts(
			array(
				'id'       => 0,
				'season'   => false,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$season         = $args['season'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			$msg = __( 'Competition not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		if ( $season ) {
			$competition->set_season( $season );
		}
		$competition->winners = $competition->get_winners( true );
		$filename             = ( ! empty( $template ) ) ? 'winners-' . $template : 'winners';
		return $this->load_template(
			$filename,
			array(
				'competition' => $competition,
			),
			'competition'
		);
	}
}
