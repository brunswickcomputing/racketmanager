<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;

/**
 * Query handler for [season-dropdown] shortcode.
 */
class Get_League_Season_Dropdown_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Shortcode_Template_Renderer $template_renderer
    ) {}

    public function handle( Get_League_Season_Dropdown_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'season-dropdown-' . $league->sport, 'league' ) ) {
            $filename = 'season-dropdown-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'season-dropdown-' . $query->template : 'season-dropdown';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league' => $league,
                'season' => $query->season,
            ],
            'league'
        );
    }
}
