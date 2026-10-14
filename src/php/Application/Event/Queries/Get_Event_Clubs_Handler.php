<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use function __;
use function is_numeric;

class Get_Event_Clubs_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Club_Service $club_service
    ) {}

    public function handle( Get_Event_Clubs_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        $event_club = null;
        $event->set_season( $query->season );

        if ( ! empty( $query->club_id ) ) {
            try {
                if ( is_numeric( $query->club_id ) ) {
                    $club = $this->club_service->get_club( (int) $query->club_id );
                } else {
                    $club = $this->club_service->get_club_by_shortcode( (string) $query->club_id );
                }
                $event_club = $event->get_club( $club );
            } catch ( Club_Not_Found_Exception ) {
                return Shortcode_View_Model::for_error( __( 'Club not found', 'racketmanager' ) );
            }
        }

        $event->clubs = $event->get_clubs( [ 'status' => 1 ] );
        $filename     = ! empty( $query->template ) ? 'clubs-' . $query->template : 'clubs';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'event'      => $event,
                'event_club' => $event_club,
            ],
            'event'
        );
    }
}
