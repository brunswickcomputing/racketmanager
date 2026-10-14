<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function __;
use function esc_url;

class Get_Tournament_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly ?Get_Latest_Tournament_Handler $latest_tournament_handler = null
    ) {}

    public function handle( Get_Tournament_Query $query ): Shortcode_View_Model {
        global $wp;

        $tournament = $query->tournament;
        if ( ! $tournament ) {
            if ( $this->latest_tournament_handler !== null ) {
                return $this->latest_tournament_handler->handle( new Get_Latest_Tournament_Query() );
            }
            $active_tournament = $this->tournament_service->get_active_tournament();
            if ( $active_tournament ) {
                $new_url = '/tournament/' . Util::seo_url( $active_tournament->get_name() ) . '/';
            } else {
                $new_url = '/tournaments/';
            }
            return Shortcode_View_Model::for_html( '<script>location.href = "' . esc_url( $new_url ) . '"</script>' );
        }

        try {
            $tournament_details = $this->tournament_service->get_tournament_with_details( $tournament );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        if ( ! $tournament_details ) {
            return Shortcode_View_Model::for_error( __( 'Tournament not found', 'racketmanager' ) );
        }

        $tournament_args = [
            'age_group' => $tournament_details->competition->age_group,
            'orderby'   => [
                'season'         => 'DESC',
                'competition_id' => 'DESC',
            ],
        ];
        $tournaments = $this->tournament_service->get_tournaments( $tournament_args );

        if ( isset( $wp ) && is_object( $wp ) && method_exists( $wp, 'set_query_var' ) ) {
            $wp->set_query_var( 'season', $tournament_details->tournament->season );
        }

        $tab      = Util_Lookup::get_tab();
        $filename = ( '' !== $query->template ) ? 'tournament-' . $query->template : 'tournament';

        return Shortcode_View_Model::for_template(
            $filename,
            [
                'tournament_details' => $tournament_details,
                'tournaments'        => $tournaments,
                'tab'                => $tab,
            ],
            false
        );
    }
}
