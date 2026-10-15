<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use function __;

class Get_Event_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service
    ) {}

    public function handle( Get_Event_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        $event->set_season( $query->season );
        if ( empty( $event->current_season ) ) {
            return Shortcode_View_Model::for_error( __( 'Season not found for event', 'racketmanager' ) );
        }

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? null, $this->competition_service ) ) {
            return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $season  = $event->current_season['name'] ?? '';
        $seasons = $event->get_seasons();
        $tab     = $query->tab;

        return Shortcode_View_Model::for_template(
            'event',
            [
                'event'       => $event,
                'seasons'     => $seasons,
                'curr_season' => $season,
                'tab'         => $tab,
            ],
            false
        );
    }
}
