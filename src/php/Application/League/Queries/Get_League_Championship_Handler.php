<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use stdClass;
use function __;
use function array_reverse;
use function ceil;
use function count;
use function is_iterable;
use function is_object;
use function max;
use function sprintf;

/**
 * Query handler for [championship] shortcode.
 */
class Get_League_Championship_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Fixture_Repository_Interface $fixture_repository,
        private readonly Competition_Service $competition_service,
        private readonly Team_Service $team_service,
        private readonly Fixture_Link_Service $fixture_link_service,
        private readonly Fixture_Permission_Service $fixture_permission_service,
        private readonly Shortcode_Template_Renderer $template_renderer,
        private readonly Rubber_Repository_Interface $rubber_repository,
        private readonly Tournament_Presenter $tournament_presenter,
        private readonly League_Query_Service $league_query_service
    ) {}

    public function handle( Get_League_Championship_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        $league->set_template( 'championship', $query->template );
        $this->league_query_service->set_season( $league, $query->season ?? $league->get_season() );
        $season = $league->get_season();
        $league->season = $season;

        $finals = [];
        if ( isset( $league->championship ) && is_object( $league->championship ) && method_exists( $league->championship, 'get_finals' ) ) {
            $num_first_round = (int) ( $league->championship->num_teams_first_round ?? 0 );
            $raw_finals      = $league->championship->get_finals();
            if ( is_array( $raw_finals ) ) {
                foreach ( array_reverse( $raw_finals ) as $f => $final ) {
                    $data              = new stdClass();
                    $data->key         = $final['key'] ?? '';
                    $data->name        = $final['name'] ?? '';
                    $data->num_matches = (int) ( $final['num_matches'] ?? 0 );
                    $data->round       = $final['round'] ?? '';
                    $num_matches       = max( 1, $data->num_matches );
                    $data->rowspan     = ( $num_first_round / 2 >= 4 ) ? (int) ceil( 4 / $num_matches ) : (int) ceil( ( $num_first_round / 2 ) / $num_matches );

                    $matches_raw = $this->league_query_service->get_matches( $league, [
                        'final'   => $final['key'] ?? '',
                        'orderby' => [ 'id' => 'ASC' ],
                    ] );

                    $matches = [];
                    $i       = 1;
                    if ( is_iterable( $matches_raw ) ) {
                        foreach ( $matches_raw as $match_item ) {
                            $match_id = is_object( $match_item ) ? (int) ( $match_item->id ?? 0 ) : (int) $match_item;
                            $fixture  = $this->fixture_repository->find_by_id( $match_id );
                            if ( ! $fixture ) {
                                continue;
                            }

                            if ( null === $fixture->rubbers && ( $league->num_rubbers ?? 0 ) > 0 ) {
                                $fixture->set_rubbers( $this->rubber_repository->find_by_fixture_id( $match_id ) );
                            }

                            if ( $this->tournament_presenter->is_tournament_competition( $league->event?->competition ?? null ) ) {
                                $matches[ $i ] = $this->tournament_presenter->present_match_card( $fixture, null, null, 'list', false, true );
                                ++$i;
                                continue;
                            }

                            $match             = Fixture_Presentation_Adapter::from_fixture(
                                $fixture,
                                $this->competition_service,
                                $this->team_service,
                                $this->fixture_link_service,
                                $this->fixture_permission_service
                            );
                            $home_title        = $match->teams['home']->title ?? '';
                            $away_title        = $match->teams['away']->title ?? '';
                            $match->title      = sprintf( '%s - %s', $home_title, $away_title );
                            $match->home_title = $home_title;
                            $match->away_title = $away_title;

                            $match->num_rubbers = $league->num_rubbers ?? null;
                            $match->num_sets    = $league->num_sets ?? null;

                            if ( isset( $match->num_rubbers ) && $match->num_rubbers > 0 ) {
                                $rubbers = $match->get_rubbers();
                                $r       = 1;
                                if ( is_iterable( $rubbers ) ) {
                                    foreach ( $rubbers as $rubber ) {
                                        $match->rubbers[ $r ] = $rubber;
                                        ++$r;
                                    }
                                }
                            }
                            if ( empty( $match->location ) ) {
                                $match->location = '';
                            }
                            $matches[ $i ] = $match;
                            ++$i;
                        }
                    }

                    if ( count( $matches ) > 0 ) {
                        $data->matches = $this->tournament_presenter->present_match_list(
                            $matches,
                            $this->tournament_presenter->is_tournament_competition( $league->event?->competition ?? null ),
                            [ 'show_header' => false ]
                        );
                        $finals[ $f ]  = $data;
                    }
                }
            }
        }

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'championship-' . $league->sport ) ) {
            $filename = 'championship-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'championship-' . $query->template : 'championship';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league' => $league,
                'finals' => $finals,
            ],
            false
        );
    }
}
