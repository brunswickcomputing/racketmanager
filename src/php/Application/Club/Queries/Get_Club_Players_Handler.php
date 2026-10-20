<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use function __;
use function is_array;

/**
 * Handles the [club-players] query: the club's active roster, or a single club player.
 */
class Get_Club_Players_Handler {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Player_Service $player_service,
        private readonly Registration_Service $registration_service,
        private readonly Club_Presenter $club_presenter,
        private readonly Option_Gateway_Interface $option_gateway,
        private readonly Player_Presenter $player_presenter
    ) {}

    public function handle( Get_Club_Players_Query $query ): Shortcode_View_Model {
        $players     = [];
        $club_player = null;
        $player_vm   = null;

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
                $player_vm = $this->player_presenter->present_player(
                    player: $club_player,
                    clubs: [ $club_player ],
                    is_page_header: false
                );
            } else {
                $raw_players = $this->registration_service->get_registered_players_list( 'active', null, (int) $club->id );
                $players     = array_map(
                    fn( $p ) => $this->club_presenter->present_roster_player( $p, $club, $use_wtn ),
                    $raw_players
                );
            }
        } catch ( Club_Not_Found_Exception|Player_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        $club_players_url = $this->club_presenter->get_link( $club, 'players' );
        $page_referrer    = $query->referrer ?: $club_players_url;

        return new Shortcode_View_Model(
            template: '' !== $query->template ? 'players-' . $query->template : 'players',
            vars: [
                'club'             => $club,
                'user_can_update'  => $this->club_service->can_user_update_players( (int) $club->get_id() ),
                'players'          => $players,
                'player'           => $club_player,
                'player_vm'        => $player_vm,
                'display_opt'      => $display_opt,
                'use_wtn'          => $use_wtn,
                'rating_header'    => $use_wtn ? __( 'WTN', 'racketmanager' ) : __( 'Rating Points', 'racketmanager' ),
                'club_players_url' => $club_players_url,
                'page_referrer'    => $page_referrer,
            ],
            template_type: 'club'
        );
    }
}
