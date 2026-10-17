<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Domain\Club;
use Racketmanager\Domain\DTO\Club\Club_Details_DTO;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\View_Models\Club_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Settings_Service;

/**
 * Query handler for resolving [club] shortcode data into a view model.
 */
class Get_Club_Handler {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Settings_Service $settings_service,
        private readonly Club_Presenter $club_presenter,
        private readonly Security_Service_Interface $security_service
    ) {}

    public function handle( Get_Club_Query $query ): Club_View_Model {
        $club_name    = $query->club_name;
        $club         = null;
        $club_details = null;

        if ( empty( $club_name ) ) {
            return Club_View_Model::for_error( __( 'Club not found', 'racketmanager' ) );
        }

        try {
            $club         = $this->club_service->get_club_by_shortcode( $club_name );
            $club_details = $this->club_service->get_club_details( (int) $club->id );
        } catch ( Club_Not_Found_Exception $e ) {
            return Club_View_Model::for_error( $e->getMessage() );
        }

        if ( ! $club_details ) {
            return Club_View_Model::for_error( __( 'Club not found', 'racketmanager' ) );
        }

        if ( is_object( $club ) ) {
            $club->single = true;
        }
        if ( is_object( $club_details ) ) {
            $club_details->single = true;
        }

        $user_can_update_club = false;
        if ( $this->security_service->is_user_logged_in() ) {
            $userid     = $this->security_service->get_current_user_id();
            $target_id  = $this->resolve_target_club_id( $club, $club_details );
            $can_manage = $this->security_service->current_user_can( 'manage_racketmanager' );
            if ( $can_manage || ( $target_id > 0 && $this->club_service->is_user_match_secretary( $userid, $target_id ) ) ) {
                $user_can_update_club = true;
            }
        }

        $keys            = $this->settings_service->get_category( 'keys' );
        $google_maps_key = $keys['googleMapsKey'] ?? '';
        $template        = ! empty( $query->template ) ? 'club-' . $query->template : 'club';

        return $this->club_presenter->present(
            club: $club_details,
            standalone: true,
            google_maps_key: $google_maps_key,
            user_can_update_club: $user_can_update_club,
            template: $template
        );
    }

    private function resolve_target_club_id( ?object $club, object|array|null $club_details ): int {
        if ( $club && isset( $club->id ) ) {
            return (int) $club->id;
        }
        if ( $club_details instanceof Club || is_object( $club_details ) ) {
            return (int) ( $club_details->id ?? 0 );
        }
        return 0;
    }
}
