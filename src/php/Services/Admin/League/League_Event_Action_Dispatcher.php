<?php
/**
 * League event action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;

/**
 * Handles event mutations for the league admin screen (add/update league in event, delete leagues).
 */
readonly final class League_Event_Action_Dispatcher {

    public function __construct(
        private League_Service $league_service,
        private Action_Guard_Interface $action_guard,
        private League_Query_Service $league_query_service,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addLeague'] ) ) {
            return $this->handle_add_or_update_league( $query, $post );
        }

        if ( isset( $post['doActionLeague'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_leagues( $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_add_or_update_league( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-league', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $league_id   = isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null;
        $event_id    = isset( $post['event_id'] ) ? intval( $post['event_id'] ) : ( isset( $query['event_id'] ) ? intval( $query['event_id'] ) : null );
        $league_name = isset( $post['league_title'] ) ? sanitize_text_field( wp_unslash( strval( $post['league_title'] ) ) ) : null;

        if ( empty( $league_id ) ) {
            try {
                $this->league_service->add_league_to_event( $event_id, $league_name );
                return new Action_Result_DTO(
                    message: __( 'League added', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                );
            } catch ( League_Not_Found_Exception $e ) {
                return new Action_Result_DTO(
                    message: $e->getMessage(),
                    message_type: Admin_Message_Type::ERROR,
                );
            }
        }

        $league = $this->league_service->get_league( $league_id );
        if ( null === $league ) {
            return new Action_Result_DTO(
                message: __( 'League not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( sanitize_text_field( wp_unslash( strval( $post['league_title'] ?? '' ) ) ) === $league->title ) {
            return new Action_Result_DTO(
                message: __( 'No updates', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $sequence = isset( $post['sequence'] ) ? sanitize_text_field( wp_unslash( strval( $post['sequence'] ) ) ) : null;
        $this->league_query_service->update( $league, $league_name, $sequence );

        return new Action_Result_DTO(
            message: __( 'League Updated', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_leagues( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'leagues-bulk', 'del_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $leagues = isset( $post['league'] ) && is_array( $post['league'] ) ? $post['league'] : array();
        if ( empty( $leagues ) ) {
            return new Action_Result_DTO();
        }

        $messages = array();
        try {
            foreach ( $leagues as $league_id ) {
                $league = $this->league_service->get_league( intval( $league_id ) );
                if ( null !== $league ) {
                    $this->league_service->delete_league( $league );
                    $messages[] = $league->title . ' ' . __( 'deleted', 'racketmanager' );
                }
            }
        } catch ( \RuntimeException | \InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( empty( $messages ) ) {
            return new Action_Result_DTO();
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
