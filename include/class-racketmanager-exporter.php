<?php
/**
 * Racketmanager_Exporter API: exporter
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Racketmanager_Exporter
 */

namespace Racketmanager;

/**
 * Class to implement the Racketmanager_Exporter object
 */
class Racketmanager_Exporter {
	/**
	 * Calendar export function
	 */
	public function calendar() {
		global $racketmanager;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['league_id'] ) && isset( $_GET['season'] ) ) {
			$league_id      = sanitize_text_field( wp_unslash( $_GET['league_id'] ) );
			$league         = get_league( $league_id );
			$season         = sanitize_text_field( wp_unslash( $_GET['season'] ) );
			$match_array    = array(
				'season'    => $season,
				'match_day' => -1,
				'limit'     => false,
			);
			$file_team_name = '';
			if ( isset( $_GET['team_id'] ) ) {
				$team_id                = sanitize_text_field( wp_unslash( $_GET['team_id'] ) );
				$team                   = get_team( $team_id );
				$file_team_name         = '-' . seo_url( $team->title );
				$match_array['team_id'] = $team_id;
			}
			$matches  = $league->get_matches( $match_array );
			$filename = $season . '-' . sanitize_title( $league->title ) . $file_team_name . '.ics';
			$this->output_calendar( $matches, $filename );
		} elseif ( isset( $_GET['competition_id'] ) ) {
			$competition_id = sanitize_text_field( wp_unslash( $_GET['competition_id'] ) );
			$competition    = get_competition( $competition_id );
			$season         = $competition->get_season();
			$match_array    = array(
				'competition_id' => $competition_id,
				'season'         => $season,
			);
			$file_club_name = '';
			if ( isset( $_GET['club_id'] ) ) {
				$club_id                       = sanitize_text_field( wp_unslash( $_GET['club_id'] ) );
				$club                          = get_club( $club_id );
				$file_club_name                = '-' . seo_url( $club->name );
				$match_array['affiliatedClub'] = $club_id;
			}
			$matches  = $racketmanager->get_matches( $match_array );
			$filename = $season . '-' . sanitize_title( $competition->name ) . $file_club_name . '.ics';
			$this->output_calendar( $matches, $filename );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
	/**
	 * Export results function
	 *
	 * Optional parameters:
	 *  club
	 *  days - defaults to 7
	 *  competition
	 */
	public function results() {
		global $racketmanager;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['club'] ) ) {
			if ( is_numeric( $_GET['club'] ) ) {
				$club_id = intval( $_GET['club'] );
				$club    = get_club( $club_id );

			} else {
				$club_name = un_seo_url( sanitize_text_field( wp_unslash( $_GET['club'] ) ) );
				$club      = get_club( $club_name, 'shortcode' );
				$club_id   = $club->id;
			}
		} else {
			$club    = '';
			$club_id = '';
		}
		if ( isset( $_GET['days'] ) ) {
			$days = intval( $_GET['days'] );
		} else {
			$days = 7;
		}
		if ( isset( $_GET['competition'] ) ) {
			$competition = un_seo_url( sanitize_text_field( wp_unslash( $_GET['competition'] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$time    = 'latest';
		$matches = $racketmanager->get_matches(
			array(
				'days'             => $days,
				'competition_name' => $competition,
				'time'             => $time,
				'history'          => $days,
				'affiliatedClub'   => $club_id,
			)
		);
		$this->match_output( $club, $matches );
	}
	/**
	 * Export fixtures function
	 *
	 * Required parameters:
	 *  competition
	 *  season
	 * Optional parameters:
	 *  club
	 *  days - defaults to 7
	 */
	public function fixtures() {
		global $racketmanager;
		$validator = new Racketmanager_Validator();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['club'] ) ) {
			if ( is_numeric( $_GET['club'] ) ) {
				$club_id = intval( $_GET['club'] );
				$club    = get_club( $club_id );
			} else {
				$club_name = un_seo_url( sanitize_text_field( wp_unslash( $_GET['club'] ) ) );
				$club      = get_club( $club_name, 'shortcode' );
				$club_id   = $club->id;
			}
			if ( ! $club ) {
				$validator = $validator->club( $club );
			}
		} else {
			$club    = '';
			$club_id = '';
		}
		if ( isset( $_GET['competition'] ) ) {
			$competition = un_seo_url( sanitize_text_field( wp_unslash( $_GET['competition'] ) ) );
		} else {
			$validator = $validator->competition( null );
		}
		if ( isset( $_GET['season'] ) ) {
			$season = sanitize_text_field( wp_unslash( $_GET['season'] ) );
		} else {
			$validator = $validator->season( null );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! $validator->error ) {
			$matches = $racketmanager->get_matches(
				array(
					'competition_name' => $competition,
					'season'           => $season,
					'affiliatedClub'   => $club_id,
				)
			);
			$this->match_output( $club, $matches );
		} else {
			$message = __( 'Error with export', 'racketmanager' );
			foreach ( $validator->error_msg as $err_msg ) {
				$message .= '<br />' . $err_msg;
			}
			echo wp_kses( $message, array( 'br' => array() ) );
			exit();
		}
	}
	/**
	 * Export standings function
	 *
	 * Required parameters:
	 *  competition / event
	 *  season
	 * Optional parameters:
	 *  club
	 *  days - defaults to 7
	 */
	public function standings() {
		$validator = new Racketmanager_Validator();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['club'] ) ) {
			if ( is_numeric( $_GET['club'] ) ) {
				$club_id = intval( $_GET['club'] );
				$club    = get_club( $club_id );
			} else {
				$club_name = un_seo_url( sanitize_text_field( wp_unslash( $_GET['club'] ) ) );
				$club      = get_club( $club_name, 'shortcode' );
				$club_id   = $club->id;
			}
			if ( ! $club ) {
				$validator = $validator->club( $club );
			}
		} else {
			$club    = '';
			$club_id = '';
		}
		if ( isset( $_GET['competition'] ) ) {
			if ( is_numeric( $_GET['competition'] ) ) {
				$competition_id = intval( $_GET['competition'] );
				$competition    = get_competition( $competition_id );
			} else {
				$competition_name = un_seo_url( sanitize_text_field( wp_unslash( $_GET['competition'] ) ) );
				$competition      = get_competition( $competition_name, 'name' );
				if ( $competition ) {
					$competition_id = $competition->id;
				}
			}
			if ( ! $competition ) {
				$validator = $validator->competition( $competition );
			}
		} elseif ( isset( $_GET['event'] ) ) {
				$event = un_seo_url( sanitize_text_field( wp_unslash( $_GET['event'] ) ) );
				$event = get_event( $event, 'name' );
			if ( $event ) {
				$validator = $validator->event( $event );
			}
		} else {
			$validator = $validator->event( null );
		}
		if ( isset( $_GET['season'] ) ) {
			$season = sanitize_text_field( wp_unslash( $_GET['season'] ) );
		} else {
			$validator = $validator->season( null );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! $validator->error ) {
			if ( ! empty( $competition ) ) {
				$events = $competition->get_events();
			} else {
				$events[] = $event;
			}
			$contents = '';
			foreach ( $events as $event ) {
				$event   = get_event( $event );
				$leagues = $event->get_leagues();
				foreach ( $leagues as $league ) {
					$league = get_league( $league->id );
					$teams  = $league->get_league_teams(
						array(
							'season' => $season,
							'club'   => $club_id,
						)
					);
					foreach ( $teams as $i => $team ) {
						$team->league = $league->title;
						$teams[ $i ]  = $team;
					}
					$contents .= $this->standings_output( $club, $teams, $contents );
				}
			}
				header( 'Content-Type: application/json; charset=utf-8' );
				echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				exit();
		} else {
			$message = __( 'Error with export', 'racketmanager' );
			foreach ( $validator->error_msg as $err_msg ) {
				$message .= '<br />' . $err_msg;
			}
			echo wp_kses( $message, array( 'br' => array() ) );
			exit();
		}
	}
	/**
	 * Produce calendar download file
	 *
	 * @param array  $matches array of matches to download.
	 * @param string $filename filename to be created.
	 */
	private function output_calendar( $matches, $filename ) {
		$contents  = "BEGIN:VCALENDAR\n";
		$contents .= "VERSION:2.0\n";
		$contents .= "PRODID:-//TENNIS CALENDAR//NONSGML Events //EN\n";
		$contents .= "CALSCALE:GREGORIAN\n";
		$contents .= 'DTSTAMP:' . gmdate( 'Ymd\THis' ) . "\n";
		foreach ( $matches as $match ) {
			$match     = get_match( $match->id );
			$contents .= "BEGIN:VEVENT\n";
			$contents .= 'UID:' . $match->id . "\n";
			$contents .= 'DTSTAMP:' . mysql2date( 'Ymd\THis', $match->date ) . "\n";
			$contents .= 'DTSTART:' . mysql2date( 'Ymd\THis', $match->date ) . "\n";
			$contents .= 'DTEND:' . gmdate( 'Ymd\THis', strtotime( '+2 hours', strtotime( $match->date ) ) ) . "\n";
			$contents .= 'SUMMARY:' . $match->match_title . "\n";
			$contents .= 'LOCATION:' . $match->location . "\n";
			$contents .= "END:VEVENT\n";
		}
		$contents .= 'END:VCALENDAR';
		header( 'Content-Type: text/calendar' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo esc_html( $contents );
		exit();
	}
	/**
	 * Produce match output data
	 *
	 * @param object $club club object.
	 * @param array  $matches array of matches to download.
	 */
	private function match_output( $club, $matches ) {
		$contents = '';
		foreach ( $matches as $match ) {
			$json_result = new \stdClass();
			if ( ! empty( $club ) ) {
				$json_result->club = str_replace( '"', '', $club->shortcode );
			}
			$json_result->home_team  = str_replace( '"', '', $match->teams['home']->title );
			$json_result->away_team  = str_replace( '"', '', $match->teams['away']->title );
			$json_result->match_date = substr( $match->date, 0, 10 );
			$json_result->match_time = $match->start_time;
			if ( $match->winner_id ) {
				$json_result->score = str_replace( '"', '', $match->score );
			}
			$contents .= wp_json_encode( $json_result ) . "\n";
		}
		header( 'Content-Type: application/json; charset=utf-8' );
		echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit();
	}
	/**
	 * Produce league standings output data
	 *
	 * @param object $club club object.
	 * @param array  $teams array of standings to download.
	 * @return string $contents updated contents.
	 */
	private function standings_output( $club, $teams ) {
		$contents = '';
		foreach ( $teams as $team ) {
			$json_result = new \stdClass();
			if ( ! empty( $club ) ) {
				$json_result->club = str_replace( '"', '', $club->shortcode );
			}
			$json_result->league = $team->league;
			$json_result->season = $team->season;
			$json_result->team   = $team->title;
			$json_result->rank   = $team->rank;
			$json_result->status = $team->status;
			$json_result->played = $team->done_matches;
			$json_result->won    = $team->won_matches;
			$json_result->drawn  = $team->draw_matches;
			$json_result->lost   = $team->lost_matches;
			$json_result->points = $team->points['plus'];
			$contents            = wp_json_encode( $json_result ) . "\n";
		}
		return $contents;
	}
}
