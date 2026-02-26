<?php
/**
 * AJAX Front end response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Frontend_Tournament
 */

namespace Racketmanager\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Partner_Request_DTO;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator_Entry_Form;
use Racketmanager\Services\Validator\Validator_Tournament;
use function Racketmanager\event_partner_modal;
use function Racketmanager\show_alert;
use function Racketmanager\tournament_withdrawal_modal;

/**
 * Implement AJAX front end tournament responses.
 *
 * @author Paul Moffat
 */
class Ajax_Tournament extends Ajax {
    /**
     * Register ajax actions.
     *
     * @param $plugin_instance
     */
    public function __construct( $plugin_instance ) {
        parent::__construct( $plugin_instance );
        add_action( 'wp_ajax_racketmanager_tournament_entry', array( &$this, 'tournament_entry_request' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_tournament_entry', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_tournament_payment_create', array( &$this, 'tournament_payment_create' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_tournament_payment_create', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_tournament_withdrawal', array( &$this, 'tournament_withdrawal' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_tournament_withdrawal', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_confirm_tournament_withdrawal', array( &$this, 'confirm_tournament_withdrawal' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_confirm_tournament_withdrawal', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_team_partner', array( &$this, 'team_partner' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_team_partner', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_validate_partner', array( &$this, 'validate_partner' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_validate_partner', array( &$this, 'logged_out_modal' ) );
    }

    /**
     * Tournament entry request
     *
     * @see templates/tournamententry.php
     */
    public function tournament_entry_request(): void {
        $validator = new Validator_Entry_Form();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_tournament-entry' );
        if ( ! $validator->error ) {
            $validator = $validator->logged_in_entry();
            if ( ! $validator->error ) {
                $request = new Tournament_Entry_Request_DTO( $_POST );
                try {
                    $response = $this->competition_entry_service->request_tournament_entry( $request );
                    if ( ! is_wp_error( $response ) ) {
                        $return = array();
                        array_push( $return, $response->message, $response->message_type, $response->payment_required, $response->return_link );
                        wp_send_json_success( $return );
                    }
                    $validator->error    = true;
                    $validator->err_flds = $response->get_error_codes();
                    $validator->err_msgs = $response->get_error_messages();
                    $err_msg             = $response->get_error_message( 'tournament' );
                    if ( empty( $err_msg ) ) {
                        $validator->msg = __( 'Errors in tournament entry form', 'racketmanager' );
                    } else {
                        $validator->msg = $err_msg;
                    }
                } catch ( Tournament_Not_Found_Exception ) {
                    $validator->error = true;
                }
            }
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Errors in entry form', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }

    /**
     * Validate partner selection
     */
    public function validate_partner(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_team-partner' );
        if ( empty( $validator->error ) ) {
            $partner_request = new Tournament_Partner_Request_DTO( $_POST );
            $response        = $this->competition_entry_service->validate_tournament_partner( $partner_request );
            if ( ! is_wp_error( $response ) ) {
                $return = array();
                array_push( $return, $partner_request->modal, $partner_request->partner_id, $partner_request->partner_name, $partner_request->event_id );
                wp_send_json_success( $return );
            }
            $validator->error    = true;
            $validator->err_flds = $response->get_error_codes();
            $validator->err_msgs = $response->get_error_messages();
        }
        if ( empty( $validator->status ) ) {
            $validator->status = 400;
        }
        wp_send_json_error( $validator, $validator->status );
    }

    /**
     * Tournament Withdrawal modal
     */
    #[NoReturn]
    public function tournament_withdrawal(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
            $modal         = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
            $output        = tournament_withdrawal_modal( $tournament_id, array( 'modal' => $modal, 'player_id' => $player_id ) );
        } else {
            $output = $validator->msg;
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Confirm Tournament Withdrawal
     */
    public function confirm_tournament_withdrawal(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
            $player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
            try {
                $response = $this->competition_entry_service->confirm_tournament_withdrawal( $tournament_id, $player_id );
                if ( $response ) {
                    $output = __( 'Tournament withdrawal successful and refund will be issued when tournament starts', 'racketmanager' );
                } else {
                    $output = __( 'Tournament withdrawal successful', 'racketmanager' );
                }
                wp_send_json_success( $output );
            } catch ( Tournament_Not_Found_Exception|Player_Not_Found_Exception $e ) {
                $validator->set_errors( 'tournament', $e->getMessage(), 404 );
            }
        }
        $details = $validator->get_details();
        wp_send_json_error( $details, $validator->status );
    }

    /**
     * Load partner modal
     */
    #[NoReturn]
    public function team_partner(): void {
        $validator             = new Validator_Tournament();
        $validator             = $validator->check_security_token();
        $event_id              = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : 0;
        $player_id             = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
        $modal                 = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
        $gender                = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : null;
        $season                = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
        $date_end              = isset( $_POST['dateEnd'] ) ? intval( $_POST['dateEnd'] ) : null;
        $partner_id            = isset( $_POST['partnerId'] ) ? intval( $_POST['partnerId'] ) : null;
        $tournament_id         = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
        $args                  = array();
        $args['player']        = $player_id;
        $args['gender']        = $gender;
        $args['season']        = $season;
        $args['date_end']      = $date_end;
        $args['modal']         = $modal;
        $args['partner_id']    = $partner_id;
        $args['tournament_id'] = $tournament_id;
        if ( empty( $validator->error ) ) {
            $output = event_partner_modal( $event_id, $args );
        } else {
            $return = $validator->get_details();
            $output = show_alert( $return->msg, 'danger', 'modal' );
            if ( ! empty( $return->status ) ) {
                status_header( $return->status );
            }
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }

    /**
     * Create payment intent for tournament entry
     *
     * @return void
     */
    public function tournament_payment_create(): void {
        $invoice_id    = isset( $_POST['invoiceId'] ) ? intval( $_POST['invoiceId'] ) : null;
        $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
        $player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
        try {
            $client_secret = $this->finance_service->create_tournament_payment_request( $tournament_id, $player_id, $invoice_id );
            wp_send_json_success( $client_secret );
        } catch ( Tournament_Not_Found_Exception|Player_Not_Found_Exception|Stripe_API_Exception|Invoice_Not_Found_Exception $e ) {
            wp_send_json_error( [ 'error' => $e->getMessage() ], '500' );
        }
    }
}
