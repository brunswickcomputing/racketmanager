<?php
/**
 * Invoice API: invoice class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Invoice
 */

namespace Racketmanager;

use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTime;

/**
 * Class to implement the invoice object
 */
final class Invoice {
    /**
     * Id
     *
     * @var int
     */
    public int $id;
    /**
     * Invoice number
     *
     * @var int
     */
    public int $invoice_number;
    /**
     * Club
     *
     * @var object
     */
    public object $club;
    /**
     * Club id
     *
     * @var int|null
     */
    public ? int $club_id;
    /**
     * Player
     *
     * @var object
     */
    public object $player;
    /**
     * Player id
     *
     * @var int|null
     */
    public int|null $player_id;
    /**
     * Charge
     *
     * @var object
     */
    public object $charge;
    /**
     * Charge id
     *
     * @var int
     */
    public int $charge_id;
    /**
     * Status
     *
     * @var string
     */
    public string $status;
    /**
     * Invoice date
     *
     * @var string
     */
    public string $date;
    /**
     * Date due
     *
     * @var string
     */
    public string $date_due;
    /**
     * Amount
     *
     * @var string
     */
    public string $amount;
    /**
     * Payment ref
     *
     * @var string|null
     */
    public string|null $payment_reference;
    /**
     * Purchase order
     *
     * @var string|null
     */
    public string|null $purchase_order;
    /**
     * Details
     *
     * @var object|string|null
     */
    public object|string|null $details;
    /**
     * Racketmanager
     *
     * @var object
     */
    public object $racketmanager;
    /**
     * Get class instance
     *
     * @param int $invoice_id id.
     */
    public static function get_instance(int $invoice_id ) {
        global $wpdb;
        if ( ! $invoice_id ) {
            return false;
        }
        $invoice = wp_cache_get( $invoice_id, 'invoice' );

        if ( ! $invoice ) {
            $invoice = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `id`, `charge_id`, `club_id`, `player_id`, `status`, `invoiceNumber` as `invoice_number`, `date`, `date_due`, `amount`, `payment_reference`, `purchase_order`, `details` FROM $wpdb->racketmanager_invoices WHERE `id` = %d LIMIT 1",
                    $invoice_id
                )
            );  // db call ok.

            if ( ! $invoice ) {
                return false;
            }

            $invoice = new Invoice( $invoice );

            wp_cache_set( $invoice->id, $invoice, 'invoice' );
        }

        return $invoice;
    }

    /**
     * Construct class instance
     *
     * @param object|null $invoice invoice object.
     */
    public function __construct( ?object $invoice = null ) {
        $this->racketmanager = RacketManager::get_instance();
        if ( ! is_null( $invoice ) ) {
            if ( isset( $invoice->details ) ) {
                $invoice->details = json_decode( $invoice->details );
            }
            foreach ( get_object_vars( $invoice ) as $key => $value ) {
                $this->$key = $value;
            }

            if ( ! isset( $this->id ) ) {
                $this->add();
            }
            if ( !empty( $this->club_id ) ) {
                $this->club   = get_club( $this->club_id );
            }
            if ( !empty( $this->player_id ) ) {
                $this->player   = get_player( $this->player_id );
            }
            $this->charge = get_charge( $this->charge_id );
        }
    }

    /**
     * Add new invoice
     */
    private function add(): void {
        global $racketmanager, $wpdb;
        $this->status = 'new';
        $billing      = $racketmanager->get_options( 'billing' );
        if ( $billing ) {
            try {
                $date_due = new DateTime($this->date);
            } catch ( DateMalformedStringException) {
                $date_due = null;
            }
            if ( isset( $billing['paymentTerms'] ) && intval( $billing['paymentTerms'] ) !== 0 ) {
                $date_interval = intval( $billing['paymentTerms'] );
                $date_interval = 'P' . $date_interval . 'D';
                try {
                    $date_due->add(new DateInterval($date_interval));
                } catch ( DateMalformedIntervalStringException) {
                    $date_due = null;
                }
            }
            $this->date_due       = $date_due->format( 'Y-m-d' );
            $this->invoice_number = $billing['invoiceNumber'];
        }
        if ( ! empty( $this->invoice_number ) ) {
            if ( empty( $this->player_id ) ) {
                $result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->prepare(
                        "INSERT INTO $wpdb->racketmanager_invoices (`charge_id`, `club_id`, `status`, `invoiceNumber`, `date`, `date_due`) VALUES (%d, %d, %s, %d, %s, %s)",
                        $this->charge_id,
                        $this->club_id,
                        $this->status,
                        $this->invoice_number,
                        $this->date,
                        $this->date_due
                    )
                );
            } else {
                $result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->prepare(
                        "INSERT INTO $wpdb->racketmanager_invoices (`charge_id`, `player_id`, `status`, `invoiceNumber`, `date`, `date_due`) VALUES (%d, %d, %s, %d, %s, %s)",
                        $this->charge_id,
                        $this->player_id,
                        $this->status,
                        $this->invoice_number,
                        $this->date,
                        $this->date_due
                    )
                );
            }
            if ( $result ) {
                $this->id                  = $wpdb->insert_id;
                $billing['invoiceNumber'] += 1;
                $racketmanager->set_options( 'billing', $billing );
            }
        }
    }
    /**
     * Delete invoice
     */
    public function delete(): void {
        global $wpdb;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_invoices WHERE `id` = %d",
                $this->id
            )
        );
        wp_cache_delete( $this->id, 'invoice' );
    }
    /**
     * Set invoice amount
     *
     * @param string $amount amount value.
     */
    public function set_amount(string $amount ):  bool {
        global $wpdb;
        $this->amount = $amount;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_invoices set `amount` = %d WHERE `id` = %d",
                $this->amount,
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'invoice' );
        return true;
    }
    /**
     * Set invoice status
     *
     * @param string $status status value.
     */
    public function set_status(string $status ): bool {
        global $wpdb;

        if ( 'resent' === $status ) {
            $email = $this->send( $status );
            if ( ! $email ) {
                return false;
            }
        }
        $prev_status  = $this->status ?? '';
        $this->status = $status;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_invoices set `status` = %s WHERE `id` = %d",
                $this->status,
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'invoice' );
        if ( 'paid' === $status && 'paid' !== $prev_status ) {
            $this->send_payment_receipt();
        }
        return true;
    }
    /**
     * Set payment reference status
     *
     * @param string $reference reference value.
     */
    public function set_payment_reference(string $reference ): bool {
        global $wpdb;

        $this->payment_reference = $reference;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_invoices set `payment_reference` = %s WHERE `id` = %d",
                $this->payment_reference,
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'invoice' );
        return true;
    }
    /**
     * Set purchase order
     *
     * @param string $purchase_order purchase order.
     */
    public function set_purchase_order(string $purchase_order ): bool {
        global $wpdb;

        $this->purchase_order = $purchase_order;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_invoices set `purchase_order` = %s WHERE `id` = %d",
                $this->purchase_order,
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'invoice' );
        return true;
    }
    /**
     * Set details
     *
     * @param object $details invoice details.
     */
    public function set_details( object $details ): bool {
        global $wpdb;

        $this->details = $details;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_invoices set `details` = %s WHERE `id` = %d",
                wp_json_encode( $this->details ),
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'invoice' );
        return true;
    }
    /**
     * Send invoice
     *
     * @param boolean $resend resend indicator.
     * return boolean
     */
    public function send( bool $resend = false ): bool {
        global $racketmanager;
        if ( empty( $this->club_id ) ) {
            $email_to = $this->player->display_name . ' <' . $this->player->email . '>';
            $target   = $this->player->display_name;
        } else {
            $email_to = $this->club->match_secretary_name . ' <' . $this->club->match_secretary_email . '>';
            $target   = $this->club->name;
        }
        $billing    = $racketmanager->get_options( 'billing' );
        $headers    = array();
        $from_email = $racketmanager->get_confirmation_email( $this->charge->competition->type );
        if ( $from_email ) {
            $headers[]         = 'From: ' . ucfirst( $this->charge->competition->type ) . 'Secretary <' . $from_email . '>';
            $headers[]         = 'cc: ' . ucfirst( $this->charge->competition->type ) . 'Secretary <' . $from_email . '>';
            $organisation_name = $racketmanager->site_name;
            $headers[]         = 'cc: Treasurer <' . $billing['billingEmail'] . '>';
            $action_url        = $racketmanager->site_url . '/invoice/' . $this->id . '/';
            $email_subject     = $racketmanager->site_name . ' - ' . ucfirst( $this->charge->competition->name ) . ' ' . $this->charge->season . ' Entry Fees Invoice - ' . $target;
            $email_message     = $racketmanager->shortcodes->load_template(
                'send-invoice',
                array(
                    'email_subject' => $email_subject,
                    'action_url'    => $action_url,
                    'organisation'  => $organisation_name,
                    'invoice'       => $this,
                    'resend'        => $resend,
                    'from_email'    => $from_email,
                ),
                'email'
            );
            wp_mail( $email_to, $email_subject, $email_message, $headers );
            return true;
        } else {
            return false;
        }
    }
    /**
     * Send payment receipt email
     *
     * @return bool
     */
    public function send_payment_receipt(): bool {
        global $racketmanager;

        if ( empty( $this->player_id ) && empty( $this->player ) ) {
            return false;
        }

        if ( empty( $this->player ) && ! empty( $this->player_id ) ) {
            $this->player = get_player( $this->player_id );
        }

        if ( empty( $this->player ) || empty( $this->player->email ) ) {
            return false;
        }

        if ( isset( $this->amount ) && (float) $this->amount <= 0 ) {
            return false;
        }

        if ( empty( $this->charge ) && ! empty( $this->charge_id ) ) {
            $this->charge = get_charge( $this->charge_id );
        }

        $season     = $this->charge?->season ?? '';
        $tournament = null;
        if ( ! empty( $this->charge?->competition_id ) && ! empty( $season ) ) {
            $tournament = get_tournament( $this->charge->competition_id . ',' . $season, 'shortcode' );
        }

        $competition_type = $this->charge?->competition?->type ?? ( $tournament ? 'tournament' : '' );
        if ( 'tournament' !== $competition_type && ! $tournament ) {
            return false;
        }

        $tournament_name = $tournament ? $tournament->name : ( $this->charge?->competition?->name ?? __( 'Tournament', 'racketmanager' ) );
        $tournament_link = $tournament ? '<a href="' . esc_url( $racketmanager->site_url . $tournament->link ) . '" style="text-decoration: none; color: #006800;">' . esc_html( $tournament->name ) . '</a>' : esc_html( $tournament_name );
        $action_url      = $racketmanager->site_url . '/invoice/' . $this->id . '/';

        $billing    = $racketmanager->get_options( 'billing' );
        $from_email = $racketmanager->get_confirmation_email( 'tournament' );
        if ( empty( $from_email ) ) {
            $from_email = $racketmanager->admin_email ?? get_option( 'admin_email' );
        }

        $player_name = $this->player->fullname ?? $this->player->display_name;
        $email_to    = $player_name . ' <' . $this->player->email . '>';
        $headers     = array();
        $secretary_title = __( 'Tournament Secretary', 'racketmanager' );
        if ( $from_email ) {
            $from_header = defined( 'RACKETMANAGER_FROM_EMAIL' ) ? RACKETMANAGER_FROM_EMAIL : 'From: ';
            $cc_header   = defined( 'RACKETMANAGER_CC_EMAIL' ) ? RACKETMANAGER_CC_EMAIL : 'cc: ';
            $headers[]   = $from_header . $secretary_title . ' <' . $from_email . '>';
            $headers[]   = $cc_header . $secretary_title . ' <' . $from_email . '>';
        }
        if ( ! empty( $billing['billingEmail'] ) && ( empty( $from_email ) || strtolower( $billing['billingEmail'] ) !== strtolower( $from_email ) ) ) {
            $cc_header = defined( 'RACKETMANAGER_CC_EMAIL' ) ? RACKETMANAGER_CC_EMAIL : 'cc: ';
            $headers[] = $cc_header . 'Treasurer <' . $billing['billingEmail'] . '>';
        }

        $email_subject = $racketmanager->site_name . ' - ' . __( 'Payment Receipt', 'racketmanager' ) . ' - ' . ucfirst( $tournament_name );

        $template_args = array(
            'email_subject'   => $email_subject,
            'tournament_name' => $tournament_name,
            'tournament_link' => $tournament_link,
            'action_url'      => $action_url,
            'organisation'    => $racketmanager->site_name,
            'player'          => $this->player,
            'invoice'         => $this,
            'tournament'      => $tournament,
            'from_email'      => $from_email,
            'contact_email'   => $from_email,
        );

        $email_message = $racketmanager->shortcodes->load_template(
            'tournament-payment-receipt',
            $template_args,
            'email'
        );

        return (bool) wp_mail( $email_to, $email_subject, $email_message, $headers );
    }
}
