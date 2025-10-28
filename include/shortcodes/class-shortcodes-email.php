<?php
/**
 * Shortcodes_email API: Shortcodes_email class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes
 */

namespace Racketmanager\shortcodes;

if ( ! class_exists('Racketmanager\\shortcodes\\Shortcodes_Email', false) ) {
    require_once RACKETMANAGER_PATH . 'src/php/shortcodes/Shortcodes_Email.php';
    return;
}

use stdClass;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_team;
use function Racketmanager\get_tournament;
use function Racketmanager\seo_url;

/**
 * Class to implement shortcode functions for emails
 */
class Shortcodes_Email extends Shortcodes {
    /**
     * Function to show match notification
     *
     *    [match-notification id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_match_notification( array $atts ): string {
		global $racketmanager;
		$args            = shortcode_atts(
			array(
				'match'           => '',
				'template'        => '',
				'tournament'      => false,
				'competition'     => '',
				'emailfrom'       => '',
				'round'           => '',
				'competition_type' => '',
			),
			$atts
		);
		$match            = $args['match'];
		$template         = $args['template'];
		$tournament       = $args['tournament'];
		$competition      = $args['competition'];
		$email_from       = $args['emailfrom'];
		$round            = $args['round'];
		$competition_type = $args['competition_type'];
		$organisation     = $racketmanager->site_name;
		$match            = get_match( $match );

		$teams = array(
			'home' => new stdClass(),
			'away' => new stdClass(),
		);

		$home_dtls       = array();
		$away_dtls       = array();
        $tournament_link = '';
		$match_link      = '';
		$cup_link        = '';
		$draw_link       = '';
		$rules_link      = $racketmanager->site_url . '/rules/' . $competition_type . '-rules/';
		if ( 'tournament' === $competition_type ) {
			$tournament      = get_tournament( $tournament );
			$tournament_link = '<a href="' . $racketmanager->site_url . $tournament->link . '">' . $tournament->name . '</a>';
			$draw_link       = '<a href="' . $racketmanager->site_url .  $tournament->link . 'draw/' . seo_url( $match->league->event->name ) . '/">' . $match->league->event->name . '</a>';
			$match_link      = $racketmanager->site_url . $tournament->link . '/match/' . seo_url( $match->league->title ) . '/' . seo_url( $match->teams['home']->title ) . '-vs-' . seo_url( $match->teams['away']->title ) . '/' . $match->id . '/';
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
		} elseif ( 'cup' === $competition_type ) {
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
		} else {
			$team_1     = 'away';
			$opponent_1 = 'home';
		}
		$team_2                     = 'home';
		$opponent_2                 = 'away';
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
		if ( 'P' === $match->teams['home']->team_type ) {
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
				'tournament'      => $tournament,
				'competition'     => $competition,
				'match'           => $match,
				'home_dtls'       => $home_dtls,
				'away_dtls'       => $away_dtls,
				'round'           => $round,
				'organisation'    => $organisation,
				'email_from'      => $email_from,
				'teams'           => $teams,
                'tournament_link' => $tournament_link,
				'draw_link'       => $draw_link,
				'action_url'      => $match_link,
				'rules_link'      => $rules_link,
				'cup_link'        => $cup_link,
			),
			'email'
		);
    }

    /**
     * Function to show result notification to administrator
     *
     *    [result-notification id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_result_notification( array $atts ): string {
		global $racketmanager;
		$args       = shortcode_atts(
			array(
				'match'            => '',
				'template'         => '',
				'league'           => false,
				'round'            => false,
				'complete'         => false,
				'errors'           => false,
				'match_day'        => '',
				'from_email'       => false,
                'challenge'        => false,
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
        $challenge  = $args['challenge'];
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
                'challenge'    => $challenge,
				'from_email'   => $from_email,
			),
			'email'
		);
    }

    /**
     * Function to show result notification to a captain
     *
     *    [result-notification-captain id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_captain_result_notification( array $atts ): string {
		global $racketmanager;

		$args        = shortcode_atts(
			array(
				'match'                 => '',
				'template'              => '',
				'outstanding'           => false,
				'time_period'           => false,
				'override'              => false,
				'from_email'            => false,
				'confirmation_required' => false,
                'confirmation_timeout'  => false,
                'timeout'               => false,
                'penalty'               => false,
			),
			$atts
		);
		$match_id              = $args['match'];
		$template              = $args['template'];
		$outstanding           = $args['outstanding'];
		$time_period           = $args['time_period'];
		$override              = $args['override'];
		$from_email            = $args['from_email'];
		$confirmation_required = $args['confirmation_required'];
        $confirmation_timeout  = $args['confirmation_timeout'];
        $timeout               = $args['timeout'];
        $penalty               = $args['penalty'];
		$match                 = get_match( $match_id );
		$action_url            = $racketmanager->site_url;
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
				'match'                 => $match,
				'organisation'          => $racketmanager->site_name,
				'action_url'            => $action_url,
				'outstanding'           => $outstanding,
				'time_period'           => $time_period,
				'override'              => $override,
				'from_email'            => $from_email,
				'confirmation_required' => $confirmation_required,
                'confirmation_timeout'  => $confirmation_timeout,
                'timeout'               => $timeout,
                'penalty'               => $penalty,
			),
			'email'
		);
    }

    /**
     * Function to show result outstanding notification
     *
     *    [result-outstanding-notification id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_result_outstanding_notification( array $atts ): string {
		global $racketmanager;

		$args        = shortcode_atts(
			array(
				'match'       => '',
				'template'    => '',
				'time_period' => false,
                'timeout'     => false,
                'penalty'     => false,
				'from_email'  => null,
			),
			$atts
		);
		$match       = $args['match'];
		$template    = $args['template'];
		$time_period = $args['time_period'];
        $timeout     = $args['timeout'];
        $penalty     = $args['penalty'];
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
                'timeout'      => $timeout,
                'penalty'      => $penalty,
				'from_email'   => $from_email,
			),
			'email'
		);
    }

    /**
     * Function to show club player notification
     *
     *    [club-player-notification club=club template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_club_player_notification( array $atts ): string {
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
     *    [match-notification id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_match_date_change_notification( array $atts ): string {
		global $racketmanager;
		$args            = shortcode_atts(
			array(
				'match'           => '',
				'template'        => '',
				'tournament'      => false,
				'competition'     => '',
				'emailfrom'       => '',
				'round'           => '',
				'competition_type' => '',
				'original_date'   => '',
				'new_date'        => '',
				'delay'           => false,
				'email_subject'   => '',
			),
			$atts
		);
		$match            = $args['match'];
		$template         = $args['template'];
		$tournament       = $args['tournament'];
		$competition      = $args['competition'];
		$email_from       = $args['emailfrom'];
		$round            = $args['round'];
		$competition_type = $args['competition_type'];
		$original_date    = $args['original_date'];
		$new_date         = $args['new_date'];
		$delay            = $args['delay'];
		$email_subject    = $args['email_subject'];
		$organisation     = $racketmanager->site_name;
		$match            = get_match( $match );
		$match_link       = '';
		$competition_link = '';
		$draw_link        = '';
		$rules_link       = $racketmanager->site_url . '/rules/' . $competition_type . '-rules/';
		if ( 'tournament' === $competition_type ) {
			$tournament       = get_tournament( $tournament );
			$tournament->link = '<a href="' . $racketmanager->site_url . $tournament->link . '">' . $tournament->name . '</a>';
			$draw_link        = '<a href="' . $racketmanager->site_url . $tournament->link . 'draw/' . seo_url( $match->league->event->name ) . '/">' . $match->league->event->name . '</a>';
			$match_link       = $racketmanager->site_url . $tournament->link . 'match/' . seo_url( $match->league->title ) . '/' . seo_url( $match->teams['home']->title ) . '-vs-' . seo_url( $match->teams['away']->title ) . '/' . $match->id . '/';
		} elseif ( 'cup' === $competition_type ) {
			$competition_link = '<a href="' . $racketmanager->site_url . '/cups/' . seo_url( $match->league->title ) . '/' . $match->season . '/">' . $match->league->title . '</a>';
			$match_link       = $racketmanager->site_url . $match->link;
			if ( ! empty( $match->leg ) ) {
				$match_link .= 'leg-' . $match->leg . '/';
			}
		} elseif ( 'league' === $competition_type ) {
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
    /**
     * Function to show team withdrawn email
     *
     *    [team-withdrawn]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_team_withdrawn( array $atts ): string {
		global $racketmanager;
		$args      = shortcode_atts(
			array(
				'team'     => false,
				'league'   => false,
				'season'   => false,
				'subject'  => false,
				'from'     => false,
				'template' => '',
			),
			$atts
		);
		$team_id   = $args['team'];
		$league_id = $args['league'];
		$season    = $args['season'];
		$subject   = $args['subject'];
		$from      = $args['from'];
		$template  = $args['template'];
		$valid     = true;
		$team      = null;
		$league    = null;
		$msg       = null;
		if ( $team_id ) {
			$team = get_team( $team_id );
			if ( $team ) {
				if ( $league_id ) {
					$league = get_league( $league_id );
					if ( $league ) {
						if ( $season ) {
							$league->set_season( $season );
						} else {
							$valid = false;
							$msg   = __( 'Season not supplied', 'racketmanager' );
						}
					} else {
						$valid = false;
						$msg   = $this->league_not_found;
					}
				} else {
					$valid = false;
					$msg   = __( 'League not supplied', 'racketmanager' );
				}
			} else {
				$valid = false;
				$msg   = $this->team_not_found;
			}
		} else {
			$valid = false;
			$msg   = __( 'Team not supplied', 'racketmanager' );
		}
		if ( $valid ) {
			$filename = ( ! empty( $template ) ) ? 'team-withdrawn-' . $template : 'team-withdrawn';
			return $this->load_template(
				$filename,
				array(
					'team'          => $team,
					'league'        => $league,
					'season'        => $season,
					'organisation'  => $racketmanager->site_name,
					'email_subject' => $subject,
					'email_from'    => $from,
				),
				'email'
			);
		} else {
			return $this->return_error( $msg );
		}
    }
    /**
     * Function to show match notification
     *
     *    [withdrawn-team-match id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_withdrawn_team_match( array $atts ): string {
        global $racketmanager;
        $args            = shortcode_atts(
            array(
                'id'            => '',
                'template'      => '',
                'is_tournament' => false,
                'event'         => '',
                'emailfrom'     => '',
                'round'         => '',
                'subject'       => null,
            ),
            $atts
        );
        $template         = $args['template'];
        $is_tournament    = $args['is_tournament'];
        $event            = $args['event'];
        $email_from       = $args['emailfrom'];
        $round            = $args['round'];
        $subject          = $args['subject'];
        $organisation     = $racketmanager->site_name;

        $filename = ( ! empty( $template ) ) ? 'match-team-withdrawn-' . $template : 'match-team-withdrawn';

        return $this->load_template(
            $filename,
            array(
                'is_tournament' => $is_tournament,
                'event'         => $event,
                'round'         => $round,
                'organisation'  => $organisation,
                'email_from'    => $email_from,
                'email_subject' => $subject,
            ),
            'email'
        );
    }
    /**
     * Function to show event constitution email
     *
     *    [event-constitution]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string content
     */
    public function show_event_constitution( array $atts ): string {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'id'       => false,
				'season'   => null,
				'template' => '',
			),
			$atts
		);
		$event_id = $args['id'];
		$season   = $args['season'];
		$template = $args['template'];
		$event    = get_event( $event_id );
		if ( ! $event ) {
			$msg = $this->event_not_found;
			return $this->return_error( $msg );
		}
		$event->leagues = $event->get_leagues();
		$event->set_season( $season );
		$filename = ( ! empty( $template ) ) ? 'constitution-' . $template : 'constitution';
		return $this->load_template(
			$filename,
			array(
				'event'        => $event,
				'organisation' => $racketmanager->site_name,
			),
			'event'
		);
    }
}
