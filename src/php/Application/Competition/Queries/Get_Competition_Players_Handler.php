<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
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
        private readonly Player_Service $player_service
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

        $season_str           = $competition->current_season['name'] ?? null;
        $competition->players = [];

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
                $player->matches = $player->get_matches( $competition, $season_str, 'competition' );
                asort( $player->matches );
                $player->stats       = $player->get_stats();
                $competition->player = $player;
            } else {
                return Shortcode_View_Model::error( __( 'Player not found', 'racketmanager' ) );
            }
        } else {
            $players              = $this->player_service->get_players_for_competition( (int) $competition->get_id(), $season_str );
            $competition->players = Util::get_players_list( $players );
        }

        $template = '' !== $query->template ? 'players-' . $query->template : 'players';

        return new Shortcode_View_Model(
            template: $template,
            vars: [
                'competition' => $competition,
            ],
            template_type: 'competition'
        );
    }
}
