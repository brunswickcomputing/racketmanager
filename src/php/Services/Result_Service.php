<?php
declare( strict_types=1 );

namespace Racketmanager\Services;

use Racketmanager\Domain\Result\Result;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Util\Util;

class Result_Service {
    /**
     * @var Fixture_Repository_Interface
     */
    private Fixture_Repository_Interface $fixture_repository;

    /**
     * @var Team_Repository_Interface
     */
    private Team_Repository_Interface $team_repository;

    /**
     * @var Mailer_Interface
     */
    private Mailer_Interface $mailer;

    /**
     * League lookups by fixture league id; without a repository no league can be resolved.
     */
    private ?League_Repository_Interface $league_repository;

    public function __construct( Fixture_Repository_Interface $fixture_repository, Team_Repository_Interface $team_repository, ?Mailer_Interface $mailer = null, ?League_Repository_Interface $league_repository = null ) {
        $this->league_repository = $league_repository;
        $this->fixture_repository = $fixture_repository;
        $this->team_repository    = $team_repository;
        $this->mailer             = $mailer ?? new Wp_Mailer();
    }

    /**
     * Look the fixture's league up by id through the injected repository (null when unknown).
     */
    private function find_league( Fixture $fixture ): ?League {
        $league_id = (int) $fixture->get_league_id();
        if ( $league_id <= 0 || null === $this->league_repository ) {
            return null;
        }
        return $this->league_repository->find_by_id( $league_id );
    }

    /**
     * Applies a result to a fixture and triggers subsequent actions (draw updates, etc.)
     *
     * @param Fixture $fixture The fixture to update.
     * @param Result $result The result to apply.
     * @param string|null $confirmed Confirmation status ('Y', 'N', 'P').
     */
    public function apply_to_fixture( Fixture $fixture, Result $result, ?string $confirmed = null ): void {
        global $racketmanager;
        
        $league = $this->find_league( $fixture );
        $competition_type = $league?->event?->competition?->type ?? 'league';
        $rm_options = $racketmanager->get_options();
        $result_confirmation = $rm_options[ $competition_type ]['resultConfirmation'] ?? 'manual';

        // Handle auto-confirmation and admin override
        if ( null === $confirmed ) {
            $confirmed = $fixture->get_confirmed() ?: 'P';
        }

        // Only auto-confirm if it's an admin OR if we are updating an already actioned match
        $is_already_actioned = 'Y' === $fixture->get_confirmed();
        $is_admin            = current_user_can( 'manage_racketmanager' );

        if ( ! $result->is_reset() && ( $is_admin || ( 'auto' === $result_confirmation && $is_already_actioned ) ) ) {
            $confirmed = 'Y';
        }

        if ( $result->is_reset() ) {
            $confirmed = null;
        }

        // 1. Update the fixture object with the result details
        $fixture->set_result( $result );
        $fixture->set_confirmed( $confirmed );

        // 2. Persist to database (via Repository)
        $this->fixture_repository->save( $fixture );

        // 3. Notify favourites if it's not a bye and not a reset
        if ( ! $result->is_reset() && '-1' !== (string) $fixture->get_home_team() && '-1' !== (string) $fixture->get_away_team() ) {
            $this->notify_favourites( $fixture );
        }
    }

    /**
     * Notify users who have favourited the teams, league, or competition.
     *
     * @param Fixture $fixture
     */
    public function notify_favourites( Fixture $fixture ): void {
        global $racketmanager;

        $league = $this->find_league( $fixture );
        if ( ! $league ) {
            return;
        }

        $favourited_users = array();
        $users            = Util::get_users_for_favourite( 'league', (string) $league->id );
        foreach ( $users as $user ) {
            $favourited_users[] = $user;
        }

        $users = Util::get_users_for_favourite( 'competition', (string) $league->event->id );
        foreach ( $users as $user ) {
            $favourited_users[] = $user;
        }

        $home_team = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );

        if ( $home_team ) {
            $users = Util::get_users_for_favourite( 'team', (string) $home_team->get_id() );
            foreach ( $users as $user ) {
                $favourited_users[] = $user;
            }
            if ( $home_team->get_club_id() ) {
                $users = Util::get_users_for_favourite( 'club', (string) $home_team->get_club_id() );
                foreach ( $users as $user ) {
                    $favourited_users[] = $user;
                }
            }
        }

        if ( $away_team ) {
            $users = Util::get_users_for_favourite( 'team', (string) $away_team->get_id() );
            foreach ( $users as $user ) {
                $favourited_users[] = $user;
            }
            if ( $away_team->get_club_id() ) {
                $users = Util::get_users_for_favourite( 'club', (string) $away_team->get_club_id() );
                foreach ( $users as $user ) {
                    $favourited_users[] = $user;
                }
            }
        }

        $favourited_users = array_unique( $favourited_users, SORT_REGULAR );
        if ( empty( $favourited_users ) ) {
            return;
        }

        $headers           = array();
        $competition_type  = $league->event->competition->type;
        $from_email        = $racketmanager->get_confirmation_email( $competition_type );
        $headers[]         = RACKETMANAGER_FROM_EMAIL . ucfirst( $competition_type ) . ' Secretary <' . $from_email . '>';
        $organisation_name = $racketmanager->site_name;
        $email_subject     = $racketmanager->site_name . ' - ' . $league->title . ' Result Notification';
        $favourite_url     = $racketmanager->site_url . '/member-account/favourites';
        
        // We need a link to the fixture, which might not be fully implemented in the new Fixture class yet
        $match_url = $racketmanager->site_url . '/fixture/' . $fixture->get_id();

        foreach ( $favourited_users as $user ) {
            $user_details  = get_userdata( $user );
            if ( ! $user_details ) {
                continue;
            }
            $email_to      = $user_details->display_name . ' <' . $user_details->user_email . '>';
            $email_message = $racketmanager->shortcodes->load_template(
                'favourite-notification',
                array(
                    'email_subject' => $email_subject,
                    'from_email'    => $from_email,
                    'match_url'     => $match_url,
                    'favourite_url' => $favourite_url,
                    'organisation'  => $organisation_name,
                    'user'          => $user_details,
                    'fixture'       => $fixture,
                    'league'        => $league,
                    'home_team'     => $home_team,
                    'away_team'     => $away_team,
                ),
                'email'
            );
            $this->mailer->send( $email_to, $email_subject, $email_message, $headers );
        }
    }

}
