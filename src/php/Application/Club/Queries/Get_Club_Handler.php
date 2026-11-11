<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Overview_Result;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Services\Club_Service;

/**
 * Query handler for resolving [club] shortcode data into a view model.
 */
readonly class Get_Club_Handler {
    /**
     * Constructor
     *
     * @param Club_Service               $club_service club service.
     * @param Settings_Service_Interface $settings_service settings service.
     * @param Security_Service_Interface $security_service security service.
     */
    public function __construct(
        private Club_Service $club_service,
        private Settings_Service_Interface $settings_service,
        private Security_Service_Interface $security_service
    ) {}

    /**
     * Handle query.
     *
     * @param Get_Club_Query $query query.
     *
     * @return Club_Overview_Result
     */
    public function handle( Get_Club_Query $query ): Club_Overview_Result {
        $club_name = $query->club_name;

        if ( empty( $club_name ) ) {
            return Club_Overview_Result::error( __( 'Club not found', 'racketmanager' ) );
        }

        try {
            $club         = $this->club_service->get_club_by_shortcode( $club_name );
            $club_details = $this->club_service->get_club_details( (int) $club->id );
        } catch ( Club_Not_Found_Exception $e ) {
            return Club_Overview_Result::error( $e->getMessage() );
        }

        $club->single         = true;
        $club_details->single = true;

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

        return new Club_Overview_Result(
            club: $club,
            club_details: $club_details,
            google_maps_key: $google_maps_key,
            user_can_update_club: $user_can_update_club,
            template: $template
        );
    }

    /**
     * Resolve target club id.
     *
     * @param object|null       $club club.
     * @param object|array|null $club_details club details.
     *
     * @return int
     */
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
