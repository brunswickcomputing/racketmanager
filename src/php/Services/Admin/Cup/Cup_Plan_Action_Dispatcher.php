<?php
/**
 * Cup plan action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Cup
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Cup;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

class Cup_Plan_Action_Dispatcher {

    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['saveCup'] ) ) {
            return $this->handle_save_cup_config( $query, $post );
        }

        if ( isset( $post['savePlan'] ) ) {
            return $this->handle_save_plan( $query, $post );
        }

        if ( isset( $post['resetPlan'] ) ) {
            return $this->handle_reset_plan( $query, $post );
        }

        return new Action_Result_DTO();
    }

    private function handle_save_cup_config( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_cup-nonce', 'racketmanager_cup', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'config'
            );
        }

        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : ( isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null );
        $season         = isset( $post['season'] ) ? intval( $post['season'] ) : ( isset( $query['season'] ) ? intval( $query['season'] ) : null );
        $start_time     = isset( $post['startTime'] ) ? sanitize_text_field( wp_unslash( strval( $post['startTime'] ) ) ) : null;
        $num_courts     = isset( $post['numCourtsAvailable'] ) ? intval( $post['numCourtsAvailable'] ) : null;
        $time_increment = isset( $post['timeIncrement'] ) ? sanitize_text_field( wp_unslash( strval( $post['timeIncrement'] ) ) ) : null;

        try {
            $updates = $this->competition_service->set_plan_config( $competition_id, $season, $start_time, $num_courts, $time_increment );

            if ( is_wp_error( $updates ) ) {
                return new Action_Result_DTO(
                    message: __( 'Error with plan details', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    tab_override: 'config',
                    flash_data: array(
                        'err_flds'  => $updates->get_error_codes(),
                        'err_msgs'  => $updates->get_error_messages(),
                        'form_data' => $this->sanitize_form_data( $post ),
                    )
                );
            }

            if ( $updates ) {
                return new Action_Result_DTO(
                    message: __( 'Plan updated', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    tab_override: 'config'
                );
            }

            return new Action_Result_DTO(
                message: __( 'No updates', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'config'
            );

        } catch ( Competition_Not_Updated_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'config'
            );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'config'
            );
        }
    }

    private function handle_save_plan( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_cup-planner-nonce', 'racketmanager_cup-planner', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches'
            );
        }

        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : ( isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null );
        $season         = isset( $post['season'] ) ? intval( $post['season'] ) : ( isset( $query['season'] ) ? intval( $query['season'] ) : null );
        $courts         = (array) ( $post['court'] ?? array() );
        $start_time     = (array) ( $post['startTime'] ?? array() );
        $matches        = (array) ( $post['match'] ?? array() );
        $match_time     = (array) ( $post['matchtime'] ?? array() );

        try {
            $updates = $this->competition_service->save_plan( $competition_id, $season, $courts, $start_time, $matches, $match_time );

            if ( $updates ) {
                return new Action_Result_DTO(
                    message: __( 'Plan saved', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    tab_override: 'matches'
                );
            }

            return new Action_Result_DTO(
                message: __( 'No updates', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'matches'
            );
        } catch ( Competition_Not_Updated_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'matches'
            );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches'
            );
        }
    }

    private function handle_reset_plan( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_cup-planner-nonce', 'racketmanager_cup-planner', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches'
            );
        }

        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : ( isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null );
        $season         = isset( $post['season'] ) ? intval( $post['season'] ) : ( isset( $query['season'] ) ? intval( $query['season'] ) : null );
        $matches        = $post['match'] ?? null;

        try {
            $updates = $this->competition_service->reset_plan( $competition_id, $season, $matches );

            if ( $updates ) {
                return new Action_Result_DTO(
                    message: __( 'Plan reset', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    tab_override: 'matches'
                );
            }

            return new Action_Result_DTO(
                message: __( 'No updates', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'matches'
            );
        } catch ( Competition_Not_Updated_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'matches'
            );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches'
            );
        }
    }

    private function sanitize_form_data( array $post ): array {
        $sanitized      = array();
        $allowed_fields = array( 'startTime', 'timeIncrement', 'numCourtsAvailable', 'competition_id', 'season' );
        foreach ( $allowed_fields as $key ) {
            if ( isset( $post[ $key ] ) ) {
                if ( is_string( $post[ $key ] ) ) {
                    $sanitized[ $key ] = sanitize_text_field( wp_unslash( $post[ $key ] ) );
                } elseif ( is_numeric( $post[ $key ] ) ) {
                    $sanitized[ $key ] = intval( $post[ $key ] );
                }
            }
        }
        return $sanitized;
    }
}
