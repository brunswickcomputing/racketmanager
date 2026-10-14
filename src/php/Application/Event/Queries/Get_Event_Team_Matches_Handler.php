<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use function __;

class Get_Event_Team_Matches_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service
    ) {}

    public function handle( Get_Event_Team_Matches_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'No event id supplied', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        if ( empty( $query->team_id ) ) {
            return Shortcode_View_Model::for_error( __( 'No team id supplied', 'racketmanager' ) );
        }

        $match_args = [
            'season'  => $event->current_season['name'] ?? '',
            'team_id' => $query->team_id,
            'pending' => true,
        ];
        $matches    = $event->get_matches( $match_args );
        $filename   = ! empty( $query->template ) ? 'team-matches-' . $query->template : 'team-matches';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'matches' => $matches,
            ],
            'event'
        );
    }
}
