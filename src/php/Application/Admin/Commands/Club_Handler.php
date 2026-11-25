<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Exception;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Club\Ports\Club_Validator_Factory_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Factory_Exception;
use stdClass;

/**
 * Club Handler
 */
final readonly class Club_Handler {
    /**
     * Constructor.
     *
     * @param Club_Service_Interface                $club_service club service.
     * @param Player_Service_Interface              $player_service player service.
     * @param Club_Validator_Factory_Interface|null $club_validator_factory validator factory.
     */
    public function __construct(
        private Club_Service_Interface $club_service,
        private Player_Service_Interface $player_service,
        private ?Club_Validator_Factory_Interface $club_validator_factory = null,
    ) {
    }

    /**
     * Add club command handler.
     *
     * @param Add_Club_Admin_Command $command add club command handler.
     *
     * @return Action_Result_DTO
     * @throws Invalid_Factory_Exception If club validator factory is missing.
     */
    public function handle_add_club( Add_Club_Admin_Command $command ): Action_Result_DTO {
        $club_input                                = new stdClass();
        $club_input->name                          = $command->name;
        $club_input->type                          = $command->type;
        $club_input->shortcode                     = $command->shortcode;
        $club_input->match_secretary               = new stdClass();
        $club_input->match_secretary->id           = $command->match_secretary_id;
        $club_input->match_secretary->display_name = $command->match_secretary_name;
        $club_input->match_secretary->contactno    = $command->match_secretary_contactno;
        $club_input->match_secretary->email        = $command->match_secretary_email;
        $club_input->contactno                     = $command->contactno;
        $club_input->website                       = $command->website;
        $club_input->founded                       = $command->founded;
        $club_input->facilities                    = $command->facilities;
        $club_input->address                       = $command->address;

        if ( null === $this->club_validator_factory ) {
            throw new Invalid_Factory_Exception( 'Club validator factory is required to validate club.' );
        }
        $validator = $this->club_validator_factory->create();
        $validator = $validator->name( $club_input->name );
        $validator = $validator->short_code( $club_input->shortcode );
        $validator = $validator->type( $club_input->type );
        $validator = $validator->address( $club_input->address );

        if ( $validator->has_errors() ) {
            $error_message = $validator->get_first_error_message() ?? __( 'Unable to add club', 'racketmanager' );
            return new Action_Result_DTO(
                message: $error_message,
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $new_club = $this->club_service->add_club( $club_input );
            $club_id  = isset( $new_club->id ) ? (int) $new_club->id : null;

            return new Action_Result_DTO(
                message: __( 'Club added', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
                entity_id: $club_id,
                query_params: $club_id ? array(
                    'view'    => 'club',
                    'club_id' => $club_id,
                ) : array(),
            );
        } catch ( Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    /**
     * Edit club command handler.
     *
     * @param Edit_Club_Admin_Command $command edit club command handler.
     *
     * @return Action_Result_DTO
     * @throws Invalid_Factory_Exception If club validator factory is missing.
     */
    public function handle_edit_club( Edit_Club_Admin_Command $command ): Action_Result_DTO {
        $club = $this->fetch_existing_club( $command->club_id );
        if ( $club instanceof Action_Result_DTO ) {
            return $club;
        }

        $query_params     = array(
            'view'    => 'club',
            'club_id' => $command->club_id,
        );
        $club_updated     = $this->build_updated_club( $club, $command );
        $validation_error = $this->validate_club( $club_updated );

        if ( null !== $validation_error ) {
            return new Action_Result_DTO(
                message: $validation_error,
                message_type: Admin_Message_Type::ERROR,
                query_params: $query_params,
            );
        }

        return $this->execute_update( $command->club_id, $club_updated, $query_params );
    }

    /**
     * Fetch existing club entity or return error result DTO.
     *
     * @param int|null $club_id Club ID.
     *
     * @return Club|Action_Result_DTO Club entity on success, or Action_Result_DTO on error.
     */
    private function fetch_existing_club( ?int $club_id ): Club|Action_Result_DTO {
        if ( ! $club_id ) {
            return new Action_Result_DTO(
                message: __( 'Club not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            return $this->club_service->get_club( $club_id );
        } catch ( Club_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    /**
     * Build updated club domain entity from command data.
     *
     * @param object                  $club    Current club entity.
     * @param Edit_Club_Admin_Command $command Command containing updated values.
     *
     * @return object Cloned and updated club entity.
     */
    private function build_updated_club( object $club, Edit_Club_Admin_Command $command ): object {
        $match_secretary               = new stdClass();
        $match_secretary->id           = $command->match_secretary_id;
        $match_secretary->display_name = $command->match_secretary_name;
        $match_secretary->contactno    = $command->match_secretary_contactno;
        $match_secretary->email        = $command->match_secretary_email;

        $club_updated = clone $club;
        $club_updated->set_name( $command->name );
        $club_updated->set_type( $command->type );
        $club_updated->set_shortcode( $command->shortcode );
        $club_updated->set_match_secretary( $match_secretary );
        $club_updated->set_contact_no( $command->contactno );
        $club_updated->set_website( $command->website );
        $club_updated->set_founded( $command->founded );
        $club_updated->set_facilities( $command->facilities );
        $club_updated->set_address( $command->address );

        return $club_updated;
    }

    /**
     * Validate updated club using the validator factory.
     *
     * @param object $club Club entity to validate.
     *
     * @return string|null First error message or null if valid.
     * @throws Invalid_Factory_Exception If club validator factory is missing.
     */
    private function validate_club( object $club ): ?string {
        if ( null === $this->club_validator_factory ) {
            throw new Invalid_Factory_Exception( 'Club validator factory is required to validate club.' );
        }

        $validator = $this->club_validator_factory->create()
                                                  ->name( $club->name )
                                                  ->short_code( $club->shortcode )
                                                  ->type( $club->type )
                                                  ->address( $club->address )
                                                  ->match_secretary( $club->match_secretary->id )
                                                  ->telephone( $club->match_secretary->contactno )
                                                  ->email( $club->match_secretary->email, $club->match_secretary->id );

        return $validator->has_errors()
            ? ( $validator->get_first_error_message() ?? __( 'Unable to update club', 'racketmanager' ) )
            : null;
    }

    /**
     * Execute club update via service.
     *
     * @param int                  $club_id      Club ID.
     * @param object               $club_updated Updated club entity.
     * @param array<string, mixed> $query_params Query parameters for redirection.
     *
     * @return Action_Result_DTO
     */
    private function execute_update( int $club_id, object $club_updated, array $query_params ): Action_Result_DTO {
        try {
            $response = $this->club_service->update_club( $club_id, $club_updated );

            return new Action_Result_DTO(
                message: $response ? __( 'Club updated', 'racketmanager' ) : __( 'Club details unchanged', 'racketmanager' ),
                message_type: $response ? Admin_Message_Type::SUCCESS : Admin_Message_Type::WARNING,
                entity_id: $club_id,
                query_params: $query_params,
            );
        } catch ( Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: $query_params,
            );
        }
    }

    /**
     * Delete clubs command handler.
     *
     * @param Delete_Clubs_Command $command delete clubs command handler.
     *
     * @return Action_Result_DTO
     */
    public function handle_delete_clubs( Delete_Clubs_Command $command ): Action_Result_DTO {
        $clubs = $command->club;
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

    /**
     * Schedule player ratings command handler.
     *
     * @return Action_Result_DTO
     */
    public function handle_schedule_player_ratings(): Action_Result_DTO {
        try {
            $result = $this->player_service->schedule_global_player_ratings();
            if ( is_wp_error( $result ) ) {
                return new Action_Result_DTO(
                    message: __( 'Error scheduling player ratings calculation', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                );
            }

            if ( $result ) {
                $dto = new Action_Result_DTO(
                    message: __( 'Player ratings calculation scheduled', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                );
            } else {
                $dto = new Action_Result_DTO(
                    message: __( 'Player ratings calculation already scheduled', 'racketmanager' ),
                    message_type: Admin_Message_Type::INFO,
                );
            }
        } catch ( Exception $e ) {
            $dto = new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
        return $dto;
    }
}
