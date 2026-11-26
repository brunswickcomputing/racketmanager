<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Competition\DTOs\Competition_Dropdown_Result;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;

/**
 * Handles competition dropdown queries by retrieving competition events and resolving the dropdown template.
 *
 * Returns a successful dropdown result when competition events are available, or an error result when the requested competition cannot be found.
 */
readonly class Get_Competition_Dropdown_Handler {
    /**
     * Initialises the instance with the competition service dependency.
     *
     * @param Competition_Service_Interface $competition_service Competition service used by this instance.
     *
     * @return void
     */
    public function __construct( private Competition_Service_Interface $competition_service ) {}

    /**
     * Handles a request for competition dropdown data.
     *
     * @param Get_Competition_Dropdown_Query $query Query containing the competition identifier and optional template suffix.
     *
     * @return Competition_Dropdown_Result Result containing dropdown events and template name, or an error result if the competition is not found.
     */
    public function handle( Get_Competition_Dropdown_Query $query ): Competition_Dropdown_Result {
        try {
            $events = $this->competition_service->get_events_for_competition( $query->competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Competition_Dropdown_Result::error( $e->getMessage() );
        }

        return new Competition_Dropdown_Result(
            events: $events,
            template: '' !== $query->template ? 'dropdown-' . $query->template : 'dropdown'
        );
    }
}
