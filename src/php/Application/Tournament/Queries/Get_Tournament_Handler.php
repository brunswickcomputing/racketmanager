<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Tournament_Result;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function __;

class Get_Tournament_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly ?Get_Latest_Tournament_Handler $latest_tournament_handler = null
    ) {}

    public function handle( Get_Tournament_Query $query ): Tournament_Result {
        global $wp;

        $tournament = $query->tournament;
        if ( ! $tournament ) {
            if ( $this->latest_tournament_handler !== null ) {
                $latest_result = $this->latest_tournament_handler->handle( new Get_Latest_Tournament_Query() );
                return Tournament_Result::redirect( $latest_result->redirect_url );
            }
            $active_tournament = $this->tournament_service->get_active_tournament();
            if ( $active_tournament ) {
                $new_url = '/tournament/' . Util::seo_url( $active_tournament->get_name() ) . '/';
            } else {
                $new_url = '/tournaments/';
            }
            return Tournament_Result::redirect( $new_url );
        }

        try {
            $tournament_details = $this->tournament_service->get_tournament_with_details( $tournament );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Tournament_Result::error( $e->getMessage() );
        }

        if ( ! $tournament_details ) {
            return Tournament_Result::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        $tournament_args = [
            'age_group' => $tournament_details->competition->age_group,
            'orderby'   => [
                'season'         => 'DESC',
                'competition_id' => 'DESC',
            ],
        ];
        $tournaments = $this->tournament_service->get_tournaments( $tournament_args );

        if ( isset( $wp ) && is_object( $wp ) && method_exists( $wp, 'set_query_var' ) ) {
            $wp->set_query_var( 'season', $tournament_details->tournament->season );
        }

        $tab = $query->tab;
        if ( ! empty( $query->match_day ) || ! empty( $query->team_id ) ) {
            $tab = 'matches';
        }
        if ( empty( $tab ) ) {
            if ( ! empty( $query->event ) ) {
                $tab = 'events';
            } elseif ( ! empty( $query->match_day ) ) {
                $tab = 'matches';
            } else {
                $tab = Util_Lookup::get_tab() ?: 'overview';
            }
        }

        $tournament_end_date        = method_exists( $tournament_details->tournament, 'get_end_date' ) ? $tournament_details->tournament->get_end_date() : ( $tournament_details->tournament->date_end ?? '' );
        $is_order_of_play_available = ! empty( $tournament_end_date ) && gmdate( 'Y-m-d' ) <= $tournament_end_date && ! empty( $tournament_details->tournament->order_of_play );
        $is_winners_available       = ! empty( $tournament_end_date ) && gmdate( 'Y-m-d' ) >= $tournament_end_date;

        $menu_options = [
            'overview'      => [
                'name'        => 'overview',
                'selected'    => 'overview' === $tab,
                'available'   => true,
                'description' => __( 'Overview', 'racketmanager' ),
            ],
            'events'        => [
                'name'        => 'events',
                'selected'    => 'events' === $tab,
                'available'   => true,
                'description' => __( 'Events', 'racketmanager' ),
            ],
            'draws'         => [
                'name'        => 'draws',
                'selected'    => 'draws' === $tab,
                'available'   => true,
                'description' => __( 'Draws', 'racketmanager' ),
            ],
            'matches'       => [
                'name'        => 'matches',
                'selected'    => 'matches' === $tab,
                'available'   => true,
                'description' => __( 'Matches', 'racketmanager' ),
            ],
            'players'       => [
                'name'        => 'players',
                'selected'    => 'players' === $tab,
                'available'   => true,
                'description' => __( 'Players', 'racketmanager' ),
            ],
            'order_of_play' => [
                'name'        => 'order_of_play',
                'selected'    => 'order_of_play' === $tab || 'orderofplay' === $tab,
                'available'   => $is_order_of_play_available,
                'description' => __( 'Order of play', 'racketmanager' ),
            ],
            'winners'       => [
                'name'        => 'winners',
                'selected'    => 'winners' === $tab,
                'available'   => $is_winners_available,
                'description' => __( 'Winners', 'racketmanager' ),
            ],
        ];

        $post_id = $query->post_id ?: ( function_exists( 'get_the_ID' ) && false !== get_the_ID() ? (int) get_the_ID() : '' );

        return new Tournament_Result(
            tournament: $tournament_details->tournament,
            competition: $tournament_details->competition,
            tab: $tab,
            seasons: $tournaments,
            menu_options: $menu_options,
            tournament_details: $tournament_details,
            template: $query->template,
            post_id: $post_id
        );
    }
}
