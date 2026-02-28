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
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Tournament;
use Racketmanager\Util\Util_Lookup;
use Racketmanager\Util\Util_Messages;

readonly final class Tournament_Tournaments_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Competition_Service $competition_service,
        private Season_Service $season_service,
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
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }

        $bulk_result = $this->handle_bulk_delete( $post, $validator );
        $filters     = $this->resolve_filters( $query );

        $age_group_select   = $filters['age_group_select'];
        $season_select      = $filters['season_select'];
        $competition_select = $filters['competition_select'];

        $tournaments = $this->tournament_service->get_tournaments_with_details(
            array(
                'season'         => $season_select,
                'competition_id' => $competition_select,
                'age_group'      => $age_group_select,
                'orderby'        => array(
                    'date' => 'desc',
                    'name' => 'asc',
                ),
            )
        );

        $vm = new Tournament_Tournaments_Page_View_Model(
            tournaments: $tournaments,
            season_select: $season_select,
            competition_select: $competition_select,
            age_group_select: $age_group_select,
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
     * @param Validator_Tournament $validator
     * @return array{message:?string, message_type:bool|string}
     *
     * @throws Invalid_Status_Exception
     */
    private function handle_bulk_delete( array $post, Validator_Tournament $validator ): array {
        $message      = null;
        $message_type = false;

        if ( ! ( isset( $post['doTournamentDel'], $post['action'] ) && 'delete' === strval( $post['action'] ) ) ) {
            return array(
                'message'      => $message,
                'message_type' => $message_type,
            );
        }

        $validator = $validator->capability( 'del_teams' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_tournaments-bulk' );
        }
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }

        $tournament_ids = isset( $post['tournament'] ) ? array_map( 'absint', (array) $post['tournament'] ) : array();
        if ( empty( $tournament_ids ) ) {
            return array(
                'message'      => $message,
                'message_type' => $message_type,
            );
        }

        $messages      = array();
        $message_error = false;

        foreach ( $tournament_ids as $tournament_id ) {
            try {
                $deleted = $this->tournament_service->remove_tournament( $tournament_id );
                $messages[] = $deleted
                    ? Util_Messages::tournament_deleted( $tournament_id )
                    : Util_Messages::tournament_not_deleted( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                $messages[]    = $e->getMessage();
                $message_error = true;
            }
        }

        $message      = implode( '<br>', $messages );
        $message_type = $message_error;

        return array(
            'message'      => $message,
            'message_type' => $message_type,
        );
    }

    /**
     * Resolve filter values from query string.
     *
     * @param array $query
     * @return array{age_group_select:string, season_select:string, competition_select:string|int}
     */
    private function resolve_filters( array $query ): array {
        $age_group_select   = isset( $query['age_group'] ) ? sanitize_text_field( wp_unslash( $query['age_group'] ) ) : '';
        $season_select      = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( $query['season'] ) ) : '';
        $competition_select = isset( $query['competition'] ) ? intval( $query['competition'] ) : '';

        return array(
            'age_group_select'   => $age_group_select,
            'season_select'      => $season_select,
            'competition_select' => $competition_select,
        );
    }
}
