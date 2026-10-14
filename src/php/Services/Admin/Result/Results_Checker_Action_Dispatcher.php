<?php
/**
 * Results checker moderation action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Result
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Result;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Result\Results_Checker_Manager;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher_Interface;

/**
 * Handles bulk Results Checker moderation mutations (approve, handle, delete).
 */
readonly class Results_Checker_Action_Dispatcher implements Results_Checker_Action_Dispatcher_Interface {

    public function __construct(
        private Results_Checker_Manager $results_checker_manager,
        private Results_Checker_Repository_Interface $results_checker_repository,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $post Typically $_POST
     */
    public function handle( array $post ): Action_Result_DTO {
        if ( ! isset( $post['doResultsChecker'] ) ) {
            return new Action_Result_DTO();
        }

        try {
            $this->action_guard->assert_allowed( '_wpnonce', 'results-checker-bulk', 'update_results' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'resultschecker',
            );
        }

        $selected_ids = isset( $post['resultsChecker'] ) && is_array( $post['resultsChecker'] )
            ? array_values( $post['resultsChecker'] )
            : array();

        $action = isset( $post['action'] ) ? sanitize_text_field( strval( $post['action'] ) ) : '';

        if ( empty( $selected_ids ) || ! in_array( $action, array( 'approve', 'handle', 'delete' ), true ) ) {
            return new Action_Result_DTO(
                message: __( 'No actions flagged', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'resultschecker',
            );
        }

        $processed_count = 0;
        $not_found_count = 0;

        foreach ( $selected_ids as $results_checker_id ) {
            $result_check = $this->results_checker_repository->find_by_id( (int) $results_checker_id );
            if ( null !== $result_check ) {
                if ( 'approve' === $action ) {
                    $this->results_checker_manager->approve( $result_check );
                    ++$processed_count;
                } elseif ( 'handle' === $action ) {
                    $this->results_checker_manager->handle( $result_check );
                    ++$processed_count;
                } elseif ( 'delete' === $action ) {
                    $this->results_checker_repository->delete( (int) $result_check->id );
                    ++$processed_count;
                }
            } else {
                ++$not_found_count;
            }
        }

        if ( 0 === $processed_count && $not_found_count > 0 ) {
            return new Action_Result_DTO(
                message: __( 'Result check not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'resultschecker',
            );
        }

        $success_message = match ( $action ) {
            'approve' => __( 'Result check(s) approved', 'racketmanager' ),
            'handle'  => __( 'Result check(s) handled', 'racketmanager' ),
            'delete'  => __( 'Result check(s) deleted', 'racketmanager' ),
        };

        if ( $not_found_count > 0 ) {
            return new Action_Result_DTO(
                message: $success_message . '<br>' . __( 'Result check not found', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'resultschecker',
            );
        }

        return new Action_Result_DTO(
            message: $success_message,
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'resultschecker',
        );
    }
}
