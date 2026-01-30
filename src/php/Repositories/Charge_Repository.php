<?php
/**
 * Charge_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Charge;
use Racketmanager\Domain\DTO\Charge_Details_DTO;
use Racketmanager\Domain\DTO\Charges_With_Totals_DTO;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Charge repository
 */
class Charge_Repository {

    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_charges';
    }

    public function save( Charge $charge ): int|bool {
        $data        = array(
            'competition_id'  => $charge->get_competition_id(),
            'season'          => $charge->get_season(),
            'date'            => $charge->get_date(),
            'status'          => $charge->get_status(),
            'fee_competition' => $charge->get_fee_competition(),
            'fee_event'       => $charge->get_fee_event(),
        );
        $data_format = array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
        );
        if ( empty( $charge->get_id() ) ) {
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            $charge->set_id( $this->wpdb->insert_id );
            wp_cache_set( $charge->get_id(), $charge, 'charges' );

            return $result !== false;
        } else {
            wp_cache_set( $charge->get_id(), $charge, 'charges' );

            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $charge->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            );
        }
    }

    public function find_by_id( null|int|string $charge_id ): ?Charge {
        if ( ! $charge_id ) {
            return null;
        }
        if ( is_numeric( $charge_id ) ) {
            $search = $this->wpdb->prepare(
                '`id` = %d',
                intval( $charge_id )
            );
        } else {
            $search_terms   = explode( '_', $charge_id );
            $competition_id = $search_terms[0];
            $season         = $search_terms[1];
            $search         = $this->wpdb->prepare(
                '`competition_id` = %d AND `season` = %s',
                intval( $competition_id ),
                $season,
            );
        }
        $charge = wp_cache_get( $charge_id, 'charges' );

        if ( ! $charge ) {
            $charge = $this->wpdb->get_row(
                "SELECT * FROM `$this->table_name` WHERE $search LIMIT 1",
            );

            if ( ! $charge ) {
                return null;
            }

            $charge = new Charge( $charge );

            wp_cache_set( $charge->id, $charge, 'charges' );
        }

        return $charge;

    }

    public function find_by_id_with_details( int $charge_id ): ?Charge_Details_DTO {
        if ( ! $charge_id ) {
            return null;
        }
        $competition_table = $this->wpdb->prefix . 'racketmanager_competitions';
        $charge            = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "
                SELECT c.*,
                       ct.`name` as `competition_name`,
                       ct.`type` as `competition_type`
                FROM `$this->table_name` c
                    INNER JOIN `$competition_table` ct ON c.competition_id = ct.id
                WHERE c.`id` = %d
                LIMIT 1",
                $charge_id
            )
        );
        if ( $charge ) {
            return new Charge_Details_DTO( $charge );
        } else {
            return null;
        }

    }

    public function find_by( array $criteria ): array {
        $competitions_table = $this->wpdb->prefix . 'racketmanager_competitions';
        $invoices_table     = $this->wpdb->prefix . 'racketmanager_invoices';
        $defaults           = array(
            'competition' => false,
            'season'      => false,
            'status'      => false,
            'entry'       => false,
            'orderby'     => array(
                'season'         => 'ASC',
                'competition_id' => 'ASC',
            ),
        );
        $args               = array_merge( $defaults, $criteria );
        $competition        = $args['competition'];
        $season             = $args['season'];
        $status             = $args['status'];
        $entry              = $args['entry'];
        $orderby            = $args['orderby'];
        $search_terms       = array();
        if ( $competition ) {
            $search_terms[] = $this->wpdb->prepare( '`competition_id` = %d', $competition );
        }
        if ( $season ) {
            $search_terms[] = $this->wpdb->prepare( '`season` = %d', $season );
        }
        if ( $status ) {
            $search_terms[] = $this->wpdb->prepare( '`status` = %s', $status );
        }
        switch ( $entry ) {
            case 'team':
                $search_terms[] = "`competition_id` IN (SELECT `id` FROM `$competitions_table` WHERE type IN ('league','cup'))";
                break;
            case 'player':
                $search_terms[] = "`competition_id` IN (SELECT `id` FROM `$competitions_table` WHERE type IN ('tournament'))";
                break;
            default:
                break;
        }
        $search  = Util::search_string( $search_terms, true );
        $order   = Util::order_by_string( $orderby );
        $results = $this->wpdb->get_results(
            "
            SELECT c.`id`, ct.`name`, c.`season`, c.`status`, c.`fee_competition`, c.`fee_event`, COALESCE( inv_totals.`total_value`, 0 ) AS total_invoice_value
            FROM `$this->table_name` c
            INNER JOIN `$competitions_table` ct ON ct.id = c.competition_id
            LEFT JOIN (SELECT `charge_id`, SUM(`amount`) AS `total_value` FROM `$invoices_table` GROUP BY `charge_id`) AS `inv_totals` ON c.`id` = inv_totals.`charge_id`
                $search
                $order
                "
        );

        return array_map(
            function ( $row ) {
                return new Charges_With_Totals_DTO( $row );
            },
            $results
        );
    }

    public function delete( int $charge_id ): int|bool {
        return $this->wpdb->delete( $this->table_name, array( 'id' => $charge_id ), array( '%d' ) );
    }

}
