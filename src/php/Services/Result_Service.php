<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Result\Result;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Util\Util;
use function Racketmanager\get_league;

class Result_Service {
    /**
     * @var Fixture_Repository
     */
    private Fixture_Repository $fixture_repository;

    /**
     * @var Team_Repository
     */
    private Team_Repository $team_repository;

    public function __construct( Fixture_Repository $fixture_repository, Team_Repository $team_repository ) {
        $this->fixture_repository = $fixture_repository;
        $this->team_repository    = $team_repository;
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
        
        $league = get_league( $fixture->get_league_id() );
        $competition_type = $league?->event?->competition?->type ?? 'league';
        $rm_options = $racketmanager->get_options();
        $result_confirmation = $rm_options[ $competition_type ]['resultConfirmation'] ?? 'manual';

        // Handle auto-confirmation and admin override
        if ( null === $confirmed ) {
            $confirmed = $fixture->get_confirmed() ?: 'P';
        }

        if ( ! $result->is_reset() && ( 'auto' === $result_confirmation || current_user_can( 'manage_racketmanager' ) ) ) {
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

        $league = get_league( $fixture->get_league_id() );
        if ( ! $league ) {
            return;
        }

        $favourited_users = array();
        $users            = Util::get_users_for_favourite( 'league', $league->id );
        foreach ( $users as $user ) {
            $favourited_users[] = $user;
        }

        $users = Util::get_users_for_favourite( 'competition', $league->event->id );
        foreach ( $users as $user ) {
            $favourited_users[] = $user;
        }

        $home_team = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );

        if ( $home_team ) {
            $users = Util::get_users_for_favourite( 'team', $home_team->get_id() );
            foreach ( $users as $user ) {
                $favourited_users[] = $user;
            }
            if ( $home_team->get_club_id() ) {
                $users = Util::get_users_for_favourite( 'club', $home_team->get_club_id() );
                foreach ( $users as $user ) {
                    $favourited_users[] = $user;
                }
            }
        }

        if ( $away_team ) {
            $users = Util::get_users_for_favourite( 'team', $away_team->get_id() );
            foreach ( $users as $user ) {
                $favourited_users[] = $user;
            }
            if ( $away_team->get_club_id() ) {
                $users = Util::get_users_for_favourite( 'club', $away_team->get_club_id() );
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
            wp_mail( $email_to, $email_subject, $email_message, $headers );
        }
    }

}
