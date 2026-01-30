<?php
/**
 * Invoice_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\DTO\Invoice_Details_DTO;
use Racketmanager\Domain\Invoice;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Invoice repository
 */
class Invoice_Repository {

    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_invoices';
    }

    public function find_by_id( null|int $invoice_id ): ?Invoice {
        if ( ! $invoice_id ) {
            return null;
        }
        $invoice = wp_cache_get( $invoice_id, 'invoices' );

        if ( ! $invoice ) {
            $invoice = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM `$this->table_name` WHERE `id` = %d LIMIT 1",
                    $invoice_id
                )
            );

            if ( ! $invoice ) {
                return null;
            }

            $invoice = new Invoice( $invoice );

            wp_cache_set( $invoice_id, $invoice, 'invoices' );
        }

        return $invoice;

    }

    public function find_by( array $criteria ): array {
        $charges_table      = $this->wpdb->prefix . 'racketmanager_charges';
        $competitions_table = $this->wpdb->prefix . 'racketmanager_competitions';
        $clubs_table        = $this->wpdb->prefix . 'racketmanager_clubs';
        $users_table        = $this->wpdb->prefix . 'users';

        $defaults    = array(
            'billable'  => false,
            'status'    => false,
            'charge'    => false,
            'reference' => false,
            'type'      => false,
            'before'    => false,
        );
        $args        = array_merge( $defaults, $criteria );
        $billable_id = $args['billable'];
        $status      = $args['status'];
        $charge_id   = $args['charge'];
        $reference   = $args['reference'];
        $type        = $args['type'];
        $before      = $args['before'];

        $search_terms = array();
        if ( $billable_id ) {
            $search_terms[] = $this->wpdb->prepare( '`billable_id` = %d', $billable_id );
        }
        if ( $status ) {
            if ( 'open' === $status ) {
                $search_terms[] = "i.`status` != ('paid')";
            } elseif ( 'overdue' === $status ) {
                $search_terms[] = "(i.`status` != ('paid') AND `date_due` < CURDATE())";
            } else {
                $search_terms[] = $this->wpdb->prepare( 'i.`status` = %s', $status );
            }
        }
        if ( $charge_id ) {
            $search_terms[] = $this->wpdb->prepare( '`charge_id` = %d', $charge_id );
        }
        if ( $reference ) {
            $search_terms[] = $this->wpdb->prepare( '`payment_reference` = %s', $reference );
        }
        switch ( $type ) {
            case 'club':
                $search_terms[] = "`billable_type` = 'club'";
                break;
            case 'player':
                $search_terms[] = "`billable_type` = 'player'";
                break;
            default:
                break;
        }
        if ( $before ) {
            $search_terms[] = $this->wpdb->prepare( '`id` < %d', $before );
        }
        $search  = Util::search_string( $search_terms, true );
        $results = $this->wpdb->get_results(
            "
                SELECT i.*,
                       c.`season`,
                       cmp.`name` as `competition_name`,
                       cmp.`type` as `competition_type`,
                       CASE
                           WHEN i.billable_type = 'club' THEN (SELECT shortcode FROM `$clubs_table` WHERE id = i.billable_id)
                           WHEN i.billable_type = 'player' THEN (SELECT display_name FROM `$users_table` WHERE ID = i.billable_id)
                           END AS billable_name
                FROM `$this->table_name` i
                     INNER JOIN `$charges_table` c ON i.charge_id = c.id
                     INNER JOIN `$competitions_table` cmp ON c.competition_id = cmp.id
                    $search
                order by `invoice_number`"
        );

        return array_map(
            function ( $row ) {
                return new Invoice_Details_DTO( $row );
            },
            $results
        );
    }

    public function find_by_charge_and_billable( ?int $charge_id, ?int $billable_id, string $billable_type = 'club' ): ?Invoice {
        if ( ! $charge_id || ! $billable_id ) {
            return null;
        }
        $cache_key = $charge_id . '_' . $billable_id . '_' . $billable_type;
        $invoice   = wp_cache_get( $cache_key, 'invoices' );

        if ( ! $invoice ) {
            $invoice = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM `$this->table_name` WHERE `billable_id` = %d AND `billable_type` = %s AND `charge_id` = %d LIMIT 1",
                    $billable_id,
                    $billable_type,
                    $charge_id
                )
            );

            if ( ! $invoice ) {
                return null;
            }

            $invoice = new Invoice( $invoice );

            wp_cache_set( $cache_key, $invoice, 'invoices' );
        }

        return $invoice;

    }

    public function save( Invoice $invoice ): int|bool {
        $data        = array(
            'charge_id'         => $invoice->get_charge_id(),
            'billable_id'       => $invoice->get_billable_id(),
            'billable_type'     => $invoice->get_billable_type(),
            'invoice_number'    => $invoice->get_invoice_number(),
            'date'              => $invoice->get_date(),
            'date_due'          => $invoice->get_date_due(),
            'status'            => $invoice->get_status(),
            'amount'            => $invoice->get_amount(),
            'payment_reference' => $invoice->get_payment_reference(),
            'purchase_order'    => $invoice->get_purchase_order(),
            'details'           => $invoice->get_details(),
        );
        $data_format = array(
            '%d',
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
        );
        if ( empty( $invoice->get_id() ) ) {
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            $invoice->set_id( $this->wpdb->insert_id );
            wp_cache_set( $invoice->get_id(), $invoice, 'invoices' );

            return $result !== false;
        } else {
            wp_cache_set( $invoice->get_id(), $invoice, 'invoices' );

            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $invoice->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            );
        }
    }

    public function delete( int $invoice_id ): int|bool {
        return $this->wpdb->delete( $this->table_name, array( 'id' => $invoice_id ), array( '%d' ) );
    }

    public function has_invoices( int $charge_id ): bool {
        return $this->wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $this->wpdb->prepare(
                "SELECT count(*) FROM `$this->table_name` WHERE `charge_id` = %d",
                $charge_id
            )
        );

    }

}
