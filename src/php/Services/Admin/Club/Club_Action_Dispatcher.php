<?php
/**
 * Club action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Club
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Club;

use Exception;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Validator\Validator_Club;
use stdClass;

/**
 * Handles club mutations for the club admin screen (add/edit club, delete clubs, schedule player ratings).
 */
readonly final class Club_Action_Dispatcher {

    public function __construct(
        private Club_Service $club_service,
        private Player_Service $player_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addClub'] ) ) {
            return $this->handle_add_club( $post );
        }

        if ( isset( $post['editClub'] ) ) {
            return $this->handle_edit_club( $query, $post );
        }

        if ( isset( $post['doClubDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_clubs( $post );
        }

        if ( isset( $post['doSchedulePlayerRatings'] ) ) {
            return $this->handle_schedule_player_ratings();
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_add_club( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-club', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $club_input = $this->get_club_input( $post );
        $validator  = new Validator_Club();
        $validator  = $validator->name( $club_input->name );
        $validator  = $validator->short_code( $club_input->shortcode );
        $validator  = $validator->type( $club_input->type );
        $validator  = $validator->address( $club_input->address );

        if ( ! empty( $validator->error ) ) {
            $error_message = ! empty( $validator->err_msgs[0] ) ? strval( $validator->err_msgs[0] ) : __( 'Unable to add club', 'racketmanager' );
            return new Action_Result_DTO(
                message: $error_message,
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $new_club = $this->club_service->add_club( $club_input );
            $club_id  = is_object( $new_club ) && isset( $new_club->id ) ? (int) $new_club->id : null;

            return new Action_Result_DTO(
                message: __( 'Club added', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
                entity_id: $club_id,
                query_params: $club_id ? array( 'view' => 'club', 'club_id' => $club_id ) : array(),
            );
        } catch ( Exception $e ) {
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
    private function handle_edit_club( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-club', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $club_id = isset( $post['club_id'] ) ? (int) $post['club_id'] : ( isset( $query['club_id'] ) ? (int) $query['club_id'] : null );
        if ( ! $club_id ) {
            return new Action_Result_DTO(
                message: __( 'Club not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $club = $this->club_service->get_club( $club_id );
        } catch ( Club_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $club_updated = $this->get_club_input( $post, $club );
        $validator    = new Validator_Club();
        $validator    = $validator->name( $club_updated->name );
        $validator    = $validator->short_code( $club_updated->shortcode );
        $validator    = $validator->type( $club_updated->type );
        $validator    = $validator->address( $club_updated->address );
        $validator    = $validator->match_secretary( $club_updated->match_secretary->id );
        $validator    = $validator->telephone( $club_updated->match_secretary->contactno );
        $validator    = $validator->email( $club_updated->match_secretary->email, $club_updated->match_secretary->id );

        if ( ! empty( $validator->error ) ) {
            $error_message = ! empty( $validator->err_msgs[0] ) ? strval( $validator->err_msgs[0] ) : __( 'Unable to update club', 'racketmanager' );
            return new Action_Result_DTO(
                message: $error_message,
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'club', 'club_id' => $club_id ),
            );
        }

        $result = $this->club_service->update_club( $club_id, $club_updated );
        if ( $result ) {
            return new Action_Result_DTO(
                message: __( 'Club updated', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
                entity_id: $club_id,
                query_params: array( 'view' => 'club', 'club_id' => $club_id ),
            );
        }

        return new Action_Result_DTO(
            message: __( 'Club details unchanged', 'racketmanager' ),
            message_type: Admin_Message_Type::WARNING,
            entity_id: $club_id,
            query_params: array( 'view' => 'club', 'club_id' => $club_id ),
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_clubs( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'clubs-bulk', 'del_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $clubs = isset( $post['club'] ) && is_array( $post['club'] ) ? $post['club'] : array();
        if ( empty( $clubs ) ) {
            return new Action_Result_DTO(
                message: __( 'No clubs selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $messages      = array();
        $message_error = false;

        foreach ( $clubs as $club_id ) {
            try {
                $this->club_service->remove_club( (int) $club_id );
                $messages[] = (int) $club_id . ' ' . __( 'deleted', 'racketmanager' );
            } catch ( Exception $e ) {
                $messages[]    = $e->getMessage();
                $message_error = true;
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: $message_error ? Admin_Message_Type::ERROR : Admin_Message_Type::SUCCESS,
        );
    }

    private function handle_schedule_player_ratings(): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'clubs-bulk', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $result = $this->player_service->schedule_global_player_ratings();
            if ( is_wp_error( $result ) ) {
                return new Action_Result_DTO(
                    message: __( 'Error scheduling player ratings calculation', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                );
            }

            if ( $result ) {
                return new Action_Result_DTO(
                    message: __( 'Player ratings calculation scheduled', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                );
            }

            return new Action_Result_DTO(
                message: __( 'Player ratings calculation already scheduled', 'racketmanager' ),
                message_type: Admin_Message_Type::INFO,
            );
        } catch ( Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    /**
     * @param array<string, mixed> $post
     */
    private function get_club_input( array $post, ?object $club = null ): object {
        if ( empty( $club ) ) {
            $club_updated = new stdClass();
        } else {
            $club_updated = clone $club;
        }

        $club_updated->name                          = isset( $post['club'] ) ? sanitize_text_field( wp_unslash( strval( $post['club'] ) ) ) : null;
        $club_updated->type                          = isset( $post['type'] ) ? sanitize_text_field( wp_unslash( strval( $post['type'] ) ) ) : null;
        $club_updated->shortcode                     = isset( $post['shortcode'] ) ? sanitize_text_field( wp_unslash( strval( $post['shortcode'] ) ) ) : null;
        $club_updated->match_secretary               = new stdClass();
        $club_updated->match_secretary->id           = isset( $post['match_secretary'] ) ? intval( $post['match_secretary'] ) : null;
        $club_updated->match_secretary->display_name = isset( $post['match_secretary_name'] ) ? sanitize_text_field( wp_unslash( strval( $post['match_secretary_name'] ) ) ) : null;
        $club_updated->match_secretary->contactno    = isset( $post['match_secretary_contact_no'] ) ? sanitize_text_field( wp_unslash( strval( $post['match_secretary_contact_no'] ) ) ) : null;
        $club_updated->match_secretary->email        = isset( $post['match_secretary_email'] ) ? sanitize_text_field( wp_unslash( strval( $post['match_secretary_email'] ) ) ) : null;
        $club_updated->contactno                     = isset( $post['contactno'] ) ? sanitize_text_field( wp_unslash( strval( $post['contactno'] ) ) ) : null;
        $club_updated->website                       = isset( $post['website'] ) ? sanitize_text_field( wp_unslash( strval( $post['website'] ) ) ) : null;
        $club_updated->founded                       = empty( $post['founded'] ) ? null : intval( $post['founded'] );
        $club_updated->facilities                    = isset( $post['facilities'] ) ? sanitize_text_field( wp_unslash( strval( $post['facilities'] ) ) ) : null;
        $club_updated->address                       = isset( $post['address'] ) ? sanitize_text_field( wp_unslash( strval( $post['address'] ) ) ) : null;

        return $club_updated;
    }
}
