<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Tournament_Withdrawal_Result;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Withdrawal_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Withdrawal_Query $query ): Tournament_Withdrawal_Result {
        $tournament_id = is_numeric( $query->tournament_id ) ? (int) $query->tournament_id : null;
        $player_id     = is_numeric( $query->player_id ) ? (int) $query->player_id : null;
        $modal         = $query->modal;
        $msg           = null;

        if ( ! $tournament_id ) {
            return Tournament_Withdrawal_Result::error( __( 'Tournament id not found', 'racketmanager' ), 'modal' );
        }

        try {
            $tournament_entries = $this->tournament_service->get_tournament_event_entry_details_for_player( $player_id, $tournament_id );
        } catch ( Tournament_Not_Found_Exception|Player_Not_Found_Exception $e ) {
            return Tournament_Withdrawal_Result::error( $e->getMessage(), 'modal' );
        }

        if ( empty( $tournament_entries ) ) {
            $msg = __( 'You are not currently entered into any event.', 'racketmanager' );
        }

        return new Tournament_Withdrawal_Result(
            tournament_id: $query->tournament_id,
            player_id: $query->player_id,
            modal: $modal,
            msg: $msg,
            events_entered: $tournament_entries
        );
    }
}
