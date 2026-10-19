<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;

class Get_Player_Search_Handler {
    public function __construct(
        private readonly Player_Repository_Interface $player_repository
    ) {}

    public function handle( Get_Player_Search_Query $query ): Shortcode_View_Model {
        $players = $this->player_repository->find_players_summary( [ 'name' => $query->search ] );

        $filename = ! empty( $query->template ) ? 'players-list-' . $query->template : 'players-list';

        return Shortcode_View_Model::for_template( $filename, [ 'players' => $players ] );
    }
}
