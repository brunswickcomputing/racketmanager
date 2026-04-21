<?php
/**
 * AJAX Front end match response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Frontend_Match
 */

namespace Racketmanager\Ajax;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Response\Logging_Json_Response_Factory;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Validator\Validator_Fixture;
use stdClass;
use function Racketmanager\get_match;
use function Racketmanager\match_header;
use function Racketmanager\match_option_modal;
use function Racketmanager\show_alert;

/**
 * Implement AJAX front end match responses.
 *
 * @author Paul Moffat
 */
class Ajax_Fixture extends Ajax {
    public string $no_match_id;
    public string $no_modal;
    public string $not_played;
    public string $match_not_found;

    /**
     * Register ajax actions.
     *
     * @param $plugin_instance
     */
    public function __construct( $plugin_instance ) {
        parent::__construct( $plugin_instance );
        add_action( 'wp_ajax_racketmanager_match_card', array( &$this, 'print_match_card' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_card', array( &$this, 'print_match_card' ) );
        add_action( 'wp_ajax_racketmanager_match_rubber_status', array( &$this, 'match_rubber_status_options' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_rubber_status', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_rubber_status', array( &$this, 'set_match_rubber_status' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_rubber_status', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_match_status', array( &$this, 'match_status_options' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_status', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_status', array( &$this, 'set_match_status' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_status', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_match_option', array( &$this, 'show_match_option' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_option', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_date', array( &$this, 'set_match_date' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_date', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_switch_home_away', array( &$this, 'switch_home_away' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_switch_home_away', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_reset_match_result', array( &$this, 'reset_match_result' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_reset_match_result', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_update_match_header', array( &$this, 'update_match_header' ) );
        add_action( 'wp_ajax_racketmanager_update_match', array( &$this, 'update_fixture_result' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_match', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_update_rubbers', array( &$this, 'update_team_match' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_rubbers', array( &$this, 'logged_out' ) );
        $this->no_match_id     = __( 'Match id not supplied', 'racketmanager' );
        $this->no_modal        = __( 'Modal name not supplied', 'racketmanager' );
        $this->not_played      = __( 'Not played', 'racketmanager' );
        $this->match_not_found = __( 'Match not found', 'racketmanager' );
    }

    /**
     * Build screen to allow printing of match cards
     */
    public function print_match_card(): void {
        $adapter = $this->get_fixture_ajax_adapter();
        $adapter->print_match_card();
    }

    /**
     * Build screen to allow match status to be captured
     */
    public function match_status_options(): void {
        $adapter = $this->get_fixture_ajax_adapter();
        $adapter->match_status_options();
    }

    /**
     * Set match status
     */
    public function set_match_status(): void {
        $adapter = $this->get_fixture_ajax_adapter();
        $adapter->set_match_status();
    }


    /**
     * Build screen to show the selected match option
     */
    #[NoReturn]
    public function show_match_option(): void {
        $output    = null;
        $validator = new Validator_Fixture();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : 0;
            $modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $option   = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : null;
            $output   = match_option_modal( array( 'option' => $option, 'modal' => $modal, 'match_id' => $match_id ) );
        }
        if ( ! empty( $validator->error ) ) {
            $output = show_alert( $validator->msg, 'danger', 'modal' );
            status_header( $validator->status );
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Set the match date function
     *
     * @return void
     */
    public function set_match_date(): void {
        $match_id      = null;
        $modal         = null;
        $schedule_date = null;
        $match         = null;
        $error_field   = 'schedule-date';
        $validator     = new Validator_Fixture();
        $validator     = $validator->check_security_token( 'racketmanager_nonce', 'match-option' );
        if ( empty( $validator->error ) ) {
            $modal         = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $match_id      = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $schedule_date = isset( $_POST['schedule-date'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule-date'] ) ) : null;
            $validator     = $validator->modal( $modal, $error_field );
            $validator     = $validator->match( $match_id, $error_field );
        }
        if ( empty( $validator->error ) ) {
            $match     = get_match( $match_id );
            $validator = $validator->scheduled_date( $schedule_date, $match->date );
        }
        if ( empty( $validator->error ) ) {
            if ( strlen( $schedule_date ) === 10 ) {
                $schedule_date_fmt = mysql2date( 'D j M', $schedule_date );
            } else {
                $schedule_date_fmt = mysql2date( 'j F Y H:i', $schedule_date );
            }
            $maintenance_service = $this->get_fixture_maintenance_service();
            $maintenance_service->update_fixture_date( $match_id, $schedule_date, $match->date );
            $maintenance_service->update_fixture_status( $match_id, 5 );

            $return                         = new stdClass();
            $return->msg                    = __( 'Match schedule updated', 'racketmanager' );
            $return->modal                  = $modal;
            $return->match_id               = $match_id;
            $return->schedule_date          = $schedule_date;
            $return->schedule_date_formated = $schedule_date_fmt;
            wp_send_json_success( $return );
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to update match schedule', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }

    /**
     * Get the Fixture AJAX Adapter with its dependencies.
     *
     * @return Fixture_Ajax_Adapter
     */
    private function get_fixture_ajax_adapter(): Fixture_Ajax_Adapter {
        $c = $this->racketmanager->container;

        return new Fixture_Ajax_Adapter(
            $c,
            new Security_Service(),
            new Logging_Json_Response_Factory( new Json_Response_Factory() ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'view_renderer' ),
            new Fixture_Presenter()
        );

    }

    /**
     * Get the Fixture Maintenance Service with its dependencies.
     *
     * @return Fixture_Maintenance_Service
     */
    private function get_fixture_maintenance_service(): Fixture_Maintenance_Service {
        $c = $this->racketmanager->container;

        $repository_provider = new Repository_Provider( $c->get( 'league_repository' ), $c->get( 'event_repository' ), $c->get( 'competition_repository' ), $c->get( 'league_team_repository' ), $c->get( 'team_repository' ), $c->get( 'player_repository' ), $c->get( 'rubber_repository' ), $c->get( 'results_checker_repository' ), $c->get( 'results_report_repository' ), $c->get( 'fixture_repository' ), $c->get( 'club_repository' ) );

        $service_provider = new Fixture_Service_Provider( $c->get( 'result_service' ), $c->get( 'knockout_progression_service' ), $c->get( 'league_service' ), $c->get( 'score_validation_service' ), $c->get( 'player_validation_service' ), $c->get( 'notification_service' ), $c->get( 'registration_service' ) );
        $service_provider->set_settings_service( $c->get( 'settings_service' ) );
        $service_provider->set_fixture_permission_service( $c->get( 'fixture_permission_service' ) );
        $service_provider->set_fixture_detail_service( $c->get( 'fixture_detail_service' ) );
        $service_provider->set_team_service( $c->get( 'team_service' ) );
        $service_provider->set_competition_service( $c->get( 'competition_service' ) );

        $fixture_result_manager = new Fixture_Result_Manager( $service_provider, $repository_provider );
        $service_provider->set_fixture_maintenance_service( new Fixture_Maintenance_Service( $service_provider, $repository_provider, $fixture_result_manager ) );

        return $service_provider->get_fixture_maintenance_service();
    }

    /**
     * Switch home and away teams function
     *
     * @return void
     */
    public function switch_home_away(): void {
        $modal       = null;
        $match_id    = null;
        $error_field = 'schedule-date';
        $validator   = new Validator_Fixture();
        $validator   = $validator->check_security_token( 'racketmanager_nonce', 'match-option' );
        if ( empty( $validator->error ) ) {
            $modal     = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $match_id  = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $validator = $validator->modal( $modal, $error_field );
            $validator = $validator->match( $match_id, $error_field );
        }
        if ( empty( $validator->error ) ) {
            $match      = get_match( $match_id );
            $old_home   = $match->home_team;
            $old_away   = $match->away_team;
            $season_dtl = $match->league->event->get_season_by_name( $match->season );
            $match_date = $season_dtl['match_dates'][ $match->match_day - 1 ];
            if ( $match_date ) {
                $maintenance_service = $this->get_fixture_maintenance_service();
                $maintenance_service->update_fixture_date( $match_id, $match_date );
                $maintenance_service->update_fixture_teams( $match_id, $old_away, $old_home );

                $return           = new stdClass();
                $return->msg      = __( 'Home and away teams switched', 'racketmanager' );
                $return->modal    = $modal;
                $return->match_id = $match_id;
                $return->link     = $match->link;
                wp_send_json_success( $return );
            } else {
                $validator->error      = true;
                $validator->err_flds[] = 'schedule-date';
                $validator->err_msgs[] = __( 'Match day not found', 'racketmanager' );
            }
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to update match schedule', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }

    /**
     * Reset result and draw for fixture
     */
    public function reset_match_result(): void {
        $adapter = $this->get_fixture_ajax_adapter();
        $adapter->reset_match_result();
    }

    /**
     * Show rubber status options
     */
    public function match_rubber_status_options(): void {
        $adapter = $this->get_fixture_ajax_adapter();
        $adapter->match_rubber_status_options();
    }

    /**
     * Set match rubber status
     */
    public function set_match_rubber_status(): void {
        $adapter = $this->get_fixture_ajax_adapter();
        $adapter->set_match_rubber_status();
    }

    /**
     * Update match header
     */
    public function update_match_header(): void {
        $match_id  = null;
        $validator = new Validator_Fixture();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $match_id  = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $validator = $validator->match( $match_id );
        }
        if ( empty( $validator->error ) ) {
            $edit_mode = isset( $_POST['edit_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_mode'] ) ) : false;
            $output    = match_header( $match_id, array( 'edit' => $edit_mode ) );
            wp_send_json_success( $output );
        }
        $return = $validator->get_details();
        wp_send_json_error( $return, $return->status );
    }

    /**
     * Update match details
     */
    public function update_fixture_result(): void {
        $adapter = $this->get_fixture_ajax_adapter();
        $adapter->update_fixture_result();
    }

    /**
     * Update match details for team matches only
     */
    public function update_team_match(): void {
        $validator = new Validator_Fixture();
        $validator->check_security_token( 'racketmanager_nonce', 'rubbers-match' );

        try {
            $fixture_id = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
            $validator->fixture( $fixture_id );

            $action = isset( $_POST['updateRubber'] ) ? sanitize_text_field( wp_unslash( $_POST['updateRubber'] ) ) : null;
            $validator->result_action( $action );

            if ( empty( $validator->error ) ) {
                $fixture_repository = new Fixture_Repository();
                $fixture            = $fixture_repository->find_by_id( $fixture_id );

                if ( $fixture ) {
                    $result_manager = $this->get_fixture_result_manager();

                    switch ( $action ) {
                        case 'results':
                            $request   = Team_Result_Update_Request::from_post( $_POST );
                            $validator = $result_manager->handle_team_result_update( $fixture, $request );
                            break;
                        case 'confirm':
                            $request   = Team_Result_Confirmation_Request::from_post( $_POST );
                            $validator = $result_manager->handle_team_result_confirmation( $fixture, $request );
                            break;
                        default:
                            break;
                    }
                }
            }
        } catch ( Exception $e ) {
            $validator->error = true;
            $validator->msg   = $e->getMessage();
        }

        if ( ! empty( $validator->error ) ) {
            $return = $validator;
            if ( empty( $return->msg ) ) {
                $return->msg = __( 'Unable to save result', 'racketmanager' );
            }
            $return->rubbers = $validator->rubbers ?? array();
            wp_send_json_error( $return, $return->status ?? 400 );
        }

        $return           = $validator;
        $return->rubbers  = $validator->rubbers ?? array();
        $return->status   = $validator->status ?? 200;
        $return->warnings = $validator->warnings ?? array();
        wp_send_json_success( $return );
    }

    /**
     * Get the Fixture Result Manager with its dependencies.
     *
     * @return Fixture_Result_Manager
     */
    private function get_fixture_result_manager(): Fixture_Result_Manager {
        $c = $this->racketmanager->container;

        $repository_provider = new Repository_Provider( $c->get( 'league_repository' ), $c->get( 'event_repository' ), $c->get( 'competition_repository' ), $c->get( 'league_team_repository' ), $c->get( 'team_repository' ), $c->get( 'player_repository' ), $c->get( 'rubber_repository' ), $c->get( 'results_checker_repository' ), $c->get( 'results_report_repository' ), $c->get( 'fixture_repository' ), $c->get( 'club_repository' ) );

        $service_provider = new Fixture_Service_Provider( $c->get( 'result_service' ), $c->get( 'knockout_progression_service' ), $c->get( 'league_service' ), $c->get( 'score_validation_service' ), $c->get( 'player_validation_service' ), $c->get( 'notification_service' ), $c->get( 'registration_service' ) );
        $service_provider->set_settings_service( $c->get( 'settings_service' ) );
        $service_provider->set_fixture_permission_service( $c->get( 'fixture_permission_service' ) );
        $service_provider->set_fixture_detail_service( $c->get( 'fixture_detail_service' ) );
        $service_provider->set_team_service( $c->get( 'team_service' ) );
        $service_provider->set_competition_service( $c->get( 'competition_service' ) );

        return new Fixture_Result_Manager( $service_provider, $repository_provider );
    }
}
