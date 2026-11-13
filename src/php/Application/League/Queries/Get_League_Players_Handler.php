<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Players_Result;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Query_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use stdClass;
use Throwable;
use function __;
use function is_array;
use function is_numeric;


/**
 * Query handler for [league-players] shortcode.
 */
readonly class Get_League_Players_Handler {
    /**
     * Initialises the service with league, player, statistics, and query dependencies.
     *
     * @param League_Service_Interface       $league_service League service dependency.
     * @param League_Repository_Interface    $league_repository League repository dependency.
     * @param Player_Service_Interface       $player_service Player service dependency.
     * @param Player_Query_Service_Interface $player_query_service Player query service dependency.
     * @param Player_Stats_Service_Interface $player_stats_service Player statistics service dependency.
     * @param League_Query_Service_Interface $league_query_service League query service dependency.
     *
     * @return void
     */
    public function __construct(
        private League_Service_Interface $league_service,
        private League_Repository_Interface $league_repository,
        private Player_Service_Interface $player_service,
        private Player_Query_Service_Interface $player_query_service,
        private Player_Stats_Service_Interface $player_stats_service,
        private League_Query_Service_Interface $league_query_service
    ) {}

    /**
     * Handles a league players query by resolving the league, season, optional player details, and player statistics.
     *
     * @param Get_League_Players_Query $query Query containing the league, season, player, and template criteria.
     *
     * @return League_Players_Result Result containing the resolved league players data, player details, or an error state.
     */
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
                } catch ( Throwable ) { //phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                    // Player not found; proceed without player details.
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
                $p->team = $this->league_repository->get_player_team(
                    $league->get_id(),
                    [
                        'player' => $p->get_id(),
                        'season' => $season_name,
                    ]
                ) ?? new stdClass();
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

    /**
     * Retrieves the league query service dependency.
     *
     * @return League_Query_Service_Interface League query service instance.
     */
    private function league_query(): League_Query_Service_Interface {
        return $this->league_query_service;
    }
}
