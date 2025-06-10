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

		add_shortcode( 'players', array( &$this, 'show_players' ) );
		add_shortcode( 'player', array( &$this, 'show_player' ) );

		add_shortcode( 'competition-entry', array( &$this, 'show_competition_entry' ) );
		add_shortcode( 'competition-entry-payment', array( &$this, 'show_competition_entry_payment' ) );
		add_shortcode( 'competition-entry-payment-complete', array( &$this, 'show_competition_entry_payment_complete' ) );

		add_shortcode( 'favourites', array( &$this, 'show_favourites' ) );
		add_shortcode( 'invoice', array( &$this, 'show_invoice' ) );
		add_shortcode( 'messages', array( &$this, 'show_messages' ) );
		add_shortcode( 'memberships', array( &$this, 'show_memberships' ) );
		add_shortcode( 'search-players', array( &$this, 'show_player_search' ) );
		add_shortcode( 'team-order', array( &$this, 'show_team_order' ) );
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
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_daily_matches( array $atts ): string {
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
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_latest_results( array $atts ): string {
		global $racketmanager, $wp;

		$args             = shortcode_atts(
			array(
				'competition_type' => 'league',
				'template'         => 'results',
				'days'             => 7,
				'club'             => '',
				'competition_id'   => '',
				'header_level'     => 1,
				'age_group'        => false,
			),
			$atts
		);
		$competition_type = $args['competition_type'];
		$template         = $args['template'];
		$days             = $args['days'];
		$club_id          = $args['club'];
		$competition_id   = $args['competition_id'];
		$header_level     = $args['header_level'];
		$age_group        = $args['age_group'];
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
		if ( isset( $wp->query_vars['age_group'] ) ) {
			$age_group = get_query_var( 'age_group' );
		}
		$time         = 'latest';
		$matches      = $racketmanager->get_matches(
			array(
				'days'             => $days,
				'competition_type' => $competition_type,
				'time'             => $time,
				'history'          => $days,
				'club'             => $club_id,
				'competition_id'   => $competition_id,
				'age_group'        => $age_group,
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
	 * Function to display Players
	 *
	 *  [[players] template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_players( array $atts ): string {
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
	 * @return string content
	 */
	public function show_player( array $atts ): string {
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
	 * Function to display Competition Entry Page
	 *
	 *    [competition-entry id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_competition_entry( array $atts ): string {
		$args             = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template           = $args['template'];
		$valid              = true;
		$is_tournament      = false;
		$competition_name   = get_query_var( 'competition_name' );
		$competition_name   = un_seo_url( $competition_name );
        $tournament_name    = null;
        $season             = null;
        $competition_season = null;
        $club               = null;
        $tournament         = null;
        $player             = null;
        $msg                = null;
		if ( $competition_name ) {
			$type = get_query_var( 'competition_type' );
			if ( 'tournament' === $type ) {
				$is_tournament   = true;
				$tournament_name = $competition_name;
			}
		} else {
			$tournament_name = get_query_var( 'tournament' );
			if ( $tournament_name ) {
				$tournament_name = un_seo_url( $tournament_name );
				$is_tournament   = true;
			} else {
				$valid = false;
				$msg   = __( 'No competition name specified', 'racketmanager' );
			}
		}
		if ( $is_tournament ) {
			$tournament = get_tournament( $tournament_name, 'name' );
			if ( $tournament ) {
				$is_tournament = true;
			} else {
				$valid = false;
				$msg   = __( 'Tournament not found specified', 'racketmanager' );
			}
		}
		if ( $valid ) {
			if ( $is_tournament ) {
				$competition_ref    = $tournament->competition_id;
				$competition_lookup = null;
			} else {
				$competition_ref    = $competition_name;
				$competition_lookup = 'name';
			}
			$competition = get_competition( $competition_ref, $competition_lookup );
			if ( $competition ) {
				if ( $competition->is_tournament ) {
					$player_id = get_query_var( 'player_id' );
					if ( $player_id ) {
						$player_id = un_seo_url( $player_id );
						$player    = get_player( $player_id, 'name' );
					} else {
						$player_id = wp_get_current_user()->ID;
						$player    = get_player( $player_id );
					}
					if ( $player ) {
						if ( empty( $tournament ) ) {
							$tournament = null;
						}
					} else {
						$valid = false;
						$msg   = __( 'Player not found', 'racketmanager' );
					}
				} else {
					$season = get_query_var( 'season' );
					if ( $season ) {
						$competition_season = $competition->seasons[$season] ?? null;
						if ( $competition_season ) {
							if ( ! empty( $competition_season['venue'] ) ) {
								$venue_club = get_club( $competition_season['venue'] );
								if ( $venue_club ) {
									$competition_season['venue_name'] = $venue_club->shortcode;
								}
							}
							$club_name = get_query_var( 'club_name' );
							if ( $club_name ) {
								$club_name = un_seo_url( $club_name );
								$club      = get_club( $club_name, 'shortcode' );
								if ( $club ) {
									//check user authorised for club
									$can_enter = $this->club_selection_available( $competition, $club->id );
									if ( ! $can_enter ) {
										$valid = false;
										$msg   = __( 'User not authorised for club entry for this competition', 'racketmanager' );
									}
								} else {
									$valid = false;
									$msg   = __( 'Club not found', 'racketmanager' );
								}
							} else {
								$club_choice = $this->show_club_selection( $competition, $season, $competition_season );
								if ( ! $club_choice ) {
									$valid = false;
									$msg   = __( 'No club specified', 'racketmanager' );
								}
							}
						} else {
							$valid = false;
							$msg   = __( 'Season not found for competition', 'racketmanager' );
						}
					} else {
						$valid = false;
						$msg   = __( 'No season specified', 'racketmanager' );
					}
					}
			} else {
				$valid = false;
				$msg   = __( 'Competition not found', 'racketmanager' );
			}
		}
		if ( $valid ) {
			if ( ! empty( $club_choice ) ) {
				$output = $club_choice;
			} else {
				$output = match ( $competition->type ) {
					'league'     => $this->show_league_entry( $competition, $season, $competition_season, $club, $template ),
					'cup'        => $this->show_cup_entry( $competition, $season, $competition_season, $club, $template ),
					'tournament' => $this->show_tournament_entry( $tournament, $player, $template ),
					default      => $this->return_error( __('Invalid competition type specified', 'racketmanager') ),
				};
			}
			return $output;
		} else {
			return $this->return_error( $msg );
		}
	}
	/**
	 * Function to check if club selection is available
	 *
	 * @param object $competition competition object.
	 * @param false|int $club_id (optional) club id.
	 * @return false|object|boolean|int|array of clubs or individual club or indicator if club entry allowed or number of clubs
	 */
	protected function club_selection_available( object $competition, false|int $club_id = false ): object|int|bool|array {
		global $racketmanager;
		$clubs        = null;
		$user         = wp_get_current_user();
		$userid       = $user->ID;
		$args['type'] = 'affiliated';
		if ( $club_id ) {
			$args['club']  = $club_id;
			$args['count'] = true;
		}
		if ( current_user_can( 'manage_racketmanager' ) ) {
			$clubs = $racketmanager->get_clubs( $args );
		} else {
			$competition_options = $racketmanager->get_options( $competition->type );
			if ( $competition_options ) {
				$entry_option = $competition_options['entry_level'] ?? null;
				if ( $entry_option ) {
					$args[ 'player_type' ] = $entry_option;
					$args[ 'player' ]      = $userid;
					$clubs = $racketmanager->get_clubs( $args );
				}
			}
		}
		if ( $clubs ) {
			if ( $club_id ) {
				return $clubs;
			} else {
				if ( 1 === count( $clubs ) ) {
					return $clubs[0];
				} else {
					return $clubs;
				}
			}
		} else {
			return false;
		}
	}
	/**
	 * Function to show club selection entry list
	 *
	 * @param object $competition competition object.
	 * @param string $season season name.
	 * @param array $competition_season competition season details.
	 * @return string|boolean screen or no details
	 */
	private function show_club_selection( object $competition, string $season, array $competition_season ): false|string {
		$clubs = $this->club_selection_available( $competition );
		if ( $clubs ) {
			return $this->load_template(
				'entry-form-clubs-list',
				array(
					'competition'        => $competition,
					'season'             => $season,
					'competition_season' => $competition_season,
					'clubs'              => $clubs,
				)
			);
		} else {
			return false;
		}
	}
	/**
	 * Function to display competition payment Page
	 *
	 * @param array $atts shortcode attributes.
	 * @return string the content
	 */
	public function show_competition_entry_payment( array $atts ): string {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template'  => '',
			),
			$atts
		);
		$template = $args['template'];
		$valid            = true;
        $msg              = null;
        $invoice_id       = null;
        $total_due        = null;
        $tournament_entry = null;
        $tournament       = null;
        $player           = null;
		$type             = get_query_var( 'competition_type' );
		if ( 'tournament' === $type ) {
			$tournament_name = get_query_var( 'tournament' );
			if ( $tournament_name ) {
				$tournament_name = un_seo_url( $tournament_name );
				$tournament      = get_tournament( $tournament_name, 'name' );
				if ( $tournament ) {
					$charge_key = $tournament->competition_id . '_' . $tournament->season;
					$charge     = get_charge( $charge_key );
					if ( $charge ) {
						$player_id = wp_get_current_user()->ID;
						$player    = get_player( $player_id );
						if ( $player ) {
							$args['charge']       = $charge->id;
							$args['player']       = $player_id;
							$args['status']       = 'open';
							$outstanding_payments = $racketmanager->get_invoices( $args );
							$total_due            = 0;
							foreach ( $outstanding_payments as $invoice ) {
								$total_due += $invoice->amount;
								$invoice_id = $invoice->id;
							}
							$search           = $tournament->id . '_' . $player->id;
							$tournament_entry = get_tournament_entry( $search, 'key' );
						} else {
							$valid = false;
							$msg   = __( 'Player not found', 'racketmanager' );
						}
					} else {
						$valid = false;
						$msg   = __( 'Charge not found', 'racketmanager' );
					}
				} else {
					$valid = false;
					$msg   = __( 'Tournament not found', 'racketmanager' );
				}
			} else {
				$valid = false;
				$msg   = __( 'No tournament name specified', 'racketmanager' );
			}
		}
		if ( $valid ) {
			$stripe_details = new Racketmanager_Stripe();
			$filename       = ( ! empty( $template ) ) ? 'tournament-payment-' . $template : 'tournament-payment';

			return $this->load_template(
				$filename,
				array(
					'tournament'       => $tournament,
					'player'           => $player,
					'tournament_entry' => $tournament_entry,
					'total_due'        => $total_due,
					'invoice_id'       => $invoice_id,
					'stripe'           => $stripe_details,
				),
				'entry'
			);
		} else {
			return $this->return_error( $msg );
		}
	}
	/**
	 * Function to display competition payment completion Page
	 *
	 * @return string the content
	 */
	public function show_competition_entry_payment_complete(): string {
		$valid            = true;
        $msg              = null;
        $tournament       = null;
        $tournament_entry = null;
        $player           = null;
		$type             = get_query_var( 'competition_type' );
		if ( 'tournament' === $type ) {
			$tournament_name = get_query_var( 'tournament' );
			if ( $tournament_name ) {
				$tournament_name = un_seo_url( $tournament_name );
				$tournament      = get_tournament( $tournament_name, 'name' );
				if ( $tournament ) {
					$player_id = wp_get_current_user()->ID;
					$player    = get_player( $player_id );
					if ( $player ) {
						$search           = $tournament->id . '_' . $player->id;
						$tournament_entry = get_tournament_entry( $search, 'key' );
					} else {
						$valid = false;
						$msg   = __( 'Player not found', 'racketmanager' );
					}
				} else {
					$valid = false;
					$msg   = __( 'Tournament not found', 'racketmanager' );
				}
			} else {
				$valid = false;
				$msg   = __( 'No tournament name specified', 'racketmanager' );
			}
		}
		if ( $valid ) {
			$filename = ( ! empty( $template ) ) ? 'tournament-payment-complete-' . $template : 'tournament-payment-complete';

			return $this->load_template(
				$filename,
				array(
					'tournament'       => $tournament,
					'player'           => $player,
					'tournament_entry' => $tournament_entry,
				),
				'entry'
			);
		} else {
			return $this->return_error( $msg );
		}
	}
	/**
	 * Function to display Cup Entry Page
	 *
	 * @param object $competition competition object.
	 * @param string $season season.
	 * @param array $competition_season competition season.
	 * @param object $club club object.
	 * @param string $template template name.
	 * @return string the content
	 */
	private function show_cup_entry( object $competition, string $season, array $competition_season, object $club, string $template ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="contact-login-msg">You need to <a href="' . wp_login_url() . '">log in</a> to enter cups</p>';
		}
		$valid = true;
        $msg   = null;
		if ( ! $club ) {
			$valid = false;
			$msg   = __( 'Club not found', 'racketmanager' );
		}
		if ( ! $competition ) {
			$valid = false;
			$msg   = __( 'Cup not found', 'racketmanager' );
		}
		if ( ! $season ) {
			$valid = false;
			$msg   = __( 'Season not found', 'racketmanager' );
		}
		if ( $valid ) {
			$events = $competition->get_events();
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
					$event_team->team_info     = $event->get_team_info( $event_team->team_id );
					$event->team               = $event_team;
					$event->status             = 'checked';
					$club->entry[ $event->id ] = $event;
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
					'club'               => $club,
					'events'             => $events,
					'ladies_teams'       => $ladies_teams,
					'mens_teams'         => $mens_teams,
					'mixed_teams'        => $mixed_teams,
					'season'             => $season,
					'competition'        => $competition,
					'competition_season' => $competition_season,
					'weekdays'           => $weekdays,
				),
				'entry'
			);
		} else {
			return $this->return_error( $msg );
		}
	}
	/**
	 * Function to display league Entry Page
	 *
	 * @param object $competition competition.
	 * @param string $season season.
	 * @param array  $competition_season competition season.
	 * @param object $club club.
	 * @param string $template template name.
	 * @return string content
	 */
	private function show_league_entry( object $competition, string $season, array $competition_season, object $club, string $template ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="contact-login-msg">You need to <a href="' . wp_login_url() . '">log in</a> to enter leagues</p>';
		}
		$valid = true;
        $msg   = null;
		if ( ! $club ) {
			$valid = false;
			$msg   = __( 'Club not found', 'racketmanager' );
		}
		if ( ! $competition ) {
			$valid = false;
			$msg   = __( 'League not found', 'racketmanager' );
		}
		if ( ! $season ) {
			$valid = false;
			$msg   = __( 'Season not found', 'racketmanager' );
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
					$found = in_array( $team->id, array_column( $event->event_teams, 'team_id' ) );
					if ( false !== $found ) {
						unset( $event->teams[ $key ] );
					} else {
						$event_team            = new stdClass();
						$event_team->team_id   = $team->id;
						$event_team->name      = $team->title;
						$event_team->league_id = 0;
						$event_team->status    = null;
						$event->event_teams[]  = $event_team;
					}
					++$key;
				}
				$event_team            = new stdClass();
				$event_team->team_id   = 0;
				$event_team->name      = __( 'New team', 'racketmanager' );
				$event_team->league_id = 0;
				$event_team->status    = null;
				$event->event_teams[]  = $event_team;
				$events[ $i ]          = $event;
				if ( ! empty( $event->status ) ) {
					$club->entry[ $event->id ] = true;
				}
			}
			$filename = ( ! empty( $template ) ) ? 'entry-league-' . $template : 'entry-league';
			return $this->load_template(
				$filename,
				array(
					'club'               => $club,
					'competition'        => $competition,
					'events'             => $events,
					'season'             => $season,
					'competition_season' => $competition_season,
				),
				'entry'
			);
		} else {
			return $this->return_error( $msg );
		}
	}
	/**
	 * Function to display Tournament Entry Page
	 *
	 * @param object $tournament tournament object.
	 * @param object|null $player player object.
	 * @param string|null $template template name.
	 * @return string content
	 */
	private function show_tournament_entry( object $tournament, object $player = null, string $template = null ): string {
		global $racketmanager;
		if ( ! $tournament ) {
			return $this->return_error( __( 'Tournament not found', 'racketmanager' ) );
		}
		$player->firstname = get_user_meta( $player->ID, 'first_name', true );
		$player->surname   = get_user_meta( $player->ID, 'last_name', true );
		$player->contactno = get_user_meta( $player->ID, 'contactno', true );
		$player->gender    = get_user_meta( $player->ID, 'gender', true );
        if ( empty( $player->year_of_birth ) ) {
            $player_age = 0;
        } else {
			$player_age = substr( $tournament->date, 0, 4 ) - intval( $player->year_of_birth );
        }
		$tournament->fees     = $tournament->get_fees();
		$args['player']       = $player->id;
		$args['status']       = 'paid';
		$tournament->payments = $tournament->get_payments( $args );

		$events = $tournament->get_events();
		$c      = 0;
		foreach ( $events as $event ) {
			$event       = get_event( $event );
			$entry_valid = false;
			if ( 'M' === $player->gender ) {
				if ( ! str_starts_with( $event->type, 'W' ) && ! str_starts_with( $event->type, 'G' ) ) {
					$entry_valid = true;
				}
			} elseif ( 'F' === $player->gender ) {
				if ( ! str_starts_with( $event->type, 'M' ) && ! str_starts_with( $event->type, 'B' ) ) {
					$entry_valid = true;
				}
			}
			if ( $entry_valid ) {
				if ( empty( $event->age_limit ) || 'open' === $event->age_limit ) {
					$entry_valid = true;
				} elseif ( empty( $player_age ) ) {
					$entry_valid = false;
				} elseif ( $event->age_limit >= 30 ) {
					$age_limit = $event->age_limit;
					if ( 'F' === $player->gender && ! empty( $event->age_offset ) ) {
						$age_limit = $event->age_limit - $event->age_offset;
					}
					if ( $player_age < $age_limit ) {
						$entry_valid = false;
					} else {
						$entry_valid = true;
					}
				} elseif ( $player_age > $event->age_limit ) {
					$entry_valid = false;
				} else {
					$entry_valid = true;
				}
			}
			if ( $entry_valid ) {
				$player_entry = new stdClass();
				$teams        = $event->get_teams(
					array(
						'player' => $player->ID,
						'season' => $tournament->season,
					)
				);
				if ( $teams ) {
					$team                  = $teams[0];
					$player_entry->team_id = $team->id;
					$p                     = 1;
					foreach ( $team->players as $team_player ) {
						if ( $team_player->id !== $player->ID ) {
							$player_entry->partner    = $team_player;
							$player_entry->partner_id = $team_player->id;
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
			++$c;
		}

		$club_memberships = $racketmanager->get_club_players(
			array(
				'player' => $player->ID,
				'active' => true,
			)
		);
		$search           = $tournament->id . '_' . $player->id;
		$tournament_entry = get_tournament_entry( $search, 'key' );
        if ( $tournament_entry ) {
            $player->tournament_entry = $tournament_entry;
        }

		$filename = ( ! empty( $template ) ) ? 'entry-tournament-' . $template : 'entry-tournament';

		return $this->load_template(
			$filename,
			array(
				'tournament'       => $tournament,
				'events'           => $events,
				'player'           => $player,
				'club_memberships' => $club_memberships,
				'season'           => $tournament->season,
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
	 * @return string content
	 */
	public function show_favourites( array $atts ): string {
		$args = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		if ( ! is_user_logged_in() ) {
			return $this->return_error( __( 'You must be logged in to view favourites', 'racketmanager' ) );
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
	 * @return string content
	 */
	public function show_invoice( array $atts ): string {
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
		return $this->return_error( __( 'No invoice found', 'racketmanager' ) );
	}
	/**
	 * Function to show messages
	 *
	 *    [messages template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_messages( array $atts ): string {
		$args = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		if ( ! is_user_logged_in() ) {
			return $this->return_error( __( 'You must be logged in to view messages', 'racketmanager' ) );
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
	 * @return string content
	 */
	public function show_memberships( array $atts ): string {
		$args = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		if ( ! is_user_logged_in() ) {
			return $this->return_error( __( 'You must be logged in to view memberships', 'racketmanager' ) );
		}
		$template = $args['template'];
		$player   = get_player( get_current_user_id() );
		if ( $player ) {
			$player->clubs         = $player->get_clubs( array( 'type' => 'active' ) );
			$player->clubs_archive = $player->get_clubs( array( 'type' => 'inactive' ) );
		} else {
			return $this->return_error( __( 'Player not found', 'racketmanager' ) );
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
	 * @return string content
	 */
	public function show_player_search( array $atts ): string {
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
	 * Function to show team order
	 *
	 *    [team-order]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_team_order( array $atts ): string {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template          = $args['template'];
		$club_args         = array();
		$club_args['type'] = 'affiliated';
		$clubs             = $racketmanager->get_clubs( $club_args );
		if ( ! $clubs ) {
			return $this->return_error( __( 'No clubs found', 'racketmanager' ) );
		}
		$event_args                    = array();
		$event_args['entry_type']      = 'team';
		$event_args['reverse_rubbers'] = true;
		$events                        = $racketmanager->get_events( $event_args );
		if ( ! $events ) {
			return $this->return_error( __( 'No events found', 'racketmanager' ) );
		}
		$event_types   = Racketmanager_Util::get_event_types();
		$age_groups   = Racketmanager_Util::get_age_groups();
		$filename     = ( ! empty( $template ) ) ? 'team-order-' . $template : 'team-order';

		return $this->load_template( $filename, array(
													  'clubs'  => $clubs,
													  'events' => $events,
													  'event_types' => $event_types,
													  'age_groups'  => $age_groups,
													  )
									);
	}
	/**
	 * Load template for user display. First the current theme directory is checked for a template
	 * before defaulting to the plugin
	 *
	 * @param string $template Name of the template file (without extension).
	 * @param array $vars Array of variables name=>value available to display code (optional).
	 * @param false|string $template_type Type of content template (email, page).
	 * @return string the content
	 */
	public function load_template( string $template, array $vars = array(), false|string $template_type = false ): string {
		if ( $template_type ) {
			$template_dir = match ($template_type) {
				'competition' => 'templates/competition',
				'event'       => 'templates/event',
				'email'       => 'templates/email',
				'entry'       => 'templates/entry',
				'form'        => 'templates/forms',
				'includes'    => 'templates/includes',
				'page'        => 'templates/page',
				'tournament'  => 'templates/tournament',
				'account'     => 'templates/account',
				'league'      => 'templates/league',
				'club'        => 'templates/club',
				default       => 'templates',
			};
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
	 * @param string|null $directory optional directory name.
	 * @return boolean
	 */
	public function check_template( string $template, string $directory = null ): bool {
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
	 * @return object
	 */
	public function get_league( int $league_id ): object {
		global $league;

		if ( 0 === $league_id ) {
			$league = get_league();
		} else {
			$league = get_league( $league_id );
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
	public function get_draw( object $event, string $season ): array {
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
	/**
	 * Return error function
	 *
	 * @param string $msg message to display.
	 * @return string output html modal
	 */
	public function return_error(string $msg ): string {
		ob_start();
		?>
		<div>
			<div class="alert_rm alert--danger">
				<div class="alert__body">
					<div class="alert__body-inner">
						<span><?php echo esc_html( $msg ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}
