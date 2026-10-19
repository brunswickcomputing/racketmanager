<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Util\Util;
use function __;
use function asort;
use function is_array;
use function is_numeric;
use function is_object;

class Get_Event_Players_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Player_Service $player_service,
        private readonly Player_Query_Service $player_query_service,
        private readonly Player_Stats_Service $player_stats_service
    ) {}

    public function handle( Get_Event_Players_Query $query ): Shortcode_View_Model {
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

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $event->players = [];

        if ( ! empty( $query->player_id ) ) {
            $player = $this->get_player_info( $event, $query->player_id );
            if ( is_object( $player ) ) {
                $event->player = $player;
            } else {
                return Shortcode_View_Model::for_error( __( 'Player not found', 'racketmanager' ) );
            }
        } else {
            $current_season = $event->current_season['name'] ?? null;
            $players        = $event->get_players( [ 'season' => $current_season ] );
            $event->players = Util::get_players_list( $players );
        }

        return Shortcode_View_Model::for_template(
            'players',
            [
                'event' => $event,
            ],
            'event'
        );
    }

    private function get_player_info( object $event, int|string $player_id ): ?object {
        try {
            if ( is_numeric( $player_id ) ) {
                $player = $this->player_service->get_player( (int) $player_id );
            } else {
                $player = $this->player_service->get_player_by_name( (string) $player_id );
            }
        } catch ( Player_Not_Found_Exception ) {
            return null;
        }

        if ( $player ) {
            $season = $event->current_season['name'] ?? null;
            $player->matches = $this->player_query_service->get_matches( $player, $event, $season, 'event' );
            if ( is_array( $player->matches ) ) {
                asort( $player->matches );
            }
            $player->stats = $this->player_stats_service->get_stats( $player );
        }

        return $player;
    }
}
