<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Roles_Result;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Services\Club_Service;

/**
 * Handles the [club-roles] query.
 */
class Get_Club_Roles_Handler {
    public function __construct( private readonly Club_Service $club_service ) {}

    public function handle( Get_Club_Roles_Query $query ): Club_Roles_Result {
        try {
            $club  = $this->club_service->get_club_by_shortcode( $query->club_name );
            $roles = $this->club_service->get_roles_for_club( (int) $club->id );
        } catch ( Club_Not_Found_Exception $e ) {
            return Club_Roles_Result::error( $e->getMessage() );
        }

        return new Club_Roles_Result(
            club: $club,
            roles: $roles,
            template: $query->template
        );
    }
}
