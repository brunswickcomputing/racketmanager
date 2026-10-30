<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Players_Result;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use function __;
use function is_array;
use function is_numeric;
use Throwable;

/**
 * Query handler for [league-players] shortcode.
 */
class Get_League_Players_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly League_Repository_Interface $league_repository,
        private readonly Player_Service $player_service,
        private readonly Player_Query_Service $player_query_service,
        private readonly Player_Stats_Service $player_stats_service,
        private readonly League_Query_Service $league_query_service
    ) {}

    public function handle( Get_League_Players_Query $query ): League_Players_Result {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return League_Players_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        $this->league_query()->set_season( $league, $query->season ?? false );
        $season_name = $league->current_season['name'] ?? null;
        $player      = null;

        if ( $query->player_id ) {
            if ( is_numeric( $query->player_id ) ) {
                try {
                    $player = $this->player_service->get_player( (int) $query->player_id );
                } catch ( Throwable ) {
                    $player = null;
                }
            } else {
                $player = $this->player_service->get_player_by_name( (string) $query->player_id );
            }

            if ( ! $player ) {
                return League_Players_Result::error( __( 'Player not found', 'racketmanager' ) );
            }

            $player->matches = $this->player_query_service->get_matches( $player, $league, $season_name, 'league' );
            $player->stats   = $this->player_stats_service->get_stats( $player );
            $league->player  = $player;
        } else {
            $league->players = $this->player_stats_service->populate_players_stats(
                $this->league_query()->get_players( $league ),
                $league,
                $season_name,
                'league'
            );
            foreach ( $league->players as $p ) {
                $p->team = $this->league_repository->get_player_team( $league->get_id(), [ 'player' => $p->get_id(), 'season' => $season_name ] ) ?? new \stdClass();
            }
        }

        return new League_Players_Result(
            league: $league,
            player: $player,
            raw_players: is_array( $league->players ?? null ) ? $league->players : [],
            season_name: $season_name,
            template: $query->template
        );
    }

    private function league_query(): League_Query_Service {
        return $this->league_query_service;
    }
}
