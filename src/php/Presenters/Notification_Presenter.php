<?php

namespace Racketmanager\Presenters;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use stdClass;
use function Racketmanager\seo_url;

class Notification_Presenter {

    public function __construct(
        private Team_Repository_Interface $team_repository,
        private Club_Repository_Interface $club_repository,
        private Tournament_Repository_Interface $tournament_repository,
        private Fixture_Link_Service $fixture_link_service,
        private Team_Service $team_service,
        private Competition_Service $competition_service,
        private Fixture_Permission_Service $permission_service,
        private string $site_url,
        private string $site_name
    ) {
    }

    /**
     * Prepare variables for the match-notification template.
     *
     * Mirrors logic in Shortcodes_Email::show_match_notification
     */
    public function present_match_notification( Fixture $fixture, array $args ): array {
        $competition_type = $args['competition_type'] ?? '';
        $is_tournament    = 'tournament' === $competition_type;

        $league      = $this->competition_service->get_league_repository()->find_by_id( (int) $fixture->get_league_id() );
        $event       = $this->competition_service->get_event_by_id( $league->get_event_id() );
        $competition = $this->competition_service->get_by_id( $event->get_competition_id() );

        $home_team = $this->get_team_details_for_fixture( $fixture->get_home_team(), $league, $fixture->get_season() );
        $away_team = $this->get_team_details_for_fixture( $fixture->get_away_team(), $league, $fixture->get_season() );

        $prev_home_fixture_title = null;
        $prev_away_fixture_title = null;

        if ( $is_tournament ) {
            if ( ! is_numeric( $fixture->get_home_team() ) ) {
                $prev_home_fixture_title = $this->resolve_placeholder_title( $fixture->get_home_team(), $fixture->get_season(), $league, $fixture->get_final() );
            }

            if ( ! is_numeric( $fixture->get_away_team() ) ) {
                $prev_away_fixture_title = $this->resolve_placeholder_title( $fixture->get_away_team(), $fixture->get_season(), $league, $fixture->get_final() );
            }
        }

        $is_update_allowed = $this->permission_service->is_update_allowed( $fixture );
        $link              = $this->fixture_link_service->get_fixture_link( $fixture, $league, $home_team, $away_team );
        $fixture_title     = $this->generate_fixture_title( $home_team, $away_team, $prev_home_fixture_title, $prev_away_fixture_title );

        $fixture_details = new Fixture_Details_DTO( $fixture, $league, $event, $competition, $home_team, $away_team, $prev_home_fixture_title, $prev_away_fixture_title, $is_update_allowed, $link, null, [], $fixture_title );

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
                $draw_link       = '<a href="' . $this->site_url . $tournament->link . 'draw/' . seo_url( $fixture_details->event->get_name() ) . '/">' . $fixture_details->event->get_name() . '</a>';
                $match_link      = $this->site_url . $tournament->link . '/match/' . seo_url( $fixture_details->league->get_name() ) . '/' . seo_url( $fixture_details->home_team->team->get_name() ) . '-vs-' . seo_url( $fixture_details->away_team->team->get_name() ) . '/' . $fixture->get_id() . '/';
            }

            if ( str_contains( $fixture_details->event->get_type() ?? '', 'D' ) ) {
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
            $cup_link   = '<a href="' . $this->site_url . '/cups/' . seo_url( $fixture_details->league->get_name() ) . '/' . $fixture->get_season() . '/">' . $fixture_details->league->get_name() . '</a>';
            $match_link = $this->fixture_link_service->get_fixture_link( $fixture, $fixture_details->league, $fixture_details->home_team, $fixture_details->away_team );

            $template             = 'match-notification-cup';
            $teams['home']->title = __( 'Home Team', 'racketmanager' );
            $teams['away']->title = __( 'Away Team', 'racketmanager' );
            $home_dtls['title']   = 'Home Team';
            $away_dtls['title']   = 'Away Team';
        }

        if ( ! $tournament ) {
            $tournament       = new stdClass();
            $tournament->name = $competition ?: ( $fixture_details->league->get_name() ?? '' );
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

        $teams[$team_1_key]->name = $fixture_details->home_team->team->get_name();
        $teams[$team_2_key]->name = $fixture_details->away_team->team->get_name();

        // Club info
        $teams['home']->club = $this->get_club_shortcode( (int) $fixture->get_home_team() );
        $teams['away']->club = $this->get_club_shortcode( (int) $fixture->get_away_team() );

        // Player/Captain details
        if ( $home_team_obj && 'P' === ($home_team_obj->team_type ?? '') ) {
            $teams['home']->player = $fixture_details->home_team->players ?? array();
            $teams['away']->player = $fixture_details->away_team->players ?? array();
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
            'fixture_details' => $fixture_details,
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
     * Prepare variables for the result-notification template.
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
     * Prepare variables for the date-change-notification template.
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
     * Prepare variables for the team-withdrawn-notification template.
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
     * Prepare variables for the result-outstanding-notification template.
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

    /**
     * Get team details for a fixture team.
     */
    private function get_team_details_for_fixture( string|int|null $team_id, League $league, string $season ): ?Team_Details_DTO {
        if ( empty( $team_id ) ) {
            return null;
        }

        $dto = is_numeric( $team_id )
            ? $this->team_service->get_team_details( (int) $team_id )
            : $this->team_service->derive_team_details( (string) $team_id );

        if ( $dto && is_numeric( $team_id ) && method_exists( $league, 'get_status' ) ) {
            $status = $league->get_status( (int) $team_id, $season );
            $dto->is_withdrawn = ( 'W' === $status );
        }

        return $dto;
    }

    /**
     * Resolve placeholder title for fixtures
     */
    private function resolve_placeholder_title( string $team_ref, string $season, League $league, ?string $fixture_final = null ): ?string {
        $team  = explode( '_', $team_ref );
        $final = $team[1] ?? null;
        if ( empty( $final ) ) {
            return null;
        }

        if ( 'final' !== $fixture_final ) {
            return $this->build_standard_placeholder_title( $team );
        }

        return $this->resolve_final_placeholder_title( $team, $season, $league );
    }

    /**
     * Build the standard placeholder title.
     */
    private function build_standard_placeholder_title( array $team ): string {
        $group_name = '';
        if ( ! empty( $team[0] ) ) {
            $group_name = str_replace( '-', ' ', $team[0] );
            $group_name = ( 'group' === $group_name ) ? '' : $group_name . ' ';
        }
        $position_suffix = ( (int) $team[1] === 1 ) ? 'st' : ( ( (int) $team[1] === 2 ) ? 'nd' : ( ( (int) $team[1] === 3 ) ? 'rd' : 'th' ) );

        return sprintf( __( '%s%d%s in Group', 'racketmanager' ), ucfirst( $group_name ), $team[1], $position_suffix );
    }

    /**
     * Resolve the final placeholder title.
     */
    private function resolve_final_placeholder_title( array $team, string $season, League $league ): ?string {
        $prev_fixtures = $this->competition_service->get_league_repository()->get_fixtures( (int) $league->get_id(), $season );
        if ( empty( $prev_fixtures ) ) {
            return null;
        }

        return $this->resolve_placeholder_from_previous_fixture( $team, $season, $league, $prev_fixtures );
    }

    /**
     * Resolve placeholder from previous fixture.
     */
    private function resolve_placeholder_from_previous_fixture( array $team, string $season, League $league, array $prev_fixtures ): ?string {
        foreach ( $prev_fixtures as $fixture ) {
            if ( $fixture->get_final() === $team[1] ) {
                $home_team = $this->get_team_details_for_fixture( $fixture->get_home_team(), $league, $season );
                $away_team = $this->get_team_details_for_fixture( $fixture->get_away_team(), $league, $season );

                return sprintf( __( 'Winner of %s v %s', 'racketmanager' ), $home_team->team->get_name(), $away_team->team->get_name() );
            }
        }

        return null;
    }

    /**
     * Generate fixture title.
     */
    private function generate_fixture_title( ?Team_Details_DTO $home_team, ?Team_Details_DTO $away_team, ?string $prev_home_title, ?string $prev_away_title ): string {
        $home_title = $home_team ? $home_team->team->get_name() : ( $prev_home_title ?? '' );
        $away_title = $away_team ? $away_team->team->get_name() : ( $prev_away_title ?? '' );

        return $home_title . ' v ' . $away_title;
    }
}
