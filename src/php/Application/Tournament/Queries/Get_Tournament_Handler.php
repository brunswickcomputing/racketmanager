<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Tournament\DTOs\Tournament_Result;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function __;

/**
 * Handles requests for a tournament page and produces the corresponding tournament result.
 *
 * Resolves the requested tournament, redirects to the latest or active tournament when no
 * tournament is supplied, reports lookup errors, and prepares view data for the selected
 * tournament tab. The handler also determines available navigation options, loads related
 * tournament seasons for the same age group, updates query state with the tournament season
 * when supported, and returns all data required to render the tournament template.
 */
readonly class Get_Tournament_Handler {
    /**
     * Initialises the tournament-related dependencies.
     *
     * @param Tournament_Service_Interface       $tournament_service Service used to access tournament data.
     * @param Get_Latest_Tournament_Handler|null $latest_tournament_handler Optional handler for retrieving the latest tournament.
     * @param Query_Vars_Interface|null          $query_vars Optional query variables provider.
     *
     * @return void
     */
    public function __construct(
        private Tournament_Service_Interface $tournament_service,
        private ?Get_Latest_Tournament_Handler $latest_tournament_handler = null,
        private ?Query_Vars_Interface $query_vars = null
    ) {}

    /**
     * Handles a tournament query and builds the tournament result.
     *
     * Resolves missing tournament input to an appropriate redirect, loads tournament details,
     * determines the active tab and menu availability, and returns the populated result object.
     *
     * @param Get_Tournament_Query $query Query containing the requested tournament, tab, filters, template, and post context.
     *
     * @return Tournament_Result Result containing tournament data, redirect information, or an error response.
     */
    public function handle( Get_Tournament_Query $query ): Tournament_Result {
        $tournament = $query->tournament;
        if ( ! $tournament ) {
            if ( null !== $this->latest_tournament_handler ) {
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
        $tournaments     = $this->tournament_service->get_tournaments( $tournament_args );

        $this->query_vars?->set( 'season', $tournament_details->tournament->get_season() );

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
