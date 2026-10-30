<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Tournament_Overview_Result;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Overview_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Overview_Query $query ): Tournament_Overview_Result {
        if ( ! $query->tournament_id ) {
            return Tournament_Overview_Result::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament_overview( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Tournament_Overview_Result::error( $e->getMessage() );
        }

        return new Tournament_Overview_Result(
            tournament: $tournament,
            template: $query->template
        );
    }
}
