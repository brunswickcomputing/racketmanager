<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_array;

/**
 * Query handler for [last-5] shortcode.
 */
class Get_League_Last_5_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Shortcode_Template_Renderer $template_renderer
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

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'last-5-' . $league->sport, 'league' ) ) {
            $filename = 'last-5-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'last-5-' . $query->template : 'last-5';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'matches' => is_array( $matches ) ? $matches : [],
                'team_id' => $query->team_id,
            ],
            'league'
        );
    }
}
