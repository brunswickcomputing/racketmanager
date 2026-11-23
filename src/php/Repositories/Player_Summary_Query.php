<?php
/**
 * Player summary query builder and executor.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use wpdb;

/**
 * Builds, executes, and caches player summary queries.
 */
class Player_Summary_Query {
    private const string META_KEY_GENDER = 'gender';
    private const string META_KEY_BTM    = 'btm';

    /**
     * WordPress database instance.
     *
     * @var wpdb
     */
    private wpdb $wpdb;

    /**
     * Initialise the query with a WordPress database connection.
     *
     * Uses the provided database object when supplied, otherwise falls back to the global WordPress database instance.
     *
     * @param wpdb|null $db Optional WordPress database connection.
     */
    public function __construct( ?wpdb $db = null ) {
        global $wpdb;
        $this->wpdb = $db ?? $wpdb;
    }

    /**
     * Execute player summaries query matching the supplied filters.
     *
     * Supports filtering to active players only, searching by BTM number when the
     * name argument is numeric, or searching by display name when it is a string.
     * Results are cached using the normalised argument set.
     *
     * @param array $args Optional arguments used to filter player summaries.
     *
     * @return array
     */
    public function execute( array $args = array() ): array {
        $defaults = array(
            'active' => false,
            'name'   => false,
        );
        $args     = array_merge( $defaults, $args );
        $active   = (bool) $args['active'];
        $name     = $args['name'];

        $cache_key = 'players_summary_' . md5( (string) wp_json_encode( $args ) );
        $cached    = wp_cache_get( $cache_key, 'players' );
        if ( false !== $cached && null !== $cached ) {
            return $cached;
        }

        $user_table     = $this->wpdb->base_prefix . 'users';
        $usermeta_table = $this->wpdb->base_prefix . 'usermeta';

        $where_clauses = array( '1=1' );
        $params        = array();
        $order_by      = 'u.display_name ASC';

        if ( $active ) {
            $rubber_players_table     = $this->wpdb->prefix . 'racketmanager_rubber_players';
            $tournament_entries_table = $this->wpdb->prefix . 'racketmanager_tournament_entries';
            $where_clauses[]          = "u.ID IN (SELECT DISTINCT player_id FROM $rubber_players_table UNION SELECT DISTINCT player_id FROM $tournament_entries_table)";
            $order_by                 = 'u.ID ASC';
        } elseif ( ! empty( $name ) ) {
            if ( is_numeric( $name ) ) {
                $where_clauses[] = "u.ID IN (SELECT user_id FROM $usermeta_table WHERE meta_key = %s AND meta_value = %s)";
                $params[]        = self::META_KEY_BTM;
                $params[]        = (string) $name;
            } else {
                $where_clauses[] = 'u.display_name LIKE %s';
                $params[]        = '%' . $this->esc_like( (string) $name ) . '%';

                $where_clauses[] = "u.ID IN (SELECT user_id FROM $usermeta_table WHERE meta_key = %s AND meta_value IN ('M', 'F'))";
                $params[]        = self::META_KEY_GENDER;
            }
        } else {
            $where_clauses[] = "u.ID IN (SELECT user_id FROM $usermeta_table WHERE meta_key = %s AND meta_value IN ('M', 'F'))";
            $params[]        = self::META_KEY_GENDER;
        }

        $where_sql = implode( ' AND ', $where_clauses );

        $sql = "
            SELECT
                u.ID AS player_id,
                u.display_name,
                u.user_nicename,
                u.user_email AS email,
                MAX(CASE WHEN m.meta_key = 'first_name' THEN m.meta_value END) AS firstName,
                MAX(CASE WHEN m.meta_key = 'last_name' THEN m.meta_value END) AS surname,
                MAX(CASE WHEN m.meta_key = '" . self::META_KEY_BTM . "' THEN m.meta_value END) AS btm,
                MAX(CASE WHEN m.meta_key = '" . self::META_KEY_GENDER . "' THEN m.meta_value END) AS gender,
                MAX(CASE WHEN m.meta_key = 'contactno' THEN m.meta_value END) AS contactno
            FROM $user_table u
            LEFT JOIN $usermeta_table m ON u.ID = m.user_id AND m.meta_key IN ('first_name', 'last_name', '" . self::META_KEY_BTM . "', '" . self::META_KEY_GENDER . "', 'contactno')
            WHERE $where_sql
            GROUP BY u.ID, u.display_name, u.user_nicename, u.user_email
            ORDER BY $order_by
        ";

        if ( ! empty( $params ) ) {
            $sql = $this->wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        $rows    = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $players = array();
        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $players[] = new Players_List_DTO( $row );
            }
        }

        wp_cache_set( $cache_key, $players, 'players' );
        return $players;
    }

    /**
     * Escape special characters for use in a SQL LIKE clause.
     *
     * @param string $text Text to escape.
     *
     * @return string Escaped text safe for a LIKE pattern.
     */
    private function esc_like( string $text ): string {
        return method_exists( $this->wpdb, 'esc_like' )
            ? $this->wpdb->esc_like( $text )
            : addcslashes( $text, '_%\\' );
    }
}
