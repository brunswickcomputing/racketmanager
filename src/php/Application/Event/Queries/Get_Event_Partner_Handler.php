<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Partner_Result;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function __;
use function str_starts_with;

/**
 * Handles retrieval and preparation of partner details for an event entry request.
 *
 * Resolves the requested event by identifier, applies the requested season context,
 * verifies that the event is allowed for the competition, determines the expected
 * partner gender from the event type and player gender. Optionally loads an
 * existing partner's display details.
 *
 * Returns an error result when the event identifier is missing, the event cannot be
 * found, or the event is not valid for the resolved competition context. Missing
 * partner records are treated as absent partner details rather than request errors.
 */
readonly class Get_Event_Partner_Handler {
    /**
     * Initialises the instance with required service dependencies.
     *
     * @param Competition_Service      $competition_service Competition service dependency.
     * @param Player_Service_Interface $player_service Player service dependency.
     * @param Event_Query_Service      $event_query_service Event query service dependency.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Player_Service_Interface $player_service,
        private Event_Query_Service $event_query_service
    ) {}

    /**
     * Handles an event partner query and returns partner modal data or an error result.
     *
     * @param Get_Event_Partner_Query $query Query containing event, player, partner, season, and template details.
     *
     * @return Event_Partner_Result Result containing resolved event partner data, or an error result when the event is unavailable.
     */
    public function handle( Get_Event_Partner_Query $query ): Event_Partner_Result {
        $event = $this->resolve_event( $query );
        if ( $event instanceof Event_Partner_Result ) {
            return $event;
        }

        [ $partner_name, $partner_btm ] = $this->get_partner_details( $query->partner_id );

        return new Event_Partner_Result(
            event: $event,
            player_id: $query->player_id,
            partner_name: $partner_name,
            partner_btm: $partner_btm,
            partner_id: $query->partner_id,
            partner_gender: $this->resolve_partner_gender( (string) ( $event->type ?? '' ), $query->gender ),
            date_end: $query->date_end,
            season: $event->current_season['name'] ?? $query->season,
            modal: $query->modal,
            tournament_id: $query->tournament_id,
            template: $this->resolve_template( $query->template ),
            template_type: 'event'
        );
    }

    /**
     * Resolves the event instance from query identifiers, sets season context, and checks allowlist.
     *
     * @param Get_Event_Partner_Query $query Query containing event identifier and season details.
     *
     * @return Event|Event_Partner_Result Resolved event instance or error result when unavailable.
     */
    private function resolve_event( Get_Event_Partner_Query $query ): Event|Event_Partner_Result {
        $identifier = $query->event_id ?? $query->event_name;
        $event      = null;

        if ( null !== $identifier && '' !== $identifier ) {
            try {
                $event = $this->competition_service->get_event_by_id( $identifier );
            } catch ( Competition_Not_Found_Exception ) { //phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // Event not found; proceed without event details.
            }
        }

        if ( null === $event ) {
            return Event_Partner_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $season = $query->season;
        $this->event_query_service->set_season( $event, $season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $season, $this->competition_service ) ) {
            return Event_Partner_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        return $event;
    }

    /**
     * Resolves expected partner gender from event type and player gender.
     *
     * @param string      $event_type Event type code.
     * @param string|null $player_gender Player gender ('M' or other/null).
     *
     * @return string Expected partner gender ('M' or 'F').
     */
    private function resolve_partner_gender( string $event_type, ?string $player_gender ): string {
        $is_male_event   = str_starts_with( $event_type, 'M' ) || str_starts_with( $event_type, 'B' );
        $is_female_event = str_starts_with( $event_type, 'W' ) || str_starts_with( $event_type, 'G' );

        if ( 'M' === $player_gender ) {
            return $is_male_event ? 'M' : 'F';
        }

        return $is_female_event ? 'F' : 'M';
    }

    /**
     * Resolves partner display name and BTM details by partner identifier.
     *
     * @param int|null $partner_id Partner identifier.
     *
     * @return array{?string, ?string} Tuple of partner display name and BTM.
     */
    private function get_partner_details( ?int $partner_id ): array {
        if ( empty( $partner_id ) ) {
            return [ null, null ];
        }

        try {
            $partner = $this->player_service->get_player( $partner_id );

            return [
                $partner->display_name ?? ( $partner->fullname ?? null ),
                $partner->btm ?? null,
            ];
        } catch ( Player_Not_Found_Exception ) {
            return [ null, null ];
        }
    }

    /**
     * Resolves the modal template name based on optional query template string.
     *
     * @param string|null $template Optional template suffix.
     *
     * @return string Resolved template filename.
     */
    private function resolve_template( ?string $template ): string {
        return ! empty( $template ) ? 'partner-modal-' . $template : 'partner-modal';
    }
}
