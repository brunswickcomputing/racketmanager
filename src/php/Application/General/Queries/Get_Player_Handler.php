<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Tournament_Service;
use function __;
use function function_exists;
use function is_object;
use function method_exists;
use function Racketmanager\get_player;

class Get_Player_Handler {
    public function __construct(
        private readonly ?Registration_Service $registration_service = null,
        private readonly ?Player_Service $player_service = null,
        private readonly ?Tournament_Service $tournament_service = null,
        private readonly ?Player_Presenter $player_presenter = null
    ) {}

    public function handle( Get_Player_Query $query ): Shortcode_View_Model {
        $player = null;
        if ( ! empty( $query->btm ) ) {
            $player = function_exists( 'Racketmanager\get_player' )
                ? get_player( $query->btm, 'btm' )
                : ( function_exists( 'get_player' ) ? \get_player( $query->btm, 'btm' ) : null );
        } elseif ( ! empty( $query->player_name ) ) {
            $player = function_exists( 'Racketmanager\get_player' )
                ? get_player( $query->player_name, 'name' )
                : ( function_exists( 'get_player' ) ? \get_player( $query->player_name, 'name' ) : null );
        }

        if ( ! is_object( $player ) ) {
            return Shortcode_View_Model::for_error( __( 'Player not found', 'racketmanager' ) );
        }

        $player_id = method_exists( $player, 'get_id' ) ? (int) $player->get_id() : (int) ( $player->ID ?? 0 );

        $player_clubs = $this->registration_service
            ? $this->registration_service->get_clubs_for_player( $player_id )
            : ( method_exists( $player, 'get_clubs' ) ? $player->get_clubs() : [] );

        $titles = $this->player_service
            ? $this->player_service->get_titles_for_player( $player_id )
            : ( method_exists( $player, 'get_titles' ) ? $player->get_titles() : [] );

        $player->stats = method_exists( $player, 'get_career_stats' ) ? $player->get_career_stats() : null;

        $player_competitions = [];
        $competition_types   = [ 'cup', 'league', 'tournament' ];
        foreach ( $competition_types as $competition_type ) {
            if ( 'tournament' === $competition_type && $this->tournament_service ) {
                $player_competitions[ $competition_type ] = $this->tournament_service->get_tournaments_for_player( $player_id );
            } elseif ( method_exists( $player, 'get_competitions' ) ) {
                $player_competitions[ $competition_type ] = $player->get_competitions( [ 'type' => $competition_type ] );
            } else {
                $player_competitions[ $competition_type ] = [];
            }
        }

        $filename  = ! empty( $query->template ) ? 'player-' . $query->template : 'player';
        $presenter = $this->player_presenter ?? new Player_Presenter();

        $player_vm = $presenter->present_player(
            player: $player,
            clubs: $player_clubs,
            titles: $titles,
            player_competitions: $player_competitions,
            is_page_header: true,
            template: $filename
        );

        return Shortcode_View_Model::for_template( $filename, $player_vm->template_vars() );
    }
}
