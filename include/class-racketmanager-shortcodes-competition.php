<?php
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
		add_shortcode( 'championship', array( &$this, 'showChampionship' ) );
		add_shortcode( 'event', array( &$this, 'show_event' ) );
		add_shortcode( 'competition', array( &$this, 'showCompetition' ) );
		add_shortcode( 'leaguearchive', array( &$this, 'showArchive' ) );
		add_shortcode( 'standings', array( &$this, 'showStandings' ) );
		add_shortcode( 'crosstable', array( &$this, 'showCrosstable' ) );
		add_shortcode( 'matches', array( &$this, 'show_matches' ) );
		add_shortcode( 'match', array( &$this, 'show_match' ) );
		add_shortcode( 'teams', array( &$this, 'show_teams' ) );
		add_shortcode( 'team', array( &$this, 'show_team' ) );
		add_shortcode( 'league-players', array( &$this, 'show_league_players' ) );
		add_shortcode( 'event-standings', array( &$this, 'show_event_standings' ) );
		add_shortcode( 'event-draw', array( &$this, 'show_event_draw' ) );
		add_shortcode( 'event-matches', array( &$this, 'show_event_matches' ) );
		add_shortcode( 'event-clubs', array( &$this, 'show_event_clubs' ) );
		add_shortcode( 'event-teams', array( &$this, 'show_event_teams' ) );
		add_shortcode( 'event-players', array( &$this, 'show_event_players' ) );
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
	 * Display Championship
	 *
	 *    [championship league_id="1" template="name"]
	 *
	 * - league_id is the ID of league
	 * - season: display specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	 *
	 * @param array $atts shorcode attributes.
	 * @return string
	 */
	public function showChampionship( $atts ) {
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
			$season = $season['name'];
		}
		$league->season = $season;
		$finals         = array();
		foreach ( array_reverse( $league->championship->get_finals() ) as $f => $final ) {
			$data              = new \stdClass();
			$class             = 'alternate';
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
				$class             = ( ! isset( $class ) || 'alternate' === $class ) ? '' : 'alternate';
				$match->class      = $class;
				$home_title        = $match->teams['home']->title;
				$away_title        = $match->teams['away']->title;
				$match->title      = sprintf( '%s - %s', $home_title, $away_title );
				$match->home_title = $home_title;
				$match->away_title = $away_title;

				$match->num_rubbers = ( isset( $league->num_rubbers ) ? $league->num_rubbers : null );
				$match->num_sets    = ( isset( $league->num_sets ) ? $league->num_sets : null );

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
			return __( 'Event not found', 'racketmanager' );
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
			return __( 'Season not found for event', 'racketmanager' );
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
	 * Show Competition
	 *
	 * [competition_id=ID season=X template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function showCompetition( $atts ) {
		global $competition;
		wp_verify_nonce( 'crosstable' );
		$args           = shortcode_atts(
			array(
				'id'             => 0,
				'season'         => false,
				'template'       => '',
				'standingstable' => '',
				'template_type'  => null,
				'organisation'   => null,
			),
			$atts
		);
		$id             = $args['id'];
		$season         = $args['season'];
		$template       = $args['template'];
		$standingstable = $args['standingstable'];
		$template_type  = $args['template_type'];
		$organisation   = $args['organisation'];
		if ( $id ) {
			$event = get_event( $id );
		} else {
			$event = get_query_var( 'competition_name' );
			if ( $event ) {
				$event = str_replace( '-', ' ', $event );
				$event = get_event( $event, 'name' );
			}
		}

		if ( ! $event ) {
			return false;
		}
		$leagues = $event->get_leagues();
		if ( ! $season ) {
			if ( isset( $_GET['season'] ) && ! empty( $_GET['season'] ) ) {
				$season = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
			} elseif ( isset( $_GET['season'] ) ) {
				$season = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
			} else {
				$season = null !== get_query_var( 'season' ) ? get_query_var( 'season' ) : false;
			}
		}
		if ( empty( $template ) && $this->check_template( 'competition-' . $event->competition->sport ) ) {
			$filename = 'competition-' . $event->competition->sport;
		} else {
			$filename = ( ! empty( $template ) ) ? 'competition-' . $template : 'competition';
		}

		$seasons = $event->seasons;
		if ( ! $season ) {
			if ( $seasons ) {
				$season = $event->current_season['name'];
			} else {
				$season = '';
			}
		}

		return $this->load_template(
			$filename,
			array(
				'competition'        => $event,
				'leagues'            => $leagues,
				'seasons'            => $seasons,
				'curr_season'        => $season,
				'standings_template' => $standingstable,
				'organisation'       => $organisation,
				'template_type'      => $template_type,
			)
		);
	}
	/**
	 * Display Archive
	 *
	 *    [leaguearchive template=X]
	 *
	 * - template: teamplate to use
	 * - standingstable: template for standings table
	 * - crosstable: template for crosstable
	 * - matches: template for matches
	 * - teams: template for teams
	 * - matches_template_type: type of match template
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function showArchive( $atts ) {
		global $league, $wp;

		$args                  = shortcode_atts(
			array(
				'league_id'             => false,
				'competition_id'        => false,
				'league_name'           => '',
				'standingstable'        => 'last5',
				'crosstable'            => '',
				'matches'               => '',
				'teams'                 => 'list',
				'template'              => '',
				'tab'                   => null,
				'matches_template_type' => '',
			),
			$atts
		);
		$league_id             = $args['league_id'];
		$competition_id        = $args['competition_id'];
		$league_name           = $args['league_name'];
		$standingstable        = $args['standingstable'];
		$crosstable            = $args['crosstable'];
		$matches               = $args['matches'];
		$teams                 = $args['teams'];
		$template              = $args['template'];
		$tab                   = $args['tab'];
		$matches_template_type = $args['matches_template_type'];
		if ( ! $tab ) {
			if ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
					'standingstable' => $standingstable,
					'crosstable'     => $crosstable,
					'matches'        => $matches,
					'teams'          => $teams,
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
	 * @param array   $atts shortcode attributes.
	 * @param boolean $widget widget indicator.
	 * @return string
	 */
	public function showStandings( $atts, $widget = false ) {
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
		$league->set_template( 'standingstable', $template );
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
	 * @return string
	 */
	public function showCrosstable( $atts ) {
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
	 * - mode can be either "all" or "home". For racing it must be "racing". If it is not specified the matches are displayed on a weekly basis
	 * - season: display specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	 * - roster is the ID of individual team member (currently only works with racing)
	 * - match_day: specific match day (integer)
	 *
	 * @param array $atts shorcode attributes.
	 * @return string
	 */
	public function show_matches( $atts ) {
		global $league, $racketmanager;
		wp_verify_nonce( 'matches' );
		$args                     = shortcode_atts(
			array(
				'league_id'                => 0,
				'team'                     => 0,
				'template'                 => 'daily',
				'template_type'            => 'tabs',
				'season'                   => '',
				'limit'                    => 'true',
				'match_day'                => -1,
				'home_only'                => 'false',
				'group'                    => false,
				'time'                     => '',
				'show_team_selection'      => false,
				'show_match_day_selection' => '',
			),
			$atts
		);
		$league_id                = $args['league_id'];
		$team                     = $args['team'];
		$template                 = $args['template'];
		$template_type            = $args['template_type'];
		$season                   = $args['season'];
		$limit                    = $args['limit'];
		$match_day                = $args['match_day'];
		$group                    = $args['group'];
		$time                     = $args['time'];
		$show_team_selection      = $args['show_team_selection'];
		$show_match_day_selection = $args['show_match_day_selection'];
		$league                   = $this->get_league( $league_id );
		if ( ! $league ) {
			return false;
		}
		$league->set_template( 'matches', $template );

		// Always disable match day in template to show matches by matchday.
		if ( in_array( $template, array( 'by_matchday' ), true ) || ! empty( $time ) ) {
			$match_day = -1;
		}

		$league->set_matches_selection( $show_match_day_selection, $match_day, $show_team_selection, $team );

		$league->set_season( $season );
		$league->set_match_day( $match_day );
		$league->matches_template_type = $template_type;

		$matches    = false;
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
			),
			'ARRAY'
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
	 * @param array $atts shorcode attributes.
	 * @return string
	 */
	public function show_match( $atts ) {
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
		// Get Match ID from shortcode or $_GET.
		if ( ! $match_id ) {
			$match_id = get_query_var( 'match_id' );
		}
		if ( ! $match_id ) {
			$league_name = un_seo_url( get_query_var( 'league_name' ) );
			if ( $league_name ) {
				$league = get_league( $league_name, 'name' );
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
						$home_team               = $racketmanager->getteam_id( $home_team_name );
						$match_args['home_team'] = $home_team;
					}
					if ( $away_team_name ) {
						if ( $league->event->competition->is_player_entry ) {
							$away_team_name = $this->get_player_team_name( $away_team_name );
						}
						$away_team               = $racketmanager->getteam_id( $away_team_name );
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
						return esc_html_e( 'Match not found', 'racketmanager' );
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
				return __( 'Match not found', 'racketmanager' );
			}
			$event = get_event( $match->league->event_id );
			if ( ! $event ) {
				return __( 'Event not found', 'racketmanager' );
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
			return esc_html__( 'Match not found', 'racketmanager' );
		}
		return $this->load_template( $filename, $template_array );
	}
	/**
	 * Get player team name function.
	 *
	 * @param string $team_name team name.
	 * @return string team name.
	 */
	private function get_player_team_name( $team_name ) {
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
	public function show_teams( $atts ) {
		global $league, $wp;
		$args      = shortcode_atts(
			array(
				'league_id' => 0,
				'template'  => '',
				'team'      => null,
				'group'     => false,
			),
			$atts
		);
		$league_id = $args['league_id'];
		$template  = $args['template'];
		$group     = $args['group'];
		$team      = $args['team'];
		$league    = $this->get_league( $league_id );

		$league->set_template( 'teams', $template );
		$league->set_group( $group );
		$league_teams = array();
		if ( isset( $wp->query_vars['team'] ) ) {
			$team = un_seo_url( get_query_var( 'team' ) );
		}
		if ( $team ) {
			$team = get_team( $team );
			if ( $team ) {
				$team->info      = $league->get_team_dtls( $team->id );
				$team->standings = $league->get_league_team( $team->id );
				if ( empty( $team->standings->rank ) ) {
					return __( 'Team not found in league', 'racketmanager' );
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
	 * Display Team Info Page
	 *
	 *    [team id=ID template=X]
	 *
	 * - id: the team ID
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "team-template.php" (optional)
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_team( $atts ) {
		global $league;
		$args     = shortcode_atts(
			array(
				'id'       => 0,
				'template' => '',
			),
			$atts
		);
		$id       = $args['id'];
		$template = $args['template'];
		$league   = get_league();
		if ( ! is_null( $league ) ) {
			$team = $league->get_league_team( intval( $id ) );
		} else {
			$team = get_league_team( intval( $id ) );
		}
		if ( empty( $template ) && $this->check_template( 'team-' . $league->sport ) ) {
			$filename = 'team-' . $league->sport;
		} else {
			$filename = ( ! empty( $template ) ) ? 'team-' . $template : 'team';
		}
		return $this->load_template(
			$filename,
			array(
				'league' => $league,
				'team'   => $team,
			),
			'league'
		);
	}
	/**
	 * Function to display league Players
	 *
	 *  [teams league_id=ID template=X season=x]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_league_players( $atts ) {
		global $league, $wp;
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
		$league->set_season( $season );
		$matches = array();
		$player  = null;
		$player  = null;
		if ( isset( $wp->query_vars['player_id'] ) ) {
			$player = un_seo_url( get_query_var( 'player_id' ) );
		}
		if ( $player ) {
			$player = get_player( $player, 'name' ); // get player by name.
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
	/**
	 * Function to display event standings
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
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
			return __( 'Event not found', 'racketmanager' );
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
	 * @return the content
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
			return __( 'Event not found', 'racketmanager' );
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
	 * @return the content
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
			return __( 'Event not found', 'racketmanager' );
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
	 * @return the content
	 */
	public function show_event_clubs( $atts ) {
		global $wp;
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
			return __( 'Event not found', 'racketmanager' );
		}
		$club       = null;
		$event_club = null;
		$event->set_season( $season );
		if ( isset( $wp->query_vars['club_name'] ) ) {
			$club = get_query_var( 'club_name' );
			$club = str_replace( '-', ' ', $club );
		}
		if ( $club ) {
			$event_club = get_club( $club, 'shortcode' );
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
				return esc_html__( 'Club not found', 'racketmanager' );
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
	 * @return the content
	 */
	public function show_event_teams( $atts ) {
		global $wp;
		$args     = shortcode_atts(
			array(
				'id'       => false,
				'template' => '',
			),
			$atts
		);
		$event_id = $args['id'];
		$template = $args['template'];
		$event    = get_event( $event_id );
		if ( ! $event ) {
			return esc_html_e( 'Event not found', 'racketmanager' );
		}
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
	 * @return the content
	 */
	public function show_event_players( $atts ) {
		global $wp;
		$args     = shortcode_atts(
			array(
				'id'       => 0,
				'season'   => null,
				'template' => '',
			),
			$atts
		);
		$event_id = $args['id'];
		$season   = $args['season'];
		$template = $args['template'];
		$event    = get_event( $event_id );
		if ( $event ) {
			$event->set_season( $season );
			$player         = null;
			$event->players = array();
			if ( isset( $wp->query_vars['player_id'] ) ) {
				$player = un_seo_url( get_query_var( 'player_id' ) );
			}
			if ( $player ) {
				$player = get_player( $player, 'name' ); // get player by name.
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
			return __( 'Event not found', 'racketmanager' );
		}
	}
	/**
	 * Show competitions function
	 *
	 * @param array $atts atrributes.
	 * @return string display output
	 */
	public function show_competitions( $atts ) {
		global $wp, $racketmanager;
		$args     = shortcode_atts(
			array(
				'type'     => false,
				'template' => '',
			),
			$atts
		);
		$type     = $args['type'];
		$template = $args['template'];
		if ( ! $type ) {
			if ( isset( $_GET['competition_type'] ) && ! empty( $_GET['type'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$type = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['type'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['type'] ) ) {
				$type = get_query_var( 'type' );
			}
			$type = un_seo_url( $type );
		}
		if ( ! $type ) {
			return esc_html_e( 'Competition type not set', 'racketmanager' );
		}
		$user_competitions = null;
		if ( 'tournament' === $type ) {
			$tournaments  = $racketmanager->get_tournaments( array( 'orderby' => array( 'date' => 'DESC' ) ) );
			$competitions = array();
			foreach ( $tournaments as $tournament ) {
				$tournament->type     = $type;
				$tournament->date_end = $tournament->date;
				$competitions[]       = $tournament;
			}
			if ( is_user_logged_in() ) {
				$player = get_player( get_current_user_id() );
				if ( $player ) {
					$user_competitions = $player->get_tournaments( array( 'type' => $type ) );
				}
			}
		} else {
			$competitions = $racketmanager->get_competitions( array( 'type' => $type ) );
			if ( is_user_logged_in() ) {
				$player = get_player( get_current_user_id() );
				if ( $player ) {
					$user_competitions = $player->get_competitions( array( 'type' => $type ) );
				}
			}
		}
		switch ( $type ) {
			case 'league':
				$competition_type = __( 'Leagues', 'racketmanager' );
				break;
			case 'cup':
				$competition_type = __( 'Cups', 'racketmanager' );
				break;
			case 'tournament':
				$competition_type = __( 'Tournaments', 'racketmanager' );
				break;
			default:
				$competition_type = __( 'Competitions', 'racketmanager' );
				break;
		}
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
	 * @param array $atts atrributes.
	 * @return string display output
	 */
	public function show_competition( $atts ) {
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
			if ( isset( $_GET['competition'] ) && ! empty( $_GET['competition'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
			return esc_html_e( 'Competition not found', 'racketmanager' );
		}
		if ( ! $season ) {
			if ( isset( $_GET['season'] ) && ! empty( $_GET['season'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$season = wp_strip_all_tags( wp_unslash( $_GET['season'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['season'] ) ) {
				$season = get_query_var( 'season' );
			}
		}
		if ( $season ) {
			$competition_season = isset( $competition->seasons[ $season ] ) ? $competition->seasons[ $season ] : null;
			if ( ! $competition_season ) {
				return esc_html_e( 'Season not found', 'racketmanager' );
			}
		} elseif ( empty( $competition->seasons ) ) {
				return esc_html_e( 'No seasons found for competition', 'racketmanager' );
		} else {
			$competition_season = $competition->current_season;
			$season             = $competition_season['name'];
		}
		if ( ! $tab ) {
			if ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$tab = wp_strip_all_tags( wp_unslash( $_GET['tab'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $wp->query_vars['tab'] ) ) {
				$tab = get_query_var( 'tab' );
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
	public function show_competition_overview( $atts ) {
		$args           = shortcode_atts(
			array(
				'id'       => false,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			return esc_html_e( 'Competition not found', 'racketmanager' );
		}
		$competition->events  = $competition->get_events();
		$competition->entries = $competition->get_teams(
			array(
				'count'  => true,
				'season' => $competition->current_season['name'],
				'status' => 1,
			)
		);

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
	public function show_competition_events( $atts ) {
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
			return __( 'Competition not found', 'racketmanager' );
		}
		if ( $season ) {
			$competition->set_season( $season );
		} else {
			$season = $competition->current_season['name'];
		}
		$competition->events = $competition->get_events();
		$i                   = 0;
		foreach ( $competition->events as $event ) {
			$event->entries            = $event->get_teams(
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
	public function show_competition_teams( $atts ) {
		$args           = shortcode_atts(
			array(
				'id'       => false,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			return esc_html_e( 'Competition not found', 'racketmanager' );
		}
		$competition->teams = $competition->get_teams(
			array(
				'status'  => 1,
				'season'  => $competition->current_season['name'],
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
	 * @return the content
	 */
	public function show_competition_clubs( $atts ) {
		global $wp;
		$args           = shortcode_atts(
			array(
				'id'       => 0,
				'template' => '',
				'season'   => false,
			),
			$atts
		);
		$competition_id = $args['id'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			return esc_html_e( 'Competition not found', 'racketmanager' );
		}
		$competition->clubs = $competition->get_clubs( array( 'status' => 1 ) );
		$competition_club   = null;
		$club               = null;
		if ( isset( $wp->query_vars['club_name'] ) ) {
			$club = get_query_var( 'club_name' );
			$club = str_replace( '-', ' ', $club );
		}
		if ( $club ) {
			$competition_club = get_club( $club, 'shortcode' );
			if ( $competition_club ) {
				$competition_club->teams   = $competition->get_teams(
					array(
						'club'   => $competition_club->id,
						'season' => $competition->current_season['name'],
						'status' => 1,
					)
				);
				$competition_club->matches = array();
				$matches                   = $competition->get_matches(
					array(
						'season'  => $competition->current_season['name'],
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
						'season'  => $competition->current_season['name'],
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
						'season' => $competition->current_season['name'],
						'stats'  => true,
					)
				);
			} else {
				return esc_html__( 'Club not found', 'racketmanager' );
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
	 * @return the content
	 */
	public function show_competition_players( $atts ) {
		global $wp;
		$args           = shortcode_atts(
			array(
				'id'       => 0,
				'players'  => null,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$player_id      = $args['players'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			return esc_html_e( 'Competition not found', 'racketmanager' );
		}
		$player               = null;
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
	 * @return the content
	 */
	public function show_competition_winners( $atts ) {
		$args           = shortcode_atts(
			array(
				'id'       => 0,
				'template' => '',
			),
			$atts
		);
		$competition_id = $args['id'];
		$template       = $args['template'];
		$competition    = get_competition( $competition_id );
		if ( ! $competition ) {
			return __( 'Competition not found', 'racketmanager' );
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
