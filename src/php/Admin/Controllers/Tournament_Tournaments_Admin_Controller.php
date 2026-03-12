<?php
/**
 * Tournament Tournaments Admin Controller (tournaments list)
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Tournaments_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Lookup;

readonly final class Tournament_Tournaments_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Competition_Service $competition_service,
        private Season_Service $season_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments (default list view)
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model:Tournament_Tournaments_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     */
    public function tournaments_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        $bulk_result = $this->handle_bulk_delete( $post );
        $filters     = $this->resolve_filters( $query );

        $vm = new Tournament_Tournaments_Page_View_Model(
            tournaments: $this->tournament_service->get_tournaments_with_details(
                array(
                    'season'         => $filters['season_select'],
                    'competition_id' => $filters['competition_select'],
                    'age_group'      => $filters['age_group_select'],
                    'orderby'        => array(
                        'date_end' => 'desc',
                        'name'     => 'asc',
                    ),
                )
            ),
            season_select: $filters['season_select'],
            competition_select: $filters['competition_select'],
            age_group_select: $filters['age_group_select'],
            seasons: $this->season_service->get_all_seasons(),
            competitions: $this->competition_service->get_tournament_competitions(),
            age_groups: Util_Lookup::get_age_groups(),
        );

        $result = array(
            'view_model' => $vm,
        );

        if ( null !== $bulk_result['message'] ) {
            $result['message']      = $bulk_result['message'];
            $result['message_type'] = $bulk_result['message_type'];
        }

        return $result;
    }

    /**
     * POST: bulk delete tournaments.
     *
     * @param array $post
     * @return array{message:string|null, message_type:bool|string}
     *
     * @throws Invalid_Status_Exception
     */
    private function handle_bulk_delete( array $post ): array {
        if ( ! ( isset( $post['doTournamentDel'], $post['action'] ) && 'delete' === strval( $post['action'] ) ) ) {
            return array(
                'message'      => null,
                'message_type' => false,
            );
        }

        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_tournaments-bulk', 'del_teams' );

        $tournament_ids = array_map( 'absint', (array) ( $post['tournament'] ?? array() ) );
        if ( empty( $tournament_ids ) ) {
            return array(
                'message'      => null,
                'message_type' => false,
            );
        }

        return $this->tournament_service->bulk_remove_tournaments( $tournament_ids );
    }

    /**
     * Resolve filter values from query string.
     *
     * @param array $query
     * @return array{age_group_select:string, season_select:string, competition_select:string|int}
     */
    private function resolve_filters( array $query ): array {
        return array(
            'age_group_select'   => sanitize_text_field( wp_unslash( strval( $query['age_group'] ?? '' ) ) ),
            'season_select'      => sanitize_text_field( wp_unslash( strval( $query['season'] ?? '' ) ) ),
            'competition_select' => isset( $query['competition'] ) ? intval( $query['competition'] ) : '',
        );
    }
}
