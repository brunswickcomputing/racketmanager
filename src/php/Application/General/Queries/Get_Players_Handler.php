<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Players_Result;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use function is_object;

class Get_Players_Handler {
    public function __construct(
        private readonly Player_Repository_Interface $player_repository,
        private readonly User_Repository_Interface $user_repository
    ) {}

    public function handle( Get_Players_Query $query ): Players_Result {
        $players = [];
        if ( ! empty( $query->search_string ) ) {
            $players = $this->player_repository->find_players_summary( [ 'name' => $query->search_string ] );
        }

        $favourites = [];
        if ( $query->is_logged_in && $query->user_id ) {
            $user = $this->user_repository->find( $query->user_id );

            if ( is_object( $user ) ) {
                $favourites = $this->user_repository->get_favourites( $user, 'player' ) ?: [];
            }
        }

        return new Players_Result(
            players: $players,
            search_string: $query->search_string,
            favourites: $favourites,
            template: $query->template
        );
    }
}
