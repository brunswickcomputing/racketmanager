<?php
declare( strict_types=1 );

/**
 * Order of Play Presenter
 *
 * @package RacketManager
 * @subpackage Presentation/Presenters
 */

namespace Racketmanager\Presentation\Presenters;

use DateTimeImmutable;
use Exception;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Presentation\View_Models\Order_Of_Play_Match_View_Model;
use Racketmanager\Presentation\View_Models\Order_Of_Play_View_Model;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use function count;
use function floor;
use function in_array;
use function is_numeric;
use function strcmp;
use function usort;

final readonly class Order_Of_Play_Presenter {

    public function __construct(
        private Fixture_Repository_Interface $fixture_repository,
        private Competition_Service $competition_service,
        private Team_Service $team_service,
        private ?Option_Gateway_Interface $option_gateway = null,
    ) {
    }

    /**
     * Build the structured order of play grid view model collection.
     *
     * @param array $finals_order List of court schedule arrays.
     * @param string|null $time_format Optional time format override.
     *
     * @return Order_Of_Play_View_Model
     */
    public function present( array $finals_order, ?string $time_format = null ): Order_Of_Play_View_Model {
        $format = $time_format ?? ( $this->option_gateway ? (string) $this->option_gateway->get_option( 'time_format', 'H:i' ) : 'H:i' );

        $match_time_grid = array();
        $times           = array();
        $time_sort_keys  = array();
        $courts          = array();

        foreach ( $finals_order as $final_courts ) {
            $court            = $final_courts['court'] ?? '';
            $courts[ $court ] = array();
            $matches          = $final_courts['matches'] ?? $final_courts['fixtures'] ?? array();
            foreach ( $matches as $match_id ) {
                if ( ! $match_id ) {
                    continue;
                }

                $match = $this->fixture_repository->find_by_id( (int) $match_id );
                if ( ! $match ) {
                    continue;
                }

                $league    = $match->get_league_id() ? $this->competition_service->get_league_repository()->find_by_id( (int) $match->get_league_id() ) : null;
                $home_val  = $match->get_home_team();
                $away_val  = $match->get_away_team();
                $home_dtls = is_numeric( $home_val ) ? $this->team_service->get_team_details( (int) $home_val ) : ( ! empty( $home_val ) ? $this->team_service->derive_team_details( (string) $home_val ) : null );
                $away_dtls = is_numeric( $away_val ) ? $this->team_service->get_team_details( (int) $away_val ) : ( ! empty( $away_val ) ? $this->team_service->derive_team_details( (string) $away_val ) : null );

                $match_time = '';
                $time_sort_key = '99:99:99';
                if ( $match->get_date() ) {
                    try {
                        $dt         = new DateTimeImmutable( $match->get_date() );
                        $match_time = $dt->format( $format );
                        $time_sort_key = $dt->format( 'H:i:s' );
                    } catch ( Exception ) {
                    }
                }

                $is_home_pending = ! is_numeric( $home_val );
                $is_away_pending = ! is_numeric( $away_val );
                $home_title      = $home_dtls && $home_dtls->team ? $home_dtls->team->get_name() : (string) $home_val;
                $away_title      = $away_dtls && $away_dtls->team ? $away_dtls->team->get_name() : (string) $away_val;
                $league_title    = $league ? $league->get_name() : '';

                $final_match = new Order_Of_Play_Match_View_Model(
                    id: (int) $match_id,
                    league_title: $league_title,
                    home_match_title: $home_title,
                    away_match_title: $away_title,
                    is_home_pending: $is_home_pending,
                    is_away_pending: $is_away_pending,
                    time: $match_time,
                    location: $match->get_location() ?? '',
                    winner: $match->get_winner_id(),
                    home_team: $home_val,
                    away_team: $away_val,
                );

                $time = $match_time;
                if ( ! in_array( $time, $times, true ) ) {
                    $times[] = $time;
                }
                if ( ! isset( $time_sort_keys[ $time ] ) || strcmp( $time_sort_key, $time_sort_keys[ $time ] ) < 0 ) {
                    $time_sort_keys[ $time ] = $time_sort_key;
                }
                $match_time_grid[ $time ][ $court ] = $final_match;
                $courts[ $court ][ $time ][]         = $final_match;
            }
        }

        usort(
            $times,
            static function ( string $first, string $second ) use ( $time_sort_keys ): int {
                $sort_order = strcmp( $time_sort_keys[ $first ] ?? '99:99:99', $time_sort_keys[ $second ] ?? '99:99:99' );
                return 0 !== $sort_order ? $sort_order : strcmp( $first, $second );
            }
        );
        foreach ( $times as $time ) {
            foreach ( $courts as $court => $court_matches ) {
                if ( ! isset( $court_matches[ $time ] ) ) {
                    $courts[ $court ][ $time ] = array();
                }
            }
        }
        foreach ( $courts as $court => $court_matches ) {
            $ordered_court_matches = array();
            foreach ( $times as $time ) {
                $ordered_court_matches[ $time ] = $court_matches[ $time ] ?? array();
            }
            $courts[ $court ] = $ordered_court_matches;
        }

        $num_courts  = count( $courts );
        $col_width   = $num_courts > 0 ? (int) floor( 12 / $num_courts ) : 12;
        $is_expanded = ( 2 === $col_width );

        return new Order_Of_Play_View_Model(
            courts: $courts,
            times: $times,
            col_width: $col_width,
            is_expanded: $is_expanded,
            match_time: $match_time_grid,
        );
    }
}
