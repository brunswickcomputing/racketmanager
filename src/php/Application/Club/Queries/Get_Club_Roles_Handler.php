<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Roles_Result;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Exceptions\Club_Not_Found_Exception;

/**
 * Handles the [club-roles] query.
 */
readonly class Get_Club_Roles_Handler {
    /**
     * Initialises the object with the club service dependency.
     *
     * @param Club_Service_Interface $club_service Club service dependency.
     *
     * @return void
     */
    public function __construct( private Club_Service_Interface $club_service ) {}

    /**
     * Handles a request to retrieve roles for a club.
     *
     * @param Get_Club_Roles_Query $query Query containing the club name and template.
     *
     * @return Club_Roles_Result Result containing the club roles or an error message.
     */
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
