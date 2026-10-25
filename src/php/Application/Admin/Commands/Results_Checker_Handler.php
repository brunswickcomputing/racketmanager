<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Services\Result\Results_Checker_Manager;

final readonly class Results_Checker_Handler {
    public function __construct(
        private Results_Checker_Manager $results_checker_manager,
        private Results_Checker_Repository_Interface $results_checker_repository,
    ) {
    }

    public function handle( Moderate_Results_Checker_Command $command ): Action_Result_DTO {
        $selected_ids = $command->resultsChecker;
        $action = $command->action;
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
