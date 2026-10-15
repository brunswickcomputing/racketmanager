<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use function __;
use function str_starts_with;

class Get_Event_Partner_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Player_Service $player_service
    ) {}

    public function handle( Get_Event_Partner_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ), 'modal' );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ), 'modal' );
        }

        $event->set_season( $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ), 'modal' );
        }

        $event_type = (string) ( $event->type ?? '' );
        if ( 'M' === $query->gender ) {
            if ( str_starts_with( $event_type, 'M' ) || str_starts_with( $event_type, 'B' ) ) {
                $partner_gender = 'M';
            } else {
                $partner_gender = 'F';
            }
        } elseif ( str_starts_with( $event_type, 'W' ) || str_starts_with( $event_type, 'G' ) ) {
            $partner_gender = 'F';
        } else {
            $partner_gender = 'M';
        }

        $partner_name = null;
        $partner_btm  = null;
        if ( ! empty( $query->partner_id ) ) {
            try {
                $partner      = $this->player_service->get_player( $query->partner_id );
                $partner_name = $partner->display_name ?? ( $partner->fullname ?? null );
                $partner_btm  = $partner->btm ?? null;
            } catch ( Player_Not_Found_Exception ) {
                $partner_name = null;
                $partner_btm  = null;
            }
        }

        $filename = ! empty( $query->template ) ? 'partner-modal-' . $query->template : 'partner-modal';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'event'          => $event,
                'player_id'      => $query->player_id,
                'partner_name'   => $partner_name,
                'partner_btm'    => $partner_btm,
                'partner_id'     => $query->partner_id,
                'partner_gender' => $partner_gender,
                'date_end'       => $query->date_end,
                'season'         => $query->season,
                'modal'          => $query->modal,
                'tournament_id'  => $query->tournament_id,
            ],
            'event'
        );
    }
}
