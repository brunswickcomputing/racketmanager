<?php
/**
 * RacketManager-Admin API: Admin_Result class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Result
 */

namespace Racketmanager\Admin;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use function Racketmanager\get_result_check;

/**
 * RacketManager Season Admin functions
 * Class to implement RacketManager Admin Result
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Result
 */
class Admin_Result extends Admin_Display {
    /**
     * Function to handle administration club displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $this->display_results_page();
    }
    /**
     * Show RacketManager results page
     */
    private function display_results_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'view_leagues' ) ) {
            $this->set_message( $this->no_permission, true );
            $this->show_message();
        } else {
            $season_select        = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
            $competition_select   = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : '';
            $event_select         = isset( $_GET['event'] ) ? intval( $_GET['event'] ) : '';
            $results_check_filter = isset( $_GET['filterResultsChecker'] ) ? sanitize_text_field( wp_unslash( $_GET['filterResultsChecker'] ) ) : 'outstanding';
            $tab                  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'resultschecker';
            if ( isset( $_POST['doResultsChecker'] ) ) {
                if ( current_user_can( 'update_results' ) ) {
                    check_admin_referer( 'results-checker-bulk' );
                    if ( isset( $_POST['resultsChecker'] ) && isset( $_POST['action'] ) ) {
                        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                        foreach ( $_POST['resultsChecker'] as $i => $results_checker_id ) {
                            $result_check = get_result_check( $results_checker_id );
                            if ( $result_check ) {
                                if ( 'approve' === $_POST['action'] ) {
                                    $result_check->approve();
                                } elseif ( 'handle' === $_POST['action'] ) {
                                    $result_check->handle();
                                } elseif ( 'delete' === $_POST['action'] ) {
                                    $result_check->delete();
                                }
                            } else {
                                $this->set_message( __( 'Result check not found', 'racketmanager' ), true );
                            }
                        }
                    } else {
                        $this->set_message( __( 'No actions flagged', 'racketmanager' ), true );
                    }
                } else {
                    $this->set_message( $this->no_permission, true );
                }
                $this->show_message();
                $tab = 'resultschecker';
            }
            $results_checkers = $racketmanager->get_result_warnings(
                array(
                    'season'      => $season_select,
                    'competition' => $competition_select,
                    'event'       => $event_select,
                    'status'      => $results_check_filter,
                )
            );
            $competitions = $this->competition_service->get_leagues();
            $events       = array();
            foreach ( $competitions as $competition ) {
                try {
                    $competition_events = $this->competition_service->get_events_for_competition( $competition->id );
                } catch ( Competition_Not_Found_Exception ) {
                    continue;
                }
                foreach ( $competition_events as $event ) {
                    $events[] = $event;
                }
            }
            include_once RACKETMANAGER_PATH . 'templates/admin/show-results.php';
        }
    }
}
