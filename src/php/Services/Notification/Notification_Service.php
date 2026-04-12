<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Notification;

use Racketmanager\Domain\Results_Checker;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Settings_Service;
use Racketmanager\RacketManager;
use function Racketmanager\captain_result_notification;
use function Racketmanager\match_date_change_notification;
use function Racketmanager\match_notification;
use function Racketmanager\match_team_withdrawn_notification;
use function Racketmanager\result_notification;
use function Racketmanager\result_outstanding_notification;

/**
 * Service for handling fixture-related notifications.
 */
class Notification_Service {
    /**
     * League Repository.
     *
     * @var League_Repository_Interface
     */
    private League_Repository_Interface $league_repository;

    /**
     * League Team Repository.
     *
     * @var League_Team_Repository_Interface
     */
    private League_Team_Repository_Interface $league_team_repository;

    /**
     * Team Repository.
     *
     * @var Team_Repository_Interface
     */
    private Team_Repository_Interface $team_repository;

    /**
     * Player Repository.
     *
     * @var Player_Repository_Interface
     */
    private Player_Repository_Interface $player_repository;

    /**
     * Club Repository.
     *
     * @var Club_Repository_Interface
     */
    private Club_Repository_Interface $club_repository;

    /**
     * Settings Service.
     *
     * @var Settings_Service
     */
    private Settings_Service $settings_service;

    /**
     * RacketManager App.
     *
     * @var RacketManager
     */
    private RacketManager $app;

    /**
     * Constructor.
     *
     * @param League_Repository_Interface      $league_repository
     * @param League_Team_Repository_Interface $league_team_repository
     * @param Team_Repository_Interface        $team_repository
     * @param Player_Repository_Interface      $player_repository
     * @param Club_Repository_Interface        $club_repository
     * @param Settings_Service       $settings_service
     * @param RacketManager          $app
     */
    public function __construct(
        League_Repository_Interface $league_repository,
        League_Team_Repository_Interface $league_team_repository,
        Team_Repository_Interface $team_repository,
        Player_Repository_Interface $player_repository,
        Club_Repository_Interface $club_repository,
        Settings_Service $settings_service,
        RacketManager $app
    ) {
        $this->league_repository      = $league_repository;
        $this->league_team_repository = $league_team_repository;
        $this->team_repository        = $team_repository;
        $this->player_repository      = $player_repository;
        $this->club_repository        = $club_repository;
        $this->settings_service       = $settings_service;
        $this->app                    = $app;
    }

    /**
     * Send result notification for a fixture.
     *
     * @param Fixture $fixture
     * @param string $fixture_status
     * @param string $fixture_message
     * @param string|false $fixture_updated_by
     */
    public function send_result_notification( Fixture $fixture, string $fixture_status, string $fixture_message, string|false $fixture_updated_by = false ): void {
        $league = $this->league_repository->find_by_id( $fixture->get_league_id() );
        if ( ! $league ) {
            return;
        }

        $admin_email = $this->app->get_confirmation_email( $league->event->competition->type );
        if ( empty( $admin_email ) ) {
            return;
        }

        $options               = $this->settings_service->get_category( $league->event->competition->type );
        $result_notification   = $options['resultNotification'] ?? 'none';
        $confirmation_required = $options['confirmationRequired'] ?? false;
        $confirmation_timeout  = $options['confirmationTimeout'] ?? 0;

        $message_args               = array();
        $message_args['email_from'] = $admin_email;
        $message_args['league']     = $league->id;

        if ( $league->is_championship ) {
            $message_args['round'] = $fixture->get_final();
        } else {
            $message_args['matchday'] = $fixture->get_match_day();
        }

        $headers = array();
        // Fixture title construction
        $home_team = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );
        $fixture_title = $this->get_fixture_title( $home_team, $away_team );

        $subject = $this->app->site_name . ' - ' . $league->title . ' - ' . $fixture_title . ' - ' . $fixture_message;

        $confirmation_email = $this->get_confirmation_email( $fixture, $fixture_status, (string) $fixture_updated_by, $result_notification );

        if ( $confirmation_email ) {
            $email_to  = $confirmation_email;
            $headers[] = $this->app->get_from_user_email();
            $headers[] = RACKETMANAGER_CC_EMAIL . ucfirst( $league->event->competition->type ) . ' Secretary <' . $admin_email . '>';

            if ( $confirmation_required ) {
                $subject .= ' - ' . __( 'Confirmation required', 'racketmanager' );
            }

            $message_args['confirmation_required'] = $confirmation_required;
            $message_args['confirmation_timeout']  = $confirmation_timeout;
            $message                               = captain_result_notification( $fixture->get_id(), $message_args );
        } else {
            $email_to  = $admin_email;
            $headers[] = $this->app->get_from_user_email();

            if ( 'Y' === $fixture_status ) {
                // To replace has_result_check(), we'd need to migrate that logic too.
                // For now, let's see if we can use the fixture status or similar.
                // In legacy, it checked a separate results_checker table.
                $message_args['complete'] = true;
                $subject                 .= ' - ' . __( 'Fixture complete', 'racketmanager' );
            } elseif ( 'C' === $fixture_status ) {
                $message_args['challenge'] = true;
            }
            $message = result_notification( $fixture->get_id(), $message_args );
        }

        wp_mail( $email_to, $subject, $message, $headers );
    }

    /**
     * Send a result error/penalty notification.
     *
     * @param Results_Checker $checker
     * @param Fixture         $fixture
     * @param float           $penalty
     */
    public function send_result_error_notification( Results_Checker $checker, Fixture $fixture, float $penalty ): void {
        $organisation_name = $this->app->site_name;
        $league            = $this->league_repository->find_by_id( $fixture->get_league_id() );
        if ( ! $league ) {
            return;
        }

        $email_from = $this->app->get_confirmation_email( $league->event->competition->type );
        $headers    = array( 'From: ' . $organisation_name . ' <' . $email_from . '>' );

        $subject = sprintf(
            /* translators: 1: organisation name, 2: fixture title */
            __( '[%1$s] Result Error: %2$s', 'racketmanager' ),
            $organisation_name,
            $fixture->fixture_title
        );

        $message = sprintf(
            /* translators: 1: fixture title, 2: description, 3: penalty */
            __( "The result for %1\$s has been flagged with the following error:\n\n%2\$s\n\nA penalty of %3\$s points has been applied.", 'racketmanager' ),
            $fixture->fixture_title,
            $checker->description,
            $penalty
        );

        $emails = array();
        $team_id = (int) $fixture->get_home_team();
        if ( $team_id > 0 ) {
            $captain_email = $this->get_team_captain_email( $team_id, (int) $league->id, (int) $fixture->get_season() );
            if ( $captain_email ) {
                $emails[] = $captain_email;
            }

            $league_team = $this->league_team_repository->find_by_id( $team_id );
            if ( $league_team ) {
                $club = $this->club_repository->find_by_id( $league_team->club_id );
                if ( $club && isset( $club->match_secretary->email ) ) {
                    $headers[] = RACKETMANAGER_CC_EMAIL . $club->match_secretary->display_name . ' <' . $club->match_secretary->email . '>';
                }
            }
        }

        if ( empty( $emails ) && ! empty( $fixture->home_captain ) ) {
            $user_info = get_userdata( $fixture->home_captain );
            if ( $user_info ) {
                $emails[] = $user_info->user_email;
            }
        }

        if ( ! empty( $emails ) ) {
            wp_mail( $emails, $subject, $message, $headers );
        }
    }

    /**
     * Get match title.
     *
     * @param object|null $home_team
     * @param object|null $away_team
     * @return string
     */
    private function get_fixture_title( ?object $home_team, ?object $away_team ): string {
        return ( $home_team && $away_team ) ? $home_team->get_name() . ' v ' . $away_team->get_name() : '';
    }

    /**
     * Send chase result notification for a fixture.
     *
     * @param Fixture $fixture
     * @param array $args
     */
    public function send_chase_result_notification( Fixture $fixture, array $args ): void {
        $data = $this->prepare_chase_notification( $fixture, $args );
        if ( ! $data ) {
            return;
        }

        $email_to = array();
        if ( $data['home_team'] ) {
            $captain_email = $this->get_team_captain_email( (int) $data['home_team']->get_id(), (int) $data['league']->id, (int) $fixture->get_season() );
            if ( $captain_email ) {
                $email_to[] = $captain_email;
            }

            $club = $this->club_repository->find_by_id( (int) $data['home_team']->get_club_id() );
            if ( $club && isset( $club->match_secretary->email ) ) {
                $data['headers'][] = RACKETMANAGER_CC_EMAIL . $club->match_secretary->display_name . ' <' . $club->match_secretary->email . '>';
            }
        }

        if ( ! empty( $email_to ) ) {
            $email_subject = __( 'Fixture result pending', 'racketmanager' ) . ' - ' . $data['fixture_title'] . ' - ' . $data['league']->title;
            $email_message = result_outstanding_notification( $fixture->get_id(), $args );
            wp_mail( $email_to, $email_subject, $email_message, $data['headers'] );
        }
    }

    /**
     * Send chase approval notification for a fixture.
     *
     * @param Fixture $fixture
     * @param array $args
     */
    public function send_chase_approval_notification( Fixture $fixture, array $args ): void {
        $data = $this->prepare_chase_notification( $fixture, $args );
        if ( ! $data ) {
            return;
        }

        $email_to           = array();
        $fixture_updated_by = $fixture->get_updated_by();
        $opponent_team_id   = 'home' === $fixture_updated_by ? (int) $fixture->get_away_team() : (int) $fixture->get_home_team();
        $opponent_team      = $this->team_repository->find_by_id( $opponent_team_id );

        if ( $opponent_team ) {
            $captain_email = $this->get_team_captain_email( (int) $opponent_team->get_id(), (int) $data['league']->id, (int) $fixture->get_season() );
            if ( $captain_email ) {
                $email_to[] = $captain_email;
            }

            $club = $this->club_repository->find_by_id( (int) $opponent_team->get_club_id() );
            if ( $club && isset( $club->match_secretary->email ) ) {
                $data['headers'][] = RACKETMANAGER_CC_EMAIL . $club->match_secretary->display_name . ' <' . $club->match_secretary->email . '>';
            }
        }

        if ( ! empty( $email_to ) ) {
            $email_subject = __( 'Fixture result approval', 'racketmanager' ) . ' - ' . $data['fixture_title'] . ' - ' . $data['league']->title;
            $email_message = captain_result_notification( $fixture->get_id(), $args );
            wp_mail( $email_to, $email_subject, $email_message, $data['headers'] );
        }
    }

    /**
     * Prepare common data for chase notifications.
     *
     * @param Fixture $fixture
     * @param array $args
     * @return array|null
     */
    private function prepare_chase_notification( Fixture $fixture, array $args ): ?array {
        $league = $this->league_repository->find_by_id( $fixture->get_league_id() );
        if ( ! $league ) {
            return null;
        }

        $admin_email = $args['from_email'] ?? $this->app->get_confirmation_email( $league->event->competition->type );
        if ( empty( $admin_email ) ) {
            return null;
        }

        $headers   = array();
        $headers[] = $this->app->get_from_user_email();
        $headers[] = RACKETMANAGER_CC_EMAIL . ucfirst( $league->event->competition->type ) . ' Secretary <' . $admin_email . '>';

        $home_team = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );
        $fixture_title = $this->get_fixture_title( $home_team, $away_team );

        return array(
            'league'        => $league,
            'admin_email'   => $admin_email,
            'headers'       => $headers,
            'home_team'     => $home_team,
            'away_team'     => $away_team,
            'fixture_title' => $fixture_title,
        );
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
            $club = $this->club_repository->find_by_id( $league_team->club_id );
            return $club?->match_secretary->email ?? null;
        }

        return null;
    }

    /**
     * Get the email addresses for a team or both teams in a fixture.
     *
     * @param Fixture $fixture
     * @param string $target 'home', 'away', or 'both'
     * @return array
     */
    public function get_fixture_emails( Fixture $fixture, string $target = 'both' ): array {
        $emails = array();

        if ( 'home' === $target || 'both' === $target ) {
            $email = $this->get_team_captain_email( (int) $fixture->get_home_team(), (int) $fixture->get_league_id(), (int) $fixture->get_season() );
            if ( $email ) {
                $emails[] = $email;
            }
        }

        if ( 'away' === $target || 'both' === $target ) {
            $email = $this->get_team_captain_email( (int) $fixture->get_away_team(), (int) $fixture->get_league_id(), (int) $fixture->get_season() );
            if ( $email ) {
                $emails[] = $email;
            }
        }

        return array_unique( $emails );
    }

    /**
     * Prepare common notification data like league, emails, and headers.
     *
     * @param Fixture $fixture
     * @return array|null Returns array containing [league, email_to, admin_email, headers] or null on failure.
     */
    private function prepare_notification_base_data( Fixture $fixture ): ?array {
        $league   = $this->league_repository->find_by_id( $fixture->get_league_id() );
        $email_to = $this->get_fixture_emails( $fixture );

        if ( ! $league || empty( $email_to ) || -1 === (int) $fixture->get_home_team() || -1 === (int) $fixture->get_away_team() ) {
            return null;
        }

        $admin_email = $this->app->get_confirmation_email( $league->event->competition->type );
        $headers     = array(
            $this->app->get_from_user_email(),
            RACKETMANAGER_CC_EMAIL . ucfirst( $league->event->competition->type ) . ' Secretary <' . $admin_email . '>',
        );

        return [
            'league'      => $league,
            'email_to'    => $email_to,
            'admin_email' => $admin_email,
            'headers'     => $headers,
        ];
    }

    /**
     * Notify teams when their next match is confirmed (e.g. in knockout draws).
     *
     * @param Fixture $fixture
     * @return void
     */
    public function send_next_fixture_notification( Fixture $fixture ): void {
        $base_data = $this->prepare_notification_base_data( $fixture );
        if ( ! $base_data ) {
            return;
        }

        $league      = $base_data['league'];
        $email_to    = $base_data['email_to'];
        $admin_email = $base_data['admin_email'];
        $headers     = $base_data['headers'];

        $message_args = array(
            'competition_type' => $league->event->competition->type,
            'emailfrom'        => $admin_email,
        );

        if ( 'tournament' === $league->event->competition->type ) {
            $message_args['tournament'] = $league->event->competition_id;
        } elseif ( 'cup' === $league->event->competition->type ) {
            $message_args['competition'] = $league->event->competition->name;
        }

        $round_name = '';
        if ( $league->is_championship && $league->championship ) {
            $round_name           = $league->championship->finals[ $fixture->get_final() ]['name'] ?? '';
            $message_args['round'] = $round_name;
        }

        $email_message = match_notification( $fixture->get_id(), $message_args );
        $subject       = __( 'Fixture Details', 'racketmanager' ) . ( $round_name ? ' - ' . $round_name : '' );

        if ( $fixture->get_leg() ) {
            $subject .= ' - ' . __( 'Leg', 'racketmanager' ) . ' ' . $fixture->get_leg();
        }
        $subject .= ' - ' . $league->title;

        wp_mail( $email_to, $subject, $email_message, $headers );
    }

    /**
     * Notify teams when a fixture date or time has changed.
     *
     * @param Fixture $fixture
     * @return void
     */
    public function send_date_change_notification( Fixture $fixture ): void {
        $base_data = $this->prepare_notification_base_data( $fixture );
        if ( ! $base_data ) {
            return;
        }

        $league      = $base_data['league'];
        $email_to    = $base_data['email_to'];
        $admin_email = $base_data['admin_email'];
        $headers     = $base_data['headers'];

        $round_name = '';
        if ( $league->is_championship && $league->championship ) {
            $round_name = $league->championship->finals[ $fixture->get_final() ]['name'] ?? '';
        }

        $message_args = array(
            'fixture'          => $fixture->get_id(),
            'round'            => $round_name,
            'new_date'         => $fixture->get_date(),
            'original_date'    => $fixture->get_date_original(),
            'competition_type' => $league->event->competition->type,
            'emailfrom'        => $admin_email,
        );

        if ( 'tournament' === $league->event->competition->type || 'cup' === $league->event->competition->type || 'league' === $league->event->competition->type ) {
            $message_args['competition'] = $league->event->competition->name;
        }

        $delay = false;
        if ( $league->event->competition->is_tournament && $fixture->get_date() > $fixture->get_date_original() ) {
            $message_args['delay'] = true;
            $delay                  = true;
        }

        $subject = __( 'Fixture Date Change', 'racketmanager' );
        if ( $delay ) {
            $subject .= ' ' . __( 'DELAY', 'racketmanager' );
        }
        if ( $round_name ) {
            $subject .= ' - ' . $round_name;
        }
        if ( $fixture->get_leg() ) {
            $subject .= ' - ' . __( 'Leg', 'racketmanager' ) . ' ' . $fixture->get_leg();
        }
        $subject .= ' - ' . $league->title;

        $message_args['email_subject'] = $subject;
        $email_message                 = match_date_change_notification( $fixture->get_id(), $message_args );

        wp_mail( $email_to, $subject, $email_message, $headers );
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

        $fixture_title = $home_team->get_name() . ' v ' . $away_team->get_name();
        $subject       = $racketmanager->site_name . ' - ' . $league->title . ' - ' . $fixture_title . ' - ' . __( 'Team withdrawn', 'racketmanager' );
        $headers     = array();
        $headers[]   = $racketmanager->get_from_user_email();
        $headers[]   = RACKETMANAGER_CC_EMAIL . ucfirst( $league->event->competition->type ) . ' Secretary <' . $admin_email . '>';

        $message_args = array(
            'fixture' => $fixture->get_id(),
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
