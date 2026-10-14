<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Domain\Club;
use Racketmanager\Domain\DTO\Club\Club_Details_DTO;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Club_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Settings_Service;

/**
 * Query handler for resolving [club] shortcode data into a view model.
 */
class Get_Club_Handler {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly ?Settings_Service $settings_service = null
    ) {}

    public function handle( Get_Club_Query $query ): Club_View_Model {
        $club_name    = $query->club_name;
        $club         = null;
        $club_details = null;

        if ( empty( $club_name ) ) {
            return new Club_View_Model(
                error_message: __( 'Club not found', 'racketmanager' )
            );
        }

        try {
            $club         = $this->club_service->get_club_by_shortcode( $club_name );
            $club_details = $this->club_service->get_club_details( (int) $club->id );
        } catch ( Club_Not_Found_Exception $e ) {
            return new Club_View_Model(
                error_message: $e->getMessage()
            );
        }

        if ( ! $club_details ) {
            return new Club_View_Model(
                error_message: __( 'Club not found', 'racketmanager' )
            );
        }

        if ( is_object( $club ) ) {
            $club->single = true;
        }
        if ( is_object( $club_details ) ) {
            $club_details->single = true;
        }

        $user_can_update_club = false;
        if ( is_user_logged_in() ) {
            $user   = wp_get_current_user();
            $userid = $user->ID ?? 0;
            $target_id = $club ? $club->id : ( $club_details instanceof Club || is_object( $club_details ) ? $club_details->id : 0 );
            if ( current_user_can( 'manage_racketmanager' ) || ( $target_id && $this->club_service->is_user_match_secretary( (int) $userid, (int) $target_id ) ) ) {
                $user_can_update_club = true;
            }
        }

        $google_maps_key = '';
        if ( $this->settings_service ) {
            $keys            = $this->settings_service->get_category( 'keys' );
            $google_maps_key = $keys['googleMapsKey'] ?? '';
        } else {
            global $racketmanager;
            if ( is_object( $racketmanager ) && method_exists( $racketmanager, 'get_options' ) ) {
                $keys            = $racketmanager->get_options( 'keys' );
                $google_maps_key = $keys['googleMapsKey'] ?? '';
            }
        }

        $template = ! empty( $query->template ) ? 'club-' . $query->template : 'club';

        return new Club_View_Model(
            template: $template,
            club: $club_details,
            google_maps_key: $google_maps_key,
            user_can_update_club: $user_can_update_club,
            standalone: true
        );
    }
}
