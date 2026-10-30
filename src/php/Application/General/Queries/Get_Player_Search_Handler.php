<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Player_Search_Result;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;

class Get_Player_Search_Handler {
    public function __construct(
        private readonly Player_Repository_Interface $player_repository
    ) {}

    public function handle( Get_Player_Search_Query $query ): Player_Search_Result {
        $players = $this->player_repository->find_players_summary( [ 'name' => $query->search ] );

        return new Player_Search_Result(
            players: $players,
            search: $query->search,
            template: $query->template
        );
    }
}
