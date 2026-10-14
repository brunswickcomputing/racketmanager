<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use function __;
use function is_array;

/**
 * Query handler for [crosstable] shortcode.
 */
class Get_League_Crosstable_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Shortcode_Template_Renderer $template_renderer
    ) {}

    public function handle( Get_League_Crosstable_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        $league->set_template( 'crosstable', $query->template );
        $league->set_season( $query->season ?? false );
        $league->set_group( $query->group ?? '' );

        $teams = $league->get_league_teams( [ 'orderby' => [ 'rank' => 'ASC' ] ] );

        if ( empty( $query->template ) && ! empty( $league->sport ) && $this->template_renderer->template_exists( 'crosstable-' . $league->sport, 'league' ) ) {
            $filename = 'crosstable-' . $league->sport;
        } else {
            $filename = ( ! empty( $query->template ) ) ? 'crosstable-' . $query->template : 'crosstable';
        }

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'league' => $league,
                'teams'  => is_array( $teams ) ? $teams : [],
                'mode'   => $query->mode,
                'season' => $query->season,
            ],
            'league'
        );
    }
}
