<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use function __;
use function is_numeric;
use function method_exists;

class Get_Team_Order_Players_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Club_Service $club_service,
        private readonly Registration_Service $registration_service,
        private readonly Settings_Service $settings_service
    ) {}

    public function handle( Get_Team_Order_Players_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'No event id supplied', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? null, $this->competition_service ) ) {
            return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        if ( empty( $query->club_id ) ) {
            return Shortcode_View_Model::for_error( __( 'Club id not supplied', 'racketmanager' ) );
        }

        try {
            if ( is_numeric( $query->club_id ) ) {
                $club = $this->club_service->get_club( (int) $query->club_id );
            } else {
                $club = $this->club_service->get_club_by_shortcode( (string) $query->club_id );
            }
        } catch ( Club_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Club not found', 'racketmanager' ) );
        }

        $club_id_val  = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? null );
        $team_args    = [
            'season' => $event->current_season['name'] ?? '',
            'club'   => $club_id_val,
        ];
        $club_players = $this->get_club_players( $event, $club );
        $can_update   = $this->club_service->can_user_update_as_captain( $club_id_val );

        return Shortcode_View_Model::for_template(
            'team-players-list',
            [
                'event'        => $event,
                'club'         => $club,
                'teams'        => $event->get_teams( $team_args ),
                'matches'      => [],
                'club_players' => $club_players,
                'can_update'   => $can_update,
            ],
            'event'
        );
    }

    private function get_club_players( object $event, object $club ): array {
        $max_age        = null;
        $min_age        = null;
        $min_age_female = null;
        $options        = $this->settings_service->get_category( 'rosters', [] ) ?: [];
        $age_limit      = $event->age_limit ?? null;
        $age_offset     = 0;
        if ( $age_limit ) {
            $age_offset = $event->age_offset ?? 0;
        }
        if ( ! empty( $options['ageLimitCheck'] ) && $age_limit && 'open' !== $age_limit ) {
            $numeric_age_limit  = (int) $age_limit;
            $numeric_age_offset = (int) $age_offset;
            if ( $numeric_age_limit < 30 ) {
                $max_age = $numeric_age_limit;
            } else {
                $min_age        = $numeric_age_limit;
                $min_age_female = $min_age - $numeric_age_offset;
            }
        }

        $club_id = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? null );
        $club_players = [];

        switch ( substr( $event->type, 0, 1 ) ) {
            case 'B':
            case 'M':
                $club_players['m'] = $this->registration_service->get_registered_players_list( 'active', null, $club_id, 'm', true, $max_age, $min_age );
                break;
            case 'G':
            case 'W':
                $club_players['f'] = $this->registration_service->get_registered_players_list( 'active', null, $club_id, 'f', true, $max_age, $min_age );
                break;
            case 'X':
            case 'L':
                $club_players['m'] = $this->registration_service->get_registered_players_list( 'active', null, $club_id, 'm', true, $max_age, $min_age );
                $club_players['f'] = $this->registration_service->get_registered_players_list( 'active', null, $club_id, 'f', true, $max_age, $min_age_female );
                break;
            case 'S':
            case 'O':
                $club_players['x'] = $this->registration_service->get_registered_players_list( 'active', null, $club_id, null, true, $max_age, $min_age );
                break;
            default:
                $club_players['m'] = [];
                $club_players['f'] = [];
        }

        return $club_players;
    }
}
