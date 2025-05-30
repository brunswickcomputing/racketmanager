<?php
/**
 * Racketmanager_Shortcodes_League API: Shortcodes_League class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/League
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Racketmanager_Shortcodes_League object
 */
class Racketmanager_Shortcodes_League extends RacketManager_Shortcodes {
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'championship', array( &$this, 'show_championship' ) );
		add_shortcode( 'leaguearchive', array( &$this, 'show_archive' ) );
		add_shortcode( 'standings', array( &$this, 'showStandings' ) );
		add_shortcode( 'crosstable', array( &$this, 'showCrosstable' ) );
		add_shortcode( 'matches', array( &$this, 'show_matches' ) );
		add_shortcode( 'match', array( &$this, 'show_match' ) );
		add_shortcode( 'teams', array( &$this, 'show_teams' ) );
		add_shortcode( 'league-players', array( &$this, 'show_league_players' ) );
	}

	/**
	 * Display Championship
	 *
	 *    [championship league_id="1" template="name"]
	 *
	 * - league_id is the ID of league
	 * - season: display specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	 *
	 * @param array $atts shortcode attributes.
	 * @return false|string
	 */
	public function show_championship( array $atts ): false|string {
		global $league;

		$args      = shortcode_atts(
			array(
				'league_id' => 0,
				'template'  => '',
				'season'    => false,
			),
			$atts
		);
		$league_id = $args['league_id'];
		$template  = $args['template'];
		$season    = $args['season'];
		$league    = $this->get_league( $league_id );
		if ( ! $league ) {
			return false;
		}
		$league->set_template( 'championship', $template );
		if ( ! $season ) {
			$season = $league->get_season();
		}
		$league->season = $season;
		$finals         = array();
		foreach ( array_reverse( $league->championship->get_finals() ) as $f => $final ) {
			$data              = new stdClass();
			$data->key         = $final['key'];
			$data->name        = $final['name'];
			$data->num_matches = $final['num_matches'];
			$data->round       = $final['round'];
			$data->rowspan     = ( $league->championship->num_teams_first_round / 2 >= 4 ) ? ceil( 4 / $final['num_matches'] ) : ceil( ( $league->championship->num_teams_first_round / 2 ) / $final['num_matches'] );

			$matches_raw = $league->get_matches(
				array(
					'final'   => $final['key'],
					'orderby' => array( 'id' => 'ASC' ),
				)
			);

			$matches = array();
			$i       = 1;
			foreach ( $matches_raw as $match ) {
				$match             = get_match( $match );
				$home_title        = $match->teams['home']->title;
				$away_title        = $match->teams['away']->title;
				$match->title      = sprintf( '%s - %s', $home_title, $away_title );
				$match->home_title = $home_title;
				$match->away_title = $away_title;

				$match->num_rubbers = ($league->num_rubbers ?? null);
				$match->num_sets    = ($league->num_sets ?? null);

				if ( isset( $match->num_rubbers ) && $match->num_rubbers > 0 ) {
					$rubbers = $match->get_rubbers();
					$r       = 1;
					foreach ( $rubbers as $rubber ) {
						$match->rubbers[ $r ] = $rubber;
						++$r;
					}
				}
				if ( empty( $match->location ) ) {
					$match->location = '';
				}
				$matches[ $i ] = $match;
				++$i;
			}

			if ( count( $matches ) ) {
				$data->matches = $matches;
				$finals[ $f ]  = $data;
			} else {
				unset( $finals[ $f ] );
			}
		}

		if ( empty( $template ) && $this->check_template( 'championship-' . $league->sport ) ) {
			$filename = 'championship-' . $league->sport;
		} else {
			$filename = ( ! empty( $template ) ) ? 'championship-' . $template : 'championship';
		}

		return $this->load_template(
			$filename,
			array(
				'league' => $league,
				'finals' => $finals,
			)
		);
	}

	/**
	 * Display Archive
	 *
	 *    [leaguearchive template=X]
	 *
	 * - template: template to use
	 * - standings: template for standings table
	 * - crosstable: template for crosstable
	 * - matches: template for matches
	 * - teams: template for teams
	 * - matches_template_type: type of match template
	 *
	 * @param array $atts shortcode attributes.
	 * @return false|string
	 */
	public function show_archive( array $atts ): false|string {
		global $league, $wp;

		$args                  = shortcode_atts(
			array(
				'league_name'           => '',
				'standings'             => 'last5',
				'crosstable'            => '',
				'matches'               => '',
				'teams'                 => 'list',
				'template'              => '',
				'tab'                   => null,
				'matches_template_type' => '',
			),
			$atts
		);
		$league_name           = $args['league_name'];
		$standings             = $args['standings'];
		$crosstable            = $args['crosstable'];
		$matches               = $args['matches'];
		$teams                 = $args['teams'];
		$template              = $args['template'];
		$tab                   = $args['tab'];
		$matches_template_type = $args['matches_template_type'];
		if ( ! $tab ) {
			if ( ! empty( $_GET['tab'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tab = wp_strip_all_tags( wp_unslash( $_GET['tab'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['tab'] ) ) {
				$tab = get_query_var( 'tab' );
			}
		}

		// Get League by Name.
		if ( ! $league_name ) {
			$league_name = get_query_var( 'league_name' );
			$league_name = str_replace( '-', ' ', $league_name );
		}
		// Get League ID from shortcode or $_GET.
		if ( $league_name ) {
			$league = get_league( $league_name );
		}
		if ( $league ) {
			// Get all leagues, needed for dropdown.
			$event = get_event( $league->event_id );
			if ( ! $event ) {
				return false;
			}
			$seasons = $event->seasons;
			$leagues = $event->get_leagues();
			$league->set_tab( true );
			$league->set_templates(
				array(
					'standings'  => $standings,
					'crosstable' => $crosstable,
					'matches'    => $matches,
					'teams'      => $teams,
				)
			);
			$league->matches_template_type = $matches_template_type;

			if ( empty( $template ) && $this->check_template( 'archive-' . $league->sport ) ) {
				$filename = 'archive-' . $league->sport;
			} else {
				$filename = ( ! empty( $template ) ) ? 'archive-' . $template : 'archive';
			}
			return $this->load_template(
				$filename,
				array(
					'leagues' => $leagues,
					'league'  => $league,
					'seasons' => $seasons,
					'tab'     => $tab,
				)
			);
		} else {
			$msg = __( 'League not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
	}

	/**
	 * Display League Standings
	 *
	 *    [standings league_id="1" template="name"]
	 *
	 * - league_id is the ID of league
	 * - season: display specific season (optional). default is current season
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "standings-template.php" (optional)
	 * - group: optional group
	 *
	 * @param array $atts shortcode attributes.
	 * @param boolean $widget widget indicator.
	 * @return false|string
	 */
	public function showStandings( array $atts, bool $widget = false ): false|string {
		global $league;
		$args = shortcode_atts(
			array(
				'league_id' => 0,
				'template'  => '',
				'season'    => false,
				'group'     => false,
			),
			$atts
		);

		$league_id = $args['league_id'];
		$template  = $args['template'];
		$season    = $args['season'];
		$group     = $args['group'];
		$league    = $this->get_league( $league_id );

		if ( ! $league ) {
			return false;
		}
		$league->set_template( 'standings', $template );
		$league->set_season( $season );
		$league->set_group( $group );

		$team_args           = array( 'orderby' => array( 'rank' => 'ASC' ) );
		$team_args['status'] = 1;
		if ( $group ) {
			$team_args['group'] = $group;
		}
		$teams = $league->get_league_teams( $team_args );
		if ( empty( $template ) ) {
			$filename = 'standings';
		} elseif ( ! $widget && $this->check_template( 'standings-' . $league->sport ) ) {
			$filename = 'standings-' . $league->sport;
		} else {
			$filename = 'standings-' . $template;
		}
		return $this->load_template(
			$filename,
			array(
				'league' => $league,
				'teams'  => $teams,
				'widget' => $widget,
				'season' => $season,
			)
		);
	}

	/**
	 * Display Crosstable
	 *
	 * [crosstable league_id="1" mode="popup" template="name"]
	 *
	 * - league_id is the ID of league to display
	 * - mode set to "popup" makes the crosstable be displayed in a thickbox popup window.
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "crosstable-template.php" (optional)
	 * - season: display crosstable of given season (optional)
	 * - group: show crosstable for specific group
	 *
	 * @param array $atts shortcode attributes.
	 * @return false|string
	 */
	public function showCrosstable( array $atts ): false|string {
		global $league;
		$args      = shortcode_atts(
			array(
				'league_id' => 0,
				'group'     => '',
				'template'  => '',
				'mode'      => '',
				'season'    => false,
			),
			$atts
		);
		$league_id = $args['league_id'];
		$group     = $args['group'];
		$template  = $args['template'];
		$mode      = $args['mode'];
		$season    = $args['season'];
		$league    = $this->get_league( $league_id );

		if ( ! $league ) {
			return false;
		}
		$league->set_template( 'crosstable', $template );
		$league->set_season( $season );
		$league->set_group( $group );

		$teams = $league->get_league_teams( array( 'orderby' => array( 'rank' => 'ASC' ) ) );

		if ( empty( $template ) && $this->check_template( 'crosstable-' . $league->sport ) ) {
			$filename = 'crosstable-' . $league->sport;
		} else {
			$filename = ( ! empty( $template ) ) ? 'crosstable-' . $template : 'crosstable';
		}
		return $this->load_template(
			$filename,
			array(
				'league' => $league,
				'teams'  => $teams,
				'mode'   => $mode,
				'season' => $season,
			),
			'league'
		);
	}
	/**
	 * Display League Matches
	 *
	 *    [matches league_id="1" mode="all|home|racing" template="name" roster=ID]
	 *
	 * - league_id is the ID of league
	 * - league_name: get league by name and not ID (optional)
	 * - mode can be either "all" or "home". If it is not specified the matches are displayed on a weekly basis
	 * - season: display specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	 * - roster is the ID of individual team member (currently only works with racing)
	 * - match_day: specific match day (integer)
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_matches( array $atts ): string {
		global $league;
		wp_verify_nonce( 'matches' );
		$args          = shortcode_atts(
			array(
				'league_id'                => 0,
				'team'                     => 0,
				'template'                 => 'daily',
				'template_type'            => 'tabs',
				'season'                   => '',
				'limit'                    => 'true',
				'matches'                  => 'current',
				'home_only'                => 'false',
				'group'                    => false,
				'time'                     => '',
				'show_match_day_selection' => '',
			),
			$atts
		);
		$league_id     = $args['league_id'];
		$template      = $args['template'];
		$template_type = $args['template_type'];
		$season        = $args['season'];
		$limit         = $args['limit'];
		$match_day     = $args['matches'];
		$time          = $args['time'];
		$league        = get_league( $league_id );
		if ( ! $league ) {
			$msg = __( 'League not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		$league->set_template( 'matches', $template );

		// Always disable match day in template to show matches by matchday.
		if ( $template === 'by_matchday' || ! empty( $time ) ) {
			$match_day = -1;
		}
		if ( empty( $match_day ) ) {
			$match_day = -1;
		}
		$league->set_season( $season );
		$league->set_match_day( $match_day );
		$league->matches_template_type = $template_type;
		$match_args = array( 'final' => '' );
		if ( 'false' === $limit ) {
			$match_args['limit'] = false;
		} elseif ( $limit && is_numeric( $limit ) ) {
			$match_args['limit'] = intval( $limit );
		}
		$match_args['time']      = $time;
		$match_args['home_only'] = false;
		// Get matches.
		$matches = $league->get_matches( $match_args );
		$league->set_num_matches();

		if ( -1 === $league->match_day ) {
			$league_matches = array();
			foreach ( $matches as $match ) {
				$key = $match->match_day;
				if ( false === array_key_exists( $key, $league_matches ) ) {
					$league_matches[ $key ] = array();
				}
				$league_matches[ $key ][] = $match;
			}
		} else {
			$league_matches = $matches;
		}
		$teams = $league->get_league_teams(
			array(
				'season'  => $season,
				'orderby' => array( 'title' => 'ASC' ),
			)
		);

		if ( empty( $template ) && $this->check_template( 'matches-' . $league->sport ) ) {
			$filename = 'matches-' . $league->sport;
		} elseif ( $this->check_template( 'matches-' . $template . '-' . $league->sport ) ) {
			$filename = 'matches-' . $template . '-' . $league->sport;
		} else {
			$filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches';
		}

		return $this->load_template(
			$filename,
			array(
				'league'  => $league,
				'matches' => $league_matches,
				'teams'   => $teams,
				'season'  => $season,
			)
		);
	}
	/**
	 * Display single match
	 *
	 * [match id="1" template="name"]
	 *
	 * - id is the ID of the match to display
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "match-template.php" (optional)
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_match( array $atts ): string {
		global $racketmanager, $match;
		$args        = shortcode_atts(
			array(
				'match_id' => 0,
				'player'   => '',
				'template' => '',
			),
			$atts
		);
		$match_id    = $args['match_id'];
		$player      = $args['player'];
		$template    = $args['template'];
		$action      = get_query_var( 'action' );
		$num_matches = null;
		$home_team_name = null;
		$away_team_name = null;
		$league         = null;
		$match_day      = null;
		$round          = null;
		$season         = null;
		$matches        = array();
		// Get Match ID from shortcode or $_GET.
		if ( ! $match_id ) {
			$match_id = get_query_var( 'match_id' );
		}
		if ( ! $match_id ) {
			$league_name = un_seo_url( get_query_var( 'league_name' ) );
			if ( $league_name ) {
				$league = get_league( $league_name );
				if ( $league ) {
					$home_team_name = un_seo_url( get_query_var( 'teamHome' ) );
					$away_team_name = un_seo_url( get_query_var( 'teamAway' ) );
					$match_day      = get_query_var( 'match_day' );
					$season         = get_query_var( 'season' );
					$round          = get_query_var( 'round' );
					$leg            = get_query_var( 'leg' );
					$match_args     = array();
					if ( $home_team_name ) {
						if ( $league->event->competition->is_player_entry ) {
							$home_team_name = $this->get_player_team_name( $home_team_name );
						}
						$home_team               = $racketmanager->get_team_id( $home_team_name );
						$match_args['home_team'] = $home_team;
					}
					if ( $away_team_name ) {
						if ( $league->event->competition->is_player_entry ) {
							$away_team_name = $this->get_player_team_name( $away_team_name );
						}
						$away_team               = $racketmanager->get_team_id( $away_team_name );
						$match_args['away_team'] = $away_team;
					}
					if ( $match_day ) {
						$match_args['match_day'] = $match_day;
					}
					if ( $round ) {
						if ( $league->is_championship ) {
							$match_args['final'] = $round;
						} else {
							$match_args['match_day'] = $round;
						}
					}
					if ( $season ) {
						$match_args['season'] = $season;
					}
					if ( $leg ) {
						$match_args['leg'] = $leg;
					}
					$matches = $league->get_matches( $match_args );
					if ( ! $matches ) {
						$msg = __( 'Match not found', 'racketmanager' );
						return $this->return_error( $msg );
					}
					$num_matches = count( $matches );
					if ( 1 === $num_matches ) {
						$match_id = $matches[0]->id;
					}
					if ( $player ) {
						$match->player = get_player( $player );
					}
				}
			}
		}
		if ( $match_id ) {
			$match = get_match( $match_id );
			if ( ! $match ) {
				$msg = __( 'Match not found', 'racketmanager' );
				return $this->return_error( $msg );
			}
			$event = get_event( $match->league->event_id );
			if ( ! $event ) {
				$msg = __( 'Event not found', 'racketmanager' );
				return $this->return_error( $msg );
			}
			$seasons           = $event->seasons;
			$leagues           = $event->get_leagues();
			$is_update_allowed = $match->is_update_allowed();
			if ( ! empty( $match->league->num_rubbers ) ) {
				$template = 'teams';
			} elseif ( 'tournament' === $match->league->event->competition->type ) {
				$template = 'tournament';
			} elseif ( $match->league->event->is_box ) {
				$template = 'tournament';
			}
			$template_array = array(
				'match'             => $match,
				'leagues'           => $leagues,
				'seasons'           => $seasons,
				'league'            => $match->league,
				'is_update_allowed' => $is_update_allowed,
			);
			if ( 'result' === $action ) {
				$age_limit  = isset( $match->league->event->age_limit ) ? sanitize_text_field( wp_unslash( $match->league->event->age_limit ) ) : null;
				$age_offset = isset( $match->league->event->age_offset ) ? intval( $match->league->event->age_offset ) : null;
				$template  .= '-' . $action;
				$home_club  = get_club( $match->teams['home']->club_id );
				$away_club  = get_club( $match->teams['away']->club_id );
				switch ( $match->league->type ) {
					case 'BD':
					case 'MD':
						$home_club_player['m'] = $home_club->get_players(
							array(
								'gender'     => 'M',
								'age_limit'  => $age_limit,
								'age_offset' => $age_offset,
							)
						);
						$away_club_player['m'] = $away_club->get_players(
							array(
								'gender'     => 'M',
								'age_limit'  => $age_limit,
								'age_offset' => $age_offset,
							)
						);
						break;
					case 'GD':
					case 'WD':
						$home_club_player['f'] = $home_club->get_players(
							array(
								'gender'     => 'F',
								'age_limit'  => $age_limit,
								'age_offset' => $age_offset,
							)
						);
						$away_club_player['f'] = $away_club->get_players(
							array(
								'gender'     => 'F',
								'age_limit'  => $age_limit,
								'age_offset' => $age_offset,
							)
						);
						break;
					case 'XD':
					case 'LD':
						$home_club_player['m'] = $home_club->get_players(
							array(
								'gender'     => 'M',
								'age_limit'  => $age_limit,
								'age_offset' => $age_offset,
							)
						);
						$home_club_player['f'] = $home_club->get_players(
							array(
								'gender'     => 'F',
								'age_limit'  => $age_limit,
								'age_offset' => $age_offset,
							)
						);
						$away_club_player['m'] = $away_club->get_players(
							array(
								'gender'     => 'M',
								'age_limit'  => $age_limit,
								'age_offset' => $age_offset,
							)
						);
						$away_club_player['f'] = $away_club->get_players(
							array(
								'gender'     => 'F',
								'age_limit'  => $age_limit,
								'age_offset' => $age_offset,
							)
						);
						break;
					default:
						$home_club_player['m'] = array();
						$home_club_player['f'] = array();
						$away_club_player['m'] = array();
						$away_club_player['f'] = array();
				}
				$template_array['home_club_player'] = $home_club_player;
				$template_array['away_club_player'] = $away_club_player;
			}
			if ( empty( $template ) && $this->check_template( 'match-' . $match->league->sport ) ) {
				$filename = 'match-' . $match->league->sport;
			} elseif ( $this->check_template( 'match-' . $template . '-' . $match->league->sport ) ) {
				$filename = 'match-' . $template . '-' . $match->league->sport;
			} else {
				$filename = ( ! empty( $template ) ) ? 'match-' . $template : 'match';
			}
		} elseif ( $num_matches ) {
			$filename       = 'match-list';
			$template_array = array(
				'matches'   => $matches,
				'home_team' => $home_team_name,
				'away_team' => $away_team_name,
				'league'    => $league,
				'match_day' => $match_day,
				'round'     => $round,
				'season'    => $season,
				'action'    => $action,
			);
		} else {
			$msg = __( 'Match not found', 'racketmanager' );
			return $this->return_error( $msg );
		}
		return $this->load_template( $filename, $template_array );
	}
	/**
	 * Get player team name function.
	 *
	 * @param string $team_name team name.
	 * @return string team name.
	 */
	private function get_player_team_name( string $team_name ): string {
		$team_names = explode( ' ', $team_name );
		if ( count( $team_names ) === 4 ) {
			$team_name = $team_names[0] . ' ' . $team_names[1] . ' / ' . $team_names[2] . ' ' . $team_names[3];
		} else {
			$team_name = $team_names[0] . ' / ' . $team_names[1];
		}
		return $team_name;
	}
	/**
	 * Display Team list
	 *
	 *    [teams league_id=ID template=X season=x]
	 *
	 * - league_id is the ID of league
	 * - season: use specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "teams-template.php" (optional)
	 * - group: show teams only from specific group
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_teams( array $atts ): string {
		global $league, $wp;
		$args      = shortcode_atts(
			array(
				'league_id' => 0,
				'template'  => '',
				'teams'     => null,
				'group'     => false,
			),
			$atts
		);
		$league_id = $args['league_id'];
		$template  = $args['template'];
		$group     = $args['group'];
		$team_id   = $args['teams'];
		if ( $league_id ) {
			$league = get_league( $league_id );
		}

		$league->set_template( 'teams', $template );
		$league->set_group( $group );
		$league_teams = array();
		if ( ! $team_id ) {
			if ( isset( $wp->query_vars['team'] ) ) {
				$team_id = un_seo_url( get_query_var( 'team' ) );
			}
		}
		if ( $team_id ) {
			$team = get_team( $team_id );
			if ( $team ) {
				$team->info      = $league->get_team_dtls( $team->id );
				$team->standings = $league->get_league_team( $team->id );
				if ( empty( $team->standings->rank ) ) {
					$msg = __( 'Team not found in league', 'racketmanager' );
					return $this->return_error( $msg );
				}
				$team->matches = $league->get_matches(
					array(
						'team_id'          => $team->id,
						'match_day'        => false,
						'limit'            => 'false',
						'reset_query_args' => true,
					)
				);
				$team->players = $league->get_players(
					array(
						'team'  => $team->id,
						'stats' => true,
					)
				);
				$league->team  = $team;
			}
		} else {
			$team_args = array( 'orderby' => array( 'title' => 'ASC' ) );
			if ( $group ) {
				$team_args['group'] = $group;
			}
			$teams = $league->get_league_teams( $team_args );
			foreach ( $teams as $team ) {
				$team->info     = $league->get_team_dtls( $team->id );
				$league_teams[] = $team;
			}
		}

		if ( empty( $template ) && $this->check_template( 'teams-' . $league->sport ) ) {
			$filename = 'teams-' . $league->sport;
		} else {
			$filename = ( ! empty( $template ) ) ? 'teams-' . $template : 'teams-list';
		}
		return $this->load_template(
			$filename,
			array(
				'league' => $league,
				'teams'  => $league_teams,
			)
		);
	}
	/**
	 * Function to display league Players
	 *
	 *  [teams league_id=ID template=X season=x]
	 *
	 * @param array $atts shortcode attributes.
	 * @return false|string content
	 */
	public function show_league_players( array $atts ): false|string {
		global $league, $wp;
		$args      = shortcode_atts(
			array(
				'league_id' => 0,
				'players'   => null,
				'template'  => '',
				'season'    => false,
			),
			$atts
		);
		$league_id = $args['league_id'];
		$player_id = $args['players'];
		$template  = $args['template'];
		$season    = $args['season'];
		$league    = $this->get_league( $league_id );

		if ( ! $league ) {
			return false;
		}
		$league->set_season( $season );
		if ( ! $player_id ) {
			if ( isset( $wp->query_vars['player_id'] ) ) {
				$player_id = un_seo_url( get_query_var( 'player_id' ) );
			}
		}
		if ( $player_id ) {
			if ( is_numeric( $player_id ) ) {
				$player = get_player( $player_id );
			} else {
				$player = get_player( $player_id, 'name' ); // get player by name.
			}
			if ( $player ) {
				$player->matches = $player->get_matches( $league, $league->current_season['name'], 'league' );
				$player->stats   = $player->get_stats();
				$league->player  = $player;
			} else {
				esc_html_e( 'Player not found', 'racketmanager' );
			}
		} else {
			$league->players = $league->get_players(
				array(
					'stats' => true,
				)
			);
		}
		if ( empty( $template ) && $this->check_template( 'players-' . $league->sport, 'league' ) ) {
			$filename = 'players-' . $league->sport;
		} else {
			$filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
		}

		return $this->load_template(
			$filename,
			array(
				'league' => $league,
			),
			'league'
		);
	}
}
