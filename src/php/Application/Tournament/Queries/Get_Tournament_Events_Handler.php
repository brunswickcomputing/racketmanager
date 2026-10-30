<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Tournament_Events_Result;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Events_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Events_Query $query ): Tournament_Events_Result {
        if ( ! $query->tournament_id ) {
            return Tournament_Events_Result::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Tournament_Events_Result::error( $e->getMessage() );
        }

        if ( $query->event_id ) {
            try {
                $event = $this->tournament_service->get_event_details_for_tournament( $tournament, $query->event_id );
            } catch ( Tournament_Not_Found_Exception|Event_Not_Found_Exception $e ) {
                return Tournament_Events_Result::error( $e->getMessage() );
            }

            return new Tournament_Events_Result(
                tournament: $tournament,
                event: $event,
                tab: 'events'
            );
        }

        return new Tournament_Events_Result(
            tournament: $tournament,
            events: $this->tournament_service->get_events_with_details_for_tournament( $query->tournament_id ),
            tab: 'events'
        );
    }
}
