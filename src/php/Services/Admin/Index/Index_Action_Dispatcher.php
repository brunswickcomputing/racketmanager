<?php
/**
 * Index action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Index
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Index;

use Closure;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Duplicate_Competition_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Validator\Validator;

/**
 * Handles competition creation and bulk-deletion actions for the admin index screen.
 */
readonly final class Index_Action_Dispatcher implements Index_Action_Dispatcher_Interface {

    /**
     * @var Closure():object
     */
    private Closure $validator_factory;

    /**
     * @param Competition_Service    $competition_service Competition domain service.
     * @param Action_Guard_Interface $action_guard        Security and capability guard.
     * @param callable():object|null $validator_factory   Optional factory returning Validator instance.
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        ?callable $validator_factory = null,
    ) {
        $factory                 = $validator_factory ?? static fn (): object => new Validator();
        $this->validator_factory = $factory( ... );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addCompetition'] ) ) {
            return $this->handle_add_competition( $post );
        }

        if ( isset( $post['doCompDel'] ) ) {
            return $this->handle_delete_competitions( $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * Handle adding a new competition.
     *
     * @param array<string, mixed> $post
     */
    private function handle_add_competition( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-competition', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $name      = isset( $post['competition_name'] ) ? sanitize_text_field( wp_unslash( strval( $post['competition_name'] ) ) ) : null;
        $type      = isset( $post['type'] ) ? sanitize_text_field( wp_unslash( strval( $post['type'] ) ) ) : null;
        $age_group = isset( $post['age_group'] ) ? sanitize_text_field( wp_unslash( strval( $post['age_group'] ) ) ) : null;

        $validator = ( $this->validator_factory )();
        $validator = $validator->competition( $name, false );
        $validator = $validator->competition_type( $type );
        $validator = $validator->age_group( $age_group );

        if ( ! empty( $validator->error ) ) {
            $errors = array();
            if ( ! empty( $validator->err_flds ) && is_array( $validator->err_flds ) ) {
                foreach ( $validator->err_flds as $i => $field ) {
                    $errors[ strval( $field ) ] = $validator->err_msgs[ $i ] ?? '';
                }
            }
            $msg = ! empty( $validator->msg ) ? $validator->msg : __( 'Error in competition creation', 'racketmanager' );

            return new Action_Result_DTO(
                message: $msg,
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'errors'           => $errors,
                    'competition_name' => $name ?? '',
                    'type'             => $type ?? '',
                    'age_group'        => $age_group ?? '',
                ),
            );
        }

        try {
            $this->competition_service->create( $name, $type, $age_group );

            return new Action_Result_DTO(
                message: __( 'Competition added', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Duplicate_Competition_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'errors'           => array( 'competition' => $e->getMessage() ),
                    'competition_name' => $name ?? '',
                    'type'             => $type ?? '',
                    'age_group'        => $age_group ?? '',
                ),
            );
        }
    }

    /**
     * Handle bulk deletion of competitions.
     *
     * @param array<string, mixed> $post
     */
    private function handle_delete_competitions( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_competitions-bulk', 'del_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $action = isset( $post['action'] ) ? sanitize_text_field( wp_unslash( strval( $post['action'] ) ) ) : '';
        if ( 'delete' !== $action ) {
            return new Action_Result_DTO(
                message: __( 'No deletions flagged', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $competitions = isset( $post['competition'] ) ? (array) $post['competition'] : array();
        if ( empty( $competitions ) ) {
            return new Action_Result_DTO(
                message: __( 'No deletions flagged', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $messages = array();
        foreach ( $competitions as $competition_id ) {
            $id = intval( $competition_id );
            if ( $id <= 0 ) {
                continue;
            }
            $this->competition_service->remove( $id );
            $messages[] = sprintf( __( 'Deleted %d', 'racketmanager' ), $id );
        }

        if ( empty( $messages ) ) {
            return new Action_Result_DTO(
                message: __( 'No deletions flagged', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
