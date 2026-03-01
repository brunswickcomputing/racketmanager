<?php
/**
 * Tournament Draw Admin Controller (context + view model)
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;

use function Racketmanager\get_league;

readonly final class Tournament_Draw_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
    ) {
    }

    /**
     * Prepare context for admin.php?page=racketmanager-tournaments&view=draw
     *
     * NOTE: This controller intentionally only prepares context + view model.
     * Mutating admin actions (teams/matches/ranking) remain in Admin_Tournament for now.
     *
     * @param array $query Typically $_GET
     * @return array{view_model:Tournament_Draw_Page_View_Model}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function draw_page_context( array $query ): array {
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league'] ) ? intval( $query['league'] ) : null;

        $tab = isset( $query['league-tab'] )
            ? sanitize_text_field( wp_unslash( $query['league-tab'] ) )
            : null;

        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $league = get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        // Default tab if none supplied
        if ( empty( $tab ) ) {
            $tab = 'finalResults';
        }

        return array(
            'view_model' => new Tournament_Draw_Page_View_Model(
                tournament: $tournament,
                league: $league,
                tab: $tab
            ),
        );
    }
}
