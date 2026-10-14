<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use function __;

/**
 * Handles the [club-players] query: the club's active roster, or a single club player.
 */
class Get_Club_Players_Handler {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Player_Service $player_service,
        private readonly Registration_Service $registration_service
    ) {}

    public function handle( Get_Club_Players_Query $query ): Shortcode_View_Model {
        $players     = [];
        $club_player = null;

        try {
            $club = $this->club_service->get_club_by_shortcode( $query->club_name );

            if ( ! empty( $query->player_name ) ) {
                $player = $this->player_service->get_player_by_name( $query->player_name );
                if ( ! $player ) {
                    throw new Player_Not_Found_Exception( __( 'Player not found', 'racketmanager' ) );
                }
                $club_player = $this->registration_service->get_player_for_club( (int) $club->id, (int) $player->id );
            } else {
                $players = $this->registration_service->get_registered_players_list( 'active', null, (int) $club->id );
            }
        } catch ( Club_Not_Found_Exception|Player_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        return new Shortcode_View_Model(
            template: '' !== $query->template ? 'players-' . $query->template : 'players',
            vars: [
                'club'            => $club,
                'user_can_update' => $this->club_service->can_user_update_players( (int) $club->get_id() ),
                'players'         => $players,
                'player'          => $club_player,
            ],
            template_type: 'club'
        );
    }
}
