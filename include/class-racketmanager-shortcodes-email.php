<?php
/**
 * Shortcodes_email API: Shortcodes_email class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement shortcode functions for emails
 */
class Racketmanager_Shortcodes_Email extends RacketManager_Shortcodes {
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'matchnotification', array( &$this, 'showMatchNotification' ) );
		add_shortcode( 'resultnotification', array( &$this, 'showResultNotification' ) );
		add_shortcode( 'resultnotificationcaptain', array( &$this, 'showCaptainResultNotification' ) );
		add_shortcode( 'resultoutstandingnotification', array( &$this, 'show_result_outstanding_notification' ) );
		add_shortcode( 'clubplayernotification', array( &$this, 'showClubPlayerNotification' ) );
		add_shortcode( 'match_date_change_notification', array( &$this, 'show_match_date_change_notification' ) );
	}
	/**
	 * Function to show match notification
	 *
	 *    [matchnotification id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function showMatchNotification( $atts ) {
		global $racketmanager;
		$args            = shortcode_atts(
			array(
				'match'           => '',
				'template'        => '',
				'tournament'      => false,
				'competition'     => '',
				'emailfrom'       => '',
				'round'           => '',
				'competitiontype' => '',
			),
			$atts
		);
		$match           = $args['match'];
		$template        = $args['template'];
		$tournament      = $args['tournament'];
		$competition     = $args['competition'];
		$email_from      = $args['emailfrom'];
		$round           = $args['round'];
		$competitiontype = $args['competitiontype'];
		$organisation    = $racketmanager->site_name;
		$match           = get_match( $match );

		$teams = array(
			'home' => new \stdClass(),
			'away' => new \stdClass(),
		);

		$home_dtls  = array();
		$away_dtls  = array();
		$match_link = '';
		$cup_link   = '';
		$draw_link  = '';
		$rules_link = $racketmanager->site_url . '/rules/' . $competitiontype . '-rules/';
		if ( 'tournament' === $competitiontype ) {
			$tournament       = get_tournament( $tournament );
			$tournament->link = '<a href="' . $racketmanager->site_url . '/tournament/' . seo_url( $tournament->name ) . '/">' . $tournament->name . '</a>';
			$draw_link        = '<a href="' . $racketmanager->site_url . '/tournament/' . seo_url( $tournament->name ) . '/draw/' . seo_url( $match->league->event->name ) . '/">' . $match->league->event->name . '</a>';
			$match_link       = $racketmanager->site_url . '/tournament/' . seo_url( $tournament->name ) . '/match/' . seo_url( $match->league->title ) . '/' . seo_url( $match->teams['home']->title ) . '-vs-' . seo_url( $match->teams['away']->title ) . '/' . $match->id . '/';
			if ( substr( $match->league->type, 1, 1 ) === 'D' ) {
				$teams['home']->title = __( 'Home Players', 'racketmanager' );
				$teams['away']->title = __( 'Away Players', 'racketmanager' );
				$home_dtls['title']   = 'Home Players';
				$away_dtls['title']   = 'Away Players';
			} else {
				$teams['home']->title = __( 'Home Player', 'racketmanager' );
				$teams['away']->title = __( 'Away Player', 'racketmanager' );
				$home_dtls['title']   = 'Home Player';
				$away_dtls['title']   = 'Away Player';
			}
		} elseif ( 'cup' === $competitiontype ) {
			$cup_link   = '<a href="' . $racketmanager->site_url . '/cups/' . seo_url( $match->league->title ) . '/' . $match->season . '/">' . $match->league->title . '</a>';
			$match_link = $racketmanager->site_url . $match->link;
			if ( ! empty( $match->leg ) ) {
				$match_link .= 'leg-' . $match->leg . '/';
			}
			$template             = 'cup';
			$teams['home']->title = __( 'Home Team', 'racketmanager' );
			$teams['away']->title = __( 'Away Team', 'racketmanager' );
			$home_dtls['title']   = 'Home Team';
			$away_dtls['title']   = 'Away Team';
		}
		if ( 'home' === $match->host ) {
			$team_1     = 'home';
			$opponent_1 = 'away';
			$team_2     = 'home';
			$opponent_2 = 'away';
		} else {
			$team_1     = 'away';
			$opponent_1 = 'home';
			$team_2     = 'home';
			$opponent_2 = 'away';
		}
			$teams[ $team_1 ]->name     = $match->teams[ $team_2 ]->title;
			$teams[ $opponent_1 ]->name = $match->teams[ $opponent_2 ]->title;
		if ( ! empty( $match->teams[ $team_2 ]->club->shortcode ) ) {
			$teams[ $team_1 ]->club = $match->teams[ $team_2 ]->club->shortcode;
		} else {
			$teams[ $team_1 ]->club = __( 'Unknown', 'racketmanager' );
		}
		if ( ! empty( $match->teams[ $opponent_2 ]->club->shortcode ) ) {
			$teams[ $opponent_1 ]->club = $match->teams[ $opponent_2 ]->club->shortcode;
		} else {
			$teams[ $opponent_1 ]->club = __( 'Unknown', 'racketmanager' );
		}
		if ( 'P' === $match->teams['home']->status ) {
			foreach ( $match->teams[ $team_2 ]->players as $player ) {
				$teams[ $team_1 ]->player[] = $player;
			}
			foreach ( $match->teams[ $opponent_2 ]->players as $player ) {
				$teams[ $opponent_1 ]->player[] = $player;
			}
		} else {
			$teams[ $team_1 ]->captain           = $match->teams[ $team_2 ]->captain;
			$teams[ $team_1 ]->captain_email     = $match->teams[ $team_2 ]->contactemail;
			$teams[ $team_1 ]->captain_tel       = $match->teams[ $team_2 ]->contactno;
			$teams[ $team_1 ]->matchDay          = $match->teams[ $team_2 ]->match_day;
			$teams[ $team_1 ]->matchTime         = $match->teams[ $team_2 ]->match_time;
			$teams[ $opponent_1 ]->captain       = $match->teams[ $opponent_2 ]->captain;
			$teams[ $opponent_1 ]->captain_email = $match->teams[ $opponent_2 ]->contactemail;
			$teams[ $opponent_1 ]->captain_tel   = $match->teams[ $opponent_2 ]->contactno;
			$teams[ $opponent_1 ]->matchDay      = $match->teams[ $opponent_2 ]->match_day;
			$teams[ $opponent_1 ]->matchTime     = $match->teams[ $opponent_2 ]->match_time;
		}

		$filename = ( ! empty( $template ) ) ? 'match-notification-' . $template : 'match-notification';

		return $this->load_template(
			$filename,
			array(
				'tournament'   => $tournament,
				'competition'  => $competition,
				'match'        => $match,
				'home_dtls'    => $home_dtls,
				'away_dtls'    => $away_dtls,
				'round'        => $round,
				'organisation' => $organisation,
				'email_from'   => $email_from,
				'teams'        => $teams,
				'draw_link'    => $draw_link,
				'action_url'   => $match_link,
				'rules_link'   => $rules_link,
				'cup_link'     => $cup_link,
			),
			'email'
		);
	}

	/**
	 * Function to show result notification
	 *
	 *    [resultnotification id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function showResultNotification( $atts ) {
		global $racketmanager;
		$args       = shortcode_atts(
			array(
				'match'            => '',
				'template'         => '',
				'league'           => false,
				'round'            => false,
				'organisationname' => false,
				'complete'         => false,
				'errors'           => false,
				'match_day'        => '',
				'from_email'       => false,
			),
			$atts
		);
		$match      = $args['match'];
		$template   = $args['template'];
		$league     = $args['league'];
		$round      = $args['round'];
		$complete   = $args['complete'];
		$errors     = $args['errors'];
		$match_day  = $args['match_day'];
		$from_email = $args['from_email'];
		$match      = get_match( $match );

		$action_url = admin_url() . '?page=racketmanager&view=results';
		if ( $league ) {
			$action_url .= '&subpage=show-league&league_id=' . $league;
		}
		if ( $match_day ) {
			$action_url .= '&match_day=' . $match_day;
		}
		if ( $round ) {
			$action_url .= '&final=' . $round . '&league-tab=matches';
		}

		$filename = ( ! empty( $template ) ) ? 'result-notification-' . $template : 'result-notification';

		return $this->load_template(
			$filename,
			array(
				'match'        => $match,
				'organisation' => $racketmanager->site_name,
				'action_url'   => $action_url,
				'complete'     => $complete,
				'errors'       => $errors,
				'from_email'   => $from_email,
			),
			'email'
		);
	}

	/**
	 * Function to show result notification
	 *
	 *    [resultnotificationcaptain id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function showCaptainResultNotification( $atts ) {
		global $racketmanager;

		$args        = shortcode_atts(
			array(
				'match'       => '',
				'template'    => '',
				'outstanding' => false,
				'time_period' => false,
				'override'    => false,
				'from_email'  => false,
			),
			$atts
		);
		$match       = $args['match'];
		$template    = $args['template'];
		$outstanding = $args['outstanding'];
		$time_period = $args['time_period'];
		$override    = $args['override'];
		$from_email  = $args['from_email'];
		$match       = get_match( $match );

		$action_url = $racketmanager->site_url;
		if ( $match->league->event->competition->is_championship ) {
			$action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ) . '/' . $match->league->current_season['name'] . '/' . $match->final_round . '/' . sanitize_title( $match->teams['home']->title ) . '-vs-' . sanitize_title( $match->teams['away']->title ) . '/';
			if ( ! empty( $match->leg ) ) {
				$action_url .= 'leg-' . $match->leg . '/';
			}
		} else {
			$action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ) . '/' . $match->league->current_season['name'] . '/day' . $match->match_day . '/' . sanitize_title( $match->teams['home']->title ) . '-vs-' . sanitize_title( $match->teams['away']->title ) . '/';
		}
		$action_url .= 'result/';
		$filename    = ( ! empty( $template ) ) ? 'result-notification-' . $template : 'result-notification';

		return $this->load_template(
			$filename,
			array(
				'match'        => $match,
				'organisation' => $racketmanager->site_name,
				'action_url'   => $action_url,
				'outstanding'  => $outstanding,
				'time_period'  => $time_period,
				'override'     => $override,
				'from_email'   => $from_email,
			),
			'email'
		);
	}

	/**
	 * Function to show result outstanding notification
	 *
	 *    [resultoutstandingnotification id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_result_outstanding_notification( $atts ) {
		global $racketmanager;

		$args        = shortcode_atts(
			array(
				'match'       => '',
				'template'    => '',
				'time_period' => false,
				'from_email'  => null,
			),
			$atts
		);
		$match       = $args['match'];
		$template    = $args['template'];
		$time_period = $args['time_period'];
		$from_email  = $args['from_email'];
		$match       = get_match( $match );

		$action_url = $racketmanager->site_url;
		if ( $match->league->event->competition->is_cup ) {
			$action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ) . '/' . $match->league->current_season['name'] . '/' . $match->final_round . '/' . sanitize_title( $match->teams['home']->title ) . '-vs-' . sanitize_title( $match->teams['away']->title );
		} elseif ( $match->league->event->competition->is_tournament ) {
			$tournament_code = $match->league->event->competition->id . ',' . $match->season;
			$tournament      = get_tournament( $tournament_code, 'shortcode' );
			if ( $tournament ) {
				$action_url .= '/' . __( 'tournament', 'racketmanager' ) . '/' . sanitize_title( $tournament->name ) . '/' . __( 'match', 'racketmanager' ) . '/' . $match->id . '/';
			}
		} else {
			$action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ) . '/' . $match->league->current_season['name'] . '/day' . $match->match_day . '/' . sanitize_title( $match->teams['home']->title ) . '-vs-' . sanitize_title( $match->teams['away']->title );
		}

		if ( empty( $template ) && 'tournament' === $match->league->event->competition->type ) {
			$template = 'tournament';
		}
		$filename = ( ! empty( $template ) ) ? 'match-result-pending-' . $template : 'match-result-pending';

		return $this->load_template(
			$filename,
			array(
				'match'        => $match,
				'organisation' => $racketmanager->site_name,
				'action_url'   => $action_url,
				'time_period'  => $time_period,
				'from_email'   => $from_email,
			),
			'email'
		);
	}

	/**
	 * Function to show club player notification
	 *
	 *    [clubplayernotification club=club template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function showClubPlayerNotification( $atts ) {
		global $racketmanager;

		$args       = shortcode_atts(
			array(
				'club'      => '',
				'action'    => false,
				'player'    => false,
				'requestor' => false,
				'btm'       => false,
				'template'  => '',
			),
			$atts
		);
		$club       = $args['club'];
		$action     = $args['action'];
		$player     = $args['player'];
		$template   = $args['template'];
		$requestor  = $args['requestor'];
		$btm        = $args['btm'];
		$action_url = $racketmanager->site_url . '/clubs/' . seo_url( $club ) . '/' . seo_url( $player ) . '/';

		$filename = ( ! empty( $template ) ) ? 'club-player-notification-' . $template : 'club-player-notification';

		return $this->load_template(
			$filename,
			array(
				'action'        => $action,
				'club'          => $club,
				'player'        => $player,
				'organisation'  => $racketmanager->site_name,
				'action_url'    => $action_url,
				'requestor'     => $requestor,
				'btm'           => $btm,
				'email_subject' => $racketmanager->site_name . ' - ' . __( 'Club Player Request', 'racketmanager' ) . ' - ' . $club,
			),
			'email'
		);
	}
	/**
	 * Function to show match date change notification
	 *
	 *    [matchnotification id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return the content
	 */
	public function show_match_date_change_notification( $atts ) {
		global $racketmanager;
		$args            = shortcode_atts(
			array(
				'match'           => '',
				'template'        => '',
				'tournament'      => false,
				'competition'     => '',
				'emailfrom'       => '',
				'round'           => '',
				'competitiontype' => '',
				'original_date'   => '',
				'new_date'        => '',
				'delay'           => false,
				'email_subject'   => '',
			),
			$atts
		);
		$match           = $args['match'];
		$template        = $args['template'];
		$tournament      = $args['tournament'];
		$competition     = $args['competition'];
		$email_from      = $args['emailfrom'];
		$round           = $args['round'];
		$competitiontype = $args['competitiontype'];
		$original_date   = $args['original_date'];
		$new_date        = $args['new_date'];
		$delay           = $args['delay'];
		$email_subject   = $args['email_subject'];
		$organisation    = $racketmanager->site_name;
		$match           = get_match( $match );

		$match_link       = '';
		$competition_link = '';
		$draw_link        = '';
		$rules_link       = $racketmanager->site_url . '/rules/' . $competitiontype . '-rules/';
		if ( 'tournament' === $competitiontype ) {
			$tournament       = get_tournament( $tournament );
			$tournament->link = '<a href="' . $racketmanager->site_url . '/tournament/' . seo_url( $tournament->name ) . '/">' . $tournament->name . '</a>';
			$draw_link        = '<a href="' . $racketmanager->site_url . '/tournament/' . seo_url( $tournament->name ) . '/draw/' . seo_url( $match->league->event->name ) . '/">' . $match->league->event->name . '</a>';
			$match_link       = $racketmanager->site_url . '/tournament/' . seo_url( $tournament->name ) . '/match/' . seo_url( $match->league->title ) . '/' . seo_url( $match->teams['home']->title ) . '-vs-' . seo_url( $match->teams['away']->title ) . '/' . $match->id . '/';
		} elseif ( 'cup' === $competitiontype ) {
			$competition_link = '<a href="' . $racketmanager->site_url . '/cups/' . seo_url( $match->league->title ) . '/' . $match->season . '/">' . $match->league->title . '</a>';
			$match_link       = $racketmanager->site_url . $match->link;
			if ( ! empty( $match->leg ) ) {
				$match_link .= 'leg-' . $match->leg . '/';
			}
		} elseif ( 'league' === $competitiontype ) {
			$competition_link = '<a href="' . $racketmanager->site_url . '/leagues/' . seo_url( $match->league->event->name ) . '/' . $match->season . '/">' . $match->league->title . '</a>';
			$match_link       = $racketmanager->site_url . $match->link;
		}

		$filename = ( ! empty( $template ) ) ? 'match-date-change-notification-' . $template : 'match-date-change-notification';

		return $this->load_template(
			$filename,
			array(
				'tournament'       => $tournament,
				'competition'      => $competition,
				'match'            => $match,
				'round'            => $round,
				'organisation'     => $organisation,
				'email_from'       => $email_from,
				'draw_link'        => $draw_link,
				'action_url'       => $match_link,
				'rules_link'       => $rules_link,
				'competition_link' => $competition_link,
				'new_date'         => $new_date,
				'original_date'    => $original_date,
				'delay'            => $delay,
				'email_subject'    => $email_subject,
			),
			'email'
		);
	}
}
