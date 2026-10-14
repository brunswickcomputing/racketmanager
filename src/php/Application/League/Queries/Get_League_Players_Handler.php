<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_numeric;
use function method_exists;
use Throwable;

/**
 * Query handler for [league-players] shortcode.
 */
class Get_League_Players_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Player_Service $player_service,
        private readonly Shortcode_Template_Renderer $template_renderer
    ) {}

    public function handle( Get_League_Players_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        $league->set_season( $query->season ?? false );

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

            $season_name     = $league->current_season['name'] ?? null;
            $player->matches = method_exists( $player, 'get_matches' ) ? $player->get_matches( $league, $season_name, 'league' ) : [];
            $player->stats   = method_exists( $player, 'get_stats' ) ? $player->get_stats() : null;
            $league->player  = $player;
        } else {
            $league->players = $league->get_players( [ 'stats' => true ] );
        }

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'players-' . $league->sport, 'league' ) ) {
            $filename = 'players-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'players-' . $query->template : 'players';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league' => $league,
            ],
            'league'
        );
    }
}
