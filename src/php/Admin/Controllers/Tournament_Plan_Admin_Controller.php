<?php
/**
 * Tournament Plan Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Config_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Tournament;

final class Tournament_Plan_Admin_Controller {

    public function __construct(
        private readonly Tournament_Service $tournament_service,
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
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }

        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;

        $tab = 'matches';

        // POST: Save plan
        if ( isset( $post['saveTournamentPlan'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_tournament-planner' );
            if ( ! empty( $validator->error ) ) {
                throw new Invalid_Status_Exception( $validator->msg );
            }

            $request              = new Tournament_Finals_Request_DTO( $post );
            $tournament_id_posted = isset( $post['tournamentId'] ) ? intval( $post['tournamentId'] ) : null;

            try {
                $response = $this->tournament_service->save_finals_plan_for_tournament( $tournament_id_posted, $request );
                if ( is_wp_error( $response ) ) {
                    $validator->error    = true;
                    $validator->err_flds = $response->get_error_codes();
                    $validator->err_msgs = $response->get_error_messages();

                    return $this->render( tournament_id: $tournament_id_posted ?? $tournament_id, tab: 'matches', validator: $validator, message: __( 'Error updating tournament finals', 'racketmanager' ), message_type: true );
                }

                $redirect_url = $this->build_plan_url( tournament_id: $tournament_id_posted ?? $tournament_id, flags: array( 'plan_saved' => 1, 'tab' => 'matches' ) );

                return array( 'redirect' => $redirect_url );
            } catch ( Tournament_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
        }

        // POST: Reset plan
        if ( isset( $post['resetTournamentPlan'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_tournament-planner' );
            if ( ! empty( $validator->error ) ) {
                throw new Invalid_Status_Exception( $validator->msg );
            }

            $tournament_id_posted = isset( $post['tournamentId'] ) ? intval( $post['tournamentId'] ) : null;

            try {
                $response = $this->tournament_service->reset_plan_for_tournament( $tournament_id_posted );

                $redirect_url = $this->build_plan_url( tournament_id: $tournament_id_posted ?? $tournament_id, flags: array(
                        'plan_reset' => $response ? 1 : 0,
                        'tab'        => 'matches',
                    ) );

                return array( 'redirect' => $redirect_url );
            } catch ( Tournament_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
        }

        // POST: Save finals config
        if ( isset( $post['saveTournamentFinalsConfig'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_tournament-finals-config' );
            if ( ! empty( $validator->error ) ) {
                throw new Invalid_Status_Exception( $validator->msg );
            }

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
                    tournament_id: $tournament_id_posted, flags: array( 'config_saved' => $response ? 1 : 0, 'tab' => 'config' )
                 );

                 return array( 'redirect' => $redirect_url );
             } catch ( Tournament_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
        }

        // GET: tab selection (optional)
        if ( isset( $query['tab'] ) ) {
            $tab = sanitize_text_field( wp_unslash( $query['tab'] ) );
        }

        // GET: post-redirect messages
        if ( isset( $query['plan_saved'] ) && '1' === strval( $query['plan_saved'] ) ) {
            return $this->render( tournament_id: $tournament_id, tab: $tab, validator: $validator, message: __( 'Tournament finals updated', 'racketmanager' ), message_type: false );
        }
        if ( isset( $query['plan_reset'] ) ) {
            $did_reset = ( '1' === strval( $query['plan_reset'] ) );

            return $this->render( tournament_id: $tournament_id, tab: $tab, validator: $validator, message: $did_reset ? __( 'Plan reset', 'racketmanager' ) : __( 'No plan to reset', 'racketmanager' ), message_type: $did_reset ? false : 'warning' );
        }
        if ( isset( $query['config_saved'] ) ) {
            $did_save = ( '1' === strval( $query['config_saved'] ) );

            return $this->render( tournament_id: $tournament_id, tab: 'config', validator: $validator, message: $did_save ? __( 'Tournament finals config updated', 'racketmanager' ) : __( 'No changes made', 'racketmanager' ), message_type: $did_save ? false : 'warning' );
        }

        return $this->render( tournament_id: $tournament_id, tab: $tab, validator: $validator );
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

        $vm = new Tournament_Plan_Page_View_Model( tournament: $tournament, final_matches: is_array( $final_matches ) ? $final_matches : array(), tab: $tab, order_of_play: array(), validator: $validator );

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
