<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Events_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
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

        if ( $query->event_id ) {
            try {
                $event = $this->tournament_service->get_event_details_for_tournament( $tournament, $query->event_id );
            } catch ( Tournament_Not_Found_Exception|Event_Not_Found_Exception $e ) {
                return Shortcode_View_Model::for_error( $e->getMessage() );
            }

            return Shortcode_View_Model::for_template(
                'event',
                [
                    'tournament' => $tournament,
                    'event'      => $event,
                    'tab'        => 'events',
                ],
                'tournament'
            );
        }

        $events = $this->tournament_service->get_events_with_details_for_tournament( $query->tournament_id );

        return Shortcode_View_Model::for_template(
            'events',
            [
                'tournament' => $tournament,
                'events'     => $events,
                'tab'        => 'events',
            ],
            'tournament'
        );
    }
}
