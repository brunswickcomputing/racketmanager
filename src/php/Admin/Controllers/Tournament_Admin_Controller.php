<?php
/**
 * Tournament Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Modify_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Tournament_Action_Result_DTO;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Action_Dispatcher;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Tournament;
use stdClass;
use WP_Error;

final readonly class Tournament_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Club_Service $club_service,
        private Competition_Service $competition_service,
        private Season_Service $season_service,
        private Tournament_Action_Dispatcher $dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=modify
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     *
     * @return array{redirect?:string, view_model?:Tournament_Modify_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    public function modify_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $is_post       = ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) );

        if ( $is_post ) {
            return $this->handle_post( $query, $post, $tournament_id );
        }

        return $this->handle_get( $query, $tournament_id );
    }

    /**
     * @param array $query
     * @param array $post
     * @param int|null $tournament_id
     *
     * @return array{redirect?:string, view_model?:Tournament_Modify_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_post( array $query, array $post, ?int $tournament_id ): array {
        $action_result = $this->dispatcher->handle( $tournament_id, $post );

        if ( Tournament_Action_Result_DTO::INTENT_NONE === $action_result->intent ) {
            return $this->handle_get( $query, $tournament_id );
        }

        if ( Admin_Message_Type::ERROR === $action_result->message_type ) {
            return $this->handle_post_error( $query, $tournament_id, $action_result );
        }

        return $this->handle_post_redirect( $query, $post, $tournament_id, $action_result );
    }

    /**
     * @param array $query
     * @param int|null $tournament_id
     * @param Tournament_Action_Result_DTO $action_result
     *
     * @return array{view_model:Tournament_Modify_Page_View_Model, message:string, message_type:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_post_error( array $query, ?int $tournament_id, Tournament_Action_Result_DTO $action_result ): array {
        $result = $this->handle_get( $query, $tournament_id );

        $result['message']      = strval( $action_result->message );
        $result['message_type'] = Admin_Message_Mapper::to_legacy( $action_result->message_type );

        if ( is_wp_error( $action_result->raw_error ) ) {
            $result['view_model'] = $this->apply_error_to_view_model(
                $result['view_model'],
                $action_result->raw_error
            );
        }

        return $result;
    }

    /**
     * @param array $query
     * @param array $post
     * @param int|null $tournament_id
     * @param Tournament_Action_Result_DTO $action_result
     *
     * @return array{redirect:string, message?:string, message_type?:bool|string}
     */
    private function handle_post_redirect( array $query, array $post, ?int $tournament_id, Tournament_Action_Result_DTO $action_result ): array {
        $flags = array();

        if ( null === $action_result->message_type ) {
            if ( Tournament_Action_Result_DTO::INTENT_ADD === $action_result->intent ) {
                $flags['added'] = 1;
            } elseif ( Tournament_Action_Result_DTO::INTENT_EDIT === $action_result->intent ) {
                $flags['updated'] = 1;
            }
        }

        $result = array(
            'redirect' => Admin_Redirect_Url_Builder::tournament_modify(
                $query,
                $post,
                $action_result->tournament_id ?? $tournament_id,
                $flags
            ),
        );

        if ( null !== $action_result->message ) {
            $result['message']      = $action_result->message;
            $result['message_type'] = null !== $action_result->message_type
                ? Admin_Message_Mapper::to_legacy( $action_result->message_type )
                : false;
        }

        return $result;
    }

    /**
     * @param array $query
     * @param int|null $tournament_id
     *
     * @return array{view_model:Tournament_Modify_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_get( array $query, ?int $tournament_id ): array {
        $existing = $this->load_tournament_and_fees_if_any( $tournament_id );
        $edit     = ! empty( $tournament_id );

        $vm = $this->build_view_model(
            edit: $edit,
            tournament: $existing['tournament'],
            fees: $existing['fees']
        );

        $result = array(
            'view_model' => $vm,
        );

        if ( isset( $query['added'] ) && '1' === strval( $query['added'] ) ) {
            $result['message']      = __( 'Tournament added', 'racketmanager' );
            $result['message_type'] = false;
        } elseif ( isset( $query['updated'] ) && '1' === strval( $query['updated'] ) ) {
            $result['message']      = __( 'Tournament updated', 'racketmanager' );
            $result['message_type'] = false;
        }

        return $result;
    }

    private function build_view_model(
        bool $edit,
        object $tournament,
        object $fees
    ): Tournament_Modify_Page_View_Model {
        $clubs        = $this->club_service->get_clubs( array( 'type' => 'affiliated' ) );
        $competitions = $this->competition_service->get_tournament_competitions();
        $seasons      = $this->season_service->get_all_seasons();

        $form_title  = empty( $edit ) ? __( 'Add Tournament', 'racketmanager' ) : __( 'Edit Tournament', 'racketmanager' );
        $form_action = empty( $edit ) ? __( 'Add', 'racketmanager' ) : __( 'Update', 'racketmanager' );

        return new Tournament_Modify_Page_View_Model(
            edit: $edit,
            form_title: $form_title,
            form_action: $form_action,
            tournament: $tournament,
            fees: $fees,
            clubs: $clubs,
            competitions: $competitions,
            seasons: $seasons,
            validator: new Validator_Tournament(), // Empty validator for the template
        );
    }

    /**
     * @param int|null $tournament_id
     * @return array{tournament:object, fees:object}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function load_tournament_and_fees_if_any( ?int $tournament_id ): array {
        if ( empty( $tournament_id ) ) {
            return array(
                'tournament' => $this->ensure_object( null ),
                'fees'      => $this->ensure_object( null ),
            );
        }

        try {
            $response   = $this->tournament_service->get_tournament_and_fees( $tournament_id );
            $tournament = $this->ensure_object( $response['tournament'] ?? null );
            $fees       = $this->ensure_object( $response['fees'] ?? null );

            return array(
                'tournament' => $tournament,
                'fees'      => $fees,
            );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
    }

    private function ensure_object( mixed $value ): object {
        if ( is_object( $value ) ) {
            return $value;
        }
        return new stdClass();
    }

    private function apply_error_to_view_model(
        Tournament_Modify_Page_View_Model $vm,
        WP_Error $error
    ): Tournament_Modify_Page_View_Model {
        $validator = new Validator_Tournament();
        $validator->error = true;

        $validator->err_msgs = $error->get_error_messages();
        $validator->err_flds = $error->get_error_codes();

        return new Tournament_Modify_Page_View_Model(
            edit: $vm->edit,
            form_title: $vm->form_title,
            form_action: $vm->form_action,
            tournament: $vm->tournament,
            fees: $vm->fees,
            clubs: $vm->clubs,
            competitions: $vm->competitions,
            seasons: $vm->seasons,
            validator: $validator,
        );
    }
}
