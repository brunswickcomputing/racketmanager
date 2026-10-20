<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Draws_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Tournament_Presenter $tournament_presenter
    ) {}

    public function handle( Get_Tournament_Draws_Query $query ): Shortcode_View_Model {
        if ( ! $query->tournament_id ) {
            return Shortcode_View_Model::for_error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        if ( $query->draw_id ) {
            try {
                $draw = $this->tournament_service->get_draw_details_for_tournament( $tournament, $query->draw_id );
            } catch ( Event_Not_Found_Exception $e ) {
                return Shortcode_View_Model::for_error( $e->getMessage() );
            }

            $raw_fixtures = $draw->get_meta( 'fixtures' ) ?? [];
            if ( is_array( $raw_fixtures ) ) {
                $draw->set_meta( 'fixtures', $this->tournament_presenter->present_match_cards( $raw_fixtures, $tournament ) );
            }

            $draw = $this->tournament_presenter->present_tournament_draw( $draw, $tournament );

            return Shortcode_View_Model::for_template(
                'draw',
                [
                    'tournament'      => $tournament,
                    'draw'            => $draw,
                    'tournament_link' => $this->tournament_presenter->get_link( $tournament ),
                    'tab'             => 'draws',
                ],
                'tournament'
            );
        }

        return Shortcode_View_Model::for_template(
            'draws',
            [
                'tournament'      => $tournament,
                'draws'           => $this->tournament_presenter->present_draw_list_rows(
                    $tournament,
                    $this->tournament_service->get_events_with_details_for_tournament( $query->tournament_id )
                ),
                'tournament_link' => $this->tournament_presenter->get_link( $tournament ),
                'tab'             => 'draws',
            ],
            'tournament'
        );
    }
}
