<?php
/**
 * Results checker moderation action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Result
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Result;

use Racketmanager\Application\Admin\Commands\Moderate_Results_Checker_Command;
use Racketmanager\Application\Admin\Commands\Results_Checker_Handler;
use Racketmanager\Services\Admin\Admin_Command_Input as Input;

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

    private Results_Checker_Handler $commands;

    public function __construct(
        Results_Checker_Manager $results_checker_manager,
        Results_Checker_Repository_Interface $results_checker_repository,
        private Action_Guard_Interface $action_guard,
        ?Results_Checker_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new Results_Checker_Handler( $results_checker_manager, $results_checker_repository );
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

        try {
            $action = Input::text( $post, 'action' ) ?? '';
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR, tab_override: 'resultschecker' );
        }

        if ( empty( $selected_ids ) || ! in_array( $action, array( 'approve', 'handle', 'delete' ), true ) ) {
            return new Action_Result_DTO(
                message: __( 'No actions flagged', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'resultschecker',
            );
        }

        try {
            $command = new Moderate_Results_Checker_Command( $action, Input::column( $post, 'resultsChecker' ) );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR, tab_override: 'resultschecker' );
        }

        return $this->commands->handle( $command );
    }
}
