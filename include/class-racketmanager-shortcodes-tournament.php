<?php
/**
 * Racketmanager_Shortcodes_Tournament API: Shortcodes_Tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Competition
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Racketmanager_Shortcodes_Tournament object
 */
class Racketmanager_Shortcodes_Tournament extends Racketmanager_Shortcodes {
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'tournament', array( &$this, 'show_tournament' ) );
		add_shortcode( 'tournament-overview', array( &$this, 'show_tournament_overview' ) );
		add_shortcode( 'tournament-events', array( &$this, 'show_events' ) );
		add_shortcode( 'tournament-draws', array( &$this, 'show_draws' ) );
		add_shortcode( 'tournament-players', array( &$this, 'show_tournament_players' ) );
		add_shortcode( 'tournament-winners', array( &$this, 'show_tournament_winners' ) );
		add_shortcode( 'tournament-matches', array( &$this, 'show_tournament_matches' ) );
		add_shortcode( 'tournamentmatch', array( &$this, 'show_tournament_match' ) );
		add_shortcode( 'orderofplay', array( &$this, 'show_order_of_play' ) );
	}
	/**
	 * Show tournament function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_tournament( $atts ) {
		global $racketmanager, $wp;
		$args        = shortcode_atts(
			array(
				'tournament' => false,
				'tab'        => false,
				'template'   => '',
			),
			$atts
		);
		$tournament  = $args['tournament'];
		$tab         = $args['tab'];
		$template    = $args['template'];
		$tournaments = $racketmanager->get_tournaments(
			array(
				'orderby' => array(
					'season'         => 'DESC',
					'competition_id' => 'DESC',
				),
			)
		);
		if ( ! $tournament ) {
			if ( isset( $_GET['tournament'] ) && ! empty( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tournament = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['tournament'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['tournament'] ) ) {
				$tournament = get_query_var( 'tournament' );
			}
			$tournament = un_seo_url( $tournament );
		}
		if ( ! $tournament ) {
			$active_tournaments = $racketmanager->get_tournaments( array( 'active' => true ) );
			if ( $active_tournaments ) {
				$tournament = $active_tournaments[0];
				$new_url    = '/tournament/' . seo_url( $tournament->name ) . '/';
			} else {
				$new_url = '/tournaments/';
			}
			echo '<script>location.href = "' . esc_url( $new_url ) . '"</script>';
			exit;
		} else {
			$tournament = get_tournament( $tournament, 'name' );
		}
		if ( ! $tournament ) {
			return esc_html_e( 'Tournament not found', 'racketmanager' );
		}
		if ( ! $tab ) {
			if ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tab = wp_strip_all_tags( wp_unslash( $_GET['tab'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['tab'] ) ) {
				$tab = get_query_var( 'tab' );
			}
		}
		$filename = ( ! empty( $template ) ) ? 'tournament-' . $template : 'tournament';

		return $this->load_template(
			$filename,
			array(
				'tournament'  => $tournament,
				'tournaments' => $tournaments,
				'tab'         => $tab,
			)
		);
	}
	/**
	 * Show tournament overview function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_tournament_overview( $atts ) {
		$args          = shortcode_atts(
			array(
				'id'       => false,
				'template' => '',
			),
			$atts
		);
		$tournament_id = $args['id'];
		$template      = $args['template'];
		$tournament    = get_tournament( $tournament_id );
		if ( ! $tournament ) {
			return esc_html_e( 'Tournament not found', 'racketmanager' );
		}
		$tournament->events  = $tournament->get_events();
		$tournament->entries = $tournament->get_entries( array( 'count' => true ) );

		$filename = ( ! empty( $template ) ) ? 'overview-' . $template : 'overview';

		return $this->load_template(
			$filename,
			array(
				'tournament' => $tournament,
			),
			'tournament'
		);
	}
	/**
	 * Show event function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_events( $atts ) {
		global $wp;
		$args               = shortcode_atts(
			array(
				'id'       => false,
				'events'   => false,
				'template' => '',
			),
			$atts
		);
		$tournament_id      = $args['id'];
		$event_id           = $args['events'];
		$template           = $args['template'];
		$event              = null;
		$tournament         = get_tournament( $tournament_id );
		$tournament->events = $tournament->get_events();
		if ( ! $event_id ) {
			if ( isset( $_GET['event'] ) && ! empty( $_GET['event'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$event_id = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['event'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['event'] ) ) {
				$event_id = get_query_var( 'event' );
			}
			$event_id = str_replace( '-', ' ', $event_id );
		}
		if ( $event_id ) {
			if ( is_numeric( $event_id ) ) {
				$event = get_event( $event_id );
			} else {
				$event = get_event( $event_id, 'name' );
			}
			if ( $event ) {
				$primary_league_id = $event->primary_league;
				if ( $primary_league_id ) {
					$league = get_league( $primary_league_id );
					if ( $league ) {
						$event->num_seeds = isset( $league->championship->num_seeds ) ? $league->championship->num_seeds : 0;
					}
				}
				$teams = $event->get_teams(
					array(
						'season'  => $tournament->season,
						'league'  => $event->primary_league,
						'orderby' => array(
							'rank' => 'ASC',
						),
					)
				);
				if ( $teams ) {
					$event->teams = $teams;
				} else {
					$event->teams = array();
				}
			}
		}
		$tab      = 'events';
		$filename = ( ! empty( $template ) ) ? 'events-' . $template : 'events';

		return $this->load_template(
			$filename,
			array(
				'tournament' => $tournament,
				'event'      => $event,
				'tab'        => $tab,
			),
			'tournament'
		);
	}
	/**
	 * Show draw function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_draws( $atts ) {
		global $racketmanager, $league, $wp;
		$args          = shortcode_atts(
			array(
				'id'       => false,
				'draws'    => false,
				'template' => '',
			),
			$atts
		);
		$tournament_id = $args['id'];
		$draw_id       = $args['draws'];
		$template      = $args['template'];
		$draw          = null;
		$tournament    = get_tournament( $tournament_id );
		if ( ! $draw_id ) {
			if ( isset( $_GET['draw'] ) && ! empty( $_GET['draw'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$draw_id = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['draw'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['draw'] ) ) {
				$draw_id = get_query_var( 'draw' );
			}
			$draw_id = str_replace( '-', ' ', $draw_id );
		}
		if ( $draw_id ) {
			if ( is_numeric( $draw_id ) ) {
				$draw = get_event( $draw_id );
			} else {
				$draw = get_event( $draw_id, 'name' );
			}
			if ( $draw ) {
				$draw->leagues = $this->get_draw( $draw, $tournament->season );
			}
			$matches = $racketmanager->get_matches(
				array(
					'season'   => $tournament->season,
					'event_id' => $draw->id,
					'latest'   => true,
					'orderby'  => array(
						'date' => 'ASC',
					),
				)
			);
		} else {
			$matches = array();
			$events  = $tournament->get_events();
			$e       = 0;
			foreach ( $events as $event ) {
				if ( ! empty( $event->primary_league ) ) {
					$league = get_league( $event->primary_league );
				} else {
					$leagues = $event->get_leagues();
					$league  = get_league( $leagues[0] );
				}
				$event->draw_size = $league->championship->num_teams_first_round;
				$events[ $e ]     = $event;
				++$e;
			}
			$tournament->events = $events;
		}
		$tab      = 'draws';
		$filename = ( ! empty( $template ) ) ? 'draws-' . $template : 'draws';

		return $this->load_template(
			$filename,
			array(
				'tournament' => $tournament,
				'draw'       => $draw,
				'matches'    => $matches,
				'tab'        => $tab,
			),
			'tournament'
		);
	}
	/**
	 * Show tournament_players function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_tournament_players( $atts ) {
		global $wp;
		$args          = shortcode_atts(
			array(
				'id'       => false,
				'players'  => false,
				'template' => '',
			),
			$atts
		);
		$tournament_id = $args['id'];
		$player_id     = $args['players'];
		$template      = $args['template'];
		$player        = null;
		$tournament    = get_tournament( $tournament_id );
		if ( $tournament ) {
			if ( ! $player_id ) {
				if ( isset( $_GET['player'] ) && ! empty( $_GET['player'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$player_id = un_seo_url( htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['player'] ) ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				} elseif ( isset( $wp->query_vars['player'] ) ) {
					$player_id = un_seo_url( get_query_var( 'player' ) );
				}
			}
			if ( $player_id ) {
				if ( is_numeric( $player_id ) ) {
					$player = get_player( $player_id ); // get player by name.

				} else {
					$player = get_player( $player_id, 'name' ); // get player by name.
				}
				if ( $player ) {
					$player = $this->get_player_info( $player, $tournament );
				}
			} else {
				$players             = $tournament->get_players();
				$tournament->players = RacketManager_Util::get_players_list( $players );
			}
			$filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
			return $this->load_template(
				$filename,
				array(
					'tournament'        => $tournament,
					'tournament_player' => $player,
				),
				'tournament'
			);
		}
	}
	/**
	 * Get tournament player information function
	 *
	 * @param object $player player object.
	 * @param object $tournament tournament object.
	 * @return object
	 */
	public function get_player_info( $player, $tournament ) {
		global $racketmanager;
		$tournament->events = $tournament->get_events();
		foreach ( $tournament->events as $event ) {
			$event = get_event( $event );
			$teams = $event->get_teams(
				array(
					'name'   => $player->display_name,
					'season' => $tournament->season,
				)
			);
			if ( $teams ) {
				$team = $teams[0];
				foreach ( $team->player as $team_player ) {
					if ( $team_player !== $player->display_name ) {
						$team->partner = $team_player;
					}
				}
				$team->event     = $event->name;
				$player->teams[] = $team;
			}
		}
		if ( ! empty( $team ) ) {
			$player->club      = $team->club_id;
			$player->club_name = get_club( $player->club )->name;
		}
		$tournament->matches = $racketmanager->get_matches(
			array(
				'season'         => $tournament->season,
				'competition_id' => $tournament->competition_id,
				'team_name'      => esc_sql( $player->display_name ),
				'orderby'        => array(
					'date'      => 'ASC',
					'event_id'  => 'ASC',
					'league_id' => 'DESC',
				),
			)
		);
		$opponents           = array( 'home', 'away' );
		$opponents_pt        = array( 'player1', 'player2' );
		foreach ( $tournament->matches as $match ) {
			if ( ! empty( $match->winner_id ) ) {
				$match_type         = strtolower( substr( $match->league->type, 1, 1 ) );
				$winner             = null;
				$loser              = null;
				$player_ref         = null;
				$player_team        = null;
				$player_team_status = null;
				foreach ( $opponents as $opponent ) {
					if ( $match->winner_id === $match->teams[ $opponent ]->id ) {
						$winner = $opponent;
					}
					if ( $match->loser_id === $match->teams[ $opponent ]->id ) {
						$loser = $opponent;
					}
					if ( array_search( $player->display_name, $match->teams[ $opponent ]->player, true ) ) {
						$player_team = $opponent;
						if ( 'home' === $player_team ) {
							$player_ref = 'player1';
						} else {
							$player_ref = 'player2';
						}
					}
				}
				if ( $winner === $player_team ) {
					$player_team_status = 'winner';
				} elseif ( $loser === $player_team ) {
					$player_team_status = 'loser';
				}
				if ( ! isset( $player->statistics[ $match_type ]['played'][ $player_team_status ] ) ) {
					$player->statistics[ $match_type ]['played'][ $player_team_status ] = 0;
				}
				++$player->statistics[ $match_type ]['played'][ $player_team_status ];
				if ( $match->is_walkover && 'winner' === $player_team_status ) {
					if ( ! isset( $player->statistics[ $match_type ]['walkover'] ) ) {
						$player->statistics[ $match_type ]['walkover'] = 0;
					}
					++$player->statistics[ $match_type ]['walkover'];
				}
				$sets = ! empty( $match->custom['sets'] ) ? $match->custom['sets'] : array();
				foreach ( $sets as $set ) {
					if ( isset( $set['player1'] ) && '' !== $set['player1'] && isset( $set['player2'] ) && '' !== $set['player2'] ) {
						if ( $set['player1'] > $set['player2'] ) {
							if ( 'player1' === $player_ref ) {
								$stat_ref = 'winner';
							} else {
								$stat_ref = 'loser';
							}
						} elseif ( 'player1' === $player_ref ) {
							$stat_ref = 'loser';
						} else {
							$stat_ref = 'winner';
						}
						if ( ! isset( $player->statistics[ $match_type ]['sets'][ $stat_ref ] ) ) {
							$player->statistics[ $match_type ]['sets'][ $stat_ref ] = 0;
						}
						++$player->statistics[ $match_type ]['sets'][ $stat_ref ];
						foreach ( $opponents_pt as $opponent ) {
							if ( $player_ref === $opponent ) {
								if ( ! isset( $player->statistics[ $match_type ]['games']['winner'] ) ) {
									$player->statistics[ $match_type ]['games']['winner'] = 0;
								}
								$player->statistics[ $match_type ]['games']['winner'] += $set[ $opponent ];
							} else {
								if ( ! isset( $player->statistics[ $match_type ]['games']['loser'] ) ) {
									$player->statistics[ $match_type ]['games']['loser'] = 0;
								}
								$player->statistics[ $match_type ]['games']['loser'] += $set[ $opponent ];
							}
						}
					}
				}
			}
		}
		$player->statistics = $player->get_stats( $player->statistics );
		return $player;
	}
	/**
	 * Show tournament winners function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_tournament_winners( $atts ) {
		global $racketmanager;
		$args          = shortcode_atts(
			array(
				'id'       => false,
				'template' => '',
			),
			$atts
		);
		$tournament_id = $args['id'];
		$template      = $args['template'];
		$tournament    = get_tournament( $tournament_id );
		if ( ! $tournament ) {
			return esc_html_e( 'Tournament not found', 'racketmanager' );
		}
		$winners = $racketmanager->get_winners( $tournament->season, $tournament->competition_id, 'tournament', true );

		$filename = ( ! empty( $template ) ) ? 'winners-' . $template : 'winners';

		return $this->load_template(
			$filename,
			array(
				'tournament' => $tournament,
				'winners'    => $winners,
			),
			'tournament'
		);
	}
	/**
	 * Show tournament_players function
	 *
	 * @param array $atts function attributes.
	 * @return string
	 */
	public function show_tournament_matches( $atts ) {
		global $racketmanager, $wp;
		$args          = shortcode_atts(
			array(
				'id'         => false,
				'match_date' => false,
				'template'   => '',
			),
			$atts
		);
		$tournament_id = $args['id'];
		$match_date    = $args['match_date'];
		$template      = $args['template'];
		$tournament    = get_tournament( $tournament_id );
		$order_of_play = array();
		$matches       = array();
		if ( ! $match_date ) {
			if ( isset( $_GET['match_date'] ) && ! empty( $_GET['match_date'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$match_date = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['match_date'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['match_date'] ) ) {
				$match_date = get_query_var( 'match_date' );
			}
		}
				$tournament_matches = $racketmanager->get_matches(
					array(
						'season'         => $tournament->season,
						'competition_id' => $tournament->competition->id,
						'final'          => 'all',
					)
				);
		$match_dates                = array();
		foreach ( $tournament_matches as $match ) {
			$key = substr( $match->date, 0, 10 );
			if ( false === array_key_exists( $key, $match_dates ) ) {
				$match_dates[ $key ] = substr( $match->date, 0, 10 );
			}
		}
		asort( $match_dates );
		$tournament->match_dates = $match_dates;

		if ( empty( $match_date ) && ! empty( $tournament->match_dates ) ) {
			$match_date = end( $tournament->match_dates );
		}
		if ( $match_date ) {
			$matches = $racketmanager->get_matches(
				array(
					'season'         => $tournament->season,
					'competition_id' => $tournament->competition_id,
					'match_date'     => $match_date,
					'final'          => 'all',
					'orderby'        => array(
						'event_id'  => 'ASC',
						'league_id' => 'DESC',
						'date'      => 'DESC',
					),
				)
			);
		}
		$tab      = 'matches';
		$filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches';

		return $this->load_template(
			$filename,
			array(
				'tournament'         => $tournament,
				'order_of_play'      => $order_of_play,
				'tournament_matches' => $matches,
				'current_match_date' => $match_date,
				'tab'                => $tab,
			),
			'tournament'
		);
	}
	/**
	 * Display single tournament match
	 *
	 * [match id="1" template="name"]
	 *
	 * - id is the ID of the match to display
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "match-template.php" (optional)
	 *
	 * @param array $atts shorcode attributes.
	 * @return string
	 */
	public function show_tournament_match( $atts ) {
		global $racketmanager, $wp;
		$args       = shortcode_atts(
			array(
				'tournament' => false,
				'match_id'   => 0,
				'template'   => '',
			),
			$atts
		);
		$tournament = $args['tournament'];
		$match_id   = $args['match_id'];
		$template   = $args['template'];

		if ( ! $tournament ) {
			if ( isset( $_GET['tournament'] ) && ! empty( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tournament = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['tournament'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['tournament'] ) ) {
				$tournament = get_query_var( 'tournament' );
			}
			$tournament = un_seo_url( $tournament );
		}
		if ( ! $tournament ) {
			return esc_html_e( 'Tournament not found', 'racketmanager' );
		}
		$tournament = get_tournament( $tournament, 'name' );
		// Get Match ID from shortcode or $_GET.
		if ( ! $match_id ) {
			$match_id = get_query_var( 'match_id' );
		}
		if ( $match_id ) {
			$match = get_match( $match_id );
			if ( ! $match ) {
				return __( 'Match not found', 'racketmanager' );
			}
			$is_update_allowed = $match->is_update_allowed();
			if ( empty( $template ) && $this->check_template( 'match-tournament' . $match->league->sport ) ) {
				$filename = 'match-tournament' . $match->league->sport;
			} elseif ( $this->check_template( 'match-tournament' . $template . '-' . $match->league->sport ) ) {
				$filename = 'match-tournament' . $template . '-' . $match->league->sport;
			} else {
				$filename = ( ! empty( $template ) ) ? 'match-tournament' . $template : 'match-tournament';
			}

			return $this->load_template(
				$filename,
				array(
					'tournament'        => $tournament,
					'match'             => $match,
					'is_update_allowed' => $is_update_allowed,
				)
			);
		}
	}
	/**
	 * Get tournaments function
	 *
	 * @return array
	 */
	private function get_tournaments() {
		global $racketmanager;
		return $racketmanager->get_tournaments(
			array(
				'orderby' => array(
					'season'         => 'DESC',
					'competition_id' => 'DESC',
				),
			)
		);
	}
	/**
	 * Function to display Tournament finals order of play
	 *
	 *    [orderofplay id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_order_of_play( $atts ) {
		global $racketmanager, $wp;
		wp_verify_nonce( 'order-of-play' );
		$args          = shortcode_atts(
			array(
				'id'       => '',
				'template' => '',
			),
			$atts
		);
		$tournament_id = $args['id'];
		$template      = $args['template'];
		$tournament    = get_tournament( $tournament_id );
		if ( ! $tournament ) {
			return esc_html_e( 'Tournament not found', 'racketmanager' );
		}
		$order_of_play           = array();
		$order_of_play['times']  = array();
		$order_of_play['courts'] = array();
		foreach ( $tournament->orderofplay as $courts ) {
			$order_of_play['courts'][ $courts['court'] ] = array();
			foreach ( $courts['matches'] as $match_id ) {
				$final_match = new \stdClass();
				if ( $match_id ) {
					$match                 = get_match( $match_id );
					$final_match->id       = $match_id;
					$final_match->time     = $match->hour . ':' . $match->minutes;
					$final_match->league   = $match->league->title;
					$final_match->location = $match->location;
					$final_match->winner   = $match->winner_id;

					$time = $final_match->time;
					if ( ! in_array( $time, $order_of_play['times'], true ) ) {
						$order_of_play['times'][] = $time;
					}
					// now just add the row data.
					$order_of_play['courts'][ $courts['court'] ][ $time ][] = $final_match;
				}
			}
		}
		$filename = ( ! empty( $template ) ) ? 'orderofplay-' . $template : 'orderofplay';

		return $this->load_template(
			$filename,
			array(
				'tournament'    => $tournament,
				'order_of_play' => $order_of_play,
			)
		);
	}
}
