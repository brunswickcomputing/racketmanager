<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;
use function Racketmanager\constitution_notification;
use function Racketmanager\withdrawn_team_email;

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
		$comp_type     = $competition->get_type();
		$email_from    = method_exists( $racketmanager, 'get_confirmation_email' )
			? $racketmanager->get_confirmation_email( $comp_type )
			: ( is_callable( $racketmanager->get_confirmation_email ?? null ) ? ( $racketmanager->get_confirmation_email )( $comp_type ) : '' );
		$headers[]     = RACKETMANAGER_FROM_EMAIL . ucfirst( $comp_type ) . ' Secretary <' . $email_from . '>';
		$headers[]     = RACKETMANAGER_CC_EMAIL . ucfirst( $comp_type ) . ' Secretary <' . $email_from . '>';
		$site_name     = $racketmanager->site_name ?? '';
		$email_subject = $site_name . ' - ' . $competition->get_name() . ' ' . $season . ' - Important Message';
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

	/**
	 * Contact League Teams
	 *
	 * @param League $league
	 * @param string $season
	 * @param string $email_message
	 * @return bool
	 */
	public function contact_league_teams( League $league, string $season, string $email_message ): bool {
		global $racketmanager;
		$message_sent  = false;
		$teams         = $league->get_league_teams( [ 'season' => $season ] );
		$email_message = str_replace( '\"', '"', $email_message );
		$headers       = [];
		$raw_type      = $league->event->competition->type ?? 'league';
		$comp_type     = $raw_type instanceof Competition_Type ? $raw_type->value : (string) $raw_type;
		$email_from    = method_exists( $racketmanager, 'get_confirmation_email' )
			? $racketmanager->get_confirmation_email( $comp_type )
			: ( is_callable( $racketmanager->get_confirmation_email ?? null ) ? ( $racketmanager->get_confirmation_email )( $comp_type ) : '' );
		$headers[]     = RACKETMANAGER_FROM_EMAIL . ucfirst( $comp_type ) . ' Secretary <' . $email_from . '>';
		$headers[]     = RACKETMANAGER_CC_EMAIL . ucfirst( $comp_type ) . ' Secretary <' . $email_from . '>';
		$site_name     = $racketmanager->site_name ?? '';
		$email_subject = $site_name . ' - ' . $league->title . ' ' . $season . ' - Important Message';
		$email_to      = [];

		foreach ( $teams as $team ) {
			$team_dtls = $league->get_team_dtls( $team->id );
			if ( ! empty( $team_dtls->contactemail ) ) {
				$email_to[]   = ucwords( $team_dtls->captain ) . ' <' . $team_dtls->contactemail . '>';
				$message_sent = true;
			}
			if ( ! empty( $team_dtls->club->match_secretary->email ) ) {
				$headers[]    = RACKETMANAGER_CC_EMAIL . ucwords( $team_dtls->club->match_secretary->display_name ) . ' <' . $team_dtls->club->match_secretary->email . '>';
				$message_sent = true;
			}
		}

		if ( $message_sent ) {
			$this->mailer->send( $email_to, $email_subject, $email_message, $headers );
		}

		return $message_sent;
	}

	/**
	 * Notify League Teams of Withdrawn Team
	 *
	 * @param League $league
	 * @param int $team_id
	 * @param string $season
	 * @return bool
	 */
	public function notify_withdrawn_team( League $league, int $team_id, string $season ): bool {
		global $racketmanager;
		$teams         = $league->get_league_teams( [ 'season' => $season ] );
		$raw_type      = $league->event->competition->type ?? 'league';
		$comp_type     = $raw_type instanceof Competition_Type ? $raw_type->value : (string) $raw_type;
		$email_from   = method_exists( $racketmanager, 'get_confirmation_email' )
			? $racketmanager->get_confirmation_email( $comp_type )
			: ( is_callable( $racketmanager->get_confirmation_email ?? null ) ? ( $racketmanager->get_confirmation_email )( $comp_type ) : '' );
		$headers      = [];
		$headers[]    = RACKETMANAGER_FROM_EMAIL . ucfirst( $comp_type ) . ' Secretary <' . $email_from . '>';
		$headers[]    = RACKETMANAGER_CC_EMAIL . ucfirst( $comp_type ) . ' Secretary <' . $email_from . '>';
		$team_obj     = $league->get_team_dtls( $team_id );
		$team_title   = $team_obj->title ?? '';
		$email_subject = $league->title . ' ' . $season . ' - ' . __( 'Withdrawn team', 'racketmanager' ) . ' - ' . $team_title;
		$email_to     = [];
		$message_send = false;

		foreach ( $teams as $team ) {
			$team_dtls = $league->get_team_dtls( $team->id );
			if ( ! empty( $team_dtls->contactemail ) ) {
				$email_to[]   = ucwords( $team_dtls->captain ) . ' <' . $team_dtls->contactemail . '>';
				$message_send = true;
			}
			if ( ! empty( $team_dtls->club->match_secretary->email ) ) {
				$headers[]    = RACKETMANAGER_CC_EMAIL . ucwords( $team_dtls->club->match_secretary->display_name ) . ' <' . $team_dtls->club->match_secretary->email . '>';
				$message_send = true;
			}
		}

		if ( $message_send ) {
			$message_args = [
				'team'    => $team_id,
				'season'  => $season,
				'league'  => $league->id,
				'subject' => $email_subject,
				'from'    => $email_from,
			];
			$email_message = withdrawn_team_email( $message_args );
			$this->mailer->send( $email_to, $email_subject, $email_message, $headers );
		}

		return $message_send;
	}

	/**
	 * Send Event Constitution Notification
	 *
	 * @param Event $event
	 * @param array $season
	 * @return void
	 */
	public function send_constitution( Event $event, array $season ): void {
		global $racketmanager;
		$raw_type      = $event->competition->type ?? 'league';
		$comp_type     = $raw_type instanceof Competition_Type ? $raw_type->value : (string) $raw_type;
		$email_address = method_exists( $racketmanager, 'get_confirmation_email' )
			? $racketmanager->get_confirmation_email( $comp_type )
			: ( is_callable( $racketmanager->get_confirmation_email ?? null ) ? ( $racketmanager->get_confirmation_email )( $comp_type ) : '' );
		$organisation  = $racketmanager->site_name ?? '';
		$message_args  = [
			'organisation'  => $organisation,
			'event'         => $event->name,
			'emailfrom'     => $email_address,
			'template_type' => 'email',
		];
		$email_message = constitution_notification( $event->id, $message_args );
		$headers       = [];
		$headers[]     = RACKETMANAGER_FROM_EMAIL . ucfirst( $comp_type ) . ' Secretary <' . $email_address . '>';
		$headers[]     = RACKETMANAGER_CC_EMAIL . ucfirst( $comp_type ) . ' Secretary <' . $email_address . '>';
		$clubs         = method_exists( $racketmanager, 'get_clubs' )
			? $racketmanager->get_clubs( [ 'type' => 'affiliated' ] )
			: ( is_callable( $racketmanager->get_clubs ?? null ) ? ( $racketmanager->get_clubs )( [ 'type' => 'affiliated' ] ) : [] );
		$clubs         = is_iterable( $clubs ) ? $clubs : [];
		$subject       = $organisation . ' - ' . $event->name . ' ' . $season['name'] . ' - Constitution';

		foreach ( $clubs as $club ) {
			if ( ! empty( $club->match_secretary->email ) ) {
				$headers[] = RACKETMANAGER_BCC_EMAIL . $club->match_secretary->display_name . ' <' . $club->match_secretary->email . '>';
			}
		}

		$this->mailer->send( $email_address, $subject, $email_message, $headers );
	}
}
