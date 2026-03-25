<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Notification;

use Racketmanager\Domain\Fixture;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Team_Repository;
use function Racketmanager\captain_result_notification;
use function Racketmanager\match_team_withdrawn_notification;
use function Racketmanager\result_notification;

/**
 * Service for handling fixture-related notifications.
 */
class Notification_Service {
    /**
     * League Repository.
     *
     * @var League_Repository
     */
    private League_Repository $league_repository;

    /**
     * League Team Repository.
     *
     * @var League_Team_Repository
     */
    private League_Team_Repository $league_team_repository;

    /**
     * Team Repository.
     *
     * @var Team_Repository
     */
    private Team_Repository $team_repository;

    /**
     * Player Repository.
     *
     * @var Player_Repository
     */
    private Player_Repository $player_repository;

    /**
     * Club Repository.
     *
     * @var Club_Repository
     */
    private Club_Repository $club_repository;

    /**
     * Constructor.
     *
     * @param League_Repository      $league_repository
     * @param League_Team_Repository $league_team_repository
     * @param Team_Repository        $team_repository
     * @param Player_Repository      $player_repository
     * @param Club_Repository        $club_repository
     */
    public function __construct(
        League_Repository $league_repository,
        League_Team_Repository $league_team_repository,
        Team_Repository $team_repository,
        Player_Repository $player_repository,
        Club_Repository $club_repository
    ) {
        $this->league_repository      = $league_repository;
        $this->league_team_repository = $league_team_repository;
        $this->team_repository        = $team_repository;
        $this->player_repository      = $player_repository;
        $this->club_repository        = $club_repository;
    }

    /**
     * Send result notification for a fixture.
     *
     * @param Fixture $fixture
     * @param string $match_status
     * @param string $match_message
     * @param string|false $match_updated_by
     */
    public function send_result_notification( Fixture $fixture, string $match_status, string $match_message, string|false $match_updated_by = false ): void {
        global $racketmanager;

        $league = $this->league_repository->find_by_id( $fixture->get_league_id() );
        if ( ! $league ) {
            return;
        }

        $admin_email = $racketmanager->get_confirmation_email( $league->event->competition->type );
        if ( empty( $admin_email ) ) {
            return;
        }

        $rm_options            = $racketmanager->get_options();
        $result_notification   = $rm_options[ $league->event->competition->type ]['resultNotification'];
        $confirmation_required = $rm_options[ $league->event->competition->type ]['confirmationRequired'];
        $confirmation_timeout  = $rm_options[ $league->event->competition->type ]['confirmationTimeout'];

        $message_args               = array();
        $message_args['email_from'] = $admin_email;
        $message_args['league']     = $league->id;

        if ( $league->is_championship ) {
            $message_args['round'] = $fixture->get_final();
        } else {
            $message_args['matchday'] = $fixture->get_match_day();
        }

        $headers = array();
        // Match title construction
        $home_team = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );
        $match_title = ( $home_team && $away_team ) ? $home_team->get_name() . ' v ' . $away_team->get_name() : '';

        $subject = $racketmanager->site_name . ' - ' . $league->title . ' - ' . $match_title . ' - ' . $match_message;

        $confirmation_email = $this->get_confirmation_email( $fixture, $match_status, (string) $match_updated_by, $result_notification );

        if ( $confirmation_email ) {
            $email_to  = $confirmation_email;
            $headers[] = $racketmanager->get_from_user_email();
            $headers[] = RACKETMANAGER_CC_EMAIL . ucfirst( $league->event->competition->type ) . ' Secretary <' . $admin_email . '>';

            if ( $confirmation_required ) {
                $subject .= ' - ' . __( 'Confirmation required', 'racketmanager' );
            }

            $message_args['confirmation_required'] = $confirmation_required;
            $message_args['confirmation_timeout']  = $confirmation_timeout;
            $message                               = captain_result_notification( $fixture->get_id(), $message_args );
        } else {
            $email_to  = $admin_email;
            $headers[] = $racketmanager->get_from_user_email();

            if ( 'Y' === $match_status ) {
                // To replace has_result_check(), we'd need to migrate that logic too.
                // For now, let's see if we can use the fixture status or similar.
                // In legacy, it checked a separate results_checker table.
                $message_args['complete'] = true;
                $subject                 .= ' - ' . __( 'Match complete', 'racketmanager' );
            } elseif ( 'C' === $match_status ) {
                $message_args['challenge'] = true;
            }
            $message = result_notification( $fixture->get_id(), $message_args );
        }

        wp_mail( $email_to, $subject, $message, $headers );
    }

    /**
     * Resolve confirmation email recipient.
     *
     * @param Fixture $fixture
     * @param string $match_status
     * @param string $match_updated_by
     * @param string $result_notification_setting
     * @return string|null
     */
    private function get_confirmation_email( Fixture $fixture, string $match_status, string $match_updated_by, string $result_notification_setting ): ?string {
        if ( 'P' !== $match_status && 'both' !== $match_updated_by ) {
            return null;
        }

        $opponent_team_id = 'home' === $match_updated_by ? (int) $fixture->get_away_team() : (int) $fixture->get_home_team();

        return match ( $result_notification_setting ) {
            'captain'   => $this->get_team_captain_email( $opponent_team_id, (int) $fixture->get_league_id(), (int) $fixture->get_season() ),
            'secretary' => $this->get_club_secretary_email( $opponent_team_id ),
            default     => null,
        };
    }

    /**
     * Get the email address of the team captain for a specific league and season.
     *
     * @param int $team_id
     * @param int $league_id
     * @param int $season_id
     * @return string|null
     */
    private function get_team_captain_email( int $team_id, int $league_id, int $season_id ): ?string {
        $league_team = $this->league_team_repository->find_by_team_league_and_season( $team_id, $league_id, $season_id );
        if ( $league_team && $league_team->get_captain() ) {
            $captain = $this->player_repository->find( $league_team->get_captain() );
            return $captain?->get_email();
        }

        return null;
    }

    /**
     * Get the email address of the club match secretary for a team.
     *
     * @param int $team_id
     * @return string|null
     */
    private function get_club_secretary_email( int $team_id ): ?string {
        $league_team = $this->league_team_repository->find_by_id( $team_id );
        if ( $league_team ) {
            $club = $this->club_repository->find( $league_team->club_id );
            return $club?->match_secretary->email ?? null;
        }

        return null;
    }

    /**
     * Notify opponents when a team is withdrawn.
     *
     * @param Fixture $fixture
     * @param int $team_id The withdrawn team ID
     */
    public function notify_team_withdrawal( Fixture $fixture, int $team_id ): void {
        global $racketmanager;

        $league = $this->league_repository->find_by_id( $fixture->get_league_id() );
        if ( ! $league ) {
            return;
        }

        $admin_email = $racketmanager->get_confirmation_email( $league->event->competition->type );
        if ( empty( $admin_email ) ) {
            return;
        }

        $home_team = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );

        if ( ! $home_team || ! $away_team ) {
            return;
        }

        $match_title = $home_team->get_name() . ' v ' . $away_team->get_name();
        $subject     = $racketmanager->site_name . ' - ' . $league->title . ' - ' . $match_title . ' - ' . __( 'Team withdrawn', 'racketmanager' );
        $headers     = array();
        $headers[]   = $racketmanager->get_from_user_email();
        $headers[]   = RACKETMANAGER_CC_EMAIL . ucfirst( $league->event->competition->type ) . ' Secretary <' . $admin_email . '>';

        $message_args = array(
            'match'   => $fixture->get_id(),
            'team_id' => $team_id,
        );

        // Determine who to notify (the opponent of the withdrawn team)
        if ( $home_team->get_id() === $team_id ) {
            $email_to = $this->get_team_captain_email( (int) $away_team->get_id(), (int) $fixture->get_league_id(), (int) $fixture->get_season() );
        } else {
            $email_to = $this->get_team_captain_email( (int) $home_team->get_id(), (int) $fixture->get_league_id(), (int) $fixture->get_season() );
        }

        if ( ! empty( $email_to ) ) {
            $message = match_team_withdrawn_notification( $fixture->get_id(), $message_args );
            wp_mail( $email_to, $subject, $message, $headers );
        }
    }
}
