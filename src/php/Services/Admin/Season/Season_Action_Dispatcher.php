<?php
/**
 * Season action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Season
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Season;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Season_Service;

/**
 * Handles season mutations for the seasons admin screen (add season, bulk delete seasons).
 */
readonly final class Season_Action_Dispatcher {

    public function __construct(
        private Season_Service $season_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addSeason'] ) ) {
            return $this->handle_add_season( $post );
        }

        if ( isset( $post['doSeasonDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_seasons( $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_add_season( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-season', 'edit_seasons' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $season_name = isset( $post['seasonName'] ) ? sanitize_text_field( wp_unslash( strval( $post['seasonName'] ) ) ) : null;

        try {
            $response = $this->season_service->create_season( $season_name );
            if ( is_wp_error( $response ) ) {
                $err_msgs = $response->get_error_messages();
                $msg      = ! empty( $err_msgs ) ? implode( ' ', $err_msgs ) : __( 'Error adding season', 'racketmanager' );

                return new Action_Result_DTO(
                    message: $msg,
                    message_type: Admin_Message_Type::ERROR,
                    flash_data: array(
                        'season_name' => $season_name ?? '',
                        'errors'      => array( 'seasonName' => $msg ),
                    ),
                );
            }

            return new Action_Result_DTO(
                message: __( 'Season added', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Invalid_Argument_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'season_name' => $season_name ?? '',
                    'errors'      => array( 'seasonName' => $e->getMessage() ),
                ),
            );
        }
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_seasons( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_seasons-bulk', 'edit_seasons' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $seasons = isset( $post['season'] ) ? (array) $post['season'] : array();
        $deleted = 0;

        foreach ( $seasons as $season_id ) {
            $season   = sanitize_text_field( wp_unslash( strval( $season_id ) ) );
            $response = $this->season_service->delete_season( (int) $season );
            if ( $response ) {
                ++$deleted;
            }
        }

        if ( $deleted > 0 ) {
            return new Action_Result_DTO(
                message: __( 'Season(s) deleted', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        }

        return new Action_Result_DTO(
            message: __( 'No season to delete', 'racketmanager' ),
            message_type: Admin_Message_Type::ERROR,
        );
    }
}
