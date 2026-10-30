<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Tournament_Winners_Result;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Winners_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Winners_Query $query ): Tournament_Winners_Result {
        if ( ! $query->tournament_id ) {
            return Tournament_Winners_Result::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
            $winners    = $this->tournament_service->get_winners_for_tournament( $tournament );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Tournament_Winners_Result::error( $e->getMessage() );
        }

        return new Tournament_Winners_Result(
            tournament: $tournament,
            winners: $winners,
            tab: 'winners',
            template: $query->template
        );
    }
}
