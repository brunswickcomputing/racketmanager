<?php
/**
 * RacketManager-Admin API: RacketManager-season class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Season
 */

namespace Racketmanager\Admin;

use Racketmanager\Domain\Season;
use Racketmanager\Services\Validator\Validator;
use stdClass;
use function Racketmanager\get_season;

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
    private function display_seasons_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_seasons' ) ) {
            $this->set_message( $this->no_permission, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['addSeason'] ) ) {
            if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-season' ) ) {
                $this->set_message( $this->invalid_security_token, true );
                $this->show_message();
            } else {
                $season    = isset( $_POST['seasonName'] ) ? sanitize_text_field( wp_unslash( $_POST['seasonName'] ) ) : null;
                $validator = new Validator();
                $validator = $validator->season( $season );
                if ( empty( $validator->error ) ) {
                    $this->add_season( $season );
                    $this->set_message( __( 'Season added', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'Season not added', 'racketmanager' ), true );
                }
            }
        } elseif ( isset( $_POST['doSeasonDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_seasons-bulk' ) ) {
                $this->set_message( $this->invalid_security_token, true );
                $this->show_message();
            } else {
                $seasons = isset( $_POST['season'] ) ? wp_unslash( $_POST['season'] ) : array();
                $deleted = $this->delete_seasons( $seasons );
                if ( $deleted ) {
                    $this->set_message( __( 'Season(s) deleted', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'No season to delete', 'racketmanager' ), true );
                }
            }
        }
        $this->show_message();
        $seasons = $racketmanager->get_seasons( 'DESC' );
        require_once RACKETMANAGER_PATH . '/Admin/show-seasons.php';
    }

    /**
     * Add new Season
     *
     * @param string $name name of season.
     *
     * @return void
     */
    private function add_season( string $name ): void {
        $season       = new stdClass();
        $season->name = $name;
        new Season( $season );
    }

    /**
     * Delete seasons
     *
     * @param array $seasons seasons to be deleted.
     *
     * @return int
     */
    private function delete_seasons( array $seasons ): int {
        $deleted = 0;
        foreach ( $seasons as $season_id ) {
            $season = get_season( intval( $season_id ) );
            if ( $season ) {
                $season->delete();
                ++$deleted;
            }
        }
        return $deleted;
    }
}
