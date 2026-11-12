<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Matches_Result;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use function __;
use function array_key_exists;
use function function_exists;
use function get_permalink;
use function get_the_ID;
use function intval;
use function is_array;
use function is_iterable;
use function is_numeric;
use function iterator_to_array;
use function property_exists;
use function wp_verify_nonce;

/**
 * Query handler for [matches] shortcode.
 */
class Get_League_Matches_Handler {
    public function __construct(
        private readonly League_Service_Interface $league_service,
        private readonly League_Query_Service_Interface $league_query_service
    ) {}

    public function handle( Get_League_Matches_Query $query ): League_Matches_Result {
        if ( function_exists( 'wp_verify_nonce' ) ) {
            wp_verify_nonce( 'matches' );
        }

        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return League_Matches_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        // Always disable match day in template to show matches by matchday.
        if ( 'by_matchday' === $query->template || ! empty( $query->time ) || empty( $query->matches ) ) {
            $match_day = -1;
        } else {
            $match_day = is_numeric( $query->matches ) ? (int) $query->matches : $query->matches;
        }

        $this->league_query_service->set_season( $league, $query->season ?? false );
        $this->league_query_service->set_match_day( $league, $match_day );
        $match_args = [ 'final' => '' ];
        if ( 'false' === $query->limit ) {
            $match_args['limit'] = false;
        } elseif ( ! empty( $query->limit ) && is_numeric( $query->limit ) ) {
            $match_args['limit'] = intval( $query->limit );
        }
        $match_args['time']      = $query->time;
        $match_args['home_only'] = false;

        $original_match_args = $league->get_match_query_args();
        if ( array_key_exists( 'limit', $match_args ) ) {
            $league->set_match_query_arg( 'limit', $match_args['limit'] );
        }
        $matches = $this->league_query_service->get_matches( $league, $match_args );
        $matches_state = $this->league_query_service->set_num_matches( $league );
        $league->restore_match_query_args( $original_match_args );

        $competition   = $league->event?->competition ?? null;
        $is_team_entry = $competition?->is_team_entry ?? true;
        $raw_matches   = is_array( $matches ) ? $matches : ( is_iterable( $matches ) ? iterator_to_array( $matches ) : [] );

        $is_grouped      = $is_team_entry && ( -1 === (int) $league->match_day );
        $grouped_matches = [];
        if ( $is_grouped ) {
            foreach ( $raw_matches as $match ) {
                $key = $match->match_day ?? 0;
                if ( ! array_key_exists( $key, $grouped_matches ) ) {
                    $grouped_matches[ $key ] = [];
                }
                $grouped_matches[ $key ][] = $match;
            }
        }

        $teams = $this->league_query_service->get_league_teams( $league, [
            'season'  => $league->current_season['name'] ?? $query->season,
            'orderby' => [ 'title' => 'ASC' ],
        ] );

        $post_id = $query->post_id ?: ( function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0 );

        $settings = is_array( $league->settings ?? null ) ? $league->settings : [];
        $show_match_day_selection = '' === $query->show_match_day_selection
            ? true
            : 'false' !== strtolower( $query->show_match_day_selection );
        $view_state = $matches_state->with(
            [
                'tab'                        => 'matches',
                'templates'                  => [ 'matches' => $query->template ],
                'matches_template_type'      => $query->template_type,
                'is_archive'                 => false,
                'current_match_day'          => is_numeric( $league->match_day ?? null ) ? (int) $league->match_day : ( is_numeric( $match_day ) ? (int) $match_day : 1 ),
                'show_match_day_selection'   => $show_match_day_selection,
                'match_display'              => (string) ( $settings['match_display'] ?? 'current_match_day' ),
            ]
        );

        return new League_Matches_Result(
            league: $league,
            raw_matches: $raw_matches,
            is_team_entry: (bool) $is_team_entry,
            is_grouped: $is_grouped,
            grouped_matches: $grouped_matches,
            teams: is_array( $teams ) ? $teams : [],
            season: $league->current_season['name'] ?? $query->season,
            post_id: $post_id,
            form_action: $post_id > 0 ? (string) get_permalink( $post_id ) : '',
            template: $query->template,
            view_state: $view_state
        );
    }
}
