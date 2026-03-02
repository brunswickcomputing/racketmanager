<?php
/**
 * Draw action dispatcher
 *
 * Centralizes:
 * - action detection
 * - nonce + capability validation
 * - calling the appropriate application service method
 *
 * @package RacketManager
 * @subpackage Services/Admin/Championship
 */

namespace Racketmanager\Services\Admin\Championship;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Validator\Validator;

readonly final class Draw_Action_Dispatcher {

    public function __construct(
        private Championship_Admin_Service $championship_admin_service,
    ) {
    }

    public function handle( Draw_Action_Request_DTO $dto ): Draw_Action_Response_DTO {
        $post = $dto->post;

        // 0) no-op: nothing to do (render only)
        if ( ! $this->has_any_action( $post ) ) {
            return new Draw_Action_Response_DTO();
        }

        foreach ( $this->policies() as $policy ) {
            $context = ( $policy['detect'] )( $post );
            if ( false === $context ) {
                continue;
            }

            $this->assert_allowed( $policy['nonce_field'], $policy['nonce_action'], $policy['capability'] );

            $action_result = ( $policy['handle'] )( $dto, is_array( $context ) ? $context : array() );
            $tab_override  = ( $policy['tab_override'] )( $action_result, is_array( $context ) ? $context : array() );

            return new Draw_Action_Response_DTO(
                $action_result->message,
                $action_result->message_type,
                $tab_override
            );
        }

        // Unknown action trigger: intentionally no-op (render only).
        return new Draw_Action_Response_DTO();
    }

    private function assert_allowed( string $nonce_field, string $nonce_action, string $capability ): void {
        $v = new Validator();
        $v = $v->check_security_token( $nonce_field, $nonce_action );
        $v = $v->capability( $capability );
        if ( ! empty( $v->error ) ) {
            throw new Invalid_Status_Exception( $v->msg );
        }
    }

    /**
     * Declarative policy map for draw-page actions.
     *
     * Each policy defines:
     * - detect: returns false (no match) or context array
     * - nonce/capability requirements
     * - handle: executes the application service call and returns Action_Result_DTO
     * - tab_override: derives the tab override from the result/context
     *
     * @return array<int,array{
     *   key:string,
     *   detect:callable(array):false|array,
     *   nonce_field:string,
     *   nonce_action:string,
     *   capability:string,
     *   handle:callable(Draw_Action_Request_DTO,array):Action_Result_DTO,
     *   tab_override:callable(Action_Result_DTO,array):?string
     * }>
     */
    private function policies(): array {
        return array(
            array(
                'key'          => 'league_team_action',
                'detect'       => function ( array $post ): false|array {
                    if ( isset( $post['action'] ) && in_array( strval( $post['action'] ), array( 'delete', 'withdraw' ), true ) ) {
                        return array();
                    }
                    return false;
                },
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_teams-bulk',
                'capability'   => 'del_teams',
                'handle'       => function ( Draw_Action_Request_DTO $dto, array $context ): Action_Result_DTO {
                    return $this->championship_admin_service->handle_league_teams_action( $dto );
                },
                'tab_override' => function ( Action_Result_DTO $result, array $context ): ?string {
                    return 'preliminary';
                },
            ),
            array(
                'key'          => 'add_teams',
                'detect'       => function ( array $post ): false|array {
                    if ( isset( $post['action'] ) && 'addTeamsToLeague' === strval( $post['action'] ) ) {
                        return array();
                    }
                    return false;
                },
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_add-teams-bulk',
                'capability'   => 'edit_teams',
                'handle'       => function ( Draw_Action_Request_DTO $dto, array $context ): Action_Result_DTO {
                    return $this->championship_admin_service->add_teams_to_league( $dto );
                },
                'tab_override' => function ( Action_Result_DTO $result, array $context ): ?string {
                    return 'preliminary';
                },
            ),
            array(
                'key'          => 'manage_matches',
                'detect'       => function ( array $post ): false|array {
                    if ( isset( $post['updateLeague'] ) && 'match' === strval( $post['updateLeague'] ) ) {
                        return array();
                    }
                    return false;
                },
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_manage-matches',
                'capability'   => 'edit_matches',
                'handle'       => function ( Draw_Action_Request_DTO $dto, array $context ): Action_Result_DTO {
                    return $this->championship_admin_service->manage_matches_in_league( $dto );
                },
                'tab_override' => function ( Action_Result_DTO $result, array $context ): ?string {
                    return 'matches';
                },
            ),
            array(
                'key'          => 'rankings',
                'detect'       => function ( array $post ): false|array {
                    $mode = $this->ranking_mode_from_post( $post );
                    if ( null === $mode ) {
                        return false;
                    }
                    return array( 'mode' => $mode );
                },
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_teams-bulk',
                'capability'   => 'update_results',
                'handle'       => function ( Draw_Action_Request_DTO $dto, array $context ): Action_Result_DTO {
                    $mode = isset( $context['mode'] ) ? strval( $context['mode'] ) : 'manual';
                    return $this->championship_admin_service->rank_teams( $dto, $mode );
                },
                'tab_override' => function ( Action_Result_DTO $result, array $context ): ?string {
                    return 'preliminary';
                },
            ),
            array(
                'key'          => 'start_finals',
                'detect'       => function ( array $post ): false|array {
                    if ( isset( $post['action'] ) && 'startFinals' === strval( $post['action'] ) ) {
                        return array();
                    }
                    return false;
                },
                'nonce_field'  => 'racketmanager_proceed_nonce',
                'nonce_action' => 'racketmanager_championship_proceed',
                'capability'   => 'update_results',
                'handle'       => function ( Draw_Action_Request_DTO $dto, array $context ): Action_Result_DTO {
                    return $this->championship_admin_service->start_finals( $dto );
                },
                'tab_override' => function ( Action_Result_DTO $result, array $context ): ?string {
                    return $result->tab_override ?? 'preliminary';
                },
            ),
            array(
                'key'          => 'update_final_results',
                'detect'       => function ( array $post ): false|array {
                    if ( isset( $post['action'] ) && 'updateFinalResults' === strval( $post['action'] ) ) {
                        return array();
                    }
                    return false;
                },
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_update-finals',
                'capability'   => 'update_results',
                'handle'       => function ( Draw_Action_Request_DTO $dto, array $context ): Action_Result_DTO {
                    return $this->championship_admin_service->update_final_results( $dto );
                },
                'tab_override' => function ( Action_Result_DTO $result, array $context ): ?string {
                    return null;
                },
            ),
            array(
                'key'          => 'set_championship_matches',
                'detect'       => function ( array $post ): false|array {
                    if ( isset( $post['action'] ) && in_array( strval( $post['action'] ), array( 'add', 'replace' ), true ) && isset( $post['rounds'] ) ) {
                        return array();
                    }
                    return false;
                },
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_add_championship-matches',
                'capability'   => 'edit_matches',
                'handle'       => function ( Draw_Action_Request_DTO $dto, array $context ): Action_Result_DTO {
                    return $this->championship_admin_service->set_championship_matches( $dto );
                },
                'tab_override' => function ( Action_Result_DTO $result, array $context ): ?string {
                    return 'matches';
                },
            ),
        );
    }

    private function ranking_mode_from_post( array $post ): ?string {
        if ( isset( $post['saveRanking'] ) ) {
            return 'manual';
        }
        if ( isset( $post['randomRanking'] ) ) {
            return 'random';
        }
        if ( isset( $post['ratingPointsRanking'] ) ) {
            return 'ratings';
        }
        return null;
    }

    private function has_any_action( array $post ): bool {
        return isset( $post['action'] )
            || isset( $post['updateLeague'] )
            || isset( $post['saveRanking'] )
            || isset( $post['randomRanking'] )
            || isset( $post['ratingPointsRanking'] );
    }
}
