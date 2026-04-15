<?php

namespace Racketmanager\Presenters;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use stdClass;

readonly class Notification_Presenter {

    public function __construct(
        private Team_Repository_Interface $team_repository,
        private Club_Repository_Interface $club_repository,
        private Tournament_Repository_Interface $tournament_repository,
        private string $site_url,
        private string $site_name
    ) {
    }

    /**
     * Prepare variables for match-notification template.
     *
     * Mirrors logic in Shortcodes_Email::show_match_notification
     */
    public function present_match_notification( Fixture $fixture, array $args ): array {
        $match = \Racketmanager\get_match( $fixture->get_id() );

        $template          = $args['template'] ?? '';
        $tournament_id     = $args['tournament'] ?? false;
        $tournament_search = $args['tournament_search'] ?? 'id';
        $competition       = $args['competition'] ?? '';
        $email_from       = $args['email_from'] ?? ($args['emailfrom'] ?? '');
        $round            = $args['round'] ?? '';
        $competition_type = $args['competition_type'] ?? '';
        $organisation     = $this->site_name;

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
        $rules_link      = $this->site_url . '/rules/' . $competition_type . '-rules/';

        $tournament = null;

        if ( 'tournament' === $competition_type ) {
            $tournament = $this->tournament_repository->find_by_id( $tournament_id, $tournament_search );
            if ( $tournament ) {
                $tournament_link = '<a href="' . $this->site_url . $tournament->link . '">' . $tournament->name . '</a>';
                $draw_link       = '<a href="' . $this->site_url . $tournament->link . 'draw/' . \Racketmanager\seo_url( $match->league->event->name ) . '/">' . $match->league->event->name . '</a>';
                $match_link      = $this->site_url . $tournament->link . '/match/' . \Racketmanager\seo_url( $match->league->title ) . '/' . \Racketmanager\seo_url( $match->teams['home']->title ) . '-vs-' . \Racketmanager\seo_url( $match->teams['away']->title ) . '/' . $fixture->get_id() . '/';
            }

            if ( str_contains( $match->league->type ?? '', 'D' ) ) {
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
            $cup_link   = '<a href="' . $this->site_url . '/cups/' . \Racketmanager\seo_url( $match->league->title ) . '/' . $fixture->get_season() . '/">' . $match->league->title . '</a>';
            $match_link = $this->site_url . $match->link;
            if ( $fixture->get_leg() ) {
                $match_link .= 'leg-' . $fixture->get_leg() . '/';
            }
            $template             = 'match-notification-cup';
            $teams['home']->title = __( 'Home Team', 'racketmanager' );
            $teams['away']->title = __( 'Away Team', 'racketmanager' );
            $home_dtls['title']   = 'Home Team';
            $away_dtls['title']   = 'Away Team';
        }

        if ( ! $tournament ) {
            $tournament       = new stdClass();
            $tournament->name = $competition ?: ( $match->league->title ?? '' );
            $tournament->link = '';
        }

        // Logic for team names and details
        $home_team_obj = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team_obj = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );

        $host = $fixture->get_host() ?: 'home';
        if ( 'home' === $host ) {
            $team_1_key = 'home';
            $team_2_key = 'away';
        } else {
            $team_1_key = 'away';
            $team_2_key = 'home';
        }

        $teams[$team_1_key]->name = $match->teams['home']->title;
        $teams[$team_2_key]->name = $match->teams['away']->title;

        // Club info
        $teams['home']->club = $this->get_club_shortcode( (int) $fixture->get_home_team() );
        $teams['away']->club = $this->get_club_shortcode( (int) $fixture->get_away_team() );

        // Player/Captain details
        if ( $home_team_obj && 'P' === ($home_team_obj->team_type ?? '') ) {
            $teams['home']->player = $match->teams['home']->players;
            $teams['away']->player = $match->teams['away']->players;
        } else {
            // For team matches, use captain info
            $teams['home']->captain       = $home_team_obj->captain ?? '';
            $teams['home']->captain_email = $home_team_obj->contactemail ?? '';
            $teams['home']->captain_tel   = $home_team_obj->contactno ?? '';
            $teams['home']->matchDay      = $home_team_obj->match_day ?? '';
            $teams['home']->matchTime     = $home_team_obj->match_time ?? '';

            $teams['away']->captain       = $away_team_obj->captain ?? '';
            $teams['away']->captain_email = $away_team_obj->contactemail ?? '';
            $teams['away']->captain_tel   = $away_team_obj->contactno ?? '';
            $teams['away']->matchDay      = $away_team_obj->match_day ?? '';
            $teams['away']->matchTime     = $away_team_obj->match_time ?? '';
        }

        return array(
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
            'template'        => $template,
        );
    }

    /**
     * Prepare variables for result-notification template.
     */
    public function present_result_notification( Fixture $fixture, array $args ): array {
        $match = \Racketmanager\get_match( $fixture->get_id() );
        return array_merge( $args, array(
            'match'        => $match,
            'organisation' => $this->site_name,
            'site_url'     => $this->site_url,
        ) );
    }

    /**
     * Prepare variables for captain result approval notification.
     */
    public function present_captain_result_approval_notification( Fixture $fixture, array $args ): array {
        $match      = \Racketmanager\get_match( $fixture->get_id() );
        $action_url = $this->site_url;

        if ( $match->league->event->competition->is_championship ) {
            $action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ) . '/' . $match->league->current_season['name'] . '/' . $match->final_round . '/' . sanitize_title( $match->teams['home']->title ) . '-vs-' . sanitize_title( $match->teams['away']->title ) . '/';
            if ( ! empty( $match->leg ) ) {
                $action_url .= 'leg-' . $match->leg . '/';
            }
        } else {
            $action_url .= '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ) . '/' . $match->league->current_season['name'] . '/day' . $match->match_day . '/' . sanitize_title( $match->teams['home']->title ) . '-vs-' . sanitize_title( $match->teams['away']->title ) . '/';
        }
        $action_url .= 'result/';

        return array_merge( $args, array(
            'match'        => $match,
            'organisation' => $this->site_name,
            'action_url'   => $action_url,
        ) );
    }

    /**
     * Prepare variables for date-change-notification template.
     */
    public function present_date_change_notification( Fixture $fixture, array $args ): array {
        $match = \Racketmanager\get_match( $fixture->get_id() );
        return array_merge( $args, array(
            'match'        => $match,
            'organisation' => $this->site_name,
            'site_url'     => $this->site_url,
        ) );
    }

    /**
     * Prepare variables for team-withdrawn-notification template.
     */
    public function present_team_withdrawn_notification( Fixture $fixture, array $args ): array {
        $match = \Racketmanager\get_match( $fixture->get_id() );
        return array_merge( $args, array(
            'match'        => $match,
            'organisation' => $this->site_name,
            'site_url'     => $this->site_url,
        ) );
    }

    /**
     * Prepare variables for result-outstanding-notification template.
     */
    public function present_result_outstanding_notification( Fixture $fixture, array $args ): array {
        $match = \Racketmanager\get_match( $fixture->get_id() );
        
        return array_merge( $args, array(
            'match'        => $match,
            'organisation' => $this->site_name,
            'action_url'   => $args['action_url'] ?? $this->site_url,
        ) );
    }

    private function get_club_shortcode( int $team_id ): string {
        $team = $this->team_repository->find_by_id( $team_id );
        if ( $team && $team->club_id ) {
            $club = $this->club_repository->find_by_id( (int) $team->club_id );
            if ( $club && ! empty( $club->shortcode ) ) {
                return $club->shortcode;
            }
        }
        return __( 'Unknown', 'racketmanager' );
    }
}
