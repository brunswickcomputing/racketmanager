<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
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
        private readonly Shortcode_Template_Renderer $template_renderer,
        private readonly Player_Query_Service $player_query_service,
        private readonly Player_Stats_Service $player_stats_service,
        private readonly Player_Presenter $player_presenter,
        private readonly League_Query_Service $league_query_service
    ) {}

    public function handle( Get_League_Players_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        $this->league_query()->set_season( $league, $query->season ?? false );
        $season_name = $league->current_season['name'] ?? null;
        $player_vm   = null;

        if ( $query->player_id ) {
            $player = null;
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
                return Shortcode_View_Model::for_error( __( 'Player not found', 'racketmanager' ) );
            }

            $player->matches = $this->player_query_service->get_matches( $player, $league, $season_name, 'league' );
            $player->stats   = $this->player_stats_service->get_stats( $player );
            $league->player  = $player;
            $player_vm       = $this->player_presenter->present_player( $player );
        } else {
            $league->players = $this->player_stats_service->populate_players_stats(
                $this->league_query()->get_players( $league ),
                $league,
                $season_name,
                'league'
            );
            foreach ( $league->players as $player ) {
                $player->team = $this->league_repository->get_player_team( $league->get_id(), [ 'player' => $player->get_id(), 'season' => $season_name ] ) ?? new \stdClass();
            }
        }

        $player_links = [];
        if ( ! empty( $league->players ) && is_array( $league->players ) ) {
            $player_links = $this->player_presenter->present_league_player_links( $league, $league->players );
        }

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'players-' . $league->sport, 'league' ) ) {
            $filename = 'players-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'players-' . $query->template : 'players';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league'       => $league,
                'player_vm'    => $player_vm,
                'player_links' => $player_links,
            ],
            'league'
        );
    }

    private function league_query(): League_Query_Service {
        return $this->league_query_service;
    }
}
