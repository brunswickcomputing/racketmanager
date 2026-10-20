<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Events_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Tournament_Presenter $tournament_presenter
    ) {}

    public function handle( Get_Tournament_Events_Query $query ): Shortcode_View_Model {
        if ( ! $query->tournament_id ) {
            return Shortcode_View_Model::for_error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        $tournament_link = $this->tournament_presenter->get_link( $tournament );

        if ( $query->event_id ) {
            try {
                $event = $this->tournament_service->get_event_details_for_tournament( $tournament, $query->event_id );
            } catch ( Tournament_Not_Found_Exception|Event_Not_Found_Exception $e ) {
                return Shortcode_View_Model::for_error( $e->getMessage() );
            }

            return Shortcode_View_Model::for_template(
                'event',
                [
                    'tournament'      => $tournament,
                    'event'           => $event,
                    'tournament_link' => $tournament_link,
                    'draw_link'       => $this->tournament_presenter->get_draw_link( $tournament, $event ),
                    'player_links'    => $this->tournament_presenter->present_event_player_links( $tournament, $event ),
                    'tab'             => 'events',
                ],
                'tournament'
            );
        }

        return Shortcode_View_Model::for_template(
            'events',
            [
                'tournament'      => $tournament,
                'events'          => $this->tournament_presenter->present_event_list_rows(
                    $tournament,
                    $this->tournament_service->get_events_with_details_for_tournament( $query->tournament_id )
                ),
                'tournament_link' => $tournament_link,
                'tab'             => 'events',
            ],
            'tournament'
        );
    }
}
