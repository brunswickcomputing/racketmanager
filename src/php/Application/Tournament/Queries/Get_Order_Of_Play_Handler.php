<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;
use function __;
use function get_option;
use function in_array;
use function is_numeric;
use function ksort;
use function mysql2date;
use function sort;

class Get_Order_Of_Play_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Competition_Repository $competition_repository,
        private readonly Fixture_Repository $fixture_repository,
        private readonly Competition_Service $competition_service,
        private readonly Team_Service $team_service
    ) {}

    public function handle( Get_Order_Of_Play_Query $query ): Shortcode_View_Model {
        $tournament_id  = $query->tournament_id;
        $competition_id = $query->competition_id;
        $season         = $query->season;
        $finals_order   = [];

        if ( $tournament_id ) {
            try {
                $tournament   = $this->tournament_service->get_tournament( $tournament_id );
                $finals_order = $tournament->get_order_of_play();
            } catch ( Tournament_Not_Found_Exception $e ) {
                return Shortcode_View_Model::for_error( $e->getMessage() );
            }
        } elseif ( $competition_id ) {
            $competition = $this->competition_repository->find_by_id( (int) $competition_id );
            if ( $competition ) {
                if ( $season ) {
                    $competition_season = $competition->get_season_by_name( $season );
                    if ( $competition_season ) {
                        $finals_order = $competition_season['orderofplay'] ?? [];
                    } else {
                        return Shortcode_View_Model::for_error( __( 'Season not found for competition', 'racketmanager' ) );
                    }
                } else {
                    return Shortcode_View_Model::for_error( __( 'Season not found', 'racketmanager' ) );
                }
            } else {
                return Shortcode_View_Model::for_error( __( 'Competition not found', 'racketmanager' ) );
            }
        } else {
            return Shortcode_View_Model::for_error( __( 'Competition not found', 'racketmanager' ) );
        }

        $order_of_play = [];
        $times         = [];
        $courts        = [];

        foreach ( $finals_order as $final_courts ) {
            $court            = $final_courts['court'];
            $courts[ $court ] = [];
            foreach ( $final_courts['matches'] as $match_id ) {
                if ( $match_id ) {
                    $match = $this->fixture_repository->find_by_id( (int) $match_id );
                    if ( $match ) {
                        $league    = $match->get_league_id() ? $this->competition_service->get_league_repository()->find_by_id( (int) $match->get_league_id() ) : null;
                        $home_val  = $match->get_home_team();
                        $away_val  = $match->get_away_team();
                        $home_dtls = is_numeric( $home_val ) ? $this->team_service->get_team_details( (int) $home_val ) : ( ! empty( $home_val ) ? $this->team_service->derive_team_details( (string) $home_val ) : null );
                        $away_dtls = is_numeric( $away_val ) ? $this->team_service->get_team_details( (int) $away_val ) : ( ! empty( $away_val ) ? $this->team_service->derive_team_details( (string) $away_val ) : null );

                        $final_match             = new stdClass();
                        $final_match->id         = $match_id;
                        $final_match->time       = $match->get_date() ? (string) mysql2date( get_option( 'time_format', 'H:i' ), $match->get_date() ) : '';
                        $final_match->league     = $league ? $league->get_name() : '';
                        $final_match->location   = $match->get_location() ?? '';
                        $final_match->winner     = $match->get_winner_id();
                        $final_match->home_team   = $home_val;
                        $final_match->away_team   = $away_val;
                        $final_match->home_title = $home_dtls && $home_dtls->team ? $home_dtls->team->get_name() : (string) $home_val;
                        $final_match->away_title = $away_dtls && $away_dtls->team ? $away_dtls->team->get_name() : (string) $away_val;
                        $time                    = $final_match->time;
                        if ( ! in_array( $time, $times, true ) ) {
                            $times[] = $time;
                        }
                        $order_of_play['match_time'][ $time ][ $court ] = $final_match;
                        $courts[ $court ][ $time ][]                     = $final_match;
                    }
                }
            }
        }

        sort( $times );
        foreach ( $times as $time ) {
            foreach ( $courts as $court => $court_matches ) {
                if ( ! isset( $court_matches[ $time ] ) ) {
                    $courts[ $court ][ $time ] = [];
                }
                ksort( $courts[ $court ] );
            }
        }
        $order_of_play['courts'] = $courts;
        $order_of_play['times']  = $times;

        $filename = ( '' !== $query->template ) ? 'orderofplay-' . $query->template : 'orderofplay';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'order_of_play' => $order_of_play,
            ],
            false
        );
    }
}
