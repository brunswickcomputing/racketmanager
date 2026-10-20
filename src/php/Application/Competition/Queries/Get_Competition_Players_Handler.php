<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Util\Util;
use function asort;
use function is_numeric;
use function Racketmanager\un_seo_url;
use function __;

/**
 * Handler for resolving [competition-players] shortcode data into a view model.
 */
class Get_Competition_Players_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Player_Service $player_service,
        private readonly Player_Query_Service $player_query_service,
        private readonly Player_Stats_Service $player_stats_service,
        private readonly Player_Presenter $player_presenter,
        private readonly Competition_Shortcode_Presenter $competition_presenter
    ) {}

    public function handle( Get_Competition_Players_Query $query ): Shortcode_View_Model {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = un_seo_url( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Shortcode_View_Model::error( $e->getMessage() );
        }

        if ( ! $competition ) {
            return Shortcode_View_Model::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( null !== $query->season ) {
            $competition->set_current_season( (string) $query->season );
        }

        $season_str           = isset( $competition->current_season['name'] ) ? (string) $competition->current_season['name'] : null;
        $competition->players = [];
        $player_vm            = null;
        $match_group_links    = [];
        $player_links         = [];
        $player_link          = null;

        if ( ! empty( $query->player_identifier ) ) {
            $player_identifier = un_seo_url( $query->player_identifier );
            try {
                if ( is_numeric( $player_identifier ) ) {
                    $player = $this->player_service->get_player( (int) $player_identifier );
                } else {
                    $player = $this->player_service->get_player_by_name( $player_identifier );
                }
            } catch ( Player_Not_Found_Exception ) {
                $player = null;
            }

            if ( $player ) {
                $player->matches     = $this->player_query_service->get_matches( $player, $competition, $season_str, 'competition' );
                asort( $player->matches );
                $player->stats       = $this->player_stats_service->get_stats( $player );
                $competition->player = $player;
                $player_vm           = $this->player_presenter->present_player( $player );
                $match_group_links   = $this->competition_presenter->present_player_match_group_links( $player->matches ?? [] );
            } else {
                return Shortcode_View_Model::error( __( 'Player not found', 'racketmanager' ) );
            }
        } else {
            $players              = $this->player_service->get_players_for_competition( (int) $competition->get_id(), $season_str );
            $competition->players = Util::get_players_list( $players );
            $player_links         = $this->competition_presenter->present_competition_players_links( $competition, $competition->players ?? [] );
            $player_link          = $this->competition_presenter->get_competition_player_base_url( $competition );
        }

        $template = '' !== $query->template ? 'players-' . $query->template : 'players';

        return new Shortcode_View_Model(
            template: $template,
            vars: [
                'competition'        => $competition,
                'player_vm'          => $player_vm,
                'match_group_links'  => $match_group_links,
                'player_links'       => $player_links,
                'player_link'        => $player_link,
            ],
            template_type: 'competition'
        );
    }
}
