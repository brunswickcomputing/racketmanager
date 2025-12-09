<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Util\Util;

class Team_Service {
    private Team_Repository $team_repository;
    private Club_Repository $club_repository;
    private Player_Service $player_service;

    public function __construct( Team_Repository $team_repository, Club_Repository $club_repository, Player_Service $player_service ) {
        $this->team_repository = $team_repository;
        $this->club_repository = $club_repository;
        $this->player_service  = $player_service;
    }

    public function get_teams_for_club( ?int $club_id, $type = null ): array {
        if ( ! $this->club_repository->find( $club_id ) ) {
            throw new Club_Not_Found_Exception( Util::club_not_found( $club_id ) );
        }
        return $this->team_repository->find_by_club( $club_id, $type );
    }

    public function get_player_teams(): array {
        return $this->team_repository->find_for_players();
    }

}
