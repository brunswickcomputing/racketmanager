<?php

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Club;
use Racketmanager\Domain\Club_Player;
use Racketmanager\Util\Util;
use wpdb;
use function Racketmanager\get_club_player;

class Club_Player_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_club_players';
    }
    public function save( Club_Player $club_player ): void {
        //`id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user`
        if ( $club_player->get_id() === null ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'player_id'      => $club_player->get_player_id(),
                    'club_id'        => $club_player->get_club_id(),
                    'requested_date' => $club_player->get_requested_date(),
                    'requested_user' => $club_player->get_requested_user(),
                    'created_date'   => $club_player->get_created_date(),
                    'created_user'   => $club_player->get_created_user(),
                    'removed_date'   => $club_player->get_removed_date(),
                    'removed_user'   => $club_player->get_removed_user(),
                    'system_record'  => $club_player->get_system_record(),
                ),
                array(
                    '%d', // Format for player_id (int)
                    '%d', // Format for club_id (int)
                    '%s', // Format for requested_date (string)
                    '%d', // Format for requested_user (int)
                    '%s', // Format for created_date (string)
                    '%d', // Format for created_user (int)
                    '%s', // Format for removed_date (string)
                    '%d', // Format for removed_user (int)
                    '%s', // Format for system_record (bool)
                )
            );
            $club_player->set_id( $this->wpdb->insert_id );
        } else {
            // UPDATE: Use wpdb->update with the prepare logic built-in
            $this->wpdb->update(
                $this->table_name,
                array(
                    'player_id'      => $club_player->get_player_id(),
                    'club_id'        => $club_player->get_club_id(),
                    'requested_date' => $club_player->get_requested_date(),
                    'requested_user' => $club_player->get_requested_user(),
                    'created_date'   => $club_player->get_created_date(),
                    'created_user'   => $club_player->get_created_user(),
                    'removed_date'   => $club_player->get_removed_date(),
                    'removed_user'   => $club_player->get_removed_user(),
                    'system_record'  => $club_player->get_system_record(),
                ), // Data to update
                array( 'id' => $club_player->get_id() ),            // Where clause
                array(
                    '%d', // Format for player_id (int)
                    '%d', // Format for club_id (int)
                    '%s', // Format for requested_date (string)
                    '%d', // Format for requested_user (int)
                    '%s', // Format for created_date (string)
                    '%d', // Format for created_user (int)
                    '%s', // Format for removed_date (string)
                    '%d', // Format for removed_user (int)
                    '%s', // Format for system_record (bool)
                ),                                          // Data format
                array( '%d' )                                 // Where format
            );
        }
        wp_cache_flush_group( 'club_players' );
    }

    public function find_by_id( $club_player_id ): ?Club_Player {
        $query = $this->wpdb->prepare("SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user` FROM $this->table_name WHERE id = %d", $club_player_id);
        $row   = $this->wpdb->get_row( $query );
        return $row ? new Club_Player( $row ) : null;
    }
    public function is_player_registered( int $club_id, int $player_id ): bool { // Only checks for 'approved' status
        $query = $this->wpdb->prepare("SELECT COUNT(id) FROM $this->table_name WHERE club_id = %d AND player_id = %d AND `created_date` IS NOT NULL", $club_id, $player_id);
        return (bool) $this->wpdb->get_var( $query );
    }
    public function find_registered_players_by_club_id( int $club_id ): array {
        $query   = $this->wpdb->prepare(
            "SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user` FROM $this->table_name WHERE club_id = %d AND `created_date` IS NOT NULL",
            $club_id
        );
        $results = $this->wpdb->get_results( $query );
        return array_map(
            function( $row ) {
                return new Club_Player( $row );
                },
            $results
        );
    }
    public function find_pending_registrations(): array { // New method for admin view
        $query = "SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user` FROM $this->table_name WHERE `created_date` IS NULL";
        $results = $this->wpdb->get_results( $query) ;
        return array_map(
            function($row) {
                return new Club_Player( $row );
                },
            $results
        );
    }
    public function get_club_players( array $args ): array|int {
        $defaults = array(
            'count'   => false,
            'club'    => false,
            'player'  => false,
            'active'  => false,
            'type'    => false,
            'status'  => false,
            'orderby' => array( 'display_name' => 'ASC' ),
        );
        $args     = array_merge( $defaults, $args );
        $count    = $args['count'];
        $type     = $args['type'];
        $club     = $args['club'];
        $player   = $args['player'];
        $active   = $args['active'];
        $status   = $args['status'];
        $orderby  = $args['orderby'];

        $search_terms = array();
        if ( $club ) {
            $search_terms[] = $this->wpdb->prepare( '`club_id` = %d', intval( $club ) );
        }

        if ( $player ) {
            $search_terms[] = $this->wpdb->prepare( '`player_id` = %d', intval( $player ) );
        }

        if ( $type ) {
            $search_terms[] = '`system_record` IS NULL';
        }

        if ( $active ) {
            $search_terms[] = '`removed_date` IS NULL';
        }
        switch( $status ) {
            case 'outstanding':
                $search_terms[] = '`created_date` IS NULL';
                break;
            case 'all':
            default:
                break;
        }
        $search = Util::search_string( $search_terms, true );
        if ( $count ) {
            $sql = "SELECT COUNT(ID) FROM $this->table_name " . $search;
            return $this->wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
        }
        $order        = Util::order_by_string( $orderby );
        $sql          = "SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user` FROM $this->table_name " . $search . $order;
        $club_players = wp_cache_get( md5( $sql ), 'club_players' );
        if ( ! $club_players ) {
            $club_players = $this->wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $club_players, 'club_players' );
        }
        return array_map(
            function($row) {
                return new Club_Player( $row );
            },
            $club_players
        );
    }
    public function find_by_player( int $player_id, string $status = 'active' ): array {
        $search = null;
        if ( 'active' === $status ) {
            $search = ' AND `removed_date` IS NULL';
        }
        $query   = $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE player_id = %d $search", $player_id );
        $results = $this->wpdb->get_results( $query );
        return array_map(
            function( $row ) {
                return new Club_Player( $row );
            },
            $results
        );
    }
    public function find_by_club_and_player( int $club_id, int $player_id): ?Club_Player {
        $query = $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE club_id = %d AND player_id = %d AND `removed_date` IS NULL", $club_id, $player_id );
        $row   = $this->wpdb->get_row( $query) ;
        return $row ? new Club_Player( $row ) : null;
    }

    public function find( $club_player_id ): ?Club_Player {
        $row = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $club_player_id ) );
        return $row ? new Club_Player( $row ) : null;
    }
}
