<?php
/**
 * AJAX Front end response methods

 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Frontend_Tournament
 */

namespace Racketmanager\ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Ajax;
use Racketmanager\Stripe_Settings;
use Racketmanager\Validator_Entry_Form;
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
                $validator     = $validator->club( $club_id );
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
     * Function to create payment intent onStripe
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
    /**
     * Update payment to complete
     */
    public function update_payment(): void {
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $payment_reference = isset( $_POST['paymentReference'] ) ? sanitize_text_field( wp_unslash( $_POST['paymentReference'] ) ) : null;
            $payment_status    = isset( $_post['paymentStatus'] ) ? sanitize_text_field( wp_unslash( $_POST['paymentStatus'] ) ) : null;
            $stripe = new Stripe_Settings();
            if ( $payment_reference ) {
                $stripe->update_payment( $payment_reference, $payment_status );
            }
        }
    }
    /**
     * Tournament withdrawal modal
     *
     * @return void
     */
    #[NoReturn] public function tournament_withdrawal(): void {
        $output = null;
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $tournament_id = isset( $_POST['tournamentId'] ) ? intval( $_POST['tournamentId'] ) : null;
            $modal         = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
            $output        = tournament_withdrawal_modal( $tournament_id, array( 'modal' => $modal, 'player_id' => $player_id ) );
        }
        if ( ! empty( $return->error ) ) {
            $output = $return->msg;
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }
    /**
     * Tournament withdrawal modal
     *
     * @return void
     */
    public function confirm_tournament_withdrawal(): void {
        $player_id     = null;
        $tournament_id = null;
        $tournament    = null;
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $tournament_id = isset( $_POST['tournamentId'] ) ? intval( $_POST['tournamentId'] ) : null;
            $player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
            if ( ! $tournament_id ) {
                $return->error  = true;
                $return->msg    = __( 'Tournament id not found', 'racketmanager' );
                $return->status = 404;
            }
            if ( ! $player_id ) {
                $return->error  = true;
                $return->msg    = __( 'Player id not found', 'racketmanager' );
                $return->status = 404;
            }
        }
        if ( empty( $return->error ) ) {
            $tournament = get_tournament( $tournament_id );
            if ( ! $tournament ) {
                $return->error  = true;
                $return->msg    = __( 'Tournament not found', 'racketmanager' );
                $return->status = 404;
            }
        }
        if ( ! empty( $return->error ) ) {
            wp_send_json_error( $return, $return->status );
        }
        $refund_amount = $tournament->withdraw_player_entry( $player_id );
        if ( $refund_amount ) {
            $output = __( 'Tournament withdrawal successful and refund will be issued when tournament starts', 'racketmanager' );
        } else {
            $output = __( 'Tournament withdrawal successful', 'racketmanager' );
        }
        wp_send_json_success( $output );
    }
    /**
     * Build screen to show team partner
     */
    public function team_partner(): void {
        $return = $this->check_security_token();
        if ( ! empty( $return->error ) ) {
            wp_send_json_error( $return->msg, $return->status );
        }
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
        wp_send_json_success( $output );
    }
    /**
     * Validate tournament partner function
     */
    public function validate_partner(): void {
        $valid        = true;
        $return       = array();
        $partner_name = null;
        $validator    = new Validator_Entry_Form();
        if ( isset( $_POST['racketmanager_nonce'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'team-partner' ) ) {
                $valid                 = false;
                $validator->err_flds[] = 'partner';
                $validator->err_msgs[] = __( 'Security token invalid', 'racketmanager' );
            }
        } else {
            $valid                 = false;
            $validator->err_flds[] = 'partner';
            $validator->err_msgs[] = __( 'No security token found in request', 'racketmanager' );
        }
        if ( $valid ) {
            $event_id   = isset( $_POST['eventId'] ) ? intval( $_POST['eventId'] ) : 0;
            $modal      = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $player_id  = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
            $partner_id = isset( $_POST['partnerId'] ) ? intval( $_POST['partnerId'] ) : null;
            $season     = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            $date_end   = isset( $_POST['dateEnd'] ) ? intval( $_POST['dateEnd'] ) : null;
            $event      = get_event( $event_id );
            if ( $event ) {
                if ( $partner_id ) {
                    $partner = get_player( $partner_id );
                    if ( $partner ) {
                        $validator = $validator->partner( $partner_id, $event_id, null, $event, $season, $player_id, $date_end );
                        if ( ! $validator->error ) {
                            $partner_name = $partner->display_name;
                        } else {
                            $valid = false;
                        }
                    } else {
                        $valid                 = false;
                        $validator->err_flds[] = 'partner';
                        $validator->err_msgs[] = __( 'Partner not found', 'racketmanager' );
                    }
                } else {
                    $valid                 = false;
                    $validator->err_flds[] = 'partner';
                    $validator->err_msgs[] = __( 'Partner id not found', 'racketmanager' );
                }
            } else {
                $valid                 = false;
                $validator->err_flds[] = 'partner';
                $validator->err_msgs[] = $this->event_not_found;
            }
        }
        if ( $valid ) {
            array_push( $return, $modal, $partner_id, $partner_name, $event_id );
            wp_send_json_success( $return );
        } else {
            $msg = __( 'Error with partner', 'racketmanager' );
            array_push( $return, $msg, $validator->err_msgs, $validator->err_flds );
            wp_send_json_error( $return, '500' );
        }
    }
}
