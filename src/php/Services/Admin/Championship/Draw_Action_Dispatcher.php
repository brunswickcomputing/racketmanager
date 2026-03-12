<?php
/**
 * Draw action dispatcher
 *
 * Centralises:
 * - action detection
 * - nonce capability validation
 * - Calling the appropriate application service method
 *
 * @package RacketManager
 * @subpackage Services/Admin/Championship
 */

namespace Racketmanager\Services\Admin\Championship;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;

class Draw_Action_Dispatcher {

    public function __construct(
        private Draw_Action_Handler_Interface $championship_admin_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    public function handle( Draw_Action_Request_DTO $dto ): Draw_Action_Response_DTO {
        $post = $dto->post;

        // 0) no-op: nothing to do (render only)
        if ( ! $this->has_any_action( $post ) ) {
            return new Draw_Action_Response_DTO();
        }

        foreach ( $this->policies() as $policy ) {
            $resolved = Draw_Action_Resolver::resolve( $post, $policy );
            if ( null === $resolved ) {
                continue;
            }
            $context = $resolved;

            $this->action_guard->assert_allowed( $policy['nonce_field'], $policy['nonce_action'], $policy['capability'] );

            $action_result = $this->invoke_handler( $policy['handler'], $dto, $context );
            $tab_override  = $this->resolve_tab_override( $policy['tab_override'], $action_result );

            return new Draw_Action_Response_DTO(
                $action_result->message,
                $action_result->message_type,
                $tab_override
            );
        }

        // Unknown action trigger: intentionally no-op (render only).
        return new Draw_Action_Response_DTO();
    }

    private function has_any_action( array $post ): bool {
        return isset( $post['action'] )
               || isset( $post['updateLeague'] )
               || isset( $post['saveRanking'] )
               || isset( $post['randomRanking'] )
               || isset( $post['ratingPointsRanking']
               );
    }

    private function policies(): array {
        return array(
            array(
                'key'          => 'league_team_action',
                'detect'       => Draw_Action_Resolver::DETECT_POST_ACTION_IN,
                'detect_args'  => array( 'delete', 'withdraw' ),
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_teams-bulk',
                'capability'   => 'del_teams',
                'handler'      => array( 'method' => 'handle_league_teams_action' ),
                'tab_override' => 'preliminary',
            ),
            array(
                'key'          => 'add_teams',
                'detect'       => Draw_Action_Resolver::DETECT_POST_ACTION_EQUALS,
                'detect_args'  => array( 'addTeamsToLeague' ),
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_add-teams-bulk',
                'capability'   => 'edit_teams',
                'handler'      => array( 'method' => 'add_teams_to_league' ),
                'tab_override' => 'preliminary',
            ),
            array(
                'key'          => 'manage_matches',
                'detect'       => Draw_Action_Resolver::DETECT_POST_FIELD_EQUALS,
                'detect_args'  => array( 'updateLeague', 'fixture' ),
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_manage-fixtures',
                'capability'   => 'edit_matches',
                'handler'      => array( 'method' => 'manage_fixtures_in_league' ),
                'tab_override' => 'fixtures',
            ),
            array(
                'key'          => 'rankings',
                'detect'       => Draw_Action_Resolver::DETECT_RANKING_MODE,
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_teams-bulk',
                'capability'   => 'update_results',
                'handler'      => array( 'method' => 'rank_teams', 'args' => array( 'mode_from_context' ) ),
                'tab_override' => 'preliminary',
            ),
            array(
                'key'          => 'start_finals',
                'detect'       => Draw_Action_Resolver::DETECT_POST_ACTION_EQUALS,
                'detect_args'  => array( 'startFinals' ),
                'nonce_field'  => 'racketmanager_proceed_nonce',
                'nonce_action' => 'racketmanager_championship_proceed',
                'capability'   => 'update_results',
                'handler'      => array( 'method' => 'start_finals' ),
                'tab_override' => array( 'strategy' => Draw_Action_Resolver::TAB_FROM_RESULT_OR_DEFAULT, 'default' => 'preliminary' ),
            ),
            array(
                'key'          => 'update_final_results',
                'detect'       => Draw_Action_Resolver::DETECT_POST_ACTION_EQUALS,
                'detect_args'  => array( 'updateFinalResults' ),
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_update-finals',
                'capability'   => 'update_results',
                'handler'      => array( 'method' => 'update_final_results' ),
                'tab_override' => null,
            ),
            array(
                'key'             => 'set_championship_fixtures',
                'detect'          => Draw_Action_Resolver::DETECT_POST_ACTION_IN,
                'detect_args'     => array( 'add', 'replace' ),
                'detect_requires' => array( 'rounds' ),
                'nonce_field'     => 'racketmanager_nonce',
                'nonce_action'    => 'racketmanager_add_championship-fixtures',
                'capability'      => 'edit_matches',
                'handler'         => array( 'method' => 'set_championship_fixtures' ),
                'tab_override'    => 'fixtures',
            ),
        );
    }

    private function invoke_handler( array $handler, Draw_Action_Request_DTO $dto, array $context ): Action_Result_DTO {
        $method = strval( $handler['method'] ?? '' );
        $args   = $handler['args'] ?? array();

        if ( '' === $method || ! method_exists( $this->championship_admin_service, $method ) ) {
            return new Action_Result_DTO();
        }

        $call_args = array( $dto );
        foreach ( (array) $args as $arg_spec ) {
            if ( 'mode_from_context' === strval( $arg_spec ) ) {
                $call_args[] = isset( $context['mode'] ) ? strval( $context['mode'] ) : 'manual';
            }
        }

        /** @var Action_Result_DTO $result */
        return $this->championship_admin_service->{$method}( ...$call_args );
    }

    private function resolve_tab_override( mixed $tab_override, Action_Result_DTO $result ): ?string {
        if ( is_string( $tab_override ) ) {
            $resolved = $tab_override;
        } elseif ( is_array( $tab_override ) && ( $tab_override['strategy'] ?? null ) === Draw_Action_Resolver::TAB_FROM_RESULT_OR_DEFAULT ) {
            $default  = isset( $tab_override['default'] ) ? strval( $tab_override['default'] ) : null;
            $resolved = $result->tab_override ?? $default;
        } else {
            $resolved = null;
        }

        return $resolved;
    }
}
