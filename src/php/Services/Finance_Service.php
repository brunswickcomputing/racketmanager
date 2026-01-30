<?php
/**
 * Finance_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTime;
use Racketmanager\Domain\Charge;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\DTO\Charge_Details_DTO;
use Racketmanager\Domain\DTO\Invoice_Full_Details_DTO;
use Racketmanager\Domain\Invoice;
use Racketmanager\Exceptions\Charge_Not_Deleted_Exception;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Charge_Not_Updated_Exception;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Created_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Updated_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Invoice_Repository;
use Racketmanager\Services\Validator\Validator_Finance;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Messages;
use stdClass;
use WP_Error;

/**
 * Class to implement the Finance Management Service
 */
class Finance_Service {
    private RacketManager $racketmanager;
    private Charge_Repository $charge_repository;
    private Invoice_Repository $invoice_repository;
    private Club_Repository $club_repository;
    private Competition_Service $competition_service;
    private Player_Service $player_service;

    /**
     * Constructor
     *
     */
    public function __construct( RacketManager $plugin_instance, Charge_Repository $charge_repository, Invoice_Repository $invoice_repository, Club_Repository $club_repository, Competition_Service $competition_service, Player_Service $player_service ) {
        $this->racketmanager       = $plugin_instance;
        $this->charge_repository   = $charge_repository;
        $this->invoice_repository  = $invoice_repository;
        $this->club_repository     = $club_repository;
        $this->competition_service = $competition_service;
        $this->player_service      = $player_service;
    }

    public function get_charge( $charge_id, bool $enhanced = false ): null|Charge|Charge_Details_DTO {
        if ( $enhanced ) {
            $charge = $this->charge_repository->find_by_id_with_details( $charge_id );
        } else {
            $charge = $this->charge_repository->find_by_id( $charge_id );
        }
        if ( ! $charge ) {
            throw new Charge_Not_Found_Exception( Util_Messages::charge_not_found( $charge_id ) );
        }

        return $charge;
    }

    public function get_charges_by_criteria( array $criteria ): array {
        return $this->charge_repository->find_by( $criteria );
    }

    public function remove_charge( $charge_id ): int {
        $charge = $this->charge_repository->find_by_id( $charge_id );
        if ( ! $charge ) {
            throw new Charge_Not_Found_Exception( Util_Messages::charge_not_found( $charge_id ) );
        }
        if ( $this->invoice_repository->has_invoices( $charge_id ) ) {
            throw new Charge_Not_Deleted_Exception( Util_Messages::charge_has_invoices( $charge_id ) );
        }

        return $this->charge_repository->delete( $charge_id );
    }

    public function add_charge( stdClass $new_charge ): Charge|int|WP_Error {
        $validator = $this->validate_charge( $new_charge );
        if ( is_wp_error( $validator ) ) {
            return $validator;
        }
        $charge  = new Charge( $new_charge );
        $updates = $this->charge_repository->save( $charge );
        if ( $updates ) {
            return $charge;
        } else {
            return $updates;
        }
    }

    private function validate_charge( stdClass $charge, ?int $charge_id = null ): bool|WP_Error {
        $validator = new Validator_Finance();
        if ( $charge_id ) {
            $validator = $validator->compare( $charge->id, $charge_id, 'competition' );
        }
        $validator = $validator->competition( $charge->competition_id );
        $validator = $validator->season( $charge->season );
        $validator = $validator->status( $charge->status );
        $validator = $validator->date( $charge->date );
        if ( ! $validator->error ) {
            $key             = $charge->competition_id . '_' . $charge->season;
            $existing_charge = $this->charge_repository->find_by_id( $key );
            if ( $existing_charge && ( empty( $charge->id ) || $charge->id !== $existing_charge->id ) ) {
                $error_field   = 'competition';
                $error_message = __( 'Charge already exists', 'racketmanager' );
                $status        = 400;
                $validator->set_errors( $error_field, $error_message, $status );
            }
        }
        if ( $validator->error ) {
            return $validator->err;
        }

        return true;
    }

    public function amend_charge( stdClass $updated_charge, int $charge_id ): Charge|int|WP_Error {
        $charge = $this->charge_repository->find_by_id( $updated_charge->id );
        if ( ! $charge ) {
            throw new Charge_Not_Found_Exception( Util_Messages::charge_not_found( $updated_charge->id ) );
        }
        $validator = $this->validate_charge( $updated_charge, $charge_id );
        if ( is_wp_error( $validator ) ) {
            return $validator;
        }
        $charge->set_competition_id( $updated_charge->competition_id );
        $charge->set_season( $updated_charge->season );
        $charge->set_date( $updated_charge->date );
        $charge->set_status( $updated_charge->status );
        $charge->set_fee_competition( $updated_charge->fee_competition );
        $charge->set_fee_event( $updated_charge->fee_event );
        $updates = $this->charge_repository->save( $charge );
        if ( $updates ) {
            return $charge;
        } else {
            throw new Charge_Not_Updated_Exception( Util_Messages::no_updates() );
        }
    }

    public function send_invoices_for_charge( ?int $charge_id, bool $clear = false ): bool {
        $charge = $this->charge_repository->find_by_id( $charge_id );
        if ( ! $charge ) {
            throw new Charge_Not_Found_Exception( Util_Messages::charge_not_found( $charge_id ) );
        }
        if ( $clear ) {
            $schedule_name   = 'rm_send_invoices';
            $schedule_args[] = $charge_id;
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
        }
        $sent         = false;
        $club_charges = $this->get_charges_for_clubs( $charge_id );
        foreach ( $club_charges as $entry ) {
            try {
                $invoice = $this->add_invoice( $charge, $entry, 'club' );
            } catch ( Invoice_Not_Created_Exception $e ) {
                throw new Invoice_Not_Created_Exception( $e->getMessage() );
            }
            try {
                $result = $this->send_invoice( $invoice->get_id() );
                $sent   = $result;
            } catch ( Invoice_Not_Found_Exception ) {
                continue;
            }
            if ( $sent ) {
                $invoice->set_status( 'sent' );
            }
        }

        return $sent;
    }

    public function get_charges_for_clubs( int $charge_id ): array {
        $charge = $this->charge_repository->find_by_id( $charge_id );
        if ( ! $charge ) {
            throw new Charge_Not_Found_Exception( Util_Messages::charge_not_found( $charge_id ) );
        }
        $club_entries = array();
        $clubs        = $this->club_repository->find_all();
        foreach ( $clubs as $club ) {
            $club_entry = $this->get_club_entry( $charge, $club );
            if ( $club_entry ) {
                $club_entries[] = $club_entry;
            }
        }

        return $club_entries;
    }

    /**
     * Get club entries for charges
     *
     * @param Charge $charge
     * @param Club $club club.
     *
     * @return false|object
     */
    public function get_club_entry( Charge $charge, Club $club ): false|object {
        $club_teams  = 0;
        $club_events = array();
        $events      = $this->competition_service->get_events_for_competition( $charge->get_competition_id() );
        foreach ( $events as $event ) {
            $teams     = $this->competition_service->get_teams_for_event( $event->get_id(), $charge->get_season(), $club->id );
            $num_teams = count( $teams );
            if ( $num_teams > 0 ) {
                $club_event        = new stdClass();
                $club_event->type  = $event->type;
                $club_event->count = $num_teams;
                $club_event->fee   = $charge->fee_event * $num_teams;
                $club_events[]     = $club_event;
            }
            $club_teams += $num_teams;
        }
        if ( $club_teams > 0 ) {
            $club_entry                  = new stdClass();
            $club_entry->id              = $club->id;
            $club_entry->name            = $club->name;
            $club_entry->num_teams       = $club_teams;
            $club_entry->fee_competition = $charge->fee_competition;
            $club_entry->fee_events      = $charge->fee_event * $club_teams;
            $club_entry->fee             = $club_entry->fee_competition + $club_entry->fee_events;
            $club_entry->events          = $club_events;

            return $club_entry;
        } else {
            return false;
        }
    }

    public function add_invoice( Charge $charge, stdClass $entry, string $type ): Invoice|bool {
        $billing = $this->racketmanager->get_options( 'billing' );
        if ( ! $billing ) {
            throw new Invoice_Not_Created_Exception( Util_Messages::no_billing_details() );
        }
        try {
            $date_due = new DateTime( $charge->get_date() );
        } catch ( DateMalformedStringException ) {
            $date_due = null;
        }
        if ( ! empty( $billing['paymentTerms'] ) && 'club' === $type ) {
            $date_interval = intval( $billing['paymentTerms'] );
            $date_interval = 'P' . $date_interval . 'D';
            try {
                $date_due->add( new DateInterval( $date_interval ) );
            } catch ( DateMalformedIntervalStringException ) {
                $date_due = null;
            }
        }
        $invoice = new Invoice();
        $invoice->set_charge_id( $charge->get_id() );
        $invoice->set_billable_id( $entry->id );
        $invoice->set_billable_type( $type );
        $invoice->set_date( $charge->get_date() );
        $invoice->set_amount( $entry->fee );
        $invoice->set_details( $entry );
        if ( $date_due ) {
            $invoice->set_date_due( $date_due->format( 'Y-m-d' ) );
        } else {
            $invoice->set_date_due( $invoice->get_date() );
        }
        $invoice->set_invoice_number( $billing['invoiceNumber'] );
        $updates = $this->invoice_repository->save( $invoice );
        if ( $updates ) {
            $billing['invoiceNumber'] += 1;
            $this->racketmanager->set_options( 'billing', $billing );

            return $invoice;
        } else {
            return $updates;
        }
    }

    public function send_invoice( int $invoice_id, $resend = false ): bool {
        $invoice = $this->get_full_invoice_details( $invoice_id );
        if ( ! $invoice ) {
            throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found( $invoice_id ) );
        }
        $email_to              = $invoice->contact->display_name . ' <' . $invoice->contact->email . '>';
        $competition_type      = $invoice->competition_type;
        $billing               = $this->racketmanager->get_options( 'billing' );
        $headers               = array();
        $from_email            = $this->racketmanager->get_confirmation_email( $competition_type );
        $competition_secretary = ucfirst( $competition_type ) . __( 'Secretary', 'racketmanager' ) . ' <' . $from_email . '>';
        if ( $from_email ) {
            $headers[]         = 'From: ' . $competition_secretary;
            $headers[]         = 'cc: ' . $competition_secretary;
            $organisation_name = $this->racketmanager->site_name;
            $headers[]         = 'cc: ' . __( 'Treasurer', 'racketmanager' ) . ' <' . $billing['billingEmail'] . '>';
            $action_url        = $this->racketmanager->site_url . '/invoice/' . $invoice->invoice->get_id() . '/';
            $email_subject     = $this->racketmanager->site_name . ' - ' . ucfirst( $invoice->charge_name ) . ' ' . __( 'Entry Fees Invoice', 'racketmanager' ) . ' - ' . $invoice->billable_name;
            $email_message     = $this->racketmanager->shortcodes->load_template(
                'send-invoice',
                array(
                    'email_subject' => $email_subject,
                    'action_url'    => $action_url,
                    'organisation'  => $organisation_name,
                    'invoice'       => $invoice,
                    'resend'        => $resend,
                    'from_email'    => $from_email,
                    'addressee'     => $invoice->contact->display_name,
                ),
                'email'
            );
            wp_mail( $email_to, $email_subject, $email_message, $headers );

            return true;
        } else {
            return false;
        }
    }

    public function get_full_invoice_details( ?int $invoice_id ): ?Invoice_Full_Details_DTO {
        $invoice = $this->invoice_repository->find_by_id( $invoice_id );
        if ( ! $invoice ) {
            throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found( $invoice_id ) );
        }
        $billable_name    = '';
        $billable_address = '';
        $contact          = '';
        if ( 'club' === $invoice->billable_type ) {
            try {
                $contact = $this->player_service->get_match_secretary_details( $invoice->billable_id );
            } catch ( Role_Assignment_Not_Found_Exception ) {
                $contact = null;
            }
            try {
                $club             = $this->club_repository->find( $invoice->billable_id );
                $billable_name    = $club->get_shortcode();
                $billable_address = $club->get_address();
            } catch ( Club_Not_Found_Exception ) {
                $billable_name    = null;
                $billable_address = null;
            }
        } elseif ( 'player' === $invoice->billable_type ) {
            $billable_address = null;
            try {
                $player        = $this->player_service->get_player( $invoice->billable_id );
                $billable_name = $player->get_fullname();
                $contact       = $player;
            } catch ( Player_Not_Found_Exception ) {
                $billable_name = null;
                $contact       = null;
            }
        }
        $charge = $this->charge_repository->find_by_id_with_details( $invoice->charge_id );

        return new Invoice_Full_Details_DTO( $invoice, $billable_name, $billable_address, $contact, $charge );
    }

    public function get_invoice( $invoice_id ): ?Invoice {
        $invoice = $this->invoice_repository->find_by_id( $invoice_id );
        if ( ! $invoice ) {
            throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found( $invoice_id ) );
        }

        return $invoice;
    }

    public function get_invoice_by_charge_and_club( ?int $charge_id, ?int $club_id ): ?Invoice {
        $invoice = $this->invoice_repository->find_by_charge_and_billable( $charge_id, $club_id );
        if ( ! $invoice ) {
            throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found() );
        }

        return $invoice;
    }

    public function get_invoices_by_criteria( array $criteria ): array {
        return $this->invoice_repository->find_by( $criteria );
    }

    public function amend_invoice( ?int $invoice_id, ?string $status, ?string $purchase_order ): Invoice|WP_Error {
        $invoice = $this->invoice_repository->find_by_id( $invoice_id );
        if ( ! $invoice ) {
            throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found( $invoice_id ) );
        }
        $validator = $this->validate_invoice( $invoice_id, $status );
        if ( is_wp_error( $validator ) ) {
            return $validator;
        }
        $invoice->set_status( $status );
        $invoice->set_purchase_order( $purchase_order );
        $updates = $this->invoice_repository->save( $invoice );
        if ( $updates ) {
            return $invoice;
        } else {
            throw new Invoice_Not_Updated_Exception( Util_Messages::no_updates() );
        }
    }

    private function validate_invoice( int $invoice_id, ?string $status ): bool|WP_Error {
        $validator = new Validator_Finance();
        if ( 'resend' === $status ) {
            $sent = $this->send_invoice( $invoice_id );
            if ( ! $sent ) {
                $error_field   = 'status';
                $error_message = __( 'Error on invoice resend', 'racketmanager' );
                $status        = 400;
                $validator->set_errors( $error_field, $error_message, $status );
            }
        }
        if ( $validator->error ) {
            return $validator->err;
        }

        return true;
    }

    public function action_invoice( ?int $invoice_id, ?string $action ): string {
        $invoice = $this->invoice_repository->find_by_id( $invoice_id );
        if ( ! $invoice ) {
            throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found( $invoice_id ) );
        }
        if ( ! $action ) {
            throw new Invalid_Argument_Exception( Util_Messages::missing_action_parameter() );
        }
        $result = match ( $action ) {
            'resend' => $this->send_invoice( $invoice_id, true ),
            'delete' => $this->remove_invoice( $invoice_id ),
            default => $this->invoice_status_change( $invoice, $action ),
        };
        if ( true === $result ) {
            if ( 'resend' === $action ) {
                $msg = sprintf( __( 'Invoice %d resent', 'racketmanager' ), $invoice->get_id() );
            } else {
                $msg = sprintf( __( 'Invoice %d deleted', 'racketmanager' ), $invoice->get_id() );
            }
        } elseif ( false === $result ) {
            if ( 'resend' === $action ) {
                $msg = sprintf( __( 'Invoice %d resend failed', 'racketmanager' ), $invoice->get_id() );
            } else {
                $msg = sprintf( __( 'Invoice %d not deleted', 'racketmanager' ), $invoice->get_id() );
            }
        } else {
            $msg = $result;
        }

        return $msg;
    }

    public function remove_invoice( $invoice_id ): int {
        $invoice = $this->invoice_repository->find_by_id( $invoice_id );
        if ( ! $invoice ) {
            throw new Invoice_Not_Found_Exception( Util_Messages::invoice_not_found( $invoice_id ) );
        }

        return $this->invoice_repository->delete( $invoice_id );
    }

    private function invoice_status_change( Invoice $invoice, bool $status ): string {
        $valid_status = array( 'draft', 'final', 'paid', 'cancelled' );
        if ( ! in_array( $status, $valid_status, true ) ) {
            return Util_Messages::invalid_parameter( $status );
        }
        $invoice->set_status( $status );
        $result = $this->invoice_repository->save( $invoice );
        if ( $result ) {
            $msg = sprintf( __( 'Invoice %d status updated', 'racketmanager' ), $invoice->get_id() );
        } else {
            $msg = sprintf( __( 'Invoice %d not updated', 'racketmanager' ), $invoice->get_id() );
        }

        return $msg;
    }

    public function set_charge_used( string $key ): bool {
        $charge = $this->charge_repository->find_by_id( $key );
        if ( ! $charge ) {
            throw new Charge_Not_Found_Exception( Util_Messages::charge_not_found( $key ) );
        }
        if ( 'final' === $charge->get_status() ) {
            return false;
        }
        $charge->set_status( 'final' );

        return $this->charge_repository->save( $charge );
    }
}
