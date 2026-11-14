<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Competition\DTOs\Competition_Dropdown_Result;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;

class Get_Competition_Dropdown_Handler {
    public function __construct( private readonly Competition_Service_Interface $competition_service ) {}

    public function handle( Get_Competition_Dropdown_Query $query ): Competition_Dropdown_Result {
        try {
            $events = $this->competition_service->get_events_for_competition( $query->competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Competition_Dropdown_Result::error( $e->getMessage() );
        }

        return new Competition_Dropdown_Result(
            events: $events ?? [],
            template: '' !== $query->template ? 'dropdown-' . $query->template : 'dropdown'
        );
    }
}