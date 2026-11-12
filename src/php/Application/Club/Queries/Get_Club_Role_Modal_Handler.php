<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Role_Modal_Result;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Util\Util_Lookup;

/**
 * Handles the [club-role-modal] query. Errors are rendered as modal alerts.
 */
readonly class Get_Club_Role_Modal_Handler {
    /**
     * Initialises the instance with the club service dependency.
     *
     * @param Club_Service_Interface $club_service Club service used to manage club-related operations.
     *
     * @return void
     */
    public function __construct( private Club_Service_Interface $club_service ) {}

    /**
     * Handles a request to retrieve club role data for a modal result.
     *
     * @param Get_Club_Role_Modal_Query $query Query containing the role identifier, modal context, and template information.
     *
     * @return Club_Role_Modal_Result Result containing the club role modal data, or an error result when the role assignment is not found.
     */
    public function handle( Get_Club_Role_Modal_Query $query ): Club_Role_Modal_Result {
        try {
            $club_role = $this->club_service->get_role( $query->role_id );
        } catch ( Role_Assignment_Not_Found_Exception $e ) {
            return Club_Role_Modal_Result::error( $e->getMessage() );
        }

        return new Club_Role_Modal_Result(
            club_role: $club_role,
            modal: $query->modal,
            club_roles: Util_Lookup::get_club_roles(),
            template: $query->template
        );
    }
}
