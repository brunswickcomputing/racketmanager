<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_array;

/**
 * Query handler for [match-dropdown] shortcode.
 */
class Get_League_Match_Dropdown_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Shortcode_Template_Renderer $template_renderer
    ) {}

    public function handle( Get_League_Match_Dropdown_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        if ( ! $query->season ) {
            return Shortcode_View_Model::for_error( __( 'Season not found', 'racketmanager' ) );
        }

        $matches = $league->get_matches( [
            'limit'            => false,
            'match_day'        => -1,
            'season'           => $query->season,
            'reset_query_args' => true,
        ] );

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'match-dropdown-' . $league->sport, 'league' ) ) {
            $filename = 'match-dropdown-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'match-dropdown-' . $query->template : 'match-dropdown';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'matches'  => is_array( $matches ) ? $matches : [],
                'match_id' => $query->match_id,
            ],
            'league'
        );
    }
}
