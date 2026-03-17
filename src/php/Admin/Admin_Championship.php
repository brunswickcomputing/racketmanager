<?php
/**
 * RacketManager-Admin API: Admin_Championship class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Championship
 */

namespace Racketmanager\Admin;

use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Validator\Validator;
use function Racketmanager\get_league;

/**
 * RacketManager Championship Admin functions
 * Class to implement RacketManager Admin Championship
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Championship
 */
class Admin_Championship extends Admin_Display {
    /**
     * Handle administration panel
     *
     * @param object|null $league league object.
     */
    public function handle_championship_admin_page( ?object $league = null ): string {
        $validator = new Validator();
        $league = get_league( $league );
        $tab    = 'finalResults'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        if ( isset( $_POST['action'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
            if ( 'startFinals' === $action ) {
                $validator = $validator->check_security_token( 'racketmanager_proceed_nonce', 'racketmanager_championship_proceed' );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->capability( 'update_results');
                }
                if ( empty( $validator->error ) ) {
                    //TODO: start final rounds now moved to championship admin service
                    //$updates = $this->start_final_rounds( $league );
                    $updates = false;
                    if ( $updates ) {
                        $this->set_message( __( 'First round started', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'First round not started', 'racketmanager' ), true );
                        $tab = 'preliminary'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                    }
                }
            } elseif ( 'updateFinalResults' === $action ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_update-finals' );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->capability( 'update_results');
                }
                if ( empty( $validator->error ) ) {
                    $custom      = $_POST['custom'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $matches     = $_POST['matches'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $home_points = $_POST['home_points'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $away_points = $_POST['away_points'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $round       = isset( $_POST['round'] ) ? intval( $_POST['round'] ) : null;

                    $championship_manager = new Championship_Manager();
                    $championship_manager->update_final_results( $league->championship, $matches, $home_points, $away_points, $custom, $round );
                }
                if ( ! empty( $validator->error ) ) {
                    if ( empty( $validator->msg ) ) {
                        $validator->msg = __( 'Errors found', 'racketmanager' );
                    }
                    $this->set_message( $validator->msg, true );
                }
            }
            $this->show_message();
        }
        if ( count( $league->championship->groups ) > 0 ) {
            $league->set_group( $league->championship->groups[0] );
        }

        return $tab; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    }
}
