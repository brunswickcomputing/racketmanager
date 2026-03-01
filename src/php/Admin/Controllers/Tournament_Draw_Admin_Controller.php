<?php
/**
 * Tournament Draw Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Tournament;

use function Racketmanager\get_league;

/**
 * Handles draw-page orchestration + tab selection.
 */
readonly final class Tournament_Draw_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Championship_Admin_Service $championship_admin_service,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=draw
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model:Tournament_Draw_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function draw_page( array $query, array $post ): array {
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league'] ) ? intval( $query['league'] ) : null;
        $tab           = isset( $query['league-tab'] ) ? sanitize_text_field( wp_unslash( $query['league-tab'] ) ) : null;

        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $league = get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $result_message = null;
        $result_message_type = null;
        $tab_override = null;

        // Default tab if none supplied
        if ( empty( $tab ) ) {
            $tab = 'finalResults';
        }

        // ---- Controller validation (capability/nonces) ----
        // Notes:
        // - This is intentionally controller-owned.
        // - Service assumes it's safe to execute mutations.

        $dto = new Draw_Action_Request_DTO(
            tournament_id: intval( $tournament_id ?? 0 ),
            league_id: intval( $league_id ?? 0 ),
            season: isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null,
            post: $post
        );

        // 1) league team actions (delete/withdraw)
        if ( isset( $post['action'] ) && in_array( strval( $post['action'] ), array( 'delete', 'withdraw' ), true ) ) {
            $v = new Validator();
            $v = $v->check_security_token( 'racketmanager_nonce', 'racketmanager_teams-bulk' );
            $v = $v->capability( 'del_teams' );
            if ( ! empty( $v->error ) ) {
                throw new Invalid_Status_Exception( $v->msg );
            }

            $action_result = $this->championship_admin_service->handle_league_teams_action( $dto );
            $result_message = $action_result->message;
            $result_message_type = $action_result->message_type;
            $tab_override = 'preliminary';
        }

        // 2) add teams
        if ( isset( $post['action'] ) && 'addTeamsToLeague' === strval( $post['action'] ) ) {
            $v = new Validator();
            $v = $v->check_security_token( 'racketmanager_nonce', 'racketmanager_add-teams-bulk' );
            $v = $v->capability( 'edit_teams' );
            if ( ! empty( $v->error ) ) {
                throw new Invalid_Status_Exception( $v->msg );
            }

            $action_result = $this->championship_admin_service->add_teams_to_league( $dto );
            $result_message = $action_result->message;
            $result_message_type = $action_result->message_type;
            $tab_override = 'preliminary';
        }

        // 3) manage matches
        if ( isset( $post['updateLeague'] ) && 'match' === strval( $post['updateLeague'] ) ) {
            $v = new Validator();
            $v = $v->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-matches' );
            $v = $v->capability( 'edit_matches' );
            if ( ! empty( $v->error ) ) {
                throw new Invalid_Status_Exception( $v->msg );
            }

            $action_result = $this->championship_admin_service->manage_matches_in_league( $dto );
            $result_message = $action_result->message;
            $result_message_type = $action_result->message_type;
            $tab_override = 'matches';
        }

        // 4) rankings
        if ( isset( $post['saveRanking'] ) || isset( $post['randomRanking'] ) || isset( $post['ratingPointsRanking'] ) ) {
            $v = new Validator();
            $v = $v->check_security_token( 'racketmanager_nonce', 'racketmanager_teams-bulk' );
            $v = $v->capability( 'update_results' );
            if ( ! empty( $v->error ) ) {
                throw new Invalid_Status_Exception( $v->msg );
            }

            $mode = isset( $post['saveRanking'] ) ? 'manual' : ( isset( $post['randomRanking'] ) ? 'random' : 'ratings' );
            $action_result = $this->championship_admin_service->rank_teams( $dto, $mode );
            $result_message = $action_result->message;
            $result_message_type = $action_result->message_type;
            $tab_override = 'preliminary';
        }

        // 5) finals actions
        if ( isset( $post['action'] ) && 'startFinals' === strval( $post['action'] ) ) {
            $v = new Validator();
            $v = $v->check_security_token( 'racketmanager_proceed_nonce', 'racketmanager_championship_proceed' );
            $v = $v->capability( 'update_results' );
            if ( ! empty( $v->error ) ) {
                throw new Invalid_Status_Exception( $v->msg );
            }

            $action_result = $this->championship_admin_service->start_finals( $dto );
            $result_message = $action_result->message;
            $result_message_type = $action_result->message_type;
            $tab_override = $action_result->tab_override ?? 'preliminary';
        }

        if ( isset( $post['action'] ) && 'updateFinalResults' === strval( $post['action'] ) ) {
            $v = new Validator();
            $v = $v->check_security_token( 'racketmanager_nonce', 'racketmanager_update-finals' );
            $v = $v->capability( 'update_results' );
            if ( ! empty( $v->error ) ) {
                throw new Invalid_Status_Exception( $v->msg );
            }

            $action_result = $this->championship_admin_service->update_final_results( $dto );
            $result_message = $action_result->message;
            $result_message_type = $action_result->message_type;
        }

        if ( ! empty( $tab_override ) ) {
            $tab = $tab_override;
        }

        $vm = new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: $tab ?: 'finalResults',
        );

        $result = array(
            'view_model' => $vm,
        );

        if ( null !== $result_message ) {
            $result['message'] = $result_message;
            // Bridge expects bool|string currently; map enum here for now.
            $result['message_type'] = match ( $result_message_type ) {
                Admin_Message_Type::ERROR   => true,
                Admin_Message_Type::WARNING => 'warning',
                Admin_Message_Type::INFO    => 'info',
                Admin_Message_Type::SUCCESS => false,
                default => false,
            };
        }

        return $result;
    }
}
