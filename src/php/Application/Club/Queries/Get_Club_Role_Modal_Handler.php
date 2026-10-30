<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Role_Modal_Result;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Services\Club_Service;
use Racketmanager\Util\Util_Lookup;

/**
 * Handles the [club-role-modal] query. Errors are rendered as modal alerts.
 */
class Get_Club_Role_Modal_Handler {
    public function __construct( private readonly Club_Service $club_service ) {}

    public function handle( Get_Club_Role_Modal_Query $query ): Club_Role_Modal_Result {
        try {
            $club_role = $this->club_service->get_role( $query->role_id );
        } catch ( Role_Assignment_Not_Found_Exception $e ) {
            return Club_Role_Modal_Result::error( $e->getMessage(), 'modal' );
        }

        return new Club_Role_Modal_Result(
            club_role: $club_role,
            modal: $query->modal,
            club_roles: Util_Lookup::get_club_roles(),
            template: $query->template
        );
    }
}
