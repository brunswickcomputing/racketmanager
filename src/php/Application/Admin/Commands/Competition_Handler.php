<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use InvalidArgumentException;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use RuntimeException;

final readonly class Competition_Handler {
    public function __construct(
        private Competition_Service $competition_service,
        private Event_Repository_Interface $event_repository,
    ) {
    }

    public function handle_update_competition_config( Update_Competition_Config_Command $command ): Action_Result_DTO {
        $competition_id        = $command->competition_id;
        $competition_id_passed = $command->competition_id_passed;

        if ( empty( $competition_id ) || ( null !== $competition_id_passed && $competition_id !== $competition_id_passed ) ) {
            return new Action_Result_DTO(
                message: __( 'Invalid competition id', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $config = $command->config;

        try {
            $updates = $this->competition_service->amend_details( $competition_id, $config );
            if ( is_wp_error( $updates ) ) {
                $err_flds = $updates->get_error_codes();
                $err_msgs = $updates->get_error_messages();
                $errors   = array();
                foreach ( $err_flds as $i => $field ) {
                    $errors[ $field ] = $err_msgs[ $i ] ?? __( 'Invalid field', 'racketmanager' );
                }

                return new Action_Result_DTO(
                    message: __( 'Error with competition details', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    flash_data: array(
                        'errors'    => $errors,
                        'form_data' => $command->form_data,
                    ),
                );
            }

            if ( $updates ) {
                return new Action_Result_DTO(
                    message: __( 'Competition updated', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                );
            }

            return new Action_Result_DTO(
                message: __( 'No updates', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        } catch ( Competition_Not_Updated_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
            );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    public function handle_delete_events( Delete_Competition_Events_Command $command ): Action_Result_DTO {
        $events = $command->event;
        if ( empty( $events ) ) {
            return new Action_Result_DTO(
                message: __( 'No events were found to delete', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'events',
            );
        }

        $msg = array();
        foreach ( $events as $event_id ) {
            $event = $this->event_repository->find_by_id( (int) $event_id );
            if ( null !== $event ) {
                try {
                    $this->competition_service->delete_event( $event );
                } catch ( RuntimeException | InvalidArgumentException $e ) {
                    return new Action_Result_DTO(
                        message: implode( '<br>', array_merge( $msg, array( $e->getMessage() ) ) ),
                        message_type: Admin_Message_Type::ERROR,
                        tab_override: 'events',
                    );
                }
                /* translators: %s: event name */
                $msg[] = sprintf( __( '%s has been deleted', 'racketmanager' ), $event->name );
            }
        }

        if ( ! empty( $msg ) ) {
            return new Action_Result_DTO(
                message: implode( '<br>', $msg ),
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'events',
            );
        }

        return new Action_Result_DTO(
            message: __( 'No events were found to delete', 'racketmanager' ),
            message_type: Admin_Message_Type::WARNING,
            tab_override: 'events',
        );
    }
}
