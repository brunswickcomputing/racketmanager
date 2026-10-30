<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Competition\DTOs\Competition_Players_Result;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Util\Util;
use function asort;
use function is_numeric;
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
        private readonly Competition_Season_Service $competition_season_service
    ) {}

    public function handle( Get_Competition_Players_Query $query ): Competition_Players_Result {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return Competition_Players_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = Seo_Slug::decode( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Competition_Players_Result::error( $e->getMessage() );
        }

        if ( ! $competition ) {
            return Competition_Players_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        $this->competition_season_service->resolve_current_season( $competition, (string) ( $query->season ?? '' ) );

        $season_str           = isset( $competition->current_season['name'] ) ? (string) $competition->current_season['name'] : null;
        $competition->players = [];
        $player               = null;

        if ( ! empty( $query->player_identifier ) ) {
            $player_identifier = Seo_Slug::decode( $query->player_identifier );
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
            } else {
                return Competition_Players_Result::error( __( 'Player not found', 'racketmanager' ) );
            }
        } else {
            $players              = $this->player_service->get_players_for_competition( (int) $competition->get_id(), $season_str );
            $competition->players = Util::get_players_list( $players );
        }

        $template = '' !== $query->template ? 'players-' . $query->template : 'players';

        return new Competition_Players_Result(
            competition: $competition,
            player: $player,
            players: $competition->players ?? [],
            raw_matches: $player->matches ?? [],
            template: $template
        );
    }
}
