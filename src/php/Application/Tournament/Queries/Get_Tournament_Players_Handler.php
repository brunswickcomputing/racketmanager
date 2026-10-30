<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Tournament_Players_Result;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Entry_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Players_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Players_Query $query ): Tournament_Players_Result {
        if ( ! $query->tournament_id ) {
            return Tournament_Players_Result::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Tournament_Players_Result::error( $e->getMessage() );
        }

        if ( $query->player_id ) {
            try {
                $tournament_player = $this->tournament_service->get_player_details_for_tournament( $tournament, $query->player_id );
            } catch ( Player_Not_Found_Exception|Tournament_Entry_Not_Found_Exception $e ) {
                return Tournament_Players_Result::error( $e->getMessage() );
            }

            return new Tournament_Players_Result(
                tournament: $tournament,
                player: $tournament_player,
                tab: 'players'
            );
        }

        $tournament_players = $this->tournament_service->get_players_for_tournament( $tournament->get_id() ) ?? [];

        return new Tournament_Players_Result(
            tournament: $tournament,
            players: $tournament_players,
            tab: 'players'
        );
    }
}
