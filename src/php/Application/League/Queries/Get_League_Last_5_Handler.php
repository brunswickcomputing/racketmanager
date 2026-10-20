<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_array;
use function is_object;

/**
 * Query handler for [last-5] shortcode.
 */
class Get_League_Last_5_Handler {
    /**
     * Cache of team ID -> title maps keyed by league_id and season.
     *
     * @var array<string, array<string, string>>
     */
    private array $team_titles_cache = [];

    public function __construct(
        private readonly League_Service $league_service,
        private readonly Shortcode_Template_Renderer $template_renderer,
        private readonly Fixture_Presenter $fixture_presenter
    ) {}

    public function handle( Get_League_Last_5_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        $league->set_season();

        if ( ! $query->team_id ) {
            return Shortcode_View_Model::for_error( __( 'No team ID found', 'racketmanager' ) );
        }

        $matches = $league->get_matches( [
            'time'             => 'prev',
            'team_id'          => $query->team_id,
            'match_day'        => -1,
            'limit'            => 5,
            'reset_query_args' => true,
        ] );

        $matches_list   = is_array( $matches ) ? $matches : [];
        $match_tooltips = [];
        if ( $matches_list ) {
            $league_id = (string) ( $league->id ?? $query->league_id ?? '' );
            $season    = (string) ( $league->season ?? ( $league->current_season['name'] ?? '' ) );
            $cache_key = $league_id . ':' . $season;

            if ( ! isset( $this->team_titles_cache[ $cache_key ] ) ) {
                $teams = $league->get_league_teams( [ 'reset_query_args' => true ] );
                $team_titles = [];
                foreach ( is_array( $teams ) ? $teams : [] as $team ) {
                    $id    = is_object( $team ) ? ( $team->id ?? null ) : ( $team['id'] ?? null );
                    $title = is_object( $team ) ? ( $team->title ?? null ) : ( $team['title'] ?? null );
                    if ( null !== $id && null !== $title ) {
                        $team_titles[ (string) $id ] = (string) $title;
                    }
                }
                $this->team_titles_cache[ $cache_key ] = $team_titles;
            }

            $team_titles = $this->team_titles_cache[ $cache_key ];

            foreach ( $matches_list as $match ) {
                $match_id = is_object( $match ) ? ( $match->id ?? null ) : ( $match['id'] ?? null );
                if ( null === $match_id ) {
                    continue;
                }
                $home_id = is_object( $match ) ? ( $match->home_team ?? null ) : ( $match['home_team'] ?? null );
                $away_id = is_object( $match ) ? ( $match->away_team ?? null ) : ( $match['away_team'] ?? null );
                $match_tooltips[ $match_id ] = $this->fixture_presenter->present_last_5_tooltip(
                    $match,
                    $team_titles[ (string) $home_id ] ?? null,
                    $team_titles[ (string) $away_id ] ?? null
                );
            }
        }

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'last-5-' . $league->sport, 'league' ) ) {
            $filename = 'last-5-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'last-5-' . $query->template : 'last-5';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'matches'        => $matches_list,
                'team_id'        => $query->team_id,
                'match_tooltips' => $match_tooltips,
            ],
            'league'
        );
    }
}
