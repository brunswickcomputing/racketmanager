<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;

/**
 * Handles the [club-roles] query.
 */
class Get_Club_Roles_Handler {
    public function __construct( private readonly Club_Service $club_service ) {}

    public function handle( Get_Club_Roles_Query $query ): Shortcode_View_Model {
        try {
            $club  = $this->club_service->get_club_by_shortcode( $query->club_name );
            $roles = $this->club_service->get_roles_for_club( (int) $club->id );
        } catch ( Club_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        return new Shortcode_View_Model(
            template: '' !== $query->template ? 'roles-' . $query->template : 'roles',
            vars: [
                'club'  => $club,
                'roles' => $roles,
            ],
            template_type: 'club'
        );
    }
}
