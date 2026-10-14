<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function array_key_exists;
use function function_exists;
use function intval;
use function is_array;
use function is_iterable;
use function is_numeric;
use function wp_verify_nonce;

/**
 * Query handler for [matches] shortcode.
 */
class Get_League_Matches_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Shortcode_Template_Renderer $template_renderer
    ) {}

    public function handle( Get_League_Matches_Query $query ): Shortcode_View_Model {
        if ( function_exists( 'wp_verify_nonce' ) ) {
            wp_verify_nonce( 'matches' );
        }

        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        $league->set_template( 'matches', $query->template );

        // Always disable match day in template to show matches by matchday.
        if ( 'by_matchday' === $query->template || ! empty( $query->time ) || empty( $query->matches ) ) {
            $match_day = -1;
        } else {
            $match_day = is_numeric( $query->matches ) ? (int) $query->matches : $query->matches;
        }

        $league->set_season( $query->season ?? false );
        $league->set_match_day( $match_day );
        $league->matches_template_type = $query->template_type;

        $match_args = [ 'final' => '' ];
        if ( 'false' === $query->limit ) {
            $match_args['limit'] = false;
        } elseif ( ! empty( $query->limit ) && is_numeric( $query->limit ) ) {
            $match_args['limit'] = intval( $query->limit );
        }
        $match_args['time']      = $query->time;
        $match_args['home_only'] = false;

        $matches = $league->get_matches( $match_args );
        $league->set_num_matches();

        if ( -1 === (int) $league->match_day ) {
            $league_matches = [];
            if ( is_iterable( $matches ) ) {
                foreach ( $matches as $match ) {
                    $key = $match->match_day ?? 0;
                    if ( ! array_key_exists( $key, $league_matches ) ) {
                        $league_matches[ $key ] = [];
                    }
                    $league_matches[ $key ][] = $match;
                }
            }
        } else {
            $league_matches = is_array( $matches ) ? $matches : [];
        }

        $teams = $league->get_league_teams( [
            'season'  => $query->season,
            'orderby' => [ 'title' => 'ASC' ],
        ] );

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'matches-' . $league->sport ) ) {
            $filename = 'matches-' . $league->sport;
        } elseif ( ! empty( $league->sport ) && $this->template_renderer->template_exists( 'matches-' . $query->template . '-' . $league->sport ) ) {
            $filename = 'matches-' . $query->template . '-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'matches-' . $query->template : 'matches';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league'  => $league,
                'matches' => $league_matches,
                'teams'   => is_array( $teams ) ? $teams : [],
                'season'  => $query->season,
            ]
        );
    }
}
