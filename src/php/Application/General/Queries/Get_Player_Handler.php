<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Player_Result;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Tournament_Service;
use function __;
use function is_object;
use function method_exists;

class Get_Player_Handler {
    public function __construct(
        private readonly Player_Query_Service $player_query_service,
        private readonly Player_Stats_Service $player_stats_service,
        private readonly Player_Service $player_service,
        private readonly ?Registration_Service $registration_service = null,
        private readonly ?Tournament_Service $tournament_service = null
    ) {}

    public function handle( Get_Player_Query $query ): Player_Result {
        $player = null;
        if ( ! empty( $query->btm ) ) {
            $player = $this->player_service->find_player_by_btm( $query->btm );
        } elseif ( ! empty( $query->player_name ) ) {
            $player = $this->player_service->get_player_by_name( $query->player_name );
        }

        if ( ! is_object( $player ) ) {
            return Player_Result::error( __( 'Player not found', 'racketmanager' ) );
        }

        $player_id = method_exists( $player, 'get_id' ) ? (int) $player->get_id() : (int) ( $player->ID ?? 0 );

        $player_clubs = $this->registration_service
            ? $this->registration_service->get_clubs_for_player( $player_id )
            : ( method_exists( $player, 'get_clubs' ) ? $player->get_clubs() : [] );

        $titles = $this->player_service->get_titles_for_player( $player_id );

        $player->stats = $this->player_stats_service->get_career_stats( $player );

        $player_competitions = [];
        $competition_types   = [ 'cup', 'league', 'tournament' ];
        foreach ( $competition_types as $competition_type ) {
            if ( 'tournament' === $competition_type && $this->tournament_service ) {
                $player_competitions[ $competition_type ] = $this->tournament_service->get_tournaments_for_player( $player_id );
            } else {
                $player_competitions[ $competition_type ] = $this->player_query_service->get_competitions( $player, [ 'type' => $competition_type ] );
            }
        }

        return new Player_Result(
            player: $player,
            clubs: $player_clubs,
            titles: $titles,
            player_competitions: $player_competitions,
            template: $query->template
        );
    }
}
