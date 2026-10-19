<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Order_Of_Play_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Order_Of_Play_Handler {

    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Competition_Repository_Interface $competition_repository,
        private readonly Order_Of_Play_Presenter $order_of_play_presenter,
    ) {
    }

    public function handle( Get_Order_Of_Play_Query $query ): Shortcode_View_Model {
        $tournament_id  = $query->tournament_id;
        $competition_id = $query->competition_id;
        $season         = $query->season;
        $finals_order   = [];

        if ( $tournament_id ) {
            try {
                $tournament   = $this->tournament_service->get_tournament( $tournament_id );
                $finals_order = $tournament->get_order_of_play();
            } catch ( Tournament_Not_Found_Exception $e ) {
                return Shortcode_View_Model::for_error( $e->getMessage() );
            }
        } elseif ( $competition_id ) {
            $competition = $this->competition_repository->find_by_id( (int) $competition_id );
            if ( $competition ) {
                if ( $season ) {
                    $competition_season = $competition->get_season_by_name( $season );
                    if ( $competition_season ) {
                        $finals_order = $competition_season['orderofplay'] ?? [];
                    } else {
                        return Shortcode_View_Model::for_error( __( 'Season not found for competition', 'racketmanager' ) );
                    }
                } else {
                    return Shortcode_View_Model::for_error( __( 'Season not found', 'racketmanager' ) );
                }
            } else {
                return Shortcode_View_Model::for_error( __( 'Competition not found', 'racketmanager' ) );
            }
        } else {
            return Shortcode_View_Model::for_error( __( 'Competition not found', 'racketmanager' ) );
        }

        $order_of_play = $this->order_of_play_presenter->present( $finals_order );

        $filename = ( '' !== $query->template ) ? 'orderofplay-' . $query->template : 'orderofplay';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'order_of_play' => $order_of_play,
            ],
            false
        );
    }
}
