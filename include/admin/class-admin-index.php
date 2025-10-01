<?php
/**
 * RacketManager-Admin API: Admin_Index class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Index
 */

namespace Racketmanager\admin;

use Racketmanager\Competition;
use Racketmanager\Validator;
use stdClass;
use function Racketmanager\get_competition;

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
        require_once RACKETMANAGER_PATH . '/admin/index.php';
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
        $competition            = new Competition( $competition );
        $return                 = new stdClass();
        $return->msg            = __( 'Competition added', 'racketmanager' );
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
                $competition = get_competition( intval( $competition_id ) );
                if ( $competition ) {
                    $competition->delete();
                    $this->delete_competition_pages( $competition->name );
                    $messages[] = $competition->name . ' ' . __( 'deleted', 'racketmanager' );
                }
            }
            $message = implode( '<br>', $messages );
        } else {
            $message = __( 'No deletions flagged', 'racketmanager' );
        }
        $return      = new stdClass();
        $return->msg = $message;
        return $return;
    }
    /**
     * Delete all Competition Pages
     *
     * @param string $competition_name competition name.
     */
    private function delete_competition_pages( string $competition_name ): void {
        $title     = $competition_name . ' ' . __( 'Tables', 'racketmanager' );
        $page_name = sanitize_title_with_dashes( $title );
        $this->delete_racketmanager_page( $page_name );
        $title     = $competition_name;
        $page_name = sanitize_title_with_dashes( $title );
        $this->delete_racketmanager_page( $page_name );
    }
    /**
     * Delete page
     *
     * @param string $page_name page name.
     */
    private function delete_racketmanager_page( string $page_name ): void {
        $option  = 'racketmanager_page_' . $page_name . '_id';
        $page_id = intval( get_option( $option ) );
        // Force delete this so the Title/slug "Menu" can be used again.
        if ( $page_id ) {
            wp_delete_post( $page_id, true );
            delete_option( $option );
        }
    }
}
