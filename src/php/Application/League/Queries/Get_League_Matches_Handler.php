<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
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
use function wp_verify_nonce;

/**
 * Query handler for [matches] shortcode.
 */
class Get_League_Matches_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Shortcode_Template_Renderer $template_renderer,
        private readonly Tournament_Presenter $tournament_presenter,
        private readonly League_Query_Service $league_query_service
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

        $this->league_query_service->set_season( $league, $query->season ?? false );
        $this->league_query_service->set_match_day( $league, $match_day );
        $league->matches_template_type = $query->template_type;

        $match_args = [ 'final' => '' ];
        if ( 'false' === $query->limit ) {
            $match_args['limit'] = false;
        } elseif ( ! empty( $query->limit ) && is_numeric( $query->limit ) ) {
            $match_args['limit'] = intval( $query->limit );
        }
        $match_args['time']      = $query->time;
        $match_args['home_only'] = false;

        $matches = $this->league_query_service->get_matches( $league, $match_args );
        $this->league_query_service->set_num_matches( $league );

        $competition   = $league->event?->competition ?? null;
        // Same condition as templates/matches.php, which renders match cards for non team-entry competitions.
        $is_team_entry = $competition?->is_team_entry ?? true;
        $raw_matches   = is_array( $matches ) ? $matches : ( is_iterable( $matches ) ? iterator_to_array( $matches ) : [] );

        if ( ! $is_team_entry ) {
            // League pages have no Tournament context: cards link to the league match page, like the legacy template.
            $league_matches = $this->tournament_presenter->present_match_cards(
                $raw_matches,
                null,
                null,
                '',
                false,
                $this->tournament_presenter->is_tournament_competition( $competition )
            );
        } elseif ( -1 === (int) $league->match_day ) {
            $league_matches = [];
            foreach ( $raw_matches as $match ) {
                $key = $match->match_day ?? 0;
                if ( ! array_key_exists( $key, $league_matches ) ) {
                    $league_matches[ $key ] = [];
                }
                $league_matches[ $key ][] = $match;
            }
            foreach ( $league_matches as $key => $day_matches ) {
                $presented = $this->tournament_presenter->present_match_list( $day_matches, options: [ 'show_header' => false, 'matches_key' => 'match_day' ] );
                $league_matches[ $key ] = new Team_Matches_Group_View_Model(
                    key: (string) $key,
                    header: sprintf( __( 'Match Day %d', 'racketmanager' ), (int) $key ),
                    matches: $presented
                );
            }
        } else {
            $league_matches = $this->tournament_presenter->present_match_list( $raw_matches, options: [ 'show_header' => false ] );
        }

        $teams = $this->league_query_service->get_league_teams( $league, [
            'season'  => $league->current_season['name'] ?? $query->season,
            'orderby' => [ 'title' => 'ASC' ],
        ] );

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'matches-' . $league->sport ) ) {
            $filename = 'matches-' . $league->sport;
        } elseif ( ! empty( $league->sport ) && $this->template_renderer->template_exists( 'matches-' . $query->template . '-' . $league->sport ) ) {
            $filename = 'matches-' . $query->template . '-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'matches-' . $query->template : 'matches';
        }

        $post_id = $query->post_id ?: ( function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0 );

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league'      => $league,
                'matches'     => $league_matches,
                'teams'       => is_array( $teams ) ? $teams : [],
                'season'      => $league->current_season['name'] ?? $query->season,
                'post_id'     => $post_id,
                'form_action' => $post_id > 0 ? (string) get_permalink( $post_id ) : '',
            ]
        );
    }
}
