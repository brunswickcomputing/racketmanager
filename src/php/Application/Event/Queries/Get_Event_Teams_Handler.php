<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use function __;

class Get_Event_Teams_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly ?Team_Service $team_service = null
    ) {}

    public function handle( Get_Event_Teams_Query $query ): Shortcode_View_Model {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
        }

        $event->set_season( $query->season );
        $current_season = $event->current_season['name'] ?? '';

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $current_season ?: $query->season, $this->competition_service ) ) {
            return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $event->teams   = $event->get_teams( [
            'season'  => $current_season,
            'orderby' => [ 'name' => 'ASC' ],
        ] );

        $team_identifier = $query->team;
        $team            = null;
        if ( ! empty( $team_identifier ) && $this->team_service ) {
            try {
                $team = $this->team_service->get_team_by_id( $team_identifier );
            } catch ( \Throwable ) {
                $team = null;
            }
            if ( $team ) {
                $team->info    = $event->get_team_info( $team->id );
                $team->matches = $event->get_matches( [
                    'team_id'          => $team->id,
                    'match_day'        => false,
                    'limit'            => 'false',
                    'reset_query_args' => true,
                    'season'           => $current_season,
                    'orderby'          => [ 'date' => 'ASC' ],
                ] );
                $team->players = $event->get_players( [
                    'team'  => $team->id,
                    'stats' => true,
                ] );
                $event->team   = $team;
            }
        }

        $template = $query->template;
        if ( null === $team && ! empty( $event->competition->is_championship ) && empty( $template ) ) {
            $template = 'list';
        }

        $tab      = 'teams';
        $filename = empty( $template ) ? 'teams' : 'teams-' . $template;

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'event'       => $event,
                'tab'         => $tab,
                'curr_season' => $current_season,
            ],
            'event'
        );
    }
}
