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

readonly final class Draw_Action_Dispatcher {

    private const string DETECT_POST_ACTION_IN        = 'post_action_in';
    private const string DETECT_POST_ACTION_EQUALS    = 'post_action_equals';
    private const string DETECT_POST_FIELD_EQUALS     = 'post_field_equals';
    private const string DETECT_RANKING_MODE          = 'ranking_mode';
    private const string TAB_FROM_RESULT_OR_DEFAULT   = 'from_result_or_default';

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
            $tab_override  = $this->resolve_tab_override( $policy['tab_override'], $action_result, $context );

            return new Draw_Action_Response_DTO(
                $action_result->message,
                $action_result->message_type,
                $tab_override
            );
        }

        // Unknown action trigger: intentionally no-op (render only).
        return new Draw_Action_Response_DTO();
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
                'detect'       => self::DETECT_POST_FIELD_EQUALS,
                'detect_args'  => array( 'updateLeague', 'match' ),
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_manage-matches',
                'capability'   => 'edit_matches',
                'handler'      => array( 'method' => 'manage_matches_in_league' ),
                'tab_override' => 'matches',
            ),
            array(
                'key'          => 'rankings',
                'detect'       => self::DETECT_RANKING_MODE,
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_teams-bulk',
                'capability'   => 'update_results',
                'handler'      => array( 'method' => 'rank_teams', 'args' => array( 'mode_from_context' ) ),
                'tab_override' => 'preliminary',
            ),
            array(
                'key'          => 'start_finals',
                'detect'       => self::DETECT_POST_ACTION_EQUALS,
                'detect_args'  => array( 'startFinals' ),
                'nonce_field'  => 'racketmanager_proceed_nonce',
                'nonce_action' => 'racketmanager_championship_proceed',
                'capability'   => 'update_results',
                'handler'      => array( 'method' => 'start_finals' ),
                'tab_override' => array( 'strategy' => self::TAB_FROM_RESULT_OR_DEFAULT, 'default' => 'preliminary' ),
            ),
            array(
                'key'          => 'update_final_results',
                'detect'       => self::DETECT_POST_ACTION_EQUALS,
                'detect_args'  => array( 'updateFinalResults' ),
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_update-finals',
                'capability'   => 'update_results',
                'handler'      => array( 'method' => 'update_final_results' ),
                'tab_override' => null,
            ),
            array(
                'key'          => 'set_championship_matches',
                'detect'       => self::DETECT_POST_ACTION_IN,
                'detect_args'  => array( 'add', 'replace' ),
                'detect_requires' => array( 'rounds' ),
                'nonce_field'  => 'racketmanager_nonce',
                'nonce_action' => 'racketmanager_add_championship-matches',
                'capability'   => 'edit_matches',
                'handler'      => array( 'method' => 'set_championship_matches' ),
                'tab_override' => 'matches',
            ),
        );
    }

    private function detect_context( array $policy, array $post ): false|array {
        $requires = $policy['detect_requires'] ?? array();
                foreach ( (array) $requires as $required_key ) {
                        if ( ! array_key_exists( strval( $required_key ), $post ) ) {
                                return false;
            }
        }

        $detector = strval( $policy['detect'] ?? '' );
        $args     = $policy['detect_args'] ?? array();

        return match ( $detector ) {
                        self::DETECT_POST_ACTION_IN => $this->detect_post_action_in( $post, (array) $args ),
                        self::DETECT_POST_ACTION_EQUALS => $this->detect_post_action_equals( $post, strval( $args[0] ?? '' ) ),
                        self::DETECT_POST_FIELD_EQUALS => $this->detect_post_field_equals( $post, strval( $args[0] ?? '' ), strval( $args[1] ?? '' ) ),
                        self::DETECT_RANKING_MODE => $this->detect_ranking_mode( $post ),
                        default => false,
        };
    }

    private function detect_post_action_in( array $post, array $allowed ): false|array {
            if ( ! isset( $post['action'] ) ) {
                    return false;
        }
        $action = strval( $post['action'] );
        if ( in_array( $action, array_map( 'strval', $allowed ), true ) ) {
                    return array();
        }
        return false;
    }

    private function detect_post_action_equals( array $post, string $expected ): false|array {
            if ( ! isset( $post['action'] ) ) {
                    return false;
        }
        return ( strval( $post['action'] ) === $expected ) ? array() : false;
    }

    private function detect_post_field_equals( array $post, string $field, string $expected ): false|array {
            if ( '' === $field || ! isset( $post[ $field ] ) ) {
                    return false;
        }
        return ( strval( $post[ $field ] ) === $expected ) ? array() : false;
    }

    private function detect_ranking_mode( array $post ): false|array {
            $mode = $this->ranking_mode_from_post( $post );
            if ( null === $mode ) {
                    return false;
        }
        return array( 'mode' => $mode );
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
        $result = $this->championship_admin_service->{$method}( ...$call_args );
        return $result;
    }

    private function resolve_tab_override( mixed $tab_override, Action_Result_DTO $result, array $context ): ?string {
            if ( is_string( $tab_override ) ) {
                    return $tab_override;
        }
        if ( null === $tab_override ) {
                    return null;
        }
        if ( is_array( $tab_override ) && ( $tab_override['strategy'] ?? null ) === self::TAB_FROM_RESULT_OR_DEFAULT ) {
                    $default = isset( $tab_override['default'] ) ? strval( $tab_override['default'] ) : null;
                    return $result->tab_override ?? $default;
        }
        return null;
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
