<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use function __;

class Get_Event_Dropdown_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service
    ) {}

    public function handle( Get_Event_Dropdown_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'No event id supplied', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        $leagues  = $event->get_leagues();
        $filename = ! empty( $query->template ) ? 'dropdown-' . $query->template : 'dropdown';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'leagues' => $leagues,
            ],
            'event'
        );
    }
}
