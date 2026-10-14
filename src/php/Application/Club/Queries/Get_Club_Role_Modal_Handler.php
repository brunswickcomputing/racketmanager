<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Util\Util_Lookup;

/**
 * Handles the [club-role-modal] query. Errors are rendered as modal alerts.
 */
class Get_Club_Role_Modal_Handler {
    public function __construct( private readonly Club_Service $club_service ) {}

    public function handle( Get_Club_Role_Modal_Query $query ): Shortcode_View_Model {
        try {
            $club_role = $this->club_service->get_role( $query->role_id );
        } catch ( Role_Assignment_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage(), 'modal' );
        }

        return new Shortcode_View_Model(
            template: '' !== $query->template ? 'club-role-modal-' . $query->template : 'club-role-modal',
            vars: [
                'club_role'  => $club_role,
                'modal'      => $query->modal,
                'club_roles' => Util_Lookup::get_club_roles(),
            ],
            template_type: 'club'
        );
    }
}
