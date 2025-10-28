<?php
/**
 * AJAX Front end response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Frontend_Tournament
 */

namespace Racketmanager\ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Stripe_Settings;
use Racketmanager\validator\Validator_Entry_Form;
use Racketmanager\validator\Validator_Tournament;
use stdClass;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use function Racketmanager\event_partner_modal;
use function Racketmanager\get_event;
use function Racketmanager\get_invoice;
use function Racketmanager\get_player;
use function Racketmanager\get_tournament;
use function Racketmanager\get_tournament_entry;
use function Racketmanager\seo_url;
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
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_racketmanager_tournament_entry', array( &$this, 'tournament_entry_request' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_tournament_entry', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_tournament_payment_create', array( &$this, 'tournament_payment_create' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_tournament_payment_create', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_update_payment', array( &$this, 'update_payment' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_payment', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_tournament_withdrawal', array( &$this, 'tournament_withdrawal' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_tournament_withdrawal', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_confirm_tournament_withdrawal', array( &$this, 'confirm_tournament_withdrawal' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_confirm_tournament_withdrawal', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_team_partner', array( &$this, 'team_partner' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_team_partner', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_validate_partner', array( &$this, 'validate_partner' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_validate_partner', array( &$this, 'logged_out_modal' ) );
        $this->event_not_found   = __( 'Event not found', 'racketmanager' );
    }
    /**
     * Tournament entry request
     *
     * @see templates/tournamententry.php
     */
    public function tournament_entry_request(): void {
        $return            = array();
        $tournament        = null;
        $tournament_events = array();
        $events            = array();
        $partners          = array();
        $player_id         = null;
        $club_id           = null;
        $btm               = null;
        $contactno         = null;
        $contactemail      = null;
        $comments          = null;
        $paid_fee          = null;
        $entry_fee         = null;
        $payment_required  = false;
        $return_link       = null;
        $validator         = new Validator_Entry_Form();
        //phpcs:disable WordPress.Security.NonceVerification.Missing
        $validator = $validator->nonce( 'tournament-entry' );
        if ( ! $validator->error ) {
            if ( ! is_user_logged_in() ) {
                $validator = $validator->logged_in_entry();
            } else {
                $tournament_id = isset( $_POST['tournamentId'] ) ? intval( $_POST['tournamentId'] ) : null;
                $season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
                $player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
                $contactno     = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : '';
                $contactemail  = isset( $_POST['contactemail'] ) ? sanitize_text_field( wp_unslash( $_POST['contactemail'] ) ) : '';
                $btm           = isset( $_POST['btm'] ) ? intval( $_POST['btm'] ) : '';
                $comments      = isset( $_POST['commentDetails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentDetails'] ) ) : '';
                $validator     = $validator->player( $player_id );
                $validator     = $validator->telephone( $contactno );
                $validator     = $validator->email( $contactemail, $player_id );
                $validator     = $validator->btm( $btm, $player_id );
                $club_id       = isset( $_POST['clubId'] ) ? sanitize_text_field( wp_unslash( $_POST['clubId'] ) ) : '';
                $validator     = $validator->club_membership( $club_id );
                // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $events            = isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : array();
                $partners          = isset( $_POST['partnerId'] ) ? wp_unslash( $_POST['partnerId'] ) : array();
                $tournament_events = isset( $_POST['tournamentEvents'] ) ? explode( ',', wp_unslash( $_POST['tournamentEvents'] ) ) : null;
                $entry_fee         = isset( $_POST['priceCostTotal'] ) ? floatval( $_POST['priceCostTotal'] ) : null;
                $paid_fee          = isset( $_POST['pricePaidTotal'] ) ? floatval( $_POST['pricePaidTotal'] ) : null;
                // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $validator  = $validator->tournament( $tournament_id );
                if ( $tournament_id ) {
                    $tournament = get_tournament( $tournament_id );
                    $validator  = $validator->tournament_open( $tournament );
                }
                $validator = $validator->events_entry( $events, $tournament->num_entries );
                foreach ( $events as $event ) {
                    $event = get_event( $event );
                    if ( substr( $event->type, 1, 1 ) === 'D' ) {
                        $partner_id = $partners[$event->id] ?? 0;
                        $field_ref  = $event->id;
                        $field_name = $event->name;
                        $validator  = $validator->partner( $partner_id, $field_ref, $field_name, $event, $season, $player_id, $tournament->date );
                    }
                }
                $acceptance = isset( $_POST['acceptance'] ) ? sanitize_text_field( wp_unslash( $_POST['acceptance'] ) ) : '';
                $validator  = $validator->entry_acceptance( $acceptance );
            }
        }
        if ( empty( $validator->error ) ) {
            $tournament_entry               = new stdClass();
            $tournament_entry->all_events   = $tournament_events;
            $tournament_entry->events       = $events;
            $tournament_entry->partners     = $partners;
            $tournament_entry->player_id    = $player_id;
            $tournament_entry->club_id      = $club_id;
            $tournament_entry->btm          = $btm;
            $tournament_entry->contactno    = $contactno;
            $tournament_entry->contactemail = $contactemail;
            $tournament_entry->comments     = $comments;
            $tournament_entry->paid         = $paid_fee;
            $tournament_entry->fee          = $entry_fee;
            $entry_status                   = $tournament->set_player_entry( $tournament_entry );
            switch ( $entry_status ) {
                case 1:
                    $msg              = __( 'Tournament entered and payment outstanding', 'racketmanager' );
                    $return_link      = '/entry-form/' . seo_url( $tournament->name ) . '-tournament/payment/';
                    $payment_required = true;
                    $message_type     = 'success';
                    break;
                case 2:
                    $msg              = __( 'Tournament entry complete and refund outstanding', 'racketmanager' );
                    $message_type     = 'warning';
                    break;
                case 3:
                    $msg              = __( 'No updates to tournament entry', 'racketmanager' );
                    $message_type     = 'info';
                    break;
                case 4:
                    $msg              = __( 'Tournament entered and payment outstanding for player', 'racketmanager' );
                    $message_type     = 'success';
                    break;
                default:
                    $msg              = __( 'Tournament entry complete', 'racketmanager' );
                    $message_type     = 'success';
                    break;
            }
            array_push( $return, $msg, $message_type, $payment_required, $return_link );
            wp_send_json_success( $return );
        } else {
            $return = $validator;
            $return->msg = __( 'Errors in entry form', 'racketmanager' );
            if ( empty( $return->status ) ) {
                $return->status = 400;
            }
            wp_send_json_error( $return, $return->status );
        }
    }

    /**
     * Validate partner selection
     */
    public function validate_partner(): void {
        $return      = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $validator   = new Validator_Tournament();
            $player_id   = $_POST['playerId'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $partner_id  = $_POST['partnerId'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $season      = $_POST['season'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $event_id    = $_POST['eventId'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $tournament_id = $_POST['tournamentId'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $tournament  = get_tournament( intval( $tournament_id ) );
            $event       = get_event( intval( $event_id ) );
            $validator   = $validator->partner( intval( $partner_id ), $event->id, $event->name, $event, $season, intval( $player_id ), $tournament->date );
        }
        if ( empty( $validator->error ) ) {
            wp_send_json_success();
        } else {
            $return = $validator;
            if ( empty( $return->status ) ) {
                $return->status = 400;
            }
            wp_send_json_error( $return, $return->status );
        }
    }

    /**
     * Tournament Withdrawal modal
     */
    public function tournament_withdrawal(): void {
        $return    = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $tournament_id = $_POST['tournament'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $player_id     = $_POST['player'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $tournament    = get_tournament( intval( $tournament_id ) );
            $entry_key     = $tournament_id . '_' . $player_id;
            $entry         = get_tournament_entry( $entry_key, 'key' );
            if ( ! $entry ) {
                $msg = __( 'Tournament entry not found', 'racketmanager' );
                show_alert( $msg, 'warning' );
                wp_die();
            }
            $output = tournament_withdrawal_modal( $tournament, $tournament_id, $player_id );
            wp_send_json_success( $output );
        } else {
            wp_send_json_error( $return->msg, $return->status );
        }
    }

    /**
     * Confirm Tournament Withdrawal
     */
    public function confirm_tournament_withdrawal(): void {
        $return       = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $tournament_id = $_POST['tournament'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $player_id     = $_POST['player'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $tournament    = get_tournament( intval( $tournament_id ) );
            $entry_key     = $tournament_id . '_' . $player_id;
            $entry         = get_tournament_entry( $entry_key, 'key' );
            if ( ! $entry ) { // no entry exists.
                $msg = __( 'Tournament entry not found', 'racketmanager' );
                show_alert( $msg, 'warning' );
                wp_die();
            }
            $events  = $_POST['event'] ?? array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ( $events as $event_id ) {
                $event    = get_event( intval( $event_id ) );
                $players  = explode( '-', $event->players );
                $withdraw = array_search( strval( $player_id ), $players, true );
                if ( is_numeric( $withdraw ) ) {
                    $tournament->remove_player_entry( intval( $event_id ), intval( $player_id ) );
                }
            }
            $msg = __( 'Tournament entry withdrawn', 'racketmanager' );
            show_alert( $msg, 'success' );
            wp_die();
        } else {
            wp_send_json_error( $return->msg, $return->status );
        }
    }

    /**
     * Load partner modal
     */
    #[NoReturn]
    public function team_partner(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $event_id           = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : 0;
            $player_id          = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
            $modal              = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $gender             = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : null;
            $season             = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            $date_end           = isset( $_POST['dateEnd'] ) ? intval( $_POST['dateEnd'] ) : null;
            $partner_id         = isset( $_POST['partnerId'] ) ? intval( $_POST['partnerId'] ) : null;
            $args               = array();
            $args['player']     = $player_id;
            $args['gender']     = $gender;
            $args['season']     = $season;
            $args['date_end']   = $date_end;
            $args['modal']      = $modal;
            $args['partner_id'] = $partner_id;
            $output             = event_partner_modal( $event_id, $args );
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
     * Update payment information after payment attempt
     *
     * @return void
     */
    public function update_payment(): void {
        $nonce    = 'racketmanager_nonce';
        $action   = 'payment-update';
        $return   = $this->check_security_token( $nonce, $action );
        if ( empty( $return->error ) ) {
            $order_id          = $_POST['orderId'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $intent_id         = $_POST['paymentIntent'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $payment_reference = $_POST['paymentReference'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $invoice           = get_invoice( intval( $order_id ) );
            $invoice->update_payment( $intent_id, $payment_reference );
            wp_send_json_success();
        } else {
            wp_send_json_error( $return->msg, $return->status );
        }
    }

    /**
     * Create payment intent for tournament entry
     *
     * @return void
     */
    public function tournament_payment_create(): void {
        global $racketmanager;
        $msg = null;
        $tournament_entry = null;
        $invoice = null;
        $valid               = true;
        $tournament_entry_id = isset( $_POST['tournament_entry'] ) ? intval( $_POST['tournament_entry'] ) : null;
        $invoice_id          = isset( $_POST['invoiceId'] ) ? intval( $_POST['invoiceId'] ) : null;
        if ( $tournament_entry_id ) {
            $tournament_entry = get_tournament_entry( $tournament_entry_id );
            if ( ! $tournament_entry ) {
                $valid = false;
                $msg   = __( 'Tournament entry not found', 'racketmanager' );
            }
        } else {
            $valid = false;
            $msg   = __( 'Tournament entry id not present', 'racketmanager' );
        }
        if ( $invoice_id ) {
            $invoice = get_invoice( $invoice_id );
            if ( ! $invoice ) {
                $valid = false;
                $msg   = __( 'Payment request not found', 'racketmanager' );
            }
        } else {
            $valid = false;
            $msg   = __( 'Payment request id not found', 'racketmanager' );
        }
        if ( ! $valid ) {
            wp_send_json_error( $msg, '500' );
        }
        $args        = array();
        $player      = get_player( $tournament_entry->player_id );
        $description = $player?->display_name;
        $tournament  = get_tournament( $tournament_entry->tournament_id );
        if ( $tournament ) {
            $description .= ' - ' . $tournament->name;
        }
        $args['description']                 = $description;
        $args['amount']                      = $invoice->amount * 100;
        $args['currency']                    = $racketmanager->currency_code;
        $args['payment_method_types']        = array('card');
        $args['statement_descriptor_suffix'] = $tournament->name;
        $args['receipt_email']               = $player?->email;
        $stripe_details                      = new Stripe_Settings();
        $stripe                              = new StripeClient( $stripe_details->api_secret_key );
        try {
            // Create a PaymentIntent with amount and currency
            $paymentIntent = $stripe->paymentIntents->create( $args );
            if ( $paymentIntent ) {
                $reference = $paymentIntent->id;
                $invoice?->set_payment_reference($reference);
            }
            $client_secret = $paymentIntent->client_secret;
            wp_send_json_success( $client_secret );
        } catch ( ApiErrorException $e ) {
            wp_send_json_error( ['error' => $e->getMessage()], '500' );
        }
    }
}
