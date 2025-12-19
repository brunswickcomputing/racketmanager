<?php
/**
 * RacketManager-Admin API: Admin_Index class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Index
 */

namespace Racketmanager\Admin;

use Racketmanager\Exceptions\Duplicate_Competition_Exception;
use Racketmanager\Services\Validator\Validator;
use stdClass;

/**
 * RacketManager Index Admin functions
 * Class to implement RacketManager Admin Index
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Index
 */
class Admin_Index extends Admin_Display {
    /**
     * Show RacketManager index page
     */
    public function display_index_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['addCompetition'] ) ) {
            $validator = $this->handle_add_competition();
        } elseif ( isset( $_POST['doCompDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $validator = $this->handle_delete_competition();
        }
        if ( ! empty( $validator->msg ) ) {
            if ( empty( $validator->error ) ) {
                $this->set_message( $validator->msg );
            } else {
                $this->set_message( $validator->msg, true );
            }
            $this->show_message();
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/index.php';
    }
    /**
     * Function to handle adding a competition
     *
     * @return object
     */
    private function handle_add_competition(): object {
        $name      = null;
        $type      = null;
        $age_group = null;
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-competition' );
            if ( empty( $validator->error ) ) {
                $name      = isset( $_POST['competition_name'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_name'] ) ) : null;
                $type      = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
                $age_group = isset( $_POST['age_group'] ) ? sanitize_text_field( wp_unslash( $_POST['age_group'] ) ) : null;
                $validator = $validator->competition( $name, false );
                $validator = $validator->competition_type( $type );
                $validator = $validator->age_group( $age_group );
            }
        }
        if ( ! empty( $validator->error ) ) {
            $return = $validator->get_details();
            if ( empty( $return->msg ) ) {
                $return->msg = __( 'Error in competition creation', 'racketmanager' );
            }
            return $return;
        }
        $competition            = new stdClass();
        $competition->name      = $name;
        $competition->type      = $type;
        $competition->age_group = $age_group;
        $return                 = new stdClass();
        try {
            $this->competition_service->create( $competition );
            $return->msg = __( 'Competition added', 'racketmanager' );
        } catch ( Duplicate_Competition_Exception $e ) {
            $return->msg   = $e->getMessage();
            $return->error = true;
        }
        return $return;
    }
    /**
     * Function to handle deleting competitions
     *
     * @return object
     */
    private function handle_delete_competition(): object {
        $validator = new Validator();
        $validator = $validator->capability( 'del_leagues' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_competitions-bulk' );
        }
        if ( ! empty( $validator->error ) ) {
            $return = $validator->get_details();
            if ( empty( $return->msg ) ) {
                $return->msg = __( 'Error in competition deletion', 'racketmanager' );
            }
            return $return;
        }
        $messages     = array();
        $competitions = isset( $_POST['competition'] ) ? wp_unslash( $_POST['competition'] ) : array();
        if ( $competitions ) {
            foreach ( $competitions as $competition_id ) {
                $this->competition_service->remove( intval( $competition_id ) );
                $messages[] = sprintf( __( 'Deleted %d', 'racketmanager' ), $competition_id );
            }
            $message = implode( '<br>', $messages );
        } else {
            $message = __( 'No deletions flagged', 'racketmanager' );
        }
        $return      = new stdClass();
        $return->msg = $message;
        return $return;
    }

}
