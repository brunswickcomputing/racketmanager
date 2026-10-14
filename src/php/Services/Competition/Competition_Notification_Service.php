<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;

/**
 * Service for competition notifications.
 */
class Competition_Notification_Service {
	private Mailer_Interface $mailer;

	public function __construct( ?Mailer_Interface $mailer = null ) {
		$this->mailer = $mailer ?? new Wp_Mailer();
	}

	/**
	 * Contact Competition Teams
	 *
	 * @param Competition $competition
	 * @param string $season
	 * @param string $email_message
	 * @return bool
	 */
	public function contact_teams( Competition $competition, string $season, string $email_message ): bool {
		global $racketmanager;
		$email_message = str_replace( '\"', '"', $email_message );
		$headers       = [];
		$email_from    = $racketmanager->get_confirmation_email( $competition->get_type() );
		$headers[]     = RACKETMANAGER_FROM_EMAIL . ucfirst( $competition->get_type() ) . ' Secretary <' . $email_from . '>';
		$headers[]     = RACKETMANAGER_CC_EMAIL . ucfirst( $competition->get_type() ) . ' Secretary <' . $email_from . '>';
		$email_subject = $racketmanager->site_name . ' - ' . $competition->get_name() . ' ' . $season . ' - Important Message';
		$email_to      = [];

		if ( ! $competition->is_player_entry ) {
			$teams  = [];
			$events = $competition->get_events();
			foreach ( $events as $event ) {
				if ( $event ) {
					$event_teams = $event->get_teams( [ 'season' => $event->current_season['name'] ] );
					if ( $event_teams ) {
						$teams = array_merge( $teams, $event_teams );
					}
				}
			}
			foreach ( $teams as $team ) {
				$league = \Racketmanager\get_league( $team->league_id );
				if ( $league ) {
					$team_dtls = $league->get_team_dtls( $team->team_id );
					if ( ! empty( $team_dtls->contactemail ) ) {
						$headers[] = RACKETMANAGER_BCC_EMAIL . ucwords( $team_dtls->captain ) . ' <' . $team_dtls->contactemail . '>';
					}
					if ( ! empty( $team_dtls->club->match_secretary->email ) ) {
						$headers[] = RACKETMANAGER_BCC_EMAIL . ucwords( $team_dtls->club->match_secretary->display_name ) . ' <' . $team_dtls->club->match_secretary->email . '>';
					}
				}
			}
		}
		
		$this->mailer->send( $email_to, $email_subject, $email_message, $headers );
		return true;
	}
}
