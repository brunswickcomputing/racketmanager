<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Tournament_Draws_Result;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Draws_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Draws_Query $query ): Tournament_Draws_Result {
        if ( ! $query->tournament_id ) {
            return Tournament_Draws_Result::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Tournament_Draws_Result::error( $e->getMessage() );
        }

        if ( $query->draw_id ) {
            try {
                $draw = $this->tournament_service->get_draw_details_for_tournament( $tournament, $query->draw_id );
            } catch ( Event_Not_Found_Exception $e ) {
                return Tournament_Draws_Result::error( $e->getMessage() );
            }

            return new Tournament_Draws_Result(
                tournament: $tournament,
                draw: $draw,
                tab: 'draws'
            );
        }

        return new Tournament_Draws_Result(
            tournament: $tournament,
            draws: $this->tournament_service->get_events_with_details_for_tournament( $query->tournament_id ),
            tab: 'draws'
        );
    }
}
