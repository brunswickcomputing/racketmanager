<?php
/**
 * Tournament Plan Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Error_Bag_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Config_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Tournament;

readonly final class Tournament_Plan_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=plan
     *
     * @param array $query Typically $_GET
     * @param array $post Typically $_POST
     *
     * @return array{redirect?:string, view_model?:Tournament_Plan_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function plan_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $validator = new Validator_Tournament(); // Kept for field-level validation + error mapping.
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $tab           = ( isset( $query['tab'] ) ) ? sanitize_text_field( wp_unslash( $query['tab'] ) ) : 'matches';

        if ( isset( $post['saveTournamentPlan'] ) ) {
            $result = $this->handle_save_tournament_plan( $post, $validator, $tournament_id );
        } elseif ( isset( $post['resetTournamentPlan'] ) ) {
            $result = $this->handle_reset_tournament_plan( $post, $validator, $tournament_id );
        } elseif ( isset( $post['saveTournamentFinalsConfig'] ) ) {
            $result = $this->handle_save_tournament_finals_config( $post, $validator, $tournament_id );
        } else {
            $result = $this->handle_get( $query, $validator, $tournament_id, $tab );
        }

        return $result;
    }

    /**
     * Handle GET rendering and post-redirect messages.
     */
    private function handle_get( array $query, Validator_Tournament $validator, ?int $tournament_id, string $tab ): array {
        $message      = null;
        $message_type = false;

        if ( isset( $query['plan_saved'] ) && '1' === strval( $query['plan_saved'] ) ) {
            $message = __( 'Tournament finals updated', 'racketmanager' );
        } elseif ( isset( $query['plan_reset'] ) ) {
            $did_reset    = ( '1' === strval( $query['plan_reset'] ) );
            $message      = $did_reset ? __( 'Plan reset', 'racketmanager' ) : __( 'No plan to reset', 'racketmanager' );
            $message_type = $did_reset ? false : 'warning';
        } elseif ( isset( $query['config_saved'] ) ) {
            $did_save     = ( '1' === strval( $query['config_saved'] ) );
            $message      = $did_save ? __( 'Tournament finals config updated', 'racketmanager' ) : __( 'No changes made', 'racketmanager' );
            $message_type = $did_save ? false : 'warning';
            $tab          = 'config';
        }

        return $this->render(
            tournament_id: $tournament_id,
            tab: $tab,
            validator: $validator,
            message: $message,
            message_type: $message_type
        );
    }

    /**
     * POST handler: Save plan
     */
    private function handle_save_tournament_plan( array $post, Validator_Tournament $validator, ?int $tournament_id ): array {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_tournament-planner', 'edit_teams' );

        $request              = new Tournament_Finals_Request_DTO( $post );
        $tournament_id_posted = isset( $post['tournamentId'] ) ? intval( $post['tournamentId'] ) : null;

        try {
            $response = $this->tournament_service->save_finals_plan_for_tournament( $tournament_id_posted, $request );
            if ( is_wp_error( $response ) ) {
                $validator->error    = true;
                $validator->err_flds = $response->get_error_codes();
                $validator->err_msgs = $response->get_error_messages();

                return $this->render(
                    tournament_id: $tournament_id_posted ?? $tournament_id,
                    tab: 'matches',
                    validator: $validator,
                    message: __( 'Error updating tournament finals', 'racketmanager' ),
                    message_type: true
                );
            }

            $redirect_url = $this->build_plan_url(
                tournament_id: $tournament_id_posted ?? $tournament_id,
                flags: array(
                    'plan_saved' => 1,
                    'tab'        => 'matches',
                )
            );

            return array( 'redirect' => $redirect_url );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
    }

    /**
     * POST handler: Reset plan
     */
    private function handle_reset_tournament_plan( array $post, Validator_Tournament $validator, ?int $tournament_id ): array {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_tournament-planner', 'edit_teams' );

        $tournament_id_posted = isset( $post['tournamentId'] ) ? intval( $post['tournamentId'] ) : null;

        try {
            $response = $this->tournament_service->reset_plan_for_tournament( $tournament_id_posted );

            $redirect_url = $this->build_plan_url(
                tournament_id: $tournament_id_posted ?? $tournament_id,
                flags: array(
                    'plan_reset' => $response ? 1 : 0,
                    'tab'        => 'matches',
                )
            );

            return array( 'redirect' => $redirect_url );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
    }

    /**
     * POST handler: Save finals config
     */
    private function handle_save_tournament_finals_config( array $post, Validator_Tournament $validator, ?int $tournament_id ): array {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_tournament-finals-config', 'edit_teams' );

        $tournament_id_posted = null;
        if ( isset( $post['tournamentId'] ) ) {
            $tournament_id_posted = intval( $post['tournamentId'] );
        } elseif ( isset( $post['tournament_id'] ) ) {
            $tournament_id_posted = intval( $post['tournament_id'] );
        }

        if ( empty( $tournament_id_posted ) ) {
            return $this->render(
                tournament_id: $tournament_id,
                tab: 'config',
                validator: $validator,
                message: __( 'Invalid request', 'racketmanager' ),
                message_type: true
            );
        }

        $config = new Tournament_Finals_Config_Request_DTO( $post );

        try {
            $response = $this->tournament_service->set_finals_config_for_tournament( $tournament_id_posted, $config );
            if ( is_wp_error( $response ) ) {
                $validator->error    = true;
                $validator->err_flds = $response->get_error_codes();
                $validator->err_msgs = $response->get_error_messages();

                return $this->render(
                    tournament_id: $tournament_id_posted,
                    tab: 'config',
                    validator: $validator,
                    message: __( 'Error updating tournament finals config', 'racketmanager' ),
                    message_type: true
                );
            }

            $redirect_url = $this->build_plan_url(
                tournament_id: $tournament_id_posted,
                flags: array(
                    'config_saved' => $response ? 1 : 0,
                    'tab'          => 'config',
                )
            );

            return array( 'redirect' => $redirect_url );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
    }

    /**
     * @param int|null $tournament_id
     * @param string $tab
     * @param Validator_Tournament $validator
     * @param string|null $message
     * @param bool|string $message_type
     *
     * @return array{view_model:Tournament_Plan_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function render(
        ?int $tournament_id, string $tab, Validator_Tournament $validator, ?string $message = null, bool|string $message_type = false
    ): array {
        try {
            $tournament_details = $this->tournament_service->get_tournament_with_details( $tournament_id );
            $tournament         = $tournament_details->tournament;
            $final_matches      = $this->tournament_service->get_finals_matches_for_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $errors = Admin_Error_Bag_Mapper::from_validator( $validator );

        $vm = new Tournament_Plan_Page_View_Model(
            tournament: $tournament,
            final_matches: $final_matches,
            tab: $tab,
            order_of_play: array(),
            errors: $errors,
            validator: $validator
        );

        $result = array(
            'view_model' => $vm,
        );

        if ( null !== $message ) {
            $result['message']      = $message;
            $result['message_type'] = $message_type;
        }

        return $result;
    }

    /**
     * @param int|null $tournament_id
     * @param array<string,int|string> $flags
     *
     * @return string
     */
    private function build_plan_url( ?int $tournament_id, array $flags = array() ): string {
        $args = array_merge( array(
                'page'       => 'racketmanager-tournaments',
                'view'       => 'plan',
                'tournament' => $tournament_id,
            ), $flags );

        return admin_url( 'admin.php?' . http_build_query( $args ) );
    }
}
