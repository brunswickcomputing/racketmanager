<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Query;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Query;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Query;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Query;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Query;
use Racketmanager\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presenters\Matches_List_Presenter;
use function get_query_var;
use function gmdate;
use function is_numeric;
use function Racketmanager\un_seo_url;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

/**
 * Driving adapter for Match and Daily/Results shortcodes.
 *
 * Every tag translates shortcode attributes and request state into a typed query, executes it
 * through a query handler and renders the result through a presenter:
 * - [dailymatches] / [latest_results]: Get_Daily_Matches_Handler / Get_Latest_Results_Handler -> Matches_List_Presenter
 * - [score] / [match-header] / [match-detail]: Get_Match_*_Handler -> Match_Shortcode_Presenter
 */
class Matches_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private readonly Get_Daily_Matches_Handler $daily_matches_handler,
        private readonly Get_Latest_Results_Handler $latest_results_handler,
        private readonly Matches_List_Presenter $presenter,
        private readonly Get_Match_Score_Handler $score_handler,
        private readonly Get_Match_Header_Handler $header_handler,
        private readonly Get_Match_Detail_Handler $detail_handler,
        private readonly Match_Shortcode_Presenter $match_presenter
    ) {}

    /**
     * [dailymatches competition_type="league" template="daily" match_date="Y-m-d"]
     */
    public function show_daily_matches( array|string $atts = [] ): string {
        $args = shortcode_atts(
            [
                'competition_type' => 'league',
                'template'         => 'daily',
                'match_date'       => false,
            ],
            is_array( $atts ) ? $atts : []
        );

        $match_date = $args['match_date'];
        if ( ! $match_date ) {
            $match_date = (string) get_query_var( 'match_date' );
            if ( '' === $match_date && isset( $_GET['match_date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only date filter.
                $match_date = sanitize_text_field( wp_unslash( (string) $_GET['match_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
        }

        $competition_type = (string) $args['competition_type'];
        if ( $this->has_query_var( 'competition_type' ) ) {
            $competition_type = un_seo_url( (string) get_query_var( 'competition_type' ) );
        }

        $query  = new Get_Daily_Matches_Query(
            match_date: (string) $match_date,
            competition_type: $competition_type,
            template: (string) $args['template'],
            today: gmdate( 'Y-m-d' )
        );
        $result = $this->daily_matches_handler->handle( $query );

        return $this->presenter->render( $this->presenter->present_daily( $result, $query->template ) );
    }

    /**
     * [latest_results competition_type="league" template="results" days="7" club="1" competition_id="1" header_level="1" age_group=""]
     */
    public function show_latest_results( array|string $atts = [] ): string {
        $args = shortcode_atts(
            [
                'competition_type' => 'league',
                'template'         => 'results',
                'days'             => 7,
                'club'             => '',
                'competition_id'   => '',
                'header_level'     => 1,
                'age_group'        => false,
            ],
            is_array( $atts ) ? $atts : []
        );

        $club_shortcode = null;
        if ( $this->has_query_var( 'club_name' ) ) {
            $club_shortcode = str_replace( '-', ' ', (string) get_query_var( 'club_name' ) );
        }
        $days = $args['days'];
        if ( $this->has_query_var( 'days' ) ) {
            $days = str_replace( '-', ' ', (string) get_query_var( 'days' ) );
        }
        $competition_type = (string) $args['competition_type'];
        if ( $this->has_query_var( 'competition_type' ) ) {
            $competition_type = un_seo_url( (string) get_query_var( 'competition_type' ) );
        }
        $competition_name = null;
        if ( $this->has_query_var( 'competition_name' ) ) {
            $competition_name = un_seo_url( (string) get_query_var( 'competition_name' ) );
        }
        $age_group = $args['age_group'];
        if ( $this->has_query_var( 'age_group' ) ) {
            $age_group = get_query_var( 'age_group' );
        }

        $query  = new Get_Latest_Results_Query(
            days: (int) $days,
            competition_type: $competition_type,
            club_id: is_numeric( $args['club'] ) ? (int) $args['club'] : null,
            club_shortcode: $club_shortcode,
            competition_id: is_numeric( $args['competition_id'] ) ? (int) $args['competition_id'] : null,
            competition_name: $competition_name,
            age_group: ( false === $age_group || null === $age_group ) ? null : (string) $age_group,
            header_level: (int) $args['header_level'],
            template: (string) $args['template']
        );
        $result = $this->latest_results_handler->handle( $query );

        return $this->presenter->render( $this->presenter->present_latest_results( $result, $query->template, $query->header_level ) );
    }


    /**
     * [score id= team= opponent= home_away= template=]
     */
    public function show_score( array|string $atts = [] ): string {
        $args = shortcode_atts(
            [
                'id'        => 0,
                'team'      => null,
                'opponent'  => null,
                'home_away' => false,
                'template'  => '',
            ],
            is_array( $atts ) ? $atts : []
        );

        $query = new Get_Match_Score_Query(
            match_id: (int) $args['id'],
            team_id: null === $args['team'] ? null : (string) $args['team'],
            opponent_id: null === $args['opponent'] ? null : (string) $args['opponent'],
            home_away: (bool) $args['home_away'],
            template: (string) $args['template']
        );

        return $this->match_presenter->render_score( $this->score_handler->handle( $query ) );
    }

    /**
     * [match-header id= edit= template=]
     */
    public function show_match_header( array|string $atts = [] ): string {
        $args = shortcode_atts(
            [
                'id'       => 0,
                'edit'     => false,
                'template' => '',
            ],
            is_array( $atts ) ? $atts : []
        );

        $query = new Get_Match_Header_Query(
            match_id: (int) $args['id'],
            edit: $args['edit'],
            template: (string) $args['template']
        );

        return $this->match_presenter->render_header( $this->header_handler->handle( $query ) );
    }

    /**
     * [match-detail id= player= template=]
     */
    public function show_match_detail( array|string $atts = [] ): string {
        $args = shortcode_atts(
            [
                'id'       => 0,
                'player'   => null,
                'template' => '',
            ],
            is_array( $atts ) ? $atts : []
        );

        $query = new Get_Match_Detail_Query(
            match_id: (int) $args['id'],
            player_id: is_numeric( $args['player'] ) && (int) $args['player'] > 0 ? (int) $args['player'] : null,
            template: (string) $args['template']
        );

        return $this->match_presenter->render_detail( $this->detail_handler->handle( $query ) );
    }

    private function has_query_var( string $name ): bool {
        global $wp;

        return isset( $wp->query_vars[ $name ] );
    }
}
