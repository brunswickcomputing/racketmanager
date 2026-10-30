<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Partner_Result;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use function __;
use function str_starts_with;

class Get_Event_Partner_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Player_Service $player_service,
        private readonly Event_Query_Service $event_query_service
    ) {}

    public function handle( Get_Event_Partner_Query $query ): Event_Partner_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Partner_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Partner_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Event_Partner_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $event_type = (string) ( $event->type ?? '' );
        if ( 'M' === $query->gender ) {
            if ( str_starts_with( $event_type, 'M' ) || str_starts_with( $event_type, 'B' ) ) {
                $partner_gender = 'M';
            } else {
                $partner_gender = 'F';
            }
        } elseif ( str_starts_with( $event_type, 'W' ) || str_starts_with( $event_type, 'G' ) ) {
            $partner_gender = 'F';
        } else {
            $partner_gender = 'M';
        }

        $partner_name = null;
        $partner_btm  = null;
        if ( ! empty( $query->partner_id ) ) {
            try {
                $partner      = $this->player_service->get_player( $query->partner_id );
                $partner_name = $partner->display_name ?? ( $partner->fullname ?? null );
                $partner_btm  = $partner->btm ?? null;
            } catch ( Player_Not_Found_Exception ) {
                $partner_name = null;
                $partner_btm  = null;
            }
        }

        $filename = ! empty( $query->template ) ? 'partner-modal-' . $query->template : 'partner-modal';

        return new Event_Partner_Result(
            event: $event,
            player_id: $query->player_id,
            partner_name: $partner_name,
            partner_btm: $partner_btm,
            partner_id: $query->partner_id,
            partner_gender: $partner_gender,
            date_end: $query->date_end,
            season: $event->current_season['name'] ?? $query->season,
            modal: $query->modal,
            tournament_id: $query->tournament_id,
            template: $filename,
            template_type: 'event'
        );
    }
}
