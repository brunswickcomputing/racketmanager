<?php
/**
 * Tournament Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Modify_Page_View_Model;
use Racketmanager\Domain\DTO\Tournament\Tournament_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Updated_Exception;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Tournament;
use stdClass;

final readonly class Tournament_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Club_Service $club_service,
        private Competition_Service $competition_service,
        private Season_Service $season_service,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=modify
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     *
     * @return array{redirect?:string, view_model?:Tournament_Modify_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function modify_page( array $query, array $post ): array {
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }

        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;

        if ( isset( $post['addTournament'] ) ) {
            $result = $this->handle_add_post( $post, $validator );
        } elseif ( isset( $post['editTournament'] ) ) {
            $result = $this->handle_edit_post( $post, $validator, $tournament_id );
        } else {
            $result = $this->handle_get( $query, $validator, $tournament_id );
        }

        return $result;
    }

    /**
     * Small helper to standardise "re-render page with message" responses.
     *
     * @param bool $edit
     * @param object $tournament
     * @param object $fees
     * @param Validator_Tournament $validator
     * @param string $message
     * @param bool|string $message_type
     *
     * @return array{view_model:Tournament_Modify_Page_View_Model, message:string, message_type:bool|string}
     */
    private function render_with_message(
        bool $edit,
        object $tournament,
        object $fees,
        Validator_Tournament $validator,
        string $message,
        bool|string $message_type = true
    ): array {
        return array(
            'view_model'    => $this->build_view_model(
                edit: $edit,
                tournament: $tournament,
                fees: $fees,
                validator: $validator
            ),
            'message'      => $message,
            'message_type' => $message_type,
        );
    }

    /**
     * Compute a user-facing message from the validator state (fallback-safe).
     *
     * @param Validator_Tournament $validator
     * @param string $fallback
     * @return string
     */
    private function render_validation_error( Validator_Tournament $validator, string $fallback = '' ): string {
        $msg = '';

        if ( ! empty( $validator->msg ) ) {
            $msg = strval( $validator->msg );
        }

        if ( ! empty( $validator->err_msgs ) ) {
            $first = $validator->err_msgs[0] ?? null;
            $msg   = is_string( $first ) ? $first : '';
        }

        if ( '' === $msg ) {
            $msg = ( '' !== $fallback ) ? $fallback : __( 'Invalid request', 'racketmanager' );
        }

        return $msg;
    }

    /**
     * @param array $post
     * @param Validator_Tournament $validator
     *
     * @return array{redirect?:string, view_model?:Tournament_Modify_Page_View_Model, message?:string, message_type?:bool|string}
     */
    private function handle_add_post( array $post, Validator_Tournament $validator ): array {
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-tournament' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }

        $request  = new Tournament_Request_DTO( $post );
        $response = $this->tournament_service->add_tournament( $request );

        if ( is_wp_error( $response ) ) {
            $validator->error    = true;
            $validator->err_flds = $response->get_error_codes();
            $validator->err_msgs = $response->get_error_messages();

            return $this->render_with_message(
                edit: false,
                tournament: $this->ensure_object( null ),
                fees: $this->ensure_object( null ),
                validator: $validator,
                message: __( 'Error adding tournament', 'racketmanager' ),
            );
        }

        $new_id = intval( $response->id ?? 0 );

        return array(
            'redirect' => $this->build_modify_url(
                tournament_id: $new_id,
                flags: array( 'added' => 1 )
            ),
        );
    }

    /**
     * @param array $post
     * @param Validator_Tournament $validator
     * @param int|null $tournament_id
     *
     * @return array{redirect?:string, view_model?:Tournament_Modify_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_edit_post( array $post, Validator_Tournament $validator, ?int $tournament_id ): array {
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-tournament' );

        if ( empty( $validator->error ) ) {
            $tournament_id_passed = isset( $post['tournament_id'] ) ? intval( $post['tournament_id'] ) : null;
            $validator            = $validator->compare( $tournament_id_passed, $tournament_id );
        }

        if ( ! empty( $validator->error ) ) {
            $existing = $this->load_tournament_and_fees_if_any( $tournament_id );

            return $this->render_with_message(
                edit: ! empty( $tournament_id ),
                tournament: $existing['tournament'],
                fees: $existing['fees'],
                validator: $validator,
                message: $this->render_validation_error( $validator, __( 'Invalid request', 'racketmanager' ) )
            );
        }
        $request = new Tournament_Request_DTO( $post );

        try {
            $response = $this->tournament_service->update_tournament( $request );

            if ( is_wp_error( $response ) ) {
                $validator->error    = true;
                $validator->err_flds = $response->get_error_codes();
                $validator->err_msgs = $response->get_error_messages();

                $existing = $this->load_tournament_and_fees_if_any( $tournament_id );

                $result = $this->render_with_message(
                    edit: ! empty( $tournament_id ),
                    tournament: $existing['tournament'],
                    fees: $existing['fees'],
                    validator: $validator,
                    message: __( 'Error updating tournament', 'racketmanager' ),
                );
            } else {
                $result = array(
                    'redirect' => $this->build_modify_url(
                        tournament_id: intval( $tournament_id ?? ( $response->id ?? 0 ) ),
                        flags: array( 'updated' => 1 )
                    ),
                );
            }
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        } catch ( Tournament_Not_Updated_Exception $e ) {
            $existing = $this->load_tournament_and_fees_if_any( $tournament_id );

            $result = $this->render_with_message(
                edit: ! empty( $tournament_id ),
                tournament: $existing['tournament'],
                fees: $existing['fees'],
                validator: $validator,
                message: $e->getMessage(),
                message_type: 'warning'
            );
        }

        return $result;
    }

    /**
     * @param array $query
     * @param Validator_Tournament $validator
     * @param int|null $tournament_id
     *
     * @return array{view_model:Tournament_Modify_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_get( array $query, Validator_Tournament $validator, ?int $tournament_id ): array {
        $existing = $this->load_tournament_and_fees_if_any( $tournament_id );
        $edit     = ! empty( $tournament_id );

        $vm = $this->build_view_model(
            edit: $edit,
            tournament: $existing['tournament'],
            fees: $existing['fees'],
            validator: $validator
        );

        if ( isset( $query['added'] ) && '1' === strval( $query['added'] ) ) {
            return $this->render_with_message(
                edit: $edit,
                tournament: $existing['tournament'],
                fees: $existing['fees'],
                validator: $validator,
                message: __( 'Tournament added', 'racketmanager' ),
                message_type: false
            );
        }

        if ( isset( $query['updated'] ) && '1' === strval( $query['updated'] ) ) {
            return $this->render_with_message(
                edit: $edit,
                tournament: $existing['tournament'],
                fees: $existing['fees'],
                validator: $validator,
                message: __( 'Tournament updated', 'racketmanager' ),
                message_type: false
            );
        }

        return array(
            'view_model' => $vm,
        );
    }

    private function build_view_model(
        bool $edit,
        object $tournament,
        object $fees,
        Validator_Tournament $validator
    ): Tournament_Modify_Page_View_Model {
        $clubs        = $this->club_service->get_clubs( array( 'type' => 'affiliated' ) );
        $competitions = $this->competition_service->get_tournament_competitions();
        $seasons      = $this->season_service->get_all_seasons();

        $form_title  = empty( $edit ) ? __( 'Add Tournament', 'racketmanager' ) : __( 'Edit Tournament', 'racketmanager' );
        $form_action = empty( $edit ) ? __( 'Add', 'racketmanager' ) : __( 'Update', 'racketmanager' );

        return new Tournament_Modify_Page_View_Model(
            edit: $edit,
            form_title: $form_title,
            form_action: $form_action,
            tournament: $tournament,
            fees: $fees,
            clubs: $clubs,
            competitions: $competitions,
            seasons: $seasons,
            validator: $validator,
        );
    }

    /**
     * @param int|null $tournament_id
     * @return array{tournament:object, fees:object}
     *
     * @throws Tournament_Not_Found_Exception
     */
    private function load_tournament_and_fees_if_any( ?int $tournament_id ): array {
        if ( empty( $tournament_id ) ) {
            return array(
                'tournament' => $this->ensure_object( null ),
                'fees'      => $this->ensure_object( null ),
            );
        }

        try {
            $response   = $this->tournament_service->get_tournament_and_fees( $tournament_id );
            $tournament = $this->ensure_object( $response['tournament'] ?? null );
            $fees       = $this->ensure_object( $response['fees'] ?? null );

            return array(
                'tournament' => $tournament,
                'fees'      => $fees,
            );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
    }

    private function ensure_object( mixed $value ): object {
        if ( is_object( $value ) ) {
            return $value;
        }
        return new stdClass();
    }

    /**
     * @param int $tournament_id
     * @param array<string,int|string> $flags
     * @return string
     */
    private function build_modify_url( int $tournament_id, array $flags = array() ): string {
        $args = array_merge(
            array(
                'page'       => 'racketmanager-tournaments',
                'view'       => 'modify',
                'tournament' => $tournament_id,
            ),
            $flags
        );

        return admin_url( 'admin.php?' . http_build_query( $args ) );
    }
}
