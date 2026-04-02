<?php
/**
 * Tournament_Entry_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Tournament_Entry;
use Racketmanager\Repositories\Interfaces\Tournament_Entry_Repository_Interface;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Tournament Entry repository
 */
class Tournament_Entry_Repository implements Tournament_Entry_Repository_Interface {

    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_tournament_entries';
    }

    public function find_by_id( int|string|null $id ): ?Tournament_Entry {
        if ( ! $id ) {
            return null;
        }
        $tournament_entry = wp_cache_get( $id, 'tournament_entries' );

        if ( ! $tournament_entry ) {
            $tournament_entry = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM `$this->table_name` WHERE `id` = %d LIMIT 1",
                    $id
                )
            );

            if ( ! $tournament_entry ) {
                return null;
            }

            $tournament_entry = new Tournament_Entry( $tournament_entry );

            wp_cache_set( $id, $tournament_entry, 'tournament_entries' );
        }

        return $tournament_entry;

    }

    public function find_by_tournament_and_player( int $tournament_id, int $player_id ): ?Tournament_Entry {
        $tournament_entry_key = $tournament_id . '_' . $player_id;
        $tournament_entry     = wp_cache_get( $tournament_entry_key, 'tournament_entries' );

        if ( ! $tournament_entry ) {
            $tournament_entry = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM `$this->table_name` WHERE `tournament_id` = %d AND `player_id` = %d LIMIT 1",
                    $tournament_id,
                    $player_id
                )
            );

            if ( ! $tournament_entry ) {
                return null;
            }

            $tournament_entry = new Tournament_Entry( $tournament_entry );

            wp_cache_set( $tournament_entry_key, $tournament_entry, 'tournament_entries' );
        }

        return $tournament_entry;

    }

    public function save( object $entity ): int|bool {
        /** @var Tournament_Entry $entity */
        $data        = array(
            'tournament_id' => $entity->get_tournament_id(),
            'player_id'     => $entity->get_player_id(),
            'status'        => $entity->get_status(),
            'fee'           => $entity->get_fee(),
            'club_id'       => $entity->get_club_id(),
        );
        $data_format = array(
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
        );
        if ( empty( $entity->get_id() ) ) {
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            if ( false === $result ) {
                return false;
            }
            $insert_id = $this->wpdb->insert_id;
            $entity->set_id( $insert_id );
            wp_cache_set( $insert_id, $entity, 'tournament_entries' );

            return $insert_id;
        } else {
            wp_cache_set( $entity->get_id(), $entity, 'tournament_entries' );

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

    public function find_by_tournament( int $tournament_id, ?string $status = null ): array {
        $players_table            = $this->wpdb->prefix . 'users';
        $clubs_table              = $this->wpdb->prefix . 'racketmanager_clubs';
        $tournament_entries_table = $this->wpdb->prefix . 'racketmanager_tournament_entries';
        $usermeta_table           = $this->wpdb->prefix . 'usermeta';
        $invoice_table            = $this->wpdb->prefix . 'racketmanager_invoices';
        $charge_table             = $this->wpdb->prefix . 'racketmanager_charges';
        $tournament_table         = $this->wpdb->prefix . 'racketmanager_tournaments';
        $search_terms             = array();

        $invoice_status_subquery = $this->wpdb->prepare(
            "SELECT i.`status`
             FROM `$invoice_table` i
             JOIN `$charge_table` c ON i.charge_id = c.id
             JOIN `$tournament_table` t ON t.competition_id = c.competition_id AND t.season = c.season
             WHERE t.`id` = %d AND i.`billable_id` = te.`player_id`
               AND i.`billable_type` = 'player'
             LIMIT 1",
            $tournament_id
        );

        if ( $status ) {
            if ( 'pending' === $status ) {
                $search_terms[] = 'te.`status` = 0';
            } elseif ( 'unpaid' === $status ) {
                $search_terms[] = 'te.`status` = 2';
                $search_terms[] = "($invoice_status_subquery) != 'paid'";
            } elseif ( 'confirmed' === $status ) {
                $search_terms[] = 'te.`status` = 2';
                $search_terms[] = "($invoice_status_subquery) = 'paid'";
            } elseif ( 'withdrawn' === $status ) {
                $search_terms[] = 'te.`status` = 3';
            }
        }
        $search = Util::search_string( $search_terms );
        $query  = $this->wpdb->prepare(
            "SELECT
            p.ID as id,
            te.player_id AS player_id,
            p.display_name AS player_name,
            p.display_name AS display_name,
            p.user_email AS email,
            um_first.meta_value AS firstname,
            um_last.meta_value AS surname,
            te.club_id AS club_id,
            c.shortcode AS club_name,
            te.status,
            ($invoice_status_subquery) AS invoice_status
         FROM `$tournament_entries_table` te
             JOIN `$players_table` p ON p.ID = te.player_id
             JOIN `$usermeta_table` um_first ON p.ID = um_first.user_id
             JOIN `$usermeta_table` um_last ON p.ID = um_last.user_id
             LEFT JOIN `$clubs_table` c ON c.id = te.club_id
         WHERE te.tournament_id = %d
           AND um_first.meta_key = 'first_name'
           AND um_last.meta_key = 'last_name'
         $search
         ORDER BY p.display_name",
            $tournament_id
        );

        return $this->wpdb->get_results( $query );
    }

}
