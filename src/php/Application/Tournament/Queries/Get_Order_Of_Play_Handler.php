<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Order_Of_Play_Result;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Order_Of_Play_Handler {

    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Competition_Repository_Interface $competition_repository
    ) {
    }

    public function handle( Get_Order_Of_Play_Query $query ): Order_Of_Play_Result {
        $tournament_id  = $query->tournament_id;
        $competition_id = $query->competition_id;
        $season         = $query->season;
        $finals_order   = [];

        if ( $tournament_id ) {
            try {
                $tournament   = $this->tournament_service->get_tournament( $tournament_id );
                $finals_order = $tournament->get_order_of_play();
            } catch ( Tournament_Not_Found_Exception $e ) {
                return Order_Of_Play_Result::error( $e->getMessage() );
            }
        } elseif ( $competition_id ) {
            $competition = $this->competition_repository->find_by_id( (int) $competition_id );
            if ( $competition ) {
                if ( $season ) {
                    $competition_season = $competition->get_season_by_name( $season );
                    if ( $competition_season ) {
                        $finals_order = $competition_season['orderofplay'] ?? [];
                    } else {
                        return Order_Of_Play_Result::error( __( 'Season not found for competition', 'racketmanager' ) );
                    }
                } else {
                    return Order_Of_Play_Result::error( __( 'Season not found', 'racketmanager' ) );
                }
            } else {
                return Order_Of_Play_Result::error( __( 'Competition not found', 'racketmanager' ) );
            }
        } else {
            return Order_Of_Play_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        return new Order_Of_Play_Result(
            finals_order: $finals_order,
            template: $query->template
        );
    }
}
