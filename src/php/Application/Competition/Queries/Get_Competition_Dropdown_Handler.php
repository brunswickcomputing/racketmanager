<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;

class Get_Competition_Dropdown_Handler {
    public function __construct( private readonly Competition_Service $competition_service ) {}

    public function handle( Get_Competition_Dropdown_Query $query ): Shortcode_View_Model {
        try {
            $events = $this->competition_service->get_events_for_competition( $query->competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        return new Shortcode_View_Model(
            template: '' !== $query->template ? 'dropdown-' . $query->template : 'dropdown',
            vars: [ 'events' => $events ],
            template_type: 'competition'
        );
    }
}