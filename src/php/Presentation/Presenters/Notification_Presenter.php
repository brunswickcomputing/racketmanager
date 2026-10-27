<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Formatters\Email_Date_Formatter;
use Racketmanager\Presentation\View_Models\Send_Fixtures_Match_Row_View_Model;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Export\DTO\Export_Criteria;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Util\Util;
use stdClass;
use function __;
use function sanitize_title;

readonly class Notification_Presenter {

    public function __construct(
        private Team_Repository_Interface $team_repository,
        private Club_Repository_Interface $club_repository,
        private Tournament_Repository_Interface $tournament_repository,
        private Fixture_Repository_Interface $fixture_repository,
        private Fixture_Link_Service $fixture_link_service,
        private Team_Service $team_service,
        private Competition_Service $competition_service,
        private Fixture_Permission_Service $permission_service,
        private string $site_url,
        private string $site_name,
        private League_Query_Service $league_query_service,
        private ?Rubber_Repository_Interface $rubber_repository = null,
        private ?Option_Gateway_Interface $option_gateway = null,
    ) {
    }

    /**
     * Prepare variables for the match-notification template.
     *
     * Mirrors logic in Email_Shortcode_Adapter::show_match_notification
     */
    public function present_match_notification( Fixture $fixture, array $args ): array {
        $competition_type = $args['competition_type'] ?? '';
        $is_tournament    = 'tournament' === $competition_type;

        $league          = $this->competition_service->get_league_repository()->find_by_id( (int) $fixture->get_league_id() );
        $event           = $this->competition_service->get_event_by_id( $league->get_event_id() );
        $competition_obj = $this->competition_service->get_by_id( $event->get_competition_id() );

        $home_team_dtls = $this->get_team_details_for_fixture( $fixture->get_home_team(), $league, $fixture->get_season() );
        $away_team_dtls = $this->get_team_details_for_fixture( $fixture->get_away_team(), $league, $fixture->get_season() );

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
        $link              = $this->fixture_link_service->get_fixture_link( $fixture, $league, $home_team_dtls, $away_team_dtls );
        $fixture_title     = $this->generate_fixture_title( $home_team_dtls, $away_team_dtls, $prev_home_fixture_title, $prev_away_fixture_title );

        $fixture_details = new Fixture_Details_DTO( $fixture, $league, $event, $competition_obj, $home_team_dtls, $away_team_dtls, $prev_home_fixture_title, $prev_away_fixture_title, $is_update_allowed, $link, null, [], $fixture_title );

        $competition_data = $this->prepare_competition_data( $fixture, $fixture_details, $args );
        $team_data        = $this->prepare_team_data( $fixture, $fixture_details );
        $this->load_rubbers( $fixture );
        $match_adapter    = new Fixture_Presentation_Adapter( $fixture, $league, $event, $competition_obj, $home_team_dtls, $away_team_dtls, $link, $is_update_allowed, $prev_home_fixture_title, $prev_away_fixture_title, $this->option_gateway );

        $formatter     = $this->date_formatter();
        $comp_name     = ( $args['competition'] ?? '' ) ?: ( $match_adapter->league->title ?? '' );
        $match_date    = $formatter->date( $fixture->get_date() );
        $match_time    = $formatter->time( $fixture->get_date() );

        return array_merge( array(
                'fixture_details'  => $fixture_details,
                'match'            => $match_adapter,
                'organisation'     => $this->site_name,
                'site_url'         => $this->site_url,
                'email_from'       => $args['email_from'] ?? ( $args['emailfrom'] ?? '' ),
                'round'            => $args['round'] ?? '',
                'competition'      => $comp_name,
                'competition_name' => $comp_name,
                'match_date'       => $match_date,
                'match_time'       => $match_time,
            ), $competition_data, $team_data );
    }

    /**
     * Get team details for a fixture team.
     */
    private function get_team_details_for_fixture( string|int|null $team_id, League $league, string $season ): ?Team_Details_DTO {
        if ( empty( $team_id ) ) {
            return null;
        }

        $dto = is_numeric( $team_id ) ? $this->team_service->get_team_details( (int) $team_id ) : $this->team_service->derive_team_details( (string) $team_id );

        if ( $dto && is_numeric( $team_id ) && method_exists( $league, 'get_status' ) ) {
            $status            = $this->league_query_service->get_status( $league, (int) $team_id, $season );
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
        $position        = (int) $team[1];
        $position_suffix = match ( $position ) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };

        return sprintf( __( '%s%d%s in Group', 'racketmanager' ), ucfirst( $group_name ), $team[1], $position_suffix );
    }

    /**
     * Resolve the final placeholder title.
     */
    private function resolve_final_placeholder_title( array $team, string $season, League $league ): ?string {
        $criteria      = new Export_Criteria( array(
                'league_id' => (int) $league->get_id(),
                'season'    => $season,
            ) );
        $prev_fixtures = $this->fixture_repository->find_by_criteria( $criteria );
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

    /**
     * Prepare competition-specific data.
     */
    private function prepare_competition_data( Fixture $fixture, Fixture_Details_DTO $fixture_details, array $args ): array {
        $competition_type  = $args['competition_type'] ?? '';
        $tournament_id     = $args['tournament'] ?? false;
        $tournament_search = $args['tournament_search'] ?? 'id';

        $data = array(
            'tournament'      => null,
            'tournament_link' => '',
            'draw_link'       => '',
            'action_url'      => '',
            'cup_link'        => '',
            'rules_link'      => $this->site_url . '/rules/' . $competition_type . '-rules/',
            'template'        => $args['template'] ?? '',
            'home_dtls'       => array(),
            'away_dtls'       => array(),
        );

        if ( 'tournament' === $competition_type ) {
            $tournament = $this->tournament_repository->find_by_id( $tournament_id, $tournament_search );
            if ( $tournament ) {
                $data['tournament']      = $tournament;
                $tournament_path         = ( new Tournament_Presenter() )->get_link( (object) $tournament );
                $data['tournament_link'] = '<a href="' . $this->site_url . $tournament_path . '">' . $tournament->name . '</a>';
                $data['draw_link']       = '<a href="' . $this->site_url . $tournament_path . 'draw/' . Seo_Slug::encode( $fixture_details->event->get_name() ) . '/">' . $fixture_details->event->get_name() . '</a>';
                $data['action_url']      = $this->site_url . $tournament_path . 'match/' . Seo_Slug::encode( $fixture_details->league->get_name() ) . '/' . Seo_Slug::encode( $fixture_details->home_team->team->get_name() ) . '-vs-' . Seo_Slug::encode( $fixture_details->away_team->team->get_name() ) . '/' . $fixture->get_id() . '/';
            }

            $suffix                     = str_contains( $fixture_details->event->get_type() ?? '', 'D' ) ? ' Players' : ' Player';
            $data['home_dtls']['title'] = 'Home' . $suffix;
            $data['away_dtls']['title'] = 'Away' . $suffix;
        } elseif ( 'cup' === $competition_type ) {
            $data['cup_link']           = '<a href="' . $this->site_url . '/cups/' . Seo_Slug::encode( $fixture_details->league->get_name() ) . '/' . $fixture->get_season() . '/">' . $fixture_details->league->get_name() . '</a>';
            $data['action_url']         = $this->fixture_link_service->get_fixture_link( $fixture, $fixture_details->league, $fixture_details->home_team, $fixture_details->away_team );
            $data['template']           = 'match-notification-cup';
            $data['home_dtls']['title'] = 'Home Team';
            $data['away_dtls']['title'] = 'Away Team';
        }

        if ( ! $data['tournament'] ) {
            $data['tournament']       = new stdClass();
            $data['tournament']->name = ( $args['competition'] ?? '' ) ? : ( $fixture_details->league->get_name() ?? '' );
            $data['tournament']->link = '';
        }

        return $data;
    }

    /**
     * Prepare team-specific data.
     */
    private function prepare_team_data( Fixture $fixture, Fixture_Details_DTO $fixture_details ): array {
        $home_team_obj = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team_obj = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );

        $teams = array(
            'home' => new stdClass(),
            'away' => new stdClass(),
        );

        $host       = $fixture->get_host() ? : 'home';
        $team_1_key = 'home' === $host ? 'home' : 'away';
        $team_2_key = 'home' === $host ? 'away' : 'home';

        $teams[ $team_1_key ]->name  = $fixture_details->home_team?->team?->get_name() ?? '';
        $teams[ $team_1_key ]->title = $teams[ $team_1_key ]->name;
        $teams[ $team_2_key ]->name  = $fixture_details->away_team?->team?->get_name() ?? '';
        $teams[ $team_2_key ]->title = $teams[ $team_2_key ]->name;

        $teams['home']->club = $this->get_club_shortcode( (int) $fixture->get_home_team() );
        $teams['away']->club = $this->get_club_shortcode( (int) $fixture->get_away_team() );

        if ( $home_team_obj && 'P' === ( $home_team_obj->team_type ?? '' ) ) {
            $teams['home']->player = $fixture_details->home_team->players ?? array();
            $teams['away']->player = $fixture_details->away_team->players ?? array();
        } else {
            $this->populate_captain_info( $teams['home'], $home_team_obj );
            $this->populate_captain_info( $teams['away'], $away_team_obj );
        }

        return array( 'teams' => $teams );
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
     * Populate captain info for a team.
     */
    private function populate_captain_info( stdClass $team, $team_obj ): void {
        $team->captain       = $team_obj->captain ?? '';
        $team->captain_email = $team_obj->contactemail ?? '';
        $team->captain_tel   = $team_obj->contactno ?? '';
        $team->matchDay      = $team_obj->match_day ?? '';
        $team->matchTime     = $team_obj->match_time ?? '';
    }

    /**
     * Prepare variables for the result-notification template.
     */
    public function present_result_notification( Fixture $fixture, array $args ): array {
        $match_adapter = $this->present_fixture( $fixture );
        $action_url    = $this->site_url;

        if ( $match_adapter->league->event->competition->is_championship ) {
            $action_url .= $this->build_championship_action_url( $match_adapter );
        } else {
            $action_url .= $this->build_standard_action_url( $match_adapter );
        }
        $action_url .= 'result/';

        return array_merge( $args, array(
            'match'        => $match_adapter,
            'organisation' => $this->site_name,
            'site_url'     => $this->site_url,
            'action_url'   => $action_url,
        ) );
    }

    /**
     * Prepare variables for captain result approval notification.
     */
    public function present_captain_result_approval_notification( Fixture $fixture, array $args ): array {
        $match_adapter = $this->present_fixture( $fixture );
        $action_url    = $this->site_url;

        if ( $match_adapter->league->event->competition->is_championship ) {
            $action_url .= $this->build_championship_action_url( $match_adapter );
        } else {
            $action_url .= $this->build_standard_action_url( $match_adapter );
        }
        $action_url .= 'result/';

        return array_merge( $args, array(
            'match'        => $match_adapter,
            'organisation' => $this->site_name,
            'action_url'   => $action_url,
        ) );
    }

    /**
     * Build championship action URL.
     */
    private function build_championship_action_url( Fixture_Presentation_Adapter $match ): string {
        $url = '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ) . '/' . $match->league->current_season['name'] . '/' . $match->final_round . '/' . sanitize_title( $match->teams['home']->title ) . '-vs-' . sanitize_title( $match->teams['away']->title ) . '/';
        if ( ! empty( $match->leg ) ) {
            $url .= 'leg-' . $match->leg . '/';
        }

        return $url;
    }

    /**
     * Build standard action URL.
     */
    private function build_standard_action_url( Fixture_Presentation_Adapter $match ): string {
        return '/' . __( 'match', 'racketmanager' ) . '/' . sanitize_title( $match->league->title ) . '/' . $match->league->current_season['name'] . '/day' . $match->match_day . '/' . sanitize_title( $match->teams['home']->title ) . '-vs-' . sanitize_title( $match->teams['away']->title ) . '/';
    }

    /**
     * Prepare variables for the date-change-notification template.
     */
    public function present_date_change_notification( Fixture $fixture, array $args ): array {
        $match_adapter    = $this->present_fixture( $fixture );
        $competition_type = $args['competition_type'] ?? '';
        $tournament_id    = $args['tournament'] ?? false;
        $tournament_link  = '';
        $competition_link = '';
        $draw_link        = '';
        $match_link       = '';
        $rules_link       = $this->site_url . '/rules/' . $competition_type . '-rules/';

        if ( 'tournament' === $competition_type && $tournament_id ) {
            $tournament = $this->tournament_repository->find_by_id( $tournament_id );
            if ( $tournament ) {
                $tournament_path = ( new Tournament_Presenter() )->get_link( (object) $tournament );
                $tournament_link = '<a href="' . $this->site_url . $tournament_path . '">' . $tournament->name . '</a>';
                $draw_link       = '<a href="' . $this->site_url . $tournament_path . 'draw/' . Seo_Slug::encode( $match_adapter->league->event->name ?? '' ) . '/">' . ( $match_adapter->league->event->name ?? '' ) . '</a>';
                $match_link      = $this->site_url . $tournament_path . 'match/' . Seo_Slug::encode( $match_adapter->league->title ?? '' ) . '/' . Seo_Slug::encode( $match_adapter->teams['home']->title ?? '' ) . '-vs-' . Seo_Slug::encode( $match_adapter->teams['away']->title ?? '' ) . '/' . $fixture->get_id() . '/';
            }
        } elseif ( 'cup' === $competition_type ) {
            $competition_link = '<a href="' . $this->site_url . '/cups/' . Seo_Slug::encode( $match_adapter->league->title ?? '' ) . '/' . $match_adapter->season . '/">' . ( $match_adapter->league->title ?? '' ) . '</a>';
            $match_link       = $this->site_url . $match_adapter->link;
            if ( ! empty( $match_adapter->leg ) ) {
                $match_link .= 'leg-' . $match_adapter->leg . '/';
            }
        } elseif ( 'league' === $competition_type ) {
            $competition_link = '<a href="' . $this->site_url . '/leagues/' . Seo_Slug::encode( $match_adapter->league->event->name ?? '' ) . '/' . $match_adapter->season . '/">' . ( $match_adapter->league->title ?? '' ) . '</a>';
            $match_link       = $this->site_url . $match_adapter->link;
        }

        // Producers pass raw MySQL datetimes in new_date/original_date; always format them here.
        $formatter     = $this->date_formatter();
        $raw_new_date  = ! empty( $args['new_date'] ) ? (string) $args['new_date'] : $fixture->get_date();
        $raw_orig_date = ! empty( $args['original_date'] ) ? (string) $args['original_date'] : $fixture->get_date_original();
        $match_date    = $formatter->date_change( $raw_new_date );
        $original_date = $formatter->date_change( $raw_orig_date );
        $comp_name     = ( $args['competition'] ?? '' ) ?: ( $match_adapter->league->title ?? '' );

        return array_merge( $args, array(
            'match'            => $match_adapter,
            'competition_name' => $comp_name,
            'match_date'       => $match_date,
            'new_date'         => $match_date,
            'original_date'    => $original_date,
            'organisation'     => $this->site_name,
            'site_url'         => $this->site_url,
            'tournament_link'  => $tournament_link,
            'competition_link' => $competition_link,
            'draw_link'        => $draw_link,
            'action_url'       => $match_link,
            'rules_link'       => $rules_link,
            'email_from'       => $args['emailfrom'] ?? ( $args['email_from'] ?? '' ),
            'from_email'       => $args['emailfrom'] ?? ( $args['email_from'] ?? '' ),
        ) );
    }

    /**
     * Prepare a single match row for the send-fixtures email.
     *
     * Accepts the domain Fixture returned by League::get_matches() (or a legacy match object/array) and
     * maps it to an immutable row view model, so no presentation state is written onto the aggregate.
     * Team ids are compared as integers (Fixture stores them as strings, League_Team ids are ints) and
     * team titles fall back to the team repository when the fixture has no team details loaded.
     */
    public function present_send_fixtures_match( object|array $match, int|string $current_team_id ): Send_Fixtures_Match_Row_View_Model {
        $formatter  = $this->date_formatter();
        $match_day  = $this->read_match_field( $match, 'match_day' );
        $date_val   = $this->read_match_field( $match, 'date' );
        $start_time = $this->read_match_field( $match, 'start_time' );
        $home_team  = $this->read_match_field( $match, 'home_team' );
        $away_team  = $this->read_match_field( $match, 'away_team' );
        $id         = $this->read_match_field( $match, 'id' );
        $teams      = $this->read_match_field( $match, 'teams' );

        $home_team_title = $this->resolve_send_fixtures_team_title( $teams, 'home', $home_team );
        $away_team_title = $this->resolve_send_fixtures_team_title( $teams, 'away', $away_team );
        $current_team_id = (int) $current_team_id;

        return new Send_Fixtures_Match_Row_View_Model(
            round_display: null !== $match_day ? (string) $match_day : '',
            date_display: $formatter->date( null !== $date_val ? (string) $date_val : null, Email_Date_Formatter::FIXTURE_LIST_DATE_FORMAT ),
            day_display: $formatter->date( null !== $date_val ? (string) $date_val : null, Email_Date_Formatter::FIXTURE_LIST_DAY_FORMAT ),
            time_display: $formatter->start_time( null !== $start_time ? (string) $start_time : null ),
            home_team_title: $home_team_title,
            away_team_title: $away_team_title,
            is_home: is_numeric( $home_team ) && (int) $home_team === $current_team_id,
            is_away: is_numeric( $away_team ) && (int) $away_team === $current_team_id,
            id: is_numeric( $id ) ? (int) $id : null,
            match_day: is_numeric( $match_day ) ? (int) $match_day : null,
            date: null !== $date_val ? (string) $date_val : null,
            start_time: null !== $start_time ? (string) $start_time : null,
            home_team: is_int( $home_team ) || is_string( $home_team ) ? $home_team : null,
            away_team: is_int( $away_team ) || is_string( $away_team ) ? $away_team : null,
            teams: [
                'home' => (object) [ 'title' => $home_team_title, 'name' => $home_team_title ],
                'away' => (object) [ 'title' => $away_team_title, 'name' => $away_team_title ],
            ],
        );
    }

    private function read_match_field( object|array $match, string $field ): mixed {
        if ( is_array( $match ) ) {
            return $match[ $field ] ?? null;
        }

        return $match->$field ?? null;
    }

    private function resolve_send_fixtures_team_title( mixed $teams, string $side, mixed $team_id ): string {
        $team = null;
        if ( is_array( $teams ) ) {
            $team = $teams[ $side ] ?? null;
        } elseif ( is_object( $teams ) ) {
            $team = $teams->$side ?? null;
        }
        if ( is_array( $team ) ) {
            $team = (object) $team;
        }
        $title = is_object( $team ) ? (string) ( $team->title ?? ( $team->name ?? '' ) ) : '';
        if ( '' !== $title || ! is_numeric( $team_id ) ) {
            return $title;
        }
        if ( -1 === (int) $team_id ) {
            return __( 'Bye', 'racketmanager' );
        }
        $team_entity = $this->team_repository->find_by_id( (int) $team_id );

        return $team_entity ? (string) $team_entity->title : '';
    }

    private function date_formatter(): Email_Date_Formatter {
        return new Email_Date_Formatter( $this->option_gateway );
    }

    /**
     * Prepare variables for club player notification template.
     */
    public function present_club_player_notification( array $args ): array {
        $club       = (string) ( $args['club'] ?? '' );
        $player     = (string) ( $args['player'] ?? '' );
        $action_url = $this->site_url . '/clubs/' . Seo_Slug::encode( $club ) . '/' . Seo_Slug::encode( $player ) . '/';

        return array_merge( $args, array(
            'action'        => $args['action'] ?? false,
            'club'          => $club,
            'player'        => $player,
            'organisation'  => $this->site_name,
            'action_url'    => $action_url,
            'requestor'     => $args['requestor'] ?? false,
            'btm'           => $args['btm'] ?? false,
            'email_subject' => $this->site_name . ' - ' . __( 'Club Player Request', 'racketmanager' ) . ' - ' . $club,
        ) );
    }

    /**
     * Prepare variables for team withdrawn email template.
     */
    public function present_team_withdrawn_email( object $team, object $league, string $season, array $args ): array {
        return array_merge( $args, array(
            'team'          => $team,
            'league'        => $league,
            'season'        => $season,
            'organisation'  => $this->site_name,
            'email_subject' => $args['subject'] ?? false,
            'email_from'    => $args['from'] ?? false,
        ) );
    }

    /**
     * Prepare variables for withdrawn team match template.
     */
    public function present_withdrawn_team_match( array $args ): array {
        return array_merge( $args, array(
            'is_tournament' => $args['is_tournament'] ?? false,
            'event'         => $args['event'] ?? '',
            'round'         => $args['round'] ?? '',
            'organisation'  => $this->site_name,
            'email_from'    => $args['emailfrom'] ?? '',
            'email_subject' => $args['subject'] ?? null,
        ) );
    }

    /**
     * Prepare variables for event constitution template.
     */
    public function present_event_constitution( object $event, ?string $season = null ): array {
        $league_urls = [];
        if ( isset( $event->leagues ) && is_iterable( $event->leagues ) ) {
            $presenter   = new Competition_Shortcode_Presenter();
            $league_urls = $presenter->present_event_league_urls( $event, $event->leagues, $season, $this->site_url );
        }

        return array(
            'event'        => $event,
            'organisation' => $this->site_name,
            'site_url'     => $this->site_url,
            'league_urls'  => $league_urls,
        );
    }

    /**
     * Prepare variables for the team-withdrawn-notification template.
     */
    public function present_team_withdrawn_notification( Fixture $fixture, array $args ): array {
        $match_adapter = $this->present_fixture( $fixture );

        return array_merge( $args, array(
            'match'        => $match_adapter,
            'organisation' => $this->site_name,
            'site_url'     => $this->site_url,
        ) );
    }

    /**
     * Prepare variables for the result-outstanding-notification template.
     */
    public function present_result_outstanding_notification( Fixture $fixture, array $args ): array {
        $match_adapter = $this->present_fixture( $fixture );
        $action_url    = $args['action_url'] ?? $this->site_url;

        if ( $this->site_url === $action_url ) {
            if ( $match_adapter->league->event->competition->is_championship ) {
                $action_url .= $this->build_championship_action_url( $match_adapter );
            } else {
                $action_url .= $this->build_standard_action_url( $match_adapter );
            }
            $action_url .= 'result/';
        }

        return array_merge( $args, array(
            'match'        => $match_adapter,
            'organisation' => $this->site_name,
            'action_url'   => $action_url,
        ) );
    }

    private function load_rubbers( Fixture $fixture ): void {
        if ( $this->rubber_repository && null === $fixture->rubbers && $fixture->get_id() > 0 ) {
            $fixture->set_rubbers( $this->rubber_repository->find_by_fixture_id( $fixture->get_id() ) );
        }
    }

    private function present_fixture( Fixture $fixture ): Fixture_Presentation_Adapter {
        $this->load_rubbers( $fixture );
        return Fixture_Presentation_Adapter::from_fixture( $fixture, $this->competition_service, $this->team_service, $this->fixture_link_service, $this->permission_service );
    }
}
