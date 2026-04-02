<?php
/**
 * Invoice_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\DTO\Finance\Invoice_Details_DTO;
use Racketmanager\Domain\Invoice;
use Racketmanager\Repositories\Interfaces\Invoice_Repository_Interface;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Invoice repository
 */
class Invoice_Repository implements Invoice_Repository_Interface {

    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_invoices';
    }

    public function find_by_id( int|string|null $id ): ?Invoice {
        if ( ! $id ) {
            return null;
        }
        $invoice = wp_cache_get( $id, 'invoices' );

        if ( ! $invoice ) {
            $invoice = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM `$this->table_name` WHERE `id` = %d LIMIT 1",
                    $id
                )
            );

            if ( ! $invoice ) {
                return null;
            }

            $invoice = new Invoice( $invoice );

            wp_cache_set( $id, $invoice, 'invoices' );
        }

        return $invoice;

    }

    public function find_by( array $criteria ): array {
        $charges_table      = $this->wpdb->prefix . 'racketmanager_charges';
        $competitions_table = $this->wpdb->prefix . 'racketmanager_competitions';
        $clubs_table        = $this->wpdb->prefix . 'racketmanager_clubs';
        $users_table        = $this->wpdb->prefix . 'users';

        $defaults       = array(
            'billable'    => false,
            'status'      => false,
            'charge'      => false,
            'reference'   => false,
            'type'        => false,
            'before'      => false,
            'competition' => false,
            'season'      => false,
        );
        $args           = array_merge( $defaults, $criteria );
        $billable_id    = $args['billable'];
        $status         = $args['status'];
        $charge_id      = $args['charge'];
        $reference      = $args['reference'];
        $type           = $args['type'];
        $before         = $args['before'];
        $competition_id = $args['competition'];
        $season         = $args['season'];

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
        if ( $competition_id ) {
            $search_terms[] = $this->wpdb->prepare( '`competition_id` = %d', $competition_id );
        }
        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( '`season` = %s', $season );
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

    public function save( object $entity ): int|bool {
        /** @var Invoice $entity */
        $data        = array(
            'charge_id'         => $entity->get_charge_id(),
            'billable_id'       => $entity->get_billable_id(),
            'billable_type'     => $entity->get_billable_type(),
            'invoice_number'    => $entity->get_invoice_number(),
            'date'              => $entity->get_date(),
            'date_due'          => $entity->get_date_due(),
            'status'            => $entity->get_status(),
            'amount'            => $entity->get_amount(),
            'payment_reference' => $entity->get_payment_reference(),
            'purchase_order'    => $entity->get_purchase_order(),
            'details'           => $entity->get_details(),
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
        if ( empty( $entity->get_id() ) ) {
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            $entity->set_id( $this->wpdb->insert_id );
            wp_cache_set( $entity->get_id(), $entity, 'invoices' );

            return $result !== false;
        } else {
            wp_cache_set( $entity->get_id(), $entity, 'invoices' );

            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $entity->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            );
        }
    }

    public function delete( int $id ): bool {
        return (bool) $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }

    public function has_invoices( int $charge_id ): bool {
        return $this->wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $this->wpdb->prepare(
                "SELECT count(*) FROM `$this->table_name` WHERE `charge_id` = %d",
                $charge_id
            )
        );

    }

    /**
     * Retrieves the total amount paid by a player for a specific tournament.
     *
     * @param int $player_id
     * @param int $tournament_id
     *
     * @return float
     */
    public function find_tournament_paid_total_by_player( int $player_id, int $tournament_id ): float {
        $tournaments_table = $this->wpdb->prefix . 'racketmanager_tournaments';
        $charges_table     = $this->wpdb->prefix . 'racketmanager_charges';

        $query = $this->wpdb->prepare(
            "SELECT COALESCE(SUM(i.amount), 0)
         FROM `$this->table_name` i
         JOIN `$charges_table` c ON i.charge_id = c.id
         JOIN `$tournaments_table` t ON t.competition_id = c.competition_id AND t.season = c.season
         WHERE t.id = %d
           AND i.billable_id = %d
           AND i.billable_type = 'player'
           AND i.status = 'paid'",
            $tournament_id,
            $player_id
        );

        return (float) $this->wpdb->get_var( $query );
    }

}
