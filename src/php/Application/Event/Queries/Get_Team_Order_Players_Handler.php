<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Event\DTOs\Team_Order_Players_Result;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function __;
use function is_numeric;
use function method_exists;
use function substr;

/**
 * Handler for getting team order players.
 */
readonly class Get_Team_Order_Players_Handler {
    /**
     * Initialises the instance with the required service dependencies.
     *
     * @param Competition_Service            $competition_service Competition service.
     * @param Club_Service_Interface         $club_service Club service.
     * @param Registration_Service_Interface $registration_service Registration service.
     * @param Settings_Service_Interface     $settings_service Settings service.
     * @param Event_Query_Service            $event_query_service Event query service.
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Club_Service_Interface $club_service,
        private Registration_Service_Interface $registration_service,
        private Settings_Service_Interface $settings_service,
        private Event_Query_Service $event_query_service
    ) {}

    /**
     * Handle get team order players query.
     *
     * @param Get_Team_Order_Players_Query $query Get team order players query.
     *
     * @return Team_Order_Players_Result Team order players result.
     */
    public function handle( Get_Team_Order_Players_Query $query ): Team_Order_Players_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Team_Order_Players_Result::error( __( 'No event id supplied', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Team_Order_Players_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season ?? null );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? null, $this->competition_service ) ) {
            return Team_Order_Players_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        if ( empty( $query->club_id ) ) {
            return Team_Order_Players_Result::error( __( 'Club id not supplied', 'racketmanager' ) );
        }

        try {
            if ( is_numeric( $query->club_id ) ) {
                $club = $this->club_service->get_club( (int) $query->club_id );
            } else {
                $club = $this->club_service->get_club_by_shortcode( (string) $query->club_id );
            }
        } catch ( Club_Not_Found_Exception ) {
            return Team_Order_Players_Result::error( __( 'Club not found', 'racketmanager' ) );
        }

        $club_id_val  = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? null );
        $team_args    = [
            'season' => $event->current_season['name'] ?? '',
            'club'   => $club_id_val,
        ];
        $club_players = $this->get_club_players( $event, $club );
        $can_update   = $this->club_service->can_user_update_as_captain( $club_id_val );

        return new Team_Order_Players_Result(
            event: $event,
            club: $club,
            teams: $this->event_query_service->get_teams( $event, $team_args ),
            matches: [],
            club_players: $club_players,
            can_update: $can_update,
            template: 'team-players-list'
        );
    }

    /**
     * Get club players.
     *
     * @param object $event Event.
     * @param object $club Club.
     *
     * @return array
     */
    private function get_club_players( object $event, object $club ): array {
        $max_age        = null;
        $min_age        = null;
        $min_age_female = null;
        $options        = $this->settings_service->get_category( 'rosters' ) ?: [];
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

        $club_id      = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? null );
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
