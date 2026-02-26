<?php
/**
 * RacketManager-Admin API: RacketManager-season class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Season
 */

namespace Racketmanager\Admin;

use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Services\Validator\Validator;

/**
 * RacketManager Season Admin functions
 * Class to implement RacketManager Admin Season
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Season
 */
class Admin_Season extends Admin_Display {
    /**
     * Function to handle administration club displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $this->display_seasons_page();
    }
    /**
     * Display seasons page
     */
    public function display_seasons_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_seasons' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['addSeason'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-season' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $season = isset( $_POST['seasonName'] ) ? sanitize_text_field( wp_unslash( $_POST['seasonName'] ) ) : null;
                try {
                    $response = $this->season_service->create_season( $season );
                    if ( is_wp_error( $response ) ) {
                        $validator->err_flds = $response->get_error_codes();
                        $validator->err_msgs = $response->get_error_messages();
                        $validator->error    = true;
                        $this->set_message( __( 'Error adding season', 'racketmanager' ), true );
                    } else {
                        $this->set_message( __( 'Season added', 'racketmanager' ) );
                    }
                } catch ( Invalid_Argument_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
        } elseif ( isset( $_POST['doSeasonDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_seasons-bulk' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $seasons = $_POST['season'] ?? array();
                $deleted = 0;
                foreach ( $seasons as $season_id ) {
                    $season = sanitize_text_field( wp_unslash( $season_id ) );
                    $response = $this->season_service->delete_season( $season );
                    if ( $response ) {
                        ++ $deleted;
                    }
                }
                if ( $deleted ) {
                    $this->set_message( __( 'Season(s) deleted', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'No season to delete', 'racketmanager' ), true );
                }
            }
        }
        $this->show_message();
        $seasons = $this->season_service->get_all_seasons();
        require_once RACKETMANAGER_PATH . 'templates/admin/show-seasons.php';
    }

}
