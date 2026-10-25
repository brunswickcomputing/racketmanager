<?php
/**
 * Player action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Player
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Player;

use Exception;
use Racketmanager\Application\Player\Commands\Create_Player_Command;
use Racketmanager\Application\Player\Commands\Create_Player_Handler;
use Racketmanager\Application\Player\Commands\Update_Player_Command;
use Racketmanager\Application\Player\Commands\Update_Player_Handler;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\LTA_Tennis_Number_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Updated_Exception;
use Racketmanager\Exceptions\WTN_Error_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;

/**
 * Handles individual and bulk player mutations.
 */
readonly final class Player_Action_Dispatcher {

    private Update_Player_Handler $update_player_handler;
    private Create_Player_Handler $create_player_handler;

    public function __construct(
        private Player_Service $player_service,
        private Action_Guard_Interface $action_guard,
        ?Update_Player_Handler $update_player_handler = null,
        ?Create_Player_Handler $create_player_handler = null,
    ) {
        $this->update_player_handler = $update_player_handler ?? new Update_Player_Handler( $this->player_service );
        $this->create_player_handler = $create_player_handler ?? new Create_Player_Handler( $this->player_service );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['updatePlayer'] ) ) {
            return $this->handle_update_player( $query, $post );
        }

        if ( isset( $post['setWTN'] ) ) {
            return $this->handle_set_wtn( $query, $post );
        }

        if ( isset( $post['addPlayer'] ) ) {
            return $this->handle_add_player( $query, $post );
        }

        if ( isset( $post['doPlayerDel'] ) ) {
            return $this->handle_delete_players( $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_update_player( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $command = Update_Player_Command::from_post( $post );
        if ( empty( $command->player_id ) ) {
            return new Action_Result_DTO(
                message: __( 'Player not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $sanitized_form_data = $this->sanitize_form_data( $post );

        try {
            $player = $this->update_player_handler->handle( $command );
            if ( is_wp_error( $player ) ) {
                $errors = array();
                foreach ( $player->get_error_codes() as $code ) {
                    $errors[ (string) $code ] = $player->get_error_message( $code );
                }

                return new Action_Result_DTO(
                    message: __( 'Error with player details', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    flash_data: array(
                        'errors'    => $errors,
                        'form_data' => $sanitized_form_data,
                    ),
                );
            }

            return new Action_Result_DTO(
                message: __( 'Player updated', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Player_Not_Updated_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
                flash_data: array(
                    'form_data' => $sanitized_form_data,
                ),
            );
        } catch ( Player_Not_Found_Exception | Duplicate_Email_Exception | Duplicate_BTM_Exception | Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'form_data' => $sanitized_form_data,
                ),
            );
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_set_wtn( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $player_id = isset( $post['playerId'] ) && '' !== strval( $post['playerId'] ) ? intval( $post['playerId'] ) : null;
        if ( empty( $player_id ) ) {
            return new Action_Result_DTO(
                message: __( 'Player not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $this->player_service->get_latest_wtn( $player_id );

            return new Action_Result_DTO(
                message: __( 'WTN set', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Player_Not_Found_Exception | LTA_Tennis_Number_Not_Found_Exception | WTN_Error_Exception | Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_add_player( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $command = Create_Player_Command::from_post( $post );
        $sanitized_form_data = $this->sanitize_form_data( $post );

        try {
            $player = $this->create_player_handler->handle( $command );
            if ( is_wp_error( $player ) ) {
                $errors = array();
                foreach ( $player->get_error_codes() as $code ) {
                    $errors[ (string) $code ] = $player->get_error_message( $code );
                }

                return new Action_Result_DTO(
                    message: __( 'Error with player details', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    flash_data: array(
                        'errors'    => $errors,
                        'form_data' => $sanitized_form_data,
                    ),
                );
            }

            return new Action_Result_DTO(
                message: __( 'Player added', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Player_Exists_Exception | Duplicate_Email_Exception | Duplicate_BTM_Exception | Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'form_data' => $sanitized_form_data,
                ),
            );
        }
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_players( array $post ): Action_Result_DTO {
        if ( ! isset( $post['action'] ) || 'delete' !== $post['action'] ) {
            return new Action_Result_DTO();
        }

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_player-bulk', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $player_ids = isset( $post['player'] ) && is_array( $post['player'] ) ? array_values( $post['player'] ) : array();
        if ( empty( $player_ids ) ) {
            return new Action_Result_DTO(
                message: __( 'No selection made', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $messages      = array();
        $message_error = false;

        foreach ( $player_ids as $player_id ) {
            try {
                $deleted = $this->player_service->delete_player( (int) $player_id );
                if ( $deleted ) {
                    /* translators: %d: player ID */
                    $messages[] = sprintf( __( '%d deleted', 'racketmanager' ), (int) $player_id );
                } else {
                    /* translators: %d: player ID */
                    $messages[]    = sprintf( __( 'Unable to delete %d', 'racketmanager' ), (int) $player_id );
                    $message_error = true;
                }
            } catch ( Player_Not_Found_Exception | Exception $e ) {
                $messages[]    = $e->getMessage();
                $message_error = true;
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: $message_error ? Admin_Message_Type::WARNING : Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function sanitize_form_data( array $post ): array {
        $sanitized = array(
            'firstname'     => isset( $post['firstname'] ) ? sanitize_text_field( wp_unslash( strval( $post['firstname'] ) ) ) : '',
            'surname'       => isset( $post['surname'] ) ? sanitize_text_field( wp_unslash( strval( $post['surname'] ) ) ) : '',
            'gender'        => isset( $post['gender'] ) ? sanitize_text_field( wp_unslash( strval( $post['gender'] ) ) ) : '',
            'btm'           => isset( $post['btm'] ) ? sanitize_text_field( wp_unslash( strval( $post['btm'] ) ) ) : '',
            'contactno'     => isset( $post['contactno'] ) ? sanitize_text_field( wp_unslash( strval( $post['contactno'] ) ) ) : '',
            'email'         => isset( $post['email'] ) ? sanitize_email( wp_unslash( strval( $post['email'] ) ) ) : '',
            'year_of_birth' => isset( $post['year_of_birth'] ) && '' !== strval( $post['year_of_birth'] ) ? intval( $post['year_of_birth'] ) : null,
            'playerId'      => isset( $post['playerId'] ) && '' !== strval( $post['playerId'] ) ? intval( $post['playerId'] ) : null,
            'club_Id'       => isset( $post['club_Id'] ) && '' !== strval( $post['club_Id'] ) ? intval( $post['club_Id'] ) : null,
            'page_referrer' => isset( $post['page_referrer'] ) ? sanitize_text_field( wp_unslash( strval( $post['page_referrer'] ) ) ) : null,
        );

        if ( ! empty( $post['locked'] ) ) {
            $sanitized['locked'] = 'Locked';
        }

        return $sanitized;
    }
}
