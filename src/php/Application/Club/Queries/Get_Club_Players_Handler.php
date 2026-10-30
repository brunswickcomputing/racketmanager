<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Players_Result;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use function __;
use function is_array;

/**
 * Handles the [club-players] query: the club's active roster, or a single club player.
 */
readonly class Get_Club_Players_Handler {
    public function __construct(
        private Club_Service $club_service,
        private Player_Service $player_service,
        private Registration_Service $registration_service,
        private Option_Gateway_Interface $option_gateway
    ) {}

    public function handle( Get_Club_Players_Query $query ): Club_Players_Result {
        $raw_players = [];
        $player      = null;
        $club_player = null;

        $display_opt = $this->option_gateway->get_option( 'racketmanager_display', [] );
        if ( ! is_array( $display_opt ) ) {
            $display_opt = [];
        }
        $use_wtn = ! empty( $display_opt['wtn'] );

        try {
            $club = $this->club_service->get_club_by_shortcode( $query->club_name );

            if ( ! empty( $query->player_name ) ) {
                $player = $this->player_service->get_player_by_name( $query->player_name );
                if ( ! $player ) {
                    throw new Player_Not_Found_Exception( __( 'Player not found', 'racketmanager' ) );
                }
                $club_player = $this->registration_service->get_player_for_club( (int) $club->id, (int) $player->id );
                if ( ! $club_player ) {
                    throw new Player_Not_Found_Exception( __( 'Player not found', 'racketmanager' ) );
                }
            } else {
                $raw_players = $this->registration_service->get_registered_players_list( 'active', null, (int) $club->id );
            }
        } catch ( Club_Not_Found_Exception|Player_Not_Found_Exception $e ) {
            return Club_Players_Result::error( $e->getMessage() );
        }

        return new Club_Players_Result(
            club: $club,
            player: $player,
            club_player: $club_player,
            raw_players: $raw_players,
            user_can_update: $this->club_service->can_user_update_players( (int) $club->get_id() ),
            display_opt: $display_opt,
            use_wtn: $use_wtn,
            referrer: $query->referrer,
            template: $query->template
        );
    }
}
